[![Latest Stable Version](https://poser.pugx.org/nickdekruijk/webshop/v/stable)](https://packagist.org/packages/nickdekruijk/webshop)
[![Latest Unstable Version](https://poser.pugx.org/nickdekruijk/webshop/v/unstable)](https://packagist.org/packages/nickdekruijk/webshop)
[![Monthly Downloads](https://poser.pugx.org/nickdekruijk/webshop/d/monthly)](https://packagist.org/packages/nickdekruijk/webshop)
[![Total Downloads](https://poser.pugx.org/nickdekruijk/webshop/downloads)](https://packagist.org/packages/nickdekruijk/webshop)
[![License](https://poser.pugx.org/nickdekruijk/webshop/license)](https://packagist.org/packages/nickdekruijk/webshop)

# Webshop
Add a simple webshop to your Laravel project

## Installation
`composer require nickdekruijk/webshop`

Publish the config file with

`php artisan vendor:publish --tag=config --provider="NickDeKruijk\Webshop\ServiceProvider"`

### Product model/table
You will need a working Product model and database table. See the sample [migration](https://github.com/nickdekruijk/webshop/blob/master/src/examples/2020_08_19_152120_create_products_table.php) and [model](https://github.com/nickdekruijk/webshop/blob/master/src/examples/Product.php) in the [examples folder](https://github.com/nickdekruijk/webshop/tree/master/src/examples).

If your Product model differs from `App\Product` then change the product_model value in your `config/webshop.php` file.

More info coming soon...

### Admin package integration
To manage products/vat/coupons etc with the [nickdekruijk/admin](https://github.com/nickdekruijk/admin) package add the modules as described in [this example file](https://github.com/nickdekruijk/webshop/blob/master/src/examples/admin.md) to your `config/admin.php` file.

### Webhooks and Csrf
To make the payment provider webhooks work you may need to update the `$except` array in `app\Http\Middleware\VerifyCsrfToken.php`
```php
    protected $except = [
        'webshop/webhook-payment',
    ];
```

## Some seeds with data to start with
Dutch VAT
`php artisan db:seed --class=NickDeKruijk\\Webshop\\Seeds\\VatDutch`

Dutch Discounts
`php artisan db:seed --class=NickDeKruijk\\Webshop\\Seeds\\DiscountsDutch`

## License
Admin is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
