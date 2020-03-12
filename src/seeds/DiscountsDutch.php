<?php

namespace NickDeKruijk\Webshop\Seeds;

use Illuminate\Database\Seeder;
use NickDeKruijk\Webshop\Model\Discount;

class DiscountsDutch extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        Discount::truncate();
        Discount::firstOrCreate([
            'active' => false,
            'title' => 'Gratis verzending boven 100 euro',
            'description' => 'Zodra iemand voor 100 euro of meer in winkelwagen heeft zitten dan is verzending gratis.',
            // 'date_start' => null,
            // 'date_end' => null,
            // 'coupon_code' => null,
            // 'uses_per_user' => null,
            'amount_min' => 100,
            // 'discount_perc' => null,
            // 'discount_abs' => null,
            // 'apply_to_shipping' => false,
            'free_shipping' => true,
        ]);
        Discount::firstOrCreate([
            'active' => false,
            'title' => 'Gratis verzending boven 50 euro',
            'description' => 'Zodra iemand voor 50 euro of meer in winkelwagen heeft zitten dan is verzending gratis, maar nu met éénmalige (per account) coupon code.',
            // 'date_start' => null,
            // 'date_end' => null,
            'coupon_code' => 'VERZENDKOSTEN0',
            'uses_per_user' => 1,
            'amount_min' => 50,
            // 'discount_perc' => null,
            // 'discount_abs' => null,
            // 'apply_to_shipping' => false,
            'free_shipping' => true,
        ]);
        Discount::firstOrCreate([
            'active' => false,
            'title' => '10% korting boven 25 euro',
            'description' => 'Zodra iemand deze coupon invoert en voor 25 euro of meer in winkelwagen heeft zitten dan krijgt deze 10% korting op alleen producten en is maximaal 1x te gebruiken per gebruiker.',
            // 'date_start' => null,
            // 'date_end' => null,
            'coupon_code' => 'KORTING10',
            'uses_per_user' => 1,
            'amount_min' => 25,
            'discount_perc' => 10,
            // 'discount_abs' => null,
            'apply_to_shipping' => false,
            // 'free_shipping' => false,
        ]);
        Discount::firstOrCreate([
            'active' => false,
            'title' => '5% korting op alles boven 25 euro',
            'description' => 'Zodra iemand deze coupon invoert en voor 25 euro of meer in winkelwagen heeft zitten dan krijgt deze 5% korting op producten èn verzendkosten en is maximaal 1x te gebruiken per gebruiker.',
            // 'date_start' => null,
            // 'date_end' => null,
            'coupon_code' => 'KORTING5',
            'uses_per_user' => 1,
            'amount_min' => 25,
            'discount_perc' => 5,
            // 'discount_abs' => null,
            'apply_to_shipping' => true,
            // 'free_shipping' => false,
        ]);
        Discount::firstOrCreate([
            'active' => false,
            'title' => '15 euro korting boven 50',
            'description' => 'Zodra iemand deze coupon invoert en voor 50 euro of meer in winkelwagen heeft zitten dan krijgt deze 15,- korting op alleen producten en is maximaal 1x te gebruiken per gebruiker.',
            // 'date_start' => null,
            // 'date_end' => null,
            'coupon_code' => 'KORTING15',
            'uses_per_user' => 1,
            'amount_min' => 50,
            // 'discount_perc' => null,
            'discount_abs' => 15,
            'apply_to_shipping' => false,
            // 'free_shipping' => false,
        ]);
    }
}
