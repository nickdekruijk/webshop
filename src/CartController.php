<?php

namespace NickDeKruijk\Webshop;

use App\Http\Controllers\Controller;
use NickDeKruijk\Webshop\Model\Cart;
use NickDeKruijk\Webshop\Model\CartItem;
use Auth;

class CartController extends Controller
{
    // Get current Cart based on sessionId or user
    private static function getCurrent($create = false)
    {
        // Session variable to store cart id in
        $session_cart_id = config('webshop.table_prefix') . 'cart_id';

        // First check if there is a valid cart_id stored in the session
        if (session($session_cart_id)) {
            $cart = Cart::find(session($session_cart_id));
            if ($cart) {
                // Found it, return it
                return $cart;
            }
        }

        // Secondly check if there the current session_id has a cart saved
        $cart = Cart::where('session_id', session()->getId())->latest()->first();
        if ($cart) {
            // Found it, store it in session and return it
            session([$session_cart_id => $cart->id]);
            return $cart;
        }

        // Thirdly check if the current user has a cart
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::user()->id)->latest()->first();
            if ($cart) {
                // Found it, store it in session and return it
                session([$session_cart_id => $cart->id]);
                return $cart;
            }
        }

        // Still no Cart? Create a new one if requested
        if ($create) {
            $cart = new Cart;
            if (Auth::check()) {
                $cart->session_id = session()->getId();
                $cart->user_id = Auth::user()->id;
            }
            $cart->save();
            // Store the id in the session for performance
            session([$session_cart_id => $cart->id]);
            // And return it
            return $cart;
        }
    }

    // Return the total count of items in the cart
    // When $unique = true it returns the total amount of unique items in the cart, even if their quantity is zero
    // When $unique = false (default) it returns the real amount of items (all quantities combined)
    public static function count($unique = false)
    {
        $cart = self::getCurrent();
        if (!$cart) {
            return 0;
        }
        if ($unique) {
            return $cart->items->count();
        } else {
            $count = 0;
            foreach($cart->items as $item) {
                $count += $item->quantity;
            }
            return $count;
        }
    }

    // Add a product to the cart
    public function add($product_id, $quantity = 1, $product_option_id = null)
    {
        // Create Product model instance with $product_id
        $product = config('webshop.product_model');
        $product = (new $product)->findOrFail($product_id);

        // Get the current cart, create if needed
        $cart = $this->getCurrent(true);

        // Check if product is already in cart
        $cart_item = $cart->items()->where('product_id', $product->id)->where('product_option_id', $product_option_id)->first();
        if ($cart_item) {
            // Allready in cart, increase quantity
            $cart_item->quantity = $cart_item->quantity + $quantity;
        } else {
            // Create a new one instead
            $cart_item = new CartItem;
            $cart_item->cart_id = $cart->id;
            $cart_item->product_id = $product->id;
            $cart_item->product_option_id = $product_option_id;
            $cart_item->quantity = $quantity;
        }

        // Save it
        $cart_item->save();

        return back()->with(['webshopStatus' => 'addedtocart']);
    }
}
