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

    public function option()
    {
        return $this->belongsTo(config('webshop.product_option_model'), 'product_option_id', config('webshop.product_option_columns.product_id'));
    }

    public function getPriceAttribute($value)
    {
        if ($this->option && config('webshop.product_option_columns.price')) {
            return $this->option[config('webshop.product_option_columns.price')] ?: $this->product[config('webshop.product_columns.price')];
        } else {
            return $this->product[config('webshop.product_columns.price')];
        }
    }

    public function getWeightAttribute($value)
    {
        if ($this->option && config('webshop.product_option_columns.weight')) {
            return $this->option[config('webshop.product_option_columns.weight')] ?: $this->product[config('webshop.product_columns.weight')];
        } else {
            return $this->product[config('webshop.product_columns.weight')];
        }
    }

    public function getTitleAttribute($value)
    {
        return $this->product[config('webshop.product_columns.title')] . ($this->option && $this->option[config('webshop.product_option_columns.title')] ? ' (' . $this->option[config('webshop.product_option_columns.title')] . ')' : '');
    }

    public function getDescriptionAttribute($value)
    {
        return $this->product[config('webshop.product_columns.description')] . ($this->option && $this->option[config('webshop.product_option_columns.description')] ? ' (' . $this->option[config('webshop.product_option_columns.description')] . ')' : '');
    }
}
