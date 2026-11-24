<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    public const STATUSES = ['aktif', 'diserahkan'];

    protected $fillable = ['patient_id', 'doctor_id', 'user_id', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'notes' => 'encrypted',
            'created_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('id');
    }

    /** Total unit obat pada resep (untuk tampilan ringkas). */
    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
