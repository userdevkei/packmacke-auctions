<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferOutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_outs', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('transfer_out_number', 20)->unique();
            $table->date('transfer_date');
            $table->string('client_id', 12); // From which client
            $table->string('recipient_id', 12)->nullable(); // To which client (if inter-client transfer)
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');
            $table->string('approved_by', 12)->nullable();
            $table->string('user_id', 12);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('client_id')->on('clients');
            $table->foreign('recipient_id')->references('client_id')->on('clients');
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_outs');
    }
}
