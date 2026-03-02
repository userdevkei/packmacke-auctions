<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferOutItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_out_items', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('transfer_out_id', 12);
            $table->string('item_id', 12);
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('transfer_out_id')->references('id')->on('transfer_outs')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_out_items');
    }
}
