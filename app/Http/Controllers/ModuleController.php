<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Support\MailSender;
use App\Support\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD generik untuk semua modul data.
 * Nama modul TIDAK PERNAH berasal dari input pengguna ke level SQL —
 * seluruh pemetaan lewat ModuleRegistry (whitelist) dan Eloquent
 * (prepared statements), sehingga kebal SQL injection.
 */
class ModuleController extends Controller
{
    /** Halaman tabel data modul. */
    public function index(Request $request, string $module): View
    {
        $def = $this->definition($module);
        $this->authorizeRead($request, $def);

        $rows = $this->queryRows($def['model'], $module);

        return view('modules', [
            'module' => $module,
            'def' => $def,
            'rows' => $rows,
            'canEdit' => in_array($request->user()->role, $def['roles'], true),
            'doctors' => $module === 'schedules'
                ? Doctor::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /** Ambil data terbaru (maks 500) dengan transformasi tampilan. */
    private function queryRows(string $modelClass, string $module)
    {
        $query = $modelClass::query();

        if ($module === 'schedules') {
            $query->with('doctor:id,name')->orderByDesc('id')->limit(500);
        } else {
            $query->orderByDesc('id')->limit(500);
        }

        return $query->get()->map(fn (Model $m) => $this->present($m, $module));
    }

    /** Bentuk array aman untuk tampilan (field terbatas, tanpa leluasa ke mass-assignment). */
    private function present(Model $m, string $module): array
    {
        $data = $m->toArray();
        $data['id'] = $m->id;

        if ($module === 'schedules') {
            $data['doctor_name'] = $m->doctor?->name ?? 'Dokter #'.$m->doctor_id;
            $data['time_start'] = substr((string) $data['time_start'], 0, 5);
            $data['time_end'] = substr((string) $data['time_end'], 0, 5);
        }
        foreach (['birth_date', 'expiry_date', 'transaction_date'] as $dateKey) {
            if (array_key_exists($dateKey, $data) && $data[$dateKey] !== null) {
                $data[$dateKey] = substr((string) $data[$dateKey], 0, 10);
            }
        }

        return $data;
    }

    /** Simpan data baru. */
    public function store(Request $request, string $module): JsonResponse
    {
        $def = $this->definition($module);
        $this->authorizeWrite($request, $def);

        $values = $this->validated($request, $def);
        $model = $def['model']::create($values);

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'CREATE', 'entity' => $module, 'record_id' => $model->id]);
        $this->notify($module, $model, $request->user()->id);

        return response()->json(['ok' => true, 'id' => $model->id], 201);
    }

    /** Perbarui data. */
    public function update(Request $request, string $module, int $id): JsonResponse
    {
        $def = $this->definition($module);
        $this->authorizeWrite($request, $def);

        $model = $def['model']::findOrFail($id);
        $model->update($this->validated($request, $def));

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'UPDATE', 'entity' => $module, 'record_id' => $id]);

        return response()->json(['ok' => true]);
    }

    /** Hapus data (permanen, tercatat di audit log). */
    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        $def = $this->definition($module);
        $this->authorizeWrite($request, $def);

        $model = $def['model']::findOrFail($id);
        $model->delete();

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'DELETE', 'entity' => $module, 'record_id' => $id]);

        return response()->json(['ok' => true]);
    }

    // ---------------------------------------------------------------------

    /** Notifikasi in-app + email untuk peristiwa penting (transaksi keuangan dll). */
    private function notify(string $module, Model $model, int $actorId): void
    {
        if ($module === 'finances' && $model instanceof \App\Models\Finance) {
            $nominal = 'Rp '.number_format((float) $model->amount, 0, ',', '.');
            $title = 'Transaksi '.$model->type.' '.$nominal;
            $body = "Kategori \"{$model->category}\" telah dicatat pada {$model->transaction_date->format('d/m/Y')}"
                .($model->description ? " — {$model->description}" : '').'.';

            AppNotification::add('keuangan', $title, $body, '/modul/finances', $actorId);
            MailSender::notifyRoles(['admin', 'keuangan'], $title, $body, '/modul/finances');
        }
    }

    // ---------------------------------------------------------------------

    private function definition(string $module): array
    {
        $def = ModuleRegistry::find($module);
        abort_if($def === null, 404, 'Menu tidak ditemukan.');

        return $def;
    }

    private function authorizeRead(Request $request, array $def): void
    {
        abort_unless(in_array($request->user()->role, $def['readRoles'], true), 403, 'Akses modul ini dibatasi.');
    }

    private function authorizeWrite(Request $request, array $def): void
    {
        abort_unless(in_array($request->user()->role, $def['roles'], true), 403, 'Akses modul ini dibatasi.');
    }

    /** Validasi ketat sesuai whitelist rule per modul. */
    private function validated(Request $request, array $def): array
    {
        return $request->validate($def['rules']);
    }
}
