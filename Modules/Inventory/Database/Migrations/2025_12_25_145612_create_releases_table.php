<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReleasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('release_number', 12)->unique();
            $table->date('release_date');
            $table->string('client_id', 12);
            $table->string('released_to', 100);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');
            $table->string('approved_by', 12)->nullable();
            $table->string('user_id', 12);
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
        Schema::dropIfExists('releases');
    }
}
