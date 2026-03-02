<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskModuleUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_module_user_roles', function (Blueprint $table) {
            $table->string('user_role_id', 12)->unique()->primary();
            $table->string('user_id', 12);
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->string('department_id', 12);
            $table->foreign('department_id')->references('department_id')->on('departments');
            $table->string('assigned_by', 12);
            $table->foreign('assigned_by')->references('user_id')->on('users');
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
        Schema::dropIfExists('task_module_user_roles');
    }
}
