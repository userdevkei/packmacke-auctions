<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuctionSheet Implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct($teas, $sale)
    {
        $this->teas = $teas;
        $this->sale = $sale;
    }
    public function collection()
    {
       $teas = $this->teas;
$lines = collect();
$line = [];
$packages = 0;
$totalUnitWeight = 0;
$totalPkgGrossWeight = 0;
$totalNetWeight = 0;
$totalGrossWeight = 0;

foreach ($teas as $key => $tea) {
    $unitWeight    = $tea->weight / $tea->packet;
    $pkGrossWeight = $unitWeight + $tea->package_tare;
    $grossWeight   = $tea->weight + ($tea->packet * $tea->package_tare);

    $packages            += $tea->packet;
    $totalUnitWeight     += $unitWeight;
    $totalPkgGrossWeight += $pkGrossWeight;
    $totalNetWeight      += $tea->weight;
    $totalGrossWeight    += $grossWeight;

    $line[] = [
        '#'               => ++$key,
        'CLIENT NAME'     => $tea->client_name,
        'DO NUMBER'       => $tea->order_number,
        'GARDEN NAME'     => $tea->garden_name,
        'GRADE NAME'      => $tea->grade_name,
        'INVOICE NUMBER'  => $tea->invoice_number,
        'PACKAGES'        => $tea->packet,
        'TOTAL WEIGHT'    => $unitWeight,
        'PKG GR. WEIGHT'  => $pkGrossWeight,
        'PKG WEIGHT'      => $tea->weight,
        'TOTAL GR WEIGHT' => $grossWeight,
        'WARRANT NUMBER'  => $tea->warrant_number,
    ];
}

// Add totals row
$totals = [
    '',
    '',
    '',
    '',
    '',
    '',
    'PACKAGES'        => $packages,
    'TOTAL WEIGHT'    => $totalUnitWeight,
    'PKG GR. WEIGHT'  => $totalPkgGrossWeight,
    'PKG WEIGHT'      => $totalNetWeight,
    'TOTAL GR WEIGHT' => $totalGrossWeight,
    '',
];

        $lines->push($line);
        $lines->push($totals);

        return $lines;
    }

    public function headings(): array
    {
        // TODO: Implement headings() method.

        return [
            ['PACKMAC HOLDINGS LIMITED'],
            ['Chai Street Shimanzi'],
            ['High Level, Shimanzi Area. Mombasa'],
            ['P.O Box 41932-80100, Mombasa, Kenya'],
            ['WEIGHT NOTES FOR AUCTION TEAS SALE '. $this->sale],
            ['#', 'CLIENT NAME', 'DO NUMBER', 'GARDEN NAME', 'GRADE NAME', 'INVOICE NUMBER', 'PACKAGES', 'PK WEIGHT', 'PKG GR WEIGHT', 'TOTAL WEIGHT', 'TOTAL GR WEIGHT', 'WARRANT NUMBER'],
        ];
    }
    public function styles(Worksheet $sheet)
    {
        foreach (range(1, 5) as $row) {
            $sheet->mergeCells("A{$row}:L{$row}");
        }

        $sheet->getStyle('A1:L6')
            ->applyFromArray(['font' => ['bold' => true]])
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:L6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A1:L5')->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $sheet->getStyle('G:K')
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00); // Ensures 2 decimal places
        return [];
    }
}
