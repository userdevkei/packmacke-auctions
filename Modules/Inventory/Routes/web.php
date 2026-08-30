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

use Modules\Inventory\Http\Controllers\InventoryController;

Route::group(['middleware' => 'auth'], function () {
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'items'])->name('inventory.view');
        Route::post('store', [InventoryController::class, 'inventoryItemStore'])->name('inventory.inventoryItemStore');
        Route::post('update/{id}', [InventoryController::class, 'inventoryItemUpdate'])->name('inventory.inventoryItemUpdate');
        Route::get('delete/{id}', [InventoryController::class, 'inventoryItemDelete'])->name('inventory.inventoryItemDelete');

    });

    Route::prefix('utilization')->group(function () {
        Route::get('/', [InventoryController::class, 'unifiedIndex'])->name('inventory.utilization');
        Route::post('/', [InventoryController::class, 'unifiedIndex'])->name('inventory.utilization');
    });

    Route::prefix('transfers')->group(function () {
        Route::get('/', [InventoryController::class, 'transfer'])->name('transfer.view');
        Route::post('/', [InventoryController::class, 'transfer'])->name('transfer.view');
    });

    Route::prefix('releases')->group(function () {
        Route::get('/', [InventoryController::class, 'releases'])->name('releases.view');
        Route::post('/', [InventoryController::class, 'releases'])->name('releases.view');
    });

    Route::prefix('in-stock')->group(function () {
        Route::get('/', [InventoryController::class, 'inStock'])->name('instock.view');
        Route::post('/', [InventoryController::class, 'inStock'])->name('instock.view');
    });

    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [InventoryController::class, 'purchases'])->name('purchases.view');
        Route::post('/', [InventoryController::class, 'purchases'])->name('purchases.view');
        Route::get('create-purchases', [InventoryController::class, 'createLpo'])->name('create.purchases');
        Route::post('store-purchases', [InventoryController::class, 'receiveLpo'])->name('store.purchases');
        Route::post('update-purchases', [InventoryController::class, 'storeLpo'])->name('update.purchases');
        Route::post('purchases-update-status/{id}', [InventoryController::class, 'lpoUpdateStatus'])->name('purchases.lpoUpdateStatus');
        Route::get('purchases-edit/{id}', [InventoryController::class, 'lpoEdit'])->name('purchases.edit');
        Route::get('purchases-delete/{id}', [InventoryController::class, 'lpoDelete'])->name('purchases.delete');
        Route::post('purchases-update/{id}', [InventoryController::class, 'lpoUpdate'])->name('purchases.update');
        Route::get('download/{id}', [InventoryController::class, 'lpoDownload'])->name('purchases.download');
        Route::post('fetch-lpo', [InventoryController::class, 'fetchLpo'])->name('fetch.lpo');

        Route::get('{id}', [InventoryController::class, 'showPurchaseOrder'])->name('inventory.lpos.show');

    });

    Route::prefix('lpos')->group(function () {
        // LPO List and Views
        Route::get('/', [InventoryController::class, 'lpoIndex'])->name('lpos.view');
        Route::post('/', [InventoryController::class, 'lpoIndex'])->name('lpos.view');
        Route::get('create', [InventoryController::class, 'lpoCreate'])->name('lpos.create');
        Route::get('{id}/edit', [InventoryController::class, 'editLpo'])->name('lpos.edit');
        Route::get('{id}', [InventoryController::class, 'lpoShow'])->name('lpos.show');

        // LPO CRUD Operations
        Route::post('store', [InventoryController::class, 'lpoStore'])->name('store.lpo');
        Route::post('update', [InventoryController::class, 'updateLpo'])->name('update.lpo');
        Route::delete('{id}', [InventoryController::class, 'destroyLpo'])->name('lpos.delete');

        // LPO Status Management
        Route::post('{id}/status', [InventoryController::class, 'updateLpoStatus'])->name('lpos.update-status');

        // LPO Reports and Exports
        Route::get('{id}/pdf', [InventoryController::class, 'exportLpoPdf'])->name('lpos.export-pdf');
        Route::get('statistics', [InventoryController::class, 'lpoStatistics'])->name('lpos.statistics');
    });

    Route::prefix('item-category')->group(function () {
        Route::get('/', [InventoryController::class, 'itemCategory'])->name('itemCategory.view');
        Route::post('store', [InventoryController::class, 'itemCategoryStore'])->name('itemCategory.store');
        Route::get('delete/{id}', [InventoryController::class, 'deleteItemCategory'])->name('itemCategory.delete');
        Route::post('update/{id}', [InventoryController::class, 'itemCategoryUpdate'])->name('itemCategory.update');
    });

   // Transfer Out Routes
    Route::prefix('inventory/transfer_outs')->name('inventory.transfer_outs.')->group(function () {
        Route::get('/', [InventoryController::class, 'transferOutIndex'])->name('index');
        Route::post('/', [InventoryController::class, 'transferOutIndex'])->name('index');
        Route::get('/create', [InventoryController::class, 'createTransferOut'])->name('create');
        Route::post('/store', [InventoryController::class, 'storeTransferOut'])->name('store');
        Route::get('/{id}', [InventoryController::class, 'showTransferOut'])->name('show');
        Route::get('/{id}/edit', [InventoryController::class, 'editTransferOut'])->name('edit');
        Route::post('/{id}/update', [InventoryController::class, 'updateTransferOut'])->name('update');
        Route::post('/{id}/approve', [InventoryController::class, 'approveTransferOut'])->name('approve');
        Route::post('/{id}/cancel', [InventoryController::class, 'cancelTransferOut'])->name('cancel');
    });

