<?php

Route::group(['middleware' => ['web']], function () {
    Route::get(config('webshop.routes_prefix') . '/cart/add/{product}/{quantity?}/{product_option?}', 'NickDeKruijk\Webshop\CartController@add')->name('webshop-cart-add');
    Route::post(config('webshop.routes_prefix') . '/checkout', 'NickDeKruijk\Webshop\CartController@post')->name('webshop-checkout-post');
});
