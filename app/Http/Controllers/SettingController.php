<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\MailSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/** Pengaturan SMTP (admin) + kirim email tes. Sandi SMTP tersimpan terenkripsi. */
class SettingController extends Controller
{
    public function smtp(Request $request): View
    {
        $s = Setting::smtp();

        return view('pengaturan-smtp', [
            'values' => [
                'smtp_enabled' => $s['smtp_enabled'] === '1',
                'smtp_host' => $s['smtp_host'] ?? '',
                'smtp_port' => (string) ($s['smtp_port'] ?? '587'),
                'smtp_username' => $s['smtp_username'] ?? '',
                'smtp_has_password' => ($s['smtp_password'] ?? '') !== '',
                'smtp_encryption' => $s['smtp_encryption'] ?? 'tls',
                'smtp_from_email' => $s['smtp_from_email'] ?? '',
                'smtp_from_name' => $s['smtp_from_name'] ?? 'SIM Puskesmas Akelamo',
            ],
            'enabled' => MailSender::enabled(),
            'testSent' => session('test_result'),
            'status' => session('status'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_enabled' => ['nullable', 'accepted'],
            'smtp_host' => ['nullable', 'string', 'max:190'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:190'],
            'smtp_password' => ['nullable', 'string', 'max:500'],
            'smtp_encryption' => ['nullable', 'in:none,tls,ssl'],
            'smtp_from_email' => ['nullable', 'email:rfc', 'max:150'],
            'smtp_from_name' => ['nullable', 'string', 'max:120'],
        ]);

        Setting::set('smtp_enabled', isset($data['smtp_enabled']) ? '1' : '0', $request->user()->id);
        Setting::set('smtp_host', trim((string) ($data['smtp_host'] ?? '')), $request->user()->id);
        Setting::set('smtp_port', (string) ($data['smtp_port'] ?? '587'), $request->user()->id);
        Setting::set('smtp_username', trim((string) ($data['smtp_username'] ?? '')), $request->user()->id);
        // Sandi kosong = pertahankan yang lama (tidak dikirim ulang ke browser).
        if (! empty($data['smtp_password'])) {
            Setting::set('smtp_password', $data['smtp_password'], $request->user()->id);
        }
        Setting::set('smtp_encryption', $data['smtp_encryption'] ?? 'tls', $request->user()->id);
        Setting::set('smtp_from_email', trim((string) ($data['smtp_from_email'] ?? '')), $request->user()->id);
        Setting::set('smtp_from_name', trim((string) ($data['smtp_from_name'] ?? '')) ?: 'SIM Puskesmas Akelamo', $request->user()->id);

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'UPDATE', 'entity' => 'settings/smtp', 'record_id' => null]);

        return back()->with('status', 'Pengaturan SMTP tersimpan (sandie tersimpan terenkripsi).');
    }

    /** Kirim email tes ke alamat mana pun. */
    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_email' => ['required', 'email:rfc', 'max:150'],
        ]);

        $html = view('emails.notifikasi', [
            'title' => 'Email Uji Coba Berhasil 🎉',
            'body' => 'Ini email percobaan dari SIM Puskesmas Akelamo. Bila Anda menerima email ini, pengaturan SMTP sudah benar dan notifikasi transaksi/resEP akan terkirim ke email staf.',
            'url' => null,
        ])->render();

        $error = MailSender::send($data['test_email'], '[SIM Akelamo] Uji Coba SMTP', $html);

        AuditLog::tryCreate(['actor_id' => $request->user()->id, 'action' => 'TEST_SMTP', 'entity' => 'settings/smtp', 'record_id' => null]);
        Log::info('Uji SMTP oleh '.$request->user()->username.' → '.($error ?? 'sukses'));

        return back()->with('test_result', $error ?? ('Email tes terkirim ke '.$data['test_email'].' — periksa kotak masuk (dan folder spam).'));
    }
}
