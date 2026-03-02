<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\UserInfo;
use App\Services\AppClass;
use Carbon\CarbonInterval;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class ExportStockCollection implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{

    public $data;
    public $appClass;
    public function __construct(AppClass $appClass, $data)
    {
       $this->data = $data;
       $this->appClass = $appClass;
    }

    public function collection()
    {

        $teas = [];
        // TODO: Implement collection() method.
        foreach ( $this->data as $tea){

            $createdAt = Carbon::createFromTimestamp($tea->date_received);
            $currentDate = Carbon::now();
            $diffInDays = $createdAt->diffInDays($currentDate);
            $user = UserInfo::where('user_id', $tea->received_by)->first();

            $teas[] = [
                'DELIVERY NUMBER' => $tea->tci_number,
                'CLIENT NAME' => $tea->client_name,
                'DELIVERY TYPE' => $tea->delivery_type == 1 ? 'DO ENTRY' : 'DIRECT DELIVERY',
                'DO NUMBER' => $tea->order_number,
                'GARDEN NAME' => $tea->garden_name,
                'GRADE' => $tea->grade_name,
                'INVOICE NUMBER' => $tea->invoice_number,
                'PACKAGES' => $tea->total_pallets,
                'NET WEIGHT' => $tea->net_weight,
                'PROMPT DATE' => Carbon::parse($tea->prompt_date)->format('D, d-m-Y'),
                'DATE DO RECEIVED' => Carbon::parse($tea->date_received)->format('D, d-m-Y'),
                'PRODUCER WAREHOUSE' => $tea->warehouse_name,
                'AGING DATE' => $this->appClass->getAgingDays($tea->delivery_id, time()).' days',
                'RECEIVED BY' => $user->surname.' '.$user->first_name,
            ];
        }

        return collect($teas);
    }

    public function headings(): array
    {
        // TODO: Implement headings() method.

        return [
            ['PACKMAC HOLDINGS LIMITED'],
            ['Chai Street Shimanzi'],
            ['High Level, Shimanzi Area. Mombasa'],
            ['P.O Box 41932-80100, Mombasa, Kenya'],
            ['STOCK COLLECTION POSITION OF TEAS IN OUR WAREHOUSES AS OF ' . Carbon::now()->format('D, d-m-Y H:i:s')],
            ['DELIVERY_NUMBER', 'CLIENT NAME', 'DELIVERY TYPE', 'DO NUMBER', 'GARDEN NAME', 'GRADE', 'INVOICE NUMBER', 'PACKAGES', 'NET WEIGHT', 'PROMPT DATE',  'DATE DO RECEIVED', 'PRODUCER WAREHOUSE', 'AGING DATE', 'RECEIVED BY']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for static heading rows
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');
        $sheet->mergeCells('A4:N4');
        $sheet->mergeCells('A5:N5');
        // Center align text in merged cells
        $sheet->getStyle('A1:N5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Apply additional styling
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('5')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('6')->applyFromArray(['font' => ['bold' => true]]);
//        $sheet->getStyle('A:R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $lastRow = $sheet->getHighestRow(); // Get the last row dynamically
        $sheet->getStyle("A6:N$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        // Return styles
        return [];
    }

}
