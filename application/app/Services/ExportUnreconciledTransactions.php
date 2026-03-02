<?php

namespace App\Services;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportUnreconciledTransactions implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public $query;
    public $bank;
    public function __construct($query,  $bank)
    {
        $this->query = $query;
        $this->bank = $bank;
    }
    public function collection()
    {
        $totalDebit = 0;
        $totalCredit = 0;
        $transactions = [];

        foreach ($this->query as $key => $statement) {
            $debit = $statement->type == 'DEBIT' ? number_format($statement->amount_received, 2) : '0.00';
            $credit = $statement->type == 'CREDIT' ? number_format($statement->amount_received, 2) : '0.00';
            $transactions[] = [
                '#' => ++$key,
                'Ref Number' => $statement->invoice_number,
                'Ledger Name' => strtoupper($statement->client_account_name),
                'Debit Amount' => $debit,
                'Credit Amount' => $credit,
                'System Date' => Carbon::createFromTimestamp($statement->date_received)->format('d-m-Y'),
                'Bank Date' => $statement->bank_date ? Carbon::createFromTimestamp($statement->bank_date)->format('d-m-Y') : null,
            ];
            $totalDebit += floatval(str_replace(',', '', $debit));
            $totalCredit += floatval(str_replace(',', '', $credit));
        }

        $total = [
            '#' => null,
            'Ref Number' => null,
            'Ledger Name' => null,
            'Debit Amount' => number_format($totalDebit, 2),
            'Credit Amount' => number_format($totalCredit, 2),
            'System Date' => null,
            'Bank Date' => null,
        ];

        $transactions[] = $total;
        return collect($transactions);
    }

    public function headings(): array
    {
        return [
            [strtoupper($this->bank->client_account_name)],
            ['#', 'Ref Number', 'Ledger Name', 'Debit Amount', 'Credit Amount', 'System Date', 'Bank Date']
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('2')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F:G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D:E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D:E')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00)
        ; // Ensures 2 decimal places
        return [];
    }
}
