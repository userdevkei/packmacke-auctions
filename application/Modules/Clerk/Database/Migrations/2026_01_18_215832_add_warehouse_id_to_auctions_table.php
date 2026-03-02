<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWarehouseIdToAuctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('warehouse_id', 12)->nullable()->after('client_id');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onUpdate('cascade')->onDelete('cascade');
            $table->date('sale_date')->nullable()->after('warehouse_id');
            $table->date('prompt_date')->nullable()->after('sale_date');
            $table->date('release_date')->nullable()->after('prompt_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('auctions', function (Blueprint $table) {

        });
    }
}
