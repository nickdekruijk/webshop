## Admin package modules example
Add and edit the modules below to your `config/admin.php` file.
```php
        'products' => [
            'view' => 'admin::model',
            'icon' => 'fa-gift',
            'title_nl' => 'Producten',
            'model' => 'App\Models\Product',
            'index' => 'name,price,home,vat.description',
            'active' => 'active',
            'orderBy' => 'sort',
            'sortable' => true,
            'columns' => [
                'active' => [
                    'title_nl' => 'Actief',
                ],
                'name' => [
                    'title_nl' => 'Product naam',
                    'validate' => 'required',
                ],
                'price' => [
                    'title_nl' => 'Prijs',
                    'type' => 'string',
                    'validate' => 'required|numeric|between:0.00,99999.99',
                ],
                'vat_id' => [
                    'title_nl' => 'BTW',
                    'type' => 'foreign',
                    'model' => 'NickDeKruijk\Webshop\Model\Vat',
                    'columns' => 'description',
                    'orderby' => 'sort',
                    'validate' => 'required',
                ],
                'images' => [
                    'title_nl' => 'Afbeeldingen',
                    'type' => 'images',
                ],
                'description' => [
                    'title_nl' => 'Beschrijving',
                    'tinymce' => true,
                ],
            ],
        ],
        'orders' => [
            'view' => 'admin::model',
            'icon' => 'fa-shopping-cart',
            'title_nl' => 'Bestellingen',
            'model' => 'NickDeKruijk\Webshop\Model\Order',
            'index' => 'id,customer.name,customer.email,amount,created_at,updated_at',
            'active' => 'paid',
            'orderByDesc' => 'id',
            'columns' => [
                'paid' => [
                    'title_nl' => 'Betaald',
                ],
                'user_id' => [
                    'title_nl' => 'Gebruiker',
                    'type' => 'foreign',
                    'model' => 'App\Models\User',
                    'columns' => 'name,email',
                    'orderby' => 'name',
                ],
                'customer' => [],
                'products' => [],
                'html' => [
                    'type' => 'htmlview',
                ],
            ],
        ],
        'discounts' => [
            'view' => 'admin::model',
            'icon' => 'fa-shopping-cart',
            'title_nl' => 'Kortingen en Coupons',
            'model' => 'NickDeKruijk\Webshop\Model\Discount',
            'index' => 'title,date_start,date_end,coupon_code,discount_perc,discount_abs,free_shipping,amount_min',
            'active' => 'active',
            'orderBy' => 'sort',
            'sortable' => true,
            'columns' => [
                'active' => [
                    'title_nl' => 'Actief',
                ],
                'title' => [
                    'title_nl' => 'Titel',
                    'validate' => 'required',
                ],
                'description' => [
                    'title_nl' => 'Beschrijving',
                ],
                'date_start' => [
                    'title_nl' => 'Geldig vanaf',
                    'index_title_nl' => 'Van',
                    'validate' => 'nullable|date',
                ],
                'date_end' => [
                    'title_nl' => 'Geldig tot',
                    'index_title_nl' => 'Tot',
                    'validate' => 'nullable|date',
                ],
                'coupon_code' => [
                    'title_nl' => 'Coupon code (leeg indien voor iedereen geldig)',
                    'index_title_nl' => 'Coupon',
                ],
                // 'uses_per_user' => [
                //     'title_nl' => 'Aantal keer door ingelogde gebruikers te gebruiken (leeg voor onbeperkt)',
                //     'validate' => 'nullable|integer',
                // ],
                'discount_perc' => [
                    'title_nl' => 'Korting in percentage %',
                    'index_title_nl' => 'Korting %',
                    'validate' => 'nullable|numeric',
                ],
                'discount_abs' => [
                    'title_nl' => 'Korting in absoluut bedrag',
                    'index_title_nl' => 'Korting €',
                    'validate' => 'nullable|numeric',
                ],
                // 'apply_to_shipping' => [
                //     'title_nl' => 'Is de korting  ook van toepassing op verzendkosten?',
                // ],
                'free_shipping' => [
                    'title_nl' => 'Gratis verzending',
                ],
                'amount_min' => [
                    'title_nl' => 'Minimum bedrag in winkelwagen noodzakelijk',
                    'index_title_nl' => 'Vanaf',
                    'validate' => 'nullable|integer',
                ],
                'amount_min' => [
                    'title_nl' => 'Minimum bedrag in winkelwagen noodzakelijk',
                    'index_title_nl' => 'Vanaf',
                    'validate' => 'nullable|integer',
                ],
                'amount_max' => [
                    'title_nl' => 'Maximum bedrag in winkelwagen mogelijk',
                    'index_title_nl' => 'Tot',
                    'validate' => 'nullable|integer',
                ],
            ],
        ],
        'shipping' => [
            'view' => 'admin::model',
            'icon' => 'fa-truck',
            'title_nl' => 'Verzendkosten',
            'model' => 'NickDeKruijk\Webshop\Model\ShippingRate',
            'index' => 'title,rate,vat.description,countries,countries_except',
            'active' => 'active',
            'orderBy' => 'sort',
            'sortable' => true,
            'columns' => [
                'active' => [
                    'title_nl' => 'Actief',
                ],
                'title' => [
                    'title_nl' => 'Titel',
                    'validate' => 'required',
                ],
                'description' => [
                    'title_nl' => 'Omschrijving',
                    'type' => 'text',
                ],
                'rate' => [
                    'title_nl' => 'Prijs',
                    'validate' => 'required|numeric|between:0.00,99999.99',
                    'type' => 'string',
                ],
                'vat_id' => [
                    'title_nl' => 'BTW',
                    'type' => 'foreign',
                    'model' => 'NickDeKruijk\Webshop\Model\Vat',
                    'columns' => 'description',
                    'orderby' => 'sort',
                    'validate' => 'required',
                ],
                'amount_from' => [
                    'title_nl' => 'Beschikbaar vanaf bestelbedrag',
                    'validate' => 'nullable|numeric|between:0.00,9999999.99',
                    'type' => 'string',
                ],
                'amount_to' => [
                    'title_nl' => 'Beschikbaar tot bestelbedrag',
                    'validate' => 'nullable|numeric|between:0.00,9999999.99',
                    'type' => 'string',
                ],
                'weight_from' => [
                    'title_nl' => 'Beschikbaar vanaf gewicht',
                    'validate' => 'nullable|numeric|between:0.000,9999999.999',
                    'type' => 'string',
                ],
                'weight_to' => [
                    'title_nl' => 'Beschikbaar tot gewicht',
                    'validate' => 'nullable|numeric|between:0.000,9999999.999',
                    'type' => 'string',
                ],
                'countries' => [
                    'title_nl' => 'Beschikbaar in alleen deze landen',
                    'placeholder_nl' => 'Bijvoorbeeld: NL, BE, L',
                ],
                'countries_except' => [
                    'title_nl' => 'Beschikbaar in alle landen behalve',
                    'placeholder_nl' => 'Bijvoorbeeld: NL, BE, L',
                ],
            ],
        ],
        'vat' => [
            'view' => 'admin::model',
            'icon' => 'fa-money',
            'title_nl' => 'BTW Tarieven',
            'model' => 'NickDeKruijk\Webshop\Model\Vat',
            'index' => 'description,rate,included,high_rate,shifted',
            'active' => 'active',
            'orderBy' => 'sort',
            'sortable' => true,
            'columns' => [
                'active' => [
                    'title_nl' => 'Actief',
                ],
                'description' => [
                    'title_nl' => 'Omschrijving',
                    'validate' => 'required',
                ],
                'rate' => [
                    'title_nl' => 'Percentage',
                    'validate' => 'required|numeric|between:0.00,99.99',
                    'type' => 'string',
                ],
                'included' => [
                    'title_nl' => 'Inclusief',
                ],
                'high_rate' => [
                    'title_nl' => 'Hoog tarief',
                ],
            ],
        ],
```