<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function getPriceFormattedAttribute($value)
    {
        return '&euro; ' . number_format($value ? $value : $this->price, 2, ',', '.');
    }
}
