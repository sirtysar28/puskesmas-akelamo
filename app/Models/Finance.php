<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    protected $fillable = ['transaction_date', 'type', 'category', 'description', 'amount'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }
}
