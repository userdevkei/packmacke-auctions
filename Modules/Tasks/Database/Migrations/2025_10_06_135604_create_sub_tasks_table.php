<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_tasks', function (Blueprint $table) {
            $table->string('sub_task_id', 12)->primary();
            $table->string('task_id', 12);
            $table->foreign('task_id')->references('task_id')->on('tasks');
            $table->string('sub_task_name', 100);
            $table->string('assigned_to', 12)->nullable();
            $table->foreign('assigned_to')->references('user_id')->on('users');
            $table->string('assigned_by', 12)->nullable();
            $table->foreign('assigned_by')->references('user_id')->on('users');
            $table->string('sub_task_description', 200)->nullable();
            $table->bigInteger('due_date');
            $table->bigInteger('date_completed')->nullable();
            $table->string('creator_id', 12);
            $table->foreign('creator_id')->references('user_id')->on('users');
            $table->tinyInteger('status', )->default(0);
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
        Schema::dropIfExists('sub_tasks');
    }
}
