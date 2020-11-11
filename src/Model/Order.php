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
}
