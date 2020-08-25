<?php

namespace NickDeKruijk\Webshop\Controllers;

use App\Http\Controllers\Controller;
use App\Order;
use Auth;
use Illuminate\Http\Request;
use Mail;
use NickDeKruijk\Webshop\Rules\CouponCode;
use NickDeKruijk\Webshop\Webshop;
use Session;

class CheckoutController extends Controller
{
    /**
     * Return an instance or the Order model
     *
     * @return mixed
     */
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
                Webshop::log('info', 'Mail sent: ' . $mailable . ' ' . $order->customer['email']);
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
            Webshop::log('info', 'Verified payment: ' . $order->payment_id);
            $this->markOrderAsPaid($order);
            if (!config('app.debug')) {
                CartController::empty();
            }
            Session::put(config('webshop.table_prefix') . 'order_id', null);
            return redirect(config('webshop.checkout_redirect_paid'));
        } else {
            Webshop::log('notice', 'Failed payment: ' . $order->payment_id . ' (' . $payment->status . ')');
            return redirect()->route('webshop-cart-show')->with(['payment_error' => trans('webshop::cart.payment_' . $payment->status)]);
        }
    }

    public function webhookPayment(Request $request)
    {
        Webshop::log('info', 'webhookPayment: ' . $request->id);
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
            Webshop::log('info', 'Login: ' . $request->email);
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
            Webshop::log('notice', 'Login failed: ' . $request->email);
            $errors = [
                'password_login' => trans('webshop::cart.checkout_validate_messages')['password_login.invalid'],
            ];
            return back()->withInput()->withErrors($errors);
        }
    }

    public function logout(Request $request)
    {
        Webshop::log('info', 'Logout: ' . Auth::user()->email);
        Auth::logout();
        return back();
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

            // Also validate coupon_code using array_reverse to put it at the top
            $validate = array_reverse($validate, true);
            $validate['coupon_code'] = ['nullable', new CouponCode];
            $validate = array_reverse($validate, true);

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

                Webshop::log('info', 'Account created ' . $user->email);

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
            $items = CartController::cartItems($customer['coupon_code']);
            $order->products = $items->items;
            $order->amount = $items->amount_including_vat;
            $order->save();

            // Check if payment method exists
            $methods = PaymentController::methods();
            $payment_method = null;
            $payment_issuer = null;
            if (isset($methods[$request->payment_method])) {
                $payment_method = $request->payment_method;
                $method = $methods[$request->payment_method];
                if (isset($method['issuers']) && isset($request['payment_method_' . $method['id'] . '_issuer'])) {
                    $payment_issuer = $request['payment_method_' . $method['id'] . '_issuer'];
                }
            }

            // Get payment id and set redirect/webhook urls
            $payment = PaymentController::create([
                'amount' => $order->amount,
                'currency' => 'EUR',
                'description' => 'Webshop order ' . $order->id,
                'webhookUrl' => app()->environment() == 'local' ? null : route('webshop-webhook-payment'),
                'redirectUrl' => route('webshop-verify-payment'),
                'method' => $payment_method,
                'issuer' => $payment_issuer,
            ]);
            $order->payment_id = $payment->id;
            $order->save();

            // Store order id in session
            Session::put(config('webshop.table_prefix') . 'order_id', $order->id);

            // Redirect to payment provider
            Webshop::log('info', 'Payment redirect: ' . $order->id . ' ' . $order->payment_id . ' ' . $order->customer['email'] . ' ' . $payment->webhookUrl);
            return redirect($payment->checkoutUrl, 303);
        }

        // No checkout, just update quantity and validate coupon_code/shipping
        if (CartController::currentCart()) {
            foreach (CartController::currentCart()->items as $item) {
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
