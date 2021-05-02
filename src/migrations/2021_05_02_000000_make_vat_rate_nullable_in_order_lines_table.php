<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeVatRateNullableInOrderLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('webshop.table_prefix') . 'order_lines', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('webshop.table_prefix') . 'order_lines', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable(false)->change();
        });
    }
}
