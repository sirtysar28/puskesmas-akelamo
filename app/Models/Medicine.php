<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = ['code', 'name', 'unit', 'stock', 'min_stock', 'expiry_date'];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'min_stock' => 'integer',
            'expiry_date' => 'date:Y-m-d',
        ];
    }
}
