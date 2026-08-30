<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class LocalPurchaseOrderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithStyles
{
    protected $lpos;

    public function __construct($inventories)
    {
        $this->inventories = $inventories;
    }

    public function collection(){
        return $this->inventories;
    }

    public function headings(): array
    {
        return [
            'Purchase Order Number',
            'LPO Number',
            'Client Name',
            'Supplier Name',
            'Date Received',
            'Status',
        ];
    }

    public function map($lpos): array
    {
        return [
            $lpos->purchase_order_number,
            $lpos->lpo_number,
            $lpos->client_name,
            $lpos->supplier_name,
            $lpos->date,
            $lpos->status,
        ];
    }

    public function styles($lpos): array
    {
        return [
            '1' => [
                'font' => [
                    'bold' => true,
                ]
            ]
        ];
    }
}
