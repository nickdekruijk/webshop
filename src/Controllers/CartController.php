<?php

namespace NickDeKruijk\Webshop\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use NickDeKruijk\Webshop\Model\Cart;
use NickDeKruijk\Webshop\Model\CartItem;
use NickDeKruijk\Webshop\Model\Discount;
use NickDeKruijk\Webshop\Model\ShippingRate;
use NickDeKruijk\Webshop\Webshop;

class CartController extends Controller
{
    /**
     * Get current Cart based on sessionId or user.
     *
     * @param  boolean $create If true create and store a new Cart instance if no existing cart is found
     * @return Cart
     */
    public static function currentCart($create = false)
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
            $cart->session_id = session()->getId();
            if (Auth::check()) {
                $cart->user_id = Auth::user()->id;
            }
            $cart->save();
            // Store the id in the session for performance
            session([$session_cart_id => $cart->id]);
            // And return it
            return $cart;
        }
    }

    /**
     * Return total count of items in the cart.
     *
     * @param  boolean $unique When true return total amount of unique items instead of adding all quantities together.
     * @return integer
     */
    public static function count($unique = false)
    {
        $cart = self::currentCart();
        if (!$cart) {
            return 0;
        }
        $count = 0;
        foreach ($cart->items as $item) {
            $count += $unique ? ($item->quantity == 0 ? 0 : 1) : $item->quantity;
        }
        return $count;
    }

    /**
     * Add a product to the cart.
     *
     * @param  integer $product_id
     * @param  integer $quantity
     * @param  integer $product_option_id
     * @return Response
     */
    public function add($product_id, $quantity = 1, $product_option_id = null)
    {
        // If ?option= parameter is given use that if $product_option_id is empty
        if (!$product_option_id && request()->option) {
            $product_option_id = request()->option;
        }

        // Create Product model instance with $product_id
        $product = config('webshop.product_model');
        $product = (new $product)->findOrFail($product_id);

        // Get the current cart, create if needed
        $cart = $this->currentCart(true);

        // Check if product is already in cart
        $cart_item = $cart->items()->where('product_id', $product->id)->where('product_option_id', $product_option_id)->first();
        if ($cart_item) {
            // Already in cart, increase quantity
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

        if (request()->ajax()) {
            return [
                'count' => self::cartItems()->count,
            ];
        } else {
            return back()->with(['webshopStatus' => 'addedtocart']);
        }
    }

    /**
     * Return all cart items and calculate total amounts, discount and VAT.
     *
     * @param  integer $coupon_code Apply discount for a coupon code.
     * @return object
     */
    public static function cartItems($coupon_code = null)
    {
        // Get cart contents
        $cart = self::currentCart();
        if (!$cart) {
            return [];
        }

        // Initialize response object
        $response = (object) [
            'items' => [],
            'amount_including_vat' => 0,
            'amount_excluding_vat' => 0,
            'amount_only_items' => 0,
            'amount_vat' => [],
            'weight' => 0,
            'count' => 0,
            'count_unique' => 0,
        ];

        // Used for tracking the highest VAT rate to calculate shipping costs
        $max_vat_rate = 0;

        // Check if product_option is used
        $with = ['product'];
        if (config('webshop.product_option_model')) {
            $with[] = 'option';
        }

        // Walk thru all items in the cart and calculate VAT
        foreach ($cart->items()->with($with)->where('quantity', '!=', 0)->get() as $item) {
            $response->items[] = (object) [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'title' => $item->title,
                'price' => $item->price,
                'weight' => $item->weight,
                'quantity' => +$item->quantity,
            ];
            $response->amount_including_vat += $item->price->price_including_vat * $item->quantity;
            $response->amount_excluding_vat += $item->price->price_excluding_vat * $item->quantity;
            $response->amount_vat[$item->price->vat_rate] = ($response->amount_vat[$item->price->vat_rate] ?? 0) + ($item->price->price_including_vat - $item->price->price_excluding_vat) * $item->quantity;

            if ($item->price->vat_rate > $max_vat_rate) {
                $max_vat_rate = $item->price->vat_rate;
            }
            $response->weight += $item->weight * $item->quantity;;

            $response->count += $item->quantity;
            $response->count_unique++;
        }

        // Fetch all available shipping rates
        $shipping_rates = ShippingRate::valid($response->amount_including_vat, $response->weight, Webshop::old('country', CountryController::geoCountry()))->get();

        // Find selected shipping rate and generate the options if available
        $shipping_rate = null;
        $shipping_options = [];
        if ($shipping_rates->count() == 1) {
            $shipping_rate = $shipping_rates->first();
            $shipping_options[$shipping_rate->id] = $shipping_rate->title;
        } elseif ($shipping_rates->count() > 1) {
            foreach ($shipping_rates as $rate) {
                $shipping_options[$rate->id] = $rate->title;
                if (Webshop::old('webshop-shipping') == $rate->id) {
                    $shipping_rate = $rate;
                }
            }
        }

        // Fetch all available discounts
        $discounts = Discount::active($response->amount_including_vat)->get();

        // Check if customer is eligible for free shipping
        foreach ($discounts as $discount) {
            if ($coupon_code == $discount->coupon_code || !$discount->coupon_code) {
                if ($discount->free_shipping && $shipping_rate) {
                    $shipping_rate->rate = 0;
                }
            }
        }

        // Create shipping item and calculate VAT
        if ($shipping_rate) {
            $shipping = (object) [
                'id' => null,
                'title' => $shipping_rate->title,
                'shipping_options' => $shipping_options,
                'price' => (object) [
                    'price' => $shipping_rate->rate,
                    'vat_included' => $shipping_rate->vat->included,
                    'vat_rate' => $max_vat_rate,
                    'price_including_vat' => null,
                    'price_excluding_vat' => null,
                    'price_vat' => null,
                ],
                'quantity' => 1,
            ];

            if ($shipping->price->vat_included) {
                $shipping->price->price_including_vat = $shipping_rate->rate;
                $shipping->price->price_excluding_vat = number_format($shipping_rate->rate / ($max_vat_rate / 100 + 1), 2);
            } else {
                $shipping->price->price_including_vat = number_format($shipping_rate->rate * ($max_vat_rate / 100 + 1), 2);
                $shipping->price->price_excluding_vat = $shipping_rate->rate;
            }
            $response->amount_vat[$max_vat_rate] = ($response->amount_vat[$max_vat_rate] ?? 0) + $shipping->price->price_including_vat - $shipping->price->price_excluding_vat;

            $response->amount_including_vat += $shipping->price->price_including_vat;
            $response->amount_excluding_vat += $shipping->price->price_excluding_vat;
            $response->items[] = $shipping;
        } else {
            // If no shipping available or selected add empty item
            $response->items[] = (object) [
                'shipping_options' => $shipping_options,
            ];
        }

        foreach ($discounts as $discount) {
            if ($coupon_code == $discount->coupon_code || !$discount->coupon_code) {
                $discountAmount = number_format(-$discount->discount_abs - ($response->amount_including_vat * $discount->discount_perc / 100), 2);
                $response->amount_including_vat += $discountAmount;
                $response->items[] = (object) [
                    'id' => null,
                    'title' => $discount->title . ($discount->coupon_code ? ' (' . $discount->coupon_code . ')' : ''),
                    'price' => (object) [
                        'price' => $discountAmount,
                        'vat_included' => true,
                        'vat_rate' => null,
                        'price_including_vat' => $discountAmount,
                        'price_excluding_vat' => $discountAmount,
                        'price_vat' => $discountAmount,
                    ],
                    'quantity' => 1,
                ];
            }
        }

        return $response;
    }


    /**
     * Empty the current users shopping cart
     *
     * @return void
     */
    public static function empty()
    {
        Webshop::log('debug', 'Empty cart: ' . Webshop::old('email'));
        $cart = self::currentCart();
        if ($cart) {
            $cart->delete();
        }
    }
}
