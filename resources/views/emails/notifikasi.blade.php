<!doctype html>
<html lang="id">
<body style="margin:0;padding:0;background:#eef4ef;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef4ef;padding:24px 12px;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dde8e0;">

        <!-- Header -->
        <tr>
            <td style="background:linear-gradient(135deg,#0b7a45,#0a5c34);background-color:#0b7a45;padding:26px 32px;">
                <table role="presentation" width="100%"><tr>
                    <td style="color:#ffffff;font-size:19px;font-weight:bold;letter-spacing:.5px;">✚ SIM Puskesmas Akelamo</td>
                    <td align="right" style="color:#bfe8d2;font-size:11px;">SMART HEALTH SYSTEM</td>
                </tr></table>
            </td>
        </tr>

        <!-- Isi -->
        <tr>
            <td style="padding:30px 34px 10px;">
                <p style="margin:0 0 6px;font-size:11px;font-weight:bold;color:#0b7a45;letter-spacing:1.2px;">NOTIFIKASI SISTEM</p>
                <h1 style="margin:0 0 14px;font-size:20px;color:#123526;">{{ $title }}</h1>
                <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#3c5348;">{{ $body }}</p>
                @if ($url ?? null)
                    <a href="{{ $url }}" style="display:inline-block;background:#0b7a45;color:#ffffff;text-decoration:none;font-size:13px;font-weight:bold;padding:11px 22px;border-radius:9px;">Buka di Aplikasi &rarr;</a>
                @endif
            </td>
        </tr>

        <!-- Info -->
        <tr>
            <td style="padding:22px 34px 30px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3faf5;border:1px solid #dcefe3;border-radius:12px;">
                    <tr><td style="padding:14px 18px;font-size:12px;color:#57766a;line-height:1.6;">
                        Email ini dikirim otomatis oleh Sistem Informasi Manajemen Puskesmas Akelamo.
                        Waktu: {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WIT.
                        Jangan membalas email ini.
                    </td></tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background:#0a4d2d;padding:16px 34px;color:#9fd4b4;font-size:11px;">
                &copy; {{ date('Y') }} Puskesmas Akelamo &middot; Developed by
                <a href="https://ciptakaryadigital.id" style="color:#c9f0d8;">Cipta Karya Digital</a>
            </td>
        </tr>
    </table>
</td></tr></table>
</body>
</html>
