<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocalPurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('local_purchase_orders', function (Blueprint $table) {
            $table->string('id', 12)->primary()->unique();
            $table->string('lpo_number', 20)->unique();
            $table->date('date');
            $table->string('supplier_id', 12)->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers')
                ->onDelete('restrict');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'rejected',
                'completed',
                'cancelled'
            ])->default('draft');
            $table->string('approved_by')->nullable();
            $table->foreign('approved_by')->references('user_id')->on('user_infos')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->string('created_by')->nullable();
            $table->foreign('created_by')->references('user_id')->on('user_infos')->onDelete('set null');
            $table->string('updated_by')->nullable();
            $table->foreign('updated_by')->references('user_id')->on('user_infos')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index('lpo_number');
            $table->index('supplier_id');
            $table->index('date');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_purchase_orders');
    }
}
