<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class InventoryTransactionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $transactions;
    protected $type;

    public function __construct($transactions, $type){
        $this->transactions = $transactions;
        $this->type = $type;
    }

    public function collection(){
        return $this->transactions;
    }

    public function headings(): array
    {
        $transactionNumber = $this->type == 'transfer_out' ? 'Transfer Number' : ($this->type == 'release' ? 'Release Number' : 'Requisition Number');
        $destination = $this->type == 'release' ? 'Released To' : ($this->type == 'requisition' ? 'SI Number' : 'Transferred To');
        return [
            'Transaction Date',
            $transactionNumber,
            'Client Name',
            $destination,
            'Total Items',
            'Status'
        ];
    }

    public function map($transactions): array
    {
        return [
            $transactions->transfer_date ?? $transactions->release_date ?? $transactions->requisition_date,
            $transactions->transfer_out_number ?? $transactions->release_number ?? $transactions->requisition_number,
            $transactions->client->client_name,
            $transactions->recipient->client_name ?? $transactions->released_to ?? $transactions->si_number,
            $transactions->items->count(),
            $transactions->status,
        ];
    }

    public function styles($transactions): array
    {
        return [
            '1' => ['font' => ['bold' => true]],
        ];
    }
}
