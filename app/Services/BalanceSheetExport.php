<?php
/*
namespace App\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $accounts;
    public function __construct($accounts)
    {
        $this->accounts = $accounts;
    }
    public function array(): array
    {
        $data = [];
        $totals = [
            'ASSETS' => ['debit' => 0, 'credit' => 0, 'balance' => 0],
            'LIABILITIES' => ['debit' => 0, 'credit' => 0, 'balance' => 0],
            'EQUITY' => ['debit' => 0, 'credit' => 0, 'balance' => 0],
        ];

        foreach ($this->accounts as $account) {
            $accountName = strtoupper($account['account_name']);
            $accountDebit = $account['debit'];
            $accountCredit = $account['credit'];
            $accountBalance = $account['balance'];

            // Track group totals
            if (isset($totals[$accountName])) {
                $totals[$accountName]['debit'] += $accountDebit;
                $totals[$accountName]['credit'] += $accountCredit;
                $totals[$accountName]['balance'] += $accountBalance;
            }

            // Display account group row
            $data[] = [
                'Account' => $accountName,
                'Chart' => '',
                'Debit' => number_format($accountDebit, 2),
                'Credit' => number_format($accountCredit, 2),
                'Balance' => number_format($accountBalance, 2),
            ];

            foreach ($account['charts'] as $chart) {
                $data[] = [
                    'Account' => '',
                    'Chart' => $chart['chart_number'] . ' - ' . $chart['chart_name'],
                    'Debit' => $chart['debit'],
                    'Credit' =>$chart['credit'],
                    'Balance' => $chart['balance'],
                ];
            }

            $data[] = ['', '', '', '', ''];
        }

        // Add Grand Totals
        $assets = $totals['ASSETS']['balance'];
        $liabilities = $totals['LIABILITIES']['balance'];
        $equity = $totals['EQUITY']['balance'];
        $checkBalance = $assets - ($liabilities + $equity);

        $data[] = ['', '', '', '', ''];
        $data[] = ['TOTAL ASSETS', '', '', '', number_format($assets, 2)];
        $data[] = ['TOTAL LIABILITIES', '', '', '', number_format($liabilities, 2)];
        $data[] = ['TOTAL EQUITY', '', '', '', number_format($equity, 2)];
        $data[] = ['BALANCE CHECK (A - [L+E])', '', '', '', number_format($checkBalance, 2)];

        return $data;
    }

    public function headings(): array
    {
        return ['Account', 'Chart', 'Debit', 'Credit', 'Balance'];
    }
    public function title(): string
    {
        return 'Balance Sheet';
    }
    public function styles(Worksheet $sheet)
    {
        $rowCount = count($this->array()) + 1; // +1 for headings

        // Apply styles directly
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Left-align all "Account" column (A)
        $sheet->getStyle("A2:A{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Right-align all numeric columns
        $sheet->getStyle("C2:E{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Format numeric columns to show 2 decimal places
        $sheet->getStyle("C2:E{$rowCount}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // Bold last 4 summary rows (totals and check)
        $sheet->getStyle("A{$rowCount}:E{$rowCount}")->getFont()->setBold(true);
        $sheet->getStyle("A" . ($rowCount - 1) . ":E" . ($rowCount - 1))->getFont()->setBold(true);
        $sheet->getStyle("A" . ($rowCount - 2) . ":E" . ($rowCount - 2))->getFont()->setBold(true);
        $sheet->getStyle("A" . ($rowCount - 3) . ":E" . ($rowCount - 3))->getFont()->setBold(true);

        return [];
    }

}*/


namespace App\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $accounts;
    protected $data = [];
    protected $rowCount = 0;

    public function __construct($accounts)
    {
        $this->accounts = $accounts;
        $this->prepareData();
    }

    protected function prepareData()
    {
        $totals = [
            'ASSETS' => ['debit' => 0, 'credit' => 0, 'balance' => 0],
            'LIABILITIES' => ['debit' => 0, 'credit' => 0, 'balance' => 0],
            'EQUITY' => ['debit' => 0, 'credit' => 0, 'balance' => 0],
        ];

        foreach ($this->accounts as $account) {
            $accountName = strtoupper($account['account_name']);

            // Track group totals
            if (isset($totals[$accountName])) {
                $totals[$accountName]['debit'] += $account['debit'];
                $totals[$accountName]['credit'] += $account['credit'];
                $totals[$accountName]['balance'] += $account['balance'];
            }

            // Account group row
            $this->data[] = [
                'Account' => $accountName,
                'Chart' => '',
//                'Debit' => $account['debit'], // Keep as number, not formatted
//                'Credit' => $account['credit'],
                'Balance' => $account['balance'],
            ];

            // Chart rows
            foreach ($account['charts'] as $chart) {
                $this->data[] = [
                    'Account' => '',
                    'Chart' => $chart['chart_number'] . ' - ' . $chart['chart_name'],
//                    'Debit' => $chart['debit'],
//                    'Credit' => $chart['credit'],
                    'Balance' => $chart['balance'],
                ];
            }

            // Empty row
            $this->data[] = ['', '', ''];
        }

        // Add totals
        $assets = $totals['ASSETS']['balance'];
        $liabilities = $totals['LIABILITIES']['balance'];
        $equity = $totals['EQUITY']['balance'];
        $checkBalance = $assets - ($liabilities + $equity);

        $this->data[] = ['', '', ''];
        $this->data[] = ['TOTAL ASSETS', '', number_format($assets, 2)];
        $this->data[] = ['TOTAL LIABILITIES', '', number_format($liabilities, 2)];
        $this->data[] = ['TOTAL EQUITY', '', number_format($equity, 2)];
        $this->data[] = ['BALANCE CHECK (A - [L+E])', '', number_format($checkBalance, 2)];

        $this->rowCount = count($this->data) + 1; // +1 for header row
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['Account', 'Chart', 'Balance'];
    }

    public function title(): string
    {
        return 'Balance Sheet';
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Column alignments
        $sheet->getStyle("A2:A{$this->rowCount}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("C2:C{$this->rowCount}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Number formatting
        $sheet->getStyle("C2:C{$this->rowCount}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // Bold summary rows
        for ($i = 0; $i < 4; $i++) {
            $row = $this->rowCount - $i;
            $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        }

        // Optional: Add borders to summary rows
        $sheet->getStyle("A" . ($this->rowCount - 3) . ":C{$this->rowCount}")
            ->getBorders()
            ->getTop()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [];
    }
}