// Release Routes
    Route::prefix('inventory/releases')->name('inventory.releases.')->group(function () {
        Route::get('/', [InventoryController::class, 'releaseIndex'])->name('index');
        Route::post('/', [InventoryController::class, 'releaseIndex'])->name('index');
        Route::get('/create', [InventoryController::class, 'createRelease'])->name('create');
        Route::post('/store', [InventoryController::class, 'storeRelease'])->name('store');
        Route::get('/{id}', [InventoryController::class, 'showRelease'])->name('show');
        Route::get('/{id}/edit', [InventoryController::class, 'editRelease'])->name('edit');
        Route::post('/{id}/update', [InventoryController::class, 'updateRelease'])->name('update');
        Route::post('/{id}/approve', [InventoryController::class, 'approveRelease'])->name('approve');
        Route::post('/{id}/cancel', [InventoryController::class, 'cancelRelease'])->name('cancel');
    });

// Requisition Routes
    Route::prefix('inventory/requisitions')->name('inventory.requisitions.')->group(function () {
        Route::get('/', [InventoryController::class, 'requisitionIndex'])->name('index');
        Route::post('/', [InventoryController::class, 'requisitionIndex'])->name('index');
        Route::get('/create', [InventoryController::class, 'createRequisition'])->name('create');
        Route::post('/store', [InventoryController::class, 'storeRequisition'])->name('store');
        Route::get('/{id}', [InventoryController::class, 'showRequisition'])->name('show');
        Route::get('/{id}/edit', [InventoryController::class, 'editRequisition'])->name('edit');
        Route::post('/{id}/update', [InventoryController::class, 'updateRequisition'])->name('update');
        Route::post('/{id}/approve', [InventoryController::class, 'approveRequisition'])->name('approve');
        Route::post('/{id}/cancel', [InventoryController::class, 'cancelRequisition'])->name('cancel');
    });

// Client Inventory & Helper Routes
    Route::get('/inventory/client/{clientId}/summary', [InventoryController::class, 'clientInventorySummary'])->name('inventory.client.summary');
    Route::get('/inventory/client/{clientId}/available-items', [InventoryController::class, 'getClientAvailableItems'])->name('inventory.client.items');
    Route::get('download-transaction/{id}', [InventoryController::class, 'downloadTransaction'])->name('download.transaction');

    // Item movements for a specific client and item
    Route::get('/inventory/client/{clientId}/item/{itemId}/movements', [InventoryController::class, 'itemMovements'])
        ->name('inventory.item.movements');
    Route::post('/inventory/client/{clientId}/item/{itemId}/movements', [InventoryController::class, 'itemMovements']);

    // In your routes file (e.g., routes/web.php or admin routes)
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [InventoryController::class, 'suppliers'])->name('suppliers.view');
        Route::post('/suppliers', [InventoryController::class, 'storeSupplier'])->name('suppliers.store');
        Route::put('/suppliers/{id}', [InventoryController::class, 'updateSupplier'])->name('suppliers.update');
        Route::get('/suppliers/{id}', [InventoryController::class, 'destroySupplier'])->name('suppliers.destroy');
    });

});
