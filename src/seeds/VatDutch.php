<?php

namespace NickDeKruijk\Webshop\Seeds;

use Illuminate\Database\Seeder;
use NickDeKruijk\Webshop\Model\Vat;

class VatDutch extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Vat::truncate();

        Vat::create([
            'description' => 'Inclusief 21% BTW',
            'high_rate' => true,
            'rate' => '21',
            'included' => true,
        ]);

        Vat::create([
            'description' => 'Exclusief 21% BTW',
            'high_rate' => true,
            'rate' => '21',
            'included' => false,
        ]);

        Vat::create([
            'description' => 'Inclusief 9% BTW',
            'high_rate' => false,
            'rate' => '9',
            'included' => true,
        ]);

        Vat::create([
            'description' => 'Exclusief 9% BTW',
            'high_rate' => false,
            'rate' => '9',
            'included' => false,
        ]);

        Vat::create([
            'description' => '0% BTW (Vrijgesteld)',
            'high_rate' => false,
            'rate' => '0',
            'included' => false,
        ]);
    }
}
