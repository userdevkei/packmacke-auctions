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
class ClientAccountAgingReport Implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function collection()
    {
        $reports = collect();  // Use a collection to store the result
        $stk = [];  // Initialize $stk array to store invoice data

        // Initialize totals
        $totalAmountDue = 0;
        $totalPayments = 0;
        $totalOutstandingBalance = 0;

        foreach ($this->data as $key => $invoice) {
            // Add invoice data to the report
            $stk[] = [
                '#' => ++$key,
                'Delivery Type' => Carbon::parse($invoice->invoice_date)->format('d/M/Y'),
                'CLIENT NAME' => $invoice->client_name,
                'Invoice Number' => $invoice->invoice_number,
                'Reference Number' => $invoice->si_number,
                'ledger' => strtoupper($invoice->ledger_name),
                'Order Number' => number_format($invoice->amount_due, 2),
                'Garden Name' => number_format($invoice->total_payments, 2),
                'Grade Name' => number_format($invoice->outstanding_balance, 2),
                'PERIOD' => $invoice->aging_category,
            ];

            // Accumulate totals
            $totalAmountDue += $invoice->amount_due;
            $totalPayments += $invoice->total_payments;
            $totalOutstandingBalance += $invoice->outstanding_balance;
        }

        // Add totals row
        $stk[] = [
            '#' => '',
            'CLIENT NAME' => 'TOTALS',
            'Invoice Number' => '',
            'Ref Type' => '',
            'Delivery Type' => '',
            'Delivery' => '',
            'Order Number' => number_format($totalAmountDue, 2),
            'Garden Name' => number_format($totalPayments, 2),
            'Grade Name' => number_format($totalOutstandingBalance, 2),
            'PERIOD' => '',
        ];

        // Push all invoices and the totals into the collection
        $reports->push(...$stk);
        return $reports;
    }
    public function headings(): array
    {
        // TODO: Implement headings() method.
        return [
            ['#', 'CLIENT NAME', 'DATE INVOICED', 'INVOICE NUMBER', 'REFERENCE NUMBER', 'ACCOUNT NAME', 'TOTAL DUE', 'TOTAL SETTLED', 'OUTSTANDING BAL', 'AGING/DATE SETTLED'],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A:H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('G:I')->applyFromArray(['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]]);
        $sheet->getStyle('E:I')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places
        return [];
    }
}
