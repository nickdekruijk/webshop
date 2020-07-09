<?php

namespace NickDeKruijk\Webshop\Controllers;

use App\Http\Controllers\Controller;
use NickDeKruijk\Webshop\Resources\Payment;
use NickDeKruijk\Webshop\Resources\PaymentProvider;

class PaymentController extends Controller
{
    /**
     * Return an instance of the payment provider set in config
     *
     * @return PaymentProvider
     */
    public static function provider()
    {
        $provider = config('webshop.payment_provider');
        return new $provider;
    }

    /**
     * Get payment details from provider
     *
     * @param string $id
     * @return Payment
     */
    public static function payment($payment_id)
    {
        return self::provider()->payment($payment_id);
    }

    /**
     * Create payment with provider
     *
     * @param array $options
     * @return Payment
     */
    public static function create(array $options)
    {
        return self::provider()->create($options);
    }
}
