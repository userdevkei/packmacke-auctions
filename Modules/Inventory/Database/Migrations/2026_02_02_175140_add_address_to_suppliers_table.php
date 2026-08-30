<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddressToSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email', 50)->nullable()->after('supplier_name');
            $table->string('phone_number', 20)->nullable()->after('email');
            $table->string('po_box', 15)->nullable()->after('phone_number');
            $table->string('street', 100)->nullable()->after('po_box');
            $table->string('town', 50)->nullable()->after('street');
            $table->string('notes', 100)->nullable()->after('town');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {

        });
    }
}
