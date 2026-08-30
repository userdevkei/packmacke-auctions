<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate, $type, AppClass $appClass)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->AppClass = $appClass;
    }

    public function collection()
    {
        return $this->AppClass->dayBookReport($this->startDate, $this->endDate, $this->type);
    }

    public function headings(): array
    {
        return ['Date', 'Ref Number', 'SI Number', 'Transaction Type', 'Ledger Name', 'Description', 'Debit', 'Credit', 'Initiator'];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_date,
            $transaction->ref_number,
            $transaction->si_number,
            $transaction->transaction_type,
            $transaction->ledger_name,
            $transaction->description,
            number_format($transaction->debit, 2),
            number_format($transaction->credit, 2),
            $transaction->user_name
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('A:E')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F:G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F:G')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places
        return [];
    }
}
