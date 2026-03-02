<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class InventoryStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $inventories;

    public function __construct($inventories)
    {
        $this->inventories = $inventories;
    }

    public function collection()
    {
        return $this->inventories;
    }

    public function headings(): array
    {
        return [
            'Client Name',
            'Item Name',
            'Unit of Measure',
            'Quantity Received',
            'Quantity Used',
            'Quantity Remaining',
            'Last Transaction Date',
        ];
    }

    public function map($inventory): array
    {
        return [
            $inventory->client_name,
            $inventory->item_name,
            $inventory->unit,
            $inventory->total_received,
            $inventory->total_issued,
            $inventory->current_balance ?? 0, // adjust field names
            $inventory->last_transaction_date,
        ];
    }

    public function styles($inventory): array
    {
        return [
            1 => [ // Row 1 (headings)
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
