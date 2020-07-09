<?php

Route::group(['middleware' => ['web']], function () {
    Route::get(config('webshop.routes_prefix') . '/cart/add/{product}/{quantity?}/{product_option?}', 'NickDeKruijk\Webshop\Controllers\CartController@add')->name('webshop-cart-add');
    Route::post(config('webshop.routes_prefix') . '/checkout', 'NickDeKruijk\Webshop\Controllers\CheckoutController@post')->name('webshop-checkout-post');
    Route::post(config('webshop.routes_prefix') . '/webhook-payment', 'NickDeKruijk\Webshop\Controllers\CheckoutController@webhookPayment')->name('webshop-webhook-payment');
    Route::get(config('webshop.routes_prefix') . '/verify-payment', 'NickDeKruijk\Webshop\Controllers\CheckoutController@verifyPayment')->name('webshop-verify-payment');
});
