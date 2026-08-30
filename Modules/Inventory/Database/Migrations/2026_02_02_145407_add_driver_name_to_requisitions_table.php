<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDriverNameToRequisitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->string('warehouse_id', 12)->after('client_id');
            $table->foreign('warehouse_id')->references('station_id')->on('stations')->onDelete('no action')->onUpdate('no action');
            $table->string('driver_name')->nullable()->after('user_id');
            $table->string('phone_number')->nullable()->after('driver_name');
            $table->string('registration_number')->nullable()->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisitions', function (Blueprint $table) {

        });
    }
}
