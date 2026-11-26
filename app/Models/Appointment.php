<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pendaftaran berobat (antrian) online.
 * Dibuat pasien lewat portal /akun-pasien atau staf lewat modul appointments.
 */
class Appointment extends Model
{
    public const STATUSES = ['Menunggu', 'Dikonfirmasi', 'Selesai', 'Batal'];

    protected $fillable = ['patient_id', 'doctor_id', 'user_id', 'visit_date', 'complaint', 'status'];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date:Y-m-d',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
