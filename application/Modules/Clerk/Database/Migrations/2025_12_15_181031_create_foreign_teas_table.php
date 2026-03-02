<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForeignTeasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foreign_teas', function (Blueprint $table) {
            $table->string('foreign_teas_id', 12)->primary();
            $table->string('delivery_order_id', 12);
            $table->foreign('delivery_order_id')->references('delivery_id')->on('delivery_orders')->onDelete('cascade');
            $table->enum('received', ['received', 'not_received'])->default('not_received');
            $table->enum('validate', ['validate', 'not_validate'])->default('not_validate');
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
        Schema::dropIfExists('foreign_teas');
    }
}
