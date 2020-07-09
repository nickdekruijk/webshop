<?php

namespace NickDeKruijk\Webshop\Controllers;

use App\Http\Controllers\Controller;
use App\Order;
use Auth;
use Log;
use Session;
use Mail;
use Illuminate\Http\Request;
use NickDeKruijk\Webshop\Model\Cart;
use NickDeKruijk\Webshop\Model\CartItem;
use NickDeKruijk\Webshop\Model\Discount;
use NickDeKruijk\Webshop\Model\ShippingRate;
use NickDeKruijk\Webshop\Rules\CouponCode;
use NickDeKruijk\Webshop\Webshop;

class CartController extends Controller
{
    /**
     * Get current Cart based on sessionId or user.
     *
     * @param  boolean $create If true create and store a new Cart instance if no existing cart is found
     * @return Cart
     */
    private static function currentCart($create = false)
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

    private static function log($type, $message)
    {
        $message = "\t" . request()->ip() . "\t" . $message;
        Log::channel('webshop')->$type($message);
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

    private static function getOrderModel()
    {
        $model = config('webshop.order_model');
        return new $model;
    }

    public function markOrderAsPaid(Order $order)
    {
        if (!$order->paid) {
            // Send notifications
            $mailables = config('webshop.mailables_paid');
            if (!is_array($mailables)) {
                $mailables = [$mailables];
            }
            foreach ($mailables as $mailable) {
                Mail::send(new $mailable($order));
                self::log('info', 'Mail sent: ' . $mailable . ' ' . $order->customer['email']);
            }
            $order->paid = true;
            $order->save();
        }
    }

    public function verifyPayment(Request $request)
    {
        $order = $this->getOrderModel()::findOrFail(session(config('webshop.table_prefix') . 'order_id'));
        $payment = PaymentController::payment($order->payment_id);
        if ($payment->paid) {
            self::log('info', 'Verified payment: ' . $order->payment_id);
            $this->markOrderAsPaid($order);
            if (!config('app.debug')) {
                self::empty();
            }
            Session::put(config('webshop.table_prefix') . 'order_id', null);
            return redirect(config('webshop.checkout_redirect_paid'));
        } else {
            self::log('notice', 'Failed payment: ' . $order->payment_id . ' (' . $payment->status . ')');
            return redirect()->route('webshop-cart-show')->with(['payment_error' => trans('webshop::cart.payment_' . $payment->status)]);
        }
    }

    public function webhookPayment(Request $request)
    {
        self::log('info', 'webhookPayment: ' . $request->id);
        abort_if(!$request->id, 404);
        $order = $this->getOrderModel()::where('payment_id', $request->id)->firstOrFail();
        $payment = PaymentController::payment($order->payment_id);
        if ($payment->paid) {
            $this->markOrderAsPaid($order);
        }
    }

    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password_login])) {
            self::log('info', 'Login: ' . $request->email);
            // Get customer columns from user
            $column = config('webshop.table_prefix') . 'customer';
            $customer = Auth::user()->$column;
            if ($customer) {
                if (!is_array($customer)) {
                    $customer = (array) json_decode($customer);
                }
                $customer = array_intersect_key($customer, array_flip(config('webshop.customer_columns')));
                Session::put(config('webshop.table_prefix') . 'form', $customer);
            }
            return back();
        } else {
            self::log('notice', 'Login failed: ' . $request->email);
            $errors = [
                'password_login' => trans('webshop::cart.checkout_validate_messages')['password_login.invalid'],
            ];
            return back()->withInput()->withErrors($errors);
        }
    }

    public function logout(Request $request)
    {
        self::log('info', 'Logout: ' . Auth::user()->email);
        Auth::logout();
        return back();
    }

    /**
     * Empty the current users shopping cart
     *
     * @return void
     */
    public static function empty()
    {
        $cart = self::currentCart();
        $cart->delete();
    }

    public function post(Request $request)
    {
        // Store all form values for Webshop::old()
        Session::put(config('webshop.table_prefix') . 'form', $request->toArray());

        // Try to login when user selected login
        if ($request->webshop_submit == 'login' || ($request->webshop_submit == 'checkout' && $request->account == 'login')) {
            return $this->login($request);
        }

        // Logout the user if they pressed the button
        if ($request->webshop_submit == 'logout') {
            return $this->logout($request);
        }

        // Validate everything and handle checkout if user pressed checkout button
        if ($request->webshop_submit == 'checkout') {

            // Get default validation rules from config
            $validate = config('webshop.checkout_validate');

            // When a new account is created additional rules are needed
            if ($request->account == 'create') {

                // Add unique rule for email
                $validate['email'] = array_merge(is_array($validate['email']) ? $validate['email'] : explode('|', $validate['email']), [
                    'unique:' . config('webshop.user_model') . ',email',
                ]);

                // Password requirements
                $validate['password_create'] = [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])/',
                ];
            }

            // Don't validate email if user is logged in
            if (!$request->account && Auth::check()) {
                unset($validate['email']);
            }

            // Run the validation
            $request->validate($validate, trans('webshop::cart.checkout_validate_messages'));

            // Get Order from DB/Session or create new
            if (session(config('webshop.table_prefix') . 'order_id')) {
                $order = $this->getOrderModel()::findOrNew(session(config('webshop.table_prefix') . 'order_id'));
            } else {
                $order = $this->getOrderModel();
            }

            // Some form fields we don't wanna store
            $customer = $request->except(['_token', 'webshop_submit', 'password_login', 'password_create', 'password_create_confirmation']);

            // Make an account if user wants to make one and isn't logged in yet
            if ($request->account == 'create' && Auth::guest()) {
                $user = config('webshop.user_model');
                $user = new $user;
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = bcrypt($request->password_create);
                $user->save();

                self::log('info', 'Account created ' . $user->email);

                // Attempt to login the newly created user
                if (!Auth::attempt(['email' => $request->email, 'password' => $request->password_create])) {
                    abort(500, 'Failed to login new user');
                }
            }

            // If user is logged in only store specific customer_columns with the User and store the user_id, or set user_id to null when not logged in
            if (Auth::check()) {
                $order->user_id = Auth::user()->id;
                $column = config('webshop.table_prefix') . 'customer';
                Auth::user()->$column = array_intersect_key($customer, array_flip(config('webshop.customer_columns')));
                Auth::user()->save();
                $customer['email'] = Auth::user()->email;
            } else {
                $order->user_id = null;
            }

            // Delete quantity fields since we don't want to store them either
            foreach ($customer as $key => $value) {
                if (substr($key, 0, 9) == 'quantity_') {
                    unset($customer[$key]);
                }
            }

            // Finalize the Order and save it
            $order->customer = $customer;
            $order->html = Webshop::showCart(1, true);
            $items = self::cartItems($customer['coupon_code']);
            $order->products = $items->items;
            $order->amount = $items->amount_including_vat;
            $order->save();

            // Get payment id and set redirect/webhook urls
            $payment = PaymentController::create([
                'amount' => $order->amount,
                'currency' => 'EUR',
                'description' => 'Webshop order ' . $order->id,
                'webhookUrl' => app()->environment() == 'local' ? null : route('webshop-webhook-payment'),
                'redirectUrl' => route('webshop-verify-payment'),
            ]);
            $order->payment_id = $payment->id;
            $order->save();

            // Store order id in session
            Session::put(config('webshop.table_prefix') . 'order_id', $order->id);

            // Redirect to payment provider
            self::log('info', 'Payment redirect: ' . $order->id . ' ' . $order->payment_id . ' ' . $order->customer['email'] . ' ' . $payment->webhookUrl);
            return redirect($payment->checkoutUrl, 303);
        }

        // No checkout, just update quantity and validate coupon_code/shipping
        if (self::currentCart()) {
            foreach (self::currentCart()->items as $item) {
                if ($request['quantity_' . $item->id] != $item->quantity) {
                    if ($request['quantity_' . $item->id]) {
                        $item->quantity = $request['quantity_' . $item->id];
                    } else {
                        $item->quantity = 0;
                    }
                    $item->save();
                }
            }
            // Run only the coupon_code and shipping validation
            $request->validate([
                'webshop-shipping' => 'required',
                'coupon_code' => ['nullable', new CouponCode],
            ], trans('webshop::cart.checkout_validate_messages'));
        }
        return back();
    }
}
