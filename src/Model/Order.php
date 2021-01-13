<?php

namespace NickDeKruijk\Webshop\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = [
        'paid' => 'boolean',
        'customer' => 'array',
        'products' => 'array',
        'amount' => 'decimal:2',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('webshop.table_prefix') . 'orders';
    }

    public function user()
    {
        return $this->belongsTo(config('webshop.user_model'));
    }

    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }

    public function getQuarterAttribute($value)
    {
        $quarter = $this->created_at->format('Y') . '-' . (ceil($this->created_at->format('m') / 3));
        return $value ?: $quarter;
    }

    public function getAmountVatAttribute($value)
    {
        $vat = 0;
        foreach ($this->products as $product) {
            $vat += ($product['price']['price_including_vat'] - $product['price']['price_excluding_vat']) * $product['quantity'];
        }
        // if ($vat < $this->amount) dd($vat, $this->amount);
        return $value ?: $vat;
    }
    public function getAmountExclVatAttribute($value)
    {
        $vat = 0;
        foreach ($this->products as $product) {
            $vat += $product['price']['price_excluding_vat'] * $product['quantity'];
        }
        return $value ?: $vat;
    }
}
