<?php

namespace NickDeKruijk\Webshop\Resources;

class Payment
{
    /**
     * Id of the payment
     *
     * @var string
     */
    public $id;

    /**
     * Amount of the payment
     *
     * @var float
     */
    public $amount;

    /**
     * Currency of the payment
     *
     * @var string
     */
    public $currency;

    /**
     * Is the payment actually paid
     *
     * @var boolean
     */
    public $paid;

    /**
     * The status of the payment
     *
     * @var string
     */
    public $status;

    /**
     * Description of the payment that is shown to the customer during the payment,
     * and possibly on the bank or credit card statement.
     *
     * @var string
     */
    public $description;

    /**
     * Redirect URL set on this payment
     *
     * @var string
     */
    public $redirectUrl;

    /**
     * Webhook URL set on this payment
     *
     * @var string|null
     */
    public $webhookUrl;

    /**
     * checkoutUrl URL set on this payment
     *
     * @var string|null
     */
    public $checkoutUrl;
}
