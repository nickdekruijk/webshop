<?php

return [

    /*
    |--------------------------------------------------------------------------
    | user_model
    |--------------------------------------------------------------------------
    |
    | Allow users to login and see their order status and save cart contents
    | Set to null to not allow users to login
    |
    */

    'user_model' => 'App\Models\User',

    /*
    |--------------------------------------------------------------------------
    | order_model
    |--------------------------------------------------------------------------
    |
    | If you want to customize the Order model then make a new model App\Order
    | class that extends \NickDeKruijk\Webshop\Model\Order and change the value
    | below to 'App\Order',
    |
    */

    'order_model' => 'NickDeKruijk\Webshop\Model\Order',

    /*
    |--------------------------------------------------------------------------
    | product_model
    |--------------------------------------------------------------------------
    |
    | Use this model for product details
    |
    */

    'product_model' => 'App\Models\Product',

    /*
    |--------------------------------------------------------------------------
    | product_columns
    |--------------------------------------------------------------------------
    |
    | Use these Product model attributes for checkout. Make sure your Product
    | model (above) returns these columns/attributes.
    | Required: id, title, price, vat_id
    |
    */

    'product_columns' => [
        // 'cart_items_column' => 'product_attribute'
        'product_id' => 'id',
        'title' => 'name',
        'description' => 'description',
        'url' => 'url',                 // url to link to when viewing cart contents
        'price' => 'price',
        'weight' => 'weight',
        'vat_id' => 'vat_id',           // Must match a valid id from webshop_vats table
    ],

    /*
    |--------------------------------------------------------------------------
    | product_option_model
    |--------------------------------------------------------------------------
    |
    | Use this model for product options
    |
    */

    'product_option_model' => 'App\Models\ProductOption',

    /*
    |--------------------------------------------------------------------------
    | product_option_columns
    |--------------------------------------------------------------------------
    |
    | Use these Product Option model attributes for checkout. Make sure your
    | ProductOption model (above) returns these columns/attributes.
    | Required: id, title, price
    |
    */

    'product_option_columns' => [
        'option_id' => 'id',
        'title' => 'title',
        'description' => 'description',
        'url' => 'url',
        'price' => 'price',
        'weight' => 'weight',
        'vat_id' => 'vat_id',           // Must match a valid id from webshop_vats table
    ],

    /*
    |--------------------------------------------------------------------------
    | table_prefix
    |--------------------------------------------------------------------------
    |
    | The package requires some migrations to run, the table names will be
    | prefixed with a string to prevent conflicts with already present tables
    |
    */

    'table_prefix' => 'webshop_',

    /*
    |--------------------------------------------------------------------------
    | routes_prefix
    |--------------------------------------------------------------------------
    |
    | Some actions like add to cart require unique urls. These routes will be
    | created automaticaly and to avoid conflicts with other routes from the
    | app you can add prefix to those urls here.
    | Add to cart will be /webshop/cart/add/{product_id} for example
    |
    */

    'routes_prefix' => 'webshop',

    /*
    |--------------------------------------------------------------------------
    | payment_provider
    |--------------------------------------------------------------------------
    |
    | PaymentProvider to use, must have at least payment() and create() methods
    |
    */

    'payment_provider' => 'NickDeKruijk\Webshop\PaymentProviders\Mollie',

    /*
    |--------------------------------------------------------------------------
    | checkout_validate
    |--------------------------------------------------------------------------
    |
    | Validation rules to use when using checkout
    |
    */

    'checkout_validate' => [
        'webshop-shipping' => 'required',
        'email' => 'required|email:rfc,dns,spoof,filter,strict',
        'name' => 'required',
        'address' => 'required',
        'zipcode' => 'required',
        'city' => 'required',
        'country' => 'required',
        'terms' => 'required',
        'payment_method' => 'required',
        'payment_method_ideal_issuer' => 'required_if:payment_method,ideal',
        'payment_method_kbc_issuer' => 'required_if:payment_method,kbc',
    ],

    /*
    |--------------------------------------------------------------------------
    | customer_columns
    |--------------------------------------------------------------------------
    |
    | Columns to store with user
    |
    */

    'customer_columns' => [
        'email',
        'name',
        'address',
        'zipcode',
        'city',
        'country',
        'phone',
    ],

    /*
    |--------------------------------------------------------------------------
    | checkout_redirect_paid
    |--------------------------------------------------------------------------
    |
    | URL to redirect too after succesful payment
    |
    */

    'checkout_redirect_paid' => '/checkout/thankyou',

    /*
    |--------------------------------------------------------------------------
    | mailables
    |--------------------------------------------------------------------------
    |
    | Mailables to call for confirmation emails
    |
    */

    'mailables_paid' => 'App\Mail\OrderPaid',

    /*
    |--------------------------------------------------------------------------
    | cookie (not implemented yet)
    |--------------------------------------------------------------------------
    |
    | By default the cart_id will be stored in the session. So when the users
    | session expires the cart will be empty. To keep the cart longer we can
    | use a dedicated cookie with longer lifetime to store the cart_id
    |
    | Defaults:
    | name = "app_name_cart"
    | lifetime = 1 year
    | path = / (root)
    | domain = null
    | secure = true
    | http_only = true
    |
    */

    'cookie' => [
        'enabled' => false,
        'name' => env('APP_NAME', 'laravel') . '_cart',
        'lifetime' => 60 * 24 * 365,
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
    ],

];
