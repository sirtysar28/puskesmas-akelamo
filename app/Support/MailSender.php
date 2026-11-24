<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim email berbasis pengaturan SMTP dinamis (tabel settings),
 * tanpa perlu config:cache ulang. Memakai Symfony Mailer langsung.
 * Semua kegagalan dicatat & tidak pernah melempar exception ke pemanggil.
 */
final class MailSender
{
    /** Konfigurasi SMTP aktif & lengkap? */
    public static function enabled(): bool
    {
        $s = Setting::smtp();

        return $s['smtp_enabled'] === '1'
            && $s['smtp_host'] !== null && $s['smtp_host'] !== ''
            && $s['smtp_from_email'] !== null && filter_var($s['smtp_from_email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Kirim email HTML ke satu penerima.
     * @return string|null pesan error bila gagal (null = sukses)
     */
    public static function send(string $to, string $subject, string $html): ?string
    {
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return 'Alamat email penerima tidak valid.';
        }
        if (! self::enabled()) {
            return 'SMTP belum aktif / belum dikonfigurasi.';
        }

        try {
            $s = Setting::smtp();
            $scheme = $s['smtp_encryption'] === 'ssl' ? 'smtps' : 'smtp';
            $dsn = new \Symfony\Component\Mailer\Transport\Dsn(
                $scheme,
                (string) $s['smtp_host'],
                $s['smtp_username'] !== null && $s['smtp_username'] !== '' ? $s['smtp_username'] : null,
                $s['smtp_password'] !== null && $s['smtp_password'] !== '' ? $s['smtp_password'] : null,
                (int) ($s['smtp_port'] ?? 587),
            );

            $factory = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory();
            $transport = $factory->create($dsn);
            $mailer = new \Symfony\Component\Mailer\Mailer($transport);

            $email = (new \Symfony\Component\Mime\Email())
                ->from(new \Symfony\Component\Mime\Address((string) $s['smtp_from_email'], (string) ($s['smtp_from_name'] ?: 'SIM Puskesmas Akelamo')))
                ->to($to)
                ->subject($subject)
                ->html($html)
                ->text(strip_tags(preg_replace('#<br\s*/?>#i', "\n", $html)));

            $mailer->send($email);

            return null;
        } catch (\Throwable $e) {
            Log::warning('SMTP gagal: '.$e->getMessage());

            return 'Gagal mengirim: '.$e->getMessage();
        }
    }

    /**
     * Kirim email notifikasi ke semua staf berperan tertentu yang punya email.
     * Konten dari view emails.notifikasi (HTML branding).
     *
     * @param array<int,string> $roles contoh ['admin','keuangan']
     */
    public static function notifyRoles(array $roles, string $title, string $body, ?string $url = null): void
    {
        if (! self::enabled()) {
            return; // tanpa SMTP — notifikasi in-app saja sudah tercatat
        }

        $recipients = User::whereIn('role', $roles)->whereNotNull('email')->where('is_active', true)->pluck('email');
        if ($recipients->isEmpty()) {
            return;
        }

        $html = view('emails.notifikasi', [
            'title' => $title,
            'body' => $body,
            'url' => $url !== null ? url($url) : null,
        ])->render();

        foreach ($recipients->unique() as $to) {
            self::send((string) $to, '[SIM Akelamo] '.$title, $html);
        }
    }
}
