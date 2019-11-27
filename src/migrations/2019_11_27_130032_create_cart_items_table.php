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
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->decimal('quantity', 15, 5);
            $table->decimal('price', 15, 2);
            $table->bigInteger('vat_id')->unsigned()->index();
            $table->timestamps();
            $table->foreign('cart_id')->references('id')->on(config('webshop.table_prefix') . 'carts')->onDelete('cascade');
            $table->foreign('vat_id')->references('id')->on(config('webshop.table_prefix') . 'vats')->onDelete('cascade');
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
