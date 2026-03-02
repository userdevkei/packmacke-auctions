<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->string('task_comment_id', 12)->primary()->unique();
            $table->string('task_id', 12);
            $table->foreign('task_id')->references('task_id')->on('tasks')->onDelete('cascade');
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
        Schema::dropIfExists('task_comments');
    }
}
