<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->string('task_attachment_id', 12)->primary();
            $table->string('task_id', 12);
            $table->foreign('task_id')->references('task_id')->on('tasks');
            $table->string('file_name', 100);
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
        Schema::dropIfExists('task_attachments');
    }
}
