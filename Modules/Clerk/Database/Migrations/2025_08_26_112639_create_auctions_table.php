<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->string('auction_id', 12)->unique()->primary();
            $table->string('delivery_id');
            $table->foreign('delivery_id')->references('delivery_id')->on('delivery_orders');
            $table->string('stock_id');
            $table->foreign('stock_id')->references('stock_id')->on('stock_ins');
            $table->string('warrant_number', 15)->unique();
            $table->string('broker_id', 12);
            $table->foreign('broker_id')->references('broker_id')->on('brokers');
            $table->string('sale', 10);
            $table->string('client_id')->nullable();
            $table->foreign('client_id')->references('client_id')->on('clients');
            $table->tinyInteger('status')->default(0);
            $table->string('user_id');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auctions');
    }
}
