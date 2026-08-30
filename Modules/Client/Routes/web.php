<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Modules\Client\Http\Controllers\ClientController;

Route::prefix('client')->middleware(['auth', 'web', 'userRoles', 'userRole:10', 'domainValidation'])->group(function() {
    Route::get('/', [ClientController::class, 'index'])->name('client.dashboard');
    Route::get('view-delivery-orders', [ClientController::class, 'viewDeliveryOrders'])->name('client.viewDeliveryOrders');
    Route::get('trace-delivery-order/{id}', [ClientController::class, 'traceTea'])->name('client.traceTea');

    Route::get('view-deliveries', [ClientController::class, 'viewDeliveries'])->name('client.viewDeliveries');
    Route::post('download-stock-report', [ClientController::class, 'StockReport'])->name('client.StockReport');

    Route::get('blend-balance-in-stock', [ClientController::class, 'viewBlendBalances'])->name('client.viewBlendBalances');
    Route::get('tea-samples-request', [ClientController::class,'teaSamplesRequest'])->name('client.teaSamplesRequest');
    Route::get('external-transfers', [ClientController::class, 'viewExternalTransfers'])->name('client.viewExternalTransfers');
    Route::get('download-extra-transfer-delivery-note/{id}', [ClientController::class, 'downloadExtraDelNote'])->name('client.downloadExtraDelNote');

    Route::get('view-external-transfer-details/{id}', [ClientController::class, 'viewExternalTransferDetails'])->name('client.viewExternalTransferDetails');
    Route::get('view-shipping-instructions', [ClientController::class, 'viewShippingInstructions'])->name('client.viewShippingInstructions');

    Route::get('add-teas-to-shipping-instruction/{id}', [ClientController::class, 'addShipmentTeas'])->name('client.addShipmentTeas');
    Route::get('download-shipping-instruction/{id}', [ClientController::class, 'downloadSIDocument'])->name('client.downloadSIDocument');
    Route::get('view-all-blend-requests', [ClientController::class, 'viewBlendProcessing'])->name('client.viewBlendProcessing');
    Route::get('add-a-blend-teas/{id}', [ClientController::class, 'addBlendTeas'])->name('client.addBlendTeas');
    Route::get('clerk-download-blend-sheet/{id}', [ClientController::class, 'downloadBlendSheet'])->name('client.downloadBlendSheet');
    Route::get('clerk-download-blend-out_turn-report/{id}', [ClientController::class, 'downloadOutturReport'])->name('client.downloadOutturReport');
    Route::post('trace-by-invoice-number', [ClientController::class, 'traceTeaByInvoice'])->name('client.traceTeaByInvoice');
    Route::get('trace-blend-balance/{id}', [ClientController::class, 'traceBlendBalance'])->name('client.traceBlendBalance');
});
