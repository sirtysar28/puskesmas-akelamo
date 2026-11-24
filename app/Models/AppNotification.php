<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $fillable = ['type', 'title', 'body', 'url', 'actor_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    /**
     * Buat notifikasi tanpa pernah menggagalkan proses utama.
     * `$url` HARUS path internal relatif (mis. "/modul/finances") —
     * divalidasi agar tidak bisa dipakai menyelipkan link eksternal.
     */
    public static function add(string $type, string $title, ?string $body = null, ?string $url = null, ?int $actorId = null): void
    {
        try {
            if ($url !== null && ! preg_match('#^/[A-Za-z0-9\-_/]*$#', $url)) {
                $url = null;
            }
            self::create([
                'type' => $type, 'title' => mb_substr($title, 0, 170),
                'body' => $body !== null ? mb_substr($body, 0, 1000) : null,
                'url' => $url, 'actor_id' => $actorId,
            ]);
        } catch (\Throwable $e) {
            report($e); // fail-safe
        }
    }

    /** Ikon per tipe (tampilan dropdown lonceng). */
    public static function iconFor(string $type): string
    {
        return match ($type) {
            'keuangan' => '▥',
            'pasien' => '♙',
            'resep' => '℞',
            'survei' => '★',
            default => '◈',
        };
    }
}
