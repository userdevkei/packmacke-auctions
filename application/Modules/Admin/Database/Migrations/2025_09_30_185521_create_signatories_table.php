<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSignatoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->string('signatory_id', 12)->primary()->unique();
            $table->string('user_id', 12);
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->string('signature', 50)->unique();
            $table->string('department_id', 12);
            $table->foreign('department_id')->references('department_id')->on('departments');
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 12);
            $table->foreign('created_by')->references('user_id')->on('users');
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
        Schema::dropIfExists('signatories');
    }
}
