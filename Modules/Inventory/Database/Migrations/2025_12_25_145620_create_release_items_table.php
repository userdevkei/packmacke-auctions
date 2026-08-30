<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReleaseItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('release_items', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('release_id', 12);
            $table->string('item_id', 12);
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('release_id')->references('id')->on('releases')->onDelete('cascade');
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
        Schema::dropIfExists('release_items');
    }
}
