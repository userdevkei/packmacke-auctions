<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnifiedInventoryTransactionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Type',
            'Transaction Number',
            'Date',
            'Client',
            'Additional Info',
            'Items Count',
            'Status',
        ];
    }

    public function map($transaction): array
    {
        $additionalInfo = $transaction->transaction_type === 'requisition'
            ? 'SI: ' . ($transaction->si_number.' - '.$transaction->warehouse?->station_name ?? 'N/A')
            : 'Released To: ' . ($transaction->released_to ?? 'N/A');

        return [
            ucfirst($transaction->transaction_type == 'requisition' ? 'Internally Used' : 'Externally Used'),
            $transaction->transaction_number,
            \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
            $transaction->client->client_name ?? 'N/A',
            $additionalInfo,
            $transaction->items->count(),
            ucfirst($transaction->status),
        ];
    }

    public function styles(Worksheet  $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
