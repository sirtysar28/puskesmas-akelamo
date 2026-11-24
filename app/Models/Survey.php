<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    public const SERVICES = ['Pendaftaran', 'Poli Umum', 'Poli Gigi', 'KIA/KB', 'Farmasi', 'Laboratorium', 'Lainnya'];

    public $timestamps = false;

    protected $fillable = ['service', 'rating', 'suggestion'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
