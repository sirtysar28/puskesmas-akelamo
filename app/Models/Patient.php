<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'medical_record_no', 'full_name', 'nik', 'birth_date', 'gender',
        'phone', 'address', 'medical_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            // Enkripsi aplikasi AES-256-GCM via APP_KEY (Laravel Encrypter).
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'medical_notes' => 'encrypted',
        ];
    }

    /**
     * Nomor rekam medis dibuat OTOMATIS sistem: RM-YYMM-NNNN (urut per bulan).
     * Input manual dari formulir diabaikan — tidak bisa dipakai menebak/menabrak nomor lain.
     */
    protected static function booted(): void
    {
        static::creating(function (self $patient) {
            if ($patient->medical_record_no !== null && $patient->medical_record_no !== '') {
                return; // sudah ada (mis. dari seeder terkontrol)
            }

            $prefix = 'RM-'.now()->format('ym').'-';
            $last = static::query()
                ->where('medical_record_no', 'like', str_replace('_', '\_', $prefix).'%')
                ->orderByDesc('medical_record_no')
                ->value('medical_record_no');

            $seq = $last !== null ? ((int) substr($last, -4)) + 1 : 1;
            $patient->medical_record_no = $prefix.sprintf('%04d', min($seq, 9999));
        });
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class)->latest('id');
    }
}
