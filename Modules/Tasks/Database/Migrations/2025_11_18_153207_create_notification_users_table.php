<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_users', function (Blueprint $table) {
            $table->string('id', 12)->primary();

            $table->string('notification_id', 12);
            $table->string('user_id', 12);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // If you want strict user FK:
             $table->foreign('user_id')->references('user_id')->on('user_infos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_users');
    }
}
