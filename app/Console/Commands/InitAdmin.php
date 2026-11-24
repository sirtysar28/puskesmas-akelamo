<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Membuat akun administrator pertama.
 *
 * Penggunaan:
 *   ADMIN_USERNAME=adminakelamo ADMIN_PASSWORD='PasswordKuat12Plus' ADMIN_NAME='Administrator' \
 *     php artisan akelamo:init-admin
 *
 * atau interaktif: php artisan akelamo:init-admin
 *
 * Tidak ada akun demo / password bawaan di source control.
 */
class InitAdmin extends Command
{
    protected $signature = 'akelamo:init-admin';

    protected $description = 'Membuat akun administrator pertama SIM Puskesmas Akelamo';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->warn('Akun admin sudah ada. Gunakan halaman Pengguna & Akses untuk menambah admin lain.');

            return self::SUCCESS;
        }

        $username = env('ADMIN_USERNAME') ?? $this->ask('Username admin (huruf, angka, titik, garis bawah, strip)');
        $name = env('ADMIN_NAME') ?? $this->ask('Nama lengkap admin');
        $password = env('ADMIN_PASSWORD') ?? $this->secret('Kata sandi admin (minimal 12 karakter)');

        $validator = Validator::make(
            compact('username', 'name', 'password'),
            [
                'name' => ['required', 'string', 'max:150'],
                'username' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,username'],
                'password' => ['required', 'string', Password::min(12)],
            ],
            [
                'username.regex' => 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip.',
                'password.min' => 'Kata sandi minimal 12 karakter.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'full_name' => $name,
            'username' => $username,
            'role' => 'admin',
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info('✅ Akun administrator berhasil dibuat. Segera login dan ganti kata sandi bila perlu.');
        $this->warn('⚠️  Hapus variabel ADMIN_PASSWORD dari lingkungan/sejarah shell Anda.');

        return self::SUCCESS;
    }
}
