<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['full_name', 'username', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'kepala', 'dokter', 'perawat', 'petugas', 'keuangan', 'pasien'];

    /** Peran dengan akses penuh ke SELURUH halaman & fitur (kepala puskesmas & admin). */
    public const FULL_ACCESS = ['admin', 'kepala'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed', // bcrypt, cost 12 (lihat AppServiceProvider)
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Kepala puskesmas & admin — melihat semua halaman dan fitur. */
    public function isFullAccess(): bool
    {
        return in_array($this->role, self::FULL_ACCESS, true);
    }

    /** Rekam medis yang tertaut ke akun pasien ini (bila ada). */
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }
}
