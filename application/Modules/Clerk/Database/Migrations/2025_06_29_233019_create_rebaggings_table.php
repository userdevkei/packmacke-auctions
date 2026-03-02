<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRebaggingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rebaggings', function (Blueprint $table) {
            $table->string('rebagging_id', 12)->primary()->unique();
            $table->string('shipping_id', 12);
            $table->string('stock_id', 12);
            $table->double('packages', 10, 2);
            $table->double('weight', 10, 2);
            $table->string('user_id', 12);
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
        Schema::dropIfExists('rebaggings');
    }
}
