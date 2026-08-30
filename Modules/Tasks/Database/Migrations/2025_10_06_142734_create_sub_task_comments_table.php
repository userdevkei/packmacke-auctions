<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubTaskCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_task_comments', function (Blueprint $table) {
            $table->string('sub_task_comment_id', 12)->primary()->unique();
            $table->string('sub_task_id', 12);
            $table->foreign('sub_task_id')->references('sub_task_id')->on('sub_tasks');
            $table->text('comment');
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
        Schema::dropIfExists('sub_task_comments');
    }
}
