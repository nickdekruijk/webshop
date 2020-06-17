<?php

namespace NickDeKruijk\Webshop\Model;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $casts = [
        'active' => 'boolean',
        'rate' => 'decimal:2',
        'amount_from' => 'decimal:5',
        'amount_to' => 'decimal:5',
        'weight_from' => 'decimal:5',
        'weight_to' => 'decimal:5',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('webshop.table_prefix') . 'shipping_rates';
    }

    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }

    public function getRateExclVatAttribute($value)
    {
        if ($this->vat->included) {
            return $this->rate / (1 + $this->vat->rate / 100);
        } else {
            return $this->rate;
        }
    }

    public function getRateInclVatAttribute($value)
    {
        if ($this->vat->included) {
            return $this->rate;
        } else {
            return $this->rate * (1 + $this->vat->rate / 100);
        }
    }

    public function scopeValid($query, $amount, $weight, $country)
    {
        $query->where(function ($query) use ($amount) {
            $query->whereNull('amount_from')->orWhere('amount_from', '>=', $amount);
        });
        $query->where(function ($query) use ($amount) {
            $query->whereNull('amount_to')->orWhere('amount_to', '<', $amount);
        });
        $query->where(function ($query) use ($weight) {
            $query->whereNull('weight_from')->orWhere('weight_from', '<=', $weight);
        });
        $query->where(function ($query) use ($weight) {
            $query->whereNull('weight_to')->orWhere('weight_to', '>=', $weight);
        });
        $query->where(function ($query) use ($country) {
            $query->whereNull('countries')->orWhere('countries', 'LIKE', '%' . $country . '%');
        });
        $query->where(function ($query) use ($country) {
            $query->whereNull('countries_except')->orWhere('countries_except', 'NOT LIKE', '%' . $country . '%');
        });
        return $query->where('active', 1)->orderBy('rate');
    }
}
