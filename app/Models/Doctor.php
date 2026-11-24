<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = ['name', 'specialty', 'sip_number', 'phone'];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
