<?php

namespace NickDeKruijk\Webshop;

use NickDeKruijk\Webshop\Model\Cart;
use NickDeKruijk\Webshop\Model\ShippingRate;
use Cache;
use File;
use GeoIp2\Database\Reader;
use Session;

class Webshop
{
    // Return html based on cart empty or not
    // Example usage: {!! Webshop::cartIcon('<span class="cart-count">$count</span><i class="icon-basket"></i>','<i class="icon-basket cart-empty"></i>', true) !!}
    public function cartIcon($html, $htmlEmpty = null, $unique = false)
    {
        // Get the amount of items in cart
        $count = CartController::count($unique);

        // If empty use $htmlEmpty if present
        if ($count == 0 && $htmlEmpty) {
            $html = $htmlEmpty;
        }

        // Replace $count string with actual count value
        $html = str_replace('$count', $count, $html);

        // Return the html
        return $html;
    }

    public static function count($unique = false)
    {
        return CartController::count($unique);
    }
  
    public static function money($amount, $currency = null)
    {
        return ($currency ?: config('webshop.currency', '&euro; ')) . number_format($amount, 2, trans('webshop::cart.dec_point'), trans('webshop::cart.thousands_sep'));
    }

    public static function geoCountry()
    {
        $ip = request()->ip();
        if ($ip == '127.0.0.1') {
            $ip = '82.217.110.129';
        }
        if ($get = Cache::get('geoip_' . $ip)) {
            return $get;
        }
        $reader = new Reader(storage_path() . '/../vendor/bobey/geoip2-geolite2-composer/GeoIP2/GeoLite2-City.mmdb');
        $record = $reader->city($ip);
        Cache::put('geoip_' . $ip, $record->country->isoCode, 3600);
        return $record->country->isoCode;
    }

    public static function countries($translation = null)
    {
        $countryFile = storage_path() . '/../vendor/mledoze/countries/countries.json';
        abort_if(!File::exists($countryFile), 500, 'Country file not found, is mledoze/countries package loaded?');
        $countries = [];
        foreach (json_decode(File::get($countryFile)) as $country) {
            if ($translation) {
                $countries[$country->cca2] = $country->translations->$translation->common;
            } else {
                $countries[$country->cca2] = $country->name->common;
            }
        }
        // echo '</select>';
        // dd($country->translations->$translation->common);
        asort($countries);
        // dd($countries);
        return $countries;
    }

    public static function old($key, $default = null)
    {
        return session(config('webshop.table_prefix') . 'form.' . $key, $default);
    }

    // Return HTML table with the cart contents
    public static function showCart()
    {
        $validOrder = false;
        $html = '';
        $html .= '<table class="webshop-cart-table">';
        $html .= '<tr>';
        $html .= '<th class="webshop-cart-title">' . trans('webshop::cart.product') . '</th>';
        $html .= '<th class="webshop-cart-price">' . trans('webshop::cart.price') . '</th>';
        $html .= '<th class="webshop-cart-quantity">' . trans('webshop::cart.quantity') . '</th>';
        $html .= '<th class="webshop-cart-total">' . trans('webshop::cart.total') . '</th>';
        $html .= '</tr>';
        $weight = 0;
        $amount = 0;
        foreach (CartController::getItems()->where('quantity', '>', 0) as $item) {
            $validOrder = true;
            $weight += $item->quantity * $item->product[config('webshop.product_columns.weight')];
            $amount += $item->quantity * $item->product[config('webshop.product_columns.price')];
            $html .= '<tr class="webshop-cart-quantity-' . +$item->quantity . '">';
            $html .= '<td><div class="webshop-cart-title">' . $item->product[config('webshop.product_columns.title')] . '</div><div class="webshop-cart-description">' . $item->product[config('webshop.product_columns.description')] . '</div></td>';
            $html .= '<td class="webshop-cart-price">' . self::money($item->product[config('webshop.product_columns.price')]) . '</td>';
            // $html .= '<td class="webshop-cart-quantity"><a href="" class="webshop-cart-minus"></a><span>' . +$item->quantity . '</span><a href="" class="webshop-cart-plus"></a></td>';
            $html .= '<td class="webshop-cart-quantity"><input onchange="this.form.submit()" type="number" name="quantity_' . $item['id'] . '" min="0" value="' . +$item->quantity . '"></td>';
            $html .= '<td class="webshop-cart-total">' . self::money($item->quantity * $item->product[config('webshop.product_columns.price')]) . '</td>';
            $html .= '</tr>';
        }
        // $html .= '<tr>';
        // $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.subtotal') . '</td>';
        // $html .= '<td class="webshop-cart-price"></td>';
        // $html .= '<td class="webshop-cart-quantity"></td>';
        // $html .= '<td class="webshop-cart-total">' . self::money($amount) . '</td>';
        // $html .= '</tr>';
        // $html .= '<tr>';
        // $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.weight') . '</td>';
        // $html .= '<td class="webshop-cart-price"></td>';
        // $html .= '<td class="webshop-cart-quantity"></td>';
        // $html .= '<td class="webshop-cart-total">' . self::money($weight, ' ') . ' kg</td>';
        // $html .= '</tr>';

        $shipping_rate = ShippingRate::valid($amount, $weight, self::old('country', Webshop::geoCountry()))->first();
        $html .= '<tr>';
        if ($shipping_rate) {
            $html .= '<td class="webshop-cart-title">' . $shipping_rate->title . '</td>';
            $html .= '<td class="webshop-cart-price"></td>';
            $html .= '<td class="webshop-cart-quantity"></td>';
            $html .= '<td class="webshop-cart-total">' . self::money($shipping_rate->rate) . '</td>';
        } else {
            $validOrder = false;
            $html .= '<td colspan="4">' . trans('webshop::cart.no-shipping-possible') . '</td>';
        }
        $html .= '</tr>';
        if ($validOrder) {
            $html .= '<tr>';
            $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.total_to_pay') . '</td>';
            $html .= '<td class="webshop-cart-price"></td>';
            $html .= '<td class="webshop-cart-quantity"></td>';
            $html .= '<td class="webshop-cart-total">' . self::money($amount + $shipping_rate->rate) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        Session::put(config('webshop.table_prefix') . 'validOrder', $validOrder);
        return $html;
    }


    // Must be called after showCart so validOrder session will be set
    public static function validOrder()
    {
        return Session::get(config('webshop.table_prefix') . 'validOrder');
    }
}
