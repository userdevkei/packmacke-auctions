<?php

namespace App\Exports;

use App\Models\UserInfo;
use App\Services\AppClass;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportBlendBalances implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
        foreach ( $this->data as $key => $tea){
            $teas[] = [
                '#' => ++$key,
                'CLIENT NAME' => $tea->client_name,
                'BLEND NUMBER' => $tea->blend_number,
                'TYPE' => $tea->type,
                'GARDEN NAME' => $tea->garden,
                'GRADE' => $tea->grade,
                'PACKAGES' => $tea->current_packages,
                'NET WEIGHT' => $tea->current_weight,
                'STOCKED AT' => $tea->station_name,
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
            ['BLEND BALANCES IN STOCK AS OF ' . Carbon::now()->format('D, d-m-Y H:i:s')],
            ['#', 'CLIENT NAME', 'BLEND NUMBER', 'BALANCE TYPE', 'GARDEN NAME', 'GRADE', 'PACKAGES', 'NET WEIGHT', 'STOCKED AT']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for static heading rows
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->mergeCells('A5:I5');

        // Center align text in merged cells
        $sheet->getStyle('A1:I5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Apply additional styling
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('5')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('6')->applyFromArray(['font' => ['bold' => true]]);
//        $sheet->getStyle('A:R')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $lastRow = $sheet->getHighestRow(); // Get the last row dynamically
        $sheet->getStyle("A6:R$lastRow")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        // Return styles
        return [];
    }

}
