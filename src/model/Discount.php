<?php

namespace NickDeKruijk\Webshop\Model;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $casts = [
        'active' => 'boolean',
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'free_shipping' => 'boolean',
        'apply_to_shipping' => 'boolean',
    ];
}
