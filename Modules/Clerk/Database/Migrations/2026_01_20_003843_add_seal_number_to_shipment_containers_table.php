<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSealNumberToShipmentContainersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipment_containers', function (Blueprint $table) {
            $table->string('seal_number')->nullable()->after('container_number');
            $table->string('tare_weight')->nullable()->after('seal_number');
            $table->string('pallet_weight')->nullable()->after('tare_weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipment_containers', function (Blueprint $table) {

        });
    }
}
