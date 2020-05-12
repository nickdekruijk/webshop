<?php

namespace NickDeKruijk\Webshop\Model;

use Illuminate\Database\Eloquent\Model;

class Vat extends Model
{
    protected $casts = [
        'active' => 'boolean',
        'rate' => 'decimal:2',
        'included' => 'boolean',
        'high_rate' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('webshop.table_prefix') . 'vats';
    }
}
