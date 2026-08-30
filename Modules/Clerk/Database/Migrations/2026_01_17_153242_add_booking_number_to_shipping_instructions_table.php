<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBookingNumberToShippingInstructionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_instructions', function (Blueprint $table) {
            $table->string('booking_number')->nullable()->after('shipping_number');
            $table->string('si_number')->nullable()->after('booking_number');
            $table->json('address')->nullable()->after('consignee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_instructions', function (Blueprint $table) {

        });
    }
}
