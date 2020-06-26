<?php

namespace NickDeKruijk\Webshop;

use NickDeKruijk\Webshop\Model\Discount;
use NickDeKruijk\Webshop\Model\ShippingRate;
use Cache;
use File;
use GeoIp2\Database\Reader;
use Session;

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
        return ($currency ?: config('webshop.currency', '&euro; ')) . number_format($amount, $decimals, trans('webshop::cart.dec_point'), trans('webshop::cart.thousands_sep'));
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
        $reader = new Reader(base_path('vendor') . '/bobey/geoip2-geolite2-composer/GeoIP2/GeoLite2-City.mmdb');
        $record = $reader->city($ip);
        Cache::put('geoip_' . $ip, $record->country->isoCode, 3600);
        return $record->country->isoCode;
    }

    public static function countries($translation = null)
    {
        $countryFile = base_path('vendor') . '/mledoze/countries/countries.json';
        abort_if(!File::exists($countryFile), 500, 'Country file not found, is mledoze/countries package loaded?');
        $countries = [];
        foreach (json_decode(File::get($countryFile)) as $country) {
            if ($translation) {
                $countries[$country->cca2] = $country->translations->$translation->common;
            } else {
                $countries[$country->cca2] = $country->name->common;
            }
        }
        asort($countries);
        return $countries;
    }

    public static function old($key, $default = null)
    {
        return session(config('webshop.table_prefix') . 'form.' . $key, $default);
    }

    // Return HTML table with the cart contents
    public static function showCart($order = false, $showId = false, $VATincluded = true)
    {
        $validOrder = false;
        $html = '';
        $html .= '<table class="webshop-cart-table">';
        $html .= '<tr>';
        if ($showId) {
            $html .= '<th class="webshop-cart-id">' . trans('webshop::cart.product_id') . '</th>';
        }
        $html .= '<th class="webshop-cart-title" align="left">' . trans('webshop::cart.product') . '</th>';
        $html .= '<th class="webshop-cart-price" align="right">' . trans('webshop::cart.price') . '</th>';
        $html .= '<th class="webshop-cart-quantity">' . trans('webshop::cart.quantity') . '</th>';
        $html .= '<th class="webshop-cart-total" align="right">' . trans('webshop::cart.total') . '</th>';
        $html .= '</tr>';
        $weight = 0;
        $amount = 0;
        $vat = [];
        $vatTotal = 0;
        $items = CartController::getItems();
        if ($items) {
            foreach ($items->where('quantity', '>', 0) as $item) {
                $validOrder = true;
                $price = $VATincluded ? $item->product->priceInclVat : $item->product->priceExclVat;
                $weight += $item->quantity * $item->weight;
                $amount += $item->quantity * $price;
                $vatItem = $VATincluded ? ($item->quantity * $price - $item->quantity * $price / (1 + $item->product->vat->rate / 100)) : ($item->quantity * $price * $item->product->vat->rate / 100);
                // dd(1 + $item->product->vat->rate / 100, $vatItem, $VATincluded);
                $vat[$item->product->vat->rate] = ($vat[$item->product->vat->rate] ?? 0) + $vatItem;
                $vatTotal += $vatItem;
                $html .= '<tr class="webshop-cart-quantity-' . +$item->quantity . '">';
                if ($showId) {
                    $html .= '<td><div class="webshop-cart-id">' . $item->product_id . '</div></td>';
                }
                $html .= '<td><div class="webshop-cart-title">' . $item->title . '</div></td>';
                $html .= '<td class="webshop-cart-price" nowrap align="right">' . self::money($price) . '</td>';
                // $html .= '<td class="webshop-cart-quantity"><a href="" class="webshop-cart-minus"></a><span>' . +$item->quantity . '</span><a href="" class="webshop-cart-plus"></a></td>';
                if ($order) {
                    $html .= '<td class="webshop-cart-quantity" nowrap align="center">' . +$item->quantity . '</td>';
                } else {
                    $html .= '<td class="webshop-cart-quantity"><input onchange="this.form.submit()" type="number" name="quantity_' . $item['id'] . '" min="0" value="' . +$item->quantity . '"></td>';
                }
                $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($item->quantity * $price) . '</td>';
                $html .= '</tr>';
            }
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

        $free_shipping = false;
        foreach (Discount::active($amount)->get() as $discount) {
            if (self::old('coupon_code') == $discount->coupon_code || !$discount->coupon_code) {
                if ($discount->free_shipping) {
                    $free_shipping = true;
                } else {
                    $html .= '<tr>';
                    if ($showId) {
                        $html .= '<td><div class="webshop-cart-id"></div></td>';
                    }
                    $html .= '<td class="webshop-cart-title">' . $discount->title . ($discount->coupon_code ? ' (' . $discount->coupon_code . ')' : '') . '</td>';
                    $html .= '<td class="webshop-cart-price"></td>';
                    $html .= '<td class="webshop-cart-quantity"></td>';
                    $discountAmount = -$discount->discount_abs - ($amount * $discount->discount_perc / 100);
                    $amount += $discountAmount;
                    $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($discountAmount) . '</td>';
                    $html .= '</tr>';
                }
            }
        }

        $shipping_rates = ShippingRate::valid($amount, $weight, self::old('country', Webshop::geoCountry()))->get();
        $html .= '<tr>';
        if ($shipping_rates->count() == 1 || $order) {
            if ($order) {
                $shipping_rate = $shipping_rates->find(self::old('webshop-shipping'));
                if ($showId) {
                    $html .= '<td align="right"><div class="webshop-cart-id">' . $shipping_rate->id . '</div></td>';
                }
            } else {
                $shipping_rate = $shipping_rates->first();
                $html .= '<input type="hidden" name="webshop-shipping" value="' . $shipping_rate->id . '">';
            }
            $html .= '<td class="webshop-cart-title">' . $shipping_rate->title . '</td>';
            $html .= '<td class="webshop-cart-price"></td>';
            $html .= '<td class="webshop-cart-quantity"></td>';
            $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($free_shipping ? 0 : ($VATincluded ? $shipping_rate->rateInclVat : $shipping_rate->rateExclVat)) . '</td>';
        } elseif ($shipping_rates->count() > 1) {
            $html .= '<td colspan="3">';
            $html .= '<div class="select webshop-shipping"><select name="webshop-shipping" onchange="this.form.submit()">';
            $html .= '<option value="">' . trans('webshop::cart.select-shipping') . '</option>';
            foreach ($shipping_rates as $rate) {
                if (self::old('webshop-shipping') == $rate->id) {
                    $shipping_rate = $rate;
                }
                $html .= '<option value="' . $rate->id . '"' . (self::old('webshop-shipping') == $rate->id ? ' selected' : '') . '>' . $rate->title . ($rate->rate > 0 ? ' ' . self::money($free_shipping ? 0 : ($VATincluded ? $rate->rateInclVat : $rate->rateExclVat)) : '') . '</option>';
            }
            if (empty($shipping_rate)) {
                $validOrder = false;
            }
            $html .= '</select></div>';
            $html .= '</td>';
            $html .= '<td class="webshop-cart-total" nowrap align="right">' . (isset($shipping_rate) ? self::money($free_shipping ? 0 : ($VATincluded ? $shipping_rate->rateInclVat : $shipping_rate->rateExclVat)) : '') . '</td>';
        } else {
            $validOrder = false;
            $html .= '<td colspan="4">' . trans('webshop::cart.no-shipping-possible') . '</td>';
        }
        $html .= '</tr>';
        if ($validOrder) {
            if ($free_shipping) {
                $totalamount = $amount;
            } else {
                $shipping_vat_rate = max(array_keys($vat));
                $shipping_vat = $shipping_rate->rateInclVat - $shipping_rate->rateInclVat / (1 + $shipping_vat_rate / 100);
                $vat[$shipping_vat_rate] += $shipping_vat;
                $vatTotal += $shipping_vat;
                if ($VATincluded) {
                    $totalamount = $amount + $shipping_rate->rateInclVat;
                } else {
                    $totalamount = $amount + $shipping_rate->rateExclVat + $vatTotal;
                }
            }
            if ($VATincluded) {
                $html .= '<tr>';
                if ($showId) {
                    $html .= '<td><div class="webshop-cart-id"></div></td>';
                }
                $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.subtotal_vatIncl') . '</td>';
                $html .= '<td class="webshop-cart-price"></td>';
                $html .= '<td class="webshop-cart-quantity"></td>';
                $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($totalamount) . '</td>';
                $html .= '</tr>';
            }
            $html .= '<tr>';
            if ($showId) {
                $html .= '<td><div class="webshop-cart-id"></div></td>';
            }
            $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.subtotal_vatExcl') . '</td>';
            $html .= '<td class="webshop-cart-price"></td>';
            $html .= '<td class="webshop-cart-quantity"></td>';
            $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($totalamount - ($VATincluded ? $vatTotal : 0)) . '</td>';
            $html .= '</tr>';
            foreach ($vat as $perc => $vatcount) {
                $html .= '<tr>';
                if ($showId) {
                    $html .= '<td><div class="webshop-cart-id"></div></td>';
                }
                $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.vat') . ' ' . +$perc . '%</td>';
                $html .= '<td class="webshop-cart-price"></td>';
                $html .= '<td class="webshop-cart-quantity"></td>';
                $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($vatcount) . '</td>';
                $html .= '</tr>';
            }
            $html .= '<tr>';
            if ($showId) {
                $html .= '<td><div class="webshop-cart-id"></div></td>';
            }
            $html .= '<td class="webshop-cart-title">' . trans('webshop::cart.total_to_pay') . '</td>';
            $html .= '<td class="webshop-cart-price"></td>';
            $html .= '<td class="webshop-cart-quantity"></td>';
            $html .= '<td class="webshop-cart-total" nowrap align="right">' . self::money($totalamount) . '</td>';
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
