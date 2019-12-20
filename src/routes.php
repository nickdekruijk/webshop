<?php

Route::group(['middleware' => ['web']], function () {
    Route::get(config('webshop.routes_prefix') . '/cart/add/{product}/{quantity?}/{product_option?}', 'NickDeKruijk\Webshop\CartController@add')->name('webshop-cart-add');
    Route::post(config('webshop.routes_prefix') . '/checkout', 'NickDeKruijk\Webshop\CartController@post')->name('webshop-checkout-post');
    Route::post(config('webshop.routes_prefix') . '/webhook_mollie', 'NickDeKruijk\Webshop\CartController@webhookMollie')->name('webshop-webhook-mollie');
    Route::get(config('webshop.routes_prefix') . '/redirect_mollie', 'NickDeKruijk\Webshop\CartController@verifyPayment')->name('webshop-checkout-verify');
});
