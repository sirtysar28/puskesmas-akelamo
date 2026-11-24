<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /** Whitelist kunci pengaturan yang dikenal aplikasi → tipe datanya. */
    public const KEYS = [
        'smtp_enabled' => 'boolean',
        'smtp_host' => 'string',
        'smtp_port' => 'integer',
        'smtp_username' => 'string',
        'smtp_password' => 'secret',   // disimpan terenkripsi (AES-256 via APP_KEY)
        'smtp_encryption' => 'string', // none | tls | ssl
        'smtp_from_email' => 'string',
        'smtp_from_name' => 'string',
    ];

    protected $fillable = ['key', 'value', 'updated_by'];

    /** Ambil nilai (didekripsi otomatis bila kunci rahasia). */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (! array_key_exists($key, self::KEYS)) {
            return $default; // whitelist — kunci tak dikenal tidak pernah tersimpan
        }

        $row = static::query()->where('key', $key)->first('value');
        if ($row === null || $row->value === null || $row->value === '') {
            return $default;
        }

        return self::KEYS[$key] === 'secret' ? Crypt::decryptString($row->value) : $row->value;
    }

    /** Simpan nilai (terenkripsi otomatis bila kunci rahasia). */
    public static function set(string $key, ?string $value, ?int $userId = null): void
    {
        if (! array_key_exists($key, self::KEYS)) {
            return;
        }
        if ($value !== null && $value !== '' && self::KEYS[$key] === 'secret') {
            $value = Crypt::encryptString($value);
        }
        if ($value === '') {
            $value = null;
        }
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $userId]
        );
    }

    /** Seluruh pengaturan SMTP sebagai array asosiatif siap pakai. */
    public static function smtp(): array
    {
        $out = [];
        foreach (array_keys(self::KEYS) as $key) {
            $out[$key] = static::get($key);
        }

        return $out;
    }
}
