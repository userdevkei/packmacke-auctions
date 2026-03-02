<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPackageTareToShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->double('package_tare', 8, 2)->default(0)->after('shipped_weight');
            $table->double('pallet_weight', 8, 2)->default(0)->after('package_tare');
            $table->double('pallet_height', 8, 2)->default(0)->after('pallet_weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipments', function (Blueprint $table) {

        });
    }
}
