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

    'product_columns' => [
        'title' => 'title',
        'price' => 'price',
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

];
