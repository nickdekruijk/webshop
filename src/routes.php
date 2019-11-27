<?php

Route::group(['middleware' => ['web']], function () {
    Route::get(config('webshop.routes_prefix').'/cart/add/{product}/{quantity?}/{product_option?}', 'NickDeKruijk\Webshop\CartController@add')->name('webshop-cart-add');
    Route::get(config('webshop.routes_prefix').'/cart', 'NickDeKruijk\Webshop\CartController@show')->name('webshop-cart-show');
});
