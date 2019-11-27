<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('webshop.table_prefix') . 'cart_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('cart_id')->unsigned()->index();
            $table->bigInteger('product_id')->unsigned()->index();
            $table->bigInteger('product_option_id')->unsigned()->nullable()->index();
            $table->decimal('quantity', 15, 5);
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on(config('webshop.table_prefix') . 'carts')->onDelete('cascade');

            $model = config('webshop.product_model');
            $table->foreign('product_id')->references('id')->on((new $model)->getTable())->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('webshop.table_prefix') . 'cart_items');
    }
}
