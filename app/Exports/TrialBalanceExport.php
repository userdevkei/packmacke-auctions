<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialBalanceExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    protected $accounts;
    protected $financial;
    public function __construct($accounts, $financial)
    {
        $this->accounts = $accounts;
        $this->financial = $financial;
    }
    public function collection()
    {
        $data = collect();
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($this->accounts as $account) {
            // Add Account Header
            $data->push([
                'ACCOUNT: ' . $account['account_name'],
                '', ''
            ]);

            foreach ($account['charts'] as $chart) {
                $balance = $chart['debit'] - $chart['credit'];

                $debit = $balance >= 0 ? $balance : 0;
                $credit = $balance < 0 ? abs($balance) : 0;

                $totalDebits += $debit;
                $totalCredits += $credit;

                $data->push([
                    $chart['chart_name'] . ' (' . $chart['chart_number'] . ')',
                    number_format($debit, 2),
                    number_format($credit, 2),
                ]);
            }

            // Subtotal row per account type
            $subtotalBalance = $account['debit'] - $account['credit'];
            $subtotalDebit = $subtotalBalance >= 0 ? $subtotalBalance : 0;
            $subtotalCredit = $subtotalBalance < 0 ? abs($subtotalBalance) : 0;

            $data->push([
                'Subtotal',
                number_format($subtotalDebit, 2),
                number_format($subtotalCredit, 2),
            ]);

            $data->push(['', '', '']); // Spacer
        }

        // Grand Total
        $data->push([
            'GRAND TOTAL (KES)',
            number_format($totalDebits, 2),
            number_format($totalCredits, 2),
        ]);

        return $data;
    }
    public function headings(): array
    {
        return [
            ['PACKMAC HOLDINGS LIMITED'],
            ['TRIAL BALANCE (FINANCIAL YEAR : '. Carbon::parse($this->financial->year_starting)->format('Y') . ')'],
            ['Account Name', 'Debit (KES)', 'Credit (KES)'],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
//        $sheet->mergeCells('A3:N3');
        // Initialize styles array
        $styles = [
            // Style the header row
            1 => ['font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]],
            2 => ['font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]],
            3 => ['font' => ['bold' => true]],

            // Style header cells
            'A1:C1' => ['font' => ['bold' => true]],

        ];

        // Apply conditional styles using row iteration
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell('A'.$row)->getValue();

            if (strpos($cellValue, 'ACCOUNT: ') === 0) {
                // Account header row
                $sheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF4F81BD']
                    ],
                ]);
                $sheet->getStyle('B'.$row.':C'.$row)->applyFromArray([
                    'font' => ['bold' => true]
                ])->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B'.$row.':C'.$row)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            }
            elseif (!empty($cellValue) && strpos($cellValue, '   ') === 0) {
                // Ledger row (indented)
                $sheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
                    'font' => ['italic' => true]
                ]);
                $sheet->getStyle('B'.$row.':C'.$row)->applyFromArray([
                    'font' => ['bold' => false, 'italic' => true]
                ])->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B'.$row.':C'.$row)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            }
            elseif (!empty($cellValue) && strpos($cellValue, 'ACCOUNT: ') === false) {
                // Chart of accounts row
                $sheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
                    'font' => ['bold' => false, 'italic' => true]
                ]);
                $sheet->getStyle('B'.$row.':C'.$row)->applyFromArray([
                    'font' => ['bold' => false, 'italic' => true]
                ])->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('B'.$row.':C'.$row)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            }
        }
        return $styles;
    }
    public function title(): string
    {
        return 'Trial Balance'.time();
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();

                // Debugging - check actual cell values
                logger("Last row values: " . print_r($event->sheet->rangeToArray("A{$highestRow}:E{$highestRow}"), true));

                // Apply styling
                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF4F81BD']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ];

//                $event->sheet->mergeCells("A{$highestRow}:{$highestRow}");
                $event->sheet->getStyle("A{$highestRow}:C{$highestRow}")->applyFromArray($styleArray);

                // Force recalculation of styles
                $event->sheet->getDelegate()->getStyle("A{$highestRow}:C{$highestRow}")->applyFromArray($styleArray);
            }
        ];
    }
}
