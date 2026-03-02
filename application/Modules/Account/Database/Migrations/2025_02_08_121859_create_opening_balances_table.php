<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpeningBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->string('opening_balance_id', 12)->primary()->unique();
            $table->string('financial_year_id', 12);
            $table->string('client_id', 12);
            $table->double('amount', 20, 2);
            $table->tinyInteger('type');
            $table->string('user_id', 12);
            $table->bigInteger('date_invoiced');
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
        Schema::dropIfExists('opening_balances');
    }
}
