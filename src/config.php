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
    | Use these Product model attributes for cart and checkout. Make sure your
    | Product model (above) returns these columns/attributes.
    | Required: id, title, price, vat_id
    |
    */

    'product_columns' => [
        // 'cart_items_column' => 'product_attribute'
        'product_id' => 'id',
        'title' => 'name',
        'description' => 'description',
        'image' => 'image',             // url to a single image
        'url' => 'url',                 // url to link to when viewing cart contents
        'price' => 'price',
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

];
