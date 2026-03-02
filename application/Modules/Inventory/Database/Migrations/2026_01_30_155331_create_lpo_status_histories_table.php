<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLpoStatusHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lpo_status_histories', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('lpo_id');
            $table->foreign('lpo_id')
                ->references('id')
                ->on('local_purchase_orders')
                ->onDelete('cascade');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->string('changed_by')->nullable();
            $table->foreign('changed_by')
                ->references('user_id')
                ->on('user_infos')
                ->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index('lpo_id');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lpo_status_histories');
    }
}
