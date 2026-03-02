<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdjustmentJournalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adjustment_journals', function (Blueprint $table) {
            $table->string('adjustment_journal_id', 12)->primary();
            $table->string('reference_code', 20);
            $table->string('ledger_id', 12);
            $table->double('amount', 15, 2);
            $table->mediumText('description');
            $table->bigInteger('date_adjusted');
            $table->tinyInteger('type');
            $table->tinyInteger('status');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ledger_id')->references('client_account_id')->on('client_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adjustment_journals');
    }
}
