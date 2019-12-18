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
}
