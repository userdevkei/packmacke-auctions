<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LpoSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $lpos;

    public function __construct($lpos)
    {
        $this->lpos = collect($lpos);
    }

    public function collection()
    {
        return $this->lpos;
    }

    public function headings(): array
    {
        return [
            'LPO Number',
            'Date',
            'Supplier',
            'Items Count',
            'Subtotal',
            'VAT Amount',
            'Grand Total',
            'Notes',
        ];
    }

    public function map($lpo): array
    {
        return [
            $lpo['lpo_number'],
            \Carbon\Carbon::parse($lpo['date'])->format('Y-m-d'),
            $lpo['supplier']['supplier_name'] ?? 'N/A',
            count($lpo['items'] ?? []),
            number_format($lpo['subtotal'], 2),
            number_format($lpo['vat_amount'], 2),
            number_format($lpo['total_amount'], 2),
            $lpo['notes'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'LPO Summary';
    }
}
