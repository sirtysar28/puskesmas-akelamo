<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'entity', 'record_id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Tulis audit log tanpa pernah menggagalkan request utama.
     * (Mis. tabel audit belum dimigrasi setelah rollback via terminal.)
     */
    public static function tryCreate(array $attributes): void
    {
        try {
            self::create($attributes);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
