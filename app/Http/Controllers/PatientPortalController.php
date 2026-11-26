<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Support\MailSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal pasien (login wajib, role: pasien):
 * - melihat profil & nomor rekam medis sendiri,
 * - melihat jadwal dokter,
 * - mendaftar berobat online (tanggal, dokter, keluhan),
 * - memantau status & membatalkan antrian yang masih menunggu.
 */
class PatientPortalController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $request->user()->patient;

        $schedules = Schedule::with('doctor:id,name,specialty')
            ->orderByRaw("CASE day WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END")
            ->orderBy('time_start')
            ->get(['id', 'doctor_id', 'day', 'time_start', 'time_end', 'room'])
            ->map(fn ($s) => [
                'doctor' => $s->doctor?->name ?? '-',
                'specialty' => $s->doctor?->specialty ?? '-',
                'day' => $s->day,
                'time' => substr((string) $s->time_start, 0, 5).'–'.substr((string) $s->time_end, 0, 5),
                'room' => $s->room ?: 'Poli',
            ]);

        $appointments = $patient
            ? Appointment::with('doctor:id,name,specialty')
                ->where('patient_id', $patient->id)->orderByDesc('id')->limit(20)->get()
            : collect();

        return view('akun-pasien', [
            'patient' => $patient,
            'schedules' => $schedules->groupBy('day'),
            'appointments' => $appointments,
            'doctors' => Doctor::orderBy('name')->get(['id', 'name', 'specialty']),
            'success' => session('status'),
        ]);
    }

    /** Daftar berobat online — antrian baru berstatus "Menunggu". */
    public function book(Request $request): RedirectResponse
    {
        $patient = $request->user()->patient;
        if ($patient === null) {
            return back()->withErrors(['berobat' => 'Rekam medis Anda belum tertaut. Hubungi petugas pendaftaran.']);
        }

        $data = $request->validate([
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'visit_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->toDateString()],
            'complaint' => ['nullable', 'string', 'max:500'],
        ], [
            'visit_date.after_or_equal' => 'Tanggal kunjungan minimal hari ini.',
            'visit_date.required' => 'Tanggal kunjungan wajib diisi.',
        ]);

        // Maksimal satu antrian aktif per hari untuk pasien yang sama.
        $exists = Appointment::where('patient_id', $patient->id)
            ->where('visit_date', $data['visit_date'])
            ->whereIn('status', ['Menunggu', 'Dikonfirmasi'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['berobat' => 'Anda sudah memiliki antrian pada tanggal tersebut.'])->withInput();
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $data['doctor_id'] ?? null,
            'user_id' => $request->user()->id,
            'visit_date' => $data['visit_date'],
            'complaint' => $data['complaint'] ?? null,
            'status' => 'Menunggu',
        ]);

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'DAFTAR_BEROBAT', 'entity' => 'appointments', 'record_id' => $appointment->id]);

        $dokter = $appointment->doctor?->name ?? 'poli umum';
        $tanggal = $appointment->visit_date->locale('id')->translatedFormat('d F Y');
        $title = 'Pendaftaran berobat online: '.$patient->full_name;
        AppNotification::add('pasien', $title, "Kunjungan {$tanggal} ke {$dokter} — menunggu konfirmasi petugas.", '/modul/appointments', $request->user()->id);
        MailSender::notifyRoles(['admin', 'kepala', 'petugas', 'perawat'], $title, "Pasien {$patient->full_name} (RM {$patient->medical_record_no}) mendaftar berobat online pada {$tanggal} ke {$dokter}.", '/modul/appointments');

        return redirect()->route('pasien.home')->with('status', 'Pendaftaran berobat berhasil dikirim. Silakan datang sesuai tanggal & tunggu konfirmasi petugas.');
    }

    /** Batalkan antrian sendiri — hanya bila masih berstatus Menunggu. */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $patient = $request->user()->patient;
        $appointment = Appointment::where('patient_id', $patient?->id)->findOrFail($id);

        if ($appointment->status !== 'Menunggu') {
            return back()->withErrors(['berobat' => 'Antrian yang sudah dikonfirmasi/selesai tidak dapat dibatalkan online.']);
        }

        $appointment->update(['status' => 'Batal']);
        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'BATAL_BEROBAT', 'entity' => 'appointments', 'record_id' => $appointment->id]);

        return redirect()->route('pasien.home')->with('status', 'Antrian berhasil dibatalkan.');
    }
}
