<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('webshop.table_prefix') . 'orders', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->after('paid');

            // Get users model table name and set foreign index
            $user = config('webshop.user_model');
            $table->foreign('user_id')->references('id')->on((new $user)->getTable());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('webshop.table_prefix') . 'orders', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}
