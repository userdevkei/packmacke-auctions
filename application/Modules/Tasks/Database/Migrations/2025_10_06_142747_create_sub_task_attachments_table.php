<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubTaskAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_task_attachments', function (Blueprint $table) {
            $table->string('sub_task_attachment_id', 12)->primary()->unique();
            $table->string('sub_task_id', 12);
            $table->foreign('sub_task_id')->references('sub_task_id')->on('sub_tasks')->onDelete('cascade');
            $table->string('file_name', 50);
            $table->string('file_type', 50);
            $table->string('uploaded_by', 12);
            $table->foreign('uploaded_by')->references('user_id')->on('users');
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
        Schema::dropIfExists('sub_task_attachments');
    }
}
