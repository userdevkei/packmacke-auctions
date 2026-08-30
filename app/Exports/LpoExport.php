<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LpoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    protected $lpos;
    protected $currentRow = 2; // Start after header

    public function __construct($lpos)
    {
        $this->lpos = collect($lpos);
    }

    public function collection()
    {
        // Flatten the data to include items
        $flattened = collect();

        foreach ($this->lpos as $lpo) {
            if (isset($lpo['items']) && count($lpo['items']) > 0) {
                foreach ($lpo['items'] as $index => $item) {
                    $flattened->push([
                        'lpo' => $lpo,
                        'item' => $item,
                        'is_first_item' => $index === 0
                    ]);
                }
            } else {
                $flattened->push([
                    'lpo' => $lpo,
                    'item' => null,
                    'is_first_item' => true
                ]);
            }
        }

        return $flattened;
    }

    public function headings(): array
    {
        return [
            'LPO Number',
            'Date',
            'Supplier',
            'Status',
            'Item Name',
            'Unit',
            'Quantity',
            'Unit Price',
            'Total Price',
            'VAT Rate (%)',
            'VAT Amount',
            'Gross Amount',
            'Subtotal',
            'Total VAT',
            'Grand Total',
            'Notes',
            'Approved By',
            'Approved At',
        ];
    }

    public function map($row): array
    {
        $lpo = $row['lpo'];
        $item = $row['item'];
        $isFirstItem = $row['is_first_item'];

        return [
            // LPO details (only show on first item)
            $isFirstItem ? $lpo['lpo_number'] : '',
            $isFirstItem ? \Carbon\Carbon::parse($lpo['date'])->format('Y-m-d') : '',
            $isFirstItem ? ($lpo['supplier']['supplier_name'] ?? 'N/A') : '',
            $isFirstItem ? ucfirst($lpo['status']) : '',

            // Item details
            $item ? $item['item_name'] : '',
            $item ? $item['unit'] : '',
            $item ? number_format($item['quantity'], 3) : '',
            $item ? number_format($item['unit_price'], 2) : '',
            $item ? number_format($item['total_price'], 2) : '',
            $item ? number_format($item['vat_rate'], 2) : '',
            $item ? number_format($item['vat_amount'], 2) : '',
            $item ? number_format($item['gross_amount'], 2) : '',

            // LPO totals (only show on first item)
            $isFirstItem ? number_format($lpo['subtotal'], 2) : '',
            $isFirstItem ? number_format($lpo['vat_amount'], 2) : '',
            $isFirstItem ? number_format($lpo['total_amount'], 2) : '',
            $isFirstItem ? ($lpo['notes'] ?? '') : '',
            $isFirstItem ? ($lpo['approved_by'] ?? 'N/A') : '',
            $isFirstItem ? ($lpo['approved_at'] ? \Carbon\Carbon::parse($lpo['approved_at'])->format('Y-m-d H:i') : 'N/A') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'LPO Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply borders to all cells with data
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Freeze the header row
                $sheet->freezePane('A2');

                // Apply alternating row colors
                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F2F2F2'],
                            ],
                        ]);
                    }
                }

                // Right align numeric columns
                $numericColumns = ['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
                foreach ($numericColumns as $col) {
                    $sheet->getStyle($col . '2:' . $col . $highestRow)->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        ],
                    ]);
                }
            },
        ];
    }
}
