<?php

namespace NickDeKruijk\Webshop;

use Log;
use NickDeKruijk\Webshop\Controllers\CartController;
use NickDeKruijk\Webshop\Controllers\CountryController;
use NickDeKruijk\Webshop\Controllers\PaymentController;
use NickDeKruijk\Webshop\Model\ShippingRate;

class Webshop
{
    /**
     * Return total count of items in the cart.
     *
     * @param  boolean $unique When true return total amount of unique items instead of adding all quantities together.
     * @return integer
     */
    public static function count($unique = false)
    {
        return CartController::count($unique);
    }

    /**
     * Return a formatted representation of an amount with currency symbol and decimals.
     *
     * @param  float   $amount
     * @param  string  $currency
     * @param  integer $decimals
     * @return string
     */
    public static function money($amount, $currency = null, $decimals = 2)
    {
        return ($currency ?: config('webshop.currency', '€ ')) . number_format($amount, $decimals, trans('webshop::cart.dec_point'), trans('webshop::cart.thousands_sep'));
    }

    /**
     * Return current country isoCode based on IP address
     *
     * @return string
     */
    public static function geoCountry()
    {
        return CountryController::geoCountry();
    }

    /**
     * Return all countries from mledoze/countries package
     *
     * @param string $translation Return specific translation for country names
     * @return array
     */
    public static function countries($translation = null)
    {
        return CountryController::countries($translation);
    }

    /**
     * Return user form input values from session.
     *
     * @param  string $key Input name.
     * @param  string $default Default value if input was empty.
     * @return string
     */
    public static function old($key, $default = null)
    {
        return session(config('webshop.table_prefix') . 'form.' . $key, $default);
    }

    /**
     * Return showcart view
     *
     * @param integer $vat_show          Include VAT info (0: hide VAT info, show all prices including VAT, 1: as default but add VAT summary, 2: show prices excluding VAT and include VAT summary)
     * @param boolean $hide_interaction  When false hide interactive or input elements like shipping options selector and quantity
     * @return View
     */
    public static function showCart($vat_show = 0, $hide_interaction = false)
    {
        $items = CartController::cartItems(self::old('coupon_code'));
        return view('webshop::showcart', compact('vat_show', 'hide_interaction', 'items'));
    }

    public function getShippingDays(): array
    {
        $shipping = ShippingRate::find(self::old('webshop-shipping'));
        return $shipping->getShippingDays();
    }

    /**
     * Write a log entry to the webshop log channel
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function log($type, $message)
    {
        $message = "\t" . request()->ip() . "\t" . $message;
        Log::channel('webshop')->$type($message);
    }

    public static function paymentMethods()
    {
        return PaymentController::methods();
    }
}
