<?php

namespace NickDeKruijk\Webshop\Rules;

use Illuminate\Contracts\Validation\Rule;
use NickDeKruijk\Webshop\Controllers\CartController;
use NickDeKruijk\Webshop\Model\Discount;

class CouponCode implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return Discount::valid($value, CartController::cartItems()->amount_including_vat)->count();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('webshop::cart.checkout_validate_messages.coupon_code');
    }
}
