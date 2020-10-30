<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function scopeActiveSorted($query)
    {
        $query->where('active', 1)->orderBy('name');
    }

    public function getPriceExclVatAttribute($value)
    {
        if ($this->vat->included) {
            return $this->price / (1 + $this->vat->rate / 100);
        } else {
            return $this->price;
        }
    }

    public function getPriceInclVatAttribute($value)
    {
        if ($this->vat->included) {
            return $this->price;
        } else {
            return $this->price * (1 + $this->vat->rate / 100);
        }
    }

    public function vat()
    {
        return $this->belongsTo("NickDeKruijk\Webshop\Model\Vat");
    }

    public function options()
    {
        return $this->hasMany('App\Models\ProductOption');
    }
}
