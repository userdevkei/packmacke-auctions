<?php

namespace App\Services;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportShipments implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
        $i = 1;
        foreach ($this->data as $tea) {
            $containerSize = $tea->total_containers . ' * ' .
                ($tea->container_size == 1 ? '20 FT' :
                    ($tea->container_size == 2 ? '40 FT' : '40 FTHC'));
            $teas[] = [
                '#' => $i++,
                'SI NUMBER' => $tea->shipping_number,
                'TYPE' => $tea->type,
                'CLIENT NAME' => $tea->client_name,
                'AGENT' => $tea->agent_name,
                'WAREHOUSE' => $tea->station_name,
                'DESTINATION PORT' => $tea->port_name,
                'TRANSPORTER' => $tea->transporter_name,
                'CONSIGNEE' => $tea->consignee,
                'VESSEL NAME' => $tea->vessel_name,
                'SHIPPING MARK' => $tea->shipping_mark,
                'CONTAINER' => $containerSize,
                'PACKAGES' => number_format($tea->output_packages, 0, '', ','),
                'WEIGHT' => number_format($tea->output_weight, 2),
                'DATE SHIPPED' => $tea->shipment_date == null ? 'Pending' : \Carbon\Carbon::createFromTimestamp($tea->shipment_date)->format('Y-m-d'),
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
            ['SHIPMENT DETAILS GENERATED ON ' . Carbon::now()->format('D, d-m-Y H:i:s')],
            ['#', 'SI NUMBER', 'TYPE', 'CLIENT NAME', 'SHIPPING AGENT', 'WAREHOUSE', 'DESTINATION PORT', 'TRANSPORTER NAME', 'CONSIGNEE', 'VESSEL NAME', 'SHIPPING MARK', 'CONTAINER', 'PACKAGES', 'NET WEIGHT', 'DATE SHIPPED/STATUS']
        ];
    }
    public function styles(Worksheet $sheet)
    {
        // Merge cells for static heading rows
        $sheet->mergeCells('A1:O1');
        $sheet->mergeCells('A2:O2');
        $sheet->mergeCells('A3:O3');
        $sheet->mergeCells('A4:O4');
        $sheet->mergeCells('A5:O5');

        // Center align text in merged cells
        $sheet->getStyle('A1:O5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Apply additional styling
        $sheet->getStyle('1')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('5')->applyFromArray(['font' => ['bold' => true]]);
        $sheet->getStyle('6')->applyFromArray(['font' => ['bold' => true]]);

        // Return styles
        return [];
    }
}
