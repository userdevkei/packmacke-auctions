<?php

namespace App\Imports;

use App\Services\CustomIds;
use App\Services\Log;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Account\Entities\AdjustmentJournal;

class ImportJournal implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public $errors = [];
    protected $data;
//    protected $logger;
    public function __construct($data)
    {
//        $this->logger = $logger;
        $this->data  = $data;
    }
    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $totalDebit = 0;
            $totalCredit = 0;
            $hasErrors = false;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Assuming row 1 is the heading

                $accountNumber = trim($row['account_number']);
                $type = strtoupper(trim($row['type']));
                $debit = floatval($row['debit']);
                $credit = floatval($row['credit']);

                // ✅ Check if account exists
                $accountExists = DB::table('ledgers')
                    ->where('client_account_number', $accountNumber)
                    ->exists();

                if (!$accountExists) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => ["Account number not found: {$accountNumber}"]
                    ];
                    $hasErrors = true;
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            // ✅ If any account errors found
            if ($hasErrors) {
                DB::rollBack();
                return;
            }

            // ✅ Check if debits and credits are balanced
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                DB::rollBack();
                $this->errors[] = [
                    'row' => 'All',
                    'errors' => [
                        "Total Debit ({$totalDebit}) does not equal Total Credit ({$totalCredit})"
                    ]
                ];
                return;
            }

            $entries = [];
            $jvNumber = AdjustmentJournal::newReferenceCode();
            // ✅ Proceed with saving records here if needed
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $ledger = DB::table('ledgers')
                    ->where('client_account_number', $row['account_number'])->first();
                $entries[] = [
                    'adjustment_journal_id' => (new CustomIds())->generateId(),
                    'reference_code' => $jvNumber,
                    'ledger_id' => $ledger->client_account_id,
                    'type' => strtolower($row['type']) == 'dr' ? 1 : 2,
                    'amount' => strtolower($row['type']) == 'dr' ? $row['debit'] : $row['credit'],
                    'description' => $this->data['description'],
                    'exchange_rate' => 1,
                    'date_adjusted' => strtotime($this->data['date_adjusted']),
                    'status' => 1,
                    'user_id' => auth()->user()->user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            AdjustmentJournal::insert($entries);
            (new Log())->create();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->errors[] = [
                'row' => $rowNumber ?? 'N/A',
                'errors' => [$e->getMessage()]
            ];
            Log::info("Import error at row {$rowNumber}: " . $e->getMessage());
        }
    }

}
