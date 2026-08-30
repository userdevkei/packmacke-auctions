<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('category_id', 12);
            $table->foreign('category_id')->references('id')->on('item_categories')->onDelete('cascade');
            $table->string('item_name')->unique();
            $table->enum('unit', ['m', 'ft', 'ltr', 'pcs', 'kg']);
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('inventory_items');
    }
}
