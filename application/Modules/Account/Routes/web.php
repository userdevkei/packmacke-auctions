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
use Modules\Account\Http\Controllers\AccountController;

Route::prefix('account')->middleware(['auth', 'web', 'userRoles', 'userRole:7,8,9', 'domainValidation'])->group(function() {
    Route::get('/', [AccountController::class, 'index'])->name('accounts.dashboard');
    Route::get('view-accounts', [AccountController::class, 'viewAccounts'])->name('accounts.viewAccounts');
    Route::post('register-account', [AccountController::class, 'registerAccount'])->name('accounts.registerAccount');
    Route::post('update-account/{id}', [AccountController::class, 'updateAccount'])->name('accounts.updateAccount');
    Route::get('delete-account/{id}', [AccountController::class, 'deleteAccount'])->name('accounts.deleteAccount');
    Route::get('get-account-type', [AccountController::class, 'getAccountType'])->name('accounts.getAccountType');

    Route::get('chart-accounts', [AccountController::class, 'viewChartAccounts'])->name('accounts.viewChartAccounts');
    Route::post('add-chart-of-account', [AccountController::class, 'addChartAccount'])->name('accounts.addChartAccount');
    Route::post('update-chart-of-account/{id}', [AccountController::class, 'updateChartAccount'])->name('accounts.updateChartAccount');
    Route::get('delete-chart-of-account/{id}', [AccountController::class, 'deleteChartAccount'])->name('accounts.deleteChartAccount');

    Route::get('view-client-accounts', [AccountController::class, 'viewClientAccounts'])->name('accounts.viewClientAccounts');
    Route::post('add-client-accounts', [AccountController::class, 'addClientAccount'])->name('accounts.addClientAccount');
    Route::post('update-client-accounts/{id}', [AccountController::class, 'updateClientAccount'])->name('accounts.updateClientAccount');
    Route::get('delete-client-accounts/{id}', [AccountController::class, 'deleteClientAccount'])->name('accounts.deleteClientAccount');
    Route::get('activate-client-accounts/{id}', [AccountController::class, 'activateClientAccount'])->name('accounts.activateClientAccount');
    Route::get('filter-sub-accounts-per-type', [AccountController::class, 'filterAccountsPerType'])->name('accounts.filterAccountsPerType');
    Route::get('filter-chart-of-accounts', [AccountController::class, 'filterChartOfAccounts'])->name('accounts.filterChartOfAccounts');


    Route::get('financial-years', [AccountController::class, 'viewFinancialYears'])->name('accounts.viewFinancialYears');
    Route::post('add-financial-year', [AccountController::class, 'addFinancialYears'])->name('accounts.addFinancialYears');
    Route::post('update-financial-year/{id}', [AccountController::class, 'updateFinancialYears'])->name('accounts.updateFinancialYears');
    Route::get('delete-financial-year/{id}', [AccountController::class, 'deleteFinancialYears'])->name('accounts.deleteFinancialYears');

    Route::get('currencies', [AccountController::class, 'viewCurrencies'])->name('accounts.viewCurrencies');
    Route::post('add-currency', [AccountController::class, 'addCurrency'])->name('accounts.addCurrency');
    Route::post('update-currency/{id}', [AccountController::class, 'updateCurrency'])->name('accounts.updateCurrency');
    Route::get('delete-currency/{id}', [AccountController::class, 'deleteCurrency'])->name('accounts.deleteCurrency');

    Route::get('view-account-sub-categories', [AccountController::class, 'accountSubCategories'])->name('accounts.accountSubCategories');
    Route::post('add-account-sub-category', [AccountController::class, 'addAccountSubCategory'])->name('accounts.addAccountSubCategory');
    Route::post('update-account-sub-category/{id}', [AccountController::class, 'updateAccountSubCategory'])->name('accounts.updateAccountSubCategory');
    Route::get('delete-account-sub-category/{id}', [AccountController::class, 'deleteAccountSubCategory'])->name('accounts.deleteAccountSubCategory');

    Route::get('view-product-categories', [AccountController::class, 'viewProductCategories'])->name('accounts.viewProductCategories');
    Route::post('add-product-categories', [AccountController::class, 'addProductCategory'])->name('accounts.addProductCategory');
    Route::post('update-product-categories/{id}', [AccountController::class, 'updateProductCategory'])->name('accounts.updateProductCategory');
    Route::get('delete-product-categories/{id}', [AccountController::class, 'deleteProductCategory'])->name('accounts.deleteProductCategory');

    Route::get('invoices', [AccountController::class, 'viewInvoices'])->name('accounts.viewInvoices');
    Route::get('add-invoice', [AccountController::class, 'addInvoice'])->name('accounts.addInvoice');
    Route::get('download-invoice/{id}', [AccountController::class, 'downloadInvoice'])->name('accounts.downloadInvoice');
    Route::get('view-invoice/{id}', [AccountController::class, 'viewInvoice'])->name('accounts.viewInvoice');
    Route::get('post-created-invoice/{id}', [AccountController::class, 'postInvoice'])->name('accounts.postInvoice');
    Route::get('delete-sale-invoice/{id}', [AccountController::class, 'deleteInvoice'])->name('accounts.deleteInvoice');
    Route::get('delete-sale-invoice-item/{id}', [AccountController::class, 'deleteInvoiceItem'])->name('accounts.deleteInvoiceItem');
    Route::get('invoices-per-financial-year/{id}', [AccountController::class, 'yearlyInvoices'])->name('accounts.yearlyInvoices');
    Route::get('receipts-per-financial-year/{id}', [AccountController::class, 'yearlyReceipts'])->name('accounts.yearlyReceipts');
    Route::get('yearly-receipts', [AccountController::class, 'receiptsFY'])->name('accounts.receiptsFY');

    Route::get('payments-per-financial-year/{id}', [AccountController::class, 'yearlyPayments'])->name('accounts.yearlyPayments');
    Route::get('yearly-payments', [AccountController::class, 'paymentsFY'])->name('accounts.paymentsFY');

    Route::get('financial-year-sales-invoices', [AccountController::class, 'salesFYTaxes'])->name('accounts.salesFYTaxes');
    Route::get('view-yearly-sales-taxes/{id}', [AccountController::class, 'yearlyTaxes'])->name('accounts.yearlyTaxes');
    Route::get('view-sales-tax-statement/{id}', [AccountController::class, 'taxStatement'])->name('accounts.taxStatement');

    Route::get('accounts-fetch-account', [AccountController::class, 'fetchAccount'])->name('accounts.fetchAccount');
    Route::post('accounts-store-invoice', [AccountController::class, 'storeInvoice'])->name('accounts.storeInvoice');

    Route::get('get-sales-statements-per-financial-year', [AccountController::class, 'getSalesFinancialYears'])->name('accounts.getSalesFinancialYears');
    Route::get('get-purchases-statements-per-financial-year', [AccountController::class, 'getPurchasesFinancialYears'])->name('accounts.getPurchasesFinancialYears');
    Route::get('get-sales-invoices-per-financial-year/{id}', [AccountController::class, 'getClientsSalesWithInvoices'])->name('accounts.getClientsSalesWithInvoices');
    Route::get('get-purchases-invoices-per-financial-year/{id}', [AccountController::class, 'getClientsPurchasesWithInvoices'])->name('accounts.getClientsPurchasesWithInvoices');
    Route::get('view-client-statement-per-year/{id}', [AccountController::class, 'viewClientStatement'])->name('accounts.viewClientStatement');
    Route::get('view-supplier-statement-per-year/{id}', [AccountController::class, 'viewSupplierStatement'])->name('accounts.viewSupplierStatement');

    Route::get('get-account-statements-per-financial-year', [AccountController::class, 'getAccountStatementFinancialYears'])->name('accounts.getAccountStatementFinancialYears');
    Route::get('yearly-trial-balance/{id}', [AccountController::class, 'getAccountsWithInvoices'])->name('accounts.getAccountsWithInvoices');
    Route::get('download-yearly-trial-balance/{id}', [AccountController::class, 'downloadTrialBalance'])->name('accounts.downloadTrialBalance');
    Route::get('view-account-statement-per-year/{id}', [AccountController::class, 'viewAccountStatement'])->name('accounts.viewAccountStatement');
    Route::get('download-account-statement-per-year/{id}', [AccountController::class, 'downloadClientStatement'])->name('accounts.downloadClientStatement');
    Route::get('download-supplier-statement-per-year/{id}', [AccountController::class, 'downloadSupplierStatement'])->name('accounts.downloadSupplierStatement');

    Route::get('view-all-transactions', [AccountController::class, 'viewAllTransactions'])->name('accounts.viewAllTransactions');
    Route::post('store-payment-invoice', [AccountController::class, 'storePaymentInvoice'])->name('accounts.storePaymentInvoice');
    Route::post('update-payment-invoice/{id}', [AccountController::class, 'updatePaymentInvoice'])->name('accounts.updatePaymentInvoice');
    Route::get('get-payment-invoice/{id}', [AccountController::class, 'downloadPaymentReceipt'])->name('accounts.downloadPaymentReceipt');
    Route::get('get-purchase-invoice/{id}', [AccountController::class, 'downloadPurchaseReceipt'])->name('accounts.downloadPurchaseReceipt');
    Route::get('get-payment-methods', [AccountController::class, 'getPaymentMethods'])->name('accounts.getPaymentMethods');

    Route::get('view-taxes', [AccountController::class, 'viewTaxes'])->name('accounts.viewTaxes');
    Route::post('store-tax', [AccountController::class, 'storeTax'])->name('accounts.storeTax');
    Route::post('update-tax/{id}', [AccountController::class, 'updateTax'])->name('accounts.updateTax');
    Route::get('delete-tax/{id}', [AccountController::class, 'deleteTax'])->name('accounts.deleteTax');

    Route::get('view-tax-brackets', [AccountController::class, 'viewTaxBrackets'])->name('accounts.viewTaxBrackets');
    Route::post('store-tax-bracket', [AccountController::class, 'storeTaxBracket'])->name('accounts.storeTaxBracket');
    Route::post('update-tax-bracket/{id}', [AccountController::class, 'updateTaxBracket'])->name('accounts.updateTaxBracket');
    Route::get('delete-tax-bracket/{id}', [AccountController::class, 'deleteTaxBracket'])->name('accounts.deleteTaxBracket');

    Route::get('view-all-purchase', [AccountController::class, 'viewPurchases'])->name('accounts.viewPurchases');
    Route::get('add-purchase-invoice', [AccountController::class, 'addPurchaseInvoice'])->name('accounts.addPurchaseInvoice');
    Route::post('store-purchase-invoice', [AccountController::class, 'storePurchaseInvoice'])->name('accounts.storePurchaseInvoice');
    Route::get('view-purchase-invoice/{id}', [AccountController::class, 'viewPurchaseInvoice'])->name('accounts.viewPurchaseInvoice');
    Route::get('post-purchase-created-invoice/{id}', [AccountController::class, 'postPurchaseInvoice'])->name('accounts.postPurchaseInvoice');
    Route::get('delete-purchase-sale-invoice/{id}', [AccountController::class, 'deletePurchaseInvoice'])->name('accounts.deletePurchaseInvoice');
    Route::get('edit-purchase-voucher/{id}', [AccountController::class, 'editPurchaseVoucher'])->name('accounts.editPurchaseVoucher');
    Route::post('update-purchase-voucher/{id}', [AccountController::class, 'updatePurchaseVoucher'])->name('accounts.updatePurchaseVoucher');
    Route::get('delete-purchase-item/{id}', [AccountController::class, 'deletePurchaseItem'])->name('accounts.deletePurchaseItem');
    Route::post('settle-purchase', [AccountController::class, 'getPurchaseDetails'])->name('getPurchaseDetails');
    Route::get('create-debit-note/{id}', [AccountController::class, 'createDebitNote'])->name('accounts.createDebitNote');
    Route::post('store-debit-note/{id}', [AccountController::class, 'storeDebitNote'])->name('accounts.storeDebitNote');

    Route::get('create-credit-note/{id}', [AccountController::class, 'createCreditNote'])->name('accounts.createCreditNote');
    Route::get('sales-invoice-distribution/{id}', [AccountController::class, 'salesInvoiceDistribution'])->name('accounts.salesInvoiceDistribution');
    Route::get('edit-sales-invoice/{id}', [AccountController::class, 'editSalesInvoice'])->name('accounts.editSalesInvoice');
    Route::post('store-credit-note/{id}', [AccountController::class, 'storeCreditNote'])->name('accounts.storeCreditNote');
    Route::post('update-sales-invoice/{id}', [AccountController::class, 'updateSalesInvoice'])->name('accounts.updateSalesInvoice');
    Route::get('delete-sales-invoice/{id}', [AccountController::class, 'deleteReceipt'])->name('accounts.deleteReceipt');

    Route::get('fetch-purchase-invoice_number', [AccountController::class, 'fetchPurchaseInvNumber'])->name('accounts.fetchPurchaseInvNumber');
    Route::get('download-purchase-invoice/{id}', [AccountController::class, 'downloadPurchaseVoucher'])->name('accounts.downloadPurchaseVoucher');

    Route::get('financial-year-purchases', [AccountController::class, 'purchaseFYTaxes'])->name('accounts.purchaseFYTaxes');
    Route::get('view-yearly-purchase-taxes/{id}', [AccountController::class, 'yearlyPurchaseTaxes'])->name('accounts.yearlyPurchaseTaxes');
    Route::get('view-purchase-tax-statement/{id}', [AccountController::class, 'purchaseTaxStatement'])->name('accounts.purchaseTaxStatement');
    Route::get('view-purchase-per-financial-year/{id}', [AccountController::class, 'yearlyPurchases'])->name('accounts.yearlyPurchases');


    Route::get('get-income-streams', [AccountController::class, 'getIncomeStreams'])->name('accounts.getIncomeStreams');
    Route::get('get-expenses-streams', [AccountController::class, 'getExpenseItems'])->name('accounts.getExpenseItems');

    Route::get('view-exchange-rate', [AccountController::class, 'exchangeRates'])->name('accounts.exchangeRates');
    Route::post('store-exchange-rate', [AccountController::class, 'addCurrencyExchangeRate'])->name('accounts.addCurrencyExchangeRate');
    Route::post('update-exchange-rate/{id}', [AccountController::class, 'updateCurrencyExchangeRate'])->name('accounts.updateCurrencyExchangeRate');
    Route::get('delete-exchange-rate/{id}', [AccountController::class, 'deleteCurrencyExchangeRate'])->name('accounts.deleteCurrencyExchangeRate');

    Route::any('generate-vat-tax-report', [AccountController::class, 'generateVatTaxReport'])->name('accounts.generateVatTaxReport');
    Route::any('generate-sales-summary/{id}', [AccountController::class, 'generateSalesSummary'])->name('accounts.generateSalesSummary');
    Route::any('generate-client-statement-summary', [AccountController::class, 'generateClientStatement'])->name('accounts.generateClientStatement');
    Route::any('generate-ledger-statement-summary', [AccountController::class, 'generateLedgerStatement'])->name('accounts.generateLedgerStatement');
    Route::any('generate-expense-ledger-statement-summary', [AccountController::class, 'generateExpenseLedgerStatement'])->name('accounts.generateExpenseLedgerStatement');
    Route::any('generate-all-expense-ledger-statement', [AccountController::class, 'generateAllExpenseLedgerStatement'])->name('accounts.generateAllExpenseLedgerStatement');
    Route::any('generate-all-ledger-statement', [AccountController::class, 'generateAllLedgerStatement'])->name('accounts.generateAllLedgerStatement');

    Route::get('view-transport-details', [AccountController::class, 'transportDetails'])->name('accounts.transportDetails');
    Route::any('export-transport-details', [AccountController::class, 'exportTransportReport'])->name('accounts.exportTransportReport');
    Route::get('view-deliveries', [AccountController::class, 'viewDeliveries'])->name('accounts.viewDeliveries');
    Route::post('download-stock-report', [AccountController::class, 'StockReport'])->name('accounts.StockReport');
    Route::any('view-all-shipments', [AccountController::class, 'viewShipments'])->name('accounts.viewShipments');
    Route::get('download-shipment/{id}', [AccountController::class, 'downloadShipment'])->name('accounts.downloadShipment');
    Route::post('download-shipment-report', [AccountController::class, 'shipmentReport'])->name('accounts.shipmentReport');

    Route::get('financial-year-ledger-statement', [AccountController::class, 'getLedgerFinancialYears'])->name('accounts.getLedgerFinancialYears');
    Route::get('view-yearly-ledger-statement/{id}', [AccountController::class, 'getLedgerWithInvoices'])->name('accounts.getLedgerWithInvoices');
    Route::get('view-ledger-statement/{id}', [AccountController::class, 'viewLedgerStatement'])->name('accounts.viewLedgerStatement');

    Route::get('view-p&l-financial-years', [AccountController::class, 'getPlFinancialYears'])->name('accounts.getPlFinancialYears');
    Route::get('view-yearly-expense-ledger-statement/{id}', [AccountController::class, 'getExpenseLedgerWithInvoices'])->name('accounts.getExpenseLedgerWithInvoices');
    Route::get('view-expense-ledger-statement/{id}', [AccountController::class, 'viewExpenseLedgerStatement'])->name('accounts.viewExpenseLedgerStatement');

    Route::post('update-opening-balance', [AccountController::class, 'updateOpeningBalance'])->name('accounts.updateOpeningBalance');

    Route::get('view-purchase-payments', [AccountController::class, 'viewPurchasePayments'])->name('accounts.viewPurchasePayments');
    Route::post('settle-purchase-selection', [AccountController::class, 'processPayment'])->name('accounts.processPayment');
    Route::post('update-purchase-payments/{id}', [AccountController::class, 'updatePurchasePaymentInvoice'])->name('accounts.updatePurchasePaymentInvoice');
    Route::get('delete-purchase-payments-invoice/{id}', [AccountController::class, 'deletePurchasePaymentInvoice'])->name('accounts.deletePurchasePaymentInvoice');
    Route::get('view-purchase-payments-distribution/{id}', [AccountController::class, 'purchaseVoucherDistribution'])->name('accounts.purchaseVoucherDistribution');
    Route::post('store-purchase-payment-invoice', [AccountController::class, 'storePurchasePaymentInvoice'])->name('accounts.storePurchasePaymentInvoice');
    Route::get('remove-payment-item/{id}', [AccountController::class, 'removePaymentItem'])->name('accounts.removePaymentItem');

    Route::get('aging-analysis-report', [AccountController::class, 'viewAgingAnalysis'])->name('accounts.viewAgingAnalysis');
    Route::get('view-aging-analysis-report/{id}', [AccountController::class, 'viewAgingReport'])->name('accounts.viewAgingReport');
    Route::post('view-aging-analysis-report/{id}', [AccountController::class, 'viewAgingReport'])->name('accounts.viewAgingReport');
    Route::get('view-aging-invoices/{id}', [AccountController::class, 'viewAgingInvoices'])->name('accounts.viewAgingInvoices');
    Route::any('download-aging-report/{id}', [AccountController::class, 'downloadAgingReport'])->name('accounts.downloadAgingReport');
    Route::any('download-debt-list-report/{id}', [AccountController::class, 'downloadDebtList'])->name('accounts.downloadDebtList');
    Route::any('download-account-aging-report/{id}', [AccountController::class, 'downloadAccountAgingReport'])->name('accounts.downloadAccountAgingReport');

    Route::get('update-transactions-invoices', [AccountController::class, 'updateTransactionsInvoices'])->name('accounts.updateTransactionsInvoices');

    /*System Journal Routes*/
    Route::get('view-system-journals', [AccountController::class, 'viewSystemJournals'])->name('accounts.viewSystemJournals');
    Route::get('fetch-credit-account', [AccountController::class, 'fetchCreditAccount'])->name('accounts.fetchCreditAccount');
    Route::get('get-credit-account', [AccountController::class, 'getCreditAccount'])->name('accounts.getCreditAccount');
    Route::post('store-system-journals', [AccountController::class, 'storeSystemJournals'])->name('accounts.storeSystemJournals');
    Route::post('update-system-journals/{id}', [AccountController::class, 'updateSystemJournals'])->name('accounts.updateSystemJournals');
    Route::get('scheduled-system-journals', [AccountController::class, 'viewScheduledSystemJournals'])->name('accounts.viewScheduledSystemJournals');
    Route::post('store-scheduled-system-journals', [AccountController::class, 'storeScheduledSystemJournals'])->name('accounts.storeScheduledSystemJournals');
    Route::post('update-scheduled-system-journals/{id}', [AccountController::class, 'updateScheduledSystemJournals'])->name('accounts.updateScheduledSystemJournals');
    Route::get('fetch-ledger-to-schedule-system-journals', [AccountController::class, 'fetchLedgerToScheduleJournal'])->name('accounts.fetchLedgerToScheduleJournal');
    Route::get('edit-selected-journal/{id}', [AccountController::class, 'editJournal'])->name('accounts.editJournal');
    Route::post('update-selected-journal/{id}', [AccountController::class, 'updateAdjustmentJournal'])->name('accounts.updateAdjustmentJournal');
    Route::get('download-selected-journal/{id}', [AccountController::class, 'downloadJournal'])->name('accounts.downloadJournal');
    Route::get('delete-selected-journal/{id}', [AccountController::class, 'deleteJournal'])->name('accounts.deleteJournal');

    Route::get('view-banks', [AccountController::class, 'viewBanks'])->name('accounts.viewBanks');
    Route::get('view-bank-statement/{id}', [AccountController::class, 'viewBankStatement'])->name('accounts.viewBankStatement');
    Route::post('update-bank-date', [AccountController::class, 'updateBankDate'])->name('accounts.updateBankDate');
    Route::get('reconcile-bank-statement', [AccountController::class, 'reconcileBankStatement'])->name('accounts.reconcileBankStatement');
    Route::get('view-reconciled-banks', [AccountController::class, 'viewReconciledBanks'])->name('accounts.viewReconciledBanks');
    Route::get('view-reconciled-bank-statement/{id}', [AccountController::class, 'viewReconciledBankStatement'])->name('accounts.viewReconciledBankStatement');
    Route::any('download-reconciled-bank-statement/{id}', [AccountController::class, 'downloadReconciledBankStatement'])->name('accounts.downloadReconciledBankStatement');
    Route::any('download-unreconciled-bank-statement/{id}', [AccountController::class, 'exportUnreconciledTransactions'])->name('accounts.exportUnreconciledTransactions');

    Route::post('settle-invoice-selection', [AccountController::class, 'processTransaction'])->name('accounts.processTransaction');
    Route::post('settle-wht-invoice-selection/{id}', [AccountController::class, 'processWHTPayment'])->name('accounts.processWHTPayment');
    Route::post('settle-invoice', [AccountController::class, 'getInvoiceDetails'])->name('settleInvoice');
    Route::post('settle-invoice-action', [AccountController::class, 'settleInvoice'])->name('settleInvoiceAction');
    Route::get('remove-transaction-item/{id}', [AccountController::class, 'removeTransactionItem'])->name('accounts.removeTransactionItem');

    Route::get('fetch-monthly-incomes-expenses', [AccountController::class, 'fetchMonthlyIncomesExpenses'])->name('accounts.fetchMonthlyIncomesExpenses');
    Route::get('fetch-top-income-streams', [AccountController::class, 'fetchTopIncomeStreams'])->name('accounts.fetchTopIncomeStreams');

    Route::get('get-petty-cash-payments', [AccountController::class, 'viewPettyCash'])->name('accounts.viewPettyCash');
    Route::get('fetch-petty-credit-account', [AccountController::class, 'fetchPettyCreditAccount'])->name('accounts.fetchPettyCreditAccount');
    Route::get('get-petty-credit-account', [AccountController::class, 'getPettyCreditAccount'])->name('accounts.getPettyCreditAccount');
    Route::post('store-petty-cash-purchase', [AccountController::class, 'storePettyCashPurchase'])->name('accounts.storePettyCashPurchase');
    Route::get('download-petty-cash-payment/{id}', [AccountController::class, 'downloadPettyCashPayment'])->name('accounts.downloadPettyCashPayment');
    Route::get('delete-petty-cash/{id}', [AccountController::class, 'deletePettyCash'])->name('accounts.deletePettyCash');
    Route::get('edit-petty-cash/{id}', [AccountController::class, 'editPettyCash'])->name('accounts.editPettyCash');
    Route::post('update-petty-cash/{id}', [AccountController::class, 'updatePettyCash'])->name('accounts.updatePettyCash');

    Route::get('yearly-p&l-statement/{id}', [AccountController::class, 'yearlyPlStatement'])->name('accounts.yearlyPlStatement');
    Route::post('download-yearly-p&l-statement/{id}', [AccountController::class, 'downloadPlStatement'])->name('accounts.downloadPlStatement');
    Route::post('/download-chart-of-accounts', [AccountController::class, 'downloadChartOfAccounts'])->name('accounts.downloadChartOfAccounts');

    Route::get('update-tax-records', [AccountController::class, 'updateTaxRecords'])->name('accounts.updateTaxRecords');
    Route::any('day-book', [AccountController::class, 'dayBook'])->name('accounts.dayBook');
    Route::any('export-day-book', [AccountController::class, 'exportDayBook'])->name('accounts.exportDayBook');

    Route::get('opening-balances', [AccountController::class, 'viewOpeningBalances'])->name('accounts.viewOpeningBalances');
    Route::post('store-opening-balance', [AccountController::class, 'storeOpeningBalance'])->name('accounts.storeOpeningBalance');
    Route::get('delete-opening-balance-entry/{id}', [AccountController::class, 'deleteOpeningBalance'])->name('accounts.deleteOpeningBalance');

    Route::any('search-results', [AccountController::class, 'searchInvoice'])->name('accounts.searchInvoice');
    Route::get('balance-sheet-financial-years', [AccountController::class, 'balanceSheetFy'])->name('accounts.balanceSheetFy');
    Route::get('balance-sheet-per-financial-year/{id}', [AccountController::class, 'showBalanceSheet'])->name('accounts.showBalanceSheet');
    Route::get('download-balance-sheet-per-financial-year/{id}', [AccountController::class, 'exportBalanceSheet'])->name('accounts.exportBalanceSheet');

    Route::post('import-journal-in-excel', [AccountController::class, 'importExcel'])->name('accounts.importExcel');

    Route::get('check-for-unique-transaction-code', [AccountController::class, 'uniqueTransactionCode'])->name('accounts.uniqueTransactionCode');


   /* stocks*/
    Route::get('internal-transfers', [AccountController::class, 'viewInternalTransfers'])->name('accounts.viewInternalTransfers');
    Route::post('internal-transfers', [AccountController::class, 'viewInternalTransfers'])->name('accounts.viewInternalTransfers');
    Route::get('view-internal-transfer-details/{id}', [AccountController::class, 'viewInternalTransferDetails'])->name('accounts.viewInternalTransferDetails');
    Route::get('approve-internal-transfer-request/{id}', [AccountController::class, 'approveInternalTransfer'])->name('accounts.approveInternalTransfer');
    Route::get('download-inter-transfer-delivery-note/{id}', [AccountController::class, 'downloadInterDelNote'])->name('accounts.downloadInterDelNote');
    Route::get('view-closing-stock-report', [AccountController::class, 'closingStockReport'])->name('accounts.closingStockReport');
    Route::post('view-closing-stock-report', [AccountController::class, 'closingStockReport'])->name('accounts.closingStockReport');

    Route::get('stock-collection-report', [AccountController::class, 'stockCollectionReport'])->name('accounts.stockCollectionReport');
    Route::post('stock-collection-report', [AccountController::class, 'stockCollectionReport'])->name('accounts.stockCollectionReport');


    Route::get('external-transfers', [AccountController::class, 'viewExternalTransfers'])->name('accounts.viewExternalTransfers');
    Route::post('external-transfers', [AccountController::class, 'viewExternalTransfers'])->name('accounts.viewExternalTransfers');
    Route::get('view-external-transfer-details/{id}', [AccountController::class, 'viewExternalTransferDetails'])->name('accounts.viewExternalTransferDetails');
    Route::get('approve-external-transfer-request/{id}', [AccountController::class, 'approveExternalTransfer'])->name('accounts.approveExternalTransfer');
    Route::get('download-extra-transfer-delivery-note/{id}', [AccountController::class, 'downloadExtraDelNote'])->name('accounts.downloadExtraDelNote');
    Route::get('download-extra-delivery-note/{id}', [AccountController::class, 'downloadDelNote'])->name('accounts.downloadDelNote');

    Route::get('list-of-unbilled-clients', [AccountController::class, 'unbilledClients'])->name('accounts.unbilledClients');
    Route::get('list-of-client-unbilled-teas/{id}', [AccountController::class, 'unbilledTeas'])->name('accounts.unbilledTeas');
    Route::post('update-bill-status', [AccountController::class, 'updateBillStatus'])->name('accounts.updateBillStatus');

    Route::get('notifications/list', [AccountController::class, 'list'])->name('accounts.notifications');
    Route::get('notifications/{id}', [AccountController::class, 'details'])->name('accounts.viewNotification');

});
