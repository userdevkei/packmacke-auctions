<?php

namespace App\Services;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Account\Entities\ForexExchange;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class ExportLedgerSummary Implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct($invoices, $account)
    {
        $this->invoices = $invoices;
        $this->account = $account;
    }
    public function collection()
    {
        $invoices = collect();  // Use a collection to store the result
        $inv = [];  // Initialize $inv array to store individual invoice data
        $totalDebit = 0;
        $totalCredit = 0;
        $balance = 0;

        foreach ($this->invoices as $key => $invoice) {
            $debit = $invoice->debit ?? 0;
            $credit = $invoice->credit ?? 0;
            $invoice->type == 8 ? $balance += ($credit - $debit) : $balance += ($debit - $credit);

            $inv[] = [
                '#' => ++$key,
                'INVOICE TYPE' => $invoice->transaction_type,
                'INVOICE DATE' => $invoice->transaction_date == null ? '--' : Carbon::createFromTimestamp($invoice->transaction_date)->format('d-m-Y'),
                'INVOICE NUMBER' => $invoice->transaction_number,
                'LEDGER NAME' => $invoice->ledger_name,
                'DESCRIPTION' => $invoice->description,
                'DEBIT' => number_format($invoice->debit, 2),
                'CREDIT' => number_format($invoice->credit, 2),
                'BALANCE' => number_format($balance, 2),
            ];

            $totalDebit += $invoice->debit;
            $totalCredit += $invoice->credit;
        }

        // Add totals row
        $totals = [
            '#' => null,
            'INVOICE TYPE' => 'TOTAL BALANCE',
            'INVOICE DATE' => null,
            'INVOICE NUMBER' => null,
            'LEDGER NAME' => null,
            'DESCRIPTION' => null,
            'DEBIT' => number_format($totalDebit, 2),
            'CREDIT' => number_format($totalCredit, 2),
            'BALANCE' => number_format($balance, 2),
        ];

        // Push all invoices and the totals into the collection
        $invoices->push(...$inv);
        $invoices->push($totals);

        return $invoices;
    }

    public function headings(): array
    {
        // TODO: Implement headings() method.
        $clientName = $this->account->client_account_name;
        return [
            [strtoupper($clientName)],
            ['#', 'INVOICE TYE', 'INVOICE DATE', 'INVOICE NUMBER', 'LEDGER NAME', 'DESCRIPTION', 'DEBIT', 'CREDIT', 'BALANCE'],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A2:I2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G:I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('2')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('D')->applyFromArray(['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]]);
        $sheet->getStyle('G:I')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places
        return [];
    }
}
