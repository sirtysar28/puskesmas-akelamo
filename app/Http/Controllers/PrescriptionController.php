<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Support\MailSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Resep — integrasi dokter ↔ pasien ↔ farmasi (admin/petugas).
 * - Dokter membuat resep (pilih pasien + obat + dosis).
 * - Admin/petugas farmasi menyerahkan resep (stok obat berkurang otomatis).
 */
class PrescriptionController extends Controller
{
    private const DISPENSERS = ['admin', 'kepala', 'petugas', 'perawat'];

    private const WRITERS = ['admin', 'kepala', 'dokter'];

    public function index(Request $request): View
    {
        $role = $request->user()->role;
        abort_unless(in_array($role, ['admin', 'kepala', 'dokter', 'perawat', 'petugas'], true), 403, 'Menu resep untuk staf puskesmas.');

        $q = Prescription::with(['patient:id,medical_record_no,full_name', 'doctor:id,name,specialty', 'creator:id,full_name', 'items.medicine:id,name,unit'])
            ->orderByDesc('id')->limit(300);

        // Dokter hanya melihat resep yang dibuat sendiri.
        if ($role === 'dokter') {
            $q->where('user_id', $request->user()->id);
        }

        $filterPatient = (int) $request->query('pasien', 0);
        if ($filterPatient > 0) {
            $q->where('patient_id', $filterPatient);
        }

        return view('resep', [
            'list' => $q->get(),
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'medical_record_no', 'full_name']),
            'medicines' => Medicine::whereColumn('stock', '>', 0)
                ->orderBy('name')->limit(300)->get(['id', 'code', 'name', 'unit', 'stock']),
            'canWrite' => in_array($role, self::WRITERS, true),
            'canDispense' => in_array($role, self::DISPENSERS, true),
            'filterPatient' => $filterPatient,
        ]);
    }

    /** Simpan resep baru (dokter/admin): pasien + daftar obat + dosis. */
    public function store(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role, self::WRITERS, true), 403, 'Hanya dokter/admin yang dapat menulis resep.');

        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:8'],
            'items.*.medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'items.*.dose' => ['required', 'string', 'max:140'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'items.required' => 'Tambahkan minimal satu obat pada resep.',
            'items.min' => 'Tambahkan minimal satu obat pada resep.',
        ]);

        $rx = DB::transaction(function () use ($data, $request) {
            // Validasi stok cukup & obat valid, dalam transaksi.
            $meds = Medicine::whereIn('id', array_column($data['items'], 'medicine_id'))->get()->keyBy('id');

            $rx = Prescription::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $this->guessDoctorId($request->user()->full_name),
                'user_id' => $request->user()->id,
                'status' => 'aktif',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $med = $meds->get($item['medicine_id']);
                abort_if($med === null, 422, 'Obat tidak ditemukan.');
                abort_if($med->stock < $item['quantity'], 422, "Stok {$med->name} tidak cukup (tersisa {$med->stock}).");

                $rx->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'dose' => $item['dose'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $rx->load('patient:id,medical_record_no,full_name');
        });

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'CREATE', 'entity' => 'prescriptions', 'record_id' => $rx->id]);

        $nama = $rx->patient?->full_name ?? 'pasien';
        AppNotification::add(
            'resep',
            'Resep baru untuk '.$nama,
            'Resep #'.$rx->id.' menunggu penyerahan obat di farmasi.',
            '/resep',
            $request->user()->id
        );
        MailSender::notifyRoles(['admin', 'kepala', 'petugas', 'perawat'], 'Resep baru untuk '.$nama, 'Resep #'.$rx->id.' menunggu penyerahan obat di farmasi.', '/resep');

        return response()->json(['ok' => true, 'id' => $rx->id, 'rm' => $rx->patient?->medical_record_no], 201);
    }

    /** Tandai resep diserahkan → stok obat berkurang otomatis (admin/petugas). */
    public function dispense(Request $request, int $id): JsonResponse
    {
        abort_unless(in_array($request->user()->role, self::DISPENSERS, true), 403, 'Hanya petugas farmasi/admin yang dapat menyerahkan obat.');

        $rx = Prescription::with('items.medicine')->where('status', 'aktif')->findOrFail($id);

        DB::transaction(function () use ($rx) {
            foreach ($rx->items as $item) {
                $med = $item->medicine;
                abort_if($med === null, 422, 'Obat sudah tidak tersedia.');
                abort_if($med->stock < $item->quantity, 422, "Stok {$med->name} tinggal {$med->stock} (butuh {$item->quantity}).");
                $med->decrement('stock', $item->quantity);
            }
            $rx->update(['status' => 'diserahkan']);
        });

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'DISPENSE', 'entity' => 'prescriptions', 'record_id' => $id]);

        AppNotification::add(
            'resep',
            'Resep #'.$id.' diserahkan',
            'Obat telah diserahkan ke pasien & stok dikurangi otomatis.',
            '/resep',
            $request->user()->id
        );

        return response()->json(['ok' => true]);
    }

    /** Cocokkan akun dokter ke data dokter berdasarkan nama (bila ada). */
    private function guessDoctorId(string $fullName): ?int
    {
        return Doctor::where('name', $fullName)->value('id');
    }
}
