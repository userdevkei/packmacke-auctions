<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequisitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('requisition_number', 20)->unique();
            $table->date('requisition_date');
            $table->string('client_id', 12);
            $table->string('si_number', 50)->nullable();
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'fulfilled', 'cancelled'])->default('pending');
            $table->string('approved_by', 12)->nullable();
            $table->string('user_id', 12)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('client_id')->on('clients');
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
        Schema::dropIfExists('requisitions');
    }
}
