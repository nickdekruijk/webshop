<?php

namespace NickDeKruijk\Webshop;

use App\Http\Controllers\Controller;
use Auth;
use Session;
use Mail;
use Illuminate\Http\Request;
use NickDeKruijk\Webshop\Model\Cart;
use NickDeKruijk\Webshop\Model\CartItem;
use NickDeKruijk\Webshop\Model\Discount;
use NickDeKruijk\Webshop\Model\ShippingRate;
use Mollie\Laravel\Facades\Mollie;

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
            foreach ($cart->items as $item) {
                $count += $item->quantity;
            }
            return $count;
        }
    }

    public function subtotal(Cart $cart)
    {
        $subtotal = 0;
        foreach ($cart->items() as $item) {
            $subtotal = $item->quantity * $item->price;
        }
        return $subtotal;
    }

    // Add a product to the cart
    public function add($product_id, $quantity = 1, $product_option_id = null)
    {
        // If ?option= parameter is given us that if $product_option_id is empty
        if (!$product_option_id && request()->option) {
            $product_option_id = request()->option;
        }

        // Create Product model instance with $product_id
        $product = config('webshop.product_model');
        $product = (new $product)->findOrFail($product_id);

        // Get the current cart, create if needed
        $cart = $this->getCurrent(true);

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
                'subtotal' => self::getItems(),
                'count' => self::getItems(true)['count'],
            ];
        } else {
            return back()->with(['webshopStatus' => 'addedtocart']);
        }
    }

    // Return all cart content
    public static function getItems($compact = false, $coupon_code = null)
    {
        $cart = self::getCurrent();
        if (!$cart) {
            return [];
        }
        if ($compact) {
            $items = [];
            $amount = 0;
            $count = 0;
            $with = ['product'];
            if (config('webshop.product_option_model')) {
                $with[] = 'option';
            }
            foreach ($cart->items()->with($with)->where('quantity', '!=', 0)->get() as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'title' => $item->title,
                    'price' => $item->price,
                    'quantity' => +$item->quantity,
                ];
                $amount += $item->price * $item->quantity;
                $count += $item->quantity;
            }
            $free_shipping = false;
            foreach (Discount::active($amount)->get() as $discount) {
                if ($coupon_code == $discount->coupon_code || !$discount->coupon_code) {
                    if ($discount->free_shipping) {
                        $free_shipping = true;
                    } else {
                        $discountAmount = -$discount->discount_abs - ($amount * $discount->discount_perc / 100);
                        $amount += $discountAmount;
                        $items[] = [
                            'id' => null,
                            'title' => $discount->title . ($discount->coupon_code ? ' (' . $discount->coupon_code . ')' : ''),
                            'price' => $discountAmount,
                            'quantity' => 1,
                        ];
                    }
                }
            }
            $shipping_rate = ShippingRate::find(Webshop::old('webshop-shipping'));
            $response = [
                'items' => $items,
                'amount' => $amount,
                'count' => $count,
            ];
            if ($shipping_rate) {
                $response['amount'] += $free_shipping ? 0 : $shipping_rate->rate;
                $response['shipping'] = [
                    'id' => $shipping_rate->id,
                    'title' => $shipping_rate->title,
                    'rate' => $free_shipping ? 0 : $shipping_rate->rate,
                ];
            }
            return $response;
        } else {
            return $cart->items()->with('product')->get();
        }
    }

    private static function getOrderModel()
    {
        $model = config('webshop.order_model');
        return new $model;
    }

    public function verifyPayment(Request $request)
    {
        $order = $this->getOrderModel()::findOrFail(session(config('webshop.table_prefix') . 'order_id'));
        $payment = Mollie::api()->payments()->get($order->payment_id);
        if ($payment->isPaid()) {
            if (!$order->paid) {
                // Send notifications
                $mailables = config('webshop.mailables_paid');
                if (!is_array($mailables)) {
                    $mailables = [$mailables];
                }
                foreach ($mailables as $mailable) {
                    Mail::send(new $mailable($order));
                }
            }
            if (!config('app.debug')) {
                self::empty();
            }
            $order->paid = true;
            $order->save();
            Session::put(config('webshop.table_prefix') . 'order_id', null);
            return redirect(config('webshop.checkout_redirect_paid'));
        } else {
            return redirect()->route('webshop-cart-show')->with(['payment_error' => trans('webshop::cart.payment_' . $payment->status)]);
        }
    }

    public function webhookMollie(Request $request)
    {
        abort_if(!$request->id, 404);
        $order = $this->getOrderModel()::where('payment_id', $request->id)->firstOrFail();
        $payment = Mollie::api()->payments()->get($request->id);
        if ($payment->isPaid()) {
            $order->paid = true;
            $order->save();
        }
    }

    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password_login])) {
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
            $errors = [
                'password_login' => trans('webshop::cart.checkout_validate_messages')['password_login.invalid'],
            ];
            return back()->withInput()->withErrors($errors);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return back();
    }

    // Empty the current users shopping cart
    public function empty()
    {
        $cart = self::getCurrent();
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
            $order->html = Webshop::showCart(true);
            $items = self::getItems(true, $customer['coupon_code']);
            $order->products = $items['items'];
            $order->amount = $items['amount'];
            $order->save();

            // Get mollie payment id and set redirect/webhook urls
            $payment = Mollie::api()->payments()->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => $order->amount,
                ],
                'description' => 'Webshop order ' . $order->id,
                'webhookUrl' => app()->environment() == 'local' ? null : route('webshop-webhook-mollie'),
                'redirectUrl' => route('webshop-checkout-verify'),
            ]);
            $order->payment_id = $payment->id;
            $order->save();

            // Store order id in session
            Session::put(config('webshop.table_prefix') . 'order_id', $order->id);

            // Redirect to Mollie
            return redirect($payment->getCheckoutUrl(), 303);
        }

        // No checkout, just update quantity and check coupon_code
        if (self::getCurrent()) {
            foreach (self::getCurrent()->items as $item) {
                if ($request['quantity_' . $item->id] != $item->quantity) {
                    if ($request['quantity_' . $item->id]) {
                        $item->quantity = $request['quantity_' . $item->id];
                    } else {
                        $item->quantity = 0;
                    }
                    $item->save();
                }
            }
            // Run only the coupon_code validation
            $request->validate(['coupon_code' => config('webshop.checkout_validate.coupon_code')], trans('webshop::cart.checkout_validate_messages'));
        }
        return back();
    }
}
