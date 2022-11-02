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

    public function scopeActive($query, $amount = null)
    {
        if ($amount) {
            return $query
                ->where('active', 1)
                ->where(function ($query) use ($amount) {
                    $query
                        ->whereNull('amount_min')
                        ->orWhere('amount_min', '<', $amount);
                })->where(function ($query) use ($amount) {
                    $query
                        ->whereNull('amount_max')
                        ->orWhere('amount_max', '>', $amount);
                })->where(function ($query) {
                    $query
                        ->whereNull('date_start')
                        ->orWhere('date_start', '<=', now());
                })->where(function ($query) {
                    $query
                        ->whereNull('date_end')
                        ->orWhere('date_end', '>=', now());
                });
        } else {
            return $query->where('active', 1);
        }
    }

    public function scopeValid($query, $coupon_code, $amount)
    {
        return $query->active($amount)->where('coupon_code', $coupon_code);
    }
}
