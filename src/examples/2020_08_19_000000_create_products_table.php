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
            $table->foreignId('vat_id')->constrained(config('webshop.table_prefix') . 'vats')->onDelete('cascade');
            $table->text('images')->nullable();
            $table->longtext('description')->nullable();
            $table->integer('sort')->unsigned()->nullable()->index();
            $table->timestamps();
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
