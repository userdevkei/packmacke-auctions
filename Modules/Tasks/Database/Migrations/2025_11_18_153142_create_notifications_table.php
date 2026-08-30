<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id', 12)->unique()->primary();
            $table->string('type')->nullable();        // e.g. task_created
            $table->string('title')->nullable();       // title of notification
            $table->text('message')->nullable();       // full notification message
            $table->json('data')->nullable();          // extra data like {task_id: 10}
            $table->string('created_by')->nullable(); // user who triggered it
            $table->timestamps();
            $table->softDeletes();

            // If you want foreign key...
             $table->foreign('created_by')->references('user_id')->on('user_infos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
