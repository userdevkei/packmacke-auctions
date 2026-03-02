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
class ClientsAgingAccounts Implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct($data, $id)
    {
        $this->data = $data;
        $this->id = $id;
    }
 /*   public function collection()
    {
        $reports = collect();  // Use a collection to store the result
        $stk = [];  // Initialize $stk array to store invoice data
        foreach ($this->data as $key => $invoice) {
            $stk[] = [
                '#' => ++$key,
                'CLIENT NAME' => $invoice->client_name,
                'CURRENCY' => $invoice->currency_symbol,
                'TOTAL DUE' => number_format($invoice->total_amount_due, 2),
                '0-30' => $invoice->amount_due_30_days == 0 ? '0.00' : number_format($invoice->amount_due_30_days, 2),
                '31-90' => $invoice->amount_due_60_days == 0 ? '0.00' : number_format($invoice->amount_due_60_days, 2),
                '91-180' => $invoice->amount_due_90_days == 0 ? '0.00' : number_format($invoice->amount_due_90_days, 2),
                '181-365' => $invoice->amount_due_90_plus == 0 ? '0.00' : number_format($invoice->amount_due_90_plus, 2),
            ];
        }
        // Push all invoices and the totals into the collection
        $reports->push(...$stk);
        return $reports;
    }*/
    public function collection()
    {
        $reports = collect();  // Use a collection to store the result
        $stk = [];  // Initialize $stk array to store invoice data

        // Initialize totals
        $totals = [
            'total_amount_due' => 0,
            'amount_due_30_days' => 0,
            'amount_due_60_days' => 0,
            'amount_due_90_days' => 0,
            'amount_due_90_plus' => 0,
        ];

        foreach ($this->data as $key => $invoice) {
            // Add invoice data to $stk
            $stk[] = [
                '#' => ++$key,
                'CLIENT NAME' => $invoice->client_name,
                'CURRENCY' => $invoice->currency_symbol,
                'TOTAL DUE' => number_format($invoice->total_amount_due, 2),
                '0-30' => $invoice->amount_due_30_days == 0 ? '0.00' : number_format($invoice->amount_due_30_days, 2),
                '31-90' => $invoice->amount_due_60_days == 0 ? '0.00' : number_format($invoice->amount_due_60_days, 2),
                '91-180' => $invoice->amount_due_90_days == 0 ? '0.00' : number_format($invoice->amount_due_90_days, 2),
                '181-365' => $invoice->amount_due_90_plus == 0 ? '0.00' : number_format($invoice->amount_due_90_plus, 2),
            ];

            // Add to totals
            $totals['total_amount_due'] += $invoice->total_amount_due;
            $totals['amount_due_30_days'] += $invoice->amount_due_30_days;
            $totals['amount_due_60_days'] += $invoice->amount_due_60_days;
            $totals['amount_due_90_days'] += $invoice->amount_due_90_days;
            $totals['amount_due_90_plus'] += $invoice->amount_due_90_plus;
        }

        // Add data rows to the collection
        $reports->push(...$stk);

        // Add totals row to the collection
        $reports->push([
            '#' => '',
            'CLIENT NAME' => 'TOTALS',
            'CURRENCY' => '',
            'TOTAL DUE' => number_format($totals['total_amount_due'], 2),
            '0-30' => number_format($totals['amount_due_30_days'], 2),
            '31-90' => number_format($totals['amount_due_60_days'], 2),
            '91-180' => number_format($totals['amount_due_90_days'], 2),
            '181-365' => number_format($totals['amount_due_90_plus'], 2),
        ]);

        return $reports;
    }

    public function headings(): array
    {
        // TODO: Implement headings() method.
        return [
            ['#', 'CLIENT NAME', 'CURRENCY', 'TOTAL DUE', '0-30 DAYS', '31-60 DAYS', '61-90 DAYS', '> 90 DAYS'],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E:H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('D:H')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places
        return [];
    }
}
