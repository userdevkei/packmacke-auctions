<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLpoItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lpo_items', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('lpo_id');
            $table->foreign('lpo_id')
                ->references('id')
                ->on('local_purchase_orders')
                ->onDelete('cascade');
            $table->string('item_id');
            $table->foreign('item_id')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('restrict');
            $table->string('item_name'); // Store name in case item is deleted/changed
            $table->string('unit'); // Store unit (e.g., "kg", "pcs", "liters")
            $table->decimal('quantity', 15, 3)->default(0.000); // Allow 3 decimals for precise quantities
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('total_price', 15, 2)->default(0.00); // quantity * unit_price
            $table->boolean('is_vatable')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(16.00); // Store rate (e.g., 16.00 for 16%)
            $table->decimal('vat_amount', 15, 2)->default(0.00); // Calculated VAT for this item
            $table->decimal('gross_amount', 15, 2)->default(0.00); // total_price + vat_amount
            $table->text('item_notes')->nullable();
            $table->integer('line_number')->default(0); // Order of items in the LPO
            $table->timestamps();
            $table->index('lpo_id');
            $table->index('item_id');
            $table->index(['lpo_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lpo_items');
    }
}
