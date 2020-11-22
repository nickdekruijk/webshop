<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusNotesToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('webshop.table_prefix') . 'orders', function (Blueprint $table) {
            $table->bigInteger('status')->nullable()->unsigned()->after('paid');
            $table->text('notes')->nullable()->after('status');
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
            $table->dropColumn([
                'status',
                'notes',
            ]);
        });
    }
}
