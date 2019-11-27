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
}
