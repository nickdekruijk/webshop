<?php

namespace NickDeKruijk\Webshop;

use NickDeKruijk\Webshop\Model\Cart;

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

    // Return HTML table with the cart contents
    public function showCart()
    {
        $html = '';
        $html .= '<table class="webshop-cart">';
        $html .= '<tr>';
        $html .= '<td'. (config('webshop.product_columns.image') ? ' colspan="2"' : '') . '>Product</td>';
        $html .= '<td>Price</td>';
        $html .= '<td>Quantity</td>';
        $html .= '<td>Total</td>';
        $html .= '</tr>';
        foreach (CartController::getItems() as $item) {
            $html .= '<tr>';
//             dd($item->product);
            if (config('webshop.product_columns.image')) {
                $html .= '<td><img src="' . $item->product[config('webshop.product_columns.image')] . '" alt=""></td>';
            }
            $html .= '<td><div class="webshop-cart-title">' . $item->product[config('webshop.product_columns.title')] . '</div><div class="webshop-cart-description">' . $item->product[config('webshop.product_columns.description')] . '</div></td>';
            $html .= '<td class="webshop-cart-price">' . $item->product[config('webshop.product_columns.price')] . '</td>';
            $html .= '<td class="webshop-cart-quantity">' . +$item->quantity . '</td>';
            $html .= '<td class="webshop-cart-total">' . $item->quantity * $item->product[config('webshop.product_columns.price')] . '</td>';
//             $html .= '<td>' . $item->product . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }
}
