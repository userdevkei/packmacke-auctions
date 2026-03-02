<?php

namespace App\Services;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportPendingTCI implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{

    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $teas = [];
        // TODO: Implement collection() method.
        foreach ( $this->data as $tea){

            $teas[] = [
                'TCI NUMBER' => $tea['loading_number'],
                'CLIENT NAME' => $tea['client_name'],
                'INVOICE NUMBER' => $tea['invoice_number'],
                'LOT NUMBER' => $tea['lot_number'],
                'GRADE' => $tea['grade_name'],
                'GARDEN NAME' => $tea['garden_name'],
                'PACKAGES' => $tea['packet'],
                'NET WEIGHT' => number_format($tea['weight'], 2),
                'DESTINATION' => $tea['station_name'],
            ];
        }

        return collect($teas);
    }

    public function headings(): array
    {
        // TODO: Implement headings() method.

        $locality = $this->data[0]['location_name'];
        return [
            ['TCI PENDING COLLECTION  FOR '. $locality],
            ['TCI NUMBER', 'CLIENT NAME', 'INVOICE NUMBER', 'LOT NUMBER', 'GRADE', 'GARDEN', 'PACKAGES', 'NET WEIGHT', 'DESTINATION'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells for static heading rows
        $sheet->mergeCells('A1:I1');

        // Center align text in merged cells
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Apply additional styling
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
//        $sheet->getStyle('5')->applyFromArray(['font' => ['bold' => true]]);
//        $sheet->getStyle('6')->applyFromArray(['font' => ['bold' => true]]);
//        $sheet->getStyle('7')->applyFromArray(['font' => ['bold' => true]]);
//        $sheet->getStyle('8')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('2')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('H:H')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places

        // Return styles
        return [];
    }

}
