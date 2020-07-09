<?php

Route::group(['middleware' => ['web']], function () {
    Route::get(config('webshop.routes_prefix') . '/cart/add/{product}/{quantity?}/{product_option?}', 'NickDeKruijk\Webshop\Controllers\CartController@add')->name('webshop-cart-add');
    Route::post(config('webshop.routes_prefix') . '/checkout', 'NickDeKruijk\Webshop\Controllers\CartController@post')->name('webshop-checkout-post');
    Route::post(config('webshop.routes_prefix') . '/webhook_mollie', 'NickDeKruijk\Webshop\Controllers\CartController@webhookMollie')->name('webshop-webhook-mollie');
    Route::get(config('webshop.routes_prefix') . '/redirect_mollie', 'NickDeKruijk\Webshop\Controllers\CartController@verifyPayment')->name('webshop-checkout-verify');
});
