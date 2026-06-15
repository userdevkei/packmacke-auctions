<?php

namespace App\Services;

use Mpdf\Mpdf;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\Station;
use App\Models\StockIn;
use App\Models\BlendTea;
use App\Models\UserInfo;
use App\Models\Transfers;
use App\Models\BlendSheet;
use App\Models\BlendBalance;
use GuzzleHttp\Psr7\Request;
use App\Exports\AuctionSheet;
use App\Models\BlendMaterial;
use App\Models\BlendShipment;
use App\Models\DeliveryOrder;
use App\Models\BlendSupervision;
use App\Models\ExternalTransfer;
use App\Models\ShipmentContainer;
use Illuminate\Support\Facades\DB;
use App\Models\ShippingInstruction;
use Modules\Clerk\Entities\Auction;
use App\Exports\ExportBlendBalances;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Clerk\Entities\Approval;
use Modules\Account\Entities\Invoice;
use Modules\Account\Entities\Journal;
use Modules\Account\Entities\Payment;
use Modules\Admin\Entities\Signatory;
use Modules\Account\Entities\Purchase;
use Modules\Account\Entities\PettyCash;
use Illuminate\Support\Facades\Response;
use Modules\Account\Entities\InvoiceItem;
use Modules\Account\Entities\Transaction;
use Modules\Clerk\Entities\ReportRequest;
use Modules\Account\Entities\PurchaseItem;
use Modules\Account\Entities\ClientAccount;
use Modules\Account\Entities\FinancialYear;
use Modules\Account\Entities\OpeningBalance;
use Modules\Account\Entities\JournalSchedule;
use Modules\Account\Entities\ScheduledJournal;
use Mpdf\Output\Destination as PdfDestination;
use Modules\Account\Entities\AdjustmentJournal;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\Payments;

