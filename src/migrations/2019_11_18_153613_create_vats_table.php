<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('webshop.table_prefix') . 'vats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('active')->default(1)->index();
            $table->string('description');
            $table->decimal('rate', 5, 2);
            $table->boolean('included')->default(0);
            $table->boolean('high_rate')->default(1);
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
        Schema::dropIfExists(config('webshop.table_prefix') . 'vats');
    }
}
