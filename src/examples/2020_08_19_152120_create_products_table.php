<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(1)->index();
            $table->string('name')->index();
            $table->decimal('price', 10, 2)->nullable()->index();
            $table->integer('vat_id')->unsigned()->default(1)->after('price');
            $table->text('images')->nullable();
            $table->longtext('description')->nullable();
            $table->integer('sort')->unsigned()->nullable()->index();
            $table->timestamps();

            $table->foreign('vat_id')->references('id')->on(config('webshop.table_prefix') . 'vats');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
