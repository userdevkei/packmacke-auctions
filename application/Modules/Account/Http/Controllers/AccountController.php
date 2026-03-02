<?php

namespace Modules\Account\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\Tasks\Entities\NotificationUser;
use Mpdf\Mpdf;
use Carbon\Carbon;
use App\Services\Log;
use App\Models\Client;
use App\Models\Driver;
use App\Models\UserInfo;
use App\Models\Transfers;
use App\Models\Warehouse;
use App\Services\AppClass;
use App\Models\Destination;
use App\Models\Transporter;
use App\Services\CustomIds;
use Illuminate\Http\Request;
use App\Models\DeliveryOrder;
use App\Services\ExportStock;
use App\Imports\ImportJournal;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Validation\Rule;
use PhpOffice\PhpWord\Settings;
use App\Models\ExternalTransfer;
use App\Services\ExportInvoices;
use PhpOffice\PhpWord\IOFactory;
use App\Services\ExportShipments;
use Modules\Account\Entities\Tax;
use App\Services\ExportAllLedgers;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Exports\ExportClosingStock;
use App\Exports\TrialBalanceExport;
use App\Models\ShippingInstruction;
use App\Services\BalanceSheetExport;
use App\Services\ExportTeaTransport;
use App\Services\ExportVATTaxReport;
use App\Services\TransactionsExport;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Clerk\Entities\Approval;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\Jc;
use App\Services\ExportLedgerSummary;
use Modules\Account\Entities\Account;
use Modules\Account\Entities\Invoice;
use Modules\Account\Entities\Payment;
use App\Exports\ExportStockCollection;
use App\Services\ClientsAgingAccounts;
use Modules\Account\Entities\Currency;
use Modules\Account\Entities\Purchase;
use Modules\Account\Entities\PettyCash;
use App\Services\ExportClientAgingStock;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpWord\TemplateProcessor;
use Modules\Account\Entities\InvoiceItem;
use Modules\Account\Entities\PaymentItem;
use Modules\Account\Entities\TaxBrackets;
use Modules\Account\Entities\Transaction;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ClientAccountAgingReport;
use Modules\Account\Entities\PurchaseItem;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Modules\Account\Entities\ClientAccount;
use Modules\Account\Entities\FinancialYear;
use Modules\Account\Entities\ForexExchange;
use Modules\Account\Entities\SystemJournal;
use NcJoes\OfficeConverter\OfficeConverter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Services\ExportShippingInstructions;
use Illuminate\Contracts\Support\Renderable;
use Modules\Account\Entities\ChartOfAccount;
use Modules\Account\Entities\OpeningBalance;
use Modules\Admin\Entities\OtherDestination;
use Modules\Admin\Entities\OtherTransporter;
use Modules\Account\Entities\JournalSchedule;
use Modules\Account\Entities\TransactionItem;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Mpdf\Output\Destination as PdfDestination;
use Modules\Account\Entities\AdjustmentJournal;
use App\Services\ExportUnreconciledTransactions;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Modules\Account\Entities\AccountSubCategories;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\Payments;

