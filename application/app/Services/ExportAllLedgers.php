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
class ExportAllLedgers Implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }
    public function collection()
    {
        $invoices = collect();  // Use a collection to store the result
        $inv = [];  // Initialize $inv array to store individual invoice data
        $totalInvoice = 0;

        foreach ($this->invoices as $key => $invoice) {
            $type = $invoice['type'] == 1 ? 1 : -1;

            $inv[] = [
                '#' => ++$key,
                'ACCOUNT NUMBER' => $invoice['client_account_number'],
                'ACCOUNT NAME' => strtoupper($invoice['clientAccount']),
                'ACCOUNT CURRENCY' => $invoice['currency_symbol'],
                'TOTAL INVOICE' => number_format($invoice['amount_due'] * $type, 2),
            ];

            $totalInvoice += $invoice['amount_due'];
        }

        // Add totals row
        $totals = [
            '#' => null,
            'ACCOUNT NUMBER' => null,
            'ACCOUNT NAME' => null,
            'ACCOUNT CURRENCY' => null,
            'TOTAL INVOICE' => number_format($totalInvoice, 2),
        ];

        // Push all invoices and the totals into the collection
        $invoices->push(...$inv);
        $invoices->push($totals);

        return $invoices;
    }

    public function headings(): array
    {
        // TODO: Implement headings() method.

        return [
            ['#', 'ACCOUNT NUMBER', 'ACCOUNT NAME', 'ACCOUNT CURRENCY', 'TOTAL INVOICE'],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E:E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('E:E')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places
        return [];
    }
}
