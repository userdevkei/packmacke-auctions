<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemMovementsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $movements;
    protected $client;
    protected $item;
    protected $summary;

    public function __construct($movements, $client, $item, $summary)
    {
        $this->movements = $movements;
        $this->client = $client;
        $this->item = $item;
        $this->summary = $summary;
    }

    public function collection()
    {
        return $this->movements;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Transaction Type',
            'Transaction Number',
            'Quantity In',
            'Quantity Out',
            'Running Balance',
            'Notes',
        ];
    }

    public function map($movement): array
    {
        $typeLabels = [
            'lpo' => 'Received Lpo',
            'release' => 'External Usage',
            'requisition' => 'Internal Usage',
        ];

        return [
            \Carbon\Carbon::parse($movement->transaction_date)->format('Y-m-d'),
            $typeLabels[$movement->transaction_type] ?? $movement->transaction_type,
            $movement->transaction_number,
            $movement->quantity_in > 0 ? $movement->quantity_in : '',
            $movement->quantity_out > 0 ? $movement->quantity_out : '',
            $movement->running_balance,
            $movement->notes ?? '',
        ];
    }

    public function title(): string
    {
        return substr($this->item->item_name, 0, 31); // Excel sheet name limit
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Make heading row bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
