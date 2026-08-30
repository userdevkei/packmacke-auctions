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

class ExportClosingStock implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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

            $createdAt = Carbon::parse($tea->created_at);
            $currentDate = Carbon::now();
            $diffInDays = $createdAt->diffInDays($currentDate);
            $user = UserInfo::where('user_id', $tea->received_by)->first();

            $teas[] = [
                'CLIENT NAME' => $tea->client_name,
                'DELIVERY TYPE' => $tea->delivery_type == 1 ? 'DO ENTRY' : 'DIRECT DELIVERY',
                'DO NUMBER' => $tea->order_number,
                'GARDEN NAME' => $tea->garden_name,
                'GRADE' => $tea->grade_name,
                'ORIGIN' => $tea->tea_type ?? 'Local',
                'INVOICE NUMBER' => $tea->invoice_number,
                'PACKAGES' => $tea->display_stock,
                'NET WEIGHT' => $tea->display_weight,
                'CLOSING DATE' => $tea->closing_date == null ? null : Carbon::parse($tea->closing_date)->format('d-m-Y'),
                'WAREHOUSE' => $tea->stocked_at,
                'WAREHOUSE BAY' => $tea->bay_name,
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
            ['CLOSING STOCK POSITION OF TEAS IN OUR WAREHOUSES AS OF ' . Carbon::now()->format('D, d-m-Y H:i:s')],
            ['CLIENT NAME', 'DELIVERY TYPE', 'DO NUMBER', 'GARDEN NAME', 'GRADE', 'ORIGIN', 'INVOICE NUMBER', 'PACKAGES', 'NET WEIGHT',  'CLOSING DATE', 'WAREHOUSE', 'WAREHOUSE BAY', 'AGING DATE', 'RECEIVED BY']
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
