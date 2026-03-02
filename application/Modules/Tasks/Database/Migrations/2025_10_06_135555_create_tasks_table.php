<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->string('task_id', 12)->unique()->primary();
            $table->string('station_id', 12);
            $table->foreign('station_id')->references('station_id')->on('stations');
            $table->string('task_number', 20)->unique();
            $table->string('task_name');
            $table->string('department_id', 12);
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->string('description', 200)->nullable();
            $table->string('assigned_to', 12)->nullable();
            $table->foreign('assigned_to')->references('user_id')->on('users')->onDelete('cascade');
            $table->string('assigned_by', 12)->nullable();
            $table->foreign('assigned_by')->references('user_id')->on('users')->onDelete('cascade');
            $table->string('creator_id', 12);
            $table->foreign('creator_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->bigInteger('task_date');
            $table->bigInteger('due_date');
            $table->bigInteger('date_completed')->nullable();
            $table->tinyInteger('priority')->default(4);
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('tasks');
    }
}
