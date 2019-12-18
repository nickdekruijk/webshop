<?php

namespace NickDeKruijk\Webshop\Model;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $casts = [
        'quantity' => 'decimal:5',
        'price' => 'decimal:2',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('webshop.table_prefix') . 'cart_items';
    }

    public function product()
    {
        return $this->belongsTo(config('webshop.product_model'));
    }

}