class AccountController extends Controller
{
    protected $logger, $appClass;
    public function __construct(Log $logger, AppClass $appClass)
    {
        $this->logger = $logger;
        $this->AppClass = $appClass;
    }
    public function index()
    {
        $accounts = Account::all();
        $group = AccountSubCategories::all();
        $ledger = ChartOfAccount::all();
        $incomes = ClientAccount::where('type', 1)->get();
        $expenses = ClientAccount::where('type', 2)->get();
        $fy = $this->AppClass->currentFinancialYear();

        $totalIncome = DB::table('invoices')
            ->selectRaw("
        SUM(CASE
            WHEN invoices.type = 1 THEN
                CASE
                    WHEN currencies.priority = 1 THEN amount_due  -- No conversion if KES
                    ELSE amount_due * (
                        SELECT fx2.exchange_rate
                        FROM forex_exchanges AS fx2
                        WHERE fx2.currency_id = client_accounts.currency_id
                          AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                        ORDER BY fx2.date_active DESC
                        LIMIT 1
                    )
                END
            WHEN invoices.type = 2 THEN
                CASE
                    WHEN currencies.priority = 1 THEN -amount_due  -- Deduct if type = 2 (No conversion if KES)
                    ELSE -amount_due * (
                        SELECT fx2.exchange_rate
                        FROM forex_exchanges AS fx2
                        WHERE fx2.currency_id = client_accounts.currency_id
                          AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                        ORDER BY fx2.date_active DESC
                        LIMIT 1
                    )
                END
            ELSE 0
        END) AS total_invoiced")
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->whereBetween('date_invoiced', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->whereNull('invoices.deleted_at')
            ->whereNull('currencies.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->first();

        $totalPaid = DB::table('transactions')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->selectRaw("
                    SUM(
                        CASE
                            WHEN currencies.priority = 1 THEN amount_received  -- No conversion if KES
                            ELSE amount_received * (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                  AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            )
                        END
                    ) AS total_paid
                ")
            ->whereBetween('transactions.date_received', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->whereNull('transactions.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('currencies.deleted_at')
            ->first();

        $totalExpense = DB::table('purchases')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->selectRaw("
        SUM(CASE
            WHEN purchases.type = 1 THEN
                CASE
                    WHEN currencies.priority = 1 THEN amount_due  -- No conversion if KES
                    ELSE amount_due * (
                        SELECT fx2.exchange_rate
                        FROM forex_exchanges AS fx2
                        WHERE fx2.currency_id = client_accounts.currency_id
                          AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                        ORDER BY fx2.date_active DESC
                        LIMIT 1
                    )
                END
            WHEN purchases.type = 2 THEN
                CASE
                    WHEN currencies.priority = 1 THEN -amount_due  -- Deduct if type = 2 (No conversion if KES)
                    ELSE -amount_due * (
                        SELECT fx2.exchange_rate
                        FROM forex_exchanges AS fx2
                        WHERE fx2.currency_id = client_accounts.currency_id
                          AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                        ORDER BY fx2.date_active DESC
                        LIMIT 1
                    )
                END
            ELSE 0
        END) AS total_expensed")
            ->whereBetween('date_invoiced', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->whereNull('purchases.deleted_at')
            ->whereNull('currencies.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->first();

        $totalSettled = DB::table('payments')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN currencies.priority = 1 THEN amount_received  -- No conversion if KES
                        ELSE amount_received * (
                            SELECT fx2.exchange_rate
                            FROM forex_exchanges AS fx2
                            WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                            ORDER BY fx2.date_active DESC
                            LIMIT 1
                        )
                    END
                ) AS total_settled
            ")
            ->whereNull('payments.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('currencies.deleted_at')
            ->whereBetween('date_received', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->first();

        return view('account::welcome')->with(['accounts' => $accounts, 'group' => $group, 'ledger' => $ledger, 'incomes' => $incomes, 'expenses' => $expenses, 'totalIncome' => $totalIncome, 'fy' => $fy, 'totalPaid' => $totalPaid, 'totalExpense' => $totalExpense, 'totalSettled' => $totalSettled]);
    }
    public function fetchTopIncomeStreams()
    {
        $fy = $this->AppClass->currentFinancialYear();
        $topIncomeStream = DB::table('invoice_items')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id')
            ->join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'ledger_id',
                'client_account_name',
                DB::raw("
            SUM(
                CASE
                    WHEN currencies.priority = 1 THEN quantity * unit_price
                    ELSE (quantity * unit_price) * (
                        SELECT fx2.exchange_rate
                        FROM forex_exchanges AS fx2
                        WHERE fx2.currency_id = client_accounts.currency_id
                          AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                        ORDER BY fx2.date_active DESC
                        LIMIT 1
                    )
                END
            ) AS total_income
        "),
                DB::raw('SUM(quantity) AS frequency')
            )
            ->whereNull('invoice_items.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->where(['client_accounts.type' => 1, 'invoices.type' => 1])
            ->whereBetween('invoices.date_invoiced', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->groupBy('ledger_id', 'client_account_name')
            ->orderByDesc('total_income')
            ->get();


        return response()->json($topIncomeStream);
    }
    public function fetchMonthlyIncomesExpenses()
    {
        // Fetch the current financial year
        $fy = $this->AppClass->currentFinancialYear();

        // Fetch Monthly Incomes (from invoices table)

        $monthlyIncomes = DB::table('invoices')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->selectRaw("
        DATE_FORMAT(FROM_UNIXTIME(invoices.date_invoiced), '%Y-%m') AS month,
        SUM(
            CASE
                WHEN currencies.priority = 1 THEN
                    CASE
                        WHEN invoices.type = 2 THEN -invoices.amount_due  -- Deduct if type is 2
                        ELSE invoices.amount_due
                    END  -- No conversion if KES (priority = 1)
                ELSE
                    CASE
                        WHEN invoices.type = 2 THEN -invoices.amount_due  -- Deduct if type is 2
                        ELSE invoices.amount_due * (
                            SELECT fx2.exchange_rate
                            FROM forex_exchanges AS fx2
                            WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                            ORDER BY fx2.date_active DESC
                            LIMIT 1
                        )
                    END
            END
        ) AS total_income
    ")
            ->whereBetween('invoices.date_invoiced', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->groupByRaw("DATE_FORMAT(FROM_UNIXTIME(invoices.date_invoiced), '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(FROM_UNIXTIME(invoices.date_invoiced), '%Y-%m')")
            ->whereNull('invoices.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('currencies.deleted_at')
            ->get();


        $monthlyExpenses = DB::table('purchases')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->selectRaw("
        DATE_FORMAT(FROM_UNIXTIME(purchases.date_invoiced), '%Y-%m') AS month,
        SUM(
            CASE
                WHEN currencies.priority = 1 THEN purchases.amount_due  -- No conversion if KES (priority = 1)
                ELSE purchases.amount_due * (
                    SELECT fx2.exchange_rate
                    FROM forex_exchanges AS fx2
                    WHERE fx2.currency_id = client_accounts.currency_id
                      AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                    ORDER BY fx2.date_active DESC
                    LIMIT 1
                )
            END
        ) AS total_expense
    ")
            ->whereBetween('purchases.date_invoiced', [strtotime($fy->year_starting), strtotime($fy->year_ending)])
            ->groupByRaw("DATE_FORMAT(FROM_UNIXTIME(purchases.date_invoiced), '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(FROM_UNIXTIME(purchases.date_invoiced), '%Y-%m')")
            ->whereNull('purchases.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('currencies.deleted_at')
            ->get();

        // Prepare the month range for the financial year
        $startDate = Carbon::parse($fy->year_starting);
        $endDate = Carbon::parse($fy->year_ending);

        $labels = [];
        $incomes = [];
        $expenses = [];

        // Create a list of all months in the financial year
        $currentMonth = $startDate->copy();
        while ($currentMonth->lte($endDate)) {
            $monthStr = $currentMonth->format('Y-m');
            $labels[] = $monthStr;

            // Set default values for income and expenses
            $incomes[$monthStr] = 0;
            $expenses[$monthStr] = 0;

            // Move to the next month
            $currentMonth->addMonth();
        }

        // Add the actual income data to the array
        foreach ($monthlyIncomes as $income) {
            $incomes[$income->month] = $income->total_income;
        }

        // Add the actual expense data to the array
        foreach ($monthlyExpenses as $expense) {
            $expenses[$expense->month] = $expense->total_expense;
        }

        // Sort the months (this will ensure they are in chronological order)
        sort($labels);

        // Prepare the response
        $response = [
            'labels' => $labels,
            'incomes' => array_values($incomes),
            'expenses' => array_values($expenses)
        ];

        return response()->json($response);
    }
    public function viewInvoices()
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->whereBetween('invoices.created_at', [$currentMonthStart, $currentMonthEnd]) // Filter by current month
            ->select(
                'invoices.invoice_id',
                'currency_symbol',
                'client_accounts.client_account_name as clientAccount',
                'invoice_number',
                'date_invoiced',
                'due_date',
                'invoices.financial_year_id',
                'amount_due',
                'year_starting',
                'year_ending',
                'invoices.status',
                'posted',
                'kra_number',
                'invoices.type'
            )
            ->orderBy('invoices.date_invoiced', 'desc')
            ->orderBy('invoices.invoice_number', 'desc')
            ->get();

        return view('account::sales.index')->with(['invoices' => $invoices]);
    }
    public function yearlyInvoices($id)
    {
        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->select('invoices.invoice_id', 'currency_symbol', 'client_accounts.client_account_name as clientAccount', 'invoice_number', 'date_invoiced', 'due_date', 'invoices.financial_year_id', 'amount_due', 'year_starting', 'year_ending', 'invoices.status', 'posted', 'kra_number', 'invoices.type')
            ->where('invoices.financial_year_id', $id)
            ->orderBy('invoices.date_invoiced', 'desc')
            ->orderBy('invoices.invoice_number', 'desc')
            ->get();

        return view('account::sales.index')->with(['invoices' => $invoices]);
    }
    public function yearlyReceipts($id)
    {
        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'transactions.financial_year_id')
            ->leftJoin('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->select('transactions.transaction_id', 'invoice_number', 'transaction_code', 'client_accounts.client_account_name', 'amount_received', 'acc.client_account_name as account', 'year_starting', 'year_ending', 'date_received', 'acc.type', 'currency_symbol', 'client_id', 'account_id', 'transactions.description', 'transactions.financial_year_id', 'si_number', 'exchange_rate')
            ->selectRaw('SUM(CASE WHEN transaction_items.type = 1 THEN transaction_items.amount_settled ELSE 0 END) as amount_settled')
            ->groupBy('transactions.transaction_id', 'invoice_number', 'transaction_code', 'client_account_name', 'amount_received', 'account', 'year_starting', 'year_ending', 'date_received', 'acc.type', 'currency_symbol', 'client_id', 'account_id', 'description', 'financial_year_id', 'si_number', 'exchange_rate')
            ->orderBy('transactions.created_at', 'desc')
            ->whereNull('transactions.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('transaction_items.deleted_at')
            ->where(['client_accounts.account_status' => 1, 'acc.account_status' => 1, 'transactions.financial_year_id' => $id])
            ->get();

        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        $accounts = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            //            ->where(['client_accounts.type' => 7])
            ->whereNull('client_accounts.deleted_at')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol')
            ->orderBy('client_account_name', 'asc')
            ->where(['client_accounts.account_status' => 1])
            ->get();

        return view('account::sales.transactions')->with(['transactions' => $transactions, 'years' => $years, 'accounts' => $accounts]);
    }
    public function postInvoice(Request $request, $id)
    {
        Invoice::find($id)->update(['posted' => 1]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Invoice successfully posted');
    }
    public function postPurchaseInvoice(Request $request, $id)
    {
        Purchase::find($id)->update(['posted' => 1]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Voucher successfully posted');
    }
    public function viewInvoice($id)
    {
        $invoices = Invoice::join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'invoice_items.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_account_name as account_name', 'tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'tax_name', 'posted', 'kra_number')
            ->where(['invoices.invoice_id' => $id])
            ->where('client_accounts.type', '!=', 3)
            ->whereNull('tax_brackets.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();

        $account = ClientAccount::join('invoices', 'invoices.client_id', '=', 'client_accounts.client_account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('invoice_number', 'due_date', 'date_invoiced', 'invoices.status', 'currency_symbol', 'client_account_name', 'invoices.invoice_id')
            ->where('invoice_id', $id)->first();

        return view('account::sales.viewInvoice')->with(['invoices' => $invoices, 'account' => $account]);
    }
    public function deleteInvoice($id)
    {
        Invoice::find($id)->delete();
        InvoiceItem::where('invoice_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Invoice successfully deleted');
    }
    public function deleteInvoiceItem($id)
    {
        DB::beginTransaction();
        try {
            $totalDeduction = 0;
            $taxAmount = 0;
            $item = InvoiceItem::find($id);
            $invoice = Invoice::where('invoice_id', $item->invoice_id)->first();
            $totalAmount = floatval($item->unit_price) * $item->quantity;
            if ($item->tax_id) {
                $tax = TaxBrackets::where('tax_bracket_id', $item->tax_id)->first();
                $totalTax = floatval($totalAmount * ($tax->tax_rate)) / 100;
                $totalDeduction = $totalAmount + $totalTax;
                $salesVat = ClientAccount::where('client_account_number', '2203001')->first();
                $taxLedger = InvoiceItem::where(['invoice_id' => $item->invoice_id, 'ledger_id' => $salesVat->client_account_id])->first();
                $taxDifference = floatval($taxLedger->unit_price - $totalTax);
                $taxLedger->update(['unit_price' => $taxDifference]);
            } else {
                $totalDeduction = $totalAmount;
            }
            $newDueAmount = floatval($invoice->amount_due - $totalDeduction);
            $invoice->update(['amount_due' => $newDueAmount]);
            $item->delete();
            DB::commit();
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Invoice Updated Successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function downloadInvoice($id)
    {
        $inv = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'invoices.user_id')
            ->leftJoin('destinations', 'destinations.destination_id', '=', 'invoices.destination_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.invoice_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'invoice_items.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->join('client_accounts as cc', 'cc.client_account_id', '=', 'invoice_items.ledger_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->select('invoices.type', 'invoice_number', 'si_number', 'customer_message', 'client_accounts.client_address', 'client_accounts.kra_pin', 'kra_number', 'port_name', 'container_type', 'date_invoiced', 'due_date', 'invoice_items.quantity', 'invoice_items.unit_price', 'invoice_items.quantity', 'currency_symbol', 'cc.client_account_name as ledgerName', 'client_accounts.client_account_name', 'surname', 'first_name', 'year_starting', 'year_ending', 'tax_rate', 'tax_name', 'client_accounts.currency_id', 'invoice_items.description as hscode', 'inv_reference')
            ->where('invoices.invoice_id', $id)
            ->whereNot('cc.type', 3)
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->orderBy('ledgerName', 'asc')
            ->get()
            ->map(function ($item) {
                // Sanitize all fields in each row
                return collect($item)->map(function ($value) {
                    return is_string($value) ? htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') : $value;
                });
            });

        $values = $inv->first();
        $invDate = Carbon::createFromTimestamp($values['date_invoiced'])->format('Y-m-d');
        $forex = ForexExchange::where('currency_id', $values['currency_id'])
            ->where('date_active', '<=', $invDate)
            ->orderBy('date_active', 'desc')
            ->first();

        $type = $values['type'] == 1 ? 'SALES' : 'CREDIT NOTE';
        $narration = $values['customer_message'] . ' INVOICE TO BE SETTLED BY OR BEFORE ' . Carbon::createFromTimestamp($values['due_date'])->format('D, d-m-Y');
        $creditNote = $values['type'] == 2 ? 'Relevant Invoice Number : ' . $values['inv_reference'] : null;

        $fYear = Carbon::parse($values['year_starting'])->format('Y') == Carbon::parse($values['year_ending'])->format('Y') ? Carbon::parse($values['year_starting'])->format('Y') : Carbon::parse($values['year_starting'])->format('Y') . '/' . Carbon::parse($values['year_ending'])->format('y');

        $domPdfPath = base_path('vendor/dompdf/dompdf');
        Settings::setPdfRendererPath($domPdfPath);
        Settings::setPdfRendererName('DomPDF');

        $table = new Table(['unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT, 'width' => 100 * 50, 'align' => 'center']);

        $header = ['size' => 10, 'name' => 'Cambria', 'space' => ['before' => 100, 'after' => 100], 'bold' => true];
        $text = ['size' => 9, 'name' => 'Cambria', 'space' => ['before' => 100, 'after' => 100], 'bold' => false];

        $table->addRow();
        $table->addCell(500, ['borderSize' => 1, 'align' => 'center', 'bgColor' => 'cccccc'])->addText('#', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(900, ['borderSize' => 1, 'align' => 'center', 'bgColor' => 'cccccc'])->addText('HS CODE', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(2500, ['borderSize' => 1, 'align' => 'center', 'bgColor' => 'cccccc'])->addText('INVOICE ITEM', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(600, ['borderSize' => 1, 'align' => 'center', 'bgColor' => 'cccccc'])->addText('QTY', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(900, ['borderSize' => 1, 'align' => 'center', 'bgColor' => 'cccccc'])->addText('UNIT PRICE', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1200, ['borderSize' => 1, 'align' => 'center', 'bgColor' => 'cccccc'])->addText('TOTAL PRICE', $header, ['space' => ['before' => 100, 'after' => 100]]);

        $amountDue = 0;
        $totalTax = 0;

        foreach ($inv as $key => $invoiceItem) {
            $table->addRow();
            $table->addCell(500, ['borderSize' => 1, 'align' => 'center'])->addText(++$key, $text, ['space' => ['before' => 100, 'after' => 100]]);
            $table->addCell(900, ['borderSize' => 1, 'align' => 'center'])->addText($invoiceItem['hscode'], $text, ['space' => ['before' => 100, 'after' => 100]]);
            $table->addCell(2500, ['borderSize' => 1, 'align' => 'center'])->addText($invoiceItem['ledgerName'], $text, ['space' => ['before' => 100, 'after' => 100]]);
            $table->addCell(600, ['borderSize' => 1, 'align' => 'center'])->addText($invoiceItem['quantity'], $text, ['space' => ['before' => 100, 'after' => 100]]);
            $table->addCell(900, ['borderSize' => 1, 'align' => 'center'])->addText(number_format($invoiceItem['unit_price'], 3), $text, ['space' => ['before' => 100, 'after' => 100]]);
            $table->addCell(1200, ['borderSize' => 1, 'align' => 'center'])->addText(number_format($invoiceItem['quantity'] * $invoiceItem['unit_price'], 3), $text, ['space' => ['before' => 100, 'after' => 100]]);

            $taxRate = $invoiceItem['tax_rate'] == null ? 0 : $invoiceItem['tax_rate'];
            $totalTax +=  floatval($taxRate) / 100 * ($invoiceItem['quantity'] * $invoiceItem['unit_price']);
            $amountDue += $invoiceItem['quantity'] * $invoiceItem['unit_price'];
        }

        $table->addRow();

        $table->addCell(null, ['gridSpan' => 4])->addText('');
        $table->addCell(null, ['gridSpan' => 1])->addText('SUBTOTAL', $header, ['size' => 7, 'space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(null, ['gridSpan' => 1, 'borderBottomSize' => 1])->addText($invoiceItem['currency_symbol'] . ' ' . number_format($amountDue, 2), $header, ['size' => 8, 'space' => ['before' => 100, 'after' => 100]]);

        $table->addRow();
        $table->addCell(null, ['gridSpan' => 4])->addText('');
        $table->addCell(null, ['gridSpan' => 1])->addText('TOTAL TAX', $header, ['size' => 7, 'space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(null, ['gridSpan' => 1, 'borderBottomSize' => 1])->addText($invoiceItem['currency_symbol'] . ' ' . number_format($totalTax, 2), $header, ['size' => 8, 'space' => ['before' => 100, 'after' => 100]]);

        $table->addRow();
        $table->addCell(null, ['gridSpan' => 4])->addText('');
        $table->addCell(null, ['gridSpan' => 1, 'borderBottomSize' => 4])->addText('AMOUNT DUE', $header, ['size' => 7, 'space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(null, ['gridSpan' => 1, 'borderBottomSize' => 4])->addText($invoiceItem['currency_symbol'] . ' ' . number_format($totalTax + $amountDue, 2), $header, ['size' => 8, 'space' => ['before' => 100, 'after' => 100]]);

        $table->addRow();
        $table->addCell(null, ['gridSpan' => 6])->addText('');

        $fNarration = null;
        $usdNarration = null;
        if ($forex) {
            $fNarration = 'Exchange rate of Ksh.' . $forex['exchange_rate'] . ' was applied. Total amount due Ksh.' . number_format(($totalTax + $amountDue) * $forex['exchange_rate'], 2);
            $usdNarration = 'Kindly Pay All USD Invoices In USD Currency';
        }

        $invoice = new TemplateProcessor(storage_path('client_invoice.docx'));
        $invoice->setComplexBlock('{table}', $table);
        $invoice->setValue('clientName', $values['client_account_name']);
        $invoice->setValue('invNumber', $values['invoice_number']);
        $invoice->setValue('fYear', $fYear);
        $invoice->setValue('type', $type);
        $invoice->setValue('ref', $values['type'] == 1 ? 'DUE DATE' : 'INV. REF');
        $invoice->setValue('siNumber', $values['si_number']);
        $invoice->setValue('pinNo', $values['kra_pin']);
        $invoice->setValue('usdNarration', $usdNarration);
        $invoice->setValue('cuNumber', $values['kra_number']);
        $invoice->setValue('conts', $values['container_type']);
        $invoice->setValue('destination', $values['port_name']);
        $invoice->setValue('invoice', $fNarration);
        $invoice->setValue('clientAddress', $values['client_address']);
        $invoice->setValue('narration', $narration);
        $invoice->setValue('exchangeRate', $forex == null ? null : 'Exchange Rate : ' . number_format($forex->exchange_rate, 2));
        $invoice->setValue('creditNoteReference', $creditNote);
        $invoice->setValue('printer', auth()->user()->user->surname . ' ' . auth()->user()->user->first_name);
        $invoice->setValue('user', $values['surname'] . ' ' . $values['first_name']);
        $invoice->setValue('date', Carbon::now()->format('D, d M Y H:i:s'));
        $invoice->setValue('invDate', Carbon::createFromTimestamp($values['date_invoiced'])->format('d/m/Y'));
        $invoice->setValue('dueDate', $values['type'] == 1 ? Carbon::createFromTimestamp($values['due_date'])->format('d/m/Y') : $values['inv_reference']);
        $docPath = 'Files/' . $values['invoice_number'] . '.docx';
        $invoice->saveAs($docPath);

        $phpWord = IOFactory::load($docPath);
        $contents = \PhpOffice\PhpWord\IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/' . $values['invoice_number'] . ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo($values['invoice_number'] . ".pdf");
        unlink($docPath);

        return view('account::sales.printInvoice', ['pdfPath' => $pdfPath]);
    }
    public function addInvoice()
    {
        $accounts = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol')
            ->orderBy('client_account_name', 'asc')
            ->where(['client_accounts.type' => 7])
            ->get();
        $financialYears = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });

        $taxes = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')->where(['tax_brackets.status' => 1, 'effect' => 1])->orderBy('tax_name', 'asc')->get();
        $items =  $accounts->where('account_type', 1);
        $debtors = $accounts->where('account_type', 2);
        $destinations = Destination::where('status', 1)->get();
        return view('account::sales.addInvoice')->with(['debtors' => collect($debtors), 'items' => collect($items), 'financialYears' => $financialYears, 'taxes' => $taxes, 'destinations' => $destinations]);
    }
    public function createCreditNote($id)
    {
        $invoice = Invoice::join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id')
            ->join('client_accounts as client', 'client.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'invoice_items.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_accounts.client_account_name as account_name', 'invoice_number', 'client.client_account_name as client_name', 'tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'tax_name', 'posted', 'kra_number', 'invoice_items.description', 'ledger_id', 'invoices.invoice_id', 'invoice_items.tax_id', 'invoice_item_id')
            ->where(['invoices.invoice_id' => $id])
            ->where('client_accounts.type', '!=', '3')
            ->whereNull('tax_brackets.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();
        return view('account::sales.creditNote')->with(['invoice' => $invoice]);
    }
    public function createDebitNote($id)
    {
        $invoice = Purchase::join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('client_accounts as client', 'client.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'purchase_items.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_accounts.client_account_name as account_name', 'invoice_number', 'client.client_account_name as client_name', 'tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'tax_name', 'posted', 'kra_number', 'purchase_items.description', 'ledger_id', 'purchases.purchase_id', 'purchase_items.tax_id', 'voucher_number', 'purchase_item_id')
            ->where(['purchases.purchase_id' => $id])
            ->where('client_accounts.type', '!=', '3')
            ->whereNull('tax_brackets.deleted_at')
            ->whereNull('purchase_items.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();
        return view('account::purchases.debitNote')->with(['invoice' => $invoice]);
    }
    public function editSalesInvoice($id)
    {
        $invoice = Invoice::join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id')
            ->join('client_accounts as client', 'client.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'invoice_items.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_accounts.client_account_name as account_name', 'client.client_account_id as client_id', 'invoice_number', 'client.client_account_name as client_name', 'tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'tax_name', 'posted', 'kra_number', 'invoice_items.description', 'ledger_id', 'invoices.invoice_id', 'invoice_items.tax_id', 'financial_year_id', 'date_invoiced', 'due_date', 'si_number', 'container_type', 'destination_id', 'customer_message', 'invoice_item_id', 'client_accounts.client_account_id', 'currencies.currency_id', 'consignee')
            ->where(['invoices.invoice_id' => $id])
            ->whereNot('client_accounts.type', 3)
            ->whereNull('tax_brackets.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            //            ->whereNull( 'tax_brackets.deleted_at')
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();

        $financialYears = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });

        $taxes = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')->where(['tax_brackets.status' => 1, 'effect' => 1])->orderBy('tax_name', 'asc')->get();
        $items =  $invoice->where('account_type', 1);
        $debtors = $invoice->where('account_type', 2);
        $clients = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol')
            ->orderBy('client_account_name', 'asc')
            ->where(['client_accounts.type' => 7])
            ->get();
        $invoiceItems =  ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->where(['client_accounts.currency_id' => $invoice[0]->currency_id])
            ->whereIn('type', [1, 3])
            ->orderBy('client_account_name')
            ->get();
        $destinations = Destination::where('status', 1)->get();
        $taxRates = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')->select('tax_rate', 'taxes.tax_id', 'tax_bracket_id')->where(['effect' => 1, 'tax_brackets.status' => 1])->first();

        return view('account::sales.editSalesInvoice')->with(['invoice' => $invoice, 'financialYears' => $financialYears, 'taxes' => $taxes, 'destinations' => $destinations, 'debtors' => collect($debtors), 'items' => collect($items), 'taxRates' => $taxRates, 'invoiceItems' => $invoiceItems, 'clients' => $clients]);
    }
    public function updateSalesInvoice(Request $request, $id)
    {
        if ($request->totalAmount == null) {
            return redirect()->back()->with('error', 'You have not made any changes to your invoice');
        }
        DB::beginTransaction();
        try {

            if($request->destination == 'other'){
                $port = Destination::firstOrCreate(
                    ['port_name' => $request->destination_name], // Match your input name
                    [
                        'destination_id' => (new CustomIds())->generateId(),
                        'country_name' => $request->destination_name,
                        'status' => 1,
                        'created_by' => auth()->user()->user_id
                    ]
                );
                $destination = $port->destination_id; // Use -> instead of ['']
            } else {
                $destination = $request->destination;
            }

            $storedInvoice = Invoice::where('invoice_id', $id)->first();
            $invoice = ['date_invoiced' => strtotime($request->invoiceDate), 'due_date' => strtotime($request->dueDate), 'customer_message' => $request->reason, 'container_type' => $request->container, 'si_number' => $request->siNumber, 'destination_id' => $destination, 'financial_year_id' => $request->financialYear, 'amount_due' => number_format(floatval($request->totalAmount + $request->totalTaxAmount), 3, '.', ''), 'consignee' => $request->consignee, 'client_id' => $request->accountId];

            Invoice::where('invoice_id', $id)->update($invoice);
            foreach ($request->creditItems as $keyItem => $invoice) {
                if (InvoiceItem::where(['invoice_item_id' => $keyItem, 'invoice_id' => $id])->exists()) {
                    $invoiceItems = ['ledger_id' => $invoice['ledger_id'], 'quantity' => $invoice['credit_quantity'], 'unit_price' => $invoice['credit_rate'], 'tax_id' => $invoice['vat'] == 0 ? null : $invoice['credit_tax'], 'description' => $invoice['description'],];

                    InvoiceItem::where('invoice_item_id', $keyItem)->update($invoiceItems);
                } else {
                    $invoiceItem = ['invoice_item_id' => (new CustomIds())->generateId(), 'ledger_id' => $invoice['ledger_id'], 'invoice_id' => $id, 'quantity' => $invoice['credit_quantity'], 'unit_price' => $invoice['credit_rate'], 'tax_id' => $invoice['vat'] == 0 ? null : $invoice['credit_tax'], 'description' => $invoice['description'],];

                    InvoiceItem::create($invoiceItem);
                }
            }
            if ($request->totalTaxAmount !== '0.00') {
                $invoiceAmount = $request->totalTaxAmount;
                $vatTax = ['debit' => $invoiceAmount, 'credit' => '0.00'];
                $salesVat = ClientAccount::where('client_account_number', '2203001')->first();
                if (InvoiceItem::where(['invoice_id' => $id, 'ledger_id' => $salesVat->client_account_id])->exists()) {
                    $vatItem = ['quantity' => 1, 'unit_price' => $request->totalTaxAmount,];
                    InvoiceItem::where(['invoice_id' => $id, 'ledger_id' => $salesVat->client_account_id])->update($vatItem);
                } else {
                    $vatItem = ['invoice_item_id' => (new CustomIds())->generateId(), 'invoice_id' => $storedInvoice->invoice_id, 'ledger_id' => $salesVat->client_account_id, 'description' => 'VAT FOR INV. NUMBER ' . $storedInvoice->invoice_number, 'quantity' => 1, 'unit_price' => $request->totalTaxAmount, 'tax_id' => null,];
                    InvoiceItem::create($vatItem);
                }
            } else {
                $salesVat = ClientAccount::where('client_account_number', '2203001')->first();
                if (InvoiceItem::where(['invoice_id' => $id, 'ledger_id' => $salesVat->client_account_id])->exists()) {
                    InvoiceItem::where(['invoice_id' => $id, 'ledger_id' => $salesVat->client_account_id])->delete();
                }
            }
            DB::commit();
            $this->logger->create();
            return redirect()->route('accounts.viewInvoices')->with('success', 'Success! Invoice Updated Successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function storeCreditNote(Request $request, $id)
    {
        $creditItems = array_filter($request->creditItems, function ($creditItem) {
            // Check if all required keys exist in the delivery array
            return array_key_exists('credit_quantity', $creditItem)
                && array_key_exists('credit_rate', $creditItem)
                // Check if any of the values are null
                && $creditItem['credit_quantity'] !== null
                && $creditItem['credit_quantity'] > 0
                && $creditItem['credit_rate'] !== null
                && $creditItem['credit_rate'] > 0;
        });

        if ($creditItems == null) {
            return back()->with('error', 'Oops! Select invoice item(s) to add a credit note');
        }

        DB::beginTransaction();
        try {
            $invoiceId = (new CustomIds())->generateId();
            $invoiceNumber = Invoice::newCreditNote();
            $invCreditNote = Invoice::find($id);
            $invoice = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'client_id' => $invCreditNote->client_id,
                'date_invoiced' => strtotime($request->creditNoteDate),
                'due_date' => $invCreditNote->due_date,
                'customer_message' => 'CREDIT NOTE FOR ' . $invCreditNote->invoice_number . ' ' . $request->customerMessage,
                'container_type' => $invCreditNote->container_type,
                'si_number' => $invCreditNote->si_number,
                'destination_id' => $invCreditNote->destination_id,
                'financial_year_id' => $invCreditNote->financial_year_id,
                'amount_due' => number_format($request->totalAmount, 3, '.', ''),
                'status' => 1,
                'posted' => 1,
                'type' => 2,
                'user_id' => auth()->user()->user_id,
                'inv_reference' => $invCreditNote->invoice_number
            ];

            if (number_format($invCreditNote->amount_due, 3, '.', '') == number_format($request->totalAmount, 2, '.', '')) {
                $invCreditNote->update(['status' => 1]);
            }
            Invoice::create($invoice);

            foreach ($creditItems as $keyItem => $invoice) {
                $invoiceItems = [
                    'invoice_item_id' => (new CustomIds())->generateId(),
                    'invoice_id' => $invoiceId,
                    'ledger_id' => $invoice['ledger_id'],
                    'quantity' => $invoice['credit_quantity'],
                    'unit_price' => number_format($invoice['credit_rate'], 3, '.', ''),
                    'tax_id' => $invoice['credit_tax'],
                    'description' => $invoice['credit_tax'] == null ? '0001.12.00' : null
                ];
                InvoiceItem::create($invoiceItems);
            }

            if (floatval($request->totalTaxAmount) > 0) {
                $purchaseVAT = ClientAccount::where('client_account_number', '2203001')->first();
                $vatItem = [
                    'invoice_item_id' => (new CustomIds())->generateId(),
                    'invoice_id' => $invoiceId,
                    'ledger_id' => $purchaseVAT->client_account_id,
                    'description' => 'VAT FOR INV. NUMBER ' . $invoiceNumber,
                    'quantity' => 1,
                    'unit_price' => number_format($request->totalTaxAmount, 2, '.', ''),
                    'tax_id' => null,
                ];
                InvoiceItem::create($vatItem);
            }

            DB::commit();
            $this->logger->create();
            return redirect()->route('accounts.viewInvoices')->with('success', 'Success! Credit Note created successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function storeDebitNote(Request $request, $id)
    {
        $creditItems = array_filter($request->creditItems, function ($creditItem) {
            // Check if all required keys exist in the delivery array
            return array_key_exists('credit_quantity', $creditItem)
                && array_key_exists('credit_rate', $creditItem)
                // Check if any of the values are null
                && $creditItem['credit_quantity'] !== null
                && $creditItem['credit_quantity'] > 0
                && $creditItem['credit_rate'] !== null
                && $creditItem['credit_rate'] > 0;
        });

        if ($creditItems == null) {
            return back()->with('error', 'Oops! Select invoice item(s) to add a credit note');
        }
        DB::beginTransaction();
        try {
            $purchaseId = (new CustomIds())->generateId();
            $invoiceNumber = Purchase::newDebitNote();
            $invCreditNote = Purchase::find($id);
            $invoice = [
                'purchase_id' => $purchaseId,
                'invoice_number' => $invCreditNote->invoice_number,
                'voucher_number' => $invoiceNumber,
                'client_id' => $invCreditNote->client_id,
                'tax_id' => $invCreditNote->tax_id,
                'date_invoiced' => strtotime($request->creditNoteDate),
                'due_date' => $invCreditNote->due_date,
                'customer_message' => 'DEBIT NOTE FOR ' . $invCreditNote->voucher_number . ' ' . $request->customerMessage,
                'financial_year_id' => $invCreditNote->financial_year_id,
                'amount_due' => number_format($request->totalAmount, 3, '.', ''),
                'status' => 1,
                'posted' => 1,
                'type' => 2,
                'user_id' => auth()->user()->user_id,
                'inv_reference' => $invCreditNote->voucher_number
            ];

            if (number_format($invCreditNote->amount_due, 2, '.', '') == number_format($request->totalAmount, 2, '.', '')) {
                $invCreditNote->update(['status' => 1]);
            }
            Purchase::create($invoice);

            foreach ($creditItems as $keyItem => $invoice) {
                $invoiceItems = [
                    'purchase_item_id' => (new CustomIds())->generateId(),
                    'purchase_id' => $purchaseId,
                    'ledger_id' => $invoice['ledger_id'],
                    'quantity' => $invoice['credit_quantity'],
                    'unit_price' => number_format($invoice['credit_rate'], 3, '.', ''),
                    'tax_id' => $invoice['credit_tax'],
                    'description' => 'DEBIT NOTE FOR ' . $invCreditNote->voucher_number
                ];

                PurchaseItem::create($invoiceItems);
            }
            if (floatval($request->totalTaxAmount) > 0) {
                $purchaseVAT = ClientAccount::where('client_account_number', '2203004')->first();
                $vatItem = [
                    'purchase_item_id' => (new CustomIds())->generateId(),
                    'purchase_id' => $purchaseId,
                    'ledger_id' => $purchaseVAT->client_account_id,
                    'description' => 'VAT FOR INV. NUMBER ' . $invoiceNumber,
                    'quantity' => 1,
                    'unit_price' => number_format($request->totalTaxAmount, 2, '.', ''),
                    'tax_id' => null,
                ];
                PurchaseItem::create($vatItem);
            }

            DB::commit();
            $this->logger->create();
            return redirect()->route('accounts.viewPurchases')->with('success', 'Success! Credit Note created successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function fetchAccount(Request $request)
    {
        $data = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->where('client_account_id', $request->account)
            ->select('client_account_id', 'account_number', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol', 'sub_category_number', 'chart_number')
            ->where(['account_status' => 1])
            ->first();

        return response()->json($data);
    }
    public function getIncomeStreams(Request $request)
    {
        //        $currency = ClientAccount::where('client_account_id', $request->account)->first()->currency_id;
        $data = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            //            ->where(['client_accounts.currency_id' => $currency])
            ->whereIn('type', [1])
            ->where('client_accounts.updated_at', '>=', '2025-01-25 15:45:31')
            ->orderBy('client_account_name')
            ->where(['account_status' => 1])
            ->get();

        return response()->json($data);
    }
    public function getExpenseItems(Request $request)
    {
        $currency = ClientAccount::where('client_account_id', $request->account)->first()->currency_id;
        $data = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            //            ->where(['client_accounts.currency_id' => $currency])
            ->whereIn('type', [2, 6, 9, 11])
            ->orderBy('client_account_name')
            ->where(['account_status' => 1])
            ->get();

        return response()->json($data);
    }
    public function storeInvoice(Request $request)
    {
        $invoiceNumber = Invoice::newInvNumber();
        DB::beginTransaction();
        try {

            if($request->destination == 'other'){
                $port = Destination::firstOrCreate(
                    ['port_name' => $request->destination_name], // Match your input name
                    [
                        'destination_id' => (new CustomIds())->generateId(),
                        'country_name' => $request->destination_name,
                        'status' => 1,
                        'created_by' => auth()->user()->user_id
                    ]
                );
                $destination = $port->destination_id; // Use -> instead of ['']
            } else {
                $destination = $request->destination;
            }

            $invoiceId = (new CustomIds())->generateId();
            $invoice = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'client_id' => $request->accountId,
                'date_invoiced' => strtotime($request->invoiceDate),
                'due_date' => strtotime($request->dueDate),
                'customer_message' => $request->customerMessage,
                'container_type' => $request->container,
                'si_number' => $request->siNumber,
                'destination_id' => $destination,
                'financial_year_id' => $request->financialYear,
                'amount_due' => $request->amountDue,
                'user_id' => auth()->user()->user_id,
                'consignee' => $request->consignee
            ];
            Invoice::create($invoice);

            foreach ($request->items as $keyItem => $items) {
                foreach ($items as $invoiceItem) {
                    $invoiceItems = [
                        'invoice_item_id' => (new CustomIds())->generateId(),
                        'invoice_id' => $invoiceId,
                        'ledger_id' => $keyItem,
                        'description' => $invoiceItem['description'],
                        'quantity' => $invoiceItem['quantity'],
                        'unit_price' => $invoiceItem['rate'],
                        'tax_id' => $invoiceItem['vatable'] == 0 ? null : $request->taxBracket,
                    ];
                    InvoiceItem::create($invoiceItems);
                }
            }

            if ($request->taxBracket != null) {
                $purchaseVAT = ClientAccount::where('client_account_number', '2203001')->first();
                $vatItem = [
                    'invoice_item_id' => (new CustomIds())->generateId(),
                    'invoice_id' => $invoiceId,
                    'ledger_id' => $purchaseVAT->client_account_id,
                    'description' => 'VAT FOR INV. NUMBER ' . $invoiceNumber,
                    'quantity' => 1,
                    'unit_price' => $request->totalTax,
                    'tax_id' => null,
                ];
                InvoiceItem::create($vatItem);
            }
            DB::commit();
            $this->logger->create();
            return redirect()->route('accounts.viewInvoices')->with('success', 'Success! Client invoiced successfully');
        } catch (Exception $e) {
            //            // Rollback the transaction if an exception occurs
            DB::rollback();
            //            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again ' . $e->getMessage());
        }
    }
    public function viewAllTransactions()
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'transactions.financial_year_id')
            ->leftJoin('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.transaction_id')
            ->select('transactions.transaction_id', 'invoice_number', 'transaction_code', 'client_accounts.client_account_name', 'amount_received', 'acc.client_account_name as account', 'year_starting', 'year_ending', 'date_received', 'acc.type', 'curr.currency_symbol', 'client_id', 'account_id', 'transactions.description', 'transactions.financial_year_id', 'exchange_rate', 'si_number')
            ->selectRaw('SUM(CASE WHEN transaction_items.type = 1 THEN transaction_items.amount_settled ELSE 0 END) as amount_settled')
            ->groupBy('transactions.transaction_id', 'invoice_number', 'transaction_code', 'client_account_name', 'amount_received', 'account', 'year_starting', 'year_ending', 'date_received', 'acc.type', 'currency_symbol', 'client_id', 'account_id', 'description', 'financial_year_id', 'exchange_rate', 'si_number')
            ->orderBy('transactions.created_at', 'desc')
            ->whereNull('transactions.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('transaction_items.deleted_at')
            ->whereBetween('transactions.created_at', [$currentMonthStart, $currentMonthEnd])
            ->where(['client_accounts.account_status' => 1, 'acc.account_status' => 1])
            ->get();

        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        $accounts = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            //            ->where(['client_accounts.type' => 7])
            ->whereNull('client_accounts.deleted_at')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol')
            ->orderBy('client_account_name', 'asc')
            ->where(['client_accounts.account_status' => 1])
            ->get();

        return view('account::sales.transactions')->with(['transactions' => $transactions, 'accounts' => $accounts, 'years' => $years]);
    }
    public function deleteReceipt($id)
    {
        Transaction::where('transaction_id', $id)->delete();
        return back()->with('success', 'Receipt successfully deleted');
    }
    public function downloadPurchaseReceipt($id)
    {
        $payment = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->join('client_accounts as account', 'account.client_account_id', '=', 'transactions.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'account.chart_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'transactions.financial_year_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'transactions.user_id')
            ->select('invoice_number', 'client_accounts.client_account_name as clientName', 'currency_name', 'chart_of_accounts.chart_name', 'transaction_code', 'year_starting', 'year_ending', 'date_received', 'amount_received', 'transactions.description', 'first_name', 'surname')
            ->where('transactions.transaction_id', $id)
            ->first();

        $type = 'PAYMENT RECEIPT';
        $action = 'RECEIVED FROM';
        $amount = $this->AppClass->numberToWords($payment->amount_received);
        $fYear = Carbon::parse($payment->year_starting)->format('Y') == Carbon::parse($payment->year_ending)->format('Y') ? Carbon::parse($payment->year_starting)->format('Y') : Carbon::parse($payment->year_starting)->format('Y') . '/' . Carbon::parse($payment->year_ending)->format('y');
        $amount = $payment['currency_name'] . ' ' . $amount . ' Only';
        $invNumber = $payment->invoice_number;
        $clientName = $payment->clientName;
        $invDate = Carbon::createFromTimestamp($payment->date_received)->format('d-m-Y');
        $invMethod = $payment->chart_name;
        $transCode = $payment->transaction_code;
        $invAmount = number_format($payment->amount_received, 2);
        $description = $payment->description;
        $user = $payment->surname . ' ' . $payment->first_name;

        // Render Blade view
        $html = View::make('account::downloads.payment_voucher', compact('payment', 'type', 'action', 'clientName', 'fYear', 'invDate', 'invNumber', 'invMethod', 'transCode', 'invAmount', 'amount', 'description', 'user'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left" style="border: none !important;"> <strong></strong></td>
                    <td align="center" style="border: none !important;">Page {PAGENO} of {nbpg}</td>
                    <td align="right" style="border: none !important;"> <strong></strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $type . ' #' . $invNumber . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadPaymentReceipt($id)
    {
        $payment = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as account', 'account.client_account_id', '=', 'payments.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            // ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'account.chart_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'payments.financial_year_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'payments.user_id')
            ->select('invoice_number', 'client_accounts.client_account_name as clientName', 'currency_name', 'account.client_account_name as bankAccount', 'transaction_code', 'year_starting', 'year_ending', 'date_received', 'amount_received', 'payments.description', 'first_name', 'surname')
            ->where('payments.payment_id', $id)
            ->first();

        $type = 'PAYMENT VOUCHER';
        $action = 'PAID TO';
        $amount = $this->AppClass->numberToWords($payment->amount_received);
        $fYear = Carbon::parse($payment['year_starting'])->format('Y') == Carbon::parse($payment['year_ending'])->format('Y') ? Carbon::parse($payment['year_starting'])->format('Y') : Carbon::parse($payment['year_starting'])->format('Y') . '/' . Carbon::parse($payment['year_ending'])->format('y');
        $amount = $payment['currency_name'] . ' ' . $amount . ' Only';
        $clientName = $payment->clientName;
        $invNumber = $payment->invoice_number;
        $description = $payment->description;
        $invMethod = $payment->bankAccount;
        $transCode = $payment->transaction_code;
        $invAmount = number_format($payment->amount_received, 2);
        $user = $payment->surname . ' ' . $payment->first_name;
        $invDate = Carbon::createFromTimestamp($payment->date_received)->format('d/m/Y');
        // Render Blade view
        $html = View::make('account::downloads.payment_voucher', compact('payment', 'type', 'action', 'clientName', 'fYear', 'invDate', 'invNumber', 'invMethod', 'transCode', 'invAmount', 'amount', 'description', 'user'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left" style="border: none !important;"> <strong></strong></td>
                    <td align="center" style="border: none !important;">Page {PAGENO} of {nbpg}</td>
                    <td align="right" style="border: none !important;"> <strong></strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $type . ' ' . $invNumber . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function getPaymentMethods(Request $request)
    {
        $clientAccount = ClientAccount::find($request->clientAccount);
        $data = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('currencies.currency_symbol', 'currencies.currency_name', 'client_accounts.*')
            ->where(['type' => 4])
            ->where(['account_status' => 1])
            ->orderBy('client_account_name')
            ->get();
        return response()->json($data);
    }
    public function storePaymentInvoice(Request $request)
    {
        $request->validate([
            'clientAccount' => 'string|required',
            'amountReceived' => 'required',
            'dateReceived' => 'required',
            'description' => 'required',
            'financialYear' => 'required|string',
            'account' => 'required|string',
            'transaction' => [
                'nullable',
                'string',
                Rule::unique('transactions', 'transaction_code')
                    ->where('account_id', $request->account)
            ],
            //            'transaction' => 'nullable|string|unique:transactions,transaction_code'
        ]);
        $exchangeRate = null;
        $bank = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where('client_account_id', $request->account)->first();
        $client = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where('client_account_id', $request->clientAccount)->first();
        if ($bank->currency_id == $client->currency_id) {
            $amountReceived = $request->get('amountReceived');
        } elseif ($bank->priority == 2 && $client->priority == 1) {
            $exchangeRate =  $request->get('exchangeRate') ?? DB::table('forex_exchanges as fx2')
                ->where('fx2.currency_id', $bank->currency_id)
                ->where('fx2.date_active', '<=', $request->get('dateReceived'))
                ->orderByDesc('fx2.date_active')
                ->limit(1)
                ->value('exchange_rate');
            $amountReceived = number_format($request->get('amountReceived') * $exchangeRate, 2, '.', '');
        } elseif ($client->priority == 2 && $bank->priority == 1) {
            $exchangeRate =  $request->get('exchangeRate') ?? DB::table('forex_exchanges as fx2')
                ->where('fx2.currency_id', $client->currency_id)
                ->where('fx2.date_active', '<=', $request->get('dateReceived'))
                ->orderByDesc('fx2.date_active')
                ->limit(1)
                ->value('exchange_rate');

            $amountReceived = number_format($request->get('amountReceived') / $exchangeRate, 2, '.', '');
        }

        DB::beginTransaction();
        try {
            $inv = [
                'transaction_id' => (new CustomIds())->generateId(),
                'invoice_number' => Transaction::newPayInvNumber(),
                'client_id' => $request->get('clientAccount'),
                'date_received' => strtotime($request->get('dateReceived')),
                'amount_received' => $amountReceived,
                'exchange_rate' => $exchangeRate,
                'financial_year_id' => $request->get('financialYear'),
                'description' => $request->get('description'),
                'user_id' => auth()->user()->user_id,
                'transaction_code' => $request->transaction,
                'account_id' => $request->account,
                'si_number' => $request->si_number

            ];
            Transaction::create($inv);
            DB::commit();
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Payment Invoice Created Successfully');
        } catch (Exception $e) {
            //            // Rollback the transaction if an exception occurs
            DB::rollback();
            //            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again ' . $e->getMessage());
        }
    }
    public function updatePaymentInvoice(Request $request, $id)
    {
        $request->validate([
            'clientAccount' => 'string|required',
            'amountReceived' => 'required',
            'dateReceived' => 'required',
            'description' => 'required',
            'financialYear' => 'required|string',
            'account' => 'required|string',
            'transaction' => [
                'nullable',
                'string',
                Rule::unique('transactions', 'transaction_code')
                    ->where('account_id', $request->account)
                    ->ignore($id, 'transaction_id')
            ],
        ]);

        $bank = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where('client_account_id', $request->account)->first();
        $client = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where('client_account_id', $request->clientAccount)->first();
        $exchangeRate = null;
        if ($bank->currency_id == $client->currency_id) {
            $amountReceived = $request->get('amountReceived');
        } elseif ($client->priority == 1 && $bank->priority == 2) {
            $exchangeRate =  $request->get('exchangeRate') ?? DB::table('forex_exchanges as fx2')
                ->where('fx2.currency_id', $bank->currency_id)
                ->where('fx2.date_active', '<=', $request->get('dateReceived'))
                ->orderByDesc('fx2.date_active')
                ->limit(1)
                ->value('exchange_rate');

            $amountReceived = number_format($request->get('amountReceived') * $exchangeRate, 2, '.', '');
        } elseif ($client->priority == 2 && $bank->priority == 1) {
            $exchangeRate =  $request->get('exchangeRate') ?? DB::table('forex_exchanges as fx2')
                ->where('fx2.currency_id', $client->currency_id)
                ->where('fx2.date_active', '<=', $request->get('dateReceived'))
                ->orderByDesc('fx2.date_active')
                ->limit(1)
                ->value('exchange_rate');

            $amountReceived = number_format($request->get('amountReceived') / $exchangeRate, 2, '.', '');
        }

        $receiptAmount = Transaction::join('client_accounts as ca', 'ca.client_account_id', '=', 'transactions.client_id')
            ->leftJoin('transaction_items as ti', function ($join) { // Use a closure for the join
                $join->on('ti.transaction_id', '=', 'transactions.transaction_id')
                    ->whereNull('ti.deleted_at'); // Move the WHERE clause here
            })
            ->select(DB::raw("ROUND(IFNULL(ti.amount_settled, 0), 0) as amount_received"))
            ->where('transactions.transaction_id', $id)
            ->whereNull('transactions.deleted_at') //Keep this where condition
            ->whereNull('ti.deleted_at') //Keep this where condition
            ->first()->amount_received;

        if ($amountReceived <= $receiptAmount) {
            return redirect()->back()->with('error', 'Oops! Amount received should not be less that receipted amount');
        }

        DB::beginTransaction();
        try {
            $inv = [
                'client_id' => $request->get('clientAccount'),
                'date_received' => strtotime($request->get('dateReceived')),
                'amount_received' => $amountReceived,
                'exchange_rate' => $exchangeRate,
                'financial_year_id' => $request->get('financialYear'),
                'description' => $request->get('description'),
                'transaction_code' => $request->transaction,
                'account_id' => $request->account,
                'si_number' => $request->si_number
            ];
            Transaction::where('transaction_id', $id)->update($inv);
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Payment Invoice Created Successfully');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Oops! An error occurred please try again ' . $e->getMessage());
        }
    }
    public function salesFYTaxes()
    {
        $fyIds = Invoice::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::sales.salesFYTaxes')->with('years', $years);
    }

    public function receiptsFY()
    {
        $fyIds = Transaction::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::sales.receiptsFy')->with('years', $years);
    }
    public function viewPurchases()
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $invoices = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->whereBetween('purchases.created_at', [$currentMonthStart, $currentMonthEnd]) // Filter by current month
            ->select('purchases.purchase_id', 'currency_symbol', 'client_accounts.client_account_name as clientAccount', 'invoice_number', 'date_invoiced', 'due_date', 'purchases.financial_year_id', 'amount_due', 'year_starting', 'year_ending', 'voucher_number', 'kra_number', 'posted', 'purchases.status', 'purchases.type')
            ->orderBy('purchases.created_at', 'desc')
            ->whereNull('purchases.deleted_at')
            ->get();
        return view('account::purchases.index')->with(['invoices' => $invoices]);
    }
    public function yearlyPurchases($id)
    {
        $invoices = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->select('purchases.purchase_id', 'currency_symbol', 'client_accounts.client_account_name as clientAccount', 'invoice_number', 'date_invoiced', 'due_date', 'purchases.financial_year_id', 'amount_due', 'year_starting', 'year_ending', 'voucher_number', 'kra_number', 'posted', 'purchases.status', 'purchases.type')
            ->orderBy('purchases.created_at', 'desc')
            ->where('purchases.financial_year_id', $id)
            ->whereNull('purchases.deleted_at')
            ->get();
        return view('account::purchases.index')->with(['invoices' => $invoices]);
    }
    public function addPurchaseInvoice()
    {
        $accounts = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol')
            ->orderBy('client_account_name', 'asc')
            ->whereIn('type', [8])
            ->get();

        $financialYears = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });

        $taxes = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')->where(['tax_brackets.status' => 1])->orderBy('tax_name', 'asc')->get();
        $debtors =  $accounts->where('account_type', 1);
        $items = $accounts->where('account_type', 2);
        return view('account::purchases.addVoucher')->with(['debtors' => collect($debtors), 'items' => collect($items), 'financialYears' => $financialYears, 'taxes' => $taxes]);
    }
    public function fetchPurchaseInvNumber(Request $request)
    {
        $data = Purchase::where(['invoice_number' => $request->invoiceNumber, 'client_id' => $request->clientId])->exists();
        return response()->json(['exists' => $data]);
    }
    public function storePurchaseInvoice(Request $request)
    {
        $voucherNumber = Purchase::newPINumber();
        //        return $request->all();
        DB::beginTransaction();
        try {
            $invoiceId = (new CustomIds())->generateId();
            $invoice = [
                'purchase_id' => $invoiceId,
                'voucher_number' => $voucherNumber,
                'invoice_number' => $request->invoiceNumber,
                'client_id' => $request->accountId,
                'tax_id' => $request->taxBracket,
                'date_invoiced' => strtotime($request->invoiceDate),
                'due_date' => strtotime($request->dueDate),
                'customer_message' => $request->customerMessage,
                'financial_year_id' => $request->financialYear,
                'amount_due' => $request->amountDue,
                'user_id' => auth()->user()->user_id,
                'type' => 1
            ];

            Purchase::create($invoice);

            foreach ($request->items as $invoice) {
                //                return $invoice;
                $invoiceItems = [
                    'purchase_item_id' => (new CustomIds())->generateId(),
                    'purchase_id' => $invoiceId,
                    'ledger_id' => $invoice['client_id'],
                    'description' => $invoice['description'],
                    'quantity' => $invoice['quantity'],
                    'unit_price' => $invoice['rate'],
                    'tax_id' => $invoice['vatable'] == 0 ? null : $request->taxBracket,
                ];

                PurchaseItem::create($invoiceItems);
            }
            if ($request->taxBracket != null) {
                $purchaseVAT = ClientAccount::where('client_account_number', '2203004')->first();
                $vatItem = [
                    'purchase_item_id' => (new CustomIds())->generateId(),
                    'purchase_id' => $invoiceId,
                    'ledger_id' => $purchaseVAT->client_account_id,
                    'description' => 'VAT FOR INV. NUMBER ' . $voucherNumber,
                    'quantity' => 1,
                    'unit_price' => $request->totalTax,
                    'tax_id' => null,
                ];
                PurchaseItem::create($vatItem);
            }
            // Commit the transaction
            DB::commit();

            $this->logger->create();
            return redirect()->route('accounts.viewPurchases')->with('success', 'Success! Purchase invoiced successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function downloadPurchaseVoucher($id)
    {
        $purchases = Purchase::join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'purchase_items.tax_id')
            ->leftJoin('tax_brackets as tb', 'tb.tax_bracket_id', '=', 'purchases.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_account_name as account_name', 'customer_message', 'tb.tax_rate as taxRate', 'tax_name', 'tax_brackets.tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'purchase_items.description', 'date_invoiced', 'due_date', 'year_starting', 'year_ending', 'voucher_number', 'invoice_number', 'user_id')
            ->where('purchases.purchase_id', $id)
            ->whereNot('client_accounts.type', 3)
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();

        $account = ClientAccount::join('purchases', 'purchases.client_id', '=', 'client_accounts.client_account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->where('purchase_id', $id)->first();


        $values = $purchases->first();
        $user = UserInfo::where('user_id', $values->user_id)->first();
        $preparedBy = $user->surname . ' ' . $user->first_name;
        $type = 'PURCHASE';
        $narration = 'INVOICE TO BE SETTLED BY OR BEFORE ' . Carbon::createFromTimestamp($values->due_date)->format('D, d-m-Y');

        $fYear = Carbon::parse($values->year_starting)->format('Y') == Carbon::parse($values->year_ending)->format('Y') ? Carbon::parse($values->year_starting)->format('Y') : Carbon::parse($values->year_starting)->format('Y') . '/' . Carbon::parse($values->year_ending)->format('y');

        // Render Blade view
        $html = View::make('account::downloads.purchase_voucher', compact('type', 'narration', 'fYear', 'values', 'account', 'purchases'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left" style="border: none !important;"> Prepared By :<strong>' . $preparedBy . '</strong></td>
                    <td align="center" style="border: none !important;">Page {PAGENO} of {nbpg}</td>
                    <td align="right" style="border: none !important;"> Printed By: <strong>' . auth()->user()->user->surname . ' ' . auth()->user()->user->first_name . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'PURCHASE VOUCHER ' . $values->voucher_number . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function viewPurchaseInvoice($id)
    {
        $invoices = Purchase::join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'purchase_items.tax_id')
            ->leftJoin('tax_brackets as tb', 'tb.tax_bracket_id', '=', 'purchases.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_account_name as account_name', 'tb.tax_rate as taxRate', 'tax_name', 'tax_brackets.tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'purchase_items.description', 'purchases.status')
            ->where('purchases.purchase_id', $id)
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();

        $account = ClientAccount::join('purchases', 'purchases.client_id', '=', 'client_accounts.client_account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('purchases.status as status', 'date_invoiced', 'due_date', 'client_account_name', 'voucher_number', 'currency_symbol', 'purchase_id')
            ->where('purchase_id', $id)->first();

        return view('account::purchases.viewVoucher')->with(['invoices' => $invoices, 'account' => $account]);
    }
    public function deletePurchaseInvoice($id)
    {
        Purchase::find($id)->delete();
        PurchaseItem::where('purchase_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Invoice successfully deleted');
    }
    public function purchaseFYTaxes()
    {
        $fyIds = Invoice::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::purchases.purchaseTaxes')->with('years', $years);
    }

    public function paymentsFY()
    {
        $fyIds = Payment::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::purchases.paymentsFY')->with('years', $years);
    }
    public function viewAccounts()
    {
        $accounts = Account::latest()->get();
        return view('account::accounts.viewAccounts')->with('accounts', $accounts);
    }
    public function registerAccount(Request $request)
    {
        $request->validate([
            'account_name' => 'string|required|unique:accounts,account_name',
            'account_type' => 'numeric|required'
        ]);

        $accNumber = Account::withTrashed()->count();
        $newAccount = ($accNumber + 1) * 1000;
        $account = [
            'account_id' => (new CustomIds())->generateId(),
            'account_number' => $newAccount,
            'account_name' => $request->account_name,
            'account_type' =>  $request->account_type,
            'description' =>  $request->description,
        ];

        Account::create($account);
        $this->logger->create();

        return back()->with('success', 'Successful! Account created successfully');
    }
    public function updateAccount(Request $request, $id)
    {
        $request->validate([
            'account_name' => 'string|required|unique:accounts,account_name,' . $id . ',account_id',
            'account_type' => 'numeric|required',
            'account_status' => 'numeric|required'
        ]);

        $account = [
            'account_name' => $request->account_name,
            'account_type' =>  $request->account_type,
            'status' =>  $request->account_status,
            'description' =>  $request->description,
        ];

        Account::where('account_id', $id)->update($account);
        $this->logger->create();
        return back()->with('success', 'Successful! Account updated successfully');
    }
    public function deleteAccount($id)
    {
        Account::where('account_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Successful! Account deleted successfully');
    }
    public function accountSubCategories()
    {
        $categories = Account::withoutTrashed()->orderBy('account_number', 'asc')->get();
        $accounts = AccountSubCategories::join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->withoutTrashed()
            ->select('accounts.account_id', 'accounts.account_name', 'account_sub_categories.sub_account_id', 'account_sub_categories.sub_category_number', 'account_sub_categories.sub_account_name', 'account_sub_categories.description', 'account_sub_categories.status')
            ->orderBy('sub_category_number', 'asc')->get();
        $currencies = Currency::withoutTrashed()->latest()->get();
        return view('account::accounts.accountSubCategories')->with(['accounts' => $accounts, 'categories' => $categories, 'currencies' => $currencies]);
    }
    public function addAccountSubCategory(Request $request)
    {
        $request->validate([
            'account' => 'required|string',
            'account_name' => 'required|string|unique:account_sub_categories,sub_account_name'
        ]);

        $category = Account::where('account_id', $request->account)->first();
        $subCat = AccountSubCategories::withTrashed()->where('account_id', $request->account)->get();
        $subAccNo = $category->account_number + ($subCat->count() + 1) * 100;
        $account = [
            'sub_account_id' => (new CustomIds())->generateId(),
            'sub_category_number' => $subAccNo,
            'account_id' => $request->account,
            'sub_account_name' => $request->account_name,
            'description' => $request->description
        ];
        AccountSubCategories::create($account);
        $this->logger->create();
        return back()->with('success', 'Successful!, Account Subcategory created successfully');
    }
    public function updateAccountSubCategory(Request $request, $id)
    {
        $request->validate([
            'account_category' => 'required|string',
            'account_name' => 'required|string|unique:account_sub_categories,sub_account_name,' . $id . ',sub_account_id',
            'status' => 'required'
        ]);

        $account = [
            'account_id' => $request->account_category,
            'sub_account_name' => $request->account_name,
            'description' => $request->description,
            'status' => $request->status
        ];
        AccountSubCategories::where('sub_account_id', $id)->update($account);
        $this->logger->create();
        return back()->with('success', 'Successful!, Account Subcategory updated successfully');
    }
    public function deleteAccountSubCategory($id)
    {
        AccountSubCategories::where('sub_account_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Account Subcategory delete successfully');
    }
    public function viewChartAccounts()
    {
        $accounts = ChartOfAccount::join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select('chart_id', 'chart_number', 'chart_name', 'account_name', 'chart_of_accounts.description', 'chart_of_accounts.status', 'account_sub_categories.sub_account_name', 'account_sub_categories.sub_account_id')
            ->orderBy('chart_number', 'asc')
            ->get();
        $categories = AccountSubCategories::latest()->get();
        $currencies = Currency::latest()->get();
        return view('account::accounts.allAccounts')->with(['accounts' => $accounts, 'categories' => $categories, 'currencies' => $currencies]);
    }
    public function addChartAccount(Request $request)
    {
        $request->validate([
            'account_name' => 'required|string',
            'account_category' => 'required|string',
        ]);

        $exists = ChartOfAccount::where(['chart_name' => $request->account_name, 'sub_account_id' => $request->account_category])->exists();
        if ($exists) {
            return back()->with('info', 'Oops! Account already exists');
        } else {

            $existing = ChartOfAccount::withTrashed()->where('sub_account_id', $request->account_category)->count();
            $accNumber = AccountSubCategories::where('sub_account_id', $request->account_category)->first();

            $chart = [
                'chart_id' => (new CustomIds())->generateId(),
                'chart_name' => $request->account_name,
                'chart_number' => $accNumber->sub_category_number + $existing + 1,
                'sub_account_id' => $request->account_category,
                'description' => $request->description
            ];
            ChartOfAccount::create($chart);
        }
        $this->logger->create();
        return back()->with('success', 'Success! Chart of account created successfully');
    }
    public function updateChartAccount(Request $request, $id)
    {
        $request->validate([
            'account_name' => 'required|string',
            'account_category' => 'required|string',
            'status' => 'required|string',
        ]);

        $exists = ChartOfAccount::where(['chart_name' => $request->account_name, 'sub_account_id' => $request->account_category])->where('chart_id', '!=', $id)->exists();

        if ($exists) {
            return back()->with('info', 'Oops! Account already exists');
        } else {
            $chart = [
                'chart_name' => $request->account_name,
                'sub_account_id' => $request->account_category,
                'description' => $request->description,
                'status' => $request->status,
            ];

            ChartOfAccount::where('chart_id', $id)->update($chart);
        }
        $this->logger->create();
        return back()->with('success', 'Success! Chart of account created successfully');
    }
    public function deleteChartAccount($id)
    {
        ChartOfAccount::where('chart_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Chart of account delete successfully');
    }
    public function viewClientAccounts()
    {
        $clientsAccounts =  DB::table('ledgers')
            ->orderBy('account_status')
            ->orderBy('account_number', 'desc')
            ->orderBy('sub_category_number', 'desc')
            ->orderBy('chart_number', 'desc')
            ->orderBy('client_account_number', 'asc')
            ->whereNull('deleted_at')
            ->get();

        $accounts = Account::all();
        $categories = ChartOfAccount::orderBy('chart_name')->get();
        $currencies = Currency::all();
        $client = Client::pluck('client_name');
        $transporter = Transporter::pluck('transporter_name');
        $clients = $client->merge($transporter);
        return view('account::accounts.viewClientAccounts')->with(['clients' => $clients, 'categories' => $categories, 'currencies' => $currencies, 'clientsAccounts' => $clientsAccounts, 'accounts' => $accounts]);
    }
    public function filterAccountsPerType(Request $request)
    {
        $data = AccountSubCategories::where('account_id', $request->accountId)->get();
        return response()->json($data);
    }
    public function filterChartOfAccounts(Request $request)
    {
        $data = ChartOfAccount::where('sub_account_id', $request->subAccountId)->get();
        return response()->json($data);
    }
    public function addClientAccount(Request $request)
    {
        $request->validate([
            'account_name' => 'required|string',
            'account_category' => 'required|string',
            'account_currency' => 'required|string'
        ]);

        $exists = ClientAccount::where(['client_account_name' => $request->account_name, 'chart_id' => $request->account_category, 'currency_id' => $request->currency_id])->exists();

        if ($exists) {
            return back()->with('info', 'Oops! Account already exists');
        } else {
            /* $coa = ChartOfAccount::where('chart_id', $request->account_category)->first();
            $ca = ClientAccount::withTrashed()->where(['chart_id' => $request->account_category])->get()->count();
            $accountNumber = $coa->chart_number. str_pad($ca + 1, 3, '0', STR_PAD_LEFT);*/

            $coa = ChartOfAccount::where('chart_id', $request->account_category)->first();

            // Fetch all existing account numbers for the given category
            $existingAccounts = ClientAccount::withTrashed()
                ->where('chart_id', $request->account_category)
                ->pluck('client_account_number') // Assuming `account_number` stores the generated number
                ->toArray();

            // Generate the full range of possible account numbers
            $prefix = $coa->chart_number;
            $maxAccounts = count($existingAccounts) + 1; // Anticipating at least one new account
            $allPossibleAccounts = array_map(function ($index) use ($prefix) {
                return $prefix . str_pad($index, 3, '0', STR_PAD_LEFT);
            }, range(1, $maxAccounts));

            // Find the first missing account number
            $accountNumber = collect($allPossibleAccounts)
                ->diff($existingAccounts)
                ->first();

            $account = [
                'client_account_id' => (new CustomIds())->generateId(),
                'client_account_number' => $accountNumber,
                'client_account_name' => $request->account_name,
                'currency_id' => $request->account_currency,
                'chart_id' => $request->account_category,
                'description' => $request->description,
                'opening_date' => time(),
                'type' => $request->type,
                'kra_pin' => $request->kraPin,
                'client_address' => $request->client_address,
                'account_status' => auth()->user()->role_id == 7 ? 1 : 0
            ];

            ClientAccount::create($account);
            $this->logger->create();
        }

        return back()->with('success', 'Success! Client account created successfully');
    }
    public function updateClientAccount(Request $request, $id)
    {
        $request->validate([
            'account_name' => 'required|string',
            'account_currency' => 'required|string',
            'type' => 'required|string',
            'account_category' => 'required|string'
        ]);

        $unique = ClientAccount::where(['client_account_name' => $request->account_name])->where('client_account_id', '!==', $id)->first();

        $clientClient = ClientAccount::where(['client_account_id' => $id])->first();

        if ($unique) {
            return back()->with('info', 'Another account exists with the same client name exists');
        }

        if ($clientClient->chart_id == $request->account_category) {
            $accounNo = $clientClient->client_account_number;
        } else {
            $coa = ChartOfAccount::where('chart_id', $request->account_category)->first();

            // Fetch all existing account numbers for the given category
            $existingAccounts = ClientAccount::withTrashed()
                ->where('chart_id', $request->account_category)
                ->pluck('client_account_number') // Assuming `account_number` stores the generated number
                ->toArray();

            // Generate the full range of possible account numbers
            $prefix = $coa->chart_number;
            $maxAccounts = count($existingAccounts) + 1; // Anticipating at least one new account
            $allPossibleAccounts = array_map(function ($index) use ($prefix) {
                return $prefix . str_pad($index, 3, '0', STR_PAD_LEFT);
            }, range(1, $maxAccounts));

            // Find the first missing account number
            $accounNo = collect($allPossibleAccounts)
                ->diff($existingAccounts)
                ->first();
        }

        $account = [
            'client_account_name' => $request->account_name,
            'client_account_number' => $accounNo,
            'chart_id' => $request->account_category,
            'closing_date' => $request->account_status == 1 ? null : time(),
            'currency_id' => $request->account_currency,
            'description' => $request->description,
            'type' => $request->type === null ? null : $request->type,
            'kra_pin' => $request->kraPin,
            'client_address' => $request->client_address
        ];

        ClientAccount::where('client_account_id', $id)->update($account);

        $this->logger->create();
        return back()->with('success', 'Success! Client account updated successfully');
    }
    public function activateClientAccount($id)
    {
        ClientAccount::where('client_account_id', $id)->update(['account_status' => 1]);
        return back()->with('success', 'Success! Client account activated successfully');
    }
    public function deleteClientAccount($id)
    {
        ClientAccount::where('client_account_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Client account created successfully');
    }
    public function exchangeRates()
    {
        $forexes = ForexExchange::join('currencies as exchange', 'exchange.currency_id', '=', 'forex_exchanges.exchange_id')
            ->join('currencies', 'currencies.currency_id', '=', 'forex_exchanges.currency_id')
            ->select('forex_id', 'exchange_rate', 'exchange.currency_name as exchange_currency_name', 'currencies.currency_name as currency', 'exchange.currency_symbol as exchange_currency_symbol', 'date_active', 'forex_exchanges.currency_id')
            ->orderBy('date_active', 'desc')
            ->get();
        $currencies = Currency::where('priority', '!=', 1)->where('status', 1)->orderBy('priority', 'asc')->get();
        return view('account::currencies.exchangeRates')->with(['currencies' => $currencies, 'forexes' => $forexes]);
    }
    public function addCurrencyExchangeRate(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|string',
            'exchange_rate' => 'required|string',
            'date_active' => 'required|date',
        ]);
        $primary = Currency::where('priority', 1)->first()->currency_id;
        $exchange = [
            'forex_id' => (new CustomIds())->generateId(),
            'currency_id' => $request->currency_id,
            'exchange_id' => $primary,
            'exchange_rate' => $request->exchange_rate,
            'date_active' => $request->date_active
        ];
        ForexExchange::create($exchange);
        $this->logger->create();
        return back()->with('success', 'Success! Exchange rate added successfully');
    }
    public function updateCurrencyExchangeRate(Request $request, $id)
    {
        $request->validate([
            'currency_id' => 'required|string',
            'exchange_rate' => 'required|string',
            'date_active' => 'required|date',
        ]);

        $primary = Currency::where('priority', 1)->first()->currency_id;
        $exchange = [
            'currency_id' => $request->currency_id,
            'exchange_id' => $primary,
            'exchange_rate' => $request->exchange_rate,
            'date_active' => $request->date_active
        ];
        ForexExchange::find($id)->update($exchange);
        $this->logger->create();
        return back()->with('success', 'Success! Exchange rate updated successfully');
    }
    public function deleteCurrencyExchangeRate($id)
    {
        ForexExchange::find($id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Exchange rate deleted successfully');
    }
    public function viewCurrencies()
    {
        $currencies = Currency::orderBy('priority', 'asc')->get();
        return view('account::currencies.viewCurrency')->with('currencies', $currencies);
    }
    public function addCurrency(Request $request)
    {
        $request->validate([
            'currency_name' => 'required|string|unique:currencies,currency_name',
            'currency_symbol' => 'required|string|unique:currencies,currency_symbol',
            'priority' => 'numeric|required'
        ]);

        if ($request->priority == 1) {
            Currency::where(['priority' => 1])->update(['priority' => 2]);;
        }

        $currency = [
            'currency_id' => (new CustomIds())->generateId(),
            'currency_name' => $request->currency_name,
            'currency_symbol' => $request->currency_symbol,
            'priority' => $request->priority
        ];

        Currency::create($currency);
        $this->logger->create();
        return back()->with('success', 'Success! Currency created successfully');
    }
    public function updateCurrency(Request $request, $id)
    {
        $request->validate([
            'currency_name' => 'required|string|unique:currencies,currency_name,' . $id . ',currency_id',
            'currency_symbol' => 'required|string|unique:currencies,currency_symbol,' . $id . ',currency_id',
            'status' => 'numeric|required',
            'priority' => 'numeric|required'
        ]);

        $currency = [
            'status' => $request->status,
            'currency_name' => $request->currency_name,
            'currency_symbol' => $request->currency_symbol,
            'priority' => $request->priority
        ];

        if ($request->priority == 1) {
            //            return Currency::whereIn(['priority' => 1])->first();
            Currency::where(['priority' => 1])->update(['priority' => 2]);
        }

        Currency::where('currency_id', $id)->update($currency);
        $this->logger->create();
        return back()->with('success', 'Success! Currency updated successfully');
    }
    public function deleteCurrency($id)
    {
        Currency::where('currency_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Currency deleted successfully');
    }
    public function viewFinancialYears()
    {
        $years = FinancialYear::latest()->get();
        return view('account::years.financialYears')->with('years', $years);
    }
    public function addFinancialYears(Request $request)
    {
        $request->validate([
            'year_starting' => [
                'required',
                'date',
                'before:year_ending'
            ],
            'year_ending' => [
                'required',
                'date',
                'after:year_starting',
            ],
        ]);

        $fy = [
            'financial_year_id' => (new CustomIds())->generateId(),
            'year_starting' => $request->year_starting,
            'year_ending' => $request->year_ending,
        ];

        FinancialYear::create($fy);
        $this->logger->create();
        return back()->with('success', 'Success! Financial year created successfully');
    }
    public function updateFinancialYears(Request $request, $id)
    {
        $request->validate([
            'year_starting' => ['required', 'date', 'before:year_ending'],
            'year_ending' => 'required|date|after:year_starting'
        ]);

        $fy = [
            'status' => $request->status,
            'year_starting' => $request->year_starting,
            'year_ending' => $request->year_ending,
        ];

        FinancialYear::where('financial_year_id', $id)->update($fy);
        $this->logger->create();
        return back()->with('success', 'Success! Financial year updated successfully');
    }
    public function deleteFinancialYears($id)
    {
        FinancialYear::where('financial_year_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Financial year deleted successfully');
    }
    public function viewTaxes()
    {
        return view('account::taxes.taxes')->with(['taxes' => Tax::all()]);
    }
    public function viewTaxBrackets()
    {
        $taxes = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('tax_name', 'tax_rate', 'tax_bracket_id', 'tax_brackets.status', 'taxes.tax_id')
            ->latest('tax_brackets.created_at')
            ->get();
        return view('account::taxes.taxBrackets')->with(['taxes' => Tax::all(), 'brackets' => $taxes]);
    }
    public function storeTax(Request $request)
    {
        $request->validate(['tax' => 'required|string|unique:taxes,tax_name']);
        $tax = [
            'tax_id' => (new CustomIds())->generateId(),
            'tax_name' => $request->tax,
            'status' => $request->status,
            'effect' => $request->effect
        ];
        Tax::create($tax);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tax Created Successfully');
    }
    public function updateTax(Request $request, $id)
    {
        $request->validate(['tax' => 'required|string|unique:taxes,tax_name,' . $id . ',tax_id']);
        $tax = [
            'tax_name' => $request->tax,
            'status' => $request->status,
            'effect' => $request->effect
        ];
        Tax::find($id)->update($tax);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tax Updated Successfully');
    }
    public function deleteTax($id)
    {
        Tax::find($id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tax deleted successfully');
    }
    public function storeTaxBracket(Request $request)
    {
        $request->validate(['tax' => 'required|string|unique:tax_brackets,tax_rate']);
        $tax = [
            'tax_bracket_id' => (new CustomIds())->generateId(),
            'tax_id' => $request->tax_id,
            'tax_rate' => $request->tax,
            'status' => $request->status
        ];

        $taxExists =  TaxBrackets::where('tax_id', $request->tax_id)->first();
        if ($taxExists) {
            $taxExists->update(['status' => 2]);
        }
        TaxBrackets::create($tax);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tax Bracket Created Successfully');
    }
    public function updateTaxBracket(Request $request, $id)
    {
        $request->validate(['tax' => 'required|string|unique:tax_brackets,tax_rate,' . $id . ',tax_bracket_id']);
        $tax = [
            'tax_id' => $request->tax_id,
            'tax_rate' => $request->tax,
            'status' => $request->status
        ];
        if ($request->tax_id != $id) {
            TaxBrackets::where('tax_id', $request->tax_id)->update(['status' => 2]);
        }

        TaxBrackets::find($id)->update($tax);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tax Bracket Updated Successfully');
    }
    public function deleteTaxBracket($id)
    {
        TaxBrackets::find($id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tax Bracket deleted successfully');
    }
    public function getSalesFinancialYears()
    {
        $fyIds = Invoice::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::reports.sales.financialYears')->with('years', $years);
    }
    public function getClientsSalesWithInvoices($id)
    {
        $data = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->select('financial_years.financial_year_id', 'invoices.invoice_id', 'currency_symbol', 'client_accounts.client_account_name as clientAccount', 'invoice_number', 'date_invoiced', 'due_date', 'client_account_number', 'currency_symbol', 'currencies.currency_id', 'client_account_id', 'amount_due')
            ->where('financial_years.financial_year_id', $id)
            ->orderBy('client_account_number')
            ->whereNull('client_accounts.deleted_at');

        $invoices = $data->get()->groupBy(['client_account_number', 'currency_symbol']);
        $currencies = Currency::whereNull('deleted_at')->get();
        $clients = $data->get()->groupBy(['client_account_id']);

        return view('account::reports.sales.invoicesPerClient')->with(['invoices' => $invoices, 'id' => $id, 'clients' => $clients, 'currencies' => $currencies]);
    }
    public function viewClientStatement($id)
    {
        $opBal = [];
        list($client, $year) = explode(':', base64_decode($id));
        $statements = Db::table('accountstatements')->where(['client_id' => $client, 'financial_year_id' => $year])->orderBy('date_invoiced', 'asc')->get();
        $fy = FinancialYear::find($year);
        $client = ClientAccount::find($client);
        $currency = Currency::find($client->currency_id);
        $opBal = ClientAccount::where(['type' => 5, 'currency_id' => $client->currency_id])->first();
        $payments = ClientAccount::where(['type' => 4, 'currency_id' => $client->currency_id])->orderBy('client_account_name', 'asc')->get();

        return view('account::reports.sales.clientStatement')->with(['statements' => $statements, 'fy' => $fy, 'client' => $client, 'currency' => $currency, 'opBal' => $opBal, 'payments' => $payments]);
    }
    public function getPurchasesFinancialYears()
    {
        $fyIds = Invoice::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });
        return view('account::reports.purchase.financialYears')->with('years', $years);
    }
    public function getClientsPurchasesWithInvoices($id)
    {
        $invoices = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->select('financial_years.financial_year_id', 'purchases.purchase_id', 'currency_symbol', 'client_accounts.client_account_name as clientAccount', 'voucher_number', 'invoice_number', 'date_invoiced', 'due_date', 'client_account_number', 'currency_symbol', 'currencies.currency_id', 'client_account_id', 'amount_due')
            ->where('financial_years.financial_year_id', $id)
            ->get()
            ->groupBy(['client_account_number', 'currency_symbol']);

        return view('account::reports.purchase.invoicesPerClient')->with(['invoices' => $invoices]);
    }
    public function viewSupplierStatement($id)
    {
        list($client, $year) = explode(':', base64_decode($id));
        $statements = Db::table('purchasestatement')->where(['client_id' => $client, 'financial_year_id' => $year])->orderBy('date_invoiced', 'asc')->get();
        $fy = FinancialYear::find($year);
        $client = ClientAccount::find($client);
        $currency = Currency::find($client->currency_id);

        return view('account::reports.purchase.clientStatement')->with(['statements' => $statements, 'fy' => $fy, 'client' => $client, 'currency' => $currency]);
    }
    public function getAccountStatementFinancialYears()
    {
        $fyIds = Invoice::pluck('financial_year_id')->toArray();
        $years = FinancialYear::whereIn('financial_year_id', $fyIds)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::reports.other.financialYears')->with('years', $years);
    }
    public function getAccountsWithInvoices($id)
    {
        $financial = FinancialYear::find($id);
        $accounts = $this->AppClass->fetchTrialBalance($financial, $id);

        // After grouping and processing the data, reorder it according to standard accounting practice
        $orderedAccounts = collect([
            'ASSETS' => $accounts->firstWhere('account_name', 'ASSETS'),
            'LIABILITIES' => $accounts->firstWhere('account_name', 'LIABILITIES'),
            'EQUITY' => $accounts->firstWhere('account_name', 'EQUITY'),
            'REVENUE' => $accounts->firstWhere('account_name', 'REVENUE'),
            'EXPENSES' => $accounts->firstWhere('account_name', 'EXPENSES'),
        ])->filter(); // Remove any null values if an account type doesn't exist

        // Within each account type, order charts appropriately
        foreach ($orderedAccounts as &$account) {
            $charts = collect($account['charts']);

            if ($account['account_name'] === 'ASSETS') {
                $account['charts'] = $charts->sortByDesc(function ($chart) {
                    // Put current assets first, then fixed assets
                    return in_array(strtoupper($chart['chart_name']), ['CASH', 'BANK', 'DEBTORS']) ? 0 : 1;
                })->values()->all();
            }

            if ($account['account_name'] === 'LIABILITIES') {
                $account['charts'] = $charts->sortByDesc(function ($chart) {
                    // Put current liabilities first, then long-term
                    return in_array(strtoupper($chart['chart_name']), ['CREDITORS', 'TAXES PAYABLE']) ? 0 : 1;
                })->values()->all();
            }

            // Similar ordering can be applied for other account types as needed
        }

        return view('account::reports.other.trialBalance')->with(['orderedAccounts' => $orderedAccounts, 'fy' => $id, 'financial' => $financial]);
    }
    public function downloadTrialBalance($id)
    {
        $financial = FinancialYear::find($id);
        $accounts = $this->AppClass->fetchTrialBalance($financial, $id);

        // After grouping and processing the data, reorder it according to standard accounting practice
        $orderedAccounts = collect([
            'ASSETS' => $accounts->firstWhere('account_name', 'ASSETS'),
            'LIABILITIES' => $accounts->firstWhere('account_name', 'LIABILITIES'),
            'EQUITY' => $accounts->firstWhere('account_name', 'EQUITY'),
            'REVENUE' => $accounts->firstWhere('account_name', 'REVENUE'),
            'EXPENSES' => $accounts->firstWhere('account_name', 'EXPENSES'),
        ])->filter(); // Remove any null values if an account type doesn't exist

        // Within each account type, order charts appropriately
        foreach ($orderedAccounts as &$account) {
            $charts = collect($account['charts']);

            if ($account['account_name'] === 'ASSETS') {
                $account['charts'] = $charts->sortByDesc(function ($chart) {
                    // Put current assets first, then fixed assets
                    return in_array(strtoupper($chart['chart_name']), ['CASH', 'BANK', 'DEBTORS']) ? 0 : 1;
                })->values()->all();
            }

            if ($account['account_name'] === 'LIABILITIES') {
                $account['charts'] = $charts->sortByDesc(function ($chart) {
                    // Put current liabilities first, then long-term
                    return in_array(strtoupper($chart['chart_name']), ['CREDITORS', 'TAXES PAYABLE']) ? 0 : 1;
                })->values()->all();
            }

            // Similar ordering can be applied for other account types as needed
        }
        return Excel::download(new TrialBalanceExport($orderedAccounts, $financial), 'trial_balance - ' . time() . '.xlsx');
    }
    public function generateVatTaxReport(Request $request)
    {
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $rating = $request->rating;
        $query = DB::table('taxstatement')
            ->join('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'taxstatement.client_id')
            ->join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->orderBy('date_invoiced', 'asc');

        if (!is_null($dateFrom)) {
            $query->where('date_invoiced', '>=', $dateFrom);
        }

        if (!is_null($dateTo)) {
            $query->where('date_invoiced', '<=', $dateTo);
        }

        if (!is_null($rating) && $rating == 2) {
            $query->where(function ($query) {
                $query->where('debit', '>', 0)
                    ->orWhere('credit', '>', 0);
            });
        }

        $statements = $query->get()
            ->map(function ($item) {
                // Sanitize all fields in each row
                return collect($item)->map(function ($value) {
                    return is_string($value) ? htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') : $value;
                });
            });
        return Excel::download(new ExportVATTaxReport($statements), 'VAT REPORT' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function generateClientStatement(Request $request)
    {
        list($client, $year) = explode(':', base64_decode($request->clientId));
        $fyear = FinancialYear::find($year);
        $clientAccount = ClientAccount::find($client);
        $opBal = $request->openingBalance ? true : false;
        // Get the account type (client, supplier, income, expense, bank)
        $account = DB::table('client_accounts')->where('client_account_id', $client)->first();
        if (!$account) {
            return []; // Account not found
        }

        $fy = FinancialYear::where('financial_year_id', $year)->orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');
            return ['fYear' => $formattedYear];
        });

        $allTransactions = $this->AppClass->getLedgerStatement($client, $year, $fyear, $opBal);
        if ($opBal) {
            if ($request->dateFrom !== null && $request->dateTo !== null || $request->dateFrom == null && $request->dateTo !== null || $request->dateFrom !== null && $request->dateTo == null) {
                $startDate = $request->dateFrom == null ? Carbon::parse($fyear->year_starting)->startOfDay()->timestamp : Carbon::parse($request->dateFrom)->startOfDay()->timestamp;
                $endDate = $request->dateTo == null ? Carbon::parse($fyear->year_ending)->endOfDay()->timestamp : Carbon::parse($request->dateTo)->endOfDay()->timestamp;

                // Step 2: Filter for transactions prior to $startDate for opening balance calculation
                $openingBalance = $allTransactions
                    ->filter(fn($tx) => $tx->transaction_date < $startDate)
                    ->reduce(fn($carry, $tx) => $carry + ($tx->debit - $tx->credit), 0);

                // Step 3: Fetch current transactions within the financial year
                $transactions = $allTransactions->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate);

                if ($openingBalance != 0) {
                    $opening = [
                        'transaction_type' => 'Op Bal', // May be null if no dateFrom was provided
                        'client_account_name' => '--',
                        'transaction_date' => null,
                        'transaction_number' => '--',
                        'ledger_name' => '--',
                        'debit' => $openingBalance >= 0 ? number_format($openingBalance, 2, '.', '') : '0.00',
                        'credit' => $openingBalance < 0 ? number_format($openingBalance * -1, 2, '.', '') : '0.00',
                        'description' => 'Balance as of ' . Carbon::createFromTimestamp($startDate)->format('d-m-Y'),
                        'type' => $account->type
                    ];

                    $statementsArray = $transactions->toArray(); // Convert collection to array

                    $statements = array_merge([$opening], $statementsArray);

                    $statements = array_map(function ($item) {
                        return (array)$item; // Convert stdClass to array
                    }, $statements);
                } else {
                    $statements = $transactions;
                }
            } elseif ($request->dateFrom == null && $request->dateTo == null) {
                $statements = $allTransactions;
            }
        } else {
            if ($request->dateFrom !== null && $request->dateTo !== null || $request->dateFrom == null && $request->dateTo !== null || $request->dateFrom !== null && $request->dateTo == null) {
                $startDate = $request->dateFrom == null ? Carbon::parse($fyear->year_starting)->startOfDay()->timestamp : Carbon::parse($request->dateFrom)->startOfDay()->timestamp;
                $endDate = $request->dateTo == null ? Carbon::parse($fyear->year_ending)->endOfDay()->timestamp : Carbon::parse($request->dateTo)->endOfDay()->timestamp;
                $statements = $allTransactions->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate);
            } else {
                $statements = $allTransactions;
            }
        }

        $statements = collect($statements)->map(function ($statement) {
            return (object) $statement;
        });

        if ($statements) {
            $client = ClientAccount::find($client);
            $currency = Currency::find($client->currency_id);

            if ($request->reportType == 2) {
                return Excel::download(new ExportLedgerSummary($statements, $account), $client->client_account_name . ' ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
            }

            // Render Blade view
            $html = View::make('account::downloads.ledger_statement', compact('statements', 'account', 'currency', 'fy'))->render();

            // Initialize mPDF with settings
            $mpdf = new Mpdf([
                'tempDir' => storage_path('app/mpdf_temp'),
                'mode'        => 'utf-8',
                'format'      => 'A4-L', // Landscape
                'orientation' => 'L',
                'margin_top'    => 2,
                'margin_bottom' => 7,
                'margin_left'   => 5,
                'margin_right'  => 5,
                'setAutoBottomMargin' => 'stretch',
            ]);

            // Set footer for all pages
            $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed On: <strong>' . Carbon::now()->format('Y-m-d H:i:s') . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by: <strong>' . auth()->user()->user->surname . ' ' . auth()->user()->user->first_name . '</strong></td>
                </tr>
            </table>
        ');

            // Write HTML content
            $mpdf->WriteHTML($html);

            // Generate PDF filename
            $pdfFileName = str_replace('/', '', $account->client_account_name) . ' LEDGER STATEMENT.pdf';

            // Output PDF as downloadable file
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);
        } else {
            return back()->with('info', 'No Transactions recorded for the selected period');
        }
    }
    public function transportDetails()
    {
        $startOfMonth = now()->startOfMonth()->timestamp;
        $endOfMonth = now()->endOfMonth()->timestamp;

        $invoices = $this->AppClass->transportSummary($startOfMonth, $endOfMonth, $report = null, $transporter = null);
        $transporters = Transporter::orderBy('transporter_name', 'asc')->get();

        return view('account::purchases.transport')->with(['invoices' => $invoices, 'transporters' => $transporters]);
    }
    public function exportTransportReport(Request $request)
    {
        $startOfMonth = $request->from ? strtotime($request->from) : now()->startOfMonth()->timestamp;
        $endOfMonth = $request->to ? strtotime($request->to) : now()->endOfMonth()->timestamp;
        $report = $request->report == 1 ? 'COLLECTION' : ($request->report == 2 ? 'TRANSFER' : null);
        $transporter = $request->transporter;
        $orders = $this->AppClass->transportSummary($startOfMonth, $endOfMonth, $report, $transporter);
        return Excel::download(new ExportTeaTransport($orders), 'TRANSPORTERS' . ' ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function getLedgerFinancialYears()
    {
        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });
        return view('account::reports.expenses.financialYears')->with('years', $years);
    }
    public function getPlFinancialYears()
    {
        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });
        return view('account::reports.incomes.financialYears')->with('years', $years);
    }
    public function getLedgerWithInvoices($id)
    {
        $ledgers = ClientAccount::withTrashed()
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('invoices', function ($join) use ($id) {
                $join->on('invoices.client_id', '=', 'client_accounts.client_account_id')
                    ->where('invoices.financial_year_id', '=', $id);
            })
            ->leftJoin('purchases', function ($join) use ($id) {
                $join->on('purchases.client_id', '=', 'client_accounts.client_account_id')
                    ->where('purchases.financial_year_id', '=', $id);
            })
            ->leftJoin('payments', function ($join) use ($id) {
                $join->on('payments.account_id', '=', 'client_accounts.client_account_id')
                    ->where('payments.financial_year_id', '=', $id);
            })
            ->leftJoin('transactions', function ($join) use ($id) {
                $join->on('transactions.account_id', '=', 'client_accounts.client_account_id')
                    ->where('transactions.financial_year_id', '=', $id);
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'currencies.currency_symbol',
                'client_accounts.client_account_number',
                /*// Combined Debits
                DB::raw("COALESCE((SELECT SUM(CASE WHEN invoices.type = 1 THEN invoices.amount_due ELSE 0 END)
                FROM invoices WHERE invoices.client_id = client_accounts.client_account_id AND invoices.financial_year_id = '{$id}' AND invoices.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(CASE WHEN invoices.type = 1 THEN CASE WHEN invoice_items.tax_id IS NOT NULL THEN invoice_items.quantity * invoice_items.unit_price * 1.16 ELSE invoice_items.quantity * invoice_items.unit_price END ELSE 0 END)
                FROM invoice_items
                JOIN invoices ON invoice_items.invoice_id = invoices.invoice_id WHERE invoice_items.ledger_id = client_accounts.client_account_id AND invoices.financial_year_id = '{$id}' AND invoice_items.deleted_at IS NULL AND invoices.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(CASE WHEN purchases.type = 2 THEN purchases.amount_due ELSE 0 END)
                FROM purchases
                WHERE purchases.client_id = client_accounts.client_account_id AND purchases.financial_year_id = '{$id}' AND purchases.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(CASE WHEN purchases.type = 2 THEN CASE WHEN purchase_items.tax_id IS NOT NULL THEN purchase_items.quantity * purchase_items.unit_price * 1.16 ELSE purchase_items.quantity * purchase_items.unit_price END ELSE 0 END)
                FROM purchase_items
                JOIN purchases ON purchase_items.purchase_id = purchases.purchase_id WHERE purchase_items.ledger_id = client_accounts.client_account_id AND purchases.financial_year_id = '{$id}' AND purchase_items.deleted_at IS NULL AND purchases.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(transactions.amount_received)
                FROM transactions
                WHERE transactions.account_id = client_accounts.client_account_id AND transactions.financial_year_id = '{$id}'), 0) + COALESCE((SELECT SUM(payments.amount_received)
                FROM payments
                WHERE payments.client_id = client_accounts.client_account_id AND payments.financial_year_id = '{$id}'), 0) AS debit"),
                // Combined Credits
                DB::raw("
            COALESCE((SELECT SUM(CASE WHEN invoices.type = 2 THEN invoices.amount_due ELSE 0 END)
            FROM invoices WHERE invoices.client_id = client_accounts.client_account_id AND invoices.financial_year_id = '{$id}' AND invoices.deleted_at IS NULL), 0) +
            COALESCE((SELECT SUM(CASE WHEN invoices.type = 2 THEN CASE WHEN invoice_items.tax_id IS NOT NULL THEN invoice_items.quantity * invoice_items.unit_price * 1.16 ELSE invoice_items.quantity * invoice_items.unit_price END ELSE 0 END)
                FROM invoice_items
                JOIN invoices ON invoice_items.invoice_id = invoices.invoice_id WHERE invoice_items.ledger_id = client_accounts.client_account_id AND invoices.financial_year_id = '{$id}' AND invoice_items.deleted_at IS NULL AND invoices.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(CASE WHEN purchases.type = 1 THEN purchases.amount_due ELSE 0 END)
                FROM purchases
                WHERE purchases.client_id = client_accounts.client_account_id AND purchases.financial_year_id = '{$id}' AND purchases.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(CASE WHEN purchases.type = 1 THEN CASE WHEN purchase_items.tax_id IS NOT NULL THEN purchase_items.quantity * purchase_items.unit_price * 1.16 ELSE purchase_items.quantity * purchase_items.unit_price END ELSE 0 END)
                FROM purchase_items
                JOIN purchases ON purchase_items.purchase_id = purchases.purchase_id WHERE purchase_items.ledger_id = client_accounts.client_account_id AND purchases.financial_year_id = '{$id}' AND purchase_items.deleted_at IS NULL AND purchases.deleted_at IS NULL), 0) + COALESCE((SELECT SUM(payments.amount_received)
                FROM payments
                WHERE payments.account_id = client_accounts.client_account_id AND payments.financial_year_id = '{$id}'), 0) AS credit"),*/
                'client_accounts.deleted_at'
            )
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'currencies.currency_symbol',
                'client_accounts.client_account_number',
                'client_accounts.deleted_at'
            )
            ->orderBy('client_accounts.client_account_name')
            ->get();

        $currencies = Currency::whereNull('deleted_at')->get();
        $year = FinancialYear::where('financial_year_id', $id)->first();

        if ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            $year = [
                'financial_year_id' => $year->financial_year_id,
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        }

        return view('account::reports.incomes.ledgerPerFinancialYear')->with(['invoices' => $ledgers, 'currencies' => $currencies, 'fy' => $id, 'year' => $year]);
    }
    public function viewLedgerStatement($id)
    {
        list($client, $id) = explode(':', base64_decode($id));
        $financial = FinancialYear::find($id);
        $clientAccount = ClientAccount::withTrashed()->find($client);
        if (!$clientAccount) {
            return back()->with('info', 'Oops! this is not a valid ledger');
        }
        $currency = Currency::find($clientAccount->currency_id);
        $transactions = $this->AppClass->viewLedgerStatement($client, $id, $financial);
        $opBal = ClientAccount::where(['type' => 5])->first();
        return view('account::reports.incomes.ledgerStatement')->with(['statements' => $transactions, 'fy' => $financial, 'client' => $clientAccount, 'currency' => $currency, 'opBal' => $opBal]);
    }
    public function updateOpeningBalance(Request $request)
    {
        list($client, $year) = explode(':', base64_decode($request->clientId));
        $fy = FinancialYear::find($year);

        $ledger = ClientAccount::where(['type' => 5])->first();

        $openingBal = [
            'opening_balance_id' => (new CustomIds())->generateId(),
            'client_id' => $client,
            'ledger_id' => $ledger->client_account_id,
            'financial_year_id' => $year,
            'type' => $request->balanceType,
            'amount' => $request->amountInvoice,
            'date_invoiced' => strtotime(Carbon::parse($fy->year_starting)->startOfDay()),
            'user_id' => auth()->user()->user_id
        ];

        if (OpeningBalance::where(['client_id' => $client, 'financial_year_id' => $year, 'type' => $request->balanceType, 'amount' => $request->amountInvoice])->exists()) {
            return redirect()->back()->with('error', 'Oops! Similar transaction exists for this client');
        } else {
            OpeningBalance::create($openingBal);
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Client opening balance updated successfully');
        }
    }
    public function salesInvoiceDistribution($id)
    {
        $transaction = Transaction::join('client_accounts as ca', 'ca.client_account_id', '=', 'transactions.client_id')
            ->join('currencies as cur', 'cur.currency_id', '=', 'ca.currency_id')
            ->leftJoin('transaction_items as ti', function ($join) { // Use a closure for the join
                $join->on('ti.transaction_id', '=', 'transactions.transaction_id')
                    ->whereNull('ti.deleted_at'); // Move the WHERE clause here
            })
            ->selectRaw("client_account_id, transactions.transaction_id, ROUND(transactions.amount_received, 2) as amount_received, transactions.invoice_number,
                ca.client_account_name, ROUND(transactions.amount_received - COALESCE(SUM(CASE WHEN ti.type = 1 THEN ti.amount_settled ELSE 0 END), 0), 2) as unused_balance")
            ->where('transactions.transaction_id', $id)
            ->whereNull('transactions.deleted_at') //Keep this where condition
            ->groupBy('client_account_id', 'transactions.transaction_id', 'transactions.amount_received', 'transactions.invoice_number', 'ca.client_account_name')
            ->first();

        $invoices = Invoice::query()
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->leftJoin('invoices as credit_notes', function ($join) {
                $join->on('credit_notes.inv_reference', '=', 'invoices.invoice_number')
                    ->where('credit_notes.type', 2);
            })
            ->leftJoin('invoice_items as credit_note_items', 'credit_notes.invoice_id', '=', 'credit_note_items.invoice_id')
            ->leftJoin(DB::raw("(SELECT invoice_id, SUM(amount_settled) as total_settled FROM transaction_items WHERE deleted_at IS NULL GROUP BY invoice_id) as transactions"), function ($join) {
                $join->on('transactions.invoice_id', '=', 'invoices.invoice_id');
            })
            ->select([
                'invoices.invoice_id',
                'invoices.invoice_number',
                'invoices.client_id',
                'invoices.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                DB::raw('invoices.amount_due - IFNULL(credit_notes.amount_due, 0) as amount_due'),
                DB::raw('IFNULL(transactions.total_settled, 0) as amount_settled'),
                DB::raw('SUM(CASE WHEN invoice_items.tax_id IS NOT NULL THEN (invoice_items.unit_price * invoice_items.quantity) * 0.02 ELSE 0 END) as total_tax')
            ])
            ->leftJoin('invoice_items', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->where(['invoices.type' => 1, 'invoices.client_id' => $transaction->client_account_id])
            ->whereNot('invoices.status', 1)
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->where(function ($query) {
                $query->whereNull('credit_notes.deleted_at')->orWhereNull('credit_notes.invoice_id');
            })
            ->where(function ($query) {
                $query->whereNull('credit_note_items.deleted_at')->orWhereNull('credit_note_items.invoice_id');
            })
            ->groupBy(
                'invoices.invoice_id',
                'invoices.invoice_number',
                'amount_due',
                'invoices.client_id',
                'invoices.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'transactions.total_settled'
            )
            ->orderBy('invoices.invoice_number', 'asc')
            ->get();

        $transactions = Invoice::query()
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->leftJoin(DB::raw(
                "
        (SELECT invoice_id,
            SUM(CASE WHEN tax_id IS NOT NULL
                THEN (unit_price * quantity) * 0.02
                ELSE 0
            END) AS wht
        FROM invoice_items
        WHERE deleted_at IS NULL
        GROUP BY invoice_id
        ) as invoice_wht"
            ), 'invoices.invoice_id', '=', 'invoice_wht.invoice_id')
            ->whereIn('invoices.invoice_id', function ($query) use ($id) {
                $query->select('invoice_id')
                    ->from('transaction_items')
                    ->where('transaction_id', $id)
                    ->whereNull('deleted_at');
            })
            ->select([
                'invoices.invoice_id',
                'invoices.invoice_number',
                'invoices.client_id',
                'invoices.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                DB::raw(
                    'invoices.amount_due - IFNULL(
            (SELECT amount_due FROM invoices AS credit_notes
             WHERE inv_reference = invoices.invoice_number
               AND type = 2
               AND deleted_at IS NULL LIMIT 1),
            0) as amount_due'
                ),
                DB::raw(
                    "
            (SELECT ti.transaction_item_id
             FROM transaction_items ti
             WHERE ti.invoice_id = invoices.invoice_id
               AND ti.deleted_at IS NULL LIMIT 1) as transaction_item_id"
                ),
                DB::raw(
                    "
            (SELECT SUM(ti.amount_settled)
             FROM transaction_items ti
             WHERE ti.invoice_id = invoices.invoice_id
               AND ti.deleted_at IS NULL) as amount_settled"
                ),
                'invoice_wht.wht as wht', // Now using the LEFT JOIN subquery result
                'currency_symbol'
            ])
            ->where(['invoices.type' => 1])
            ->whereNull('invoices.deleted_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_items')
                    ->whereColumn('transaction_items.invoice_id', 'invoices.invoice_id')
                    ->whereNull('transaction_items.deleted_at');
            })
            ->groupBy(
                'invoices.invoice_id',
                'invoices.invoice_number',
                'invoices.amount_due',
                'invoices.client_id',
                'invoices.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'currency_symbol',
                'invoice_wht.wht' // Include this in groupBy
            )
            ->orderBy('invoices.invoice_number', 'desc')
            ->get();

        $journals = AdjustmentJournal::where(['ledger_id' => $transaction->client_account_id, 'type' => 2])->orderBy('date_adjusted')->get();

        return view('account::sales.salesInvoiceDistribution')->with(['invoices' => $invoices, 'transaction' => $transaction, 'transactions' => $transactions, 'journals' => $journals]);
    }
    public function removeTransactionItem($id)
    {
        $transactionItem = TransactionItem::find($id);
        $amountPaid = TransactionItem::where(['invoice_id' => $transactionItem->invoice_id, 'transaction_id' => $transactionItem->transaction_id])->sum('amount_settled');
        $invoice = Invoice::leftJoin('invoices as credit_notes', function ($join) {
            $join->on('credit_notes.inv_reference', '=', 'invoices.invoice_number')
                ->where('credit_notes.type', 2);
        })
            ->select(DB::raw('invoices.amount_due - IFNULL(credit_notes.amount_due, 0) as amount_due'))
            ->where('invoices.invoice_id', $transactionItem->invoice_id)
            ->first();

        if (number_format($amountPaid, 2) >= number_format($invoice->amount_due, 2)) {
            Invoice::where('invoice_id', $transactionItem->invoice_id)->update(['status' => 0]);
            TransactionItem::where(['invoice_id' => $transactionItem->invoice_id, 'transaction_id' => $transactionItem->transaction_id])->delete();
        } else {
            Invoice::where('invoice_id', $transactionItem->invoice_id)->update(['status' => 2]);
            $transactionItem->delete();
        }

        return back()->with('success', 'Invoice deallocation successful');
    }
    public function purchaseVoucherDistribution($id)
    {
        $transactions = Purchase::query()
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->leftJoin(DB::raw(
                "
        (SELECT purchase_id,
            SUM(CASE WHEN tax_id IS NOT NULL
                THEN (unit_price * quantity) * 0.02
                ELSE 0
            END) AS wht
        FROM purchase_items
        WHERE deleted_at IS NULL
        GROUP BY purchase_id
        ) AS purchase_wht"
            ), 'purchases.purchase_id', '=', 'purchase_wht.purchase_id')
            ->whereIn('purchases.purchase_id', function ($query) use ($id) {
                $query->select('purchase_id')
                    ->from('payment_items')
                    ->where('payment_id', $id)
                    ->whereNull('deleted_at');
            })
            ->select([
                'purchases.purchase_id',
                'purchases.voucher_number',
                'purchases.client_id',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'purchases.amount_due',
                DB::raw(
                    "
            (SELECT SUM(pi.amount_settled)
             FROM payment_items pi
             WHERE pi.purchase_id = purchases.purchase_id
               AND pi.deleted_at IS NULL) as amount_settled"
                ),
                DB::raw(
                    "
            (SELECT pi.payment_item_id
             FROM payment_items pi
             WHERE pi.purchase_id = purchases.purchase_id
               AND pi.deleted_at IS NULL
             LIMIT 1) as payment_item_id"
                ), // Get a representative `payment_item_id`
                'purchase_wht.wht as wht', // Now using the LEFT JOIN subquery result
                'currency_symbol'
            ])
            ->whereNull('purchases.deleted_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('payment_items')
                    ->whereColumn('payment_items.purchase_id', 'purchases.purchase_id')
                    ->whereNull('payment_items.deleted_at');
            })
            ->groupBy(
                'purchases.purchase_id',
                'purchases.voucher_number',
                'purchases.client_id',
                'purchases.amount_due',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'currency_symbol',
                'purchase_wht.wht' // Include this in groupBy
            )
            ->orderBy('purchases.voucher_number', 'desc')
            ->get();

        $transaction = Payment::join('client_accounts as ca', 'ca.client_account_id', '=', 'payments.client_id')
            ->join('currencies as cur', 'cur.currency_id', '=', 'ca.currency_id')
            ->leftJoin('payment_items as ti', function ($join) { // Use a closure for the join
                $join->on('ti.payment_id', '=', 'payments.payment_id')
                    ->whereNull('ti.deleted_at'); // Move the WHERE clause here
            })
            ->selectRaw("client_account_id, payments.payment_id, ROUND(payments.amount_received, 2) as amount_received, payments.invoice_number,
                ca.client_account_name, ROUND(payments.amount_received - COALESCE(SUM(CASE WHEN ti.type = 1 THEN ti.amount_settled ELSE 0 END), 0), 2) as unused_balance")
            ->where('payments.payment_id', $id)
            ->whereNull('payments.deleted_at') //Keep this where condition
            ->groupBy('client_account_id', 'payments.payment_id', 'payments.amount_received', 'payments.invoice_number', 'ca.client_account_name')
            ->first();

        $invoices = Purchase::query()
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->leftJoin('purchases as debit_notes', function ($join) {
                $join->on('debit_notes.inv_reference', '=', 'purchases.voucher_number')
                    ->where('debit_notes.type', 2);
            })
            ->leftJoin('purchase_items as debit_note_items', 'debit_notes.purchase_id', '=', 'debit_note_items.purchase_id')
            ->leftJoin(DB::raw("(SELECT purchase_id, SUM(amount_settled) as total_settled FROM payment_items WHERE deleted_at IS NULL GROUP BY purchase_id) as transactions"), function ($join) {
                $join->on('transactions.purchase_id', '=', 'purchases.purchase_id');
            })
            ->select([
                'purchases.purchase_id',
                'purchases.voucher_number',
                'purchases.client_id',
                'purchases.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                DB::raw('purchases.amount_due - IFNULL(debit_notes.amount_due, 0) as amount_due'),
                DB::raw('IFNULL(transactions.total_settled, 0) as amount_settled'),
                DB::raw('SUM(CASE WHEN purchase_items.tax_id IS NOT NULL THEN (purchase_items.unit_price * purchase_items.quantity) * 0.02 ELSE 0 END) as total_tax')
            ])
            ->leftJoin('purchase_items', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->where(['purchases.type' => 1, 'purchases.client_id' => $transaction->client_account_id])
            ->whereNot('purchases.status', 1)
            ->whereNull('purchases.deleted_at')
            ->whereNull('purchase_items.deleted_at')
            ->where(function ($query) {
                $query->whereNull('debit_notes.deleted_at')->orWhereNull('debit_notes.purchase_id');
            })
            ->where(function ($query) {
                $query->whereNull('debit_note_items.deleted_at')->orWhereNull('debit_note_items.purchase_id');
            })
            ->groupBy(
                'purchases.purchase_id',
                'purchases.voucher_number',
                'amount_due',
                'purchases.client_id',
                'purchases.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'transactions.total_settled'
            )
            ->orderBy('purchases.voucher_number', 'asc')
            ->get();

        return view('account::purchases.purchaseVoucherDistribution')->with(['invoices' => $invoices, 'transaction' => $transaction, 'transactions' => $transactions]);
    }
    public function viewPurchasePayments()
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $accounts =  DB::table('ledgers')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol', 'currency_id')
            ->orderBy('client_account_name', 'asc')
            ->where(['account_status' => 1])
            ->get();

        $methods = DB::table('ledgers')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol', 'currency_id')
            ->orderBy('client_account_name', 'asc')
            ->where(['account_status' => 1, 'type' => 4])
            ->get();

        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });
        $transactions = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'payments.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('currencies as cc', 'cc.currency_id', '=', 'acc.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'payments.financial_year_id')
            ->leftJoin('payment_items', 'payment_items.payment_id', '=', 'payments.payment_id')
            ->select(
                'payments.payment_id',
                'payments.invoice_number',
                'payments.transaction_code',
                'client_accounts.client_account_name',
                'payments.amount_received',
                'acc.client_account_name as account',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'payments.date_received',
                'acc.type',
                'payments.account_id',
                'payments.client_id',
                'payments.financial_year_id',
                'payments.description',
                'si_number',
                'exchange_rate',
                DB::raw("
            CASE
                WHEN client_accounts.currency_id = acc.currency_id
                    THEN payments.amount_received
                WHEN currencies.priority = 1 AND cc.priority = 2
                    THEN payments.amount_received * payments.exchange_rate
                WHEN currencies.priority = 2 AND cc.priority = 1
                    THEN payments.amount_received / payments.exchange_rate
                ELSE 0
            END AS amount_received
        "),
                DB::raw('SUM(payment_items.amount_settled) as amount_settled')
            )
            ->whereNull('payments.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('payment_items.deleted_at')
            ->groupBy(
                'payments.payment_id',
                'payments.invoice_number',
                'payments.transaction_code',
                'client_accounts.client_account_name',
                'payments.amount_received',
                'acc.client_account_name',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'payments.date_received',
                'acc.type',
                'si_number',
                'exchange_rate',
                'payments.account_id',
                'payments.client_id',
                'payments.financial_year_id',
                'payments.description',
                'client_accounts.currency_id',
                'acc.currency_id',
                'currencies.priority',
                'cc.priority',
                'payments.exchange_rate'  // **Added this field to fix the error**
            )
            ->orderBy('payments.created_at', 'desc')
            ->whereBetween('payments.created_at', [$currentMonthStart, $currentMonthEnd])
            ->get();

        return view('account::purchases.payments')->with(['years' => $years, 'accounts' => $accounts, 'transactions' => $transactions, 'methods' => $methods]);
    }
    public function yearlyPayments($id)
    {
        $accounts = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol')
            ->orderBy('client_account_name', 'asc')
            ->where(['account_status' => 1])
            ->get();
        $methods = DB::table('ledgers')
            ->select('client_account_id', 'client_account_number', 'client_account_name', 'opening_date', 'chart_name', 'sub_account_name', 'account_name', 'account_type', 'currency_name', 'currency_symbol', 'currency_id')
            ->orderBy('client_account_name', 'asc')
            ->where(['account_status' => 1, 'type' => 4])
            ->get();

        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });

        $transactions = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'payments.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('currencies as cc', 'cc.currency_id', '=', 'acc.currency_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'payments.financial_year_id')
            ->leftJoin('payment_items', 'payment_items.payment_id', '=', 'payments.payment_id')
            ->select(
                'payments.payment_id',
                'payments.invoice_number',
                'payments.transaction_code',
                'client_accounts.client_account_name',
                'payments.amount_received',
                'acc.client_account_name as account',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'payments.date_received',
                'acc.type',
                'payments.account_id',
                'payments.client_id',
                'payments.financial_year_id',
                'payments.description',
                DB::raw("
            CASE
                WHEN client_accounts.currency_id = acc.currency_id
                    THEN payments.amount_received
                WHEN currencies.priority = 1 AND cc.priority = 2
                    THEN payments.amount_received * payments.exchange_rate
                WHEN currencies.priority = 2 AND cc.priority = 1
                    THEN payments.amount_received / payments.exchange_rate
                ELSE 0
            END AS amount_received
        "),
                DB::raw('SUM(payment_items.amount_settled) as amount_settled'),
                'si_number',
                'currencies.currency_symbol',
                'exchange_rate'
            )
            ->whereNull('payments.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->whereNull('payment_items.deleted_at')
            ->groupBy(
                'payments.payment_id',
                'payments.invoice_number',
                'payments.transaction_code',
                'client_accounts.client_account_name',
                'payments.amount_received',
                'acc.client_account_name',
                'financial_years.year_starting',
                'financial_years.year_ending',
                'payments.date_received',
                'acc.type',
                'payments.account_id',
                'payments.client_id',
                'payments.financial_year_id',
                'payments.description',
                'client_accounts.currency_id',
                'acc.currency_id',
                'currencies.priority',
                'cc.priority',
                'payments.exchange_rate',
                'si_number',
                'currency_symbol',
                'exchange_rate'
            )
            ->orderBy('payments.created_at', 'desc')
            ->where('payments.financial_year_id', $id)
            ->get();

        return view('account::purchases.payments')->with(['years' => $years, 'accounts' => $accounts, 'transactions' => $transactions, 'methods' => $methods]);
    }
    public function storePurchasePaymentInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            foreach ($request->accounts as $key => $account) {
                $inv = [
                    'payment_id' => (new CustomIds())->generateId(),
                    'invoice_number' => Payment::newPayInvNumber(),
                    'client_id' => $account['account_id'],
                    'date_received' => strtotime($request->get('dateReceived')),
                    'amount_received' => $account['amount'],
                    'exchange_rate' => $account['exchange_rate'],
                    'financial_year_id' => $request->get('financialYear'),
                    'description' => $request->get('description'),
                    'user_id' => auth()->user()->user_id,
                    'transaction_code' => $request->transaction,
                    'account_id' => $request->account,
                    'si_number' => $request->si_number
                ];
                Payment::create($inv);
            }
            DB::commit();
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Payment Invoice Created Successfully');
        } catch (Exception $e) {
            //            // Rollback the transaction if an exception occurs
            DB::rollback();
            //            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function updatePurchasePaymentInvoice(Request $request, $id)
    {
        $request->validate([
            'clientAccount' => 'string|required',
            'amountReceived' => 'required',
            'dateReceived' => 'required',
            'description' => 'required',
            'financialYear' => 'required|string',
            'account' => 'required|string'
        ]);
        $amountReceived = $request->get('amountReceived');
        $receiptAmount = Payment::join('client_accounts as ca', 'ca.client_account_id', '=', 'payments.client_id')
            ->leftJoin('payment_items as ti', function ($join) { // Use a closure for the join
                $join->on('ti.payment_id', '=', 'payments.payment_id')
                    ->whereNull('ti.deleted_at'); // Move the WHERE clause here
            })
            ->select(DB::raw("ROUND(IFNULL(ti.amount_settled, 0), 0) as amount_received"))
            ->where('payments.payment_id', $id)
            ->whereNull('payments.deleted_at') //Keep this where condition
            ->whereNull('ti.deleted_at') //Keep this where condition
            ->first()->amount_received;

        if ((float) $amountReceived < (float) $receiptAmount) {
            return redirect()->back()->with('error', 'Oops! Amount received should not be less that receipted amount');
        }

        DB::beginTransaction();
        try {
            $inv = [
                'client_id' => $request->get('clientAccount'),
                'date_received' => strtotime($request->get('dateReceived')),
                'amount_received' => $request->get('amountReceived'),
                'financial_year_id' => $request->get('financialYear'),
                'description' => $request->get('description'),
                'transaction_code' => $request->transaction,
                'account_id' => $request->account,
                'si_number' => $request->si_number,
                'exchange_rate' => $request->exchangeRate
            ];
            Payment::where('payment_id', $id)->update($inv);
            DB::commit();
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Payment Invoice Updated Successfully');
        } catch (Exception $e) {
            //            // Rollback the transaction if an exception occurs
            DB::rollback();
            //            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function deletePurchasePaymentInvoice($id)
    {
        Payment::where('payment_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Payment deleted successfully');
    }
    public function viewAgingAnalysis()
    {
        return view('account::reports.aging.index');
    }
    public function viewAgingReport(Request $request, $id)
    {
        $fy = FinancialYear::find($request->financial_year);
        $currency = Currency::find($request->currency_id);

        // Determine date range based on financial year filter
        if ($fy !== null) {
            $startOfYear = Carbon::parse($fy->year_starting)->format('Y-m-d');
            $endOfYear = Carbon::parse($fy->year_ending)->format('Y-m-d');
        } else {
            $startOfYear = null;
            $endOfYear = null;
        }

        if (base64_decode($id) == 1) {
            // Subquery to get the sum of payments per invoice
            $subquery = DB::table('transaction_items')
                ->select('invoice_id', DB::raw('SUM(amount_settled) as total_payments'))
                ->whereNull('deleted_at')
                ->groupBy('invoice_id');

            // Subquery to calculate the sum of credit note amounts for each invoice
            $creditNotesSubquery = DB::table('invoices as credit')
                ->select(
                    'credit.inv_reference as invoice_reference',
                    DB::raw('SUM(credit.amount_due) as total_credit_notes')
                )
                ->where('credit.type', 2) // Credit notes type
                ->whereNull('credit.deleted_at')
                ->groupBy('credit.inv_reference');

            $agingQuery = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_id as client_id',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',

                    // 0-30 Days
                    DB::raw("
                            SUM(CASE
                                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(invoices.date_invoiced)) <= 30
                                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                                ELSE 0
                            END) as amount_due_30_days
                        "),

                                        // 31-60 Days
                                        DB::raw("
                            SUM(CASE
                                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(invoices.date_invoiced)) > 30
                                AND DATEDIFF(CURDATE(), FROM_UNIXTIME(invoices.date_invoiced)) <= 60
                                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                                ELSE 0
                            END) as amount_due_60_days
                        "),

                                        // 61-90 Days
                                        DB::raw("
                            SUM(CASE
                                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(invoices.date_invoiced)) > 60
                                AND DATEDIFF(CURDATE(), FROM_UNIXTIME(invoices.date_invoiced)) <= 90
                                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                                ELSE 0
                            END) as amount_due_90_days
                        "),

                                        // 90+ Days
                                        DB::raw("
                            SUM(CASE
                                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(invoices.date_invoiced)) > 90
                                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                                ELSE 0
                            END) as amount_due_90_plus
                        "),

                                        // Total Amount Due
                                        DB::raw("
                            SUM((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as total_amount_due
                        ")
                )
                ->join('invoices', 'client_accounts.client_account_id', '=', 'invoices.client_id')
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                // Join with the subquery to get the sum of payments
                ->leftJoinSub($subquery, 'payments', function ($join) {
                    $join->on('invoices.invoice_id', '=', 'payments.invoice_id');
                })

                // Join with the credit notes subquery
                ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                    $join->on('invoices.invoice_number', '=', 'credit_notes.invoice_reference');
                })
                ->where('invoices.status', '!=', 1) // Exclude fully paid invoices
                ->where('invoices.type', 1) // Filter for specific invoice type
                ->whereNull('invoices.deleted_at')
                ->whereNull('client_accounts.deleted_at');

            // Apply financial year filter if $fy is not null
            if ($fy !== null) {
                $agingQuery->whereBetween(DB::raw('FROM_UNIXTIME(invoices.date_invoiced)'), [$startOfYear, $endOfYear]);
            }

            if ($currency !== null){
                $agingQuery->where('currencies.currency_id', $currency->currency_id);
            }

            $agingData = $agingQuery
                ->groupBy(
                    'client_accounts.client_account_id',
                    'client_accounts.client_account_name',
                    'currencies.currency_symbol'
                )
                ->orderBy('client_account_name')
                ->get();
        } else {
            // Subquery to get the sum of payments per invoice
            $subquery = DB::table('payment_items')
                ->select('purchase_id', DB::raw('SUM(amount_settled) as total_payments'))
                ->whereNull('deleted_at')
                ->groupBy('purchase_id');

            // Subquery to get the sum of petty cash per client
            $pettyCash = DB::table('petty_cashes')
                ->select('ledger_id', DB::raw('SUM(amount) as petty_payments'))
                ->whereNull('deleted_at')
                ->groupBy('ledger_id');

            $agingQuery = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_id as client_id',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',

                    // 0-30 Days
                    DB::raw("
                        SUM(CASE
                            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(purchases.date_invoiced)) <= 30
                            THEN (purchases.amount_due - (COALESCE(payments.total_payments, 0) + COALESCE(cash.petty_payments, 0)))
                            ELSE 0
                        END) as amount_due_30_days
                    "),

                                    // 31-60 Days
                                    DB::raw("
                        SUM(CASE
                            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(purchases.date_invoiced)) > 30
                            AND DATEDIFF(CURDATE(), FROM_UNIXTIME(purchases.date_invoiced)) <= 60
                            THEN (purchases.amount_due - (COALESCE(payments.total_payments, 0) + COALESCE(cash.petty_payments, 0)))
                            ELSE 0
                        END) as amount_due_60_days
                    "),

                                    // 61-90 Days
                                    DB::raw("
                        SUM(CASE
                            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(purchases.date_invoiced)) > 60
                            AND DATEDIFF(CURDATE(), FROM_UNIXTIME(purchases.date_invoiced)) <= 90
                            THEN (purchases.amount_due - (COALESCE(payments.total_payments, 0) + COALESCE(cash.petty_payments, 0)))
                            ELSE 0
                        END) as amount_due_90_days
                    "),

                                    // 90+ Days
                                    DB::raw("
                        SUM(CASE
                            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(purchases.date_invoiced)) > 90
                            THEN (purchases.amount_due - (COALESCE(payments.total_payments, 0) + COALESCE(cash.petty_payments, 0)))
                            ELSE 0
                        END) as amount_due_90_plus
                    "),

                                    // Total Amount Due
                                    DB::raw("
                        SUM(purchases.amount_due - (COALESCE(payments.total_payments, 0) + COALESCE(cash.petty_payments, 0))) as total_amount_due
                    ")
                )
                ->join('purchases', 'client_accounts.client_account_id', '=', 'purchases.client_id')
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                // Join with payments subquery (per invoice)
                ->leftJoinSub($subquery, 'payments', function ($join) {
                    $join->on('purchases.purchase_id', '=', 'payments.purchase_id');
                })

                // Join with petty cash subquery (per client ledger)
                ->leftJoinSub($pettyCash, 'cash', function ($join) {
                    $join->on('client_accounts.client_account_id', '=', 'cash.ledger_id');
                })
                ->where('purchases.status', '!=', 1) // Exclude fully paid invoices
                ->where('purchases.type', 1)        // Filter for specific invoice type
                ->whereNull('purchases.deleted_at')
                ->whereNull('client_accounts.deleted_at');

            // Apply financial year filter if $fy is not null
            if ($fy !== null) {
                $agingQuery->whereBetween(DB::raw('FROM_UNIXTIME(purchases.date_invoiced)'), [$startOfYear, $endOfYear]);
            }

            if ($currency !== null){
                $agingQuery->where('currencies.currency_id', $currency->currency_id);
            }

            $agingData = $agingQuery
                ->groupBy(
                    'client_accounts.client_account_id',
                    'client_accounts.client_account_name',
                    'currencies.currency_symbol',
                    'cash.petty_payments'
                )
                ->orderBy('client_account_name')
                ->get();
        }

        $currencies = Currency::where('status', 1)->get();
        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id,
                'financial_year' => $formattedYear
            ];
        });

        // Handle Export Request
        if ($request->export) {
            return $this->exportAgingReport($agingData, $id, $fy);
        }

        return view('account::reports.aging.agingReport')->with([
            'data' => $agingData,
            'id' => $id,
            'currencies' => $currencies,
            'years' => $years
        ]);
    }

    /**
     * Export aging report to Excel
     */
    private function exportAgingReport($agingData, $id, $fy)
    {
        // Filter creditors with non-zero balance
        $creditorsWithBalance = $agingData->filter(function ($creditor) {
            return abs((float)$creditor->total_amount_due) > 0.01; // Use small threshold for floating point comparison
        });

        if ($creditorsWithBalance->isEmpty()) {
            return back()->with('error', 'No creditors with outstanding balance found.');
        }

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();

        // Create summary sheet
        $this->createSummarySheet($spreadsheet, $creditorsWithBalance, $fy, base64_decode($id));

        // Create individual ledger sheets for each creditor
        foreach ($creditorsWithBalance as $creditor) {
            $this->createLedgerSheet(
                $spreadsheet,
                $creditor,
                $fy ? $fy->financial_year_id : null,
                $fy
            );
        }

        // Generate filename
        $reportType = base64_decode($id) == 1 ? 'Debtors' : 'Creditors';
        $yearSuffix = $fy ? '_FY_' . Carbon::parse($fy->year_starting)->format('Y') : '';
        $filename = $reportType . '_Aging_Report' . $yearSuffix . '_' . date('Y-m-d_His') . '.xlsx';

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'aging_report_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Return download response
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Create summary sheet with aging buckets
     */
    private function createSummarySheet($spreadsheet, $creditors, $fy, $reportType)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $reportTypeName = $reportType == 1 ? 'Debtors' : 'Creditors';
        $sheet->setTitle($reportTypeName . ' Summary');

        // Title
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', $reportTypeName . ' Aging Report Summary');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currentRow = 2;

        // Financial Year Info
        if ($fy) {
            $sheet->setCellValue('A' . $currentRow, 'Financial Year: ' .
                Carbon::parse($fy->year_starting)->format('Y-m-d') . ' to ' .
                Carbon::parse($fy->year_ending)->format('Y-m-d'));
            $sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
            $currentRow++;
        }

        // Report Date
        $sheet->setCellValue('A' . $currentRow, 'Report Date: ' . date('Y-m-d H:i:s'));
        $sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
        $currentRow += 2; // Skip a row

        // Headers
        $headerRow = $currentRow;
        $headers = ['Client Name', 'Currency', '0-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total Balance'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $col++;
        }

        // Style headers
        $headerStyle = $sheet->getStyle('A' . $headerRow . ':G' . $headerRow);
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data rows
        $row = $headerRow + 1;
        foreach ($creditors as $creditor) {
            $sheet->setCellValue('A' . $row, $creditor->client_name);
            $sheet->setCellValue('B' . $row, $creditor->currency_symbol ?? 'N/A');
            $sheet->setCellValue('C' . $row, (float)$creditor->amount_due_30_days);
            $sheet->setCellValue('D' . $row, (float)$creditor->amount_due_60_days);
            $sheet->setCellValue('E' . $row, (float)$creditor->amount_due_90_days);
            $sheet->setCellValue('F' . $row, (float)$creditor->amount_due_90_plus);
            $sheet->setCellValue('G' . $row, (float)$creditor->total_amount_due);

            // Format currency columns
            $sheet->getStyle('C' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Totals row
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        $sheet->setCellValue('B' . $totalRow, '');
        $sheet->getStyle('A' . $totalRow)->getFont()->setBold(true);

        $startDataRow = $headerRow + 1;
        $endDataRow = $totalRow - 1;

        $sheet->setCellValue('C' . $totalRow, '=SUM(C' . $startDataRow . ':C' . $endDataRow . ')');
        $sheet->setCellValue('D' . $totalRow, '=SUM(D' . $startDataRow . ':D' . $endDataRow . ')');
        $sheet->setCellValue('E' . $totalRow, '=SUM(E' . $startDataRow . ':E' . $endDataRow . ')');
        $sheet->setCellValue('F' . $totalRow, '=SUM(F' . $startDataRow . ':F' . $endDataRow . ')');
        $sheet->setCellValue('G' . $totalRow, '=SUM(G' . $startDataRow . ':G' . $endDataRow . ')');

        $sheet->getStyle('C' . $totalRow . ':G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A' . $totalRow . ':G' . $totalRow)->getFont()->setBold(true);

        // Add borders to data area
        $dataRange = 'A' . $headerRow . ':G' . $totalRow;
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);
    }

    /**
     * Create ledger sheet for a specific creditor
     */
    private function createLedgerSheet($spreadsheet, $creditor, $financialYearId, $fy)
    {
        $id = base64_encode($creditor->client_id.':'.$financialYearId);
        // Get ledger transactions using existing method
        $transactions = $this->AppClass->viewLedgerStatement(
            $creditor->client_id,
            $financialYearId ?? 0,
            $fy,
            true // Don't include opening balance adjustment in detailed view
        );

        // Sanitize sheet name (max 31 chars, no special chars)
        $sheetName = substr($creditor->client_name, 0, 31);
        $invalidChars = [':', '\\', '/', '?', '*', '[', ']'];
        foreach ($invalidChars as $char) {
            $sheetName = str_replace($char, '', $sheetName);
        }

        // Ensure unique sheet name
        $originalSheetName = $sheetName;
        $counter = 1;
        while ($spreadsheet->sheetNameExists($sheetName)) {
            $sheetName = substr($originalSheetName, 0, 28) . '_' . $counter;
            $counter++;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetName);

        // Title
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Ledger Statement - ' . $creditor->client_name);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add financial year info if available
        if ($fy) {
            $sheet->mergeCells('A2:H2');
            $sheet->setCellValue('A2', 'Period: ' .
                Carbon::parse($fy->year_starting)->format('Y-m-d') . ' to ' .
                Carbon::parse($fy->year_ending)->format('Y-m-d'));
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Headers
        $headerRow = $fy ? 4 : 3;
        $headers = ['Date', 'Type', 'Reference', 'Description', 'Ledger', 'Debit', 'Credit', 'Balance'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $col++;
        }

        // Style headers
        $headerStyle = $sheet->getStyle('A' . $headerRow . ':H' . $headerRow);
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data rows
        $row = $headerRow + 1;
        $runningBalance = 0;

        if ($transactions && $transactions->count() > 0) {
            foreach ($transactions as $transaction) {
                $debit = (float)($transaction->debit ?? 0);
                $credit = (float)($transaction->credit ?? 0);

                // Calculate balance based on account type
                // For creditors (liabilities), credits increase, debits decrease
                // For debtors (assets), debits increase, credits decrease
                if (isset($transaction->type) && $transaction->type == 3) {
                    // Creditor/Liability: Credit increases balance
                    $runningBalance += ($credit - $debit);
                } else {
                    // Debtor/Asset: Debit increases balance
                    $runningBalance += ($debit - $credit);
                }

                // Format date
                $transactionDate = '';
                if (isset($transaction->transaction_date)) {
                    if (is_numeric($transaction->transaction_date)) {
                        $transactionDate = date('Y-m-d', $transaction->transaction_date);
                    } else {
                        $transactionDate = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
                    }
                }

                $sheet->setCellValue('A' . $row, $transactionDate);
                $sheet->setCellValue('B' . $row, $transaction->transaction_type ?? '');
                $sheet->setCellValue('C' . $row, $transaction->transaction_number ?? '');
                $sheet->setCellValue('D' . $row, $transaction->description ?? '');
                $sheet->setCellValue('E' . $row, $transaction->ledger_name ?? '');
                $sheet->setCellValue('F' . $row, $debit);
                $sheet->setCellValue('G' . $row, $credit);
                $sheet->setCellValue('H' . $row, $runningBalance);

                // Format currency columns
                $sheet->getStyle('F' . $row . ':H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

                $row++;
            }
        } else {
            // No transactions found
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->setCellValue('A' . $row, 'No transactions found for this period');
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row)->getFont()->setItalic(true);
        }

        // Add borders to data area
        if ($transactions && $transactions->count() > 0) {
            $dataRange = 'A' . $headerRow . ':H' . ($row - 1);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(18);

        // Freeze header row
        $sheet->freezePane('A' . ($headerRow + 1));
    }
    public function downloadDebtList($id)
    {
        $today = Carbon::today()->format('Y-m-d');
        $earlier = Carbon::now()->startOfYear()->format('Y-m-d');

        if (base64_decode($id) == 1) {

            $openingBalanceSub = DB::table('opening_balances')
                ->select(
                    'client_id',
                    DB::raw("SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS opening_debit"),
                    DB::raw("SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS opening_credit")
                )
                ->whereNull('deleted_at')
                ->groupBy('client_id');

            // Subquery: Total payments per client
            $paymentsSub = DB::table('transactions')
                ->select('transactions.client_id', DB::raw('SUM(transactions.amount_received) as total_credits'))
                ->whereNull('transactions.deleted_at')
                ->groupBy('transactions.client_id'); // <-- Group by is required here

            // Subquery: Total credit notes per client
            $creditNotesSub = DB::table('invoices as credit')
                ->select('credit.client_id', DB::raw('SUM(credit.amount_due) as total_credit_notes'))
                ->where('credit.type', 2) // Credit Notes
                ->whereNull('credit.deleted_at')
                ->groupBy('credit.client_id');

            $debtors = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_number',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',

                    DB::raw('(SUM(invoices.amount_due) + COALESCE(opening.opening_debit, 0)) as total_debits'),
                    DB::raw('(COALESCE(payments.total_credits, 0) + COALESCE(opening.opening_credit, 0)) as total_credits'),
                    DB::raw('COALESCE(credit_notes.total_credit_notes, 0) as total_credit_notes')
                )
                ->join('invoices', function ($join) {
                    $join->on('invoices.client_id', '=', 'client_accounts.client_account_id')
                        ->where('invoices.type', 1)
                        ->whereNull('invoices.deleted_at');
                })
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
                ->leftJoinSub(
                    DB::table('transactions')
                        ->select('client_id', DB::raw('SUM(amount_received) as total_credits'))
                        ->whereNull('deleted_at')
                        ->groupBy('client_id'),
                    'payments',
                    function ($join) {
                        $join->on('payments.client_id', '=', 'client_accounts.client_account_id');
                    }
                )
                ->leftJoinSub(
                    DB::table('invoices as credit')
                        ->select('client_id', DB::raw('SUM(amount_due) as total_credit_notes'))
                        ->where('type', 2)
                        ->whereNull('deleted_at')
                        ->groupBy('client_id'),
                    'credit_notes',
                    function ($join) {
                        $join->on('credit_notes.client_id', '=', 'client_accounts.client_account_id');
                    }
                )
                ->leftJoinSub(
                    DB::table('opening_balances')
                        ->select(
                            'client_id',
                            DB::raw('SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS opening_debit'),
                            DB::raw('SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS opening_credit')
                        )
                        ->whereNull('deleted_at')
                        ->groupBy('client_id'),
                    'opening',
                    function ($join) {
                        $join->on('opening.client_id', '=', 'client_accounts.client_account_id');
                    }
                )
                ->whereNull('client_accounts.deleted_at')
                ->where('client_accounts.type', 7)
                ->groupBy(
                    'client_accounts.client_account_number',
                    'client_accounts.client_account_name',
                    'currencies.currency_symbol',
                    'payments.total_credits',
                    'credit_notes.total_credit_notes',
                    'opening.opening_debit',
                    'opening.opening_credit'
                )
                ->orderBy('client_accounts.client_account_name')
                ->get();

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header row
            $headers = ['Account Number', 'Client Name', 'Currency', 'Debit', 'Credit', 'Balance'];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);

            // Data rows
            $row = 2;
            foreach ($debtors as $debtor) {

                $debit = number_format(($debtor->total_debits ?? 0) - ($debtor->total_credit_notes ?? 0), 2, '.', '');
                $credit = number_format($debtor->total_credits ?? 0, 2, '.', '');
                $balance = number_format($debit - $credit, 2, '.', '');

                $sheet->setCellValueExplicit("A{$row}", $debtor->client_account_number, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$row}", $debtor->client_name, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", $debtor->currency_symbol, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", $debit, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("E{$row}", $credit, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("F{$row}", $balance, DataType::TYPE_NUMERIC);

                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Export
            $writer = new Xlsx($spreadsheet);
            $filename = 'Debtors_List.xlsx';

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } else {


            $openingBalanceSub = DB::table('opening_balances')
                ->select(
                    'client_id',
                    DB::raw("SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS opening_debit"),
                    DB::raw("SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS opening_credit")
                )
                ->whereNull('deleted_at')
                ->whereBetween(DB::raw('FROM_UNIXTIME(opening_balances.date_invoiced)'), [$earlier, $today])
                ->groupBy('client_id');

            // Subquery: Total payments per client
            $paymentsSub = DB::table('payments')
                ->select('payments.client_id', DB::raw('SUM(payments.amount_received) as total_credits'))
                ->whereNull('payments.deleted_at')
                ->whereBetween(DB::raw('FROM_UNIXTIME(payments.date_received)'), [$earlier, $today])
                ->groupBy('payments.client_id'); // <-- Group by is required here

            // Subquery: Total credit notes per client
            $creditNotesSub = DB::table('purchases as credit')
                ->select('credit.client_id', DB::raw('SUM(credit.amount_due) as total_credit_notes'))
                ->whereBetween(DB::raw('FROM_UNIXTIME(credit.date_invoiced)'), [$earlier, $today])
                ->where('credit.type', 2) // Credit Notes
                ->whereNull('credit.deleted_at')
                ->groupBy('credit.client_id');

           $debtors = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_number',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',

                    // Purchases + opening debits
                    DB::raw('(SUM(purchases.amount_due) + COALESCE(opening.opening_debit, 0)) as total_debits'),

                    // Payments + Petty Cash + opening credits
                    DB::raw('
                        (COALESCE(payments.total_credits, 0)
                        + COALESCE(cash.total_petty, 0)
                        + COALESCE(opening.opening_credit, 0)) as total_credits
                    '),

                    // Credit notes
                    DB::raw('COALESCE(credit_notes.total_credit_notes, 0) as total_credit_notes'),

                    // Net balance (Debits - Credits - Credit Notes)
                    DB::raw('
                        (SUM(purchases.amount_due)
                        + COALESCE(opening.opening_debit, 0))
                        - (COALESCE(payments.total_credits, 0)
                        + COALESCE(cash.total_petty, 0)
                        + COALESCE(opening.opening_credit, 0)
                        + COALESCE(credit_notes.total_credit_notes, 0))
                        as balance
                    ')
                    )
                    ->join('purchases', function ($join) use ($earlier, $today) {
                    $join->on('purchases.client_id', '=', 'client_accounts.client_account_id')
                        ->where('purchases.type', 1)
                        ->whereNull('purchases.deleted_at')
                        ->whereBetween(DB::raw('FROM_UNIXTIME(purchases.date_invoiced)'), [$earlier, $today]);

                })
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                // Subquery: payments
                ->leftJoinSub(
                    DB::table('payments')
                        ->select('client_id', DB::raw('SUM(amount_received) as total_credits'))
                        ->whereNull('deleted_at')
                        ->whereBetween(DB::raw('FROM_UNIXTIME(payments.date_received)'), [$earlier, $today])
                        ->groupBy('client_id'),
                    'payments',
                    function ($join) {
                        $join->on('payments.client_id', '=', 'client_accounts.client_account_id');
                    }
                )

                // Subquery: petty cash (treated as credit)
                ->leftJoinSub(
                    DB::table('petty_cashes')
                        ->select('ledger_id', DB::raw('SUM(amount) as total_petty'))
                        ->whereNull('deleted_at')
                        ->whereBetween(DB::raw('FROM_UNIXTIME(petty_cashes.date_invoiced)'), [$earlier, $today])
                        ->groupBy('ledger_id'),
                    'cash',
                    function ($join) {
                        $join->on('cash.ledger_id', '=', 'client_accounts.client_account_id');
                    }
                )

                // Subquery: credit notes
                ->leftJoinSub(
                    DB::table('purchases as credit')
                        ->select('client_id', DB::raw('SUM(amount_due) as total_credit_notes'))
                        ->where('type', 2)
                        ->whereNull('deleted_at')
                        ->whereBetween(DB::raw('FROM_UNIXTIME(credit.date_invoiced)'), [$earlier, $today])
                        ->groupBy('client_id'),
                    'credit_notes',
                    function ($join) {
                        $join->on('credit_notes.client_id', '=', 'client_accounts.client_account_id');
                    }
                )

                // Subquery: opening balances
                ->leftJoinSub(
                    DB::table('opening_balances')
                        ->select(
                            'client_id',
                            DB::raw('SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS opening_debit'),
                            DB::raw('SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS opening_credit')
                        )
                        ->whereNull('deleted_at')
                        ->whereBetween(DB::raw('FROM_UNIXTIME(opening_balances.date_invoiced)'), [$earlier, $today])
                        ->groupBy('client_id'),
                    'opening',
                    function ($join) {
                        $join->on('opening.client_id', '=', 'client_accounts.client_account_id');
                    }
                )

                ->whereNull('client_accounts.deleted_at')
                ->where('client_accounts.type', 8)

                ->groupBy(
                    'client_accounts.client_account_number',
                    'client_accounts.client_account_name',
                    'currencies.currency_symbol',
                    'payments.total_credits',
                    'cash.total_petty',
                    'credit_notes.total_credit_notes',
                    'opening.opening_debit',
                    'opening.opening_credit'
                )
                ->orderBy('client_accounts.client_account_name')
                ->get();


            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header row
            $headers = ['Account Number', 'Client Name', 'Currency', 'Debit', 'Credit', 'Balance'];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);

            // Data rows
            $row = 2;
            foreach ($debtors as $debtor) {

                $debit = number_format(($debtor->total_debits ?? 0) - ($debtor->total_credit_notes ?? 0), 2, '.', '');
                $credit = number_format($debtor->total_credits ?? 0, 2, '.', '');
                $balance = number_format($debit - $credit, 2, '.', '');

                $sheet->setCellValueExplicit("A{$row}", $debtor->client_account_number, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$row}", $debtor->client_name, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", $debtor->currency_symbol, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", $debit, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("E{$row}", $credit, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("F{$row}", $balance, DataType::TYPE_NUMERIC);

                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Export
            $writer = new Xlsx($spreadsheet);
            $filename = 'Creditors_List.xlsx';

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }
    }
    public function downloadAgingReport(Request $request, $id)
    {
        $today = Carbon::today()->format('Y-m-d');
        //        return $request->all();
        if (base64_decode($id) == 1) {
            // Subquery to get the sum of payments per invoice
            $subquery = DB::table('transaction_items')
                ->select('invoice_id', DB::raw('SUM(amount_settled) as total_payments'))
                ->whereNull('deleted_at')
                ->groupBy('invoice_id');

            // Subquery to calculate the sum of credit notes for each invoice
            $creditNotesSubquery = DB::table('invoices as credit')
                ->select(
                    'credit.inv_reference as inv_reference',
                    DB::raw('SUM(credit.amount_due) as total_credit_notes')
                )
                ->where('credit.type', 2) // Credit notes
                ->whereNull('credit.deleted_at')
                ->groupBy('credit.inv_reference');

            $data = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_id as client_id',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',
                    'client_accounts.currency_id',

                    // 0-30 Days
                    DB::raw("
            SUM(CASE
                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 30
                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                ELSE 0
            END) as amount_due_30_days
        "),

                    // 31-60 Days
                    DB::raw("
            SUM(CASE
                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 30
                AND DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 60
                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                ELSE 0
            END) as amount_due_60_days
        "),

                    // 61-90 Days
                    DB::raw("
            SUM(CASE
                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 60
                AND DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 90
                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                ELSE 0
            END) as amount_due_90_days
        "),

                    // 90+ Days
                    DB::raw("
            SUM(CASE
                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 90
                THEN ((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0))
                ELSE 0
            END) as amount_due_90_plus
        "),

                    // Total Amount Due
                    DB::raw("
            SUM((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as total_amount_due
        ")
                )
                ->join('invoices', 'client_accounts.client_account_id', '=', 'invoices.client_id')
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                // Join with the payments subquery
                ->leftJoinSub($subquery, 'payments', function ($join) {
                    $join->on('invoices.invoice_id', '=', 'payments.invoice_id');
                })

                // Join with the credit notes subquery
                ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                    $join->on('invoices.invoice_number', '=', 'credit_notes.inv_reference');
                })

                ->where('invoices.status', '!=', 1) // Exclude fully paid invoices
                ->where('invoices.type', 1) // Filter for specific invoice type
                ->whereNull('invoices.deleted_at')
                ->whereNull('client_accounts.deleted_at')

                ->groupBy(
                    'client_accounts.client_account_id',
                    'client_accounts.client_account_name',
                    'currencies.currency_symbol',
                    'currency_id'
                )
                ->orderBy('client_account_name');

            // Apply filters
            if ($request->currency) {
                $data->where('client_accounts.currency_id', $request->currency);
            }
            if ($request->client) {
                $data->where('client_accounts.client_account_id', $request->client);
            }

            $agingData = $data->get();
        } else {
            // Subquery to get the sum of payments per invoice
            $subquery = DB::table('payment_items')
                ->select('purchase_id', DB::raw('SUM(amount_settled) as total_payments'))
                ->whereNull('deleted_at')
                ->groupBy('purchase_id');

            $data = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_id as client_id',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',

                    // 0-30 Days
                    DB::raw("
                SUM(CASE
                    WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 30
                    THEN (purchases.amount_due - COALESCE(payments.total_payments, 0))
                    ELSE 0
                END) as amount_due_30_days
            "),

                    // 31-60 Days
                    DB::raw("
                SUM(CASE
                    WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 30
                    AND DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 60
                    THEN (purchases.amount_due - COALESCE(payments.total_payments, 0))
                    ELSE 0
                END) as amount_due_60_days
            "),

                    // 61-90 Days
                    DB::raw("
                SUM(CASE
                    WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 60
                    AND DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 90
                    THEN (purchases.amount_due - COALESCE(payments.total_payments, 0))
                    ELSE 0
                END) as amount_due_90_days
            "),

                    // 90+ Days
                    DB::raw("
                SUM(CASE
                    WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 90
                    THEN (purchases.amount_due - COALESCE(payments.total_payments, 0))
                    ELSE 0
                END) as amount_due_90_plus
            "),
                    // Total Amount Due
                    DB::raw("
            SUM(purchases.amount_due - COALESCE(payments.total_payments, 0)) as total_amount_due
        ")
                )
                ->join('purchases', 'client_accounts.client_account_id', '=', 'purchases.client_id')
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                // Join with the subquery to get the sum of partial payments
                ->leftJoinSub($subquery, 'payments', function ($join) {
                    $join->on('purchases.purchase_id', '=', 'payments.purchase_id');
                })
                ->where('purchases.status', '!=', 1) // Exclude fully paid invoices
                ->where('purchases.type', 1) // Filter for specific invoice type
                ->whereNull('purchases.deleted_at')
                ->whereNull('client_accounts.deleted_at')

                ->groupBy(
                    'client_accounts.client_account_id',
                    'client_accounts.client_account_name',
                    'currencies.currency_symbol'
                )
                ->orderBy('client_account_name');

            if ($request->currency) {
                $data->where('client_accounts.currency_id', $request->currency);
            }
            if ($request->client) {
                $data->where('client_accounts.client_account_id', $request->client);
            }
            $agingData = $data->get();
        }
        return Excel::download(new ClientsAgingAccounts($agingData, $id), 'AGING REPORT' . ' ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function updateTransactionsInvoices()
    {
        // Group transactions by client
        $transactions = Transaction::orderBy('date_received', 'asc')->get()->groupBy('client_id');

        foreach ($transactions as $clientId => $payments) {
            foreach ($payments as $payment) {
                // Track the remaining amount from the payment
                $amountRemaining = $payment->amount_received;

                // Fetch invoices for the client sorted by invoice date
                $invoices = Invoice::where([
                    'client_id' => $clientId,
                    'financial_year_id' => $payment->financial_year_id,
                    'type' => 1,
                ])
                    ->where('deleted_at', null)
                    ->orderBy('date_invoiced', 'asc')
                    ->get();

                // Loop through each invoice for the current client
                foreach ($invoices as $invoice) {
                    if ($amountRemaining <= 0) break; // Stop if no amount is left to settle

                    // Check the remaining balance of the invoice from existing transaction items
                    $totalSettled = TransactionItem::where('invoice_id', $invoice->invoice_id)
                        ->sum('amount_settled');
                    $remainingDue = $invoice->amount_due - $totalSettled;

                    // Skip fully settled invoices
                    if ($remainingDue <= 0) continue;

                    // Calculate the amount to settle for this invoice
                    $amountSettled = min($amountRemaining, $remainingDue);

                    // Create a new transaction item record
                    TransactionItem::create([
                        'transaction_item_id' => (new CustomIds())->generateId(),
                        'transaction_id' => $payment->transaction_id,
                        'invoice_id' => $invoice->invoice_id,
                        'amount_settled' => $amountSettled,
                    ]);

                    // Deduct the settled amount from the total amount remaining
                    $amountRemaining -= $amountSettled;

                    // Update invoice status based on remaining balance
                    if ($amountSettled < $remainingDue) {
                        $invoice->update(['status' => 2]); // Partially paid
                    } else {
                        $invoice->update(['status' => 1]); // Fully paid
                    }
                }
                $this->logger->create();
            }
            $this->logger->create();
        }

        return redirect()->back()->with('success', 'Success! Invoices updated and transactions processed.');
    }
    public function viewAgingInvoices($id)
    {
        list($clientId, $type) = explode(':', base64_decode($id));
        $today = Carbon::today()->format('Y-m-d');

        if ($type == 2) {
            // Subquery to get the sum of payments per purchase
            $subquery = DB::table('payment_items')
                ->select('purchase_id', DB::raw('SUM(amount_settled) as total_payments'))
                ->whereNull('deleted_at')
                ->groupBy('purchase_id');

            $agingData = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_id as client_id',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',
                    'purchases.purchase_id',
                    'purchases.invoice_number',
                    DB::raw('FROM_UNIXTIME(purchases.date_invoiced) as invoice_date'),
                    'purchases.amount_due',
                    DB::raw('COALESCE(payments.total_payments, 0) as total_payments'),
                    DB::raw('(purchases.amount_due - COALESCE(payments.total_payments, 0)) as outstanding_balance'),

                    // Aging Category
                    DB::raw("
            CASE
                WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 30 THEN '< 30 days'
                WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 30 AND DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 60 THEN '31-60 days'
                WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 60 AND DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 90 THEN '61-90 days'
                ELSE '> 90 days'
            END as aging_category
        ")
                )
                ->join('purchases', 'client_accounts.client_account_id', '=', 'purchases.client_id')
                ->leftJoin('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                // Join with the subquery to get the sum of partial payments
                ->leftJoinSub($subquery, 'payments', function ($join) {
                    $join->on('purchases.purchase_id', '=', 'payments.purchase_id');
                })
                ->where('purchases.status', '!=', 1) // Exclude fully paid purchases
                ->where('purchases.type', 1) // Filter for specific purchase type
                ->whereNull('purchases.deleted_at')
                ->whereNull('client_accounts.deleted_at')
                ->where('client_id', $clientId)
                ->orderBy('client_account_name')
                ->orderBy('invoice_date', 'asc')
                ->get();
        } else {

            // Subquery to get the sum of payments per invoice
            $subquery = DB::table('transaction_items')
                ->select('invoice_id', DB::raw('SUM(amount_settled) as total_payments'))
                ->whereNull('deleted_at')
                ->groupBy('invoice_id');

            // Subquery to calculate the sum of credit notes for each invoice
            $creditNotesSubquery = DB::table('invoices as credit')
                ->select(
                    'credit.inv_reference as inv_reference',
                    DB::raw('SUM(credit.amount_due) as total_credit_notes')
                )
                ->where('credit.type', 2) // Credit notes
                ->whereNull('credit.deleted_at')
                ->groupBy('credit.inv_reference');

            $agingData = DB::table('client_accounts')
                ->select(
                    'client_accounts.client_account_id as client_id',
                    'client_accounts.client_account_name as client_name',
                    'currencies.currency_symbol',
                    'invoices.invoice_id',
                    'invoices.invoice_number',
                    DB::raw('FROM_UNIXTIME(invoices.date_invoiced) as invoice_date'),

                    // Adjusted amount_due excluding credit notes
                    DB::raw('(invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) as amount_due'),

                    DB::raw('COALESCE(payments.total_payments, 0) as total_payments'),
                    DB::raw('COALESCE(credit_notes.total_credit_notes, 0) as total_credit_notes'),

                    // Outstanding balance now based on adjusted_amount_due
                    DB::raw('((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as outstanding_balance'),

                    // Aging Category
                    DB::raw("
                        CASE
                            WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 30 THEN '< 30 days'
                            WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 30 AND DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 60 THEN '31-60 days'
                            WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 60 AND DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 90 THEN '61-90 days'
                            ELSE '> 90 days'
                        END as aging_category
                    ")
                )
                ->join('invoices', 'client_accounts.client_account_id', '=', 'invoices.client_id')
                ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
                // Join with the payments subquery
                ->leftJoinSub($subquery, 'payments', function ($join) {
                    $join->on('invoices.invoice_id', '=', 'payments.invoice_id');
                })
                // Join with the credit notes subquery
                ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                    $join->on('invoices.invoice_number', '=', 'credit_notes.inv_reference');
                })
                ->where('invoices.status', '!=', 1) // Exclude fully paid invoices
                ->where('invoices.type', 1) // Filter for specific invoice type (non-credit notes)
                ->where('client_id', $clientId)
                ->whereNull('invoices.deleted_at')
                ->whereNull('client_accounts.deleted_at')
                ->orderBy('client_account_name')
                ->orderBy('invoice_date', 'asc')
                ->get();
        }
        return view('account::reports.aging.viewAgingInvoices')->with(['invoices' => $agingData, 'id' => $type]);
    }
    public function downloadAccountAgingReport(Request $request, $id)
    {
        list($clientId, $type) = explode(':', base64_decode($id));
        $today = Carbon::today()->format('Y-m-d');

        if ($type == 2) {
            if ($request->reportFilter == 1) {
                $subquery = DB::table('payment_items')
                    ->select('purchase_id', DB::raw('MAX(created_at) as first_payment_date'), DB::raw('SUM(amount_settled) as total_payments'))
                    ->whereNull('deleted_at')
                    ->groupBy('purchase_id');

                $creditNotesSubquery = DB::table('purchases as credit')
                    ->select('credit.inv_reference as inv_reference', DB::raw('MIN(date_invoiced) as credit_note_date'), DB::raw('SUM(credit.amount_due) as total_credit_notes'))
                    ->where('credit.type', 2)
                    ->whereNull('credit.deleted_at')
                    ->groupBy('credit.inv_reference');

                $invoiceItemSubquery = DB::table('purchase_items as pi1')
                    ->select('pi1.purchase_id', 'pi1.ledger_id')
                    ->whereColumn('pi1.unit_price', '=', DB::raw("(SELECT pi2.unit_price FROM purchase_items as pi2 WHERE pi2.purchase_id = pi1.purchase_id ORDER BY pi2.unit_price * pi2.quantity DESC LIMIT 1)"))
                    ->whereColumn('pi1.quantity', '=', DB::raw("(SELECT pi2.quantity FROM purchase_items as pi2 WHERE pi2.purchase_id = pi1.purchase_id ORDER BY pi2.unit_price * pi2.quantity DESC LIMIT 1)"));

                $agingData = DB::table('purchases')
                    ->select(
                        'client_accounts.client_account_id as client_id',
                        'client_accounts.client_account_name as client_name',
                        'currencies.currency_symbol',
                        'purchases.purchase_id',
                        'purchases.voucher_number as invoice_number',
                        'purchases.invoice_number as si_number',
                        DB::raw('DATE(FROM_UNIXTIME(purchases.date_invoiced)) as invoice_date'),
                        DB::raw('(purchases.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) as amount_due'),
                        DB::raw('COALESCE(payments.total_payments, 0) as total_payments'),
                        DB::raw('((purchases.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as outstanding_balance'),
                        DB::raw("
                            CASE
                                WHEN payments.first_payment_date IS NOT NULL THEN payments.first_payment_date ELSE DATE(FROM_UNIXTIME(credit_notes.credit_note_date)) END as aging_category
                        "),
                        'ledgers.client_account_name as ledger_name'
                    )
                    ->join('client_accounts', 'purchases.client_id', '=', 'client_accounts.client_account_id')
                    ->join('currencies', 'client_accounts.currency_id', '=', 'currencies.currency_id')
                    ->leftJoinSub($subquery, 'payments', function ($join) {
                        $join->on('purchases.purchase_id', '=', 'payments.purchase_id');
                    })
                    ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                        $join->on('purchases.voucher_number', '=', 'credit_notes.inv_reference');
                    })
                    ->leftJoinSub($invoiceItemSubquery, 'first_invoice_item', function ($join) {
                        $join->on('purchases.purchase_id', '=', 'first_invoice_item.purchase_id');
                    })
                    ->join('client_accounts as ledgers', 'ledgers.client_account_id', '=', 'first_invoice_item.ledger_id')
                    ->where('purchases.status', 1)
                    ->where('purchases.type', 1)
                    ->where('purchases.client_id', $clientId)
                    ->whereNull('purchases.deleted_at')
                    ->whereNull('client_accounts.deleted_at')
                    ->orderBy('client_accounts.client_account_name')
                    ->orderBy('invoice_date', 'asc');

                $data = $agingData->get();
            } else {
                // Subquery to get the sum of payments per invoice
                $subquery = DB::table('payment_items')
                    ->select('purchase_id', DB::raw('SUM(amount_settled) as total_payments'))
                    ->whereNull('deleted_at')
                    ->groupBy('purchase_id');

                // Subquery to calculate the sum of credit notes for each invoice
                $creditNotesSubquery = DB::table('purchases as credit')
                    ->select(
                        'credit.inv_reference as inv_reference',
                        DB::raw('SUM(credit.amount_due) as total_credit_notes')
                    )
                    ->where('credit.type', 2) // Credit notes
                    ->whereNull('credit.deleted_at')
                    ->groupBy('credit.inv_reference');

                $invoiceItemSubquery = DB::table('purchase_items as pi1')
                    ->select('pi1.purchase_id', 'pi1.ledger_id')
                    ->whereColumn('pi1.unit_price', '=', DB::raw("(SELECT pi2.unit_price FROM purchase_items as pi2 WHERE pi2.purchase_id = pi1.purchase_id ORDER BY pi2.unit_price * pi2.quantity DESC LIMIT 1)"))
                    ->whereColumn('pi1.quantity', '=', DB::raw("(SELECT pi2.quantity FROM purchase_items as pi2 WHERE pi2.purchase_id = pi1.purchase_id ORDER BY pi2.unit_price * pi2.quantity DESC LIMIT 1)"));

                $agingData = DB::table('client_accounts')
                    ->select(
                        'client_accounts.client_account_id as client_id',
                        'client_accounts.client_account_name as client_name',
                        'currencies.currency_symbol',
                        'purchases.purchase_id',
                        'purchases.voucher_number as invoice_number',
                        'purchases.invoice_number as si_number',
                        DB::raw('FROM_UNIXTIME(purchases.date_invoiced) as invoice_date'),
                        // Adjusted amount_due excluding credit notes
                        DB::raw('(purchases.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) as amount_due'),
                        DB::raw('COALESCE(payments.total_payments, 0) as total_payments'),
                        // Outstanding balance now based on adjusted_amount_due
                        DB::raw('((purchases.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as outstanding_balance'),
                        // Aging Category
                        DB::raw("
                            CASE
                                WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 30 THEN '< 30 days'
                                WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 30 AND DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 60 THEN '31-60 days'
                                WHEN DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) > 60 AND DATEDIFF('$today', FROM_UNIXTIME(purchases.date_invoiced)) <= 90 THEN '61-90 days'
                                ELSE '> 90 days'
                            END as aging_category
                        "),
                        'ledgers.client_account_name as ledger_name'
                    )
                    ->join('purchases', 'client_accounts.client_account_id', '=', 'purchases.client_id')
                    ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                    // Join with the payments subquery
                    ->leftJoinSub($subquery, 'payments', function ($join) {
                        $join->on('purchases.purchase_id', '=', 'payments.purchase_id');
                    })

                    // Join with the credit notes subquery
                    ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                        $join->on('purchases.voucher_number', '=', 'credit_notes.inv_reference');
                    })
                    ->leftJoinSub($invoiceItemSubquery, 'first_invoice_item', function ($join) {
                        $join->on('purchases.purchase_id', '=', 'first_invoice_item.purchase_id');
                    })
                    ->join('client_accounts as ledgers', 'ledgers.client_account_id', '=', 'first_invoice_item.ledger_id')
                    ->where('purchases.status', '!=', 1) // Exclude fully paid invoices
                    ->where('purchases.type', 1) // Filter for specific invoice type
                    ->where('client_id', $clientId)
                    ->whereNull('purchases.deleted_at')
                    ->whereNull('client_accounts.deleted_at')
                    //                    ->orderBy('client_account_name')
                    ->orderBy('invoice_date', 'asc');

                // Filter by aging period if provided
                if ($request->period) {
                    $agingData->having('aging_category', '=', $request->period);
                }

                if ($request->reportFilter) {
                    $status = $request->reportFilter == 2 ? [2] : ($request->reportFilter == 3 ? [null, 0] : [null, 0, 2]);
                    $agingData->whereIn('purchases.status', $status);
                }

                $data = $agingData->get();
            }
        } else {
            if ($request->reportFilter == 1) {

                $subquery = DB::table('transaction_items')
                    ->select('invoice_id', DB::raw('MAX(created_at) as first_payment_date'), DB::raw('SUM(amount_settled) as total_payments'))
                    ->whereNull('deleted_at')
                    ->groupBy('invoice_id');

                $creditNotesSubquery = DB::table('invoices as credit')
                    ->select('credit.inv_reference as inv_reference', DB::raw('MIN(date_invoiced) as credit_note_date'), DB::raw('SUM(credit.amount_due) as total_credit_notes'))
                    ->where('credit.type', 2)
                    ->whereNull('credit.deleted_at')
                    ->groupBy('credit.inv_reference');

                $invoiceItemSubquery = DB::table('invoice_items as ii1')
                    ->select('ii1.invoice_id', 'ii1.ledger_id')
                    ->whereColumn('ii1.unit_price', '=', DB::raw("(SELECT ii2.unit_price FROM invoice_items as ii2 WHERE ii2.invoice_id = ii1.invoice_id ORDER BY ii2.unit_price * ii2.quantity DESC LIMIT 1)"))
                    ->whereColumn('ii1.quantity', '=', DB::raw("(SELECT ii2.quantity FROM invoice_items as ii2 WHERE ii2.invoice_id = ii1.invoice_id ORDER BY ii2.unit_price * ii2.quantity DESC LIMIT 1)"));


                $agingData = DB::table('invoices')
                    ->select(
                        'client_accounts.client_account_id as client_id',
                        'client_accounts.client_account_name as client_name',
                        'currencies.currency_symbol',
                        'invoices.invoice_id',
                        'invoices.invoice_number',
                        'invoices.si_number',
                        DB::raw('DATE(FROM_UNIXTIME(invoices.date_invoiced)) as invoice_date'),
                        DB::raw('(invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) as amount_due'),
                        DB::raw('COALESCE(payments.total_payments, 0) as total_payments'),
                        DB::raw('((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as outstanding_balance'),
                        DB::raw("
                            CASE
                                WHEN payments.first_payment_date IS NOT NULL THEN payments.first_payment_date ELSE DATE(FROM_UNIXTIME(credit_notes.credit_note_date)) END as aging_category
                        "),
                        'ledgers.client_account_name as ledger_name'
                    )
                    ->join('client_accounts', 'invoices.client_id', '=', 'client_accounts.client_account_id')
                    ->join('currencies', 'client_accounts.currency_id', '=', 'currencies.currency_id')
                    ->leftJoinSub($subquery, 'payments', function ($join) {
                        $join->on('invoices.invoice_id', '=', 'payments.invoice_id');
                    })
                    ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                        $join->on('invoices.invoice_number', '=', 'credit_notes.inv_reference');
                    })
                    ->leftJoinSub($invoiceItemSubquery, 'first_invoice_item', function ($join) {
                        $join->on('invoices.invoice_id', '=', 'first_invoice_item.invoice_id');
                    })
                    ->join('client_accounts as ledgers', 'ledgers.client_account_id', '=', 'first_invoice_item.ledger_id')
                    ->where('invoices.status', 1)
                    ->where('invoices.type', 1)
                    ->where('invoices.client_id', $clientId)
                    ->whereNull('invoices.deleted_at')
                    ->whereNull('client_accounts.deleted_at')
                    ->orderBy('client_accounts.client_account_name')
                    ->orderBy('invoice_date', 'asc');

                $data = $agingData->get();
            } else {
                // Subquery to get the sum of payments per invoice
                $subquery = DB::table('transaction_items')
                    ->select('invoice_id', DB::raw('SUM(amount_settled) as total_payments'))
                    ->whereNull('deleted_at')
                    ->groupBy('invoice_id');

                // Subquery to calculate the sum of credit notes for each invoice
                $creditNotesSubquery = DB::table('invoices as credit')
                    ->select(
                        'credit.inv_reference as inv_reference',
                        DB::raw('SUM(credit.amount_due) as total_credit_notes')
                    )
                    ->where('credit.type', 2) // Credit notes
                    ->whereNull('credit.deleted_at')
                    ->groupBy('credit.inv_reference');

                $invoiceItemSubquery = DB::table('invoice_items as ii1')
                    ->select('ii1.invoice_id', 'ii1.ledger_id')
                    ->whereColumn('ii1.unit_price', '=', DB::raw("(SELECT ii2.unit_price FROM invoice_items as ii2 WHERE ii2.invoice_id = ii1.invoice_id ORDER BY ii2.unit_price * ii2.quantity DESC LIMIT 1)"))
                    ->whereColumn('ii1.quantity', '=', DB::raw("(SELECT ii2.quantity FROM invoice_items as ii2 WHERE ii2.invoice_id = ii1.invoice_id ORDER BY ii2.unit_price * ii2.quantity DESC LIMIT 1)"));

                $agingData = DB::table('client_accounts')
                    ->select(
                        'client_accounts.client_account_id as client_id',
                        'client_accounts.client_account_name as client_name',
                        'currencies.currency_symbol',
                        'invoices.invoice_id',
                        'invoices.invoice_number',
                        'si_number as si_number',
                        DB::raw('FROM_UNIXTIME(invoices.date_invoiced) as invoice_date'),
                        // Adjusted amount_due excluding credit notes
                        DB::raw('(invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) as amount_due'),
                        DB::raw('COALESCE(payments.total_payments, 0) as total_payments'),
                        // Outstanding balance now based on adjusted_amount_due
                        DB::raw('((invoices.amount_due - COALESCE(credit_notes.total_credit_notes, 0)) - COALESCE(payments.total_payments, 0)) as outstanding_balance'),
                        // Aging Category
                        DB::raw("
                            CASE
                                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 30 THEN '< 30 days'
                                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 30 AND DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 60 THEN '31-60 days'
                                WHEN DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) > 60 AND DATEDIFF('$today', FROM_UNIXTIME(invoices.date_invoiced)) <= 90 THEN '61-90 days'
                                ELSE '> 90 days'
                            END as aging_category
                        "),
                        'ledgers.client_account_name as ledger_name'
                    )
                    ->join('invoices', 'client_accounts.client_account_id', '=', 'invoices.client_id')
                    ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')

                    // Join with the payments subquery
                    ->leftJoinSub($subquery, 'payments', function ($join) {
                        $join->on('invoices.invoice_id', '=', 'payments.invoice_id');
                    })

                    // Join with the credit notes subquery
                    ->leftJoinSub($creditNotesSubquery, 'credit_notes', function ($join) {
                        $join->on('invoices.invoice_number', '=', 'credit_notes.inv_reference');
                    })
                    ->leftJoinSub($invoiceItemSubquery, 'first_invoice_item', function ($join) {
                        $join->on('invoices.invoice_id', '=', 'first_invoice_item.invoice_id');
                    })
                    ->join('client_accounts as ledgers', 'ledgers.client_account_id', '=', 'first_invoice_item.ledger_id')
                    ->where('invoices.status', '!=', 1) // Exclude fully paid invoices
                    ->where('invoices.type', 1) // Filter for specific invoice type
                    ->where('client_id', $clientId)
                    ->whereNull('invoices.deleted_at')
                    ->whereNull('client_accounts.deleted_at')
                    //                    ->orderBy('client_account_name')
                    ->orderBy('invoice_date', 'asc');

                // Filter by aging period if provided
                if ($request->period) {
                    $agingData->having('aging_category', '=', $request->period);
                }

                if ($request->reportFilter) {
                    $status = $request->reportFilter == 2 ? [2] : ($request->reportFilter == 3 ? [null, 0] : [null, 0, 2]);
                    $agingData->whereIn('invoices.status', $status);
                }

                $data = $agingData->get();
            }
        }
        return Excel::download(new ClientAccountAgingReport($data), 'AGING REPORT' . ' ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function viewSystemJournals()
    {
        $debtors = ClientAccount::where(['account_status' => 1])->orderBy('client_account_name')->get();
        $journals = AdjustmentJournal::join('client_accounts', 'client_accounts.client_account_id', '=', 'adjustment_journals.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'adjustment_journals.*',
                'client_accounts.client_account_name as ledger_name',
                'client_accounts.currency_id',
                'currencies.priority',
                DB::raw('CASE WHEN adjustment_journals.type = 1 THEN amount END as debit'),
                DB::raw('CASE WHEN adjustment_journals.type = 2 THEN amount END as credit')
            )
            ->orderBy('adjustment_journals.date_adjusted', 'desc')
            ->orderBy('adjustment_journals.reference_code', 'desc')
            ->orderBy('adjustment_journals.type')
            ->orderBy('client_accounts.client_account_name')
            ->get();
        return view('account::journals.index')->with(['debtors' => $debtors, 'journals' => $journals]);
    }
    public function fetchCreditAccount(Request $request)
    {
        $debtor = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->where('client_account_id', $request->clientId)
            ->where(['account_status' => 1])
            ->select('chart_name', 'currency_name', 'client_account_number', 'sub_account_name', 'client_accounts.currency_id')
            ->first();

        $creditors = ClientAccount::whereNull('client_accounts.deleted_at')->where(['account_status' => 1])
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->get();
        return response()->json([
            'debtor' => $debtor,
            'creditors' => $creditors
        ]);
    }
    public function getCreditAccount(Request $request)
    {
        $creditor = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->where('client_account_id', $request->clientId)
            ->where(['account_status' => 1])
            //                    ->where('client_accounts.updated_at', '>=', '2025-01-25 15:45:31')
            ->select('chart_name', 'currency_name', 'client_account_number', 'sub_account_name', 'currencies.currency_id')
            ->first();

        return response()->json($creditor);
    }
    public function storeSystemJournals(Request $request)
    {
        DB::beginTransaction();
        try {
            $entries = [];
            // Process debits
            foreach ($request->input('debits', []) as $debitId => $debitDetails) {
                $entries[] = [
                    'adjustment_journal_id' => (new CustomIds())->generateId(),
                    'reference_code' => AdjustmentJournal::newReferenceCode(),
                    'ledger_id' => $debitDetails['account_id'],
                    'type' => 1, // Debit
                    'amount' => $debitDetails['amount'],
                    'description' => $request->description,
                    'exchange_rate' => $debitDetails['exchange_rate'] ?? 1,
                    'date_adjusted' => strtotime($request->date_adjusted),
                    'status' => 1,
                    'user_id' => auth()->user()->user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            // Process credits
            foreach ($request->input('credits', []) as $creditId => $creditDetails) {
                $entries[] = [
                    'adjustment_journal_id' => (new CustomIds())->generateId(),
                    'reference_code' => AdjustmentJournal::newReferenceCode(),
                    'ledger_id' => $creditDetails['account_id'],
                    'type' => 2, // Credit
                    'amount' => $creditDetails['amount'],
                    'description' => $request->description,
                    'exchange_rate' => $creditDetails['exchange_rate'] ?? 1,
                    'date_adjusted' => strtotime($request->date_adjusted),
                    'status' => 1,
                    'user_id' => auth()->user()->user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            // Optional: Save to database
            AdjustmentJournal::insert($entries);
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success!, Journal Created Successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'System is unable to process the transaction at the moment ' . $e->getMessage());
        }
    }
    public function viewBanks()
    {
        $banks = ChartOfAccount::join('client_accounts', 'client_accounts.chart_id', '=', 'chart_of_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->where(['chart_number' => '1101', 'client_accounts.account_status' => 1])
            ->whereNull('client_accounts.deleted_at')
            ->orderBy('client_account_number')
            ->get();
        return view('account::banks.index')->with(['banks' => $banks]);
    }
    public function viewBankStatement($id)
    {
        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select('transactions.transaction_id as transaction_id', 'client_accounts.client_account_id', 'transactions.date_received', 'transactions.amount_received', 'transactions.description', DB::raw("'DEBIT' as type"), 'client_account_name', 'invoice_number', 'bank_date', 'reconciled')
            ->whereNull('transactions.deleted_at')
            ->orderBy('date_received');

        $journals = DB::table('adjustment_journals as aj1')
            ->join('adjustment_journals as aj2', function ($join) {
                $join->on('aj1.reference_code', '=', 'aj2.reference_code') // Match by journal number
                    ->whereRaw('aj1.type != aj2.type'); // Ensure opposite types (debit and credit)
            })
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'aj2.ledger_id')
            ->where(['aj1.ledger_id' => $id, 'aj1.reconciled' => 0])
            ->select('aj1.adjustment_journal_id as transaction_id', 'client_accounts.client_account_id', 'aj1.date_adjusted as date_received', 'aj1.amount as amount_received', 'aj1.description', DB::raw("CASE WHEN aj1.type = 1 THEN 'DEBIT' ELSE 'CREDIT' END AS type"), 'client_account_name', 'aj1.reference_code as invoice_number', 'aj1.bank_date', 'aj1.reconciled')
            ->whereNull('aj1.deleted_at')
            ->whereNull('aj2.deleted_at')
            ->orderBy('date_received');

        // Step 1: Subquery to find the highest payment's client per transaction_code
        $highestPaymentSubquery = DB::table('payments')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select(
                'payments.transaction_code',
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'payments.amount_received',
                // Rank payments per transaction_code (highest amount first, then alphabetical)
                DB::raw('ROW_NUMBER() OVER (PARTITION BY payments.transaction_code ORDER BY payments.amount_received DESC, client_accounts.client_account_name ASC) as payment_rank')
            );

        // Step 2: Filter to only the top-ranked payment per transaction_code
        $topClients = DB::table(DB::raw("({$highestPaymentSubquery->toSql()}) as ranked_payments"))
            ->mergeBindings($highestPaymentSubquery)
            ->where('payment_rank', 1)
            ->select(
                'transaction_code',
                'client_account_id',
                'client_account_name'
            );

        // Step 3: Main query - sum amounts and join with selected clients
        $payments = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->joinSub($topClients, 'top_clients', function ($join) {
                $join->on('payments.transaction_code', '=', 'top_clients.transaction_code');
            })
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select(
                'payments.transaction_code as transaction_id',
                'top_clients.client_account_id',
                DB::raw('MAX(payments.date_received) as date_received'),
                DB::raw('SUM(payments.amount_received) as amount_received'),
                DB::raw('MAX(payments.description) as description'),
                DB::raw("'CREDIT' as type"),
                'top_clients.client_account_name',
                DB::raw('MAX(payments.transaction_code) as invoice_number'),
                DB::raw('MAX(payments.bank_date) as bank_date'),
                DB::raw('MAX(payments.reconciled) as reconciled')
            )
            ->groupBy(
                'payments.transaction_code',
                'top_clients.client_account_id',
                'top_clients.client_account_name'
            )
            ->whereNull('payments.deleted_at')
            ->orderBy('amount_received', 'DESC');

        // Combine the queries
        $statements = $transactions->union($payments)->union($journals)->orderBy('date_received')->get();

        $bank = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where(['client_account_id' => $id])->first();
        return view('account::banks.bankStatement')->with(['statements' => $statements, 'bank' => $bank]);
    }
    public function exportUnreconciledTransactions(Request $request, $id)
    {
        $dateFrom = $request->from ? Carbon::parse($request->from)->startOfDay()->timestamp : null;
        $dateTo   = $request->to ? Carbon::parse($request->to)->endOfDay()->timestamp : null;

        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select('transactions.transaction_id as transaction_id', 'client_accounts.client_account_id', 'transactions.date_received', 'transactions.amount_received', 'transactions.description', DB::raw("'DEBIT' as type"), 'client_account_name', 'invoice_number', 'bank_date', 'reconciled')
            ->whereNull('transactions.deleted_at')
            ->orderBy('date_received');

        $journals = DB::table('adjustment_journals as aj1')
            ->join('adjustment_journals as aj2', function ($join) {
                $join->on('aj1.reference_code', '=', 'aj2.reference_code') // Match by journal number
                    ->whereRaw('aj1.type != aj2.type'); // Ensure opposite types (debit and credit)
            })
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'aj2.ledger_id')
            ->where(['aj1.ledger_id' => $id, 'aj1.reconciled' => 0])
            ->select('aj1.adjustment_journal_id as transaction_id', 'client_accounts.client_account_id', 'aj1.date_adjusted as date_received', 'aj1.amount as amount_received', 'aj1.description', DB::raw("CASE WHEN aj1.type = 1 THEN 'DEBIT' ELSE 'CREDIT' END AS type"), 'client_account_name', 'aj1.reference_code as invoice_number', 'aj1.bank_date', 'aj1.reconciled')
            ->whereNull('aj1.deleted_at')
            ->whereNull('aj2.deleted_at')
            ->orderBy('date_received');

        // Step 1: Subquery to find the highest payment's client per transaction_code
        $highestPaymentSubquery = DB::table('payments')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select(
                'payments.transaction_code',
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'payments.amount_received',
                // Rank payments per transaction_code (highest amount first, then alphabetical)
                DB::raw('ROW_NUMBER() OVER (PARTITION BY payments.transaction_code ORDER BY payments.amount_received DESC, client_accounts.client_account_name ASC) as payment_rank')
            );

        // Step 2: Filter to only the top-ranked payment per transaction_code
        $topClients = DB::table(DB::raw("({$highestPaymentSubquery->toSql()}) as ranked_payments"))
            ->mergeBindings($highestPaymentSubquery)
            ->where('payment_rank', 1)
            ->select(
                'transaction_code',
                'client_account_id',
                'client_account_name'
            );

        // Step 3: Main query - sum amounts and join with selected clients
        $payments = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->joinSub($topClients, 'top_clients', function ($join) {
                $join->on('payments.transaction_code', '=', 'top_clients.transaction_code');
            })
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select(
                'payments.transaction_code as transaction_id',
                'top_clients.client_account_id',
                DB::raw('MAX(payments.date_received) as date_received'),
                DB::raw('SUM(payments.amount_received) as amount_received'),
                DB::raw('MAX(payments.description) as description'),
                DB::raw("'CREDIT' as type"),
                'top_clients.client_account_name',
                DB::raw('MAX(payments.transaction_code) as invoice_number'),
                DB::raw('MAX(payments.bank_date) as bank_date'),
                DB::raw('MAX(payments.reconciled) as reconciled')
            )
            ->groupBy(
                'payments.transaction_code',
                'top_clients.client_account_id',
                'top_clients.client_account_name'
            )
            ->whereNull('payments.deleted_at')
            ->orderBy('amount_received', 'DESC');

        // Combine them using UNION
        $union = $transactions->union($payments)->union($journals);
        // Wrap the union in a subquery
        $query = DB::query()->fromSub($union, 'statements');
        // Apply filters on the wrapped union
        if ($dateFrom !== null) {
            $query->where('date_received', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where('date_received', '<=', $dateTo);
        }
        // Order the result
        $records = $query->orderBy('date_received')->get();
        $bank = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where(['client_account_id' => $id])->first();
        return Excel::download(new ExportUnreconciledTransactions($records, $bank), 'UNRECONCILED TRANSACTIONS_ ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function viewReconciledBanks()
    {
        $banks = ChartOfAccount::join('client_accounts', 'client_accounts.chart_id', '=', 'chart_of_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->where(['chart_number' => '1101', 'client_accounts.account_status' => 1])
            ->whereNull('client_accounts.deleted_at')
            ->orderBy('client_account_number')
            ->get();
        return view('account::banks.viewReconciledBanks')->with(['banks' => $banks]);
    }
    public function viewReconciledBankStatement($id)
    {
        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->whereNotNull('bank_date')
            ->select('transactions.transaction_id as transaction_id', 'client_accounts.client_account_id', 'transactions.date_received', 'transactions.amount_received', 'transactions.description', DB::raw("'DEBIT' as type"), 'client_account_name', 'invoice_number', 'bank_date', 'reconciled')
            ->whereNull('transactions.deleted_at')
            ->orderBy('date_received');

        $journals = DB::table('adjustment_journals as aj1')
            ->join('adjustment_journals as aj2', function ($join) {
                $join->on('aj1.reference_code', '=', 'aj2.reference_code') // Match by journal number
                    ->whereRaw('aj1.type != aj2.type'); // Ensure opposite types (debit and credit)
            })
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'aj2.ledger_id')
            ->where(['aj1.ledger_id' => $id, 'aj1.reconciled' => 1])
            ->select('aj1.adjustment_journal_id as transaction_id', 'client_accounts.client_account_id', 'aj1.date_adjusted as date_received', 'aj1.amount as amount_received', 'aj1.description', DB::raw("CASE WHEN aj1.type = 1 THEN 'DEBIT' ELSE 'CREDIT' END AS type"), 'client_account_name', 'aj1.reference_code as invoice_number', 'aj1.bank_date', 'aj1.reconciled')
            ->whereNull('aj1.deleted_at')
            ->whereNull('aj2.deleted_at')
            ->orderBy('date_received');

        // Step 1: Subquery to find the highest payment's client per transaction_code
        $highestPaymentSubquery = DB::table('payments')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->select(
                'payments.transaction_code',
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'payments.amount_received',
                // Rank payments per transaction_code (highest amount first, then alphabetical)
                DB::raw('ROW_NUMBER() OVER (PARTITION BY payments.transaction_code ORDER BY payments.amount_received DESC, client_accounts.client_account_name ASC) as payment_rank')
            );

        // Step 2: Filter to only the top-ranked payment per transaction_code
        $topClients = DB::table(DB::raw("({$highestPaymentSubquery->toSql()}) as ranked_payments"))
            ->mergeBindings($highestPaymentSubquery)
            ->where('payment_rank', 1)
            ->select(
                'transaction_code',
                'client_account_id',
                'client_account_name'
            );

        // Step 3: Main query - sum amounts and join with selected clients
        $payments = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->joinSub($topClients, 'top_clients', function ($join) {
                $join->on('payments.transaction_code', '=', 'top_clients.transaction_code');
            })
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->select(
                'payments.transaction_code as transaction_id',
                'top_clients.client_account_id',
                DB::raw('MAX(payments.date_received) as date_received'),
                DB::raw('SUM(payments.amount_received) as amount_received'),
                DB::raw('MAX(payments.description) as description'),
                DB::raw("'CREDIT' as type"),
                'top_clients.client_account_name',
                DB::raw('MAX(payments.transaction_code) as invoice_number'),
                DB::raw('MAX(payments.bank_date) as bank_date'),
                DB::raw('MAX(payments.reconciled) as reconciled')
            )
            ->groupBy(
                'payments.transaction_code',
                'top_clients.client_account_id',
                'top_clients.client_account_name'
            )
            ->whereNull('payments.deleted_at')
            ->orderBy('amount_received', 'DESC');

        // Combine the queries
        $statements = $transactions->union($payments)->union($journals)->orderBy('date_received')->get();

        $months = DB::table('transactions')
            ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(date_received), '%Y-%m') as month_year")
            ->where('account_id', $id)
            ->union(
                DB::table('payments')
                    ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(date_received), '%Y-%m') as month_year")
                    ->where('account_id', $id)
            )
            ->distinct() // Ensure unique months
            ->orderBy('month_year') // Order by year and month correctly
            ->get()
            ->map(function ($item) {
                return date('M Y', strtotime($item->month_year . '-01')); // Format to 'Jan 2024'
            })
            ->toArray();

        $bank = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where(['client_account_id' => $id])->first();
        return view('account::banks.viewReconciledBankStatement')->with(['statements' => $statements, 'bank' => $bank, 'months' => $months]);
    }
    public function updateBankDate(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'date' => 'nullable|date'
        ]);
        // Find the record and update the date
        if ($request->date == '' || $request->date == null) {
            if (Transaction::where(['transaction_id' => $request->id])->exists()) {
                Transaction::where(['transaction_id' => $request->id])->update(['bank_date' => null, 'reconciled' => 0, 'status' => 0]);
            } elseif (Payment::where(['transaction_code' => $request->id])->exists()) {
                Payment::where(['transaction_code' => $request->id])->update(['bank_date' => null, 'reconciled' => 0, 'status' => 0]);
            } elseif (AdjustmentJournal::where(['adjustment_journal_id' => $request->id])->exists()) {
                AdjustmentJournal::where(['adjustment_journal_id' => $request->id])->update(['bank_date' => null, 'reconciled' => 0]);
            }
        } else {
            if (Transaction::where(['transaction_id' => $request->id])->exists()) {
                Transaction::where(['transaction_id' => $request->id])->update(['bank_date' => strtotime($request->date)]);
            } elseif (Payment::where(['transaction_code' => $request->id])->exists()) {
                Payment::where(['transaction_code' => $request->id])->update(['bank_date' => strtotime($request->date)]);
            } elseif (AdjustmentJournal::where(['adjustment_journal_id' => $request->id])->exists()) {
                AdjustmentJournal::where(['adjustment_journal_id' => $request->id])->update(['bank_date' => strtotime($request->date)]);
            }
        }
        $this->logger->create();
        return response()->json(['message' => 'Date updated successfully!'], 200);
    }
    public function reconcileBankStatement()
    {
        Transaction::whereNotNull('bank_date')->where(['reconciled' => 0])->update(['reconciled' => 1, 'status' => 1]);
        Payment::whereNotNull('bank_date')->where(['reconciled' => 0])->update(['reconciled' => 1, 'status' => 1]);
        AdjustmentJournal::whereNotNull('bank_date')->where(['reconciled' => 0])->update(['reconciled' => 1, 'status' => 1]);
        $this->logger->create();
        return redirect()->route('accounts.viewBanks')->with('success', 'Success! Bank Reconciliation Successful');
    }
    public function getInvoiceDetails(Request $request)
    {
        $transaction = Transaction::find($request->transactionId);
        $invoice = Invoice::leftJoin('invoices as credit_notes', function ($join) {
            $join->on('credit_notes.inv_reference', '=', 'invoices.invoice_number')
                ->where('credit_notes.type', 2);
        })
            ->select(DB::raw('invoices.amount_due - IFNULL(credit_notes.amount_due, 0) as amount_due'))
            ->where('invoices.invoice_id', $request->invoiceId)
            ->first();

        if (!$transaction || !$invoice) {
            return response()->json(['success' => false, 'message' => 'Transaction or Invoice not found.']);
        }

        // Calculate the unused balance from the transaction
        $unusedBalance = $transaction->amount_received - TransactionItem::where(['transaction_id' => $transaction->transaction_id, 'type' => 1])->sum('amount_settled');

        return response()->json([
            'success' => true,
            'unused_balance' => $unusedBalance,
            'amount_due' => $invoice->amount_due,
        ]);
    }
    public function processTransaction(Request $request)
    {
        $request->validate([
            'form_data' => ['required', 'json', function ($attribute, $value, $fail) {
                if (empty(json_decode($value, true))) {
                    $fail('The selected items in form_data are required.');
                }
            }],
        ]);

        $paymentTypes = json_decode($request->form_data);
        // Retrieve the transaction and invoice
        $transaction = Transaction::find($request->transactionId);
        foreach ($paymentTypes as $invoiceId => $paymentType) {
            $invoice = Invoice::query()
                ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
                ->leftJoin('invoice_items', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
                ->leftJoin('invoices as credit_notes', function ($join) {
                    $join->on('credit_notes.inv_reference', '=', 'invoices.invoice_number')
                        ->where('credit_notes.type', 2);
                })
                ->leftJoin('invoice_items as credit_note_items', 'credit_notes.invoice_id', '=', 'credit_note_items.invoice_id')
                ->leftJoin('transaction_items', function ($join) {
                    $join->on('transaction_items.invoice_id', '=', 'invoices.invoice_id')
                        ->whereNull('transaction_items.deleted_at');
                })
                ->where('invoices.invoice_id', $invoiceId)
                ->select([
                    'invoices.invoice_id',
                    'invoices.invoice_number',
                    'invoices.client_id',
                    'invoices.date_invoiced',
                    'financial_years.year_starting',
                    'financial_years.year_ending',
                    DB::raw('invoices.amount_due - IFNULL(credit_notes.amount_due, 0) as amount_due'),
                    DB::raw('SUM(transaction_items.amount_settled) as amount_settled'),
                    DB::raw('SUM(CASE WHEN invoice_items.tax_id IS NOT NULL THEN (invoice_items.unit_price * invoice_items.quantity) * 0.02 ELSE 0 END) as total_tax')
                ])
                ->where(['invoices.type' => 1])
                ->whereNot('invoices.status', 1)
                ->whereNull('invoices.deleted_at')
                ->whereNull('invoice_items.deleted_at')
                ->where(function ($query) {
                    $query->whereNull('credit_notes.deleted_at')->orWhereNull('credit_notes.invoice_id');
                })
                ->where(function ($query) {
                    $query->whereNull('credit_note_items.deleted_at')->orWhereNull('credit_note_items.invoice_id');
                })
                ->groupBy('invoices.invoice_id', 'invoices.invoice_number', 'amount_due', 'invoices.client_id', 'invoices.date_invoiced', 'financial_years.year_starting', 'financial_years.year_ending' /*'invoice_items.tax_id'*/)
                ->orderBy('invoices.invoice_number', 'asc')
                ->first();
            // Calculate the unused balance
            $unusedBalance = $transaction->amount_received - TransactionItem::where(['transaction_id' => $transaction->transaction_id, 'type' => 1])->sum('amount_settled');

            if ($unusedBalance > 0) {

                $totalTax = $invoice->total_tax;
                $remainingDue = 0;
                // Calculate the remaining due amount for the invoice
                $totalSettled = TransactionItem::where(['invoice_id' => $invoice->invoice_id, 'type' => 1])->sum('amount_settled');
                if ($paymentType == 1) {
                    $remainingDue = $invoice->amount_due - $totalSettled - $totalTax;
                } else {
                    $remainingDue = $invoice->amount_due - $totalSettled;
                }
                $settleAmount = min($unusedBalance, $remainingDue);
                // Determine the settlement amount
                $settleAmount = min($unusedBalance, $remainingDue, $settleAmount);

                DB::transaction(function () use ($paymentType, $transaction, $invoice, $totalTax, $remainingDue, &$unusedBalance) {
                    if ($invoice->status == 0) {
                        // Settle the remaining due amount
                        if ($unusedBalance >= $remainingDue) {
                            // Fully settle the invoice
                            TransactionItem::create([
                                'transaction_item_id' => (new CustomIds())->generateId(),
                                'transaction_id' => $transaction->transaction_id,
                                'invoice_id' => $invoice->invoice_id,
                                'amount_settled' => number_format($remainingDue, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            if ($paymentType == 2) {
                                $invoice->update(['status' => 1]); // Fully settled
                            } else {
                                $invoice->update(['status' => 2]); // Fully settled
                            }
                            $unusedBalance -= $remainingDue;
                        } else {
                            // Partially settle the invoice
                            TransactionItem::create([
                                'transaction_item_id' => (new CustomIds())->generateId(),
                                'transaction_id' => $transaction->transaction_id,
                                'invoice_id' => $invoice->invoice_id,
                                'amount_settled' => number_format($unusedBalance, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            $invoice->update(['status' => 2]); // Partially settled
                            $unusedBalance = 0;
                        }
                    } else {
                        // Settle the partially settled invoice
                        if ($unusedBalance >= $remainingDue) {
                            // Fully settle the invoice
                            TransactionItem::create([
                                'transaction_item_id' => (new CustomIds())->generateId(),
                                'transaction_id' => $transaction->transaction_id,
                                'invoice_id' => $invoice->invoice_id,
                                'amount_settled' => number_format($remainingDue, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            if ($paymentType == 2) {
                                $invoice->update(['status' => 1]); // Fully settled
                            } else {
                                $invoice->update(['status' => 2]); // Fully settled
                            }
                            $unusedBalance -= $remainingDue;
                        } else {
                            // Partially settle the invoice
                            TransactionItem::create([
                                'transaction_item_id' => (new CustomIds())->generateId(),
                                'transaction_id' => $transaction->transaction_id,
                                'invoice_id' => $invoice->invoice_id,
                                'amount_settled' => number_format($unusedBalance, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            $invoice->update(['status' => 2]); // Partially settled
                            $unusedBalance = 0;
                        }
                    }
                });
            }
        }
        return back()->with('success', 'Successful! Payment allocation successfully updated');
    }
    public function processWHTPayment(Request $request, $id)
    {
        $balanceAmount = AdjustmentJournal::where('adjustment_journal_id', $request->journalId)->value('amount') -  TransactionItem::where('transaction_id', $request->journalId)->sum('amount_settled');

        $invoices = TransactionItem::join('invoices', function ($join) {
            $join->on('transaction_items.invoice_id', '=', 'invoices.invoice_id')
                ->whereNull('transaction_items.deleted_at');
        })
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->leftJoin(
                DB::raw("(SELECT invoice_id, SUM(CASE WHEN tax_id IS NOT NULL THEN (unit_price * quantity) * 0.02 ELSE 0 END) as total_tax FROM invoice_items WHERE deleted_at IS NULL GROUP BY invoice_id) as invoice_items"),
                'invoices.invoice_id',
                '=',
                'invoice_items.invoice_id'
            )
            ->leftJoin('invoices as credit_notes', function ($join) {
                $join->on('credit_notes.inv_reference', '=', 'invoices.invoice_number')
                    ->where('credit_notes.type', 2);
            })
            ->leftJoin('invoice_items as credit_note_items', 'credit_notes.invoice_id', '=', 'credit_note_items.invoice_id')
            ->where(['transaction_id' => $id, 'invoices.status' => 2])
            ->select([
                'invoices.invoice_id',
                'invoices.invoice_number',
                'invoices.client_id',
                'invoices.date_invoiced',
                'financial_years.year_starting',
                'financial_years.year_ending',
                DB::raw('invoices.amount_due - IFNULL(credit_notes.amount_due, 0) as amount_due'),
                DB::raw('SUM(transaction_items.amount_settled) as amount_settled'),
                DB::raw('IFNULL(invoice_items.total_tax, 0) as total_tax')
            ])
            ->where(['invoices.type' => 1])
            ->whereNot('invoices.status', 1)
            ->whereNull('invoices.deleted_at')
            ->whereNull('credit_notes.deleted_at')
            ->where(function ($query) {
                $query->whereNull('credit_notes.invoice_id')->orWhereNull('credit_note_items.invoice_id');
            })
            ->groupBy('invoices.invoice_id', 'invoices.invoice_number', 'amount_due', 'invoices.client_id', 'invoices.date_invoiced', 'financial_years.year_starting', 'financial_years.year_ending', 'invoice_items.total_tax')
            ->orderBy('transaction_items.created_at', 'asc')
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->total_tax > 0 && $invoice->total_tax !== null) {
                if ($balanceAmount >= $invoice->total_tax) {
                    TransactionItem::create([
                        'transaction_item_id' => (new CustomIds())->generateId(),
                        'transaction_id' => $request->journalId,
                        'invoice_id' => $invoice->invoice_id,
                        'amount_settled' => number_format($invoice->total_tax, 2, '.', ''),
                        'type' => 1, // Invoice type
                    ]);
                    Invoice::where('invoice_id', $invoice->invoice_id)->update(['status' => 1]); // Fully settled
                    $balanceAmount -= $invoice->total_tax;
                }
            }
        }
        return redirect()->back()->with('success', 'Success! Withholding Taxes settled successfully');
    }
    public function downloadReconciledBankStatement(Request $request, $id)
    {
        $monthFilter = $request->month ?? Carbon::now()->format('M Y');
        $date = Carbon::createFromFormat('M Y', $monthFilter)->format('t/m/Y');
        $bank = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')->where(['client_account_id' => $id])->first();
        $user = auth()->user()->user;
        $printed = Carbon::now();
        $balance = OpeningBalance::where('client_id', $id)->selectRaw("SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) -SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_balance")->value('total_balance'); // Directly fetch the numeric result

        // Query for transactions
        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->where(['account_id' => $id, 'reconciled' => 1])->whereNotNull('transactions.bank_date')->whereRaw("DATE_FORMAT(FROM_UNIXTIME(transactions.bank_date), '%b %Y') = ?", [$monthFilter])
            ->select(
                'transactions.transaction_id as transaction_id',
                'client_accounts.client_account_id',
                DB::raw("FROM_UNIXTIME(transactions.date_received) as date_received"),
                DB::raw("transactions.amount_received as debit"),
                DB::raw("'0.00' as credit"),
                'transactions.description',
                'client_account_name',
                'invoice_number',
                DB::raw("FROM_UNIXTIME(transactions.bank_date) as bank_date"),
                'transactions.reconciled',
            );

        // Step 1: Subquery to find the highest payment's client per transaction_code
        $highestPaymentSubquery = DB::table('payments')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->select(
                'payments.transaction_code',
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'payments.amount_received',
                // Rank payments per transaction_code (highest amount first, then alphabetical)
                DB::raw('ROW_NUMBER() OVER (PARTITION BY payments.transaction_code ORDER BY payments.amount_received DESC, client_accounts.client_account_name ASC) as payment_rank')
            );

        // Step 2: Filter to only the top-ranked payment per transaction_code
        $topClients = DB::table(DB::raw("({$highestPaymentSubquery->toSql()}) as ranked_payments"))
            ->mergeBindings($highestPaymentSubquery)
            ->where('payment_rank', 1)
            ->select(
                'transaction_code',
                'client_account_id',
                'client_account_name'
            );

        // Step 3: Main query - sum amounts and join with selected clients
        $payments = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->joinSub($topClients, 'top_clients', function ($join) {
                $join->on('payments.transaction_code', '=', 'top_clients.transaction_code');
            })
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->select(
                'payments.transaction_code as transaction_id',
                'top_clients.client_account_id',
                DB::raw('MAX(payments.date_received) as date_received'),
                DB::raw("'0.00' as debit"),
                DB::raw('SUM(payments.amount_received) as credit'),
                DB::raw('MAX(payments.description) as description'),
                'top_clients.client_account_name',
                DB::raw('MAX(payments.transaction_code) as invoice_number'),
                DB::raw('MAX(payments.bank_date) as bank_date'),
                DB::raw('MAX(payments.reconciled) as reconciled')
            )
            ->groupBy(
                'payments.transaction_code',
                'top_clients.client_account_id',
                'top_clients.client_account_name'
            )
            ->orderBy('amount_received', 'DESC');

        $journals = DB::table('adjustment_journals as aj1')
            ->join('adjustment_journals as aj2', function ($join) {
                $join->on('aj1.reference_code', '=', 'aj2.reference_code') // Match by journal number
                    ->whereRaw('aj1.type != aj2.type'); // Ensure opposite types (debit and credit)
            })
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'aj2.ledger_id')
            ->where(['aj1.ledger_id' => $id, 'aj1.reconciled' => 1])
            ->select('aj1.adjustment_journal_id as transaction_id', 'client_accounts.client_account_id', 'aj1.date_adjusted as date_received', 'aj1.amount as amount_received', 'aj1.description', DB::raw("CASE WHEN aj1.type = 1 THEN 'DEBIT' ELSE 'CREDIT' END AS type"), 'client_account_name', 'aj1.reference_code as invoice_number', 'aj1.bank_date', 'aj1.reconciled')
            ->orderBy('date_received');

        // Combine the queries using union
        $reconciled = $transactions->union($payments)->union($journals)->orderBy('bank_date')->get();

        $newDate = Carbon::createFromFormat('d/m/Y', $date);
        $lastDayPreviousMonth = $newDate->subMonth()->endOfMonth()->format('Y-m-d');

        $transactionDebit = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->where('transactions.bank_date', '<=', strtotime($lastDayPreviousMonth))
            ->selectRaw("SUM(transactions.amount_received) AS debit, 0 AS credit");

        $paymentCredit = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->where(['account_id' => $id, 'reconciled' => 1])
            ->where('payments.bank_date', '<=', strtotime($lastDayPreviousMonth))
            ->selectRaw("0 AS debit, SUM(payments.amount_received) AS credit");

        // Wrap the queries in DB::table() to ensure they are treated as subqueries
        $lastBalance = DB::table(DB::raw("({$transactionDebit->toSql()} UNION ALL {$paymentCredit->toSql()}) as combined"))
            ->mergeBindings($transactionDebit->getQuery())
            ->mergeBindings($paymentCredit->getQuery())
            ->selectRaw("SUM(debit) - SUM(credit) AS final_balance")
            ->value('final_balance');

        // Query for sales
        $sales = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->where(['account_id' => $id, 'reconciled' => 0, 'status' => 0])->whereNull('bank_date')->whereRaw("DATE_FORMAT(FROM_UNIXTIME(transactions.date_received), '%b %Y') = ?", [$monthFilter])
            ->select('transactions.transaction_id as transaction_id', 'client_accounts.client_account_id', DB::raw("FROM_UNIXTIME(transactions.date_received) as date_received"), DB::raw("transactions.amount_received as debit"), DB::raw("'0.00' as credit"), 'transactions.description', 'client_account_name', 'invoice_number', 'reconciled');

        // Step 1: Subquery to find the highest payment's client per transaction_code
        $highestPaymentSubquery = DB::table('payments')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select(
                'payments.transaction_code',
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'payments.amount_received',
                // Rank payments per transaction_code (highest amount first, then alphabetical)
                DB::raw('ROW_NUMBER() OVER (PARTITION BY payments.transaction_code ORDER BY payments.amount_received DESC, client_accounts.client_account_name ASC) as payment_rank')
            );

        // Step 2: Filter to only the top-ranked payment per transaction_code
        $topClients = DB::table(DB::raw("({$highestPaymentSubquery->toSql()}) as ranked_payments"))
            ->mergeBindings($highestPaymentSubquery)
            ->where('payment_rank', 1)
            ->select(
                'transaction_code',
                'client_account_id',
                'client_account_name'
            );

        // Step 3: Main query - sum amounts and join with selected clients
        $purchases = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->joinSub($topClients, 'top_clients', function ($join) {
                $join->on('payments.transaction_code', '=', 'top_clients.transaction_code');
            })
            ->where(['account_id' => $id, 'reconciled' => 0])
            ->select(
                'payments.transaction_code as transaction_id',
                'top_clients.client_account_id',
                DB::raw('MAX(FROM_UNIXTIME(payments.date_received)) as date_received'),
                DB::raw("'0.00' as debit"),
                DB::raw('SUM(payments.amount_received) as credit'),
                DB::raw('MAX(payments.description) as description'),
                'top_clients.client_account_name',
                DB::raw('MAX(payments.transaction_code) as invoice_number'),
                DB::raw('MAX(payments.reconciled) as reconciled')
            )
            ->groupBy(
                'payments.transaction_code',
                'top_clients.client_account_id',
                'top_clients.client_account_name'
            )
            ->orderBy('amount_received', 'DESC');

        $adjustments = DB::table('adjustment_journals as aj1')
            ->join('adjustment_journals as aj2', function ($join) {
                $join->on('aj1.reference_code', '=', 'aj2.reference_code') // Match by journal number
                    ->whereRaw('aj1.type != aj2.type'); // Ensure opposite types (debit and credit)
            })
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'aj2.ledger_id')
            ->where(['aj1.ledger_id' => $id, 'aj1.reconciled' => 0])
            ->select('aj1.adjustment_journal_id as transaction_id', 'client_accounts.client_account_id', DB::raw('FROM_UNIXTIME(aj1.date_adjusted) as date_received'), DB::raw("CASE WHEN aj1.type = 1 THEN aj1.amount END AS debit"), DB::raw("CASE WHEN aj1.type = 2 THEN aj1.amount END AS credit"), 'aj1.description', 'client_account_name', 'aj1.reference_code as invoice_number', 'aj1.reconciled')
            ->orderBy('date_received');

        // Combine the queries using union
        $unreconciled = $sales->union($purchases)->union($adjustments)->orderBy('date_received')->get();

        // Render Blade view
        $html = View::make('account::downloads.bank_statement', compact('unreconciled', 'bank', 'reconciled', 'date', 'lastBalance', 'balance'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%" >
                <tr>
                    <td align="left" style="border: none !important;"> <strong></strong></td>
                    <td align="center" style="border: none !important;">Page {PAGENO} of {nbpg}</td>
                    <td align="right" style="border: none !important;"> <strong></strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $bank->client_account_name . ' BANK RECONCILIATION STATEMENT.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function editPurchaseVoucher($id)
    {
        $invoice = Purchase::join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('client_accounts as client', 'client.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('tax_brackets', 'tax_brackets.tax_bracket_id', '=', 'purchase_items.tax_id')
            ->leftJoin('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')
            ->select('client_accounts.client_account_name as account_name', 'invoice_number', 'client.client_account_name as client_name', 'tax_rate', 'quantity', 'unit_price', 'currency_symbol', 'tax_name', 'posted', 'kra_number', 'purchase_items.description', 'ledger_id', 'purchases.purchase_id', 'purchase_items.tax_id', 'financial_year_id', 'date_invoiced', 'due_date', 'customer_message', 'purchase_item_id', 'client_accounts.client_account_id', 'currencies.currency_id', 'client_id')
            ->where(['purchases.purchase_id' => $id])
            ->whereNot('client_accounts.type', 3)
            ->whereNull('tax_brackets.deleted_at')
            ->whereNull('purchase_items.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->orderBy('client_accounts.client_account_name', 'ASC')
            ->get();
        $financialYears = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });

        $taxes = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')->where(['tax_brackets.status' => 1, 'effect' => 1])->orderBy('tax_name', 'asc')->get();
        $items =  $invoice->where('account_type', 2);
        $debtors = $invoice->where('account_type', 1);
        $suppliers = ClientAccount::orderBy('client_account_name', 'asc')->get();
        $invoiceItems =  ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->where(['client_accounts.currency_id' => $invoice[0]->currency_id])
            ->whereNotIn('type', [1, 4, 7, 8])
            ->orderBy('client_account_name')
            ->get();
        $taxRates = TaxBrackets::join('taxes', 'taxes.tax_id', '=', 'tax_brackets.tax_id')->select('tax_rate', 'taxes.tax_id', 'tax_bracket_id')->where(['effect' => 1, 'tax_brackets.status' => 1])->first();
        return view('account::purchases.editPurchaseVoucher')->with(['invoice' => $invoice, 'financialYears' => $financialYears, 'taxes' => $taxes, 'debtors' => collect($debtors), 'items' => collect($items), 'taxRates' => $taxRates, 'invoiceItems' => $invoiceItems, 'suppliers' => $suppliers]);
    }
    public function updatePurchaseVoucher(Request $request, $id)
    {
        $request->validate(['voucherNumber' => ['required', Rule::unique('purchases', 'voucher_number')->ignore($id, 'purchase_id'),],]);

        if ($request->totalAmount == null) {
            return redirect()->back()->with('error', 'You have not made any changes to your invoice');
        }
        DB::beginTransaction();
        try {
            $storedInvoice = Purchase::where('purchase_id', $id)->first();
            $purchase = ['client_id' => $request->supplier, 'invoice_number' => $request->voucherNumber, 'date_invoiced' => strtotime($request->invoiceDate), 'due_date' => strtotime($request->dueDate), 'customer_message' => $request->reason, 'financial_year_id' => $request->financialYear, 'amount_due' => number_format(floatval($request->totalAmount + $request->totalTaxAmount - $request->totalWhtAmount), 3, '.', ''),];
            Purchase::where('purchase_id', $id)->update($purchase);

            foreach ($request->creditItems as $keyItem => $invoice) {
                if (PurchaseItem::where(['purchase_item_id' => $keyItem, 'purchase_id' => $id])->exists()) {
                    $invoiceItems = ['ledger_id' => $invoice['ledger_id'], 'quantity' => $invoice['credit_quantity'], 'unit_price' => $invoice['credit_rate'], 'tax_id' => $invoice['vat'] == 0 ? null : $invoice['credit_tax'],];
                    PurchaseItem::where('purchase_item_id', $keyItem)->update($invoiceItems);
                } else {
                    $invoiceItem = ['purchase_item_id' => (new CustomIds())->generateId(), 'ledger_id' => $invoice['ledger_id'], 'purchase_id' => $id, 'quantity' => $invoice['credit_quantity'], 'unit_price' => $invoice['credit_rate'], 'tax_id' => $invoice['vat'] == 0 ? null : $invoice['credit_tax'],];
                    PurchaseItem::create($invoiceItem);
                }
            }

            if ($request->totalTaxAmount !== '0.00') {
                $invoiceAmount = $request->totalTaxAmount;
                $purchaseVAT = ClientAccount::where('client_account_number', '2203004')->first();
                if (PurchaseItem::where(['purchase_id' => $id, 'ledger_id' => $purchaseVAT->client_account_id])->exists()) {
                    $vatItem = ['quantity' => 1, 'unit_price' => $invoiceAmount,];
                    PurchaseItem::where(['purchase_id' => $id, 'ledger_id' => $purchaseVAT->client_account_id])->update($vatItem);
                } else {
                    $vatItem = ['purchase_item_id' => (new CustomIds())->generateId(), 'purchase_id' => $storedInvoice->purchase_id, 'ledger_id' => $purchaseVAT->client_account_id, 'description' => 'VAT FOR INV. NUMBER ' . $storedInvoice->voucher_number, 'quantity' => 1, 'unit_price' => $invoiceAmount, 'tax_id' => null,];
                    PurchaseItem::create($vatItem);
                }
            } else {
                $purchaseVAT = ClientAccount::where('client_account_number', '2203004')->first();
                if (PurchaseItem::where(['purchase_id' => $id, 'ledger_id' => $purchaseVAT->client_account_id])->exists()) {
                    PurchaseItem::where(['purchase_id' => $id, 'ledger_id' => $purchaseVAT->client_account_id])->delete();
                }
            }

            DB::commit();
            $this->logger->create();
            return redirect()->route('accounts.viewPurchases')->with('success', 'Success! Invoice Updated Successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function deletePurchaseItem($id)
    {
        DB::beginTransaction();
        try {
            $totalDeduction = 0;
            $taxAmount = 0;
            $item = PurchaseItem::find($id);
            $invoice = Purchase::where('purchase_id', $item->purchase_id)->first();
            $totalAmount = floatval($item->unit_price) * $item->quantity;
            if ($item->tax_id) {
                $tax = TaxBrackets::where('tax_bracket_id', $item->tax_id)->first();
                $taxAmount = floatval($totalAmount * ($tax->tax_rate)) / 100;
                $purchaseVat = ClientAccount::where('client_account_number', '2203004')->first();
                $taxLedger = PurchaseItem::where(['purchase_id' => $item->purchase_id, 'ledger_id' => $purchaseVat->client_account_id])->first();
                $taxDifference = floatval($taxLedger->unit_price - $taxAmount);
                $taxLedger->update(['unit_price' => $taxDifference]);
                $totalDeduction = $totalAmount + $taxAmount;
            } else {
                $totalDeduction = $totalAmount;
            }
            $newDueAmount = floatval($invoice->amount_due - $totalDeduction);
            $invoice->update(['amount_due' => $newDueAmount]);
            $item->delete();
            DB::commit();
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Invoice Updated Successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! ' . $e->getMessage());
        }
    }
    public function getPurchaseDetails(Request $request)
    {
        $transaction = Payment::find($request->paymentId);
        $invoice = Purchase::find($request->purchaseId);

        if (!$transaction || !$invoice) {
            return response()->json(['success' => false, 'message' => 'Transaction or Invoice not found.']);
        }

        // Calculate the unused balance from the transaction
        $unusedBalance = $transaction->amount_received - PaymentItem::where(['payment_id' => $transaction->payment_id, 'type' => 1])->sum('amount_settled');

        return response()->json([
            'success' => true,
            'unused_balance' => $unusedBalance,
            'amount_due' => $invoice->amount_due,
        ]);
    }
    public function processPayment(Request $request)
    {
        $request->validate([
            'form_data' => ['required', 'json', function ($attribute, $value, $fail) {
                if (empty(json_decode($value, true))) {
                    $fail('The selected items in form_data are required.');
                }
            }],
        ]);

        $paymentTypes = json_decode($request->form_data);

        // Retrieve the transaction and invoice
        $transaction = Payment::find($request->paymentId);

        foreach ($paymentTypes as $invoiceId => $paymentType) {
            $purchase = Purchase::query()
                ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
                ->leftJoin('purchase_items', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
                ->leftJoin('purchases as debit_notes', function ($join) {
                    $join->on('debit_notes.inv_reference', '=', 'purchases.voucher_number')
                        ->where('debit_notes.type', 2);
                })
                ->leftJoin('purchase_items as debit_note_items', 'debit_notes.purchase_id', '=', 'debit_note_items.purchase_id')
                ->leftJoin('payment_items', function ($join) {
                    $join->on('payment_items.purchase_id', '=', 'purchases.purchase_id')
                        ->whereNull('purchase_items.deleted_at');
                })
                ->where('purchases.purchase_id', $invoiceId)
                ->select([
                    'purchases.purchase_id',
                    'purchases.voucher_number',
                    'purchases.client_id',
                    'purchases.date_invoiced',
                    'financial_years.year_starting',
                    'financial_years.year_ending',
                    DB::raw('purchases.amount_due - IFNULL(debit_notes.amount_due, 0) as amount_due'),
                    DB::raw('SUM(payment_items.amount_settled) as amount_settled'),
                    DB::raw('SUM(CASE WHEN purchase_items.tax_id IS NOT NULL THEN (purchase_items.unit_price * purchase_items.quantity) * 0.02 ELSE 0 END) as total_tax')
                ])
                ->where(['purchases.type' => 1])
                ->whereNot('purchases.status', 1)
                ->whereNull('purchases.deleted_at')
                ->whereNull('purchase_items.deleted_at')
                ->where(function ($query) {
                    $query->whereNull('debit_notes.deleted_at')->orWhereNull('debit_notes.purchase_id');
                })
                ->where(function ($query) {
                    $query->whereNull('debit_note_items.deleted_at')->orWhereNull('debit_note_items.purchase_id');
                })
                ->groupBy('purchases.purchase_id', 'purchases.voucher_number', 'amount_due', 'purchases.client_id', 'purchases.date_invoiced', 'financial_years.year_starting', 'financial_years.year_ending' /*'invoice_items.tax_id'*/)
                ->first();

            $unusedBalance = $transaction->amount_received - PaymentItem::where(['payment_id' => $transaction->payment_id, 'type' => 1])->sum('amount_settled');

            if ($unusedBalance > 0) {
                $totalTax = $purchase->total_tax;
                $remainingDue = 0;
                // Calculate the remaining due amount for the invoice
                $totalSettled = PaymentItem::where(['purchase_id' => $purchase->purchase_id, 'type' => 1])->sum('amount_settled');
                if ($paymentType == 1) {
                    $remainingDue = $purchase->amount_due - $totalSettled - $totalTax;
                } else {
                    $remainingDue = $purchase->amount_due - $totalSettled;
                }

                $settleAmount = min($unusedBalance, $remainingDue);
                // Determine the settlement amount
                $settleAmount = min($unusedBalance, $remainingDue, $settleAmount);

                DB::transaction(function () use ($paymentType, $transaction, $purchase, $totalTax, $remainingDue, &$unusedBalance) {
                    if ($purchase->status == 0) {
                        // Settle the remaining due amount
                        if ($unusedBalance >= $remainingDue) {
                            // Fully settle the invoice
                            PaymentItem::create([
                                'payment_item_id' => (new CustomIds())->generateId(),
                                'payment_id' => $transaction->payment_id,
                                'purchase_id' => $purchase->purchase_id,
                                'amount_settled' => number_format($remainingDue, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            if ($paymentType == 2) {
                                $purchase->update(['status' => 1]); // Fully settled
                            } else {
                                $purchase->update(['status' => 2]); // Fully settled
                            }
                            $unusedBalance -= $remainingDue;
                        } else {
                            // Partially settle the invoice
                            PaymentItem::create([
                                'payment_item_id' => (new CustomIds())->generateId(),
                                'payment_id' => $transaction->payment_id,
                                'purchase_id' => $purchase->purchase_id,
                                'amount_settled' => number_format($unusedBalance, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            $purchase->update(['status' => 2]); // Partially settled
                            $unusedBalance = 0;
                        }
                    } else {
                        // Settle the partially settled invoice
                        if ($unusedBalance >= $remainingDue) {
                            // Fully settle the invoice
                            PaymentItem::create([
                                'payment_item_id' => (new CustomIds())->generateId(),
                                'payment_id' => $transaction->payment_id,
                                'purchase_id' => $purchase->purchase_id,
                                'amount_settled' => number_format($remainingDue, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            if ($paymentType == 2) {
                                $purchase->update(['status' => 1]); // Fully settled
                            } else {
                                $purchase->update(['status' => 2]); // Fully settled
                            }
                            $unusedBalance -= $remainingDue;
                        } else {
                            // Partially settle the invoice
                            PaymentItem::create([
                                'payment_item_id' => (new CustomIds())->generateId(),
                                'payment_id' => $transaction->payment_id,
                                'purchase_id' => $purchase->purchase_id,
                                'amount_settled' => number_format($unusedBalance, 2, '.', ''),
                                'type' => 1, // Invoice type
                            ]);
                            $purchase->update(['status' => 2]); // Partially settled
                            $unusedBalance = 0;
                        }
                    }
                });
            }
        }
        return redirect()->back()->with('success', 'Success! Payment voucher distribution successful');
    }
    public function removePaymentItem($id)
    {
        $transactionItem = PaymentItem::find($id);
        $amountPaid = PaymentItem::where(['purchase_id' => $transactionItem->purchase_id, 'payment_id' => $transactionItem->payment_id])->sum('amount_settled');
        $invoice = Purchase::leftJoin('purchases as debit_notes', function ($join) {
            $join->on('debit_notes.inv_reference', '=', 'purchases.voucher_number')
                ->where('debit_notes.type', 2);
        })
            ->select(DB::raw('purchases.amount_due - IFNULL(debit_notes.amount_due, 0) as amount_due'))
            ->where('purchases.purchase_id', $transactionItem->purchase_id)
            ->first();

        if (number_format($amountPaid, 2) >= number_format($invoice->amount_due, 2)) {
            Purchase::where('purchase_id', $transactionItem->purchase_id)->update(['status' => 0]);
            PaymentItem::where(['purchase_id' => $transactionItem->purchase_id, 'payment_id' => $transactionItem->payment_id])->delete();
        } else {
            Purchase::where('purchase_id', $transactionItem->purchase_id)->update(['status' => 2]);
            $transactionItem->delete();
        }

        return back()->with('success', 'Invoice deallocation successful');
    }
    public function viewPettyCash()
    {
        $accounts = ClientAccount::where(['type' => 4])->whereIn('client_account_number', [1102002, 1102003, 1102004, 1102005, 1102006, 1102007, 1102015])->get();
        $transactions = PettyCash::withoutTrashed()
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'petty_cashes.ledger_id')
            ->orderBy('date_invoiced', 'desc')
            ->orderBy('reference_code', 'desc')
            ->orderBy('petty_cashes.type', 'asc')
            ->get(['petty_cashes.*', 'client_accounts.client_account_name as ledger_name'])
            ->map(function ($journal) {
                $journal->debit = $journal->type == 1 ? $journal->amount : null;
                $journal->credit = $journal->type == 2 ? $journal->amount : null;
                return $journal;
            });;
        return view('account::petty.index')->with(['accounts' => $accounts, 'transactions' => $transactions]);
    }
    public function fetchPettyCreditAccount(Request $request)
    {
        $debtor = ClientAccount::withTrashed()->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->where('client_account_id', $request->clientId)
            ->where(['account_status' => 1])
            ->select('chart_name', 'currency_name', 'client_account_number', 'sub_account_name', 'client_accounts.currency_id')
            ->first();

        $creditors = ClientAccount::where(['client_accounts.currency_id' => $debtor->currency_id])->where(['account_status' => 1])
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_accounts.*', 'currency_symbol', 'currency_name')
            ->whereNull('client_accounts.deleted_at')->get();
        return response()->json([
            'debtor' => $debtor,
            'creditors' => $creditors
        ]);
    }
    public function getPettyCreditAccount(Request $request)
    {
        $creditor = ClientAccount::join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->where(['client_account_id' => $request->clientId])
            ->where(['account_status' => 1])
            ->select('chart_name', 'currency_name', 'client_account_number', 'sub_account_name', 'currencies.currency_id')
            ->first();

        return response()->json($creditor);
    }
    public function storePettyCashPurchase(Request $request)
    {
        // Debtor and Credit Account Details
        $debtor = ClientAccount::where('client_account_id', $request->debtor)->first();
        $currency = Currency::where('currency_id', $debtor->currency_id)->first();

        // Initialize array for entries
        $entries = [];

        // Loop through each credit account (assuming multiple credit accounts can be provided in the request)
        foreach ($request->account as $creditId => $creditDetails) {
            $creditAmount = $creditDetails['amount'];

            // Add Credit Entry
            $entries[] = [
                'petty_cash_id' => (new CustomIds())->generateId(),
                'reference_code' => PettyCash::newReferenceCode(),
                'ledger_id' => $creditId,
                'type' => 2,  // Credit type
                'amount' => $creditAmount,
                'description' => $creditDetails['description'],
                'exchange_rate' => $creditDetails['exchange_rate'] == null ? 1 : $creditDetails['exchange_rate'],
                'date_adjusted' => strtotime($request->date_adjusted),
                'si_number' => $request->si_number,
                'status' => 1
            ];

            $desc = $entries[0]['description'];
        }
        // Debit Entry
        $entries[] = [
            'petty_cash_id' => (new CustomIds())->generateId(),
            'reference_code' => PettyCash::newReferenceCode(),
            'ledger_id' => $request->debtor,
            'type' => 1,  // Debit type
            'amount' => $request->debitAmount,
            'description' => $desc,
            'exchange_rate' => 1,
            'date_adjusted' => strtotime($request->date_adjusted),
            'si_number' => $request->si_number,
            'status' => 1
        ];

        // Post the entries
        $this->AppClass->postPettyDoubleEntry(
            $entries,
        );

        /*$this->AppClass->postPettyDoubleEntry(
            $entries = [
                ['petty_cash_id' => (new CustomIds())->generateId(), 'reference_code' => PettyCash::newReferenceCode(), 'ledger_id' => $request->debtor, 'type' => 1, 'amount' => $request->amount, 'si_number' => $request->si_number],
                ['petty_cash_id' => (new CustomIds())->generateId(), 'reference_code' => PettyCash::newReferenceCode(), 'ledger_id' => $request->creditor, 'type' => 2, 'amount' => $request->amount, 'si_number' => $request->si_number],
            ],
            $description = $request->description,
            $reference = $request->reference,
            $date = strtotime($request->dateInvoiced),
        );*/
        return redirect()->back()->with('success', 'Success!, Petty Cash Record Created Successfully');
    }
    public function editPettyCash($id)
    {
        $petty = PettyCash::join('client_accounts', 'client_accounts.client_account_id', '=', 'petty_cashes.ledger_id')->findOrFail($id);
        $journals = PettyCash::where(['reference_code' => $petty->reference_code])
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'petty_cashes.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('petty_cashes.*', 'client_account_name', 'client_accounts.currency_id', 'priority')
            ->orderBy('client_account_name')
            ->get();
        $ledgers = ClientAccount::orderBy('client_account_name')->get();

        return view('account::petty.editPettyCash')->with(['journals' => $journals, 'petty' => $petty, 'ledgers' => $ledgers]);
    }
    public function updatePettyCash(Request $request, $id)
    {
        $journal = PettyCash::where('petty_cash_id', $id)->first();
        // Debtor and Credit Account Details
        $debtor = ClientAccount::where('client_account_id', $request->debitAccount)->first();
        $currency = Currency::where('currency_id', $debtor->currency_id)->first();

        $keys = array_keys($request->credits);
        $adjustmentIds = PettyCash::where(['reference_code' => $journal->reference_code, 'type' => 2])->pluck('petty_cash_id')->toArray();
        // Get the IDs from $adjustmentIds that are NOT in $keys
        $idsNotInKeys = array_diff($adjustmentIds, $keys);

        // Convert to an indexed array with values only (no keys)
        $idsNotInKeys = array_values($idsNotInKeys);

        PettyCash::whereIn('petty_cash_id', $idsNotInKeys)->delete();

        // Initialize array for entries
        $entries = [];

        // Loop through each credit account (assuming multiple credit accounts can be provided in the request)
        foreach ($request->credits as $creditId => $creditDetails) {
            $creditAmount = $creditDetails['creditAmount'];

            /*// If exchange_rate exists for the current credit account, apply exchange rate logic
            if (isset($creditDetails['exchangeRate']) && $creditDetails['exchangeRate'] !== null) {
                if ($currency->priority !== 1) {
                    $creditAmount = $creditAmount * $creditDetails['exchangeRate'];
                } else {
                    $creditAmount = $creditAmount / $creditDetails['exchangeRate'];
                }
            }*/

            $adjustmentJournalId = (strlen($creditId) < 11) ? (new CustomIds())->generateId() : $creditId;

            // Add Credit Entry
            $entries[] = [
                'petty_cash_id' => $adjustmentJournalId,
                'reference_code' => $journal->reference_code,
                'ledger_id' => $creditDetails['creditAccount'],
                'type' => 2,  // Credit type
                'amount' => number_format($creditAmount, 2, '.', ''),
                'description' => $creditDetails['description'],
                'exchange_rate' => $creditDetails['exchangeRate'] == null ? 1 : $creditDetails['exchangeRate'],
                'si_number' => $journal->si_number,
                'date_invoiced' => strtotime($request->paymentDate),
            ];

            $desc = $entries[0]['description'];
        }

        // Debit Entry
        $entries[] = [
            'petty_cash_id' => $journal->petty_cash_id,
            'reference_code' => $journal->reference_code,
            'ledger_id' => $request->debitAccount,
            'type' => 1,  // Debit type
            'amount' => number_format($request->debitAmount, 2, '.', ''),
            'description' => $desc,
            'exchange_rate' => 1,
            'si_number' => $journal->si_number,
            'date_invoiced' => strtotime($request->paymentDate),
        ];

        // Post the entries
        $this->AppClass->updatePettyCashDoubleEntry(
            $entries,
            $status = 1,
        );

        return redirect()->route('accounts.viewPettyCash')->with('success', 'Success!, Petty Cash updated Successfully');
    }
    public function deletePettyCash($id)
    {
        PettyCash::where('reference_code', base64_decode($id))->delete();
        return redirect()->back()->with('success', 'Success!, Petty Cash Record Deleted Successfully');
    }
    public function yearlyPlStatement($id)
    {
        $financial = FinancialYear::find($id);
        $data = $this->AppClass->fetchPLStatement($financial, $id);
        $results = [];

        foreach ($data as $entry) {
            $key = $entry->client_account_id;

            // Initialize
            if (!isset($results[$key])) {
                $results[$key] = [
                    'chart_name' => $entry->chart_name ?? '',
                    'currency_symbol' => $entry->currency_symbol ?? '',
                    'chart_number' => $entry->chart_number ?? '',
                    'ledger_name' => $entry->client_account_name ?? '',
                    'client_account_number' => $entry->client_account_number ?? '',
                    'client_account_id' => $entry->client_account_id ?? '',
                    'total_amount_due' => 0.0,
                    'type' => $entry->type == 1 ? 'revenue' : 'expense',
                    'jan' => 0.0,
                    'feb' => 0.0,
                    'mar' => 0.0,
                    'apr' => 0.0,
                    'may' => 0.0,
                    'jun' => 0.0,
                    'jul' => 0.0,
                    'aug' => 0.0,
                    'sep' => 0.0,
                    'oct' => 0.0,
                    'nov' => 0.0,
                    'dec' => 0.0,
                ];
            }

            $debit = floatval($entry->debit ?? 0);
            $credit = floatval($entry->credit ?? 0);

            // Apply the correct logic based on type
            $amount = $entry->type == 1 ? ($credit - $debit) : ($debit - $credit);

            $results[$key]['total_amount_due'] += $amount;

            // Monthly distribution
            if (!empty($entry->transaction_date)) {
                $timestamp = is_numeric($entry->transaction_date)
                    ? intval($entry->transaction_date)
                    : strtotime($entry->transaction_date);

                $month = strtolower(date('M', $timestamp));
                if (isset($results[$key][$month])) {
                    $results[$key][$month] += $amount;
                }
            }
        }

        $accounts = array_values($results);

        // Split into two groups for view
        $revenues = collect($accounts)->where('type', 'revenue');
        $expenses = collect($accounts)->where('type', 'expense');

        return view('account::reports.expenses.ledgerPerFinancialYear')->with(['expenses' => $expenses, 'revenues' => $revenues, 'financial' => $id, 'fy' => $financial]);
    }
    public function downloadPlStatement(Request $request, $id)
    {
        //        return $request->all();
        list($year, $type) = explode(':', base64_decode($id));
        $financial = FinancialYear::where('financial_year_id', $year)->first();
        $data = $this->AppClass->fetchPLStatement($financial, $year);

        $statements = $data;
        if ($request->dateFrom) {
            $statements = $statements->where('transaction_date', '>=', strtotime($request->dateFrom));
        }
        if ($request->dateTo) {
            $statements = $statements->where('transaction_date', '<=', strtotime($request->dateTo));
        }

        $collection = collect($statements); // your revenue + expense array

        // === 1. Prepare REVENUES ===
        $revenues = $collection
            ->filter(fn($item) => $item->type == '1')
            ->map(function ($item) {
                return (object) [
                    'chart_name' => $item->chart_name,
                    'ledger_name' => $item->client_account_name ?? 'Unknown',
                    'currency_symbol' => $item->currency_symbol ?? 'KES',
                    'total_amount_due' => ($item->credit ?? 0) - ($item->debit ?? 0),
                ];
            })
            ->groupBy('chart_name');

        // === 2. Prepare EXPENSES ===
        $expenses = $collection
            ->filter(fn($item) => $item->type == '2')
            ->map(function ($item) {
                return (object) [
                    'chart_name' => $item->chart_name,
                    'ledger_name' => $item->client_account_name ?? 'Unknown',
                    'currency_symbol' => $item->currency_symbol ?? 'KES',
                    'total_amount_due' => ($item->debit ?? 0) - ($item->credit ?? 0),
                ];
            })
            ->groupBy('chart_name');

        if ($request->reportFormat == 2) {
            // === 3. Setup Spreadsheet ===
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headerStyle = ['font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],];

            $textStyle = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],];

            $currencyStyle = ['numberFormat' => ['formatCode' => '#,##0.00'], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],];

            $row = 1;

            // === HEADER ROW ===
            $sheet->setCellValue("A{$row}", "#");
            $sheet->setCellValue("B{$row}", "CATEGORY");
            $sheet->setCellValue("C{$row}", "AMOUNT (KES)");
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($headerStyle);
            $row++;

            // === SECTION FUNCTION ===
            function writeSection($sheet, $sectionTitle, $groupedData, &$row, &$grandTotal, $headerStyle, $textStyle, $currencyStyle)
            {
                $sheet->setCellValue("A{$row}", strtoupper($sectionTitle));
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($headerStyle);
                $row++;

                foreach ($groupedData as $chartName => $items) {
                    $categoryTotal = $items->sum('total_amount_due');

                    // Chart Name Title Row
                    $sheet->setCellValue("A{$row}", strtoupper($chartName));
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($headerStyle);
                    $row++;

                    $i = 1;
                    foreach ($items->groupBy('ledger_name') as $ledger => $ledgerGroup) {
                        $amount = $ledgerGroup->sum('total_amount_due');
                        $sheet->setCellValue("A{$row}", $i++);
                        $sheet->setCellValue("B{$row}", "{$ledger}");
                        $sheet->setCellValue("C{$row}", $amount);
                        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($textStyle);
                        $sheet->getStyle("C{$row}")->applyFromArray($currencyStyle);
                        $row++;
                    }

                    // Subtotal Row
                    $sheet->setCellValue("A{$row}", "TOTAL " . ' ' . number_format($categoryTotal, 2));
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($headerStyle);
                    $row++;

                    $grandTotal += $categoryTotal;
                }
            }

            // === REVENUE SECTION ===
            $totalRevenue = 0;
            writeSection($sheet, "REVENUE", $revenues, $row, $totalRevenue, $headerStyle, $textStyle, $currencyStyle);

            // Blank Row
            $row++;

            // === EXPENSES SECTION ===
            $totalExpenses = 0;
            writeSection($sheet, "EXPENSES", $expenses, $row, $totalExpenses, $headerStyle, $textStyle, $currencyStyle);

            // Blank Row
            $row++;

            // === NET PROFIT ROW ===
            $sheet->setCellValue("A{$row}", "NET PROFIT");
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("C{$row}", number_format($totalRevenue - $totalExpenses, 2));
            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($headerStyle);

            // === Auto-size columns ===
            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // === Export File ===
            $filename = 'profit_loss_statement_' . time() . '.xlsx';
            $path = storage_path('app/public/' . $filename);
            (new Xlsx($spreadsheet))->save($path);

            return response()->download($path)->deleteFileAfterSend(true);
        }

        $reportType = $request->reportType;
        $financial = FinancialYear::find($year);
        // Render Blade view
        $html = View::make('account::downloads.pl_statement', compact('revenues', 'expenses', 'reportType', 'financial'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left"> <strong></strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right"> <strong></strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'P&L STATEMENT FOR THE FINANCIAL YEAR ' . Carbon::parse($financial->year_starting)->format('Y') . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadPettyCashPayment($id)
    {
        $payment = PettyCash::join('client_accounts', 'client_accounts.client_account_id', '=', 'petty_cashes.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'petty_cashes.user_id')
            ->select('user_infos.*', 'petty_cashes.*', 'currencies.currency_name', 'currency_symbol', 'client_account_name')
            ->where('petty_cash_id', $id)->first();

        $account = PettyCash::join('client_accounts', 'client_accounts.client_account_id', '=', 'petty_cashes.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->orderBy('amount', 'desc')
            ->where(['reference_code' => $payment->reference_code, 'petty_cashes.type' => 2])
            ->first();

        $financial =  FinancialYear::where('year_starting', '<=', Carbon::createFromTimestamp($payment['date_invoiced'])->format('Y-m-d'))->where('year_ending', '>=', Carbon::createFromTimestamp($payment['date_invoiced'])->format('Y-m-d'))->first();

        $type = 'PETTY CASH PAYMENT VOUCHER - ' . $payment->client_account_name;
        $action = 'PAID FOR';
        $clientName = $account->client_account_name;
        $amount = $this->AppClass->numberToWords($payment['amount']);
        $fYear = Carbon::parse($financial->year_starting)->format('Y') == Carbon::parse($financial->year_ending)->format('Y') ? Carbon::parse($financial->year_starting)->format('Y') : Carbon::parse($financial->year_starting)->format('Y') . '/' . Carbon::parse($financial->year_ending)->format('y');
        $amount = $payment['currency_name'] . ' ' . $amount . ' Only';
        $invDate = Carbon::createFromTimestamp($payment->date_invoiced)->format('d/m/Y');
        $invNumber = $payment->reference_code;
        $invMethod = $payment->client_account_name;
        $transCode = '';
        $invAmount = number_format($payment->amount, 2);
        $description = $payment->description;
        $user = $payment->surname . ' ' . $payment->first_name;

        // Render Blade view
        $html = View::make('account::downloads.payment_voucher', compact('payment', 'type', 'action', 'clientName', 'fYear', 'invDate', 'invNumber', 'invMethod', 'transCode', 'invAmount', 'amount', 'description', 'user'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left" style="border: none !important;"> <strong></strong></td>
                    <td align="center" style="border: none !important;">Page {PAGENO} of {nbpg}</td>
                    <td align="right" style="border: none !important;"> <strong></strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $type . ' #' . $invNumber . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadChartOfAccounts(Request $request)
    {
        $query = ClientAccount::withTrashed()
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('system_logs', 'system_logs.created_at', '=', 'client_accounts.created_at')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'system_logs.user_id')
            ->orderBy('account_number')
            ->orderBy('chart_name')
            ->orderBy('client_account_number')
            ->where(['activity' => 'https://stocks.packmac.net/account/add-client-accounts'])
            ->select('account_number', 'account_name', 'sub_category_number', 'sub_account_name', 'chart_number', 'chart_name', 'client_account_number', 'client_account_name', 'currency_name', 'currency_symbol', DB::raw('CONCAT(first_name," ",surname) AS created_by'), 'client_accounts.deleted_at', 'accounts.account_id', 'account_sub_categories.sub_account_id', 'chart_of_accounts.chart_id');

        if ($request->accountId !== null) {
            $query->where('accounts.account_id', $request->accountId);
        }

        if ($request->subAccountId !== null) {
            $query->where('account_sub_categories.sub_account_id', $request->subAccountId);
        }

        if ($request->chartAccountId !== null) {
            $query->where('chart_of_accounts.chart_id', $request->chartAccountId);
        }

        $accounts = $query->get();

        if ($request->type == 2) {
            /* // Usage example:
            $tempExcelFile = $this->generateAccountsExcel($accounts);

            // Download the file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="CHART OF ACCOUNTS.xlsx"');
            header('Cache-Control: max-age=0');
            readfile($tempExcelFile);

            // Clean up
            unlink($tempExcelFile);*/
            // Usage example:
            ob_start(); // Start output buffering
            try {
                $tempExcelFile = $this->generateAccountsExcel($accounts);

                // Verify the file exists and is readable
                if (!file_exists($tempExcelFile)) {  // <-- Added missing parenthesis
                    throw new Exception("Excel file not created");
                }

                // Clear any previous output
                ob_end_clean();

                // Download the file
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="CHART OF ACCOUNTS.xlsx"');
                header('Cache-Control: max-age=0');
                header('Content-Length: ' . filesize($tempExcelFile));

                readfile($tempExcelFile);

                // Clean up
                unlink($tempExcelFile);
                exit; // Important to prevent any further output
            } catch (Exception $e) {
                ob_end_clean();
                // Handle error appropriately
                die("Error generating Excel file: " . $e->getMessage());
            }
        }

        // Render Blade view
        $html = View::make('account::downloads.chart_of_accounts', compact('accounts'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left" style="border: none !important;"> <strong></strong></td>
                    <td align="center" style="border: none !important;">Page {PAGENO} of {nbpg}</td>
                    <td align="right" style="border: none !important;"> <strong></strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'CHART OF ACCOUNTS.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    function generateAccountsExcel($accounts)
    {
        // Group accounts hierarchically and calculate row counts
        $groupedAccounts = [];
        $rowCounts = [];

        foreach ($accounts as $account) {
            $master = $account['account_name'];
            $group = $account['sub_account_name'];
            $subgroup = $account['chart_name'];

            if (!isset($groupedAccounts[$master])) {
                $groupedAccounts[$master] = [];
            }
            if (!isset($groupedAccounts[$master][$group])) {
                $groupedAccounts[$master][$group] = [];
            }
            $groupedAccounts[$master][$group][$subgroup][] = $account;
        }

        // Calculate row counts for each level
        foreach ($groupedAccounts as $master => $groups) {
            $masterCount = 0;
            foreach ($groups as $group => $subgroups) {
                $groupCount = 0;
                foreach ($subgroups as $subgroup => $accounts) {
                    $count = count($accounts);
                    $rowCounts[$master][$group][$subgroup] = $count;
                    $groupCount += $count;
                    $masterCount += $count;
                }
                $rowCounts[$master][$group]['total'] = $groupCount;
            }
            $rowCounts[$master]['total'] = $masterCount;
        }

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Master Ledger',
            'Group Ledger',
            'Sub Group',
            'Acc Number',
            'Account Name',
            'Currency',
            'Status',
            'Created By'
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Apply header styles
        $headerStyle = [
            'font' => [
                'bold' => true,
                'name' => 'Book Antiqua',
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD3D3D3']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Populate data
        $row = 2;
        $mergeRanges = [];
        $maxColumnWidths = array_fill_keys(range('A', 'H'), 10); // Minimum width

        foreach ($groupedAccounts as $masterName => $groups) {
            $masterFirstRow = $row;
            $masterRowCount = $rowCounts[$masterName]['total'];
            $masterNameStr = strtoupper($masterName);
            $maxColumnWidths['A'] = max($maxColumnWidths['A'], strlen($masterNameStr));

            foreach ($groups as $groupName => $subGroups) {
                $groupFirstRow = $row;
                $groupRowCount = $rowCounts[$masterName][$groupName]['total'];
                $groupNameStr = strtoupper($groupName);
                $maxColumnWidths['B'] = max($maxColumnWidths['B'], strlen($groupNameStr));

                foreach ($subGroups as $subGroupName => $accounts) {
                    $subGroupFirstRow = $row;
                    $subGroupRowCount = count($accounts);
                    $subGroupNameStr = strtoupper($subGroupName);
                    $maxColumnWidths['C'] = max($maxColumnWidths['C'], strlen($subGroupNameStr));

                    foreach ($accounts as $account) {
                        // Set master name only on first row
                        if ($row == $masterFirstRow) {
                            $sheet->setCellValue('A' . $row, $masterNameStr);
                        }

                        // Set group name only on first row
                        if ($row == $groupFirstRow) {
                            $sheet->setCellValue('B' . $row, $groupNameStr);
                        }

                        // Set subgroup name only on first row
                        if ($row == $subGroupFirstRow) {
                            $sheet->setCellValue('C' . $row, $subGroupNameStr);
                        }

                        // Set account details and track max widths
                        $accNumber = strtoupper($account['client_account_number']);
                        $sheet->setCellValue('D' . $row, $accNumber);
                        $maxColumnWidths['D'] = max($maxColumnWidths['D'], strlen($accNumber));

                        $accName = strtoupper($account['client_account_name']);
                        $sheet->setCellValue('E' . $row, $accName);
                        $maxColumnWidths['E'] = max($maxColumnWidths['E'], strlen($accName));

                        $currency = strtoupper($account['currency_symbol']);
                        $sheet->setCellValue('F' . $row, $currency);
                        $maxColumnWidths['F'] = max($maxColumnWidths['F'], strlen($currency));

                        $status = $account['deleted_at'] == null ? 'ACTIVE' : 'CLOSED';
                        $sheet->setCellValue('G' . $row, $status);
                        $maxColumnWidths['G'] = max($maxColumnWidths['G'], strlen($status));

                        $createdBy = strtoupper($account['created_by']);
                        $sheet->setCellValue('H' . $row, $createdBy);
                        $maxColumnWidths['H'] = max($maxColumnWidths['H'], strlen($createdBy));

                        // Apply borders
                        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN
                                ]
                            ]
                        ]);

                        $row++;
                    }

                    $mergeRanges[] = 'C' . $subGroupFirstRow . ':C' . ($subGroupFirstRow + $subGroupRowCount - 1);
                }

                $mergeRanges[] = 'B' . $groupFirstRow . ':B' . ($groupFirstRow + $groupRowCount - 1);
            }

            $mergeRanges[] = 'A' . $masterFirstRow . ':A' . ($masterFirstRow + $masterRowCount - 1);
        }

        // Apply all merges
        foreach ($mergeRanges as $range) {
            $sheet->mergeCells($range);
        }

        // Set auto column widths with some padding
        foreach ($maxColumnWidths as $column => $width) {
            $sheet->getColumnDimension($column)
                ->setWidth(min($width + 2, 50)); // Add padding but limit to 50
        }

        // Center align all cells
        $sheet->getStyle('A2:H' . ($row - 1))->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Create writer and save to temporary file
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'accounts_') . '.xlsx';
        $writer->save($tempFile);

        return $tempFile;
    }
    public function editJournal($id)
    {
        $journal = AdjustmentJournal::join('client_accounts', 'client_accounts.client_account_id', '=', 'adjustment_journals.ledger_id')->select('reference_code', 'adjustment_journal_id', 'adjustment_journals.description', 'date_adjusted')->findOrFail($id);
        $journals = AdjustmentJournal::where('reference_code', $journal->reference_code)
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'adjustment_journals.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('adjustment_journals.*', 'client_account_name', 'client_accounts.currency_id', 'priority')
            ->orderBy('client_account_name')
            ->get();

        $debitEntries = $journals->where('type', 1);
        $creditEntries = $journals->where('type', 2);

        $accounts = ClientAccount::orderBy('client_account_name')->orderBy('client_account_name')->get();
        return view('account::journals.editJournal')->with(['debitEntries' => $debitEntries, 'creditEntries' => $creditEntries, 'accounts' => $accounts, 'journal' => $journal]);
    }
    public function updateAdjustmentJournal(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $journal = AdjustmentJournal::where('adjustment_journal_id', $id)->first();
            $entries = [];
            // Loop through each credit account (assuming multiple credit accounts can be provided in the request)
            foreach ($request->credit as $creditId => $creditDetails) {
                $keys = array_keys($request->credit);
                $adjustmentIds = AdjustmentJournal::where(['reference_code' => $journal->reference_code, 'type' => 2])->pluck('adjustment_journal_id')->toArray();
                // Get the IDs from $adjustmentIds that are NOT in $keys
                $idsNotInKeys = array_diff($adjustmentIds, $keys);

                // Convert to an indexed array with values only (no keys)
                $idsNotInKeys = array_values($idsNotInKeys);

                AdjustmentJournal::whereIn('adjustment_journal_id', $idsNotInKeys)->delete();

                $creditAmount = $creditDetails['amount'];
                $adjustmentJournalId = (strlen($creditId) > 12) ? (new CustomIds())->generateId() : $creditId;

                // Add Credit Entry
                $entries[] = [
                    'adjustment_journal_id' => $adjustmentJournalId,
                    'reference_code' => $journal->reference_code,
                    'ledger_id' => $creditDetails['account_id'],
                    'type' => 2,
                    'date_adjusted' => strtotime($request->date_adjusted),
                    'amount' => number_format($creditAmount, 2, '.', ''),
                    'description' => $request->description,
                    'exchange_rate' => $creditDetails['exchange_rate'] == null ? 1 : $creditDetails['exchange_rate']
                ];
            }

            foreach ($request->debit as $creditId => $creditDetails) {
                $keys = array_keys($request->debit);
                $adjustmentIds = AdjustmentJournal::where(['reference_code' => $journal->reference_code, 'type' => 1])->pluck('adjustment_journal_id')->toArray();
                // Get the IDs from $adjustmentIds that are NOT in $keys
                $idsNotInKeys = array_diff($adjustmentIds, $keys);

                // Convert to an indexed array with values only (no keys)
                $idsNotInKeys = array_values($idsNotInKeys);

                AdjustmentJournal::whereIn('adjustment_journal_id', $idsNotInKeys)->delete();

                $creditAmount = $creditDetails['amount'];
                $adjustmentJournalId = (strlen($creditId) > 12) ? (new CustomIds())->generateId() : $creditId;

                // Add Credit Entry
                $entries[] = [
                    'adjustment_journal_id' => $adjustmentJournalId,
                    'reference_code' => $journal->reference_code,
                    'ledger_id' => $creditDetails['account_id'],
                    'type' => 1,
                    'date_adjusted' => strtotime($request->date_adjusted),
                    'amount' => number_format($creditAmount, 2, '.', ''),
                    'description' => $request->description,
                    'exchange_rate' => $creditDetails['exchange_rate'] == null ? 1 : $creditDetails['exchange_rate']
                ];
            }

            foreach ($entries as $entry) {
                $exists = AdjustmentJournal::where('adjustment_journal_id', $entry['adjustment_journal_id'])->first();
                if (!$exists) {
                    AdjustmentJournal::create([
                        'adjustment_journal_id' => $entry['adjustment_journal_id'],
                        'reference_code' => $entry['reference_code'],
                        'ledger_id' => $entry['ledger_id'],
                        'type' => $entry['type'],
                        'amount' => $entry['amount'],
                        'description' => $entry['description'],
                        'exchange_rate' => $entry['exchange_rate'],
                        'date_adjusted' => $entry['date_adjusted'],
                        'status' => 1,
                        'user_id' => auth()->user()->user_id
                    ]);
                } else {
                    AdjustmentJournal::where('adjustment_journal_id', $entry['adjustment_journal_id'])->update([
                        'ledger_id' => $entry['ledger_id'],
                        'amount' => $entry['amount'],
                        'description' => $entry['description'],
                        'exchange_rate' => $entry['exchange_rate'],
                        'date_adjusted' => $entry['date_adjusted'],
                    ]);
                }
            }
            $this->logger->create();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return back()->with('error', 'Oops. Error updating journal ' . $exception->getMessage());
        }
        return redirect()->route('accounts.viewSystemJournals')->with('success', 'Success!, Journal updated Successfully');
    }
    public function deleteJournal($id)
    {
        AdjustmentJournal::where('reference_code', base64_decode($id))->delete();
        return redirect()->back()->with('success', 'Success!, Journal Record Deleted Successfully');
    }
    public function downloadJournal($id)
    {
        $journal = AdjustmentJournal::join('client_accounts', 'client_accounts.client_account_id', '=', 'adjustment_journals.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'adjustment_journals.user_id')
            ->select('adjustment_journals.*', 'priority', 'surname', 'first_name')
            ->find($id);
        $journals = AdjustmentJournal::where('reference_code', $journal->reference_code)
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'adjustment_journals.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'adjustment_journals.*',
                'client_accounts.client_account_name',
                'client_accounts.currency_id',
                'priority',
                DB::raw('CASE WHEN adjustment_journals.type = 1 THEN amount END as debit'),
                DB::raw('CASE WHEN adjustment_journals.type = 2 THEN amount END as credit')
            )
            ->orderBy('adjustment_journals.type')
            ->orderBy('client_account_name')
            ->get();

        $user = $journal->surname . ' ' . $journal->first_name;
        // Render Blade view
        $html = View::make('account::downloads.journal', compact('journals', 'journal'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Prepared By: <strong> ' . $user . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Printen On: <strong>' . Carbon::now() . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'ADJUSTMENT JOURNAL # ' . $journal->reference_code . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function updateTaxRecords()
    {
        $TAX_LEDGER_ID = '6FIvhWZrLWku';
        $TAX_RATE = 0.16;

        // 1. Get invoice IDs that have taxable items
        $taxableInvoiceIds = InvoiceItem::whereNotNull('tax_id')
            ->whereNull('deleted_at')
            ->pluck('invoice_id')
            ->unique()
            ->toArray();

        // 2. Get the invoices that match
        $invoices = Invoice::whereIn('invoice_id', $taxableInvoiceIds)
            //            ->where('type', 2)
            //            ->whereIn('invoice_number', ['INV0012-25', 'INV0955-25', 'INV1182-25', 'INV1207-25'])
            ->whereNull('deleted_at')
            ->get();

        foreach ($invoices as $invoice) {
            // 3. Get all vatable items for this invoice
            $vatableItems = InvoiceItem::where('invoice_id', $invoice->invoice_id)
                ->whereNotNull('tax_id')
                ->whereNull('deleted_at')
                ->get();

            // 4. Calculate total taxable base
            $totalBase = $vatableItems->sum(function ($item) {
                return $item->unit_price * $item->quantity;
            });

            $expectedTax = round($totalBase * $TAX_RATE, 2);

            // 5. Look for existing tax line (should be only one per invoice)
            $taxLine = InvoiceItem::where('invoice_id', $invoice->invoice_id)
                ->where('ledger_id', $TAX_LEDGER_ID)
                ->whereNull('deleted_at')
                ->first();

            if ($taxLine) {
                $actualTax = round($taxLine->unit_price * $taxLine->quantity, 2);

                if ($actualTax !== $expectedTax) {
                    $taxLine->unit_price = $expectedTax;
                    $taxLine->quantity = 1;
                    $taxLine->description = 'VAT FOR INV. NUMBER ' . $invoice->invoice_number;
                    $taxLine->save();
                }
            } else {
                InvoiceItem::create([
                    'invoice_item_id' => (new CustomIds())->generateId(),
                    'invoice_id' => $invoice->invoice_id,
                    'ledger_id' => $TAX_LEDGER_ID,
                    'quantity' => 1,
                    'unit_price' => $expectedTax,
                    'description' => 'VAT FOR INV. NUMBER ' . $invoice->invoice_number,
                    'status' => 0
                ]);
            }
        }

        return back()->with('success', 'Tax records updated successfully.');
    }

    public function dayBook(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::parse($request->start_date)->endOfDay();
        $type = $request->type;
        $daybook = $this->AppClass->dayBookReport($startDate, $endDate, $type);

        return view('account::reports.daybook.index')->with(['daybook' => $daybook, 'startDate' => $startDate, 'endDate' => $endDate, 'type' => $type]);
    }
    public function exportDayBook(Request $request)
    {
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        $type = $request->type;

        return Excel::download(new TransactionsExport($startDate, $endDate, $type, new AppClass()), 'daybook_report.xlsx');
    }
    public function viewOpeningBalances()
    {
        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear
            ];
        });

        $balances = OpeningBalance::join('financial_years', 'financial_years.financial_year_id', '=', 'opening_balances.financial_year_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.client_id')
            ->select('client_account_name', 'year_starting', DB::raw('CASE WHEN opening_balances.type = 1 THEN amount ELSE 0 END AS debit'), DB::raw('CASE WHEN opening_balances.type = 2 THEN amount ELSE 0 END AS credit'), 'opening_balance_id')
            ->latest('opening_balances.created_at')
            ->get();
        $accounts = ClientAccount::all();
        return view('account::balances.index')->with(['years' => $years, 'balances' => $balances, 'accounts' => $accounts]);
    }
    public function storeOpeningBalance(Request $request)
    {
        $ledger = ClientAccount::where(['type' => 5])->first();
        $fy = FinancialYear::where('financial_year_id', $request->financialYear)->first();
        $openingBal = [
            'opening_balance_id' => (new CustomIds())->generateId(),
            'client_id' => $request->clientId,
            'ledger_id' => $ledger->client_account_id,
            'financial_year_id' => $request->financialYear,
            'type' => $request->type,
            'amount' => $request->amount,
            'date_invoiced' => strtotime(Carbon::parse($fy->year_starting)->startOfDay()),
            'user_id' => auth()->user()->user_id
        ];

        if (OpeningBalance::where(['client_id' => $request->clientId, 'financial_year_id' => $request->financialYear, 'type' => $request->type, 'amount' => $request->amount])->exists()) {
            return redirect()->back()->with('error', 'Oops! Similar transaction exists for this client');
        } else {
            OpeningBalance::create($openingBal);
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Client opening balance updated successfully');
        }
    }
    public function deleteOpeningBalance($id)
    {
        OpeningBalance::where('opening_balance_id', $id)->delete();
        return redirect()->back()->with('success', 'Success! Opening balance entry deleted successfully');
    }
    public function searchInvoice(Request $request)
    {
        $query = trim($request->input('invoice'));

        $ledgers = DB::table('client_accounts')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->crossJoin('financial_years')
            ->where(function ($q) use ($query) {
                $q->where('client_accounts.client_account_number', 'like', "%{$query}%")
                    ->orWhere('chart_name', 'like', "%{$query}%")
                    ->orWhere('client_accounts.client_account_name', 'like', "%{$query}%");
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'client_accounts.client_account_number',
                'chart_name',
                'client_accounts.client_account_number',
                'financial_years.financial_year_id as financial_year_id',
                DB::raw("YEAR(financial_years.year_starting) as financial_year"),
                'type'
            )
            ->whereNull('client_accounts.deleted_at')
            ->get();

        $sales = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'invoices.financial_year_id')
            ->where('invoice_number', 'like', "%{$query}%")
            ->orWhere('chart_name', 'like', "%{$query}%")
            ->orWhere('client_account_name', 'like', "%{$query}%")
            ->orWhere('amount_due', 'like', "%{$query}%")
            ->select('invoice_id', 'amount_due', 'invoice_number', 'date_invoiced', 'client_account_name',  DB::raw("YEAR(financial_years.year_starting) as financial_year"))
            ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'purchases.financial_year_id')
            ->where('voucher_number', 'like', "%{$query}%")
            ->orWhere('purchases.invoice_number', 'like', "%{$query}%")
            ->orWhere('chart_name', 'like', "%{$query}%")
            ->orWhere('client_account_name', 'like', "%{$query}%")
            ->orWhere('amount_due', 'like', "%{$query}%")
            ->select('purchase_id', 'amount_due', 'invoice_number', 'voucher_number', 'date_invoiced', 'client_account_name', DB::raw("YEAR(financial_years.year_starting) as financial_year"))
            ->get();

        $receipts = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->join('client_accounts as bank', 'bank.client_account_id', '=', 'transactions.account_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'transactions.financial_year_id')
            ->where('invoice_number', 'like', "%{$query}%")
            ->orWhere('client_accounts.client_account_name', 'like', "%{$query}%")
            ->orWhere('bank.client_account_name', 'like', "%{$query}%")
            ->orWhere('amount_received', 'like', "%{$query}%")
            ->select('bank.client_account_name as bankName', 'client_accounts.client_account_name as clientName', 'invoice_number', 'amount_received', DB::raw("YEAR(year_starting) as financial_year"), 'transaction_id', 'date_received')
            ->get();

        $payments = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as bank', 'bank.client_account_id', '=', 'payments.account_id')
            ->join('financial_years', 'financial_years.financial_year_id', '=', 'payments.financial_year_id')
            ->where('invoice_number', 'like', "%{$query}%")
            ->orWhere('client_accounts.client_account_name', 'like', "%{$query}%")
            ->orWhere('bank.client_account_name', 'like', "%{$query}%")
            ->orWhere('amount_received', 'like', "%{$query}%")
            ->select('bank.client_account_name as bankName', 'client_accounts.client_account_name as clientName', 'invoice_number', 'amount_received', DB::raw("YEAR(year_starting) as financial_year"), 'payment_id', 'date_received')
            ->get();

        return view('account::search.index')->with(['ledgers' => $ledgers, 'sales' => $sales, 'purchases' => $purchases, 'receipts' => $receipts, 'payments' => $payments, 'searchTerm' => $query]);
    }
    public function showBalanceSheet($id)
    {
        $financial = FinancialYear::find($id);
        $balanceSheet = $this->AppClass->fetchBalanceSheet($financial, $id);
        return view('account::reports.balance.balanceSheet')->with(['balancesheet' => $balanceSheet, 'fy' => $financial]);
    }
    public function exportBalanceSheet($id)
    {
        $financial = FinancialYear::find($id);
        $balanceSheet = $this->AppClass->fetchBalanceSheet($financial, $id);
        return Excel::download(new BalanceSheetExport($balanceSheet), 'balance_sheet.xlsx');
    }
    public function balanceSheetFy()
    {
        $years = FinancialYear::orderBy('year_starting', 'desc')->get()->map(function ($year) {
            $formattedYear = Carbon::parse($year->year_starting)->format('Y') == Carbon::parse($year->year_ending)->format('Y')
                ? Carbon::parse($year->year_starting)->format('Y')
                : Carbon::parse($year->year_starting)->format('Y') . '/' . Carbon::parse($year->year_ending)->format('y');

            return [
                'financial_year_id' => $year->financial_year_id, // Assuming there's an 'id' field
                'financial_year' => $formattedYear,
                'year_starting' => $year->year_starting,
                'year_ending' => $year->year_ending,
            ];
        });

        return view('account::reports.balance.financialYears')->with('years', $years);
    }
    public function viewDeliveries()
    {
        $deliveries = DB::table('currentstock')
            ->orderBy('sortOrder', 'desc')->where('current_stock', '>', 0)->where('current_weight', '>', 0)
            ->select('delivery_id', 'garden_name', 'grade_name', 'client_name', 'order_number', 'lot_number', 'invoice_number', 'date_received', 'stocked_at', 'bay_name', 'current_stock', 'current_weight', 'sortOrder', 'client_id', 'grade_id', 'garden_id', 'station_id', 'sale_number', 'tea_id', 'stock_id')
            ->whereNull('deleted_at')
            ->get();
        return view('account::reports.aging.stocks')->with(['stocks' => $deliveries]);
    }
    public function StockReport(Request $request)
    {
        $client = $request->input('client');
        $station = $request->input('station');
        $from = $request->input('from');
        $to = $request->input('to');
        $report = $request->report;
        return $this->AppClass->downloadCurrentStock($client, $station, $from, $to, $report);
    }


public function closingStockReport(Request $request)
{
    $user = Client::first();
    $dateTo = $request->input('to') ?? Carbon::now()->endOfDay()->toDateString();
    // $client = $request->input('client') ?? $user->client_name;
    $client = $request->isMethod('get')
    ? ($request->input('client') ?? $user->client_name)
    : $request->input('client');

    $station = $request->input('station');
    $zeroBalance = $request->input('zero_balance') ?? 0;

    $query = DB::table('currentstock')
        ->whereNull('deleted_at')
        ->orderBy('date_received', 'desc')
        ->select(
            'delivery_id',
            'stock_id',
            'garden_name',
            'grade_name',
            'client_name',
            'order_number',
            'lot_number',
            'invoice_number',
            'date_received',
            'stocked_at',
            'bay_name',
            'sortOrder',
            'client_id',
            'grade_id',
            'garden_id',
            'station_id',
            'sale_number',
            'tea_id',
            'created_at',
            'received_by',
            'delivery_type',
            'pallet_weight',
            'package_tare',
            'total_pallets',
            'total_weight',
            'closing_date',
            'shipped_packages',
            'shipped_weight',
            'requested_palettes',
            'requested_weight',
            'transferred_palettes',
            'transferred_weight',
            'blended_packages',
            'blended_weight',
            'sample_palletes',
            'package_weight',
            'net_weight',
            'tea_type'
        );

    // Apply filters
    if (!empty($client)) {
        $query->where(function ($q) use ($client) {
            if (is_numeric($client)) {
                $q->where('client_id', $client);
            } else {
                $q->whereRaw('LOWER(TRIM(client_name)) = ?', [strtolower(trim($client))]);
            }
        });
    }

    if (!empty($station)) {
        $query->where(function ($q) use ($station) {
            if (is_numeric($station)) {
                $q->where('station_id', $station);
            } else {
                $q->whereRaw('LOWER(TRIM(stocked_at)) = ?', [strtolower(trim($station))]);
            }
        });
    }

    $stocks = $query->get();

    // Get all shipments, transfers, samples etc. with their dates for this stock
    $stockIds = $stocks->pluck('stock_id')->toArray();
    $deliveryIds = $stocks->pluck('delivery_id')->toArray();

    // Fetch shipments with dates
    $shipments = DB::table('shipments as sh')
        ->join('shipping_instructions as si', 'si.shipping_id', '=', 'sh.shipping_id')
        ->whereIn('sh.stock_id', $stockIds)
        ->whereIn('sh.delivery_id', $deliveryIds)
        ->whereNull('sh.deleted_at')
        ->where('sh.status', '>=', 0)
        ->select(
            'sh.stock_id',
            'sh.delivery_id',
            'sh.shipped_packages',
            'sh.shipped_weight',
            'si.ship_date',
            DB::raw('DATE(FROM_UNIXTIME(si.ship_date)) as ship_date_formatted')
        )
        ->get()
        ->groupBy(function($item) {
            return $item->stock_id . '_' . $item->delivery_id;
        });

    // Fetch transfers with dates
    $transfers = DB::table('transfers')
        ->whereIn('stock_id', $stockIds)
        ->whereIn('delivery_id', $deliveryIds)
        ->whereNull('deleted_at')
        ->select(
            'stock_id',
            'delivery_id',
            'requested_palettes',
            'requested_weight',
            DB::raw('DATE(created_at) as transfer_date')
        )
        ->get()
        ->groupBy(function($item) {
            return $item->stock_id . '_' . $item->delivery_id;
        });

    // Fetch external transfers with dates
    $externalTransfers = DB::table('external_transfers')
        ->whereIn('stock_id', $stockIds)
        ->whereIn('delivery_id', $deliveryIds)
        ->whereNull('deleted_at')
        ->select(
            'stock_id',
            'delivery_id',
            'transferred_palettes',
            'transferred_weight',
            DB::raw('DATE(created_at) as transfer_date')
        )
        ->get()
        ->groupBy(function($item) {
            return $item->stock_id . '_' . $item->delivery_id;
        });

    // Fetch samples with dates
    $samples = DB::table('tea_samples')
        ->whereIn('stock_id', $stockIds)
        ->whereIn('delivery_id', $deliveryIds)
        ->whereNull('deleted_at')
        ->select(
            'stock_id',
            'delivery_id',
            'sample_palletes',
            'sample_weight',
            DB::raw('DATE(created_at) as sample_date')
        )
        ->get()
        ->groupBy(function($item) {
            return $item->stock_id . '_' . $item->delivery_id;
        });

    // Fetch blends with dates
    $blends = DB::table('blend_teas as bt')
        ->join('blend_sheets as bs', 'bs.blend_id', '=', 'bt.blend_id')
        ->whereIn('bt.stock_id', $stockIds)
        ->whereIn('bt.delivery_id', $deliveryIds)
        ->whereNull('bt.deleted_at')
        ->where('bt.status', '>=', 0)
        ->select(
            'bt.stock_id',
            'bt.delivery_id',
            'bt.blended_packages',
            'bt.blended_weight',
            DB::raw('DATE(FROM_UNIXTIME(bs.blend_shipped)) as blend_date')
        )
        ->get()
        ->groupBy(function($item) {
            return $item->stock_id . '_' . $item->delivery_id;
        });

    // Fetch rebaggings with dates
    $rebaggings = DB::table('rebaggings')
        ->whereIn('stock_id', $stockIds)
        ->whereNull('deleted_at')
        ->select(
            'stock_id',
            'packages',
            'weight',
            DB::raw('DATE(created_at) as rebag_date')
        )
        ->get()
        ->groupBy('stock_id');

    // Calculate historical closing stock for each record
    $stocks = $stocks->map(function ($stock) use ($dateTo, $shipments, $transfers, $externalTransfers, $samples, $blends, $rebaggings) {
        $key = $stock->stock_id . '_' . $stock->delivery_id;

        // Start with total stock received
        $currentPackages = $stock->total_pallets;
        $currentWeight = $stock->net_weight;

        // Array to collect all transaction dates
        $transactionDates = [];

        // Subtract shipments that occurred BEFORE dateTo (not on the date)
        $stockShipments = $shipments->get($key, collect());
        foreach ($stockShipments as $shipment) {
            if ($shipment->ship_date_formatted && $shipment->ship_date_formatted < $dateTo) {
                $currentPackages -= floatval(str_replace(',', '', $shipment->shipped_packages ?? 0));
                $currentWeight -= floatval(str_replace(',', '', $shipment->shipped_weight ?? 0));
                $transactionDates[] = $shipment->ship_date_formatted;
            }
        }

        // Subtract transfers that occurred BEFORE dateTo (not on the date)
        $stockTransfers = $transfers->get($key, collect());
        foreach ($stockTransfers as $transfer) {
            if ($transfer->transfer_date && $transfer->transfer_date < $dateTo) {
                $currentPackages -= floatval(str_replace(',', '', $transfer->requested_palettes ?? 0));
                $currentWeight -= floatval(str_replace(',', '', $transfer->requested_weight ?? 0));
                $transactionDates[] = $transfer->transfer_date;
            }
        }

        // Subtract external transfers that occurred BEFORE dateTo (not on the date)
        $stockExternalTransfers = $externalTransfers->get($key, collect());
        foreach ($stockExternalTransfers as $exTransfer) {
            if ($exTransfer->transfer_date && $exTransfer->transfer_date < $dateTo) {
                $currentPackages -= floatval(str_replace(',', '', $exTransfer->transferred_palettes ?? 0));
                $currentWeight -= floatval(str_replace(',', '', $exTransfer->transferred_weight ?? 0));
                $transactionDates[] = $exTransfer->transfer_date;
            }
        }

        // Subtract samples that occurred BEFORE dateTo (not on the date)
        $stockSamples = $samples->get($key, collect());
        foreach ($stockSamples as $sample) {
            if ($sample->sample_date && $sample->sample_date < $dateTo) {
                $currentPackages -= floatval(str_replace(',', '', $sample->sample_palletes ?? 0));
                $currentWeight -= floatval(str_replace(',', '', $sample->sample_weight ?? 0));
                $transactionDates[] = $sample->sample_date;
            }
        }

        // Subtract blends that occurred BEFORE dateTo (not on the date)
        $stockBlends = $blends->get($key, collect());
        foreach ($stockBlends as $blend) {
            if ($blend->blend_date && $blend->blend_date < $dateTo) {
                $currentPackages -= floatval(str_replace(',', '', $blend->blended_packages ?? 0));
                $currentWeight -= floatval(str_replace(',', '', $blend->blended_weight ?? 0));
                $transactionDates[] = $blend->blend_date;
            }
        }

        // Subtract rebaggings that occurred BEFORE dateTo (not on the date)
        $stockRebaggings = $rebaggings->get($stock->stock_id, collect());
        foreach ($stockRebaggings as $rebag) {
            if ($rebag->rebag_date && $rebag->rebag_date < $dateTo) {
                $currentPackages -= floatval(str_replace(',', '', $rebag->packages ?? 0));
                $currentWeight -= floatval(str_replace(',', '', $rebag->weight ?? 0));
                $transactionDates[] = $rebag->rebag_date;
            }
        }

        // Set the calculated values
        $stock->display_stock = max(0, $currentPackages);
        $stock->display_weight = max(0, $currentWeight);
        $stock->current_stock = max(0, $currentPackages);
        $stock->current_weight = max(0, $currentWeight);

        // Set the latest closing date from all transactions that occurred before dateTo
        $stock->closing_date = !empty($transactionDates) ? max($transactionDates) : null;

        return $stock;
    });

    // Filter zero balance if requested
    if ($zeroBalance == 0 || $zeroBalance === '0') {
        $stocks = $stocks->filter(function ($stock) {
            return $stock->display_stock > 0 && $stock->display_weight > 0;
        })->values();
    }

    // Distinct client list
    $clients = DB::table('currentstock')
        ->whereNull('deleted_at')
        ->select(DB::raw('TRIM(LOWER(client_name)) as client_name'))
        ->distinct()
        ->pluck('client_name')
        ->map(fn($name) => ucwords($name))
        ->unique()
        ->values()
        ->toArray();

    // Distinct station list
    $stations = DB::table('currentstock')
        ->whereNull('deleted_at')
        ->select(DB::raw('TRIM(LOWER(stocked_at)) as stocked_at'))
        ->distinct()
        ->pluck('stocked_at')
        ->unique()
        ->values()
        ->toArray();

    if ($request->action == 'download') {
        $appClass = new AppClass();
        return Excel::download(
            new ExportClosingStock($appClass, $stocks),
            'CLOSING STOCK ' . time() . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    return view('account::reports.aging.closing_stock', compact(
        'stocks', 'dateTo', 'client', 'station', 'clients', 'stations', 'zeroBalance'
    ));
}

    public function stockCollectionReport(Request $request)
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()->timestamp
            : Carbon::now()->startOfMonth()->startOfDay()->timestamp;

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()->timestamp
            : Carbon::now()->endOfMonth()->endOfDay()->timestamp;

        $client   = $request->input('client');
        $deliveryType = $request->input('delivery_type');

        $data = DeliveryOrder::join('stock_ins', function ($join) {
        $join->on('stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->where(function ($query) {
                $query->where('stock_ins.delivery_type', 1)
                      ->orWhereNull('stock_ins.delivery_type');
            })
            ->whereNull('stock_ins.deleted_at');
    })
    ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
    ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
    ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
    ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
    ->leftJoin('tea_samples', function ($join) {
        $join->on('tea_samples.delivery_id', '=', 'delivery_orders.delivery_id')
            ->whereNull('tea_samples.deleted_at');
    })
    ->leftJoin('loading_instructions', function ($join) {
        $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
            ->whereNull('loading_instructions.deleted_at');
    })
    ->whereBetween('stock_ins.date_received', [$from, $to])

    ->select([
        'clients.client_name',
        'clients.client_id',
        'delivery_orders.sale_number',
        'warehouses.warehouse_name',
        'gardens.garden_name',
        'grades.grade_name',
        'delivery_orders.delivery_type',
        'delivery_orders.invoice_number',
        'delivery_orders.lot_number',
        'delivery_orders.order_number',
        'stock_ins.total_pallets',
        'stock_ins.net_weight',
        'delivery_orders.status',
        'stock_ins.user_id as received_by',
        'delivery_orders.created_at as date_received',
        'delivery_orders.delivery_id',
        DB::raw("CASE WHEN loading_instructions.loading_id IS NOT NULL THEN loading_instructions.loading_number ELSE stock_ins.delivery_number END AS tci_number"),
        DB::raw("
            CASE
                WHEN tea_samples.type = 1 THEN 'Sampled'
                WHEN tea_samples.type = 2 THEN 'Damage'
                WHEN tea_samples.type = 3 THEN 'Loss'
                ELSE ''
            END AS difference
        "),
        'stock_ins.stock_id', 'prompt_date', 'collection'
    ])
    ->groupBy('delivery_orders.delivery_id', 'stock_ins.stock_id')
    ->where('delivery_orders.status', 2)
    ->orderBy('gardens.garden_name', 'asc')
    ->orderBy('date_received', 'asc');

if ($client) {
    $data->where('delivery_orders.client_id', $client);
}

if (!is_null($deliveryType)) {
    $data->where('delivery_orders.delivery_type', $deliveryType);
}

        $collections = $data->get();

        if ($request->action == 'download') {
            $appClass = new AppClass();
            return Excel::download(new ExportStockCollection($appClass, $collections), 'STOCK COLLECTION ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $clients = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->select('clients.client_id', 'clients.client_name')
            ->distinct()
            ->orderBy('clients.client_name', 'asc')
            ->get();


        return view('account::reports.aging.stock_collections', compact('collections', 'from', 'to', 'client', 'clients', 'deliveryType'));
    }
    public function viewShipments(Request $request)
    {
        $selectedMonth = $request->input('month'); // Format: YYYY-MM
        $monthToUse = $selectedMonth ? Carbon::parse($selectedMonth . '-01') : now();
        $monthStart = $monthToUse->copy()->startOfMonth()->timestamp;
        $monthEnd = $monthToUse->copy()->endOfMonth()->timestamp;
        $sheets = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->join('transporters', 'transporters.transporter_id', 'blend_sheets.transporter_id')
            ->select(
                'blend_sheets.blend_id as shipping_id',
                'blend_sheets.created_at',
                'station_name',
                'client_name',
                'blend_number as shipping_number',
                'vessel_name',
                'port_name',
                'blend_sheets.status',
                DB::raw("'BL' as type"),
                'blend_shipped as shipment_date',
                'container_size',
                DB::raw("(SELECT COUNT(*) FROM shipment_containers WHERE shipment_containers.blend_id = blend_sheets.blend_id ) as total_containers"),
                'transporter_name',
                'clients.client_id'
            )
            ->whereNull('blend_sheets.deleted_at')
            ->where('blend_sheets.status', 4)
            ->whereBetween('blend_shipped', [$monthStart, $monthEnd])
            ->get();

        // Get Shipping Instructions
        $shipping = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->join('transporters', 'transporters.transporter_id', 'shipping_instructions.transporter_id')
            ->select(
                'shipping_id',
                'client_name',
                'shipping_instructions.created_at',
                'shipping_instructions.status',
                'station_name',
                'shipping_number',
                'vessel_name',
                'port_name',
                DB::raw("'SL' as type"),
                'ship_date as shipment_date',
                'container_size',
                DB::raw("1 as total_containers"),
                'transporter_name',
                'clients.client_id'
            )
            ->where('shipping_instructions.status', 4)
            ->whereBetween('ship_date', [$monthStart, $monthEnd])
            ->get();

        $shipments = $sheets->merge($shipping)->sortByDesc('shipment_date')->values();

        // For month dropdown - get distinct months from both tables
        $availableMonths = collect()
            ->merge(DB::table('blend_sheets')->whereNull('deleted_at')->whereNotNull('blend_shipped')->pluck('blend_shipped'))
            ->merge(ShippingInstruction::whereNotNull('ship_date')->pluck('ship_date'))
            ->map(function ($date) {
                return Carbon::createFromTimestamp($date)->format('Y-m');
            })
            ->unique()
            ->sortDesc()
            ->values();

        return view('account::reports.aging.shipments')->with(['sheets' => $shipments, 'selectedMonth' => $selectedMonth, 'availableMonths' => $availableMonths]);
    }
    public function downloadShipment($id)
    {
        list($shippingId, $shippingType) = explode(':', base64_decode($id));
        if ($shippingType == 'BL') {
            return $this->AppClass->downloadBlendJob($shippingId);
        } else {
            return $this->AppClass->downloadStraightLine($shippingId);
        }
    }
    public function shipmentReport(Request $request)
    {
        $type = $request->type ?? null;
        $client = $request->client ?? null;
        $from = $request->from ?? null;
        $to = $request->to ?? null;
        $report = $request->report ?? null;

        $blend = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->join('transporters', 'transporters.transporter_id', '=', 'blend_sheets.transporter_id')
            ->join('clearing_agents', 'clearing_agents.agent_id', 'blend_sheets.agent_id')
            ->select('blend_sheets.blend_id as shipping_id', 'station_name', 'client_name', 'blend_number as shipping_number', 'vessel_name', 'port_name', 'blend_sheets.status', DB::raw("'BL' as type"), 'blend_shipped as shipment_date', 'container_size', DB::raw("(SELECT COUNT(*) FROM shipment_containers WHERE shipment_containers.blend_id = blend_sheets.blend_id ) as total_containers"), 'transporter_name', 'clients.client_id', 'output_packages', 'output_weight', 'consignee', 'agent_name', 'shipping_mark')
            ->whereNull('blend_sheets.deleted_at')
            ->where('blend_sheets.status', 4);

        if ($client) {
            $blend->where('clients.client_id', $client);
        }
        if ($to) {
            $blend->where('blend_shipped', '<=', Carbon::parse($to)->timestamp);
        }
        if ($from) {
            $blend->where('blend_shipped', '>=', Carbon::parse($from)->timestamp);
        }

        $sis = DB::table('shipping_instructions')
            ->join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->join('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->join('clearing_agents', 'clearing_agents.agent_id', 'shipping_instructions.clearing_agent')
            ->select('shipping_id', 'station_name', 'client_name', 'shipping_number', 'vessel_name', 'port_name', 'shipping_instructions.status', DB::raw("'SL' as type"), 'ship_date as shipment_date', 'container_size', DB::raw("1 as total_containers"), 'transporter_name', 'clients.client_id', DB::raw("(SELECT SUM(shipped_packages) FROM shipments WHERE shipments.shipping_id = shipping_instructions.shipping_id ) as output_packages"), DB::raw("(SELECT SUM(shipped_weight) FROM shipments WHERE shipments.shipping_id = shipping_instructions.shipping_id ) as output_weight"), 'consignee', 'agent_name', 'shipping_mark')
            ->where('shipping_instructions.status', 4);
        if ($client) {
            $sis->where('clients.client_id', $client);
        }
        if ($to) {
            $sis->where('ship_date', '<=', Carbon::parse($to)->timestamp);
        }
        if ($from) {
            $sis->where('ship_date', '>=', Carbon::parse($from)->timestamp);
        }

        // Union BEFORE executing queries
        $unionQuery = $blend->union($sis);

        // Wrap with DB::table()->fromQuery() to execute
        $shipments = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->mergeBindings($unionQuery) // to apply bindings properly
            ->orderByDesc('shipment_date')
            ->get();
        if ($type) {
            $shipments = $shipments->where('type', $type);
        }
        $date = date('D, d-m-Y, h:i:s');
        $printed = auth()->user()->user;

        $by = $printed->first_name . ' ' . $printed->surname;
        if ($from == null) {
            $period = 'FULL STATEMENT UPTO ' . $to;
        } else {
            $dateTo = $to == null ? Carbon::today()->format('Y-m-d') : $to;
            $period = 'FOR PERIOD BETWEEN ' . $from . ' AND ' . $dateTo;
        }

        if ($request->report == 2) {
            return Excel::download(new ExportShipments($shipments), 'SHIPPING INSTRUCTION' . ' ' . time() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top' => 2,
            'margin_bottom' => 4,
            'margin_left' => 5,
            'margin_right' => 5,
            'setAutoBottomMargin' => 'stretch',
            'tempDir' => storage_path('app/mpdf_temp'),
            'pcre.backtrack_limit' => '10000000' // Increase backtrack limit
        ]);

        // Set footer
        $mpdf->SetHTMLFooter('
                <table width="100%">
                    <tr>
                        <td align="left">Printed On : <strong>' . $date . '</strong></td>
                        <td align="center">Page {PAGENO} of {nbpg}</td>
                        <td align="right">Prepared by : <strong>' . $by . '</strong></td>
                    </tr>
                </table>
            ');

        // Add company header (using absolute path for image)
        $logoPath = 'assets/img/favicons/icon.png';
        $companyHeader = '
            <style>
                .company-container {
                    position: relative;
                    width: 100%;
                    display: flex;
                    justify-content: flex-start; /* Align company info to left */
                    align-items: flex-start; /* Align content at top */
                    padding-top: 20px;
                }
                .company-info {
                    text-align: left;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .logo {
                    height: 50px;
                    width: 50px;
                    margin-bottom: 5px;
                }
                .heading {
                    color: green;
                    font-size: 14px;
                    font-weight: bold;
                    margin: 0;
                }
                .header {
                    text-align: center;
                    font-weight: bold;
                    font-size: 12px;
                    margin: 5px 0;
                }
                hr {
                    border: 1px solid #000;
                    margin: 5px 0;
                }
                /* QR Code - Force it to top-right */
                .qr-container {
                    position: absolute;
                    top: 0;
                    right: 0;
                    padding: 10px; /* Optional for spacing */
                }
                .qr-code {
                    text-align: right !important;
                    width: 110px !important;
                    height: 110px !important;
                }
                .address{
                    text-align: center !important;
                }
                .verification{
                    color: red !important;
                    font-weight: bold;
                    font-size: 11px !important;
                    font-family: Cambria,monospace;
                }
            </style>';

        $mpdf->WriteHTML($companyHeader);
        $html = View::make('clerk::downloads.shipments', [
            'orders' => $shipments,
            'by' => $by,
            'printed' => $printed,
            'period' => $period
        ])->render();

        $mpdf->WriteHTML($html);

        // Output PDF
        $pdfFileName = 'SHIPMENT JOBS.pdf';
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function importExcel(Request $request)
    {
        $request->validate([
            'excelUpload' => 'required|file|mimes:xlsx,xls'
        ]);
        $data = [
            'date_adjusted' => $request->date_adjusted,
            'description' => $request->description
        ];
        $import = new ImportJournal($data);
        Excel::import($import, $request->file('excelUpload'));
        if (!empty($import->errors)) {
            return back()->with('importErrors', $import->errors);
        }
        return back()->with('success', 'Upload successful!');
    }
    public function uniqueTransactionCode(Request $request)
    {
        $data = Payment::where('transaction_code', 'LIKE', '%' . $request->code)->get();
        return response()->json($data);
    }

    public function viewInternalTransfers(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();

        $transfers = Transfers::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'transfers.delivery_id');
            })
            ->leftJoin('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->select(
                'stations.station_name',
                'stations.station_id',
                DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'),
                'destination_station.station_name as destination_name',
                'destination_station.station_id as destination',
                'transfers.status',
                'transfers.delivery_number',
                DB::raw('DATE(transfers.created_at) as created_at'),
                'warehouse_locations.location_id',
                'stations.location_id as origin'
            )
            ->selectRaw('SUM(transfers.requested_palettes) as total_palettes')
            ->selectRaw('SUM(transfers.requested_weight) as total_weight')
            ->groupBy(
                'transfers.delivery_number',
                'stations.station_name',
                DB::raw('COALESCE(clients.client_name, blendBalances.client_name)'),
                'destination_station.station_name',
                'transfers.status',
                DB::raw('DATE(transfers.created_at)'),
                'stations.station_id',
                'destination_station.station_id',
                'warehouse_locations.location_id',
                'stations.location_id'
            )
            ->whereBetween('transfers.created_at', [$from, $to])
            ->orderBy('transfers.created_at', 'desc')
            ->get();

        return view('account::transfers.internalTransfers')->with(['transfers' => $transfers, 'from' => $from, 'to' => $to]);
    }

    public function viewInternalTransferDetails($id)
    {
        $transfers = Transfers::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'transfers.delivery_id');
            })
            ->join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->select('stations.station_name', 'stations.station_id', 'clients.client_name', 'destination_station.station_name as destination_name', 'destination_station.station_id as destination', 'transfers.status', 'transfers.delivery_number', 'transfers.created_at', 'warehouse_locations.location_id', 'stations.location_id as origin', 'transfers.requested_palettes', 'transfers.requested_weight', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'stock_id', 'registration', 'driver_name', 'id_number', 'drivers.phone', 'transporters.transporter_id', 'transporter_name', 'transfer_id', 'grade', 'garden', 'blend_number', 'blendBalances.client_name as client')
            ->where(['delivery_number' => base64_decode($id)])
            ->orderBy('garden_name', 'asc')
            ->orderBy('garden', 'asc')
            ->orderBy('invoice_number', 'asc')
            ->orderBy('blend_number', 'asc')
            ->get();
        return view('account::transfers.viewInternalTransfer')->with(['transfers' => $transfers]);
    }

    public function approveInternalTransfer($id)
    {
        DB::beginTransaction();
        try {
            Transfers::where('delivery_number', base64_decode($id))->update(['status' => 2]);
            Approval::create([
                'approval_id' => (new CustomIds())->generateId(),
                'job_id' => base64_decode($id),
                'user_id' => auth()->user()->user_id,
                'approval_date' => time(),
                'order' => 2
            ]);
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');
        } catch (Exception $exception) {
            return back()->with('error', 'Oops! Failed Try Again ' . $exception->getMessage());
        }
    }

    public function downloadInterDelNote($id)
    {
        return $this->AppClass->downloadInternalTransfer($id);
    }

    public function viewExternalTransfers(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();

        $transfers = ExternalTransfer::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
                    ->whereNull('delivery_orders.deleted_at');
            })
            ->leftJoin('auctions', function ($join) {
                $join->on('auctions.delivery_id', '=', 'external_transfers.delivery_id')
                    ->whereNull('auctions.deleted_at');
            })
            ->leftJoin('clients as buyer', 'buyer.client_id', '=', 'auctions.client_id')
            ->leftJoin('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftjoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftjoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('other_transporters', 'other_transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->select(
                'external_transfers.status',
                DB::raw("CONCAT(COALESCE(clients.client_name, ''), COALESCE(blendBalances.client_name, '')) as client_name"),
                DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'),
                DB::raw('COALESCE(warehouses.warehouse_id, other_destinations.warehouse_id) as warehouse_id'),
                DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'),
                'external_transfers.delivery_number',
                DB::raw('DATE(external_transfers.created_at) as created_at'),
                'buyer.client_name as buyer_name',
                'warehouse_locations.location_id',
                DB::raw("CONCAT(COALESCE(transporters.transporter_id, ''), COALESCE(other_transporters.transporter_id, '')) as transporter_id"),
                DB::raw("CONCAT(COALESCE(transporters.transporter_name, ''), COALESCE(other_transporters.transporter_name, '')) as transporter_name"),
                'external_transfers.driver_id',
                'driver_name',
                'drivers.phone',
                'id_number',
                'external_transfers.registration'
            )
            ->selectRaw('SUM(external_transfers.transferred_palettes) AS total_palettes')
            ->selectRaw('SUM(external_transfers.transferred_weight) AS total_weight')
            ->orderBy('delivery_number', 'desc')
            ->orderBy('external_transfers.created_at', 'desc')
            ->groupBy(
                'external_transfers.status',
                DB::raw("CONCAT(COALESCE(clients.client_name, ''), COALESCE(blendBalances.client_name, ''))"),
                DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name)'),
                DB::raw('COALESCE(warehouses.warehouse_id, other_destinations.warehouse_id)'),
                DB::raw('COALESCE(stations.station_name, blendBalances.station_name)'),
                'external_transfers.delivery_number',
                DB::raw('DATE(external_transfers.created_at)'),
                'buyer_name',
                'warehouse_locations.location_id',
                DB::raw("CONCAT(COALESCE(transporters.transporter_id, ''), COALESCE(other_transporters.transporter_id, ''))"),
                DB::raw("CONCAT(COALESCE(transporters.transporter_name, ''), COALESCE(other_transporters.transporter_name, ''))"),
                'driver_id',
                'driver_name',
                'phone',
                'id_number',
                'registration'
            )
            ->whereBetween('external_transfers.created_at', [$from, $to])
            ->get();

        return view('account::transfers.externalTransfers')->with(['transfers' => $transfers, 'from' => $from, 'to' => $to]);
    }
    public function viewExternalTransferDetails($id)
    {
        $transfers = ExternalTransfer::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'external_transfers.delivery_id');
            })
            ->leftJoin('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftjoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftjoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->select('ex_transfer_id', 'external_transfers.status', DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'), DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'), DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'), 'external_transfers.delivery_number', DB::raw('DATE(external_transfers.created_at) as created_at'), 'location_id', DB::raw('COALESCE(gardens.garden_name, blendBalances.garden) as garden_name'), DB::raw('COALESCE(grades.grade_name, blendBalances.grade) as grade_name'), DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number) as invoice_number'), 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'delivery_orders.lot_number')
            ->where(['external_transfers.delivery_number' => base64_decode($id)])
            ->get();

        return view('account::transfers.viewExternalTransfer')->with(['transfers' => $transfers]);
    }
    public function approveExternalTransfer($id)
    {
        DB::beginTransaction();
        try {
            ExternalTransfer::where('delivery_number', base64_decode($id))->update(['status' => 3]);
            Approval::create([
                'approval_id' => (new CustomIds())->generateId(),
                'job_id' => base64_decode($id),
                'user_id' => auth()->user()->user_id,
                'approval_date' => time(),
                'order' => 2
            ]);
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');
        } catch (Exception $exception) {
            return back()->with('error', 'Oops! Failed Try Again ' . $exception->getMessage());
        }
    }

    public function downloadExtraDelNote($id)
    {
        return $this->AppClass->downloadExternalTransfers($id);
    }

    public function downloadDelNote($id)
    {
        return $this->AppClass->downloadExternalDelNote($id);
    }

    public function unbilledClients()
    {
        $clients = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->where([
                'billed' => 0,
                'delivery_orders.status' => 2
            ])
            ->groupBy('clients.client_id', 'clients.client_name')
            ->selectRaw("
                clients.client_id,
                clients.client_name,
                SUM(packet) AS total_packages,
                SUM(
                    CASE
                        WHEN unit_weight IS NULL OR unit_weight = '' THEN
                            CAST(REPLACE(weight, ',', '') AS DECIMAL(10,2))
                        ELSE
                            CAST(REPLACE(unit_weight, ',', '') AS DECIMAL(10,2))
                    END
                ) AS total_weight
            ")
            ->orderBy('client_name')
            ->get();

        return view('account::shipping.unbilledClients', compact('clients'));
    }

    public function unbilledTeas($id)
    {
        $teas = DeliveryOrder::join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->where(['billed' => 0, 'delivery_orders.status' => 2, 'delivery_orders.client_id' => $id])
            ->select('delivery_id', 'invoice_number', 'order_number', 'garden_name', 'grade_name', 'lot_number', 'packet', 'warehouse_name', 'sub_warehouse_name', 'packet as packages', 'billed', DB::raw("CASE WHEN unit_weight IS NULL THEN weight ELSE unit_weight END AS weight"))
            ->get();
        $client = Client::findOrFail($id);
        return view('account::shipping.unbilledTeas', compact('teas', 'client'));
    }

    public function updateBillStatus(Request $request)
    {
        DeliveryOrder::where('delivery_id', $request->delivery_id)
            ->update(['billed' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function list()
    {
        $items = NotificationUser::with('notification', 'notification.creator')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($items);
    }

    public function details($id)
    {
        $record = NotificationUser::with([
            'notification.creator',
            'notification.users.user'
        ])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        // Mark as read for ONLY the current user
        if ($record && $record->is_read == 0) {
            $record->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);
        }

        $isAdmin = DB::table('task_module_user_roles')->where(['user_id' => Auth::id(), 'role_id' => 1])->first();

        return response()->json([
            'details' => $record,
            'readers' => $isAdmin ? [
                'read_by' => $record->notification->users->where('is_read', 1)->pluck('user'),
                'pending' => $record->notification->users->where('is_read', 0)->pluck('user'),
            ] : null
        ]);
    }
}
