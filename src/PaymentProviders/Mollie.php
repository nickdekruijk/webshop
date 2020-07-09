<?php

namespace NickDeKruijk\Webshop\PaymentProviders;

use NickDeKruijk\Webshop\Resources\Payment;
use NickDeKruijk\Webshop\Resources\PaymentProvider;

class Mollie extends PaymentProvider
{
    /**
     * convert a Mollie Payment object to our Payment
     *
     * @param \Mollie\Api\Resources\Payment $result
     * @return Payment
     */
    private static function convertPayment(\Mollie\Api\Resources\Payment $result)
    {
        $payment = new Payment;

        $payment->id = $result->id;
        $payment->amount = $result->amount->value;
        $payment->currency = $result->amount->currency;
        $payment->paid = $result->isPaid();
        $payment->status = $result->status;
        $payment->description = $result->description;
        $payment->webhookUrl = $result->webhookUrl;
        $payment->redirectUrl = $result->redirectUrl;
        $payment->checkoutUrl = $result->getCheckoutUrl();

        return $payment;
    }

    /**
     * Get the payment details from the payment provider
     *
     * @param string $payment_id
     * @return Payment;
     */
    public function payment($payment_id)
    {
        return self::convertPayment(mollie()->payments()->get($payment_id));
    }

    /**
     * Create payment with provider
     *
     * @param array $options
     * @return Payment
     */
    public function create(array $options)
    {
        $payment = mollie()->payments()->create([
            'amount' => [
                'currency' => $options['currency'],
                'value' => $options['amount'],
            ],
            'description' => $options['description'],
            'webhookUrl' => $options['webhookUrl'],
            'redirectUrl' => $options['redirectUrl'],
        ]);
        return self::convertPayment($payment);
    }
}