class AppClass
{
    protected $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
        50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    ];
    protected $suffixes = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];
    public function currentFinancialYear()
    {
        $today = Carbon::today()->format('Y-m-d');
        $fy = FinancialYear::where('year_starting', '<=', $today)->orderBy('year_starting', 'desc')->first();
        return $fy;
    }
    public function numberToWords($number)
{
    if ($number == 0) {
        return 'Zero';
    }

    $integerPart = floor((float) $number);
    $decimalPart = round(((float) $number - $integerPart) * 100);

    $result = '';
    $suffixIndex = 0;

    while ($integerPart > 0) {
        $chunk = $integerPart % 1000;

        if ($chunk > 0) {
            $result = $this->chunkToWords($chunk) . ' ' . $this->suffixes[$suffixIndex] . ' ' . $result;
        }

        $integerPart = floor($integerPart / 1000);
        $suffixIndex++;
    }

    $result = trim($result);

    if ($decimalPart > 0) {
        $result .= ' and ' . $this->chunkToWords($decimalPart) . ' Cents';
    }

    return $result;
}
    private function chunkToWords($number)
    {
        $result = '';

        if ($number >= 100) {
            $result .= $this->words[floor((float) $number / 100)] . ' Hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $result .= $this->words[floor((float) $number / 10) * 10] . ' ';
            $number %= 10;
        }

        if ($number > 0) {
            $result .= $this->words[$number] . ' ';
        }

        return trim($result);
    }
    public function updateDoubleEntry($entries, $status = 1, $date = null)
    {
        foreach ($entries as $entry) {
            $this->updateEntry(
                $entry['adjustment_journal_id'],
                $entry['reference_code'],
                $entry['ledger_id'],
                $entry['type'],
                $entry['amount'],
                $entry['description'],
                $entry['exchange_rate'],
                $status,
                $date,
            );
        }
    }
    public function updateEntry($journalId, $referenceCode, $ledgerId, $type, $amount, $description, $exchangeRate, $status, $date)
    {
        if (AdjustmentJournal::where('adjustment_journal_id', $journalId)->count() > 0) {
            return AdjustmentJournal::where('adjustment_journal_id', $journalId)->update([
                'ledger_id' => $ledgerId,
                'amount' => $amount,
                'description' => $description,
                'exchange_rate' => $exchangeRate,
            ]);

        }else {
            return AdjustmentJournal::create([
                'adjustment_journal_id' => $journalId,
                'reference_code' => $referenceCode,
                'ledger_id' => $ledgerId,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'exchange_rate' => $exchangeRate,
                'date_adjusted' => $date,
                'status' => $status ?? 1,
                'user_id' => auth()->user()->user_id
            ]);
        }
    }
    public function updatePettyCashDoubleEntry($entries, $status, $description = null, $reference = null)
    {
        foreach ($entries as $entry) {
            $this->updatePettyCashEntry(
                $entry['petty_cash_id'],
                $entry['reference_code'],
                $entry['ledger_id'],
                $entry['type'],
                $entry['amount'],
                $entry['si_number'],
                $entry['exchange_rate'],
                $entry['description'],
                $entry['date_invoiced'],
                $reference,
                $status,
            );
        }
    }
    public function updatePettyCashEntry($journalId, $referenceCode, $ledgerId, $type, $amount, $siNumber, $exchangeRate, $description, $date, $status)
    {
        if (PettyCash::where('petty_cash_id', $journalId)->count() > 0) {
            return PettyCash::where('petty_cash_id', $journalId)->update([
                'ledger_id' => $ledgerId,
                'amount' => $amount,
                'description' => $description,
                'exchange_rate' => $exchangeRate,
                'date_invoiced' => $date,
            ]);

        }else {
            return PettyCash::create([
                'petty_cash_id' => $journalId,
                'reference_code' => $referenceCode,
                'ledger_id' => $ledgerId,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'exchange_rate' => $exchangeRate,
                'si_number' => $siNumber,
                'date_invoiced' => $date,
                'status' => $status ?? 1,
                'user_id' => auth()->user()->user_id
            ]);
        }
    }
    public function postEntry($journalId, $referenceCode, $ledgerId, $type, $amount, $description, $exchangeRate,  $date, $status)
    {
        return AdjustmentJournal::create([
            'adjustment_journal_id' => $journalId,
            'reference_code' => $referenceCode,
            'ledger_id' => $ledgerId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'exchange_rate' => $exchangeRate,
            'date_adjusted' => $date,
            'status' => $status ?? 1,
            'user_id' => auth()->user()->user_id
        ]);
    }
    public function postDoubleEntry($entries, $status = 1, $date = null)
    {
        foreach ($entries as $entry) {
            $this->postEntry(
                $entry['adjustment_journal_id'],
                $entry['reference_code'],
                $entry['ledger_id'],
                $entry['type'],
                $entry['amount'],
                $entry['description'],
                $entry['exchange_rate'],
                $entry['date_adjusted'],
                $status
            );
        }
    }
    public function postPettyEntry($journalId, $referenceCode, $ledgerId, $type, $amount, $description, $siNumber, $exchangeRate , $date, $status)
    {
        return PettyCash::create([
            'petty_cash_id' => $journalId,
            'reference_code' => $referenceCode,
            'ledger_id' => $ledgerId,
            'type' => $type,
            'amount' => $amount,
            'si_number' => $siNumber,
            'description' => $description,
            'date_invoiced' => $date,
            'exchange_rate' => $exchangeRate ?? 1,
            'status' => $status ?? 1,
            'user_id' => auth()->user()->user_id
        ]);
    }
    public function postPettyDoubleEntry($entries)
    {
        foreach ($entries as $entry) {
            $this->postPettyEntry(
                $entry['petty_cash_id'],
                $entry['reference_code'],
                $entry['ledger_id'],
                $entry['type'],
                $entry['amount'],
                $entry['description'],
                $entry['si_number'],
                $entry['exchange_rate'],
                $entry['date_adjusted'],
                $entry['status']
            );
        }
    }
    public function yearlyTrialBalance($id)
    {
        $financial = FinancialYear::where('financial_year_id', $id)->first();
        $transactions = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin('transactions', 'transactions.account_id', '=', 'client_accounts.client_account_id')
            ->select(
                'client_accounts.client_account_id',
                DB::raw("MIN(client_accounts.client_account_name) AS client_account_name"),
                DB::raw("MIN(accounts.account_name) AS account_name"),
                DB::raw("MIN(chart_of_accounts.chart_number) AS chart_number"),
                DB::raw("MIN(chart_of_accounts.chart_name) AS chart_name"),
                DB::raw("MIN(currencies.currency_symbol) AS currency_symbol"),
                // Summing up debit values
                DB::raw("
                    SUM(
                        CASE
                            WHEN currencies.priority = 2 THEN
                                CASE
                                    WHEN transactions.exchange_rate IS NOT NULL THEN
                                        transactions.amount_received * transactions.exchange_rate
                                    ELSE
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                              AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ) * transactions.amount_received
                                END
                            ELSE transactions.amount_received
                        END
                    ) AS debit
                "),
                // Summing up credit values (if needed)
                DB::raw("0.00 AS credit"),
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('transactions.deleted_at')
            ->groupBy('client_accounts.client_account_id', 'account_name', 'client_account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $clients = DB::table('transactions')
            ->join('client_accounts', function ($join) {
                $join->on('client_accounts.client_account_id', '=', 'transactions.client_id');
            })
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies as client_currency', 'client_currency.currency_id', '=', 'client_accounts.currency_id')
            ->join('client_accounts as account', function ($join) {
                $join->on('account.client_account_id', '=', 'transactions.account_id');
            })
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->leftJoin(DB::raw('(SELECT currency_id FROM currencies WHERE priority != 1 LIMIT 1) as priority_currency'), function ($join) {
                $join->on('priority_currency.currency_id', '=', 'client_accounts.currency_id')
                    ->orOn('priority_currency.currency_id', '=', 'account.currency_id');
            })
            ->leftJoin('forex_exchanges', function ($join) {
                $join->on('forex_exchanges.currency_id', '=', 'priority_currency.currency_id')
                    ->whereRaw('forex_exchanges.date_active = (
                SELECT MAX(fx.date_active)
                FROM forex_exchanges AS fx
                WHERE fx.date_active <= FROM_UNIXTIME(transactions.date_received)
                AND fx.currency_id = priority_currency.currency_id
            )');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("
            SUM(
                CASE
                    WHEN account.currency_id = client_accounts.currency_id THEN transactions.amount_received
                    WHEN transactions.exchange_rate IS NULL THEN
                        CASE
                            WHEN client_currency.priority = 1 THEN transactions.amount_received * COALESCE(forex_exchanges.exchange_rate, 1)
                            ELSE transactions.amount_received / COALESCE(forex_exchanges.exchange_rate, 1)
                        END
                    ELSE
                        CASE
                            WHEN client_currency.priority = 1 THEN transactions.amount_received * transactions.exchange_rate
                            ELSE transactions.amount_received / transactions.exchange_rate
                        END
                END
            ) AS credit
        "),
                'account.client_account_name as ledger_name',
                'account.currency_id as account_currency_id',
                'client_accounts.currency_id as client_currency_id'
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('transactions.deleted_at')
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                'account.client_account_name',
                'account.currency_id',
                'client_accounts.currency_id'
            )
            ->get();

        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND invoices.type = 1 THEN amount_due
                    WHEN currencies.priority = 2 AND invoices.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    ) AS debit"),
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND invoices.type = 2 THEN amount_due
                    WHEN currencies.priority = 2 AND invoices.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    ) AS credit")
            )
            ->where('invoices.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('invoices.deleted_at')
            ->get();

        $payments = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('payments', function ($join) use ($id) {
                $join->on('payments.account_id', '=', 'client_accounts.client_account_id')
                    ->where('payments.financial_year_id', $id)
                    ->whereNull('payments.deleted_at');
            })
            ->select(
                'client_accounts.client_account_id',
                DB::raw("MIN(client_accounts.client_account_name) AS client_account_name"),
                DB::raw("MIN(accounts.account_name) AS account_name"),
                DB::raw("MIN(chart_of_accounts.chart_number) AS chart_number"),
                DB::raw("MIN(chart_of_accounts.chart_name) AS chart_name"),
                DB::raw("MIN(currencies.currency_symbol) AS currency_symbol"),
                // Summing up credit values (if needed)
                DB::raw("0.00 AS debit"),
                // Summing up debit values
                DB::raw("
                    SUM(
                        CASE
                            WHEN payments.exchange_rate IS NOT NULL
                            THEN payments.amount_received * payments.exchange_rate
                            WHEN currencies.priority = 2
                            THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * payments.amount_received
                            ELSE payments.amount_received
                        END
                    ) AS credit
                ")
            )
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('payments.deleted_at')
            ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND purchases.type = 2 THEN amount_due
                    WHEN currencies.priority = 2 AND purchases.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    ) AS debit"),

                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND purchases.type = 1 THEN amount_due
                    WHEN currencies.priority = 2 AND purchases.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    ) AS credit"),
            )
            ->where('purchases.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('purchases.deleted_at')
            ->get();

        $incomes = InvoiceItem::join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id') // ledger
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id') // ledger currency
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'invoices.client_id') // client
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // client currency
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN invoices.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS credit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN invoices.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS debit
                ")
            )
            ->where('invoices.financial_year_id', $id)
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('client_accounts.client_account_name')
            ->whereNull('invoice_items.deleted_at')
            ->get();


        $expenses = PurchaseItem::join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'purchases.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id')
            ->select('client_accounts.client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currencies.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN purchases.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS debit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN purchases.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS credit
                ")
            )
            ->where('purchases.financial_year_id', $id)
            ->groupBy('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_name')
            ->orderBy('client_account_name')
            ->whereNull('purchase_items.deleted_at')
            ->get();

        $crossPayments = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id') // client currency
            ->leftJoin('payments', function ($join) use ($id) {
                $join->on('payments.client_id', '=', 'client_accounts.client_account_id')
                    ->where('payments.financial_year_id', $id)
                    ->whereNull('payments.deleted_at');
            })
            ->leftJoin('client_accounts as acc', 'payments.account_id', '=', 'acc.client_account_id')
            ->leftJoin('currencies as curr', 'acc.currency_id', '=', 'curr.currency_id') // account currency
            ->select(
                'client_accounts.client_account_id',
                DB::raw("MIN(client_accounts.client_account_name) AS client_account_name"),
                DB::raw("MIN(accounts.account_name) AS account_name"),
                DB::raw("MIN(chart_of_accounts.chart_number) AS chart_number"),
                DB::raw("MIN(chart_of_accounts.chart_name) AS chart_name"),
                DB::raw("MIN(currencies.currency_symbol) AS currency_symbol"),
                DB::raw("0.00 AS credit"),
                DB::raw("
            SUM(
                CASE
                    WHEN payments.exchange_rate IS NOT NULL THEN payments.amount_received * payments.exchange_rate
                    WHEN currencies.priority = 2 THEN (
                        SELECT fx1.exchange_rate
                        FROM forex_exchanges fx1
                        WHERE fx1.currency_id = currencies.currency_id
                        AND fx1.date_active <= FROM_UNIXTIME(payments.date_received)
                        ORDER BY fx1.date_active DESC
                        LIMIT 1
                    ) * payments.amount_received
                    WHEN curr.priority = 2 THEN (
                        SELECT fx2.exchange_rate
                        FROM forex_exchanges fx2
                        WHERE fx2.currency_id = curr.currency_id
                        AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                        ORDER BY fx2.date_active DESC
                        LIMIT 1
                    ) * payments.amount_received
                    ELSE payments.amount_received
                END
            ) AS debit
        ")
            )
            ->groupBy(
                'client_accounts.client_account_id',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('client_accounts.client_account_name')
            ->whereNull('payments.deleted_at')
            ->get();

        $journals = DB::table('adjustment_journals as aj1')
            ->join('adjustment_journals as aj2', function ($join) {
                $join->on('aj1.reference_code', '=', 'aj2.reference_code')
                    ->whereRaw('aj1.type != aj2.type');
            })
            ->join('client_accounts as account1', 'aj1.ledger_id', '=', 'account1.client_account_id')
            ->join('client_accounts as account2', 'aj2.ledger_id', '=', 'account2.client_account_id')
            ->join('currencies as cur1', 'account1.currency_id', '=', 'cur1.currency_id')
            ->join('currencies as cur2', 'account2.currency_id', '=', 'cur2.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'account1.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'account1.client_account_id',
                'account1.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur1.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN aj1.type = 1 THEN
                                CASE
                                    WHEN cur1.priority = 2 OR cur2.priority = 2 THEN
                                        CASE
                                            WHEN aj2.exchange_rate IS NOT NULL AND aj2.exchange_rate != 1 THEN aj1.amount * aj2.exchange_rate
                                            ELSE COALESCE(
                                                (
                                                    SELECT fx2.exchange_rate
                                                    FROM forex_exchanges AS fx2
                                                     WHERE (fx2.currency_id = account2.currency_id OR fx2.currency_id = account1.currency_id)
                                                    AND fx2.date_active <= FROM_UNIXTIME(aj1.date_adjusted)
                                                    ORDER BY fx2.date_active DESC
                                                    LIMIT 1
                                                ), 1
                                            ) * aj1.amount
                                        END
                                    ELSE aj1.amount
                                END
                            ELSE 0
                        END
                    ) as debit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN aj1.type = 2 THEN
                                CASE
                                    WHEN cur1.priority = 2 OR cur2.priority = 2 THEN
                                        CASE
                                            WHEN aj2.exchange_rate IS NOT NULL AND aj2.exchange_rate != 1 THEN aj1.amount * aj2.exchange_rate
                                            ELSE COALESCE(
                                                (
                                                    SELECT fx2.exchange_rate
                                                    FROM forex_exchanges AS fx2
                                                     WHERE (fx2.currency_id = account2.currency_id OR fx2.currency_id = account1.currency_id)
                                                    AND fx2.date_active <= FROM_UNIXTIME(aj1.date_adjusted)
                                                    ORDER BY fx2.date_active DESC
                                                    LIMIT 1
                                                ), 1
                                            ) * aj1.amount
                                        END
                                    ELSE aj1.amount
                                END
                            ELSE 0
                        END
                    ) as credit
                "),
            )
            ->whereNull('aj1.deleted_at')
            ->whereNull('aj2.deleted_at')
            ->whereBetween('aj1.date_adjusted', [strtotime($financial->year_starting), strtotime($financial->year_ending)])
            ->groupBy(
                'account1.client_account_id',
                'account1.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur1.currency_symbol'
            )
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('account1.client_account_name')
            ->get();

        $cashes = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('petty_cashes', function ($join) use ($financial) {
                $join->on('petty_cashes.ledger_id', '=', 'client_accounts.client_account_id')
                    ->where('date_invoiced', '>=', strtotime($financial->year_starting))
                    ->where('date_invoiced', '<=', strtotime($financial->year_ending))
                    ->whereNull('petty_cashes.deleted_at');
            })
            ->select(
                'client_accounts.client_account_id',
                DB::raw("MIN(client_accounts.client_account_name) AS client_account_name"),
                DB::raw("MIN(accounts.account_name) AS account_name"),
                DB::raw("MIN(chart_of_accounts.chart_number) AS chart_number"),
                DB::raw("MIN(chart_of_accounts.chart_name) AS chart_name"),
                DB::raw("MIN(currencies.currency_symbol) AS currency_symbol"),

                // DEBIT logic
                DB::raw("
            SUM(
                CASE
                    WHEN petty_cashes.type = 2 AND currencies.priority = 2
                    THEN
                        CASE
                            WHEN petty_cashes.exchange_rate IS NOT NULL
                            THEN petty_cashes.amount * petty_cashes.exchange_rate
                            ELSE COALESCE(
                                (
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = client_accounts.currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1
                            ) * petty_cashes.amount
                        END
                    WHEN petty_cashes.type = 2 AND currencies.priority = 1
                    THEN petty_cashes.amount
                    ELSE 0
                END
            ) AS debit
        "),

                // CREDIT logic
                DB::raw("
            SUM(
                CASE
                    WHEN petty_cashes.type = 1 AND currencies.priority = 2
                    THEN
                        CASE
                            WHEN petty_cashes.exchange_rate IS NOT NULL
                            THEN petty_cashes.amount * petty_cashes.exchange_rate
                            ELSE COALESCE(
                                (
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = client_accounts.currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1
                            ) * petty_cashes.amount
                        END
                    WHEN petty_cashes.type = 1 AND currencies.priority = 1
                    THEN petty_cashes.amount
                    ELSE 0
                END
            ) AS credit
        ")
            )
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('petty_cashes.deleted_at')
            ->get();

        $balances = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currency_symbol',
                // Debit Calculation with Currency Conversion
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 1 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 1 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS debit
                "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 2 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 2 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS credit
                ")
            )
            ->where('opening_balances.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('opening_balances.deleted_at')
            ->get();

        $opening = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.ledger_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'opening_balances.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // Fixed alias reference
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name', // Ensure full reference
                'accounts.account_name', // Ensure full reference
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                // Debit Calculation with Currency Conversion
                DB::raw("
            SUM(
                CASE
                    WHEN opening_balances.type = 1 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 1 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
            ) AS credit
        "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
            SUM(
                CASE
                    WHEN opening_balances.type = 2 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 2 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
            ) AS debit
        ")
            )
            ->where('opening_balances.financial_year_id', $id)
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('opening_balances.deleted_at')
            ->get();

        $combinedResults = collect([])
            ->merge($transactions)
            ->merge($payments)
            ->merge($incomes)
            ->merge($expenses)
            ->merge($purchases)
            ->merge($clients)
            ->merge($invoices)
            ->merge($crossPayments)
            ->merge($journals)
            ->merge($balances)
            ->merge($opening)
            ->merge($cashes)->sortBy(function ($item) {
                // Sorting by account_name first, then chart_name
                return [$item->chart_name];
            });

        $accounts = $combinedResults->groupBy('chart_name')->map(function ($group, $chartName) {
            $firstItem = (object) $group->first(); // Ensure it's an object

            // Correctly sum debit & credit for the entire chart group
            $totalDebit = $group->sum('debit');
            $totalCredit = $group->sum('credit');
            $balance = $totalDebit - $totalCredit;

            return [
                'chart_name' => $chartName,
                'chart_number' => $firstItem->chart_number,
                'debit' => $balance > 0 ? $balance : 0.00,
                'credit' => $balance < 0 ? abs($balance) : 0.00,
                'ledgers' => $group->groupBy('client_account_id')->map(function ($ledgerGroup) {
                    $ledgerFirst = (object) $ledgerGroup->first(); // Ensure it's an object

                    return [
                        'client_account_id' => $ledgerFirst->client_account_id,
                        'client_account_name' => $ledgerFirst->client_account_name,
                        'currency_symbol' => $ledgerFirst->currency_symbol,
                        'debit' => $ledgerGroup->sum('debit'),  // Sum debit per ledger
                        'credit' => $ledgerGroup->sum('credit'), // Sum credit per ledger
                    ];
                })->values(),
            ];
        })->values();

        return $accounts;
    }
    public function dayBookReport($startDate, $endDate, $type)
    {
        $transactions = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.client_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'transactions.user_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('invoice_number as ref_number', 'si_number',
                DB::raw("DATE(transactions.created_at) as transaction_date"),
                'client_account_name as ledger_name',
                'transactions.description',
                DB::raw("'Receipt' as transaction_type"),
                DB::raw("'0.00' as debit"),
                DB::raw("(
                        CASE
                            WHEN transactions.exchange_rate IS NOT NULL
                            THEN transactions.amount_received * transactions.exchange_rate
                            WHEN currencies.priority = 2
                            THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * transactions.amount_received
                            ELSE transactions.amount_received
                        END
                    ) AS credit
                "),
                DB::raw("CONCAT(LEFT(first_name, 1),'. ', surname) AS user_name")
            )
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->orderBy('transactions.created_at', 'desc')
            ->orderBy('transactions.invoice_number', 'desc')
            ->whereNull('transactions.deleted_at')
            ->get();

        $payments = Payment::join('client_accounts', 'client_accounts.client_account_id', '=', 'payments.client_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'payments.user_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('invoice_number as ref_number', 'si_number',
                DB::raw("DATE(payments.created_at) as transaction_date"),
                'client_account_name as ledger_name', 'payments.description',
                DB::raw("'Payment' as transaction_type"),
                DB::raw("'0.00' as credit"),
                DB::raw("(CASE
                            WHEN payments.exchange_rate IS NOT NULL
                            THEN payments.amount_received * payments.exchange_rate
                            WHEN currencies.priority = 2
                            THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * payments.amount_received
                            ELSE payments.amount_received
                        END
                    ) AS debit
                "),
                DB::raw("CONCAT(LEFT(first_name, 1),'. ', surname) AS user_name"))
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->orderBy('payments.created_at', 'desc')
            ->orderBy('payments.invoice_number', 'desc')
            ->whereNull('payments.deleted_at')
            ->get();

        $cash = PettyCash::join('client_accounts', 'client_accounts.client_account_id', '=', 'petty_cashes.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'petty_cashes.user_id')
            ->select(
                'reference_code as ref_number', 'si_number',
                DB::raw("DATE(petty_cashes.created_at) as transaction_date"),
                'client_account_name as ledger_name',
                'petty_cashes.description',
                DB::raw("'Petty Cash' as transaction_type"),
                // Debit Calculation
                DB::raw("
                    CASE
                        WHEN petty_cashes.type = 1 THEN
                            petty_cashes.amount * COALESCE(
                                (SELECT fx2.exchange_rate
                                 FROM forex_exchanges AS fx2
                                 WHERE fx2.currency_id = client_accounts.currency_id
                                 AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                 ORDER BY fx2.date_active DESC
                                 LIMIT 1), 1)
                        ELSE 0
                    END AS debit
                "),
                // Credit Calculation
                DB::raw("
                    CASE
                        WHEN petty_cashes.type = 2 THEN
                            petty_cashes.amount * COALESCE(
                                (SELECT fx2.exchange_rate
                                 FROM forex_exchanges AS fx2
                                 WHERE fx2.currency_id = client_accounts.currency_id
                                 AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                 ORDER BY fx2.date_active DESC
                                 LIMIT 1), 1)
                        ELSE 0
                    END AS credit
                "),
                // User Name Formatting (First Initial + Surname)
                DB::raw("CONCAT(LEFT(first_name, 1), '. ', surname) AS user_name")
            )
            ->whereBetween('petty_cashes.created_at', [$startDate, $endDate])
            ->whereNull('petty_cashes.deleted_at')
            ->orderBy('petty_cashes.created_at', 'desc')
            ->orderBy('petty_cashes.reference_code', 'desc')
            ->get();


        $journals = AdjustmentJournal::join('client_accounts', 'client_accounts.client_account_id', '=', 'adjustment_journals.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'adjustment_journals.user_id')
            ->select('reference_code as ref_number', DB::raw("'' as si_number"),
                DB::raw("DATE(adjustment_journals.created_at) as transaction_date"),
                'client_account_name as ledger_name',
                'adjustment_journals.description',
                DB::raw("'Journal Entry' as transaction_type"),
                DB::raw("(
                        CASE
                            WHEN adjustment_journals.type = 1 THEN
                                COALESCE(
                                    (SELECT aj.exchange_rate
                                     FROM adjustment_journals AS aj
                                     WHERE aj.reference_code = adjustment_journals.reference_code
                                     AND aj.type = 2
                                     LIMIT 1),
                                    1
                                ) * adjustment_journals.amount
                            WHEN adjustment_journals.type = 1 AND currencies.priority = 1 THEN adjustment_journals.amount
                            ELSE 0
                        END
                    ) AS debit
                "),
                // Summing up credit values (ensuring NULL values become 0)
                DB::raw("(
                        CASE
                            WHEN adjustment_journals.type = 2 THEN adjustment_journals.amount * adjustment_journals.exchange_rate
                            ELSE 0
                        END
                    ) AS credit
                "),
                DB::raw("CONCAT(LEFT(first_name, 1),'. ', surname) AS user_name")
            )
            ->whereBetween('adjustment_journals.created_at', [$startDate, $endDate])
            ->orderBy('adjustment_journals.created_at', 'desc')
            ->orderBy('adjustment_journals.reference_code', 'desc')
            ->whereNull('adjustment_journals.deleted_at')
            ->get();

        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'invoices.user_id')
            ->select(
                'invoices.invoice_number as ref_number', 'si_number',
                DB::raw("DATE(invoices.created_at) as transaction_date"),
                'client_account_name as ledger_name',
                'invoices.customer_message as description',
                DB::raw("'Sales' as transaction_type"),
                DB::raw("(CASE
                            WHEN invoices.type = 1 AND priority = 2 THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * amount_due
                            WHEN invoices.type = 1 AND currencies.priority = 1 THEN invoices.amount_due
                            ELSE 0
                        END
                    ) AS debit
                "),
                DB::raw("(CASE
                            WHEN invoices.type = 2 AND priority = 2 THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * amount_due
                            WHEN invoices.type = 2 AND currencies.priority = 1 THEN invoices.amount_due
                            ELSE 0
                        END
                    ) AS credit
                "),
                DB::raw("CONCAT(LEFT(first_name, 1),'. ', surname) AS user_name")
            )
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->orderBy('invoices.created_at', 'desc')
            ->orderBy('invoices.invoice_number', 'desc')
            ->whereNull('invoices.deleted_at')
            ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'purchases.user_id')
            ->select(
                'purchases.voucher_number as ref_number', 'invoice_number as si_number',
                DB::raw("DATE(purchases.created_at) as transaction_date"),
                'client_account_name as ledger_name',
                'purchases.customer_message as description',
                DB::raw("'Purchases' as transaction_type"),
                DB::raw("(CASE
                            WHEN purchases.type = 1 AND priority = 2 THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * amount_due
                            WHEN purchases.type = 1 AND currencies.priority = 1 THEN purchases.amount_due
                            ELSE 0
                        END
                    ) AS credit
                "),
                DB::raw("(CASE
                            WHEN purchases.type = 2 AND priority = 2 THEN (
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = client_accounts.currency_id
                                AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ) * amount_due
                            WHEN purchases.type = 2 AND currencies.priority = 1 THEN purchases.amount_due
                            ELSE 0
                        END
                    ) AS debit
                "),
                DB::raw("CONCAT(LEFT(first_name, 1),'. ', surname) AS user_name")
            )
            ->whereBetween('purchases.created_at', [$startDate, $endDate])
            ->orderBy('purchases.created_at', 'desc')
            ->orderBy('purchases.invoice_number', 'desc')
            ->whereNull('purchases.deleted_at')
            ->get();

        $daybook = collect([])->merge($transactions)->merge($payments)->merge($cash)->merge($journals)->merge($invoices)->merge($purchases);

//        dd($type);

        if ($type !== null) {
            $daybook = $daybook->where('transaction_type', $type);
        }

        return $daybook;

    }
    public function transportSummary($startOfMonth, $endOfMonth, $report, $transporter)
    {
        $collections = StockIn::join('delivery_orders', function ($join){
            $join->on('delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
                ->whereNull('delivery_orders.deleted_at');
            })
            ->join('drivers', function ($join){
                $join->on('drivers.driver_id', '=', 'stock_ins.driver_id')
                    ->whereNull('drivers.deleted_at');
            })
            ->join('loading_instructions', function ($join){
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->join('transporters', function ($join){
                $join->on('transporters.transporter_id', '=', 'stock_ins.transporter_id')
                    ->whereNull('transporters.deleted_at');
            })
            ->join('clients', function ($join){
                $join->on('clients.client_id', '=', 'delivery_orders.client_id')
                    ->whereNull('clients.deleted_at');
            })
            ->join('warehouses', function ($join){
                $join->on('warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
                    ->whereNull('warehouses.deleted_at');
            })
            ->join('sub_warehouses', function ($join){
                $join->on('sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
                    ->whereNull('sub_warehouses.deleted_at');
            })
            ->join('stations', function ($join){
                $join->on('stations.station_id', '=', 'stock_ins.station_id')
                    ->whereNull('stations.deleted_at');
            })
            ->whereBetween('stock_ins.date_received', [$startOfMonth, $endOfMonth])
            ->select('total_pallets', 'stock_ins.total_weight', 'date_received', DB::raw("'COLLECTION' as delivery_type"), 'stock_ins.transporter_id', 'stock_ins.registration', 'driver_name', 'id_number', 'loading_number', 'transporter_name', 'client_name', 'invoice_number', 'warehouse_name', 'sub_warehouse_name', 'station_name', 'locality')
            ->orderBy('date_received', 'desc')
            ->where(['stock_ins.delivery_type' => 1])
            ->get();

        $transfers = StockIn::join('transfers', function ($join) {
            $join->on('transfers.delivery_id', '=', 'stock_ins.delivery_id')
                ->on('transfers.delivery_number', '=', 'stock_ins.delivery_number')
                ->whereNull('transfers.deleted_at');
        })
            ->join('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
                    ->whereNull('delivery_orders.deleted_at');
            })
            ->join('clients', function ($join) {
                $join->on('clients.client_id', '=', 'delivery_orders.client_id')
                    ->whereNull('clients.deleted_at');
            })
            ->join('transporters', function ($join) {
                $join->on('transporters.transporter_id', '=', 'transfers.transporter_id')
                    ->whereNull('transporters.deleted_at');
            })
            ->join('drivers', function ($join) {
                $join->on('drivers.driver_id', '=', 'transfers.driver_id')
                    ->whereNull('drivers.deleted_at');
            })
            ->join('stations as trs', function ($join) {
                $join->on('trs.station_id', '=', 'transfers.station_id')
                ->whereNull('trs.deleted_at');
            })
            ->join('warehouse_locations', function ($join) {
                $join->on('warehouse_locations.location_id', '=', 'trs.location_id');
            })
            ->join('stations', function ($join) {
                $join->on('stations.station_id', '=', 'stock_ins.station_id')
                    ->whereNull('stations.deleted_at');
            })
            ->where('stock_ins.delivery_type', 2)
            ->whereBetween('stock_ins.date_received', [$startOfMonth, $endOfMonth])
            ->select(
                'stock_ins.total_pallets',
                DB::raw('CAST(stock_ins.total_weight AS DECIMAL(10,2)) AS total_weight'),
                'stock_ins.date_received',
                DB::raw("'TRANSFER' as delivery_type"),
                'stock_ins.transporter_id',
                'stock_ins.registration',
                'drivers.driver_name',
                'drivers.id_number',
                'transfers.delivery_number as loading_number',
                'transporters.transporter_name',
                'clients.client_name',
                'delivery_orders.invoice_number',
                DB::raw('NULL as sub_warehouse_name'),
                'trs.station_name as warehouse_name',
                'warehouse_locations.location_name as locality',
                'stations.station_name'
            )
            ->orderBy('stock_ins.date_received', 'desc')
            ->get();

        $query = collect([])->merge($transfers)->merge($collections);

        if ($transporter != null) {
            $query = $query->where('transporter_id', $transporter);
        }

        if ($report != null) {
            $query = $query->where('delivery_type', $report);
        }

        return $query;

    }
    public function downloadVerifiedReport($id)
    {
        $request = ReportRequest::find($id);
        $image = $request->service_number.'_'.time().'.png';

        if($request->request_type === 1){
            $data = DB::table('currentstock')
                ->select('invoice_number', 'garden_name', 'grade_name', 'sale_number', 'current_weight', 'current_stock', 'package_tare', 'pallet_weight', 'loading_number', 'order_number', 'date_received', 'warehouse_name', 'delivery_number', 'stock_date', 'client_id', 'client_name', 'delivery_id', 'allocated_packages', 'allocated_weight')
                ->where('current_stock', '>', 0)
                ->where('current_weight', '>', 0)
                ->where(['client_id' => $request->client_id])
                ->orderBy('garden_name', 'asc');

            if ($request->request_number !== null){
                $data->where('delivery_number', $request->request_number);
            }

            if ($request->date_from !== null){
                $data->where('stock_date', '>=', $request->date_from);
            }

            if ($request->date_to !== null){
                $data->where('stock_date', '<=', $request->date_to);
            }

            $reports = $data->get();
            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;
            $client = Client::where('client_id', $request->client_id)->first();

            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.Carbon::parse($request->date_to)->format('d-m-Y');
            }else{
                $period = 'FOR PERIOD BETWEEN '.Carbon::parse($request->date_from)->format('d-m-Y').' AND '.Carbon::parse($request->date_to)->format('d-m-Y');
            }

            if ($reports->count() == 0) {
                    $totalWeight = 0;
                    $totalStock = 0;

                    $qrCodePath = 'Files/QrCodes/'.$image;
                    if ($request->approved_by !== null){
                        $approved = UserInfo::find($request['approved_by']);
                        \QrCode::size(1000)
                            ->format('png')
                            ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                                'REPORT TYPE: ' . 'CURRENT STOCK POSITION' . "\n" .
                                'CLIENT NAME: ' . $client->client_name. "\n" .
                                'REPORTING: ' . $period . "\n" .
                                'TOTAL PACKAGES: ' . number_format($totalStock). "\n" .
                                'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                                'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                                'Files/QrCodes/'.$image);
                    }

                    // Create the PDF structure
                    $mpdf = new \Mpdf\Mpdf([
                        'mode' => 'utf-8',
                        'format' => 'A4-L',
                        'orientation' => 'L',
                        'margin_top' => 2,
                        'margin_bottom' => 7,
                        'margin_left' => 5,
                        'margin_right' => 5,
                        'tempDir' => storage_path('app/mpdf_temp'),
                        'pcre.backtrack_limit' => '10000000'
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

                    // Add company header
                    $logoPath = 'assets/img/favicons/icon.png';
                    $companyHeader = '
                    <style>
                        .company-container {
                            position: relative;
                            width: 100%;
                            display: flex;
                            justify-content: flex-start;
                            align-items: flex-start;
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
                        .qr-container {
                            position: absolute;
                            top: 0;
                            right: 0;
                            padding: 10px;
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
                        .no-stock-message {
                            text-align: center;
                            font-size: 14px;
                            font-weight: bold;
                            color: #333;
                            margin: 20px 0;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 10px;
                        }
                        th {
                            background-color: #f0f0f0;
                            border: 1px solid #000;
                            padding: 8px;
                            text-align: left;
                            font-size: 10px;
                        }
                    </style>
                    <table>
                    <tr>
                        <td class="address" style="width: 90% !important;">
                        <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                        <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                        <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
                        </td>
                        <td class="qr-code" style="width: 10% !important;">
                            ' . ($request->approved_by === null
                            ? '<span class="verification">NOT APPROVED </span>'
                            : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                        </td>
                    </tr>
                    </table>

                    <div class="header">' . $period . '<hr></div>';

                    $mpdf->WriteHTML($companyHeader);

                    // Render the view with empty data to show table headers and "no stock" message
                    $html = View::make('clerk::downloads.current_stock', [
                        'clientName' => $client->client_name,
                        'orders' => collect([]), // Empty collection
                        'by' => $by,
                        'printed' => $printed,
                        'noStock' => true // Add this flag to indicate no stock
                    ])->render();

                    $mpdf->WriteHTML($html);

                    if (file_exists('Files/QrCodes/' . $image)){
                        unlink('Files/QrCodes/' . $image);
                    }

                    // Output PDF
                    $pdfFileName = 'STOCK POSITION.pdf';
                    return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
                    ]);
            }else{
                $totalWeight = $data->sum('current_weight');
                $totalStock = $data->sum('current_stock');

                $qrCodePath = 'Files/QrCodes/'.$image;
                if ($request->approved_by !== null){
                    $approved = UserInfo::find($request['approved_by']);
                    \QrCode::size(1000)
                        ->format('png')
                        ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                            'REPORT TYPE: ' . 'CURRENT STOCK POSITION' . "\n" .
                            'CLIENT NAME: ' . $reports->first()->client_name. "\n" .
                            'REPORTING: ' . $period . "\n" .
                            'TOTAL PACKAGES: ' . number_format($totalStock). "\n" .
                            'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                            'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                            'Files/QrCodes/'.$image);
                }
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'orientation' => 'L',
                'margin_top' => 2,
                'margin_bottom' => 7,
                'margin_left' => 5,
                'margin_right' => 5,
                'tempDir' => storage_path('app/mpdf_temp'),
                'pcre.backtrack_limit' => '10000000'
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 90% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
                </td>
                <td class="qr-code" style="width: 10% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>

            <div class="header">' . $period . '<hr></div>';

            $mpdf->WriteHTML($companyHeader);

            // Process data in smaller chunks
            $chunks = array_chunk($reports->toArray(), 1000); // Reduced from 100 to 50 for smaller chunks

            foreach ($chunks as $chunk) {
                // Render each client group separately
                $groupedChunk = collect($chunk)->groupBy('client_name');

                foreach ($groupedChunk as $clientName => $clientOrders) {
                    $html = View::make('clerk::downloads.current_stock', [
                        'clientName' => $clientName ?? $client->client_name,
                        'orders' => $clientOrders,
                        'by' => $by,
                        'printed' => $printed
                    ])->render();

                    $mpdf->WriteHTML($html);
                }
            }

            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }

            // Output PDF
            $pdfFileName = 'STOCK POSITION.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);

        }elseif ($request->request_type == 2){
            $data = DB::table('blendBalances')
                ->where('current_packages', '>', 0)
                ->where('current_weight', '>', 0)
                ->whereRaw("type COLLATE utf8mb4_unicode_ci != ?", ['SIEVED DUST'])
                ->where(['client_id' => $request->client_id])
                ->orderBy('garden', 'asc');
            if ($request->request_number !== null){
                $data->where('blend_number', $request->request_number);
            }
            if ($request->date_from !== null){
                $data->where('blend_date', '>=', $request->date_from);
            }
            if ($request->date_to !== null){
                $data->where('blend_date', '<=', $request->date_to);
            }
            $reports = $data->get();
            $totalPackets = $reports->sum('current_packages');
            $totalWeight = $reports->sum('current_weight');
            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;
            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.$request->date_to;
            }else{
                $period = 'FOR PERIOD BETWEEN '.$request->date_from.' AND '.$request->date_to;
            }
            if ($request['approved_by'] !== null){
                $approved = UserInfo::find($request['approved_by']);
                \QrCode::size(1000)
                    ->format('png')
                    ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                        'REPORT TYPE: ' . 'CURRENT BLEND BALANCES POSITION' . "\n" .
                        'CLIENT NAME: ' . $reports->first()->client_name . "\n" .
                        'REPORTING: ' . $period . "\n" .
                        'TOTAL PACKAGES: ' . number_format($totalPackets, 2). "\n" .
                        'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                        'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                        'Files/QrCodes/'.$image);
            }
            $qrCodePath = 'Files/QrCodes/'.$image;
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-P', // Landscape
                'orientation' => 'P',
                'margin_top' => 2,
                'margin_bottom' => 7,
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
                    width: 100px !important;
                    height: 100px !important;
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 87% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p> <small> Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</small></p>
                </td>
                <td class="qr-code" style="width: 13% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>
            <div class="header">' . $period . '<hr></div>';
            $mpdf->WriteHTML($companyHeader);
            // Process data in smaller chunks
            $chunks = array_chunk($reports->toArray(), 1000); // Reduced from 100 to 50 for smaller chunks
            foreach ($chunks as $chunk) {
                // Render each client group separately
                $groupedChunk = collect($chunk)->groupBy('client_name');

                foreach ($groupedChunk as $clientName => $clientOrders) {
                    $html = View::make('clerk::downloads.current_blend_balances', [
                        'clientName' => $clientName,
                        'orders' => $clientOrders,
                        'by' => $by,
                        'printed' => $printed
                    ])->render();

                    $mpdf->WriteHTML($html);
                }
            }
            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }
            // Output PDF
            $pdfFileName = 'BLEND BALANCE IN STOCK.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);

        }elseif ($request->request_type == 3){
            $data = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
                ->join('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
                ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
                ->join('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
                ->select('shipping_number', 'consignee', 'vessel_name', 'client_name', 'shipping_mark', 'container_number', 'ship_date', 'clients.client_name', 'clearing_agents.agent_name', 'destinations.port_name')
                ->selectRaw("SUM(CAST(REPLACE(REPLACE(shipments.shipped_packages, ',', ''), '.00', '') AS UNSIGNED)) AS packagesShipped")
                ->selectRaw("SUM(CAST(REPLACE(REPLACE(shipments.shipped_weight, ',', ''), '.00', '') AS UNSIGNED))  as weightShipped")
                ->groupBy('shipping_number', 'consignee', 'vessel_name', 'client_name', 'shipping_mark', 'container_number', 'ship_date', 'agent_name', 'port_name')
                ->where(['shipping_instructions.client_id' => $request->client_id])
                ->orderBy('shipping_number', 'asc');

            if ($request->request_number !== null){
                $data->where('shipping_number', $request->request_number);
            }
            if ($request->date_from !== null){
                $data->where('ship_date', '>=', strtotime($request->date_from));
            }
            if ($request->date_to !== null){
                $data->where('ship_date', '<=', strtotime($request->date_to));
            }
            $reports = $data->get();
            $totalPackets = $reports->sum('packagesShipped');
            $totalWeight = $reports->sum('weightShipped');

            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;
            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.$request->date_to;
            }else{
                $period = 'FOR PERIOD BETWEEN '.$request->date_from.' AND '.$request->date_to;
            }
            if ($request->approved_by !== null){
                $approved = UserInfo::find($request->approved_by);
                \QrCode::size(300)
                    ->format('png')
                    ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                        'REPORT TYPE: ' . 'STRAIGHT LINE REPORT' . "\n" .
                        'CLIENT NAME: ' . $reports[0]['client_name'] . "\n" .
                        'REPORTING: ' . $period . "\n" .
                        'TOTAL PACKAGES: ' . number_format($totalPackets, 2). "\n" .
                        'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                        'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                        'Files/QrCodes/'.$image);
            }
            $qrCodePath = 'Files/QrCodes/'.$image;
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Landscape
                'orientation' => 'L',
                'margin_top' => 2,
                'margin_bottom' => 7,
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 90% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
                </td>
                <td class="qr-code" style="width: 10% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>

            <div class="header">' . $period . '<hr></div>';

            $mpdf->WriteHTML($companyHeader);
            $clientName = $reports->first()->client_name;
            $html = View::make('clerk::downloads.straight_line', [
                'clientName' => $clientName,
                'orders' => $reports,
                'by' => $by,
                'printed' => $printed
            ])->render();

            $mpdf->WriteHTML($html);

            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }

            // Output PDF
            $pdfFileName = 'STRAIGHT LINE JOBS.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);

        }elseif ($request->request_type == 4){
            $data = BlendSheet::join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
                ->join('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
                ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
                ->join('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
                ->select('client_name', 'port_name', 'agent_name', 'blend_number', 'consignee', 'shipping_mark', 'vessel_name', 'garden', 'blend_shipped')
                ->selectRaw('SUM(blend_teas.blended_packages) as blendedPackages')
                ->selectRaw('SUM(blend_teas.blended_weight) as blendedWeight')
                ->groupBy('client_name', 'port_name', 'agent_name', 'blend_number', 'consignee', 'shipping_mark', 'vessel_name', 'garden', 'blend_shipped')
                ->where(['blend_sheets.client_id' => $request->client_id])
                ->orderBy('blend_number', 'asc');

            if ($request->request_number !== null){
                $data->where('blend_number', $request->request_number);
            }

            if ($request->date_from !== null){
                $data->where('blend_shipped', '>=', strtotime($request->date_from));
            }

            if ($request->date_to !== null){
                $data->where('blend_shipped', '<=', strtotime($request->date_to));
            }

            $reports = $data->get();
            $totalPackets = $reports->sum('blendedPackages');
            $totalWeight = $reports->sum('blendedWeight');
            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;
            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.$request->date_to;
            }else{
                $period = 'FOR PERIOD BETWEEN '.$request->date_from.' AND '.$request->date_to;
            }
            if ($request->approved_by !== null){
                $approved = UserInfo::find($request->approved_by);
                \QrCode::size(300)
                    ->format('png')
                    ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                        'REPORT TYPE: ' . 'BLEND REPORT' . "\n" .
                        'CLIENT NAME: ' . $reports->first()->client_name. "\n" .
                        'REPORTING: ' . $period . "\n" .
                        'TOTAL PACKAGES: ' . number_format($totalPackets, 2). "\n" .
                        'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                        'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                        'Files/QrCodes/'.$image);
            }

            $qrCodePath = 'Files/QrCodes/'.$image;
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Landscape
                'orientation' => 'L',
                'margin_top' => 2,
                'margin_bottom' => 7,
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 90% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
                </td>
                <td class="qr-code" style="width: 10% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>

            <div class="header">' . $period . '<hr></div>';

            $mpdf->WriteHTML($companyHeader);
            $clientName = $reports->first()->client_name;
            $html = View::make('clerk::downloads.blend_jobs', [
                'clientName' => $clientName,
                'orders' => $reports,
                'by' => $by,
                'printed' => $printed
            ])->render();

            $mpdf->WriteHTML($html);

            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }

            // Output PDF
            $pdfFileName = 'STRAIGHT LINE JOBS.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);

        }elseif ($request->request_type == 5){
            $data = ExternalTransfer::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
                ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
                ->join('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
                ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                ->select('client_name', 'warehouse_name', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'order_number', 'delivery_number', 'transferred_palettes', 'transferred_weight', 'external_transfers.updated_at', 'external_transfers.status')
                ->where(['delivery_orders.client_id' => $request->client_id])
                ->orderBy('garden_name', 'asc');
            if ($request->request_number !== null){
                $data->where('delivery_number', $request->request_number);
            }
            if ($request->date_from !== null){
                $data->where('external_transfers.created_at', '>=', $request->date_from);
            }
            if ($request->date_to !== null){
                $data->where('external_transfers.created_at', '<=', $request->date_to);
            }
            $reports = $data->get();
            $totalPackets = $reports->sum('transferred_palettes');
            $totalWeight = $reports->sum('transferred_weight');
            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;

            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.$request->date_to;
            }else{
                $period = 'FOR PERIOD BETWEEN '.$request->date_from.' AND '.$request->date_to;
            }
            if ($request->approved_by !== null){
                $approved = UserInfo::find($request->approved_by);
                \QrCode::size(300)
                    ->format('png')
                    ->generate('REQUEST NUMBER: ' . $request->service_number. "\n" .
                        'REPORT TYPE: ' . 'EXTERNAL TRANSFERS REPORT' . "\n" .
                        'CLIENT NAME: ' . $reports->first()->client_name. "\n" .
                        'REPORTING: ' . $period . "\n" .
                        'TOTAL PACKAGES: ' . number_format($totalPackets, 2). "\n" .
                        'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                        'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                        'Files/QrCodes/'.$image);
            }
            $qrCodePath = 'Files/QrCodes/'.$image;
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Landscape
                'orientation' => 'L',
                'margin_top' => 2,
                'margin_bottom' => 7,
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 90% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
                </td>
                <td class="qr-code" style="width: 10% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>

            <div class="header">' . $period . '<hr></div>';

            $mpdf->WriteHTML($companyHeader);
            $clientName = $reports->first()->client_name;
            $html = View::make('clerk::downloads.external_transfers', [
                'clientName' => $clientName,
                'orders' => $reports,
                'by' => $by,
                'printed' => $printed
            ])->render();

            $mpdf->WriteHTML($html);

            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }

            // Output PDF
            $pdfFileName = 'EXTERNAL TRANSFERS.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);

        }elseif ($request->request_type == 6){

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
                    'delivery_orders.packet',
                    'delivery_orders.weight',
                    'delivery_orders.unit_weight',
                    'delivery_orders.status',
                    'tea_samples.sample_weight',
                    DB::raw("
                        CASE
                            WHEN tea_samples.type = 1 THEN 'Sampled'
                            WHEN tea_samples.type = 2 THEN 'Damage'
                            WHEN tea_samples.type = 3 THEN 'Loss'
                            ELSE ''
                        END AS difference
                    "),
                    'stock_ins.date_received', 'stock_ins.stock_id'
                ])
                ->groupBy('delivery_orders.delivery_id', 'stock_ins.stock_id')
                ->where('clients.client_id', $request->client_id)
                ->where('delivery_orders.status', '=', 2)
                ->orderBy('gardens.garden_name', 'asc')
                ->orderBy('stock_ins.date_received', 'asc');

            if ($request->request_number !== null){
                $data->where('invoice_number', $request->request_number);
            }

            if ($request->date_from !== null){
                $data->where('delivery_orders.created_at', '>=', $request->date_from);
            }

            if ($request->date_to !== null){
                $data->where('delivery_orders.created_at', '<=', $request->date_to);
            }

            $reports = $data->get();
            if ($reports->count() == 0){
                return back()->with('success', 'No items found');
            }
            $totalPackets = $reports->sum('packet');
            $totalWeight = $reports->sum('weight');

            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;

            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.$request->date_to;
            }else{
                $period = 'FOR PERIOD BETWEEN '.$request->date_from.' AND '.$request->date_to;
            }
            if ($request->approved_by !== null){
                $approved = UserInfo::find($request->approved_by);
                \QrCode::size(300)
                    ->format('png')
                    ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                        'REPORT TYPE: ' . 'TEA ARRIVAL REPORT' . "\n" .
                        'CLIENT NAME: ' . $reports->first()->client_name. "\n" .
                        'REPORTING: ' . $period . "\n" .
                        'TOTAL PACKAGES: ' . number_format($totalPackets, 2). "\n" .
                        'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                        'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                        'Files/QrCodes/'.$image);
            }
            $qrCodePath = 'Files/QrCodes/'.$image;
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // Landscape
                'orientation' => 'L',
                'margin_top' => 2,
                'margin_bottom' => 7,
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 90% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
                </td>
                <td class="qr-code" style="width: 10% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>

            <div class="header">' . $period . '<hr></div>';

            $mpdf->WriteHTML($companyHeader);
            $clientName = $reports->first()->client_name;
            $html = View::make('clerk::downloads.tea_collections', [
                'clientName' => $clientName,
                'orders' => $reports,
                'by' => $by,
                'printed' => $printed
            ])->render();

            $mpdf->WriteHTML($html);

            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }

            // Output PDF
            $pdfFileName = 'TEA COLLECTIONS.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);
        }elseif ($request->request_type == 7){
            $data = DB::table('blendBalances')
                ->where('current_packages', '>', 0)
                ->where('current_weight', '>', 0)
                ->whereRaw("type COLLATE utf8mb4_unicode_ci != ?", ['BLEND REMNANT'])
                ->where(['client_id' => $request->client_id])
                ->orderBy('garden', 'asc');

            if ($request->request_number !== null){
                $data->where('blend_number', $request->request_number);
            }
            if ($request->date_from !== null){
                $data->where('blend_date', '>=', $request->date_from);
            }
            if ($request->date_to !== null){
                $data->where('blend_date', '<=', $request->date_to);
            }

            $reports = $data->get();
            $totalPackets = $reports->sum('current_packages');
            $totalWeight = $reports->sum('current_weight');
            $date = date('D, d-m-Y, h:i:s');
            $printed = auth()->user()->user;
            $by = $printed->first_name.' '.$printed->surname;
            if ($request->date_from == null){
                $period = 'FULL STATEMENT UPTO '.$request->date_to;
            }else{
                $period = 'FOR PERIOD BETWEEN '.$request->date_from.' AND '.$request->date_to;
            }

            if ($request['approved_by'] !== null){
                $approved = UserInfo::find($request['approved_by']);
                \QrCode::size(1000)
                    ->format('png')
                    ->generate('REQUEST NUMBER: ' . $request->service_number . "\n" .
                        'REPORT TYPE: ' . 'CURRENT BLEND BALANCES (SIEVE DUST) POSITION' . "\n" .
                        'CLIENT NAME: ' . $reports->first()->client_name . "\n" .
                        'REPORTING: ' . $period . "\n" .
                        'TOTAL PACKAGES: ' . number_format($totalPackets, 2). "\n" .
                        'TOTAL NET WEIGHT: ' . number_format($totalWeight, 2). "\n" .
                        'REPORT APPROVED BY: ' . $approved->first_name.' '.$approved->surname . "\n",
                        'Files/QrCodes/'.$image);
            }

            $qrCodePath = 'Files/QrCodes/'.$image;

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-P', // Landscape
                'orientation' => 'P',
                'margin_top' => 2,
                'margin_bottom' => 7,
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
                    width: 100px !important;
                    height: 100px !important;
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
            </style>
            <table>
            <tr>
                <td class="address" style="width: 87% !important;">
                 <img class="logo" src="' . $logoPath . '" alt="Company Logo">
                 <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                 <p> <small> Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</small></p>
                </td>
                <td class="qr-code" style="width: 13% !important;">
                    ' . ($request->approved_by === null
                    ? '<span class="verification">NOT APPROVED </span>'
                    : '<img class="qr-code" src="' . $qrCodePath . '" alt="QR Code">') . '
                </td>
            </tr>
            </table>
            <div class="header">' . $period . '<hr></div>';
            $mpdf->WriteHTML($companyHeader);
            // Process data in smaller chunks
            $chunks = array_chunk($reports->toArray(), 1000); // Reduced from 100 to 50 for smaller chunks

            foreach ($chunks as $chunk) {
                // Render each client group separately
                $groupedChunk = collect($chunk)->groupBy('client_name');

                foreach ($groupedChunk as $clientName => $clientOrders) {
                    $html = View::make('clerk::downloads.current_blend_balances', [
                        'clientName' => $clientName,
                        'orders' => $clientOrders,
                        'by' => $by,
                        'printed' => $printed
                    ])->render();

                    $mpdf->WriteHTML($html);
                }
            }

            if (file_exists('Files/QrCodes/' . $image)){
                unlink('Files/QrCodes/' . $image);
            }

            // Output PDF
            $pdfFileName = 'BLEND BALANCE IN STOCK - SIEVED DUST.pdf';
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);
        }

    }
    public function blendBalanceReport($client, $station, $from, $to, $report)
    {
        $query = DB::table('blendBalances')->where('current_weight', '>', 0)
            ->orderBy('client_name', 'asc')
            ->orderBy('station_name', 'asc')
            ->orderBy('blend_number', 'asc');

            if ($client !== null) {
                $query->where('client_id', '=', $client);
            }
            if ($station !== null) {
                $query->where('station_id', '=', $station);
            }

            $balances = $query->get();

            $appClass = new AppClass();
            return Excel::download(new ExportBlendBalances($appClass, $balances), 'BLEND BALANCES IN STOCK '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function downloadCurrentStock($client, $station, $from, $to, $report)
    {
        $query = DB::table('currentstock')
            ->select('received_by', 'delivery_type', 'lot_number', 'total_weight', 'stocked_at', 'bay_name', 'client_name', 'garden_name', 'grade_name', 'invoice_number', 'prompt_date', 'sale_number', 'sale_date',  'date_received', 'current_stock', 'current_weight', 'loading_number', 'warehouse_name', 'pallet_weight', 'package_tare', 'order_number', 'station_id', 'created_at', 'delivery_id', 'tea_type', 'allocated_weight', 'allocated_packages')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('current_stock', '>', 0)
                        ->where('current_weight', '>', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('allocated_weight', '>', 0)
                            ->where('current_stock', '>', 0)
                            ->where('allocated_packages', '>', 0);
                    });
            })
            ->whereNull('deleted_at')
            ->orderBy('client_name', 'asc')
            ->orderBy('sortOrder', 'desc')
            ->orderBy('stocked_at', 'asc');
        // Apply filtering conditions based on the request parameters
        if (!is_null($client)) {
            $query->where('client_id', $client);
        }
        if (!is_null($station)) {
            $query->where('station_id', $station);
        }
        if (!is_null($from)) {
            $query->where(DB::raw('DATE(FROM_UNIXTIME(date_received))'), '>=', Carbon::parse($from)->format('Y-m-d'));
        }
        if (!is_null($to)) {
            $query->where(DB::raw('DATE(FROM_UNIXTIME(date_received))'), '<=', Carbon::parse($to)->format('Y-m-d'));
        }

        ini_set("pcre.backtrack_limit", "5000000"); // Increase to 5 million
        ini_set("memory_limit", "51200000M"); // Prevent memory exhaustion

        $results = $query->get();

        if ($report == 2){
            $appClass = new AppClass();
            return Excel::download(new ExportStock($appClass, $results), 'STOCK '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $date = date('D, d-m-Y, h:i:s');
        $printed = auth()->user()->user;
        $by = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top' => 4,
            'margin_bottom' => 4,
            'margin_left' => 5,
            'margin_right' => 5,
//            'setAutoTopMargin' => 'stretch',
//            'setAutoBottomMargin' => 'stretch',
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
                .company-info { text-align: center; margin-bottom: 2px !important; font-size: 12px !important; line-height: 0.9 !important; }
                .logo { height: 50px; width: 50px; padding: 2px !important; }
                .heading { color: green; font-size: 13px; font-weight: bold; margin: 0 0; }
                .header { text-align: center; font-weight: bold; font-size: 12px; margin: 1px 0; }
                hr { border: 1px solid #000; margin: 3px 0; }
            </style>
            <div class="company-info">
                <span><img class="logo" src="' . $logoPath . '"></span>
                <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
            </div>
            <div class="header">STOCK POSITION<hr></div>';

        $mpdf->WriteHTML($companyHeader);

// Process data in smaller chunks
        $chunks = array_chunk($results->toArray(), 1000); // Reduced from 100 to 50 for smaller chunks

        foreach ($chunks as $chunk) {
            // Render each client group separately
            $groupedChunk = collect($chunk)->groupBy('client_name');

            foreach ($groupedChunk as $clientName => $clientOrders) {
                $html = View::make('clerk::downloads.current_stock', [
                    'clientName' => $clientName,
                    'orders' => $clientOrders,
                    'by' => $by,
                    'printed' => $printed
                ])->render();

                $mpdf->WriteHTML($html);
            }
        }

// Output PDF
        $pdfFileName = 'STOCK POSITION.pdf';
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadInternalTransfer($id)
    {
           $orders = Transfers::leftJoin('blendBalances', function ($join) {
               $join->on('blendBalances.blend_balance_id', '=', 'transfers.stock_id')
                   ->on('blendBalances.blend_id', '=', 'transfers.delivery_id');
           })
               ->leftJoin('delivery_orders', function ($join){
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
               ->select('stations.station_name', 'stations.station_id', 'clients.client_name', 'destination_station.station_name as destination_name', 'destination_station.station_id as destination', 'transfers.status', 'transfers.delivery_number', 'transfers.created_at', 'warehouse_locations.location_id', 'stations.location_id as origin', 'transfers.requested_palettes', 'transfers.requested_weight', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'stock_id', 'registration', 'driver_name', 'id_number', 'drivers.phone', 'transporters.transporter_id', 'transporter_name', 'transfer_id', 'grade', 'garden', 'blend_number', 'blendBalances.client_name as client', 'transfers.created_by as prepared_by')
               ->where(['delivery_number' => base64_decode($id)])
               ->orderBy('garden_name', 'asc')
               ->orderBy('garden', 'asc')
               ->orderBy('invoice_number', 'asc')
               ->orderBy('blend_number', 'desc')
               ->get();

        $details = $orders[0];

        $prepared = UserInfo::where('user_id', $details['prepared_by'])->first();

        $user = $prepared->first_name.' '.$prepared->surname;
        $printed = auth()->user()->user;
        $by = $printed->first_name.' '.$printed->surname;

        $approvals = Approval::join('user_infos', 'user_infos.user_id', '=', 'approvals.user_id')
            ->leftJoin('signatories', 'signatories.user_id', 'approvals.user_id')
            ->select(DB::raw("CONCAT(COALESCE(surname, ''),' ', COALESCE(first_name, '')) as full_name"), DB::raw("FROM_UNIXTIME(approval_date) as approval_date"), 'signature')
            ->where(['job_id' => base64_decode($id)])
            ->orderBy('order', 'asc')
            ->get();

        $signatories = Signatory::join('departments', 'departments.department_id', '=', 'signatories.department_id')->get();
        // Render Blade view
        $html = View::make('clerk::downloads.internal_transfer', compact('orders', 'details', 'user', 'by', 'printed', 'approvals', 'signatories'))->render();

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
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed by: <strong>' . $by . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by: <strong>' . $user . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $details->delivery_number.'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);

    }
    public function downloadExternalTransfers($id)
    {
        list($delNumber, $lot) = explode(':', base64_decode($id));

        $orders = ExternalTransfer::leftJoin('blendBalances', function ($join) {
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
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('other_transporters', 'other_transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->select('ex_transfer_id', 'external_transfers.status', DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'), DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'), DB::raw('COALESCE(transporters.transporter_name, other_transporters.transporter_name) as transporter_name'), DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'), 'external_transfers.delivery_number', DB::raw('DATE(external_transfers.created_at) as created_at'), 'location_id', DB::raw('COALESCE(gardens.garden_name, blendBalances.garden) as garden_name'),DB::raw('COALESCE(grades.grade_name, blendBalances.grade) as grade_name'), DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number) as invoice_number'), 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'delivery_orders.lot_number', 'external_transfers.created_by', 'external_transfers.updated_at', 'external_transfers.delivery_id', 'drivers.driver_name', 'drivers.phone', 'external_transfers.registration', 'drivers.id_number', 'external_transfers.release_date', 'lot')
            ->where(['external_transfers.delivery_number' => $delNumber, 'lot' => $lot ?: null])
            ->orderBy(DB::raw('COALESCE(gardens.garden_name, blendBalances.garden)'))
            ->orderBy(DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number)'))
            ->get();

        $date = Carbon::parse($orders->min('created_at'))->format('d-m-Y');

        $details = $orders[0];

        $prepared = UserInfo::where('user_id', $details['created_by'])->first();

        $user = $prepared->first_name.' '.$prepared->surname;
        $printed = auth()->user()->user;
        $by = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;

        $approvals = Approval::join('user_infos', 'user_infos.user_id', '=', 'approvals.user_id')
            ->leftJoin('signatories', 'signatories.user_id', 'approvals.user_id')
            ->select(DB::raw("CONCAT(COALESCE(surname, ''),' ', COALESCE(first_name, '')) as full_name"), DB::raw("FROM_UNIXTIME(approval_date) as approval_date"), 'signature')
            ->where(['job_id' => $delNumber])
            ->orderBy('order', 'asc')
            ->get();

        $signatories = Signatory::join('departments', 'departments.department_id', '=', 'signatories.department_id')->get();

        // Render Blade view
        $html = View::make('clerk::downloads.external_transfer', compact('orders', 'details', 'user', 'by', 'printed', 'approvals', 'signatories', 'date'))->render();

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
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed by: <strong>' . $by . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by: <strong>' . $user . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $details->delivery_number.'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadExternalDelNote($id)
    {
        list($delNumber, $lot) = explode(':', base64_decode($id));

        $orders = ExternalTransfer::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
            })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'external_transfers.delivery_id');
            })
            ->leftJoin('auctions', function ($join) {
                $join->on('auctions.delivery_id', '=', 'external_transfers.delivery_id');
            })
            ->join('clients as buyer', 'buyer.client_id', '=', 'auctions.client_id')
            ->leftJoin('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftjoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('other_transporters', 'other_transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->select('ex_transfer_id', 'external_transfers.status', DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'), DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'), DB::raw('COALESCE(transporters.transporter_name, other_transporters.transporter_name) as transporter_name'), DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'), 'external_transfers.delivery_number', DB::raw('DATE(external_transfers.created_at) as created_at'), 'location_id', DB::raw('COALESCE(gardens.garden_name, blendBalances.garden) as garden_name'),DB::raw('COALESCE(grades.grade_name, blendBalances.grade) as grade_name'), DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number) as invoice_number'), 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'delivery_orders.lot_number', 'external_transfers.created_by', 'external_transfers.updated_at', 'external_transfers.delivery_id', 'drivers.driver_name', 'drivers.phone', 'external_transfers.registration', 'drivers.id_number', 'buyer.client_name as buyer_name', 'external_transfers.loading_number', 'lot', 'external_transfers.release_date')
            ->where(['external_transfers.delivery_number' => $delNumber, 'lot' => $lot ?: null])
            ->orderBy(DB::raw('COALESCE(gardens.garden_name, blendBalances.garden)'))
            ->orderBy(DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number)'))
            ->get();

        $details = $orders[0];

        $prepared = UserInfo::where('user_id', $details['created_by'])->first();

        $user = $prepared->first_name.' '.$prepared->surname;
        $printed = auth()->user()->user;
        $by = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;

        $approvals = Approval::join('user_infos', 'user_infos.user_id', '=', 'approvals.user_id')
            ->leftJoin('signatories', 'signatories.user_id', 'approvals.user_id')
            ->select(DB::raw("CONCAT(COALESCE(surname, ''),' ', COALESCE(first_name, '')) as full_name"), DB::raw("FROM_UNIXTIME(approval_date) as approval_date"), 'signature')
            ->where(['job_id' => $delNumber])
            ->orderBy('order', 'asc')
            ->get();

        $signatories = Signatory::join('departments', 'departments.department_id', '=', 'signatories.department_id')->get();
        // Render Blade view
        $html = View::make('clerk::downloads.external_transfer_dn', compact('orders', 'details', 'user', 'by', 'printed', 'approvals', 'signatories'))->render();

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
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed by: <strong>' . $by . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by: <strong>' . $user . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $details->delivery_number.'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadStraightLine($id)
    {
        $shippings = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'shipping_instructions.driver_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->leftJoin('delivery_orders', 'delivery_orders.delivery_id', 'shipments.delivery_id')
            ->leftJoin('gardens', 'gardens.garden_id', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', 'delivery_orders.grade_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->select(
                'sale_number', 'garden_name', 'grade_name', 'invoice_number', 'registration',
                'driver_name', 'drivers.phone', 'shipping_instructions', 'ship_date',
                'client_name', 'shipping_number', 'vessel_name', 'port_name',
                'shipped_packages', 'shipped_weight', 'consignee', 'shipping_mark',
                'container_number', 'agent_name', 'transporter_name', 'seal_number', 'container_size', 'ship_date', 'delivery_orders.delivery_id', 'warehouse_name', 'production_date', 'expiry_date', 'height'
            )
            ->where('shipping_instructions.shipping_id', $id)
            ->whereNull('shipments.deleted_at')
            ->get();


        $sheet = $shippings[0];
        $printed = auth()->user();
        $staffName = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;
        $date = Carbon::now()->format('D, d-m-Y H:i:s');

        // Render Blade view
        $html = View::make('clerk::downloads.si', compact('shippings', 'sheet', 'staffName', 'date'))->render();

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
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheet->shipping_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadSIPackingList($id)
    {
        list($shippingId, $type) = explode(':', base64_decode($id));

        $shippings = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->leftJoin('delivery_orders', 'delivery_orders.delivery_id', 'shipments.delivery_id')
            ->leftJoin('gardens', 'gardens.garden_id', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', 'delivery_orders.grade_id')
            ->join('stock_ins', 'stock_ins.stock_id', '=', 'shipments.stock_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'shipping_instructions.user_id')
            ->select(
                'clients.address as client_address', 'shipping_instructions.address', 'garden_name', 'grade_name', 'invoice_number', 'ship_date',
                'client_name', 'shipping_number', 'vessel_name', 'port_name', 'shipped_packages', 'shipped_weight', 'consignee', 'shipping_mark', 'container_number', 'agent_name', 'seal_number','delivery_orders.delivery_id', 'production_date', 'expiry_date', 'shipments.pallet_height as height', 'lot_number', 'shipments.package_tare', 'shipments.pallet_weight', 'surname', 'first_name', 'shipping_instructions.booking_number'
            )
            ->where('shipping_instructions.shipping_id', $shippingId)
            ->whereNull('shipments.deleted_at')
            ->get();


        $sheet = $shippings[0];
        $printed = auth()->user();
        $staffName = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;
        $date = Carbon::now()->format('D, d-m-Y H:i:s');

        // Render Blade view
        if ($type == 2) {
            $html = View::make('clerk::downloads.si_palletized_packing_list', compact('shippings', 'sheet', 'staffName', 'date'))->render();
        }else{
            $html = View::make('clerk::downloads.si_loose_packing_list', compact('shippings', 'sheet', 'staffName', 'date'))->render();
        }

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top'    => 7,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheet->shipping_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadSIContinuedPackingList($id)
    {

        $shippings = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->leftJoin('delivery_orders', 'delivery_orders.delivery_id', 'shipments.delivery_id')
            ->leftJoin('gardens', 'gardens.garden_id', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', 'delivery_orders.grade_id')
            ->join('stock_ins', 'stock_ins.stock_id', '=', 'shipments.stock_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'shipping_instructions.user_id')
            ->select(
                'clients.address as client_address', 'shipping_instructions.address', 'garden_name', 'grade_name', 'invoice_number', 'ship_date',
                'client_name', 'shipping_number', 'vessel_name', 'port_name', 'shipped_packages', 'shipped_weight', 'consignee', 'shipping_mark', 'container_number', 'agent_name', 'seal_number','delivery_orders.delivery_id', 'production_date', 'expiry_date', 'shipments.pallet_height as height', 'lot_number', 'shipments.package_tare', 'shipments.pallet_weight', 'surname', 'first_name', 'shipping_instructions.booking_number'
            )
            ->where(['shipping_instructions.si_number' => base64_decode($id), 'load_type' => 2])
            ->whereNull('shipments.deleted_at')
            ->get();


        $sheet = $shippings[0];
        $printed = auth()->user();
        $staffName = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;
        $date = Carbon::now()->format('D, d-m-Y H:i:s');

        // Render Blade view
            $html = View::make('clerk::downloads.si_palletized_continued_packing_list', compact('shippings', 'sheet', 'staffName', 'date'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top'    => 7,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheet->shipping_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadStraightLineClearance($id)
    {
        $shipment = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'shipping_instructions.driver_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->leftJoin('currentstock', function($join) {
                $join->on('currentstock.stock_id', '=', 'shipments.stock_id')
                    ->on('currentstock.delivery_id', '=', 'shipments.delivery_id');
            })
            ->select('shipping_number', 'port_name', 'consignee', 'shipping_mark', 'vessel_name', 'seal_number', 'container_number', 'container_size', 'agent_name', 'transporters.transporter_name', 'shipping_instructions.registration', 'drivers.driver_name', 'drivers.phone', 'load_type')
            ->selectRaw("SUM(shipments.shipped_packages) as totalPackages")
            ->selectRaw("SUM(CAST(REPLACE(REPLACE(shipments.shipped_weight, ',', ''), '.00', '') AS DECIMAL(10,2))) as totalWeight")
            ->where('shipping_instructions.shipping_id', $id)
            ->groupBy('shipping_number', 'port_name', 'consignee', 'shipping_mark', 'vessel_name', 'seal_number', 'container_number', 'container_size', 'agent_name', 'transporters.transporter_name', 'shipping_instructions.registration', 'drivers.driver_name', 'drivers.phone', 'load_type')
            ->first();

        $staffName = auth()->user()->user->surname.' '.auth()->user()->user->first_name;
        $date = Carbon::now()->format('D, d-m-Y H:i:s');

        // Render Blade view
        $html = View::make('clerk::downloads.si_clearance', compact('shipment','staffName', 'date'))->render();

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
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $shipment->shipping_number).' CLEARANCE.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadBlendJob($id)
    {
        $sheet = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->leftJoin('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'blend_sheets.driver_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'blend_sheets.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->select('blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone as cPhone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'standard_details', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'transporters.transporter_id', 'driver_name', 'drivers.phone as driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'blend_sheets.packet_tare', 'blend_sheets.agent_id', 'id_number', 'stations.station_id', 'stations.station_name')
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->groupBy('blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'standard_details', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'driver_name', 'driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'packet_tare', 'agent_id', 'transporter_id', 'id_number', 'station_id', 'station_name')
            ->whereNull('blend_teas.deleted_at')
            ->where('blend_sheets.blend_id', $id)
            ->latest('blend_sheets.created_at')
            ->first();


        $teas = BlendTea::join('blend_sheets', 'blend_sheets.blend_id', '=', 'blend_teas.blend_id')
            ->leftJoin('delivery_orders', 'delivery_orders.delivery_id', '=', 'blend_teas.delivery_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('grades','grades.grade_id','=','delivery_orders.grade_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('loading_instructions', function($join) {
                $join->on('loading_instructions.delivery_id','=','delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('blendBalances', function ($join){
                $join->on('blendBalances.blend_balance_id', '=', 'blend_teas.stock_id')
                    ->on('blendBalances.blend_id', '=', 'blend_teas.delivery_id');
            })
            ->select('blended_id', 'blend_teas.blended_packages', 'blend_teas.blended_weight', 'blend_teas.status', 'gardens.garden_name', 'grades.grade_name', 'blendBalances.grade', 'blendBalances.garden', 'loading_number', 'sale_number', 'prompt_date', 'invoice_number', 'blendBalances.blend_number', 'blend_sheets.blend_date', 'blend_shipped', DB::raw("CASE WHEN blendBalances.blend_id IS NULL THEN delivery_orders.delivery_id ELSE blendBalances.blend_id END AS delivery_id"), DB::raw("COALESCE(warehouses.warehouse_name, blendBalances.station_name) AS warehouse_name"))
            ->where('blend_teas.blend_id', $id)
            ->orderBy('blend_teas.created_at', 'desc')
            ->get();


        $date = Carbon::now()->format('D, d-m-Y H:i:s');
        $balance = BlendBalance::where(['blend_id' => $id, 'type' => 1])
            ->selectRaw("SUM(ex_packages) as balPacks")
            ->selectRaw("SUM(net_weight) as balWeight")
            ->whereNull('deleted_at')
            ->first();

        $containers = ShipmentContainer::where('blend_id', $id)->pluck('container_number')->toArray();
        $printed = auth()->user();
        $staffName = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;


        // Render Blade view
        $html = View::make('clerk::downloads.blend', compact('teas', 'sheet', 'staffName', 'date', 'containers', 'balance'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheet->blend_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadBlendPackingList($id)
    {
        list($blendId, $type) = explode(":", base64_decode($id));
        $sheet = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->leftJoin('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->select('blend_sheets.blend_id', 'client_name', 'clients.phone as cPhone', 'email', 'blend_number', 'vessel_name', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'blend_date', 'container_size', 'clients.address', 'package_type', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'output_packages', 'output_weight', 'blend_sheets.packet_tare', 'blend_sheets.address as consignee_address', 'booking_number', 'si_number')
            ->groupBy('blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone', 'email', 'blend_number', 'vessel_name', 'port_name', 'shipping_mark', 'consignee', 'address', 'booking_number', 'si_number', 'contract', 'grade', 'garden', 'blend_date', 'container_size', 'clients.address', 'package_type', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'output_packages', 'output_weight', 'packet_tare')
            ->whereNull('blend_teas.deleted_at')
            ->where('blend_sheets.blend_id', $blendId)
            ->latest('blend_sheets.created_at')
            ->first();

        $containers = ShipmentContainer::where('blend_id', $blendId)->get();
        $printed = auth()->user();
        $staffName = $printed ? ($printed->first_name.' '.$printed->surname) : auth()->user()->username;

//        return [
//          'sheet' => $sheet, 'container' => $containers
//        ];

        // Render Blade view
        $html = View::make('clerk::downloads.blend_packing_list', compact('sheet', 'staffName', 'containers'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'P',
            'margin_top'    => 7,
            'margin_bottom' => 7,
            'margin_left'   => 7,
            'margin_right'  => 7,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheet->blend_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadBlendPackingListCont($id)
    {
        list($blendId, $type) = explode(":", base64_decode($id));

       $sheets = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->select(
                'blend_sheets.blend_id',
                'client_name',
                'clients.phone as cPhone',
                'email',
                'blend_number',
                'vessel_name',
                'port_name',
                'shipping_mark',
                'consignee',
                'contract',
                'grade',
                'garden',
                'blend_date',
                'container_size',
                'clients.address',
                'package_type',
                'container_tare',
                'blend_shipped',
                'agent_name',
                'seal_number',
                'output_packages',
                'output_weight',
                'blend_sheets.packet_tare',
                'blend_sheets.address as consignee_address',
                'booking_number',
                'si_number',
            )
            ->groupBy(
                'blend_sheets.blend_id',
                'blend_sheets.client_id',
                'client_name',
                'clients.phone',
                'email',
                'blend_number',
                'vessel_name',
                'port_name',
                'shipping_mark',
                'consignee',
                'clients.address',
                'booking_number',
                'si_number',
                'contract',
                'grade',
                'garden',
                'blend_date',
                'container_size',
                'package_type',
                'container_tare',
                'blend_shipped',
                'agent_name',
                'seal_number',
                'output_packages',
                'output_weight',
                'blend_sheets.packet_tare',
                'blend_sheets.address',
            )
            ->where('blend_sheets.si_number', $blendId)
            ->latest('blend_sheets.created_at')
            ->get();

        // Get all blend IDs from the sheets
        $blendIds = $sheets->pluck('blend_id')->unique()->toArray();

        // Fetch all containers for these blends in one query
        $allContainers = ShipmentContainer::whereIn('blend_id', $blendIds)
            ->orderBy('blend_id')
            ->orderBy('container_number')
            ->get();

        // Group containers by blend_id for easy lookup
        $containers = $allContainers->groupBy('blend_id');

        // Attach containers to each sheet
        $sheets = $sheets->map(function($sheet) use ($containers) {
            $sheet->containers = $containers->get($sheet->blend_id, collect([]));
            return $sheet;
        });

        $printed = auth()->user();
        $staffName = $printed ? ($printed->first_name . ' ' . $printed->surname) : auth()->user()->username;

        // Render Blade view
        $html = View::make('clerk::downloads.continuous_blend_packing_list', compact('sheets', 'staffName'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'P',
            'margin_top'    => 7,
            'margin_bottom' => 7,
            'margin_left'   => 7,
            'margin_right'  => 7,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
        <table width="100%">
            <tr>
                <td align="center">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>
    ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheets[0]->blend_number) . '.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function driverClearanceBlends($id)
    {
        $shipment = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->leftJoin('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'blend_sheets.driver_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'blend_sheets.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->select('blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone as cPhone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'standard_details', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'transporters.transporter_id', 'driver_name', 'drivers.phone as driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'blend_sheets.packet_tare', 'blend_sheets.agent_id', 'id_number', 'stations.station_id', 'stations.station_name')
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->groupBy('blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'standard_details', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'driver_name', 'driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'packet_tare', 'agent_id', 'transporter_id', 'id_number', 'station_id', 'station_name')
            ->whereNull('blend_teas.deleted_at')
            ->where('blend_sheets.blend_id', $id)
            ->latest('blend_sheets.created_at')
            ->first();

        $containers = ShipmentContainer::where('blend_id', $id)->pluck('container_number')->toArray();
        $staffName = auth()->user()->user->surname.' '.auth()->user()->user->first_name;
        $date = Carbon::now()->format('D, d-m-Y H:i:s');

        // Render Blade view
        $html = View::make('clerk::downloads.blend_clearance', compact('shipment','staffName', 'date', 'containers'))->render();

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
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $shipment->blend_number).' CLEARANCE.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadBlendOutTurn($id)
    {
        $sheet = BlendSheet::join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->select('blend_date', 'vessel_name', 'consignee', 'standard_details', 'port_name', 'blend_number', 'b_dust', 'c_dust', 'fibre', 'sweepings')
            ->where('blend_sheets.blend_id', $id)
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->groupBy('blend_date', 'vessel_name', 'consignee', 'standard_details', 'port_name', 'blend_number', 'b_dust', 'c_dust', 'fibre', 'sweepings')
            ->whereNull('blend_teas.deleted_at')
            ->first();

        $input = BlendTea::where('blend_id', $id)
            ->selectRaw('SUM(blended_packages) as input_packages, SUM(blended_weight) as input_weight')
            ->first();

        $blendSummaries = BlendShipment::where('blend_id', $id)->orderBy('blended_packages', 'desc')->get();
        $blendBalances = BlendBalance::where('blend_id', $id)->where('net_weight', '>', 0)->orderBy('ex_packages', 'desc')->get();
        $supervisors = BlendSupervision::where('blend_id', $id)->get();
        $materials = BlendMaterial::where('blend_id', $id)->get();

        if ($supervisors){
            $prepared = UserInfo::where('user_id', $supervisors[0]['compiled_by'])->first();
            $user = $prepared->first_name.' '.$prepared->surname;
        }else{
            $user = null;
        }

        // Render Blade view
        $html = View::make('clerk::downloads.blend_outturn_report', compact('sheet', 'blendSummaries', 'blendBalances', 'materials', 'user', 'supervisors', 'input'))->render();

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
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sheet->blend_number).' OUTTURN REPORT.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function getAgingDays($id, $date)
    {
            $originalDate = StockIn::where('delivery_id', $id)
                ->orderBy('date_received', 'asc')
                ->value('date_received');

            if (is_null($originalDate)) {
                $blendDate = DB::table('blendBalances')
                    ->where('blend_id', $id)
                    ->orderBy('blend_date', 'asc')
                    ->value('blend_date');

                $originalDate = $blendDate ? strtotime($blendDate) : null;
            }

        $receivedDate = Carbon::createFromTimestamp($originalDate);
        if (Auction::where(['delivery_id' => $id])->exists()) {
            $dates = Auction::where(['delivery_id' => $id])->value('auctions.sale_date');
            $finalDate = Carbon::parse($dates);
        } else {
            $finalDate = Carbon::createFromTimestamp($date) ?? Carbon::now();
        }
        return $finalDate->diffInDays($receivedDate);
    }
    public function getPromptAgingDays($id, $date)
    {
        $originalDate = Auction::where('delivery_id', $id)
            ->orderBy('auctions.prompt_date', 'asc')
            ->value('auctions.prompt_date');

        $receivedDate = Carbon::parse($originalDate);
        $finalDate = Carbon::parse($date) ?? Carbon::now();
        $age = $finalDate->diffInDays($receivedDate) - 7;
        return $age <= 0 ? 0 : $age;
    }
    public function getLedgerStatement($client, $id, $financial, $opBal)
    {
        $statement = $this->viewLedgerStatement($client, $id, $financial, $opBal);
        return $statement;
    }
    public function fetchBalanceSheet($financial, $id) {
        $receipts = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.account_id')
            ->join('client_accounts as account', 'account.client_account_id', '=', 'transactions.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'account.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                // Summing up debit values
                DB::raw("SUM(
                        CASE
                            WHEN currencies.priority = 2 AND curr.priority = 1 OR currencies.priority = 2 AND curr.priority = 2 THEN
                                CASE
                                    WHEN transactions.exchange_rate IS NOT NULL THEN
                                        transactions.amount_received * transactions.exchange_rate
                                    ELSE
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                              AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ) * transactions.amount_received
                                END
                            ELSE transactions.amount_received
                        END)
                     AS debit
                "),
                DB::raw("0.00 AS credit"),
            )
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('client_accounts.deleted_at')
            ->get();

        $clients = DB::table('transactions')
            ->join('client_accounts', function ($join) {
                $join->on('client_accounts.client_account_id', '=', 'transactions.client_id');
            })
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies as client_currency', 'client_currency.currency_id', '=', 'client_accounts.currency_id')
            ->join('client_accounts as account', function ($join) {
                $join->on('account.client_account_id', '=', 'transactions.account_id');
            })
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->leftJoin(DB::raw('(SELECT currency_id FROM currencies WHERE priority != 1 LIMIT 1) as priority_currency'), function ($join) {
                $join->on('priority_currency.currency_id', '=', 'client_accounts.currency_id')
                    ->orOn('priority_currency.currency_id', '=', 'account.currency_id');
            })
            ->leftJoin('forex_exchanges', function ($join) {
                $join->on('forex_exchanges.currency_id', '=', 'priority_currency.currency_id')
                    ->whereRaw('forex_exchanges.date_active = (
                SELECT MAX(fx.date_active)
                FROM forex_exchanges AS fx
                WHERE fx.date_active <= FROM_UNIXTIME(transactions.date_received)
                AND fx.currency_id = priority_currency.currency_id
            )');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("
            SUM(
                CASE
                    WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 1 AND account_currency.priority = 2 OR client_currency.priority = 2 AND account_currency.priority = 2
                        THEN transactions.amount_received * COALESCE(forex_exchanges.exchange_rate, 1)
                    WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 1 AND account_currency.priority = 2 OR client_currency.priority = 2 AND account_currency.priority = 2
                        THEN transactions.amount_received * transactions.exchange_rate
                    ELSE
                    transactions.amount_received
                END
            ) AS credit
        "),
                'account.client_account_name as ledger_name',
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('transactions.deleted_at')
            ->whereNull('client_accounts.deleted_at')
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                'account.client_account_name',
            )
            ->get();

       $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.invoice_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND invoices.type = 1 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND invoices.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS debit"),
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND invoices.type = 2 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND invoices.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS credit")
            )
            ->where('invoices.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->get();

       $payments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.account_id')
               ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.client_id')
               ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
               ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
               ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
               ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
               ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
               ->select(
                   'client_ca.client_account_id',
                   'client_ca.client_account_name',
                   'account_name',
                   'chart_number',
                   'chart_name',
                   'currencies.currency_symbol',
                   DB::raw("SUM(
    CASE
        -- New condition: cross-priority (one is 2, the other is 1)
        WHEN (account_currency.priority = 2 AND currencies.priority = 1)
          OR (currencies.priority = 2 AND account_currency.priority = 1) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is NULL or =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is present OR =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * payments.exchange_rate

        -- Default case
        ELSE payments.amount_received
    END
) AS credit"),

                //    DB::raw("SUM(
                //     CASE
                //         WHEN payments.exchange_rate IS NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * COALESCE((
                //                 SELECT fx2.exchange_rate
                //                 FROM forex_exchanges AS fx2
                //                 WHERE (
                //                     fx2.currency_id = currencies.currency_id OR fx2.currency_id = account_currency.currency_id
                //                 )
                //                 AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                //                 ORDER BY fx2.date_active DESC
                //                 LIMIT 1
                //             ), 1)
                //         WHEN payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * payments.exchange_rate
                //         ELSE payments.amount_received
                //     END) AS credit
                // "),
                   DB::raw("0.00 AS debit"),
               )
               ->groupBy(
                   'client_ca.client_account_id',
                   'client_ca.client_account_name',
                   'account_name',
                   'chart_number',
                   'chart_name',
                   'currencies.currency_symbol'
               )
               ->where('payments.financial_year_id', $id)
               ->whereNull('payments.deleted_at')
               ->whereNull('client_ca.deleted_at')
               ->whereNull('account_ca.deleted_at')
               ->orderBy('invoice_number', 'asc')
               ->get();

        $crossPayments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.client_id')
             ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.account_id')
             ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
             ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
             ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
             ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
             ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
             ->select(
                 'client_ca.client_account_id',
                 'client_ca.client_account_name',
                 'account_name',
                 'chart_number',
                 'chart_name',
                 'currencies.currency_symbol',
                //  DB::raw("SUM(
                //     CASE
                //         WHEN payments.exchange_rate IS NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2)THEN
                //             payments.amount_received * COALESCE((
                //                 SELECT fx2.exchange_rate
                //                 FROM forex_exchanges AS fx2
                //                 WHERE fx2.currency_id = account_currency.currency_id
                //                   AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                //                 ORDER BY fx2.date_active DESC
                //                 LIMIT 1
                //             ), 1)
                //         WHEN payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * payments.exchange_rate
                //         ELSE payments.amount_received
                //     END) AS debit
                // "),
                DB::raw("SUM(
    CASE
        -- New condition: cross-priority (one is 2, the other is 1)
        WHEN (account_currency.priority = 2 AND currencies.priority = 1)
          OR (currencies.priority = 2 AND account_currency.priority = 1) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is NULL or =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is present OR =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * payments.exchange_rate

        -- Default case
        ELSE payments.amount_received
    END
) AS debit"),

                 DB::raw("0.00 AS credit"),
             )
             ->groupBy(
                 'client_ca.client_account_id',
                 'client_ca.client_account_name',
                 'account_name',
                 'chart_number',
                 'chart_name',
                 'currencies.currency_symbol'
             )
            ->where('payments.financial_year_id', $id)
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('invoice_number', 'asc')
             ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND purchases.type = 2 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND purchases.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS debit"),

                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND purchases.type = 1 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND purchases.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS credit"),
            )
            ->where('purchases.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('purchase_items.deleted_at')
            ->get();

        $incomes = InvoiceItem::join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id') // ledger
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id') // ledger currency
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'invoices.client_id') // client
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // client currency
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN invoices.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS credit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN invoices.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS debit
                ")
            )
            ->where('invoices.financial_year_id', $id)
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('client_accounts.client_account_name')
            ->get();

        $journalDr = DB::table('adjustment_journals as debit')
            ->join('client_accounts as debit_account', 'debit.ledger_id', '=', 'debit_account.client_account_id')
            ->join('currencies as cur_debit', 'debit_account.currency_id', '=', 'cur_debit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'debit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin(DB::raw('
                (
                    SELECT aj_credit.*,
                           ca.client_account_name AS credit_account_name,
                           ca.currency_id AS credit_currency_id
                    FROM adjustment_journals aj_credit
                    JOIN client_accounts ca ON aj_credit.ledger_id = ca.client_account_id
                    WHERE aj_credit.type = 2 AND aj_credit.deleted_at IS NULL
                      AND aj_credit.adjustment_journal_id IN (
                          SELECT MAX(inner_aj.adjustment_journal_id)
                          FROM adjustment_journals inner_aj
                          WHERE inner_aj.type = 2 AND inner_aj.deleted_at IS NULL
                          GROUP BY inner_aj.reference_code
                      )
                ) as credit
            '), 'credit.reference_code', '=', 'debit.reference_code')
            ->leftJoin('currencies as cur_credit', 'credit.credit_currency_id', '=', 'cur_credit.currency_id')
            ->select(
                'debit_account.client_account_id',
                'debit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_debit.currency_symbol',
                DB::raw("SUM(
                    CASE
                        WHEN cur_debit.priority = 2 AND cur_credit.priority = 1 OR cur_credit.priority = 2 AND cur_debit.priority = 1 OR cur_debit.priority = 2 AND cur_credit.priority = 2 THEN
                            CASE
                                WHEN credit.exchange_rate IS NOT NULL AND debit.exchange_rate != 1 THEN debit.exchange_rate * debit.amount
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE (fx2.currency_id = credit.credit_currency_id OR fx2.currency_id = cur_debit.currency_id)
                                    AND fx2.date_active <= FROM_UNIXTIME(debit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * debit.amount
                            END
                        ELSE debit.amount
                    END) as debit
                "),
                DB::raw("0 as credit"),
                'debit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'credit.credit_account_name as ledger_name',
                'debit.description',
                'debit.reference_code as transaction_number',
                'debit_account.type'
            )
            ->groupBy(
                'debit_account.client_account_id',
                'debit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_debit.currency_symbol',
                'debit.date_adjusted',
                'credit.credit_account_name',
                'debit.description',
                'debit.reference_code',
                'debit_account.type'
            )
            ->whereNull('debit.deleted_at')
            ->where('debit.type', 1)
            ->whereBetween('debit.date_adjusted', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('debit_account.client_account_name')
            ->get();

        $journalsCr = DB::table('adjustment_journals as credit')
            ->join('client_accounts as credit_account', 'credit.ledger_id', '=', 'credit_account.client_account_id')
            ->join('currencies as cur_credit', 'credit_account.currency_id', '=', 'cur_credit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'credit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin(DB::raw('(
        SELECT aj_debit.*,
               ca.client_account_name AS debit_account_name,
               ca.currency_id AS debit_currency_id
        FROM adjustment_journals aj_debit
        JOIN client_accounts ca ON aj_debit.ledger_id = ca.client_account_id
        WHERE aj_debit.type = 1 AND aj_debit.deleted_at IS NULL
          AND aj_debit.adjustment_journal_id IN (
              SELECT MAX(inner_aj.adjustment_journal_id)
              FROM adjustment_journals inner_aj
              WHERE inner_aj.type = 1 AND inner_aj.deleted_at IS NULL
              GROUP BY inner_aj.reference_code
          )
    ) as debit'), 'credit.reference_code', '=', 'debit.reference_code')
            ->leftJoin('currencies as cur_debit', 'debit.debit_currency_id', '=', 'cur_debit.currency_id')
            ->select(
                'credit_account.client_account_id',
                'credit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_credit.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("SUM(
            CASE
                WHEN cur_debit.priority = 2 OR cur_credit.priority = 2 OR cur_debit.priority = 2 AND cur_credit.priority = 2 THEN
                    CASE
                        WHEN debit.exchange_rate IS NOT NULL AND debit.exchange_rate != 1 THEN credit.amount * debit.exchange_rate
                        ELSE COALESCE((
                            SELECT fx2.exchange_rate
                            FROM forex_exchanges AS fx2
                            WHERE (fx2.currency_id = debit.debit_currency_id OR fx2.currency_id = cur_credit.currency_id)
                            AND fx2.date_active <= FROM_UNIXTIME(credit.date_adjusted)
                            ORDER BY fx2.date_active DESC
                            LIMIT 1
                        ), 1) * credit.amount
                    END
                ELSE credit.amount
            END) as credit
        "),
                'credit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'debit.debit_account_name as ledger_name',
                'credit.description',
                'credit.reference_code as transaction_number',
                'credit_account.type'
            )
            ->groupBy(
                'credit_account.client_account_id',
                'credit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_credit.currency_symbol',
                'credit.date_adjusted',
                'debit.debit_account_name',
                'credit.description',
                'credit.reference_code',
                'credit_account.type'
            )
            ->whereNull('credit.deleted_at')
            ->where('credit.type', 2)
            ->whereBetween('credit.date_adjusted', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('credit_account.client_account_name')
            ->get();

        $cashes = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('petty_cashes', function ($join) use ($financial) {
                $join->on('petty_cashes.ledger_id', '=', 'client_accounts.client_account_id')
                    ->where('date_invoiced', '>=', strtotime($financial->year_starting))
                    ->where('date_invoiced', '<=', strtotime($financial->year_ending))
                    ->whereNull('petty_cashes.deleted_at');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                'client_accounts.type',
                // DEBIT logic
                DB::raw("SUM(
                        CASE
                            WHEN petty_cashes.type = 2 AND currencies.priority = 2
                            THEN
                                CASE
                                    WHEN petty_cashes.exchange_rate IS NOT NULL
                                    THEN petty_cashes.amount * petty_cashes.exchange_rate
                                    ELSE COALESCE(
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                            AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ), 1
                                    ) * petty_cashes.amount
                                END
                            WHEN petty_cashes.type = 2 AND currencies.priority = 1
                            THEN petty_cashes.amount
                            ELSE 0
                        END)
                     AS debit
                "),
                // CREDIT logic
                DB::raw("SUM(
                        CASE
                            WHEN petty_cashes.type = 1 AND currencies.priority = 2
                            THEN
                                CASE
                                    WHEN petty_cashes.exchange_rate IS NOT NULL
                                    THEN petty_cashes.amount * petty_cashes.exchange_rate
                                    ELSE COALESCE(
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                            AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ), 1
                                    ) * petty_cashes.amount
                                END
                            WHEN petty_cashes.type = 1 AND currencies.priority = 1
                            THEN petty_cashes.amount
                            ELSE 0
                        END)
                     AS credit
                ")
            )
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol', 'type')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('client_accounts.deleted_at')
            ->get();

        $balances = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currency_symbol',
                // Debit Calculation with Currency Conversion
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 1 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 1 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS debit
                "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 2 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 2 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS credit
                ")
            )
            ->where('opening_balances.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $opening = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.ledger_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'opening_balances.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // Fixed alias reference
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name', // Ensure full reference
                'accounts.account_name', // Ensure full reference
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                // Debit Calculation with Currency Conversion
                DB::raw("
            SUM(
                CASE
                    WHEN opening_balances.type = 1 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 1 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
            ) AS credit
        "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
            SUM(
                CASE
                    WHEN opening_balances.type = 2 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 2 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
            ) AS debit
        ")
            )
            ->where('opening_balances.financial_year_id', $id)
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $expenses = PurchaseItem::join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'purchases.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id')
            ->select('client_accounts.client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currencies.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN purchases.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS debit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN purchases.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS credit
                ")
            )
            ->where('purchases.financial_year_id', $id)
            ->groupBy('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->whereNull('purchase_items.deleted_at')
            ->get();

        $preferredOrder = ['ASSETS', 'LIABILITIES', 'EQUITY'];
        $combinedResults = collect([])
            ->merge($receipts)
            ->merge($incomes)
            ->merge($clients)
            ->merge($invoices)
            ->merge($payments)
            ->merge($expenses)
            ->merge($purchases)
            ->merge($crossPayments)
            ->merge($journalsCr)
            ->merge($journalDr)
            ->merge($balances)
            ->merge($opening)
            ->merge($cashes)
            ->sortBy(function ($item) use ($preferredOrder) {
                $index = array_search(strtoupper($item->account_name), $preferredOrder);
                return $index !== false ? $index : count($preferredOrder);
            })
            ->values();

        /*// Reclassify REVENUE (and optionally INCOME) as EQUITY before grouping
        $combinedResults = $combinedResults->map(function ($item) {
            $name = strtoupper($item->account_name ?? '');
            if (in_array($name, ['REVENUE', 'INCOME', 'EXPENSES'])) {
                $item->account_name = 'EQUITY';
            }
            return $item;
        });*/
        // Consolidate REVENUE, INCOME, and EXPENSES under 'Retained Earnings' in EQUITY
        $combinedResults = $combinedResults->map(function ($item) {
            $name = strtoupper($item->account_name ?? '');
            if (in_array($name, ['REVENUE', 'INCOME', 'EXPENSES'])) {
                $item->account_name = 'EQUITY';
                $item->chart_name = 'Retained Earnings';
                $item->chart_number = 'RETAINED'; // Optional, for uniqueness
                $item->client_account_id = 'retained_earnings';
                $item->client_account_name = 'Retained Earnings';
            }
            return $item;
        });

// Group and process balance sheet structure
        $accounts = $combinedResults
            ->groupBy('account_name') // 1st level group (ASSETS, LIABILITIES, EQUITY)
            ->map(function ($accountGroup, $accountName) {
                $accountName = strtoupper(trim($accountName));
                $accountDebit = $accountGroup->sum('debit');
                $accountCredit = $accountGroup->sum('credit');

                // Determine balance calculation based on account type
                $accountBalance = ($accountName === 'ASSETS')
                    ? $accountDebit - $accountCredit  // Assets: Debit - Credit
                    : $accountCredit - $accountDebit; // Liabilities/Equity: Credit - Debit

                $charts = $accountGroup
                    ->groupBy('chart_name') // 2nd level group (e.g., Bank, Cash, Tax Payable)
                    ->map(function ($chartGroup, $chartName) use ($accountName) {
                        $chartFirst = (object) $chartGroup->first();
                        $chartDebit = $chartGroup->sum('debit');
                        $chartCredit = $chartGroup->sum('credit');

                        // Chart level balance
                        $chartBalance = ($accountName === 'ASSETS')
                            ? $chartDebit - $chartCredit
                            : $chartCredit - $chartDebit;

                        $ledgers = $chartGroup
                            ->groupBy('client_account_id') // 3rd level group (individual ledgers)
                            ->map(function ($ledgerGroup) use ($accountName) {
                                $ledgerFirst = (object) $ledgerGroup->first();
                                $debit = $ledgerGroup->sum('debit');
                                $credit = $ledgerGroup->sum('credit');

                                $balance = ($accountName === 'ASSETS')
                                    ? $debit - $credit
                                    : $credit - $debit;

                                return [
                                    'client_account_id' => $ledgerFirst->client_account_id,
                                    'client_account_name' => $ledgerFirst->client_account_name,
                                    'currency_symbol' => $ledgerFirst->currency_symbol,
                                    'debit' => $debit,
                                    'credit' => $credit,
                                    'balance' => $balance,
                                ];
                            })->values();

                        return [
                            'chart_name' => $chartName,
                            'chart_number' => $chartFirst->chart_number,
                            'debit' => $chartDebit,
                            'credit' => $chartCredit,
                            'balance' => $chartBalance,
                            'ledgers' => $ledgers,
                        ];
                    })->values();

                return [
                    'account_name' => $accountName,
                    'debit' => $accountDebit,
                    'credit' => $accountCredit,
                    'balance' => $accountBalance,
                    'charts' => $charts,
                ];
            })->values();
        return $accounts;
    }
    public function fetchTrialBalance($financial, $id)
    {
        $receipts = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.account_id')
            ->join('client_accounts as account', 'account.client_account_id', '=', 'transactions.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'account.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                // Summing up debit values
                DB::raw("SUM(
                        CASE
                            WHEN currencies.priority = 2 AND curr.priority = 1 OR currencies.priority = 2 AND curr.priority = 2 THEN
                                CASE
                                    WHEN transactions.exchange_rate IS NOT NULL THEN
                                        transactions.amount_received * transactions.exchange_rate
                                    ELSE
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                              AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ) * transactions.amount_received
                                END
                            ELSE transactions.amount_received
                        END)
                     AS debit
                "),
                DB::raw("0.00 AS credit"),
            )
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->where('transactions.financial_year_id', $id)
            ->get();

        $clients = DB::table('transactions')
            ->join('client_accounts', function ($join) {
                $join->on('client_accounts.client_account_id', '=', 'transactions.client_id');
            })
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies as client_currency', 'client_currency.currency_id', '=', 'client_accounts.currency_id')
            ->join('client_accounts as account', function ($join) {
                $join->on('account.client_account_id', '=', 'transactions.account_id');
            })
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->leftJoin(DB::raw('(SELECT currency_id FROM currencies WHERE priority != 1 LIMIT 1) as priority_currency'), function ($join) {
                $join->on('priority_currency.currency_id', '=', 'client_accounts.currency_id')
                    ->orOn('priority_currency.currency_id', '=', 'account.currency_id');
            })
            ->leftJoin('forex_exchanges', function ($join) {
                $join->on('forex_exchanges.currency_id', '=', 'priority_currency.currency_id')
                    ->whereRaw('forex_exchanges.date_active = (
                SELECT MAX(fx.date_active)
                FROM forex_exchanges AS fx
                WHERE fx.date_active <= FROM_UNIXTIME(transactions.date_received)
                AND fx.currency_id = priority_currency.currency_id
            )');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("
            SUM(
                CASE
                    WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 1 AND account_currency.priority = 2 OR client_currency.priority = 2 AND account_currency.priority = 2
                        THEN transactions.amount_received * COALESCE(forex_exchanges.exchange_rate, 1)
                    WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 1 AND account_currency.priority = 2 OR client_currency.priority = 2 AND account_currency.priority = 2
                        THEN transactions.amount_received * transactions.exchange_rate
                    ELSE
                    transactions.amount_received
                END
            ) AS credit
        "),
                'account.client_account_name as ledger_name',
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('transactions.deleted_at')
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                'account.client_account_name',
            )
            ->get();

        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.invoice_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND invoices.type = 1 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND invoices.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS debit"),
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND invoices.type = 2 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND invoices.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS credit")
            )
            ->where('invoices.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at')
            ->get();

        $payments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.account_id')
            ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.client_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                DB::raw("SUM(
    CASE
        -- New condition: cross-priority (one is 2, the other is 1)
        WHEN (account_currency.priority = 2 AND currencies.priority = 1)
          OR (currencies.priority = 2 AND account_currency.priority = 1) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is NULL or =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is present OR =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * payments.exchange_rate

        -- Default case
        ELSE payments.amount_received
    END
) AS credit"),

                // DB::raw("SUM(
                //     CASE
                //         WHEN payments.exchange_rate IS NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * COALESCE((
                //                 SELECT fx2.exchange_rate
                //                 FROM forex_exchanges AS fx2
                //                 WHERE (
                //                     fx2.currency_id = currencies.currency_id OR fx2.currency_id = account_currency.currency_id
                //                 )
                //                 AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                //                 ORDER BY fx2.date_active DESC
                //                 LIMIT 1
                //             ), 1)
                //         WHEN payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * payments.exchange_rate
                //         ELSE payments.amount_received
                //     END) AS credit
                // "),
                DB::raw("0.00 AS debit"),
            )
            ->groupBy(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol'
            )
            ->where('payments.financial_year_id', $id)
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('invoice_number', 'asc')
            ->get();

        $crossPayments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.account_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                // DB::raw("SUM(
                //     CASE
                //         WHEN payments.exchange_rate IS NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * COALESCE((
                //                 SELECT fx2.exchange_rate
                //                 FROM forex_exchanges AS fx2
                //                 WHERE fx2.currency_id = account_currency.currency_id
                //                   AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                //                 ORDER BY fx2.date_active DESC
                //                 LIMIT 1
                //             ), 1)
                //         WHEN payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                //             payments.amount_received * payments.exchange_rate
                //         ELSE payments.amount_received
                //     END) AS debit
                // "),
                DB::raw("SUM(
    CASE
        -- New condition: cross-priority (one is 2, the other is 1)
        WHEN (account_currency.priority = 2 AND currencies.priority = 1)
          OR (currencies.priority = 2 AND account_currency.priority = 1) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is NULL or =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * COALESCE((
                SELECT fx2.exchange_rate
                FROM forex_exchanges AS fx2
                WHERE fx2.currency_id IN (currencies.currency_id, account_currency.currency_id)
                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                ORDER BY fx2.date_active DESC
                LIMIT 1
            ), 1)

        -- When exchange_rate is present OR =1 AND account/currency priority = 2
        WHEN (payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1)
          AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
            payments.amount_received * payments.exchange_rate

        -- Default case
        ELSE payments.amount_received
    END
) AS debit"),


                DB::raw("0.00 AS credit"),
            )
            ->groupBy(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol'
            )
            ->where('payments.financial_year_id', $id)
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('invoice_number', 'asc')
            ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.purchase_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND purchases.type = 2 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND purchases.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS debit"),

                DB::raw("SUM(CASE WHEN currencies.priority = 1 AND purchases.type = 1 THEN (unit_price * quantity)
                    WHEN currencies.priority = 2 AND purchases.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * (unit_price * quantity)
                    ELSE 0 END
                    ) AS credit"),
            )
            ->where('purchases.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('purchase_items.deleted_at')
            ->get();

        $incomes = InvoiceItem::join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id') // ledger
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id') // ledger currency
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'invoices.client_id') // client
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // client currency
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN invoices.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS credit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN invoices.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS debit
                ")
            )
            ->where('invoices.financial_year_id', $id)
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('client_accounts.client_account_name')
            ->get();

       $journalDr = DB::table('adjustment_journals as debit')
            ->join('client_accounts as debit_account', 'debit.ledger_id', '=', 'debit_account.client_account_id')
            ->join('currencies as cur_debit', 'debit_account.currency_id', '=', 'cur_debit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'debit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin(DB::raw('
                (
                    SELECT aj_credit.*,
                           ca.client_account_name AS credit_account_name,
                           ca.currency_id AS credit_currency_id
                    FROM adjustment_journals aj_credit
                    JOIN client_accounts ca ON aj_credit.ledger_id = ca.client_account_id
                    WHERE aj_credit.type = 2 AND aj_credit.deleted_at IS NULL
                      AND aj_credit.adjustment_journal_id IN (
                          SELECT MAX(inner_aj.adjustment_journal_id)
                          FROM adjustment_journals inner_aj
                          WHERE inner_aj.type = 2 AND inner_aj.deleted_at IS NULL
                          GROUP BY inner_aj.reference_code
                      )
                ) as credit
            '), 'credit.reference_code', '=', 'debit.reference_code')
            ->leftJoin('currencies as cur_credit', 'credit.credit_currency_id', '=', 'cur_credit.currency_id')
            ->select(
                'debit_account.client_account_id',
                'debit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_debit.currency_symbol',
                DB::raw("SUM(
                    CASE
                        WHEN cur_debit.priority = 2 AND cur_credit.priority = 1 OR cur_credit.priority = 2 AND cur_debit.priority = 1 OR cur_credit.priority = 2 AND cur_debit.priority = 2 THEN
                            CASE
                                WHEN debit.exchange_rate != 1 THEN debit.exchange_rate * debit.amount
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE (fx2.currency_id = cur_debit.currency_id OR fx2.currency_id = credit.credit_currency_id OR cur_credit.currency_id)
                                    AND fx2.date_active <= FROM_UNIXTIME(debit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * debit.amount
                            END
                        ELSE debit.amount
                    END
                    )
                    as debit
                "),
                DB::raw("0 as credit"),
                'debit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'credit.credit_account_name as ledger_name',
                'debit.description',
                'debit.reference_code as transaction_number',
                'debit_account.type'
            )
            ->groupBy(
                'debit_account.client_account_id',
                'debit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_debit.currency_symbol',
                'debit.date_adjusted',
                'credit.credit_account_name',
                'debit.description',
                'debit.reference_code',
                'debit_account.type'
            )
            ->whereNull('debit.deleted_at')
            ->where('debit.type', 1)
            ->whereBetween('debit.date_adjusted', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('debit_account.client_account_name')
            ->get();

        $journalsCr = DB::table('adjustment_journals as credit')
            ->join('client_accounts as credit_account', 'credit.ledger_id', '=', 'credit_account.client_account_id')
            ->join('currencies as cur_credit', 'credit_account.currency_id', '=', 'cur_credit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'credit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin(DB::raw('(
        SELECT aj_debit.*,
               ca.client_account_name AS debit_account_name,
               ca.currency_id AS debit_currency_id
        FROM adjustment_journals aj_debit
        JOIN client_accounts ca ON aj_debit.ledger_id = ca.client_account_id
        WHERE aj_debit.type = 1 AND aj_debit.deleted_at IS NULL
          AND aj_debit.adjustment_journal_id IN (
              SELECT MAX(inner_aj.adjustment_journal_id)
              FROM adjustment_journals inner_aj
              WHERE inner_aj.type = 1 AND inner_aj.deleted_at IS NULL
              GROUP BY inner_aj.reference_code
          )
    ) as debit'), 'credit.reference_code', '=', 'debit.reference_code')
            ->leftJoin('currencies as cur_debit', 'debit.debit_currency_id', '=', 'cur_debit.currency_id')
            ->select(
                'credit_account.client_account_id',
                'credit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_credit.currency_symbol',
                DB::raw("0 as debit"),
                /*DB::raw("SUM(
                    CASE
                        WHEN cur_credit.priority = 2 AND cur_debit.priority = 1 OR cur_debit.priority = 2 AND cur_credit.priority = 1 THEN
                            CASE
                                WHEN debit.exchange_rate IS NOT NULL AND debit.exchange_rate != 1 THEN credit.amount * debit.exchange_rate
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = debit.debit_currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(credit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * credit.amount
                            END
                        ELSE credit.amount
                    END) as credit
                "),*/
                DB::raw("SUM(
                    CASE
                        WHEN cur_debit.priority = 2 OR cur_credit.priority = 2 OR cur_credit.priority = 2 AND cur_debit.priority = 2 THEN
                            CASE
                                WHEN debit.exchange_rate != 1 THEN credit.amount * debit.exchange_rate
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE (fx2.currency_id = debit.debit_currency_id OR fx2.currency_id = cur_credit.currency_id OR fx2.currency_id = cur_debit.currency_id)
                                    AND fx2.date_active <= FROM_UNIXTIME(credit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * credit.amount
                            END
                        ELSE credit.amount
                    END) as credit
                "),
                'credit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'debit.debit_account_name as ledger_name',
                'credit.description',
                'credit.reference_code as transaction_number',
                'credit_account.type'
            )
            ->groupBy(
                'credit_account.client_account_id',
                'credit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_credit.currency_symbol',
                'credit.date_adjusted',
                'debit.debit_account_name',
                'credit.description',
                'credit.reference_code',
                'credit_account.type'
            )
            ->whereNull('credit.deleted_at')
            ->where('credit.type', 2)
            ->whereBetween('credit.date_adjusted', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('credit_account.client_account_name')
            ->get();

        $cashes = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('petty_cashes', function ($join) use ($financial) {
                $join->on('petty_cashes.ledger_id', '=', 'client_accounts.client_account_id')
                    ->where('date_invoiced', '>=', strtotime($financial->year_starting))
                    ->where('date_invoiced', '<=', strtotime($financial->year_ending))
                    ->whereNull('petty_cashes.deleted_at');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                'client_accounts.type',
                // DEBIT logic
                DB::raw("SUM(
                        CASE
                            WHEN petty_cashes.type = 2 AND currencies.priority = 2
                            THEN
                                CASE
                                    WHEN petty_cashes.exchange_rate IS NOT NULL
                                    THEN petty_cashes.amount * petty_cashes.exchange_rate
                                    ELSE COALESCE(
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                            AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ), 1
                                    ) * petty_cashes.amount
                                END
                            WHEN petty_cashes.type = 2 AND currencies.priority = 1
                            THEN petty_cashes.amount
                            ELSE 0
                        END)
                     AS debit
                "),
                // CREDIT logic
                DB::raw("SUM(
                        CASE
                            WHEN petty_cashes.type = 1 AND currencies.priority = 2
                            THEN
                                CASE
                                    WHEN petty_cashes.exchange_rate IS NOT NULL
                                    THEN petty_cashes.amount * petty_cashes.exchange_rate
                                    ELSE COALESCE(
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                            AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ), 1
                                    ) * petty_cashes.amount
                                END
                            WHEN petty_cashes.type = 1 AND currencies.priority = 1
                            THEN petty_cashes.amount
                            ELSE 0
                        END)
                     AS credit
                ")
            )
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol', 'type')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('client_accounts.deleted_at')
            ->get();

        $balances = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currency_symbol',
                // Debit Calculation with Currency Conversion
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 1 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 1 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS debit
                "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 2 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 2 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS credit
                ")
            )
            ->where('opening_balances.financial_year_id', $id)
            ->groupBy('client_accounts.client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $opening = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.ledger_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'opening_balances.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // Fixed alias reference
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name', // Ensure full reference
                'accounts.account_name', // Ensure full reference
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                // Debit Calculation with Currency Conversion
                DB::raw("
            SUM(
                CASE
                    WHEN opening_balances.type = 1 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 1 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
            ) AS credit
        "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
            SUM(
                CASE
                    WHEN opening_balances.type = 2 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 2 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
            ) AS debit
        ")
            )
            ->where('opening_balances.financial_year_id', $id)
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $expenses = PurchaseItem::join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'purchases.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id')
            ->select('client_accounts.client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currencies.currency_symbol',
                DB::raw("
                    SUM(
                        CASE
                            WHEN purchases.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS debit
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN purchases.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                    ) AS credit
                ")
            )
            ->where('purchases.financial_year_id', $id)
            ->groupBy('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol')
            ->whereNull('purchase_items.deleted_at')
            ->get();

        $preferredOrder = ['ASSETS', 'LIABILITIES', 'REVENUE', 'EXPENSES'];
        $combinedResults = collect([])
            ->merge($receipts)
            ->merge($payments)
            ->merge($incomes)
            ->merge($expenses)
            ->merge($purchases)
            ->merge($clients)
            ->merge($invoices)
            ->merge($crossPayments)
            ->merge($journalsCr)
            ->merge($journalDr)
            ->merge($balances)
            ->merge($opening)
            ->merge($cashes)
            ->sortBy(function ($item) use ($preferredOrder) {
                $index = array_search(strtoupper($item->account_name), $preferredOrder);
                return $index !== false ? $index : count($preferredOrder);
            })
            ->values();

        // Group and process balance sheet structure
        $accounts = $combinedResults
            ->groupBy('account_name') // 1st level group (ASSETS, LIABILITIES, EQUITY)
            ->map(function ($accountGroup, $accountName) {
                $accountName = strtoupper(trim($accountName));
                $accountDebit = $accountGroup->sum('debit');
                $accountCredit = $accountGroup->sum('credit');

                // Determine balance calculation based on account type
                $accountBalance = $accountDebit - $accountCredit; // Liabilities/Equity: Credit - Debit

                $charts = $accountGroup
                    ->groupBy('chart_name') // 2nd level group (e.g., Bank, Cash, Tax Payable)
                    ->map(function ($chartGroup, $chartName) use ($accountName) {
                        $chartFirst = (object) $chartGroup->first();
                        $chartDebit = $chartGroup->sum('debit');
                        $chartCredit = $chartGroup->sum('credit');

                        // Chart level balance
                        $chartBalance = $chartDebit - $chartCredit;

                        $ledgers = $chartGroup
                            ->groupBy('client_account_id') // 3rd level group (individual ledgers)
                            ->map(function ($ledgerGroup) use ($accountName) {
                                $ledgerFirst = (object) $ledgerGroup->first();
                                $debit = $ledgerGroup->sum('debit');
                                $credit = $ledgerGroup->sum('credit');

                                $balance = $debit - $credit;

                                return [
                                    'client_account_id' => $ledgerFirst->client_account_id,
                                    'client_account_name' => $ledgerFirst->client_account_name,
                                    'currency_symbol' => $ledgerFirst->currency_symbol,
                                    'debit' => $debit,
                                    'credit' => $credit,
                                    'balance' => $balance,
                                ];
                            })->values();

                        return [
                            'chart_name' => $chartName,
                            'chart_number' => $chartFirst->chart_number,
                            'debit' => $chartDebit,
                            'credit' => $chartCredit,
                            'balance' => $chartBalance,
                            'ledgers' => $ledgers,
                        ];
                    })->values();

                return [
                    'account_name' => $accountName,
                    'debit' => $accountDebit,
                    'credit' => $accountCredit,
                    'balance' => $accountBalance,
                    'charts' => $charts,
                ];
            })->values();
        return $accounts;
    }
    public function viewLedgerStatement ($client, $id, $financial, $opBal = true){

        $receipts = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.account_id')
            ->join('client_accounts as account', 'account.client_account_id', '=', 'transactions.client_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('currencies as client_currency', 'client_currency.currency_id', '=', 'client_accounts.currency_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
//            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->leftJoin(DB::raw('(SELECT currency_id FROM currencies WHERE priority != 1 LIMIT 1) as priority_currency'), function ($join) {
                $join->on('priority_currency.currency_id', '=', 'client_accounts.currency_id')
                    ->orOn('priority_currency.currency_id', '=', 'account.currency_id');
            })
            ->leftJoin('forex_exchanges', function ($join) {
                $join->on('forex_exchanges.currency_id', '=', 'priority_currency.currency_id')
                    ->whereRaw('forex_exchanges.date_active = (
                SELECT MAX(fx.date_active)
                FROM forex_exchanges AS fx
                WHERE fx.date_active <= FROM_UNIXTIME(transactions.date_received)
                AND fx.currency_id = priority_currency.currency_id
            )');
            })
            ->select(
                'transactions.date_received as transaction_date',
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                // Summing up debit values
                DB::raw("
                        CASE
                            WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 1 AND account_currency.priority = 2
                                THEN transactions.amount_received
                            WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 1 AND account_currency.priority = 2
                                THEN transactions.amount_received *  COALESCE(forex_exchanges.exchange_rate, 1)
                                WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 2 AND account_currency.priority = 1
                                THEN transactions.amount_received
                            WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 2 AND account_currency.priority = 1
                                THEN transactions.amount_received /  COALESCE(forex_exchanges.exchange_rate, 1)
                            ELSE
                            transactions.amount_received
                        END
                     AS debit
                "),
                DB::raw("0.00 AS credit"),
                DB::raw("'Receipt' AS transaction_type"),
                'client_accounts.type',
                'account.client_account_name as ledger_name',
                'transactions.invoice_number as transaction_number',
                'transactions.description'
            )
            ->where(['transactions.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->get();

       $clients = DB::table('transactions')
            ->join('client_accounts', function ($join) {
                $join->on('client_accounts.client_account_id', '=', 'transactions.client_id');
            })
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies as client_currency', 'client_currency.currency_id', '=', 'client_accounts.currency_id')
            ->join('client_accounts as account', function ($join) {
                $join->on('account.client_account_id', '=', 'transactions.account_id');
            })
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->leftJoin(DB::raw('(SELECT currency_id FROM currencies WHERE priority != 1 LIMIT 1) as priority_currency'), function ($join) {
                $join->on('priority_currency.currency_id', '=', 'client_accounts.currency_id')
                    ->orOn('priority_currency.currency_id', '=', 'account.currency_id');
            })
            ->leftJoin('forex_exchanges', function ($join) {
                $join->on('forex_exchanges.currency_id', '=', 'priority_currency.currency_id')
                    ->whereRaw('forex_exchanges.date_active = (
                SELECT MAX(fx.date_active)
                FROM forex_exchanges AS fx
                WHERE fx.date_active <= FROM_UNIXTIME(transactions.date_received)
                AND fx.currency_id = priority_currency.currency_id
            )');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("'Receipt' as transaction_type"),
                DB::raw("
                        CASE
                            WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 1 AND account_currency.priority = 2
                                THEN transactions.amount_received * COALESCE(forex_exchanges.exchange_rate, 1)
                            WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 1 AND account_currency.priority = 2
                                THEN transactions.amount_received
                                WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 2 AND account_currency.priority = 1
                                THEN transactions.amount_received / COALESCE(forex_exchanges.exchange_rate, 1)
                            WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 2 AND account_currency.priority = 1
                                THEN transactions.amount_received
                            ELSE
                            transactions.amount_received
                        END
                     AS credit
                "),
                'account.client_account_name as ledger_name',
                'transactions.date_received as transaction_date',
                'transactions.invoice_number as transaction_number',
                'transactions.description',
                'client_accounts.type'
            )
            ->where(['transactions.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->whereNull('transactions.deleted_at')
            ->get();

        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->leftJoin(DB::raw('(
                    SELECT *
                    FROM (
                        SELECT
                            ii.invoice_id,
                            (ii.quantity * ii.unit_price) AS item_total,
                            ii.ledger_id,
                            ca.client_account_name,
                            ROW_NUMBER() OVER (
                                PARTITION BY ii.invoice_id
                                ORDER BY (ii.quantity * ii.unit_price) DESC, ca.client_account_name ASC
                            ) as row_num
                        FROM invoice_items ii
                        JOIN client_accounts ca ON ca.client_account_id = ii.ledger_id
                    ) as ranked_items
                    WHERE row_num = 1
                ) as highest_value_item'), 'invoices.invoice_id', '=', 'highest_value_item.invoice_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("CASE WHEN invoices.type = 1 THEN amount_due ELSE 0 END AS debit"),
                DB::raw("CASE WHEN invoices.type = 2 THEN amount_due ELSE 0 END AS credit"),
                DB::raw("'Sale' as transaction_type"),
                'invoices.date_invoiced as transaction_date',
                'invoices.invoice_number as transaction_number',
                DB::raw("CONCAT(COALESCE(invoices.si_number, ''), ': ', COALESCE(invoices.customer_message, '')) as description"),
                'highest_value_item.client_account_name as ledger_name',
                'client_accounts.type'
            )
            ->where(['invoices.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $payments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.account_id')

            // Get top client per transaction_code
            ->leftJoin(DB::raw("(
        SELECT *
        FROM (
            SELECT
                p.transaction_code,
                ca.client_account_name AS ledger_name,
                ROW_NUMBER() OVER (
                    PARTITION BY p.transaction_code
                    ORDER BY p.amount_received DESC
                ) AS row_num
            FROM payments p
            JOIN client_accounts ca ON ca.client_account_id = p.client_id
            WHERE p.deleted_at IS NULL
        ) ranked
        WHERE row_num = 1
    ) as top_client"), 'payments.transaction_code', '=', 'top_client.transaction_code')

            ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.client_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')

            ->select(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                DB::raw("SUM(CASE
            WHEN payments.exchange_rate IS NULL AND (account_currency.priority = 2 AND currencies.currency_id != account_currency.currency_id) THEN
                payments.amount_received * COALESCE((
                    SELECT fx2.exchange_rate
                    FROM forex_exchanges AS fx2
                    WHERE fx2.currency_id = account_currency.currency_id
                    AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                    ORDER BY fx2.date_active DESC
                    LIMIT 1
                ), 1)
            WHEN payments.exchange_rate IS NOT NULL AND (account_currency.priority = 2 AND currencies.currency_id != account_currency.currency_id) THEN
                payments.amount_received * payments.exchange_rate
            ELSE payments.amount_received
        END) AS credit"),
                DB::raw("0.00 AS debit"),
                DB::raw("'Payment' AS transaction_type"),
                'payments.date_received as transaction_date',
                'payments.transaction_code as transaction_number',
                'payments.description',
                DB::raw('MAX(top_client.ledger_name) as ledger_name'), // ensures grouping compatibility
                'client_ca.type'
            )

            ->groupBy([
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                'payments.date_received',
                'payments.transaction_code',
                'payments.description',
                'client_ca.type'
            ])

            ->where([
                'payments.financial_year_id' => $id,
                'client_ca.client_account_id' => $client
            ])
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('payments.transaction_code', 'asc')
            ->get();


        $crossPayments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.account_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                DB::raw("CASE
                        WHEN payments.exchange_rate IS NULL AND (account_currency.priority = 2 AND currencies.currency_id != account_currency.currency_id) THEN
                            payments.amount_received * COALESCE((
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE (fx2.currency_id = account_currency.currency_id)
                                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ), 1)
                        WHEN payments.exchange_rate IS NOT NULL AND (account_currency.priority = 2 AND currencies.currency_id != account_currency.currency_id) THEN
                            payments.amount_received * payments.exchange_rate
                        ELSE payments.amount_received
                    END AS debit
                "),
                DB::raw("0.00 AS credit"),
                DB::raw("'Payment' AS transaction_type"),
                'payments.date_received as transaction_date',
                'payments.invoice_number as transaction_number',
                'account_ca.client_account_name as ledger_name',
                'payments.description',
                'client_ca.type'
            )
            ->where(['payments.financial_year_id' => $id, 'client_ca.client_account_id' => $client])
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('invoice_number', 'asc')
            ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->leftJoin(DB::raw('(
                    SELECT *
                    FROM (
                        SELECT
                            pi.purchase_id,
                            (pi.quantity * pi.unit_price) AS item_total,
                            pi.ledger_id,
                            ca.client_account_name,
                            ROW_NUMBER() OVER (
                                PARTITION BY pi.purchase_id
                                ORDER BY (pi.quantity * pi.unit_price) DESC, ca.client_account_name ASC
                            ) as row_num
                        FROM purchase_items pi
                        JOIN client_accounts ca ON ca.client_account_id = pi.ledger_id
                    ) as ranked_items
                    WHERE row_num = 1
                ) as highest_value_item'), 'purchases.purchase_id', '=', 'highest_value_item.purchase_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol', 'highest_value_item.client_account_name as ledger_name',
                DB::raw("CASE WHEN purchases.type = 2 THEN amount_due ELSE 0 END AS debit"),
                DB::raw("CASE WHEN purchases.type = 1 THEN amount_due ELSE 0 END AS credit"),
                DB::raw("'Purchase' as transaction_type"),
                'purchases.date_invoiced as transaction_date',
                'purchases.invoice_number as transaction_number',
                DB::raw("CONCAT(purchases.voucher_number,': ',purchases.customer_message) as description"), 'client_accounts.type'
            )
            ->where(['purchases.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->get();

        $incomes = InvoiceItem::join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id') // ledger
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id') // ledger currency
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'invoices.client_id') // client
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // client currency
            ->select(
                'invoices.invoice_number as transaction_number',
                'invoices.date_invoiced as transaction_date',
                DB::raw("CONCAT(COALESCE(invoices.si_number, ''), ': ', COALESCE(invoices.customer_message, '')) as description"),
                'acc.client_account_name as ledger_name',
                DB::raw("'Sale' as transaction_type"),
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("SUM(
                        CASE
                            WHEN invoices.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END)
                     AS credit
                "),
                DB::raw("SUM(
                        CASE
                            WHEN invoices.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END)
                     AS debit
                "),
                'client_accounts.type'
            )
            ->where(['invoices.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('client_accounts.client_account_name')
            ->groupBy('invoice_number')
            ->get();

        $journalDr = DB::table('adjustment_journals as debit')
            ->join('client_accounts as debit_account', 'debit.ledger_id', '=', 'debit_account.client_account_id')
            ->join('currencies as cur_debit', 'debit_account.currency_id', '=', 'cur_debit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'debit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            // Derived subquery: get ONE credit per reference_code using MAX(id)
            ->leftJoin(DB::raw('
                (
                    SELECT aj_credit.*,
                           ca.client_account_name AS credit_account_name,
                           ca.currency_id AS credit_currency_id
                    FROM adjustment_journals aj_credit
                    JOIN client_accounts ca ON aj_credit.ledger_id = ca.client_account_id
                    WHERE aj_credit.type = 2 AND aj_credit.deleted_at IS NULL
                      AND aj_credit.adjustment_journal_id IN (
                          SELECT MAX(inner_aj.adjustment_journal_id)
                          FROM adjustment_journals inner_aj
                          WHERE inner_aj.type = 2 AND inner_aj.deleted_at IS NULL
                          GROUP BY inner_aj.reference_code
                      )
                ) as credit
            '), 'credit.reference_code', '=', 'debit.reference_code')
            ->leftJoin('currencies as cur_credit', 'credit.credit_currency_id', '=', 'cur_credit.currency_id')
            ->select(
                'debit_account.client_account_id',
                'debit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_debit.currency_symbol',
                DB::raw("
                    CASE
                        WHEN cur_debit.priority = 2 AND cur_credit.priority = 1 OR cur_credit.priority = 2 AND cur_debit.priority = 1 THEN
                            CASE
                                WHEN credit.exchange_rate IS NOT NULL AND credit.exchange_rate != 1 THEN debit.amount * credit.exchange_rate
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = credit.credit_currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(debit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * debit.amount
                            END
                        ELSE debit.amount
                    END as debit
                "),
                DB::raw("0 as credit"),
                'debit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'credit.credit_account_name as ledger_name',
                'debit.description',
                'debit.reference_code as transaction_number',
                'debit_account.type'
            )
            ->whereNull('debit.deleted_at')
            ->where('debit.type', 1)
            ->whereBetween('debit.date_adjusted', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->where('debit_account.client_account_id', $client)
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('debit_account.client_account_name')
            ->get();

        $journalsCr = DB::table('adjustment_journals as credit')
            ->join('client_accounts as credit_account', 'credit.ledger_id', '=', 'credit_account.client_account_id')
            ->join('currencies as cur_credit', 'credit_account.currency_id', '=', 'cur_credit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'credit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin(DB::raw('(
                SELECT *
                FROM adjustment_journals aj_sub
                WHERE aj_sub.type = 1
                  AND aj_sub.deleted_at IS NULL
                  AND aj_sub.adjustment_journal_id IN (
                      SELECT aj_inner_with_max_id.adjustment_journal_id
                      FROM (
                          SELECT reference_code, MAX(amount) AS max_amount
                          FROM adjustment_journals
                          WHERE type = 1 AND deleted_at IS NULL
                          GROUP BY reference_code
                      ) max_amounts
                      JOIN adjustment_journals aj_inner_with_max_id
                        ON aj_inner_with_max_id.reference_code = max_amounts.reference_code
                       AND aj_inner_with_max_id.amount = max_amounts.max_amount
                       AND aj_inner_with_max_id.type = 1
                       AND aj_inner_with_max_id.deleted_at IS NULL
                  )
            ) as debit'), function ($join) {
                            $join->on('credit.reference_code', '=', 'debit.reference_code')
                                ->whereRaw('credit.type = 2');
                        })

            ->leftJoin('client_accounts as debit_account', 'debit.ledger_id', '=', 'debit_account.client_account_id')
            ->leftJoin('currencies as cur_debit', 'debit_account.currency_id', '=', 'cur_debit.currency_id')
            ->select(
                'credit_account.client_account_id',
                'credit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_credit.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("
                    CASE
                        WHEN cur_credit.priority = 2 AND cur_debit.priority = 1 OR cur_debit.priority = 2 AND cur_credit.priority = 1 THEN
                            CASE
                                WHEN debit.exchange_rate IS NOT NULL AND debit.exchange_rate != 1 THEN credit.amount * debit.exchange_rate
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = debit_account.currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(credit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * credit.amount
                            END
                        ELSE credit.amount
                    END as credit
                "),
                'credit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'debit_account.client_account_name as ledger_name',  // show one debit name
                'credit.description',
                'credit.reference_code as transaction_number',
                'credit_account.type'
            )
            ->whereNull('credit.deleted_at')
            ->where('credit.type', 2)
            ->whereBetween('credit.date_adjusted', [strtotime($financial->year_starting), strtotime($financial->year_ending)])
            ->where(['credit_account.client_account_id' => $client])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('credit_account.client_account_name')
            ->get();

        $cashes = DB::table('petty_cashes as pc1')
            ->join('client_accounts as account1', 'pc1.ledger_id', '=', 'account1.client_account_id')
            ->join('currencies as cur1', 'account1.currency_id', '=', 'cur1.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'account1.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')

            // Subquery to get only ONE credit per reference_code (latest or highest id)
            ->leftJoin(DB::raw('
        (
            SELECT pc2.*, ca.client_account_name AS credit_account_name, ca.currency_id AS credit_currency_id
            FROM petty_cashes pc2
            JOIN client_accounts ca ON pc2.ledger_id = ca.client_account_id
            WHERE pc2.type = 1 AND pc2.deleted_at IS NULL
              AND pc2.petty_cash_id IN (
                  SELECT MAX(inner_pc.petty_cash_id)
                  FROM petty_cashes inner_pc
                  WHERE inner_pc.type = 1 AND inner_pc.deleted_at IS NULL
                  GROUP BY inner_pc.reference_code
              )
        ) as credit
    '), 'credit.reference_code', '=', 'pc1.reference_code')

            ->leftJoin('currencies as cur2', 'credit.credit_currency_id', '=', 'cur2.currency_id')

            ->select(
                'account1.client_account_id',
                'account1.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur1.currency_symbol',

                DB::raw("
            CASE
                WHEN pc1.type = 2 THEN
                    CASE
                        WHEN cur1.priority = 2 OR cur2.priority = 2 THEN
                            CASE
                                WHEN credit.exchange_rate IS NOT NULL AND credit.exchange_rate != 1 THEN pc1.amount * credit.exchange_rate
                                ELSE COALESCE(
                                    (
                                        SELECT fx2.exchange_rate
                                        FROM forex_exchanges AS fx2
                                        WHERE fx2.currency_id = credit.credit_currency_id
                                          AND fx2.date_active <= FROM_UNIXTIME(pc1.date_invoiced)
                                        ORDER BY fx2.date_active DESC
                                        LIMIT 1
                                    ), 1
                                ) * pc1.amount
                            END
                        ELSE pc1.amount
                    END
                ELSE 0
            END AS debit
        "),
                DB::raw("
            CASE
                WHEN pc1.type = 1 THEN
                    CASE
                        WHEN cur1.priority = 2 OR cur2.priority = 2 THEN
                            CASE
                                WHEN credit.exchange_rate IS NOT NULL AND credit.exchange_rate != 1 THEN pc1.amount * credit.exchange_rate
                                ELSE COALESCE(
                                    (
                                        SELECT fx2.exchange_rate
                                        FROM forex_exchanges AS fx2
                                        WHERE fx2.currency_id = credit.credit_currency_id
                                          AND fx2.date_active <= FROM_UNIXTIME(pc1.date_invoiced)
                                        ORDER BY fx2.date_active DESC
                                        LIMIT 1
                                    ), 1
                                ) * pc1.amount
                            END
                        ELSE pc1.amount
                    END
                ELSE 0
            END AS credit
        "),
                'pc1.date_invoiced as transaction_date',
                DB::raw("'P. Cash' as transaction_type"),
                'credit.credit_account_name as ledger_name',
                'pc1.description',
                'pc1.reference_code as transaction_number',
                'account1.type'
            )
            ->whereNull('pc1.deleted_at')
            ->whereBetween('pc1.date_invoiced', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->where('account1.client_account_id', $client)
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('account1.client_account_name')
            ->get();


        $balance = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                DB::raw("'Op Bal' as transaction_type"),
                DB::raw("'' as transaction_number"),
                DB::raw("'' as client_account_name"),
                DB::raw("'' as ledger_name"),
                DB::raw("'Opening Balance Adjustment' as description"),
                'opening_balances.date_invoiced as transaction_date',
                'currency_symbol',
                DB::raw("SUM(CASE WHEN opening_balances.type = 1 THEN opening_balances.amount ELSE 0 END) AS debit"),
                DB::raw("SUM(CASE WHEN opening_balances.type = 2 THEN opening_balances.amount ELSE 0 END) AS credit"),
                'client_accounts.type'
            )
            ->where(['opening_balances.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->groupBy('opening_balances.date_invoiced', 'currencies.currency_symbol', 'client_accounts.type')
            ->get();
        $balances = $opBal ? $balance : [];

        $opening = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.ledger_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'opening_balances.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // Fixed alias reference
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                DB::raw("'Op Bal' as transaction_type"),
                DB::raw("'Opening Balance Adjustment' as description"),
                DB::raw("'--' as transaction_number"),
                'client_accounts.client_account_id',
                'client_accounts.client_account_name', // Ensure full reference
                'acc.client_account_name as ledger_name', // Ensure full reference
                'accounts.account_name', // Ensure full reference
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                'opening_balances.date_invoiced as transaction_date',
                // Debit Calculation with Currency Conversion
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 1 AND curr.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = acc.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 1 AND curr.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS credit
                "),
                // Credit Calculation with Currency Conversion (Fixed)
                DB::raw("
                    SUM(
                        CASE
                            WHEN opening_balances.type = 2 AND curr.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = acc.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 2 AND curr.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                    ) AS debit
                "),
                'client_accounts.type'
            )
            ->where(['opening_balances.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                'acc.client_account_name',
                'opening_balances.date_invoiced','client_accounts.type'
            )
            ->orderBy('client_accounts.client_account_name')
            ->get();

        $expenses = PurchaseItem::join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'purchases.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id')
            ->select('client_accounts.client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currencies.currency_symbol',
                DB::raw("SUM(
                        CASE
                            WHEN purchases.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 AND curr.priority = 1 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 AND currencies.priority = 1 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END)
                     AS debit
                "),
                DB::raw("SUM(
                        CASE
                            WHEN purchases.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 AND curr.priority = 1 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 AND currencies.priority = 1 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END)
                     AS credit
                "),
                'purchases.date_invoiced as transaction_date',
                'purchases.invoice_number as transaction_number',
                DB::raw("'Purchase' as transaction_type"),
                'acc.client_account_name as ledger_name',
                DB::raw("CONCAT(purchases.voucher_number,': ',purchases.customer_message) as description"), 'client_accounts.type'
            )
            ->where(['purchases.financial_year_id' => $id, 'client_accounts.client_account_id' => $client])
            ->whereNull('purchase_items.deleted_at')
            ->groupBy('voucher_number')
            ->get();

    
        $combinedResults = collect([])
            ->merge($receipts)
            ->merge($payments)
            ->merge($incomes)
            ->merge($expenses)
            ->merge($purchases)
            ->merge($clients)
            ->merge($invoices)
            ->merge($crossPayments)
            ->merge($journalsCr)
            ->merge($journalDr)
            ->merge($balances)
            ->merge($opening)
            ->merge($cashes)
            ->filter(function ($item) {
                $debit = floatval($item->debit ?? 0);
                $credit = floatval($item->credit ?? 0);
                return $debit !== 0.0 || $credit !== 0.0;
            })

            ->sortBy(function ($item) {
                return [$item->transaction_date, $item->transaction_number];
            });
        return $combinedResults;
    }
    public function fetchPLStatement($financial, $id) {
         $receipts = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin('transactions', 'transactions.account_id', '=', 'client_accounts.client_account_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("CASE
                            WHEN currencies.priority = 2 THEN
                                CASE
                                    WHEN transactions.exchange_rate IS NOT NULL THEN
                                        transactions.amount_received * transactions.exchange_rate
                                    ELSE
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                              AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ) * transactions.amount_received
                                END
                            ELSE transactions.amount_received
                        END
                    AS debit
                "),
                // Summing up credit values (if needed)
                DB::raw("0.00 AS credit"),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'transactions.date_received as transaction_date'
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('transactions.deleted_at')
            ->get();
        /*$receipts = Transaction::join('client_accounts', 'client_accounts.client_account_id', '=', 'transactions.account_id')
            ->join('client_accounts as account', 'account.client_account_id', '=', 'transactions.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'account.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                // Summing up debit values
                DB::raw("SUM(
                        CASE
                            WHEN currencies.priority = 2 AND curr.priority = 1 OR currencies.priority = 2 AND curr.priority = 2 THEN
                                CASE
                                    WHEN transactions.exchange_rate IS NOT NULL THEN
                                        transactions.amount_received * transactions.exchange_rate
                                    ELSE
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                              AND fx2.date_active <= FROM_UNIXTIME(transactions.date_received)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ) * transactions.amount_received
                                END
                            ELSE transactions.amount_received
                        END)
                     AS debit
                "),
                DB::raw("0.00 AS credit"),
            )
            ->groupBy(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol'
            )
            ->where('transactions.financial_year_id', $id)
            ->get();*/

        $clients = DB::table('transactions')
            ->join('client_accounts', function ($join) {
                $join->on('client_accounts.client_account_id', '=', 'transactions.client_id');
            })
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies as client_currency', 'client_currency.currency_id', '=', 'client_accounts.currency_id')
            ->join('client_accounts as account', function ($join) {
                $join->on('account.client_account_id', '=', 'transactions.account_id');
            })
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account.currency_id')
            ->leftJoin(DB::raw('(SELECT currency_id FROM currencies WHERE priority != 1 LIMIT 1) as priority_currency'), function ($join) {
                $join->on('priority_currency.currency_id', '=', 'client_accounts.currency_id')
                    ->orOn('priority_currency.currency_id', '=', 'account.currency_id');
            })
            ->leftJoin('forex_exchanges', function ($join) {
                $join->on('forex_exchanges.currency_id', '=', 'priority_currency.currency_id')
                    ->whereRaw('forex_exchanges.date_active = (
                SELECT MAX(fx.date_active)
                FROM forex_exchanges AS fx
                WHERE fx.date_active <= FROM_UNIXTIME(transactions.date_received)
                AND fx.currency_id = priority_currency.currency_id
            )');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'client_currency.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("CASE
                            WHEN transactions.exchange_rate IS NULL AND client_currency.priority = 2
                                THEN transactions.amount_received * COALESCE(forex_exchanges.exchange_rate, 1)
                            WHEN transactions.exchange_rate IS NOT NULL AND client_currency.priority = 2
                                THEN transactions.amount_received * transactions.exchange_rate
                            ELSE
                            transactions.amount_received
                        END
                    AS credit
                "),
                'account.client_account_name as ledger_name',
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_received as transaction_date'
            )
            ->where('transactions.financial_year_id', $id)
            ->whereNull('transactions.deleted_at')
            ->get();

        $invoices = Invoice::join('client_accounts', 'client_accounts.client_account_id', '=', 'invoices.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("CASE WHEN currencies.priority = 1 AND invoices.type = 1 THEN amount_due
                    WHEN currencies.priority = 2 AND invoices.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    AS debit"),
                DB::raw("CASE WHEN currencies.priority = 1 AND invoices.type = 2 THEN amount_due
                    WHEN currencies.priority = 2 AND invoices.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    AS credit"),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction_date'
            )
            ->where('invoices.financial_year_id', $id)
            ->whereNull('invoices.deleted_at')
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $payments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.account_id')
            ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.client_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                DB::raw("CASE
                        WHEN payments.exchange_rate IS NULL AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                            payments.amount_received * COALESCE((
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE (
                                    fx2.currency_id = currencies.currency_id OR fx2.currency_id = account_currency.currency_id
                                )
                                AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ), 1)
                        WHEN payments.exchange_rate IS NOT NULL AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                            payments.amount_received * payments.exchange_rate
                        ELSE payments.amount_received
                    END AS credit
                "),
                DB::raw("0.00 AS debit"),
                'client_ca.type',
                'client_ca.client_account_number',
                'date_received as transaction_date'
            )
            ->where('payments.financial_year_id', $id)
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('invoice_number', 'asc')
            ->get();

        $crossPayments = Payment::join('client_accounts as client_ca', 'client_ca.client_account_id', '=', 'payments.client_id')
            ->join('client_accounts as account_ca', 'account_ca.client_account_id', '=', 'payments.account_id')
            ->join('currencies as account_currency', 'account_currency.currency_id', '=', 'account_ca.currency_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_ca.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_ca.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->select(
                'client_ca.client_account_id',
                'client_ca.client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currencies.currency_symbol',
                DB::raw("CASE
                        WHEN payments.exchange_rate IS NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                            payments.amount_received * COALESCE((
                                SELECT fx2.exchange_rate
                                FROM forex_exchanges AS fx2
                                WHERE fx2.currency_id = account_currency.currency_id
                                  AND fx2.date_active <= FROM_UNIXTIME(payments.date_received)
                                ORDER BY fx2.date_active DESC
                                LIMIT 1
                            ), 1)
                        WHEN payments.exchange_rate IS NOT NULL OR payments.exchange_rate = 1 AND (account_currency.priority = 2 OR currencies.priority = 2) THEN
                            payments.amount_received * payments.exchange_rate
                        ELSE payments.amount_received
                    END AS debit
                "),
                DB::raw("0.00 AS credit"),
                'client_ca.type',
                'client_ca.client_account_number',
                'date_received as transaction_date'
            )
            ->where('payments.financial_year_id', $id)
            ->whereNull('payments.deleted_at')
            ->whereNull('client_ca.deleted_at')
            ->whereNull('account_ca.deleted_at')
            ->orderBy('invoice_number', 'asc')
            ->get();

        $purchases = Purchase::join('client_accounts', 'client_accounts.client_account_id', '=', 'purchases.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select('client_account_id', 'client_account_name', 'account_name', 'chart_number', 'chart_name', 'currency_symbol',
                DB::raw("CASE WHEN currencies.priority = 1 AND purchases.type = 2 THEN amount_due
                    WHEN currencies.priority = 2 AND purchases.type = 2
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                     AS debit"),
                DB::raw("CASE WHEN currencies.priority = 1 AND purchases.type = 1 THEN amount_due
                    WHEN currencies.priority = 2 AND purchases.type = 1
                    THEN (
                        SELECT fx2.exchange_rate
                              FROM forex_exchanges AS fx2
                              WHERE fx2.currency_id = client_accounts.currency_id
                              AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                              ORDER BY fx2.date_active DESC
                              LIMIT 1
                          ) * amount_due
                    ELSE 0 END
                    AS credit"),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction date'
            )
            ->where('purchases.financial_year_id', $id)
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $incomes = InvoiceItem::join('invoices', 'invoices.invoice_id', '=', 'invoice_items.invoice_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'invoice_items.ledger_id') // ledger
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id') // ledger currency
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'invoices.client_id') // client
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // client currency
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("CASE
                            WHEN invoices.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                    AS credit
                "),
                DB::raw("CASE
                            WHEN invoices.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(invoices.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * invoice_items.quantity * invoice_items.unit_price
                                    )
                                    ELSE invoice_items.quantity * invoice_items.unit_price
                                END
                            ELSE 0
                        END
                     AS debit
                "),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction_date'
            )
            ->where('invoices.financial_year_id', $id)
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('client_accounts.client_account_name')
            ->get();

        $journalDr = DB::table('adjustment_journals as debit')
            ->join('client_accounts as debit_account', 'debit.ledger_id', '=', 'debit_account.client_account_id')
            ->join('currencies as cur_debit', 'debit_account.currency_id', '=', 'cur_debit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'debit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            // Derived subquery: get ONE credit per reference_code using MAX(id)
            ->leftJoin(DB::raw('
                (
                    SELECT aj_credit.*,
                           ca.client_account_name AS credit_account_name,
                           ca.currency_id AS credit_currency_id
                    FROM adjustment_journals aj_credit
                    JOIN client_accounts ca ON aj_credit.ledger_id = ca.client_account_id
                    WHERE aj_credit.type = 2 AND aj_credit.deleted_at IS NULL
                      AND aj_credit.adjustment_journal_id IN (
                          SELECT MAX(inner_aj.adjustment_journal_id)
                          FROM adjustment_journals inner_aj
                          WHERE inner_aj.type = 2 AND inner_aj.deleted_at IS NULL
                          GROUP BY inner_aj.reference_code
                      )
                ) as credit
            '), 'credit.reference_code', '=', 'debit.reference_code')
            ->leftJoin('currencies as cur_credit', 'credit.credit_currency_id', '=', 'cur_credit.currency_id')
            ->select(
                'debit_account.client_account_id',
                'debit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_debit.currency_symbol',

                DB::raw("
                    CASE
                        WHEN cur_debit.priority = 2 OR cur_credit.priority = 2 THEN
                            CASE
                                WHEN credit.exchange_rate IS NOT NULL AND credit.exchange_rate != 1 THEN debit.amount * credit.exchange_rate
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = credit.credit_currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(debit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * debit.amount
                            END
                        ELSE debit.amount
                    END as debit
                "),
                DB::raw("0 as credit"),
                'debit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'credit.credit_account_name as ledger_name',
                'debit.description',
                'debit.reference_code as transaction_number',
                'debit_account.type'
            )
            ->whereNull('debit.deleted_at')
            ->where('debit.type', 1)
            ->whereBetween('debit.date_adjusted', [
                strtotime($financial->year_starting),
                strtotime($financial->year_ending)
            ])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('debit_account.client_account_name')
            ->get();

        $journalsCr = DB::table('adjustment_journals as credit')
            ->join('client_accounts as credit_account', 'credit.ledger_id', '=', 'credit_account.client_account_id')
            ->join('currencies as cur_credit', 'credit_account.currency_id', '=', 'cur_credit.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'credit_account.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->leftJoin(DB::raw('(
                SELECT *
                FROM adjustment_journals aj_sub
                WHERE aj_sub.type = 1
                AND aj_sub.deleted_at IS NULL
                AND aj_sub.adjustment_journal_id IN (
                    SELECT MAX(aj_inner.adjustment_journal_id)
                    FROM adjustment_journals aj_inner
                    WHERE aj_inner.type = 1 AND aj_inner.deleted_at IS NULL
                    GROUP BY aj_inner.reference_code
                )
            ) as debit'), function ($join) {
                $join->on('credit.reference_code', '=', 'debit.reference_code')
                    ->whereRaw('credit.type = 2');
            })
            ->leftJoin('client_accounts as debit_account', 'debit.ledger_id', '=', 'debit_account.client_account_id')
            ->leftJoin('currencies as cur_debit', 'debit_account.currency_id', '=', 'cur_debit.currency_id')
            ->select(
                'credit_account.client_account_id',
                'credit_account.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'cur_credit.currency_symbol',
                DB::raw("0 as debit"),
                DB::raw("
                    CASE
                        WHEN cur_credit.priority = 2 OR cur_debit.priority = 2 THEN
                            CASE
                                WHEN debit.exchange_rate IS NOT NULL AND debit.exchange_rate != 1 THEN credit.amount * debit.exchange_rate
                                ELSE COALESCE((
                                    SELECT fx2.exchange_rate
                                    FROM forex_exchanges AS fx2
                                    WHERE fx2.currency_id = debit_account.currency_id
                                    AND fx2.date_active <= FROM_UNIXTIME(credit.date_adjusted)
                                    ORDER BY fx2.date_active DESC
                                    LIMIT 1
                                ), 1) * credit.amount
                            END
                        ELSE credit.amount
                    END as credit
                "),
                'credit.date_adjusted as transaction_date',
                DB::raw("'Journal' as transaction_type"),
                'debit_account.client_account_name as ledger_name',  // show one debit name
                'credit.description',
                'credit.reference_code as transaction_number',
                'credit_account.type'
            )
            ->whereNull('credit.deleted_at')
            ->where('credit.type', 2)
            ->whereBetween('credit.date_adjusted', [strtotime($financial->year_starting), strtotime($financial->year_ending)])
            ->orderBy('chart_of_accounts.chart_number')
            ->orderBy('credit_account.client_account_name')
            ->get();

        $cashes = ClientAccount::join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->leftJoin('petty_cashes', function ($join) use ($financial) {
                $join->on('petty_cashes.ledger_id', '=', 'client_accounts.client_account_id')
                    ->where('date_invoiced', '>=', strtotime($financial->year_starting))
                    ->where('date_invoiced', '<=', strtotime($financial->year_ending))
                    ->whereNull('petty_cashes.deleted_at');
            })
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name',
                'accounts.account_name',
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("
                        CASE
                            WHEN petty_cashes.type = 2 AND currencies.priority = 2
                            THEN
                                CASE
                                    WHEN petty_cashes.exchange_rate IS NOT NULL
                                    THEN petty_cashes.amount * petty_cashes.exchange_rate
                                    ELSE COALESCE(
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                            AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ), 1
                                    ) * petty_cashes.amount
                                END
                            WHEN petty_cashes.type = 2 AND currencies.priority = 1
                            THEN petty_cashes.amount
                            ELSE 0
                        END
                     AS debit
                "),
                DB::raw("
                        CASE
                            WHEN petty_cashes.type = 1 AND currencies.priority = 2
                            THEN
                                CASE
                                    WHEN petty_cashes.exchange_rate IS NOT NULL
                                    THEN petty_cashes.amount * petty_cashes.exchange_rate
                                    ELSE COALESCE(
                                        (
                                            SELECT fx2.exchange_rate
                                            FROM forex_exchanges AS fx2
                                            WHERE fx2.currency_id = client_accounts.currency_id
                                            AND fx2.date_active <= FROM_UNIXTIME(petty_cashes.date_invoiced)
                                            ORDER BY fx2.date_active DESC
                                            LIMIT 1
                                        ), 1
                                    ) * petty_cashes.amount
                                END
                            WHEN petty_cashes.type = 1 AND currencies.priority = 1
                            THEN petty_cashes.amount
                            ELSE 0
                        END
                    AS credit
                "),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction_date'
            )
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->whereNull('client_accounts.deleted_at')
            ->get();

        $balances = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.client_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_account_name',
                'account_name',
                'chart_number',
                'chart_name',
                'currency_symbol',
                DB::raw("
                        CASE
                            WHEN opening_balances.type = 1 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 1 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                     AS debit
                "),
                DB::raw("
                        CASE
                            WHEN opening_balances.type = 2 AND currencies.priority = 2
                                THEN COALESCE(
                                    (SELECT fx2.exchange_rate
                                     FROM forex_exchanges AS fx2
                                     WHERE fx2.currency_id = client_accounts.currency_id
                                     AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                                     ORDER BY fx2.date_active DESC
                                     LIMIT 1), 1) * opening_balances.amount
                            WHEN opening_balances.type = 2 AND currencies.priority = 1
                                THEN opening_balances.amount
                            ELSE 0
                        END
                     AS credit
                "),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction_date'
            )
            ->whereNull('opening_balances.deleted_at')
            ->where('opening_balances.financial_year_id', $id)
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $opening = OpeningBalance::join('client_accounts', 'client_accounts.client_account_id', '=', 'opening_balances.ledger_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'opening_balances.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id') // Fixed alias reference
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->select(
                'client_accounts.client_account_id',
                'client_accounts.client_account_name', // Ensure full reference
                'accounts.account_name', // Ensure full reference
                'chart_of_accounts.chart_number',
                'chart_of_accounts.chart_name',
                'currencies.currency_symbol',
                DB::raw("
                CASE
                    WHEN opening_balances.type = 1 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 1 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
             AS credit
        "),
                DB::raw("
                CASE
                    WHEN opening_balances.type = 2 AND curr.priority = 2
                        THEN COALESCE(
                            (SELECT fx2.exchange_rate
                             FROM forex_exchanges AS fx2
                             WHERE fx2.currency_id = acc.currency_id
                             AND fx2.date_active <= FROM_UNIXTIME(opening_balances.date_invoiced)
                             ORDER BY fx2.date_active DESC
                             LIMIT 1), 1) * opening_balances.amount
                    WHEN opening_balances.type = 2 AND curr.priority = 1
                        THEN opening_balances.amount
                    ELSE 0
                END
             AS debit
        "),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction_date'
            )
            ->whereNull('opening_balances.deleted_at')
            ->where('opening_balances.financial_year_id', $id)
            ->orderBy('chart_number')
            ->orderBy('client_account_name')
            ->get();

        $expenses = PurchaseItem::join('purchases', 'purchases.purchase_id', '=', 'purchase_items.purchase_id')
            ->join('client_accounts', 'client_accounts.client_account_id', '=', 'purchase_items.ledger_id')
            ->join('currencies', 'currencies.currency_id', '=', 'client_accounts.currency_id')
            ->join('chart_of_accounts', 'chart_of_accounts.chart_id', '=', 'client_accounts.chart_id')
            ->join('account_sub_categories', 'account_sub_categories.sub_account_id', '=', 'chart_of_accounts.sub_account_id')
            ->join('accounts', 'accounts.account_id', '=', 'account_sub_categories.account_id')
            ->join('client_accounts as acc', 'acc.client_account_id', '=', 'purchases.client_id')
            ->join('currencies as curr', 'curr.currency_id', '=', 'acc.currency_id')
            ->select('client_accounts.client_account_id', 'client_accounts.client_account_name', 'account_name', 'chart_number', 'chart_name', 'currencies.currency_symbol',
                DB::raw("
                        CASE
                            WHEN purchases.type = 1 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx1.exchange_rate
                                             FROM forex_exchanges fx1
                                             WHERE fx1.currency_id = currencies.currency_id
                                               AND fx1.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx1.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx2.exchange_rate
                                             FROM forex_exchanges fx2
                                             WHERE fx2.currency_id = curr.currency_id
                                               AND fx2.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx2.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                     AS debit
                "),
                DB::raw("
                        CASE
                            WHEN purchases.type = 2 THEN
                                CASE
                                    WHEN currencies.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx3.exchange_rate
                                             FROM forex_exchanges fx3
                                             WHERE fx3.currency_id = currencies.currency_id
                                               AND fx3.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx3.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    WHEN curr.priority = 2 THEN (
                                        COALESCE(
                                            (SELECT fx4.exchange_rate
                                             FROM forex_exchanges fx4
                                             WHERE fx4.currency_id = curr.currency_id
                                               AND fx4.date_active <= FROM_UNIXTIME(purchases.date_invoiced)
                                             ORDER BY fx4.date_active DESC
                                             LIMIT 1
                                            ), 1
                                        ) * purchase_items.quantity * purchase_items.unit_price
                                    )
                                    ELSE purchase_items.quantity * purchase_items.unit_price
                                END
                            ELSE 0
                        END
                     AS credit
                "),
                'client_accounts.type',
                'client_accounts.client_account_number',
                'date_invoiced as transaction_date'
            )
            ->where('purchases.financial_year_id', $id)
            ->whereNull('purchase_items.deleted_at')
            ->get();

        $combinedResults = collect([])
            ->merge($receipts)
            ->merge($payments)
            ->merge($incomes)
            ->merge($expenses)
            ->merge($purchases)
            ->merge($clients)
            ->merge($invoices)
            ->merge($crossPayments)
            ->merge($journalsCr)
            ->merge($journalDr)
            ->merge($balances)
            ->merge($opening)
            ->merge($cashes)
            ->whereIn('type', [1, 2]) ->sortBy(function ($item) {
                // Sorting by account_name first, then chart_name
                return [$item->chart_number, $item->chart_name, $item->account_name, $item->client_account_name];
            });
        return $combinedResults;
    }
    public function downloadAuctionSheet ($sale, $type, $query)
    {
        $teas = Auction::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'auctions.delivery_id')
                ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                ->join('stock_ins', function ($join){
                    $join->on('delivery_orders.delivery_id', '=', 'stock_ins.delivery_id');
                })
                ->join('clients as stock', 'stock.client_id', '=', 'delivery_orders.client_id')
                ->join('brokers', 'brokers.broker_id', '=', 'auctions.broker_id')
                ->leftJoin('clients', 'clients.client_id', '=', 'auctions.client_id')
                ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'auctions.warehouse_id')
                ->select('auction_id', 'stock.client_name', 'warrant_number', 'stock_ins.total_pallets as current_stock', 'stock_ins.net_weight as current_weight', 'auctions.status', 'clients.client_name as buyer_name', 'brokers.broker_name', 'invoice_number', 'garden_name', 'grade_name', 'order_number', 'auctions.client_id', 'auctions.broker_id', 'sale', 'auctions.sale_date', 'auctions.prompt_date', 'auctions.warehouse_id', 'warehouses.warehouse_name', 'release_date', 'clients.client_name as buyer', 'stock_ins.delivery_id', 'total_pallets as packet', 'stock_ins.net_weight as weight', 'stock_ins.package_tare')
                ->where('auctions.sale', $sale)
                ->orderBy('warrant_number')
                ->get();

        if ($type == 2){
            return Excel::download(new AuctionSheet($teas, $sale), 'WEIGHT NOTES FOR AUCTION TEAS SALE '.str_replace('/', '', $sale).time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
        // Render Blade view
        $html = View::make('clerk::downloads.auction', compact('teas', 'sale'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sale).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadAuctionSheetReport ($id)
    {
        $sale = base64_decode($id);
        $teas = Auction::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'auctions.delivery_id')
                ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                ->join('stock_ins', function ($join){
                    $join->on('delivery_orders.delivery_id', '=', 'stock_ins.delivery_id');
                })
                ->join('clients as stock', 'stock.client_id', '=', 'delivery_orders.client_id')
                ->join('brokers', 'brokers.broker_id', '=', 'auctions.broker_id')
                ->leftJoin('clients', 'clients.client_id', '=', 'auctions.client_id')
                ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'auctions.warehouse_id')
                ->select('auction_id', 'stock.client_name', 'warrant_number', 'stock_ins.total_pallets as current_stock', 'stock_ins.net_weight as current_weight', 'auctions.status', 'clients.client_name as buyer_name', 'brokers.broker_name', 'invoice_number', 'garden_name', 'grade_name', 'order_number', 'auctions.client_id', 'auctions.broker_id', 'sale', 'auctions.sale_date', 'auctions.prompt_date', 'auctions.warehouse_id', 'warehouses.warehouse_name', 'release_date', 'clients.client_name as buyer', 'stock_ins.delivery_id', 'total_pallets as packet', 'stock_ins.net_weight as weight')
                ->where('auctions.sale', $sale)
                ->orderBy('warrant_number')
                ->get();


        // Render Blade view
        $html = View::make('clerk::downloads.auction_report', compact('teas', 'sale'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch'
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = str_replace('/', '', $sale).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }

}
