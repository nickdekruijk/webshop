<?php

namespace NickDeKruijk\Webshop\Resources;

use NickDeKruijk\Webshop\Resources\Payment;

abstract class PaymentProvider
{
    /**
     * Get the payment details from the payment provider
     *
     * @param string $payment_id
     * @return Payment
     */
    public function payment($payment_id)
    {
        // Overwrite this function when you extend this class
    }

    public function create(array $options)
    {
        // Overwrite this function when you extend this class
    }
}
