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

    'user_model' => 'App\User',

    /*
    |--------------------------------------------------------------------------
    | product_model
    |--------------------------------------------------------------------------
    |
    | Allow users to login and see their order status and save cart contents
    | Set to null to not allow users to login
    |
    */

    'product_model' => 'App\Product',

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
//         'image' => 'image',             // url to a single image
        'url' => 'url',                 // url to link to when viewing cart contents
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

    'payment_methods' => [
        'mollie',
    ],

    'checkout_validate' => [
        'webshop-shipping' => 'required',
        'name' => 'required',
        'address' => 'required',
        'zipcode' => 'required',
        'city' => 'required',
        'country' => 'required',
        'email' => 'email:rfc,dns,spoof,filter,strict|required',
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
    | By default the cart_id will be store in the session. So when the users
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
        'name' => Str::slug(env('APP_NAME', 'laravel'), '_') . '_cart',
        'lifetime' => 60 * 24 * 365,
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
    ],

];
