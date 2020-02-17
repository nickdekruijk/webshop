<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebshopCustomerToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get users model table name and set foreign index
        $user = config('webshop.user_model');
        Schema::table((new $user)->getTable(), function (Blueprint $table) {
            $table->json(config('webshop.table_prefix') . 'customer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $user = config('webshop.user_model');
        Schema::table((new $user)->getTable(), function (Blueprint $table) {
            $table->dropColumn(config('webshop.table_prefix') . 'customer');
        });
    }
}
