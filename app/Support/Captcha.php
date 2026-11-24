<?php

namespace App\Support;

/**
 * CAPTCHA 5 karakter — dirender sebagai SVG murni (tanpa dependensi eksternal).
 *
 * - Hanya huruf besar & angka yang tidak ambigu (tanpa I, O, 0, 1).
 * - Kode disimpan di session sebagai hash SHA-256 (bukan plaintext)
 *   supaya tidak bisa dibaca dari payload session yang bocor.
 * - Perbandingan memakai hash_equals (timing-safe), case-insensitive,
 *   dan sekali pakai (hapus setelah diverifikasi).
 */
final class Captcha
{
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public const LENGTH = 5;

    /** Buat kode captcha baru & simpan hash-nya di session. */
    public static function issue(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }

        session()->put('captcha_hash', hash('sha256', strtolower($code)));

        return $code;
    }

    /** Verifikasi jawaban pengguna (sekali pakai). */
    public static function verify(?string $answer): bool
    {
        $hash = session()->pull('captcha_hash'); // pull = hapus setelah dibaca

        if (! is_string($hash) || $answer === null || $answer === '') {
            return false;
        }

        return hash_equals($hash, hash('sha256', strtolower(trim($answer))));
    }

    /** Render SVG captcha dengan distorsi & noise. */
    public static function svg(?string $code = null): string
    {
        $code ??= self::issue();

        $w = 190;
        $h = 64;
        $parts = [];

        // Garis noise
        for ($i = 0; $i < 5; $i++) {
            $opacity = 0.18 + random_int(0, 12) / 100;
            $parts[] = sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#%s" stroke-width="%d" opacity="%.2f"/>',
                random_int(0, $w), random_int(0, $h),
                random_int(0, $w), random_int(0, $h),
                ['9fb8a8', '7ea893', '5f8f77'][random_int(0, 2)],
                random_int(1, 2), $opacity
            );
        }

        // Titik noise
        for ($i = 0; $i < 26; $i++) {
            $parts[] = sprintf(
                '<circle cx="%d" cy="%d" r="1" fill="#8fae9d" opacity="%.2f"/>',
                random_int(0, $w), random_int(0, $h), 0.25 + random_int(0, 40) / 100
            );
        }

        // Karakter: rotasi & offset acak per karakter
        for ($i = 0; $i < strlen($code); $i++) {
            $x = 20 + $i * 32 + random_int(-3, 3);
            $y = (int) ($h / 2 + 12 + random_int(-6, 6));
            $rotate = random_int(-22, 22);
            $color = ['0b6e41', '0e8b50', '125333', '0a7a48'][$i % 4];

            $parts[] = sprintf(
                '<text x="%d" y="%d" font-family="Georgia,\'Times New Roman\',serif" font-size="%d" font-weight="700" fill="#%s" transform="rotate(%d %d %d)">%s</text>',
                $x, $y, random_int(29, 34), $color, $rotate, $x, $y, htmlspecialchars($code[$i], ENT_XML1)
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="Kode captcha">%s%s</svg>',
            $w, $h, $w, $h,
            '<rect width="100%" height="100%" fill="#f2f9f4"/>',
            implode('', $parts)
        );
    }
}
