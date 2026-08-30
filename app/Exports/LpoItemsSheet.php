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

class LpoItemsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $lpos;

    public function __construct($lpos)
    {
        $this->lpos = collect($lpos);
    }

    public function collection()
    {
        // Flatten items from all LPOs
        $items = collect();

        foreach ($this->lpos as $lpo) {
            if (isset($lpo['items'])) {
                foreach ($lpo['items'] as $item) {
                    $items->push([
                        'lpo' => $lpo,
                        'item' => $item
                    ]);
                }
            }
        }

        return $items;
    }

    public function headings(): array
    {
        return [
            'LPO Number',
            'LPO Date',
            'Supplier',
            'Item Name',
            'Unit',
            'Quantity',
            'Unit Price',
            'Total Price',
            'Is Vatable',
            'VAT Rate (%)',
            'VAT Amount',
            'Gross Amount',
            'Item Notes',
        ];
    }

    public function map($row): array
    {
        $lpo = $row['lpo'];
        $item = $row['item'];

        return [
            $lpo['lpo_number'],
            \Carbon\Carbon::parse($lpo['date'])->format('Y-m-d'),
            $lpo['supplier']['supplier_name'] ?? 'N/A',
            $item['item_name'],
            $item['unit'],
            number_format($item['quantity'], 3),
            number_format($item['unit_price'], 2),
            number_format($item['total_price'], 2),
            $item['is_vatable'] ? 'Yes' : 'No',
            number_format($item['vat_rate'], 2),
            number_format($item['vat_amount'], 2),
            number_format($item['gross_amount'], 2),
            $item['item_notes'] ?? '',
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
                    'startColor' => ['rgb' => '70AD47'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'LPO Items';
    }
}
