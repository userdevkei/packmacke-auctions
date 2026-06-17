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


use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;

Route::prefix('admin')->middleware(['auth', 'web', 'userRoles', 'userRole:1', 'domainValidation'])->group(function() {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('dashboard-report/{id}', [AdminController::class, 'dashboardReport'])->name('admin.dashboardReport');
    Route::get('users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('register-user', [AdminController::class, 'registerUser'])->name('admin.registerUser');
    Route::post('update-user/{id}', [AdminController::class, 'updateUser'])->name('admin.updateUser');
    Route::get('delete-user/{id}', [AdminController::class, 'disableStaff'])->name('admin.disableStaff');


    Route::get('view-roles', [AdminController::class, 'viewRoles'])->name('admin.viewRoles');
    Route::post('register-role', [AdminController::class, 'registerRole'])->name('admin.registerRole');
    Route::post('update-role/{id}', [AdminController::class, 'updateRoles'])->name('admin.updateRole');

    Route::get('view-stations', [AdminController::class, 'viewStations'])->name('admin.viewStations');
    Route::post('register-station', [AdminController::class, 'registerStation'])->name('admin.registerStation');
    Route::post('update-station/{id}', [AdminController::class, 'updateStation'])->name('admin.updateStation');
    Route::post('update-station-bays/{id}', [AdminController::class, 'updateWarehouseBays'])->name('admin.updateWarehouseBays');
    Route::post('update-station-bay-name/{id}', [AdminController::class, 'updateSubwarehouseName'])->name('admin.updateSubwarehouseName');


    Route::get('our-clients', [AdminController::class, 'viewClients'])->name('admin.viewClients');
    Route::post('register-client', [AdminController::class, 'registerClient'])->name('admin.registerClient');
    Route::post('update-client/{id}', [AdminController::class, 'updateClient'])->name('admin.updateClient');
    Route::get('manage-client/{id}', [AdminController::class, 'manageClient'])->name('admin.manageClient');
    Route::post('create-logins/{id}', [AdminController::class, 'createLogins'])->name('admin.createLogins');


    Route::get('our-brokers', [AdminController::class, 'viewBrokers'])->name('admin.viewBrokers');
    Route::post('register-broker', [AdminController::class, 'registerBroker'])->name('admin.registerBroker');
    Route::post('update-broker/{id}', [AdminController::class, 'updateBroker'])->name('admin.updateBroker');

    Route::get('view-tea-grades', [AdminController::class, 'viewTeaGrade'])->name('admin.viewTeaGrade');
    Route::post('register-tea-grade', [AdminController::class, 'registerTeaGrade'])->name('admin.registerTeaGrade');
    Route::post('update-tea-grade/{id}', [AdminController::class, 'updateTeaGrade'])->name('admin.updateTeaGrade');

    Route::get('view-gardens', [AdminController::class, 'viewGardens'])->name('admin.viewGardens');
    Route::post('register-garden', [AdminController::class, 'registerGarden'])->name('admin.registerGarden');
    Route::post('update-garden/{id}', [AdminController::class, 'updateGarden'])->name('admin.updateGarden');


    Route::get('view-warehouses', [AdminController::class, 'viewWarehouses'])->name('admin.viewWarehouses');
    Route::post('register-warehouse', [AdminController::class, 'registerWarehouse'])->name('admin.registerWarehouse');
    Route::post('update-warehouse/{id}', [AdminController::class, 'updateWarehouse'])->name('admin.updateWarehouse');

    Route::get('view-logistics', [AdminController::class, 'viewTransporters'])->name('admin.viewTransporters');
    Route::post('register-transporter', [AdminController::class, 'registerTransporter'])->name('admin.registerTransporter');
    Route::post('update-transporter/{id}', [AdminController::class, 'updateTransporter'])->name('admin.updateTransporter');

    Route::get('view-shipping-vessels', [AdminController::class, 'viewShippingVessels'])->name('admin.viewShippingVessels');
    Route::post('add-shipping-vessel', [AdminController::class, 'addShippingVessel'])->name('admin.addShippingVessel');
    Route::post('update-shipping-vessel/{id}', [AdminController::class, 'updateShippingVessel'])->name('admin.updateShippingVessel');

    Route::get('view-shipping-destinations', [AdminController::class, 'viewShippingDestinations'])->name('admin.viewShippingDestinations');
    Route::post('add-shipping-destination', [AdminController::class, 'addShippingDestination'])->name('admin.addShippingDestination');
    Route::post('update-shipping-destination/{id}', [AdminController::class, 'updateShippingDestination'])->name('admin.updateShippingDestination');


    Route::get('view-clearing-agents', [AdminController::class, 'viewClearingAgents'])->name('admin.viewClearingAgents');
    Route::post('add-clearing-agent', [AdminController::class, 'addClearingAgent'])->name('admin.addClearingAgent');
    Route::post('update-clearing-agent/{id}', [AdminController::class, 'updateClearingAgent'])->name('admin.updateClearingAgent');

    Route::get('view-loading-instructions', [AdminController::class, 'viewLLIs'])->name('admin.viewLLIs');
    Route::post('view-loading-instructions', [AdminController::class, 'viewLLIs'])->name('admin.viewLLIs');
    Route::get('fetch-driver-by-id', [AdminController::class, 'fetchIdNumber'])->name('admin.fetchIdNumber');
    Route::post('create-tci', [AdminController::class, 'createLLI'])->name('admin.createLLI');
    Route::get('view-tci-details/{id}', [AdminController::class, 'viewTciDetails'])->name('admin.viewTciDetails');
    Route::get('amend-tci-details/{id}', [AdminController::class, 'amendTciDetails'])->name('admin.amendTciDetails');
    Route::get('download-loading-instructions/{id}', [AdminController::class, 'downloadLLI'])->name('admin.downloadLLI');
    Route::get('cancel-loading-instructions/{id}', [AdminController::class, 'revertTCI'])->name('admin.revertTCI');
    Route::get('remove-tea-from-tci/{id}', [AdminController::class, 'removeTeaFromTCI'])->name('admin.removeTeaFromTCI');
    Route::post('update-loading-instructions/{id}', [AdminController::class, 'updateLLI'])->name('admin.updateLLI');
    Route::get('filter-dos-by-garden', [AdminController::class, 'filterByGarden'])->name('admin.filterByGarden');
    Route::get('filter-dos-by-client', [AdminController::class, 'filterByClient'])->name('admin.filterByClient');
    Route::get('filter-dos-by-sale-number', [AdminController::class, 'filterBySaleNumber'])->name('admin.filterBySaleNumber');

    Route::get('view-delivery-orders', [AdminController::class, 'viewDeliveryOrders'])->name('admin.viewDeliveryOrders');
    Route::post('view-delivery-orders', [AdminController::class, 'viewDeliveryOrders'])->name('admin.viewDeliveryOrders');
    Route::get('add-delivery-orders', [AdminController::class, 'addDeliveryOrders'])->name('admin.addDeliveryOrders');
    Route::post('register-delivery-order', [AdminController::class, 'registerDeliveryOrder'])->name('admin.registerDeliveryOrder');
    Route::post('update-delivery-order/{id}', [AdminController::class, 'updateDeliveryOrder'])->name('admin.updateDeliveryOrder');
    Route::get('filter-warehouse-branches', [AdminController::class, 'filterWarehouseBranch'])->name('admin.filterWarehouseBranch');
    Route::get('filter-warehouse-bays', [AdminController::class, 'filterWarehouseBay'])->name('admin.filterWarehouseBay');
    Route::get('view-deliveries', [AdminController::class, 'viewDeliveries'])->name('admin.viewDeliveries');
    Route::post('view-deliveries', [AdminController::class, 'viewDeliveries'])->name('admin.viewDeliveries');
    Route::get('delete-delivery-order/{id}', [AdminController::class, 'deleteDeliveryOrder'])->name('admin.deleteDeliveryOrder');
    Route::post('fetch-do-number', [AdminController::class, 'getDoNumber'])->name('admin.getDoNumber');
    Route::post('receive-delivery', [AdminController::class, 'receiveDelivery'])->name('admin.receiveDelivery');
    Route::post('update-delivery/{id}', [AdminController::class, 'updateStock'])->name('admin.updateStock');
    Route::any('download-stock-report', [AdminController::class, 'StockReport'])->name('admin.StockReport');
    Route::post('download-collection-report', [AdminController::class, 'collectionReport'])->name('admin.collectionReport');
    Route::get('edit-delivery-order-details/{id}', [AdminController::class, 'editDO'])->name('admin.editDO');
    Route::post('import-stock', [AdminController::class, 'importStock'])->name('admin.importStock');
    Route::get('edit-current-stock/{id}', [AdminController::class, 'editStock'])->name('admin.editStock');
    Route::get('list-all-archived-teas', [AdminController::class, 'allArchivedTeas'])->name('admin.allArchivedTeas');
    Route::get('restore-archived-tea/{id}', [AdminController::class, 'restoreArchivedTea'])->name('admin.restoreArchivedTea');

    Route::get('internal-transfers', [AdminController::class, 'viewInternalTransfers'])->name('admin.viewInternalTransfers');
    Route::post('internal-transfers', [AdminController::class, 'viewInternalTransfers'])->name('admin.viewInternalTransfers');

    Route::get('external-transfers', [AdminController::class, 'viewExternalTransfers'])->name('admin.viewExternalTransfers');
    Route::post('external-transfers', [AdminController::class, 'viewExternalTransfers'])->name('admin.viewExternalTransfers');

    Route::get('filter-clients-per-warehouse', [AdminController::class, 'selectClients'])->name('admin.selectClients');
    Route::get('filter-client-per-warehouse', [AdminController::class, 'selectClient'])->name('admin.selectClient');
    Route::post('register-internal-transfer-request', [AdminController::class, 'registerInternalRequest'])->name('admin.registerInternalRequest');
    Route::post('register-external-transfer-request', [AdminController::class, 'registerExternalRequest'])->name('admin.registerExternalRequest');
    Route::get('initiate-transfer-request/{id}', [AdminController::class, 'initiateTransfer'])->name('admin.initiateTransfer');
    Route::post('release-external-transfer-request/{id}', [AdminController::class, 'releaseExternalTransfer'])->name('admin.releaseExternalTransfer');
    Route::get('service-transfer-request/{id}', [AdminController::class, 'serviceRequest'])->name('admin.serviceRequest');
    Route::post('update-transfer-request/{id}', [AdminController::class, 'updateInterTransferRequest'])->name('admin.updateInterTransferRequest');
    Route::get('cancel-transfer-request/{id}', [AdminController::class, 'cancelInterTransferRequest'])->name('admin.cancelInterTransferRequest');
    Route::get('remove-tea-from-transfer-request/{id}', [AdminController::class, 'removeInterTransferRequestTea'])->name('admin.removeInterTransferRequestTea');
    Route::get('cancel-external-transfer-request/{id}', [AdminController::class, 'cancelExternalTransferRequest'])->name('admin.cancelExternalTransferRequest');
    Route::post('receive-transfer-request/{id}', [AdminController::class, 'receiveInterTransferRequest'])->name('admin.receiveInterTransferRequest');
    Route::post('update-external-transfer-request/{id}', [AdminController::class, 'updateExternalTransferRequest'])->name('admin.updateExternalTransferRequest');
    Route::get('initiate-external-transfer-request/{id}', [AdminController::class, 'initiateExternalTransfer'])->name('admin.initiateExternalTransfer');
    Route::get('approve-external-transfer-request/{id}', [AdminController::class, 'approveExternalTransfer'])->name('admin.approveExternalTransfer');
    Route::get('download-inter-transfer-delivery-note/{id}', [AdminController::class, 'downloadInterDelNote'])->name('admin.downloadInterDelNote');
    Route::get('remove-tea-from-external-transfer-request/{id}', [AdminController::class, 'removeExTransferRequestTea'])->name('admin.removeExTransferRequestTea');
    Route::get('download-extra-transfer-delivery-note/{id}', [AdminController::class, 'downloadExtraDelNote'])->name('admin.downloadExtraDelNote');
    Route::get('download-extra-delivery-note/{id}', [AdminController::class, 'downloadDelNote'])->name('admin.downloadDelNote');
    Route::any('prepare-internal-transfer', [AdminController::class, 'prepareInternalTransfer'])->name('admin.prepareInternalTransfer');
    Route::any('prepare-external-transfer', [AdminController::class, 'prepareExternalTransfer'])->name('admin.prepareExternalTransfer');
    Route::get('view-external-transfer-details/{id}', [AdminController::class, 'viewExternalTransferDetails'])->name('admin.viewExternalTransferDetails');

    Route::post('release-transfer-request', [AdminController::class, 'releaseTransfer'])->name('admin.releaseTransfer');
    Route::get('external-transfer-release-form/{delivery}', [AdminController::class, 'getReleaseForm'])->name('admin.releaseForm');

    Route::post('amend-registered-external-transfer-request/{id}', [AdminController::class, 'amendRegisteredExternalRequest'])->name('admin.amendRegisteredExternalRequest');

    Route::get('view-internal-transfer-details/{id}', [AdminController::class, 'viewInternalTransferDetails'])->name('admin.viewInternalTransferDetails');
    Route::get('prepare-to-receive-transfer/{id}', [AdminController::class, 'prepareToReceiveTransfer'])->name('admin.prepareToReceiveTransfer');

    Route::get('view-shipping-instructions', [AdminController::class, 'viewShippingInstructions'])->name('admin.viewShippingInstructions');
    Route::post('add-shipping-instruction', [AdminController::class, 'addShippingInstruction'])->name('admin.addShippingInstruction');
    Route::get('add-teas-to-shipping-instruction/{id}', [AdminController::class, 'addShipmentTeas'])->name('admin.addShipmentTeas');
    Route::post('store-shipping-instruction/{id}', [AdminController::class, 'storeShippingInstruction'])->name('admin.storeShippingInstruction');
    Route::get('update-shipping-instruction/{id}', [AdminController::class, 'updateShippingInstruction'])->name('admin.updateShippingInstruction');
    Route::post('update-shipping-instruction-details/{id}', [AdminController::class, 'updateShippingInstructionDetails'])->name('admin.updateShippingInstructionDetails');
    Route::get('delete-shipping-instruction/{id}', [AdminController::class, 'deleteShippingInstruction'])->name('admin.deleteShippingInstruction');
    Route::get('delete-shipping-instruction-tea/{id}', [AdminController::class, 'deleteShippingInstructionTea'])->name('admin.deleteShippingInstructionTea');
    Route::get('ship-shipping-instruction/{id}', [AdminController::class, 'markAsShipped'])->name('admin.markAsShipped');
    Route::get('download-shipping-instruction/{id}', [AdminController::class, 'downloadSIDocument'])->name('admin.downloadSIDocument');
    Route::get('download-driver-clearance-form/{id}', [AdminController::class, 'downloadDriverClearance'])->name('admin.downloadDriverClearance');
    Route::get('download-stl-report', [AdminController::class, 'exportSTLReport'])->name('admin.exportSTLReport');
    Route::get('create-shipping-instruction', [AdminController::class, 'createSI'])->name('admin.createSI');
    Route::get('initiate-shipping-instruction/{id}', [AdminController::class, 'initateSI'])->name('admin.initateSI');
    Route::get('edit-si-details/{id}', [AdminController::class, 'editSI'])->name('admin.editSI');
    Route::post('update-si-details/{id}', [AdminController::class, 'updateSI'])->name('admin.updateSI');
    Route::get('download-shipping-instruction-packing-list/{id}', [AdminController::class, 'downloadSIPackingList'])->name('admin.downloadSIPackingList');
    Route::get('download-continued-shipping-instruction-packing-list/{id}', [AdminController::class, 'downloadSIContinuedPackingList'])->name('admin.downloadSIContinuedPackingList');

    Route::get('view-all-blend-requests', [AdminController::class, 'viewBlendProcessing'])->name('admin.viewBlendProcessing');
    Route::get('create-blend-sheet', [AdminController::class, 'createBlendSheet'])->name('admin.createBlendSheet');
    Route::post('add-a-blend-sheet', [AdminController::class, 'addBlendSheet'])->name('admin.addBlendSheet');
    Route::get('add-a-blend-teas/{id}', [AdminController::class, 'addBlendTeas'])->name('admin.addBlendTeas');
    Route::get('delete-a-blend-teas/{id}', [AdminController::class, 'deleteBlendTea'])->name('admin.deleteBlendTea');
    Route::post('store-blend-teas/{id}', [AdminController::class, 'storeBlendTeas'])->name('admin.storeBlendTeas');
    Route::get('update-blend-sheet-status/{id}', [AdminController::class, 'updateBlendSheet'])->name('admin.updateBlendSheet');
    Route::post('update-blend-sheet-details/{id}', [AdminController::class, 'updateBlendSheetDetails'])->name('admin.updateBlendSheetDetails');
    Route::get('update-blend-sheet-teas/{id}', [AdminController::class, 'markBlendTeaAsShipped'])->name('admin.markBlendTeaAsShipped');
    Route::get('view-blend-balance-in-stock', [AdminController::class, 'viewBlendBalances'])->name('admin.viewBlendBalances');
    Route::get('download-blend-sheet/{id}', [AdminController::class, 'downloadBlendSheet'])->name('admin.downloadBlendSheet');
    Route::get('download-blend-sheet-release/{id}', [AdminController::class, 'downloadBlendDriverClearance'])->name('admin.downloadBlendDriverClearance');
    Route::get('clerk-download-blend-sheet-packing-list/{id}', [AdminController::class, 'downloadBlendPackingList'])->name('admin.downloadBlendPackingList');
    Route::get('clerk-download-blend-sheet-packing-list-cont/{id}', [AdminController::class, 'downloadBlendPackingListCont'])->name('admin.downloadBlendPackingListCont');
    Route::post('add-a-blend-balance{id}', [AdminController::class, 'addBlendBalanceTeas'])->name('admin.addBlendBalanceTeas');
    Route::get('download-blend-outturn-report/{id}', [AdminController::class, 'downloadOutturReport'])->name('admin.downloadOutturReport');
    Route::get('download-blends-report', [AdminController::class, 'exportBlendsReport'])->name('admin.exportBlendsReport');
    Route::get('update-outturn-report-details/{id}', [AdminController::class, 'updateOutTurnReport'])->name('admin.updateOutTurnReport');
    Route::get('delete-blend-sheet/{id}', [AdminController::class, 'deleteBlendSheet'])->name('admin.deleteBlendSheet');
    Route::get('edit-blend-sheet-details/{id}', [AdminController::class, 'editBlendSheet'])->name('admin.editBlendSheet');
    Route::post('update-blend-sheet/{id}', [AdminController::class, 'updateBlend'])->name('admin.updateBlend');


    Route::get('view-direct-deliveries', [AdminController::class, 'viewDirectDeliveries'])->name('admin.viewDirectDeliveries');
    Route::post('view-direct-deliveries', [AdminController::class, 'viewDirectDeliveries'])->name('admin.viewDirectDeliveries');
    Route::get('download-template', [AdminController::class, 'downloadTemplate'])->name('admin.downloadTemplate');
    Route::get('filter-warehouse-bays', [AdminController::class, 'filterWarehouseBay'])->name('admin.filterWarehouseBay');
    Route::post('receive-direct-deliveries/{id}', [AdminController::class, 'receiveDirectDeliveries'])->name('admin.receiveDirectDeliveries');
    Route::get('download-direct-deliveries/{id}', [AdminController::class, 'downloadDirectDeliveries'])->name('admin.downloadDirectDeliveries');
    Route::get('delete-direct-delivery-tea/{id}', [AdminController::class, 'removeDirectDeliveryTea'])->name('admin.removeDirectDeliveryTea');
    Route::get('delete-direct-delivery-teas/{id}', [AdminController::class, 'removeDirectDeliveryTeas'])->name('admin.removeDirectDeliveryTeas');
    Route::post('register-direct-delivery', [AdminController::class, 'registerDirectDeliveryOrder'])->name('admin.registerDirectDeliveryOrder');
    Route::get('view-direct-delivery/{id}', [AdminController::class, 'viewDirectDeliveryOrder'])->name('admin.viewDirectDeliveryOrder');
    Route::get('add-direct-delivery', [AdminController::class, 'addDirectDelivery'])->name('admin.addDirectDelivery');


    // routes/web.php (inside your clerk middleware group)
    Route::post('/direct-deliveries-preview-import',  [AdminController::class, 'previewImport'])->name('admin.previewImport');
    Route::get('/direct-deliveries-import-preview',   [AdminController::class, 'importPreviewPage'])->name('admin.importPreviewPage');
    Route::post('/direct-deliveries-save-import',     [AdminController::class, 'saveImport'])->name('admin.saveImport');

    Route::post('export-transport-report', [AdminController::class, 'exportTransportReport'])->name('admin.exportTransportReport');
    Route::post('export-internal-transfers-report', [AdminController::class, 'exportInterTransferReport'])->name('admin.exportInterTransferReport');
    Route::post('export-external-transfers-report', [AdminController::class, 'exportExterTransferReport'])->name('admin.exportExterTransferReport');

    Route::get('delete-in-stock-tea/{id}', [AdminController::class, 'deleteInStock'])->name('admin.deleteInStock');

    Route::post('trace-by-invoice-number', [AdminController::class, 'traceTeaByInvoice'])->name('admin.traceTeaByInvoice');
    Route::get('trace-delivery-order/{id}', [AdminController::class, 'traceTea'])->name('admin.traceTea');
    Route::get('trace-blend-balance/{id}', [AdminController::class, 'traceBlendBalance'])->name('admin.traceBlendBalance');

    Route::get('packmac-warehouse-locations', [AdminController::class, 'viewOurLocations'])->name('admin.viewOurLocations');
    Route::post('register-warehouse-location', [AdminController::class, 'registerLocation'])->name('admin.registerLocation');
    Route::post('update-warehouse-location/{id}', [AdminController::class, 'updateLocation'])->name('admin.updateLocation');
    Route::post('update-warehouse-locations/{id}', [AdminController::class, 'updateWarehouseLocations'])->name('admin.updateWarehouseLocations');


    Route::get('manage-teas-in-stock', [AdminController::class,'manageStock'])->name('admin.manageStock');
    Route::post('delete-multiple-teas-in-stock', [AdminController::class,'deleteMultipleTeas'])->name('admin.deleteMultipleTeas');
    Route::get('delete-tea-in-stock/{id}', [AdminController::class,'deleteTea'])->name('admin.deleteTea');

    Route::get('tea-samples-request', [AdminController::class,'teaSamplesRequest'])->name('admin.teaSamplesRequest');
    Route::get('withdraw-sample/{id}', [AdminController::class,'withdrawSample'])->name('admin.withdrawSample');
    Route::post('store-sample-request/{id}', [AdminController::class,'storeSampleRequest'])->name('admin.storeSampleRequest');
    Route::get('clerk-fetch-station-to-request-transfer-from', [AdminController::class, 'selectStation'])->name('admin.selectStation');

    Route::get('view-all-report-requests', [AdminController::class, 'viewReportRequest'])->name('admin.viewReportRequest');
    Route::get('filter-report-request', [AdminController::class, 'filterReports'])->name('admin.filterReports');
    Route::post('store-report-request', [AdminController::class, 'storeReport'])->name('admin.storeReport');
    Route::get('approve-report-request/{id}', [AdminController::class, 'approveReportRequest'])->name('admin.approveReportRequest');
    Route::get('download-report-request/{id}', [AdminController::class, 'downloadReportRequest'])->name('admin.downloadReportRequest');
    Route::get('delete-report-request/{id}', [AdminController::class, 'deleteReportRequest'])->name('admin.deleteReportRequest');

    Route::get('download-teas-collection-report/{id}', [AdminController::class, 'collectionStatus'])->name('admin.collectionStatus');
    Route::get('download-transfers-report/{id}', [AdminController::class, 'transferReport'])->name('admin.transferReport');
    Route::post('download-blend-balance-report', [AdminController::class, 'downloadBlendBalances'])->name('admin.downloadBlendBalances');

    Route::get('client-aging-analysis', [AdminController::class, 'stockAgingReport'])->name('admin.stockAgingReport');
    Route::any('download-aging-analysis', [AdminController::class, 'downloadStockAgingReport'])->name('admin.downloadStockAgingReport');
    Route::get('client-stock-aging-analysis/{id}', [AdminController::class, 'clientStock'])->name('admin.clientStock');
    Route::any('download-client-stock-aging-analysis/{id}', [AdminController::class, 'downloadClientStockAgingReport'])->name('admin.downloadClientStockAgingReport');

    Route::get('stock-per-warehouse', [AdminController::class, 'stockPerWarehouse'])->name('admin.stockPerWarehouse');
    Route::get('client-stock-per-warehouse/{id}', [AdminController::class, 'clientStockPerWarehouse'])->name('admin.clientStockPerWarehouse');
    Route::get('stock-distribution-by-warehouse/{id}', [AdminController::class, 'getClientStockData'])->name('admin.getClientStockData');
    Route::get('/stock-distribution-by-bay/{id}', [AdminController::class, 'getStockDataByBay'])->name('admin.getStockDataByBay');
    Route::get('/client-stock-distribution-by-warehouse/{id}', [AdminController::class, 'getClientStockPerWarehouse'])->name('admin.getClientStockPerWarehouse');
    Route::get('/client-stock-aging-report-analysis/{id}', [AdminController::class, 'getClientStockAgingReport'])->name('admin.getClientStockAgingReport');

    Route::get('view-tcis-pending-collection', [AdminController::class, 'viewPendingTCIs'])->name('admin.viewPendingTCIs');
    Route::get('view-tcis-pending-collection-by-location/{id}', [AdminController::class, 'viewLocationPendingTCIs'])->name('admin.viewLocationPendingTCIs');
    Route::any('download-tcis-pending-collection-by-location/{id}', [AdminController::class, 'downloadLocationPendingTCIs'])->name('admin.downloadLocationPendingTCIs');

    Route::get('view-rebagging', [AdminController::class, 'rebagging'])->name('admin.viewRebagging');
    Route::post('prepare-rebagging-job', [AdminController::class, 'prepareRebagJob'])->name('admin.prepareRebagJob');
    Route::get('prepare-rebagging-job', [AdminController::class, 'fetchBySiNumber'])->name('admin.fetchBySiNumber');
    Route::post('store-rebagging-job/{id}', [AdminController::class, 'storeRebaggingRequest'])->name('admin.storeRebaggingRequest');
    Route::get('view-rebagged-teas/{id}', [AdminController::class, 'viewRebaggedTeas'])->name('admin.viewRebaggedTeas');
    Route::get('remove-rebagged-tea/{id}', [AdminController::class, 'removeRebaggedTea'])->name('admin.removeRebaggedTea');
    Route::get('remove-rebagged-teas/{id}', [AdminController::class, 'removeRebaggedTeas'])->name('admin.removeRebaggedTeas');

    Route::get('view-departments', [AdminController::class, 'viewDepartments'])->name('admin.viewDepartments');
    Route::post('store-department', [AdminController::class, 'storeDepartment'])->name('admin.storeDepartment');
    Route::post('update-department/{id}', [AdminController::class, 'updateDepartment'])->name('admin.updateDepartment');
    Route::get('delete-department/{id}', [AdminController::class, 'deleteDepartment'])->name('admin.deleteDepartment');

    Route::get('view-signatories', [AdminController::class, 'viewSignatories'])->name('admin.viewSignatories');
    Route::post('store-signatory', [AdminController::class, 'storeSignatory'])->name('admin.storeSignatory');
    Route::post('update-signatory/{id}', [AdminController::class, 'updateSignatory'])->name('admin.updateSignatory');
    Route::get('delete-signatory/{id}', [AdminController::class, 'deleteSignatory'])->name('admin.deleteSignatory');

    /*Auction Routes*/
    Route::get('tea-auctions', [AdminController::class, 'teaAuction'])->name('admin.teaAuction');
    Route::post('tea-auctions', [AdminController::class, 'teaAuction'])->name('admin.teaAuction');
    Route::get('tea-auctions-sales', [AdminController::class, 'viewSales'])->name('admin.viewSales');
    Route::post('prepare-auction-list', [AdminController::class, 'prepareAuctionList'])->name('admin.prepareAuctionList');
    Route::post('store-auction-list', [AdminController::class, 'storeAuctionList'])->name('admin.storeAuctionList');
    Route::get('view-sale/{id}', [AdminController::class, 'viewSale'])->name('admin.viewSale');
    Route::get('remove-line-from-sale/{id}', [AdminController::class, 'removeLineFromSale'])->name('admin.removeLineFromSale');
    Route::post('update-auction-list/{id}', [AdminController::class, 'updateAuctionList'])->name('admin.updateAuctionList');
    Route::get('download-auction-sheet/{id}', [AdminController::class, 'downloadAuctionSheet'])->name('admin.downloadAuctionSheet');
    Route::get('download-auction-sheet-report/{id}', [AdminController::class, 'downloadAuctionSheetReport'])->name('admin.downloadAuctionSheetReport');

    Route::get('notifications/list', [AdminController::class, 'list'])->name('admin.notifications');
    Route::get('notifications/{id}', [AdminController::class, 'details'])->name('admin.viewNotification');

    Route::post('update-transporter-details/{id}', [AdminController::class, 'updateTransporterDetails'])->name('admin.updateTransporterDetails');

    Route::post('/orders/{order}/collection', [AdminController::class, 'updateCollection'])->name('admin.orders.issue');

    Route::get('download-delivery-note/{id}', [AdminController::class, 'downloadDeliveryNote'])->name('admin.downloadDeliveryNote');

    Route::get('foreign-teas-validation', [AdminController::class, 'foreignTeas'])->name('admin.foreignTeas');
    Route::post('receive-foreign-teas-entries/{id}', [AdminController::class, 'updateEntriesReceived'])->name('admin.receive-entry');
    Route::post('validate-foreign-teas-entries/{id}', [AdminController::class, 'updateEntriesValidated'])->name('admin.validate-entry');

    Route::get('user-permissions', [AdminController::class, 'userPermissions'])->name('admin.userPermissions');
    Route::get('user-permission/{id}', [AdminController::class, 'userPermission'])->name('admin.userPermission');
    Route::post('user-permission-toggle', [AdminController::class, 'usersPermissionsToggle'])->name('admin.usersPermissionsToggle');

});
