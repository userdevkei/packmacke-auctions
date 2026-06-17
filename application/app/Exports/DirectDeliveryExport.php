<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DirectDeliveryExport
{
    protected array $filters;

    // Logo path — store the Packmac logo in public/images/packmac_logo.png
    const LOGO_PATH = 'public/images/packmac_logo.png';

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    protected function getData(): \Illuminate\Support\Collection
    {
        $query = DB::table('stock_ins as si')
            ->join('delivery_orders as do', 'do.delivery_id', '=', 'si.delivery_id')
            ->join('clients as c', 'c.client_id', '=', 'do.client_id')
            ->join('gardens as g', 'g.garden_id', '=', 'do.garden_id')
            ->join('grades as gr', 'gr.grade_id', '=', 'do.grade_id')
            ->join('warehouses as w', 'w.warehouse_id', '=', 'do.warehouse_id')
            ->join('stations as st', 'st.station_id', '=', 'si.station_id')
            ->leftJoin('transporters as t', 't.transporter_id', '=', 'si.transporter_id')
            ->leftJoin('drivers as d', 'd.driver_id', '=', 'si.driver_id')
            ->where('do.delivery_type', 2)
            ->select([
                'si.delivery_number',
                'si.registration',
                'c.client_name',
                'w.warehouse_name',        // Factory of Origin (producer warehouse)
                'st.station_name',         // Warehouse Stored (PHML warehouse)
                't.transporter_name',
                'do.dispatch_date',
                DB::raw('FROM_UNIXTIME(si.date_received) as arrival_date'),
                'g.garden_name',
                'do.invoice_number',
                'gr.grade_name',
                'do.packet as packages',
                'do.package',              // 1=PB, 2=PS
                'do.weight as unit_weight',
                'do.gross_weight',
                'si.package_tare',
                'si.net_weight as actual_net',
                'do.printed_weight',
                'si.total_weight',
                'si.pallet_weight',
                'do.height as pallet_height',
                'si.ra',
                'si.sample_received',
                'si.gain_loss',
                DB::raw('si.total_pallets * si.package_tare as total_tare')
            ]);

        if (!empty($this->filters['dispatch_from'])) {
            $query->whereDate('do.dispatch_date', '>=', $this->filters['dispatch_from']);
        }
        if (!empty($this->filters['dispatch_to'])) {
            $query->whereDate('do.dispatch_date', '<=', $this->filters['dispatch_to']);
        }
        if (!empty($this->filters['arrival_from'])) {
            $query->whereRaw('DATE(FROM_UNIXTIME(si.date_received)) >= ?', [$this->filters['arrival_from']]);
        }
        if (!empty($this->filters['arrival_to'])) {
            $query->whereRaw('DATE(FROM_UNIXTIME(si.date_received)) <= ?', [$this->filters['arrival_to']]);
        }
        if (!empty($this->filters['client_id'])) {
            $query->where('do.client_id', $this->filters['client_id']);
        }
        if (!empty($this->filters['delivery_number'])) {
            $query->where('si.delivery_number', 'like', '%' . $this->filters['delivery_number'] . '%');
        }
        if (!empty($this->filters['transporter_id'])) {
            $query->where('si.transporter_id', $this->filters['transporter_id']);
        }

        return $query->orderBy('si.delivery_number')->orderBy('si.registration')->get();
    }

    protected function writeBlock(Worksheet $ws, int $startRow, $groupRows): int
    {
        $first   = $groupRows->first();
        $bold    = ['font' => ['bold' => true, 'name' => 'Arial', 'size' => 10]];
        $normal  = ['font' => ['bold' => false, 'name' => 'Arial', 'size' => 10]];

        // ── Logo (rows 2-6, cols F-H) ─────────────────────────────────────
        $logoPath =  'assets/img/favicons/icon.png';
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setPath($logoPath);
            $drawing->setWidth(77);
            $drawing->setHeight(78);
            $drawing->setCoordinates('F' . ($startRow + 1));
            $drawing->setOffsetX(35);
            $drawing->setWorksheet($ws);
        }

        // ── Company name (row 7, merged C:K) ─────────────────────────────
        $r = $startRow + 6;
        $ws->mergeCells("C{$r}:K{$r}");
        $ws->setCellValue("C{$r}", 'PACKMAC HOLDINGS LIMITED');
        $ws->getStyle("C{$r}")->applyFromArray([
            'font'      => ['bold' => false, 'name' => 'Arial', 'size' => 18],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Address block (rows 10-12) ────────────────────────────────────
        $r10 = $startRow + 9;
        $ws->setCellValue("D{$r10}",       'Postal Address: 87227 - 80100');
        $ws->setCellValue("D" . ($r10+1),  'Physical Address: Chai Street, Shimanzi, Mombasa - KENYA');
        $ws->setCellValue("D" . ($r10+2),  'Email: info@packmac.net');
        $ws->getStyle("D{$r10}:D" . ($r10+2))->applyFromArray($bold);

        // ── Title (row 16, merged G:J) ────────────────────────────────────
        $r16 = $startRow + 15;
        $ws->mergeCells("G{$r16}:J{$r16}");
        $ws->setCellValue("G{$r16}", 'Tea Arrival Report');
        $ws->getStyle("G{$r16}")->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Client block (rows 17-18) ─────────────────────────────────────
        $r17 = $startRow + 16;
        $ws->setCellValue("A{$r17}",      $first->client_name);
        $ws->getStyle("A{$r17}")->applyFromArray($bold);

        // ── Warehouse / factory row (row 20) ──────────────────────────────
        $r20 = $startRow + 19;
        $ws->mergeCells("L{$r20}:Q{$r20}");
        $ws->setCellValue("A{$r20}", 'Warehouse Stored : ' . $first->station_name);
        $ws->setCellValue("L{$r20}", 'Factory of Origin: ' . $first->warehouse_name);
        $ws->getStyle("A{$r20}")->applyFromArray($bold);
        $ws->getStyle("L{$r20}")->applyFromArray($bold);

        // ── Transporter / Truck row (row 22) ──────────────────────────────
        $r22 = $startRow + 21;
        $ws->mergeCells("A{$r22}:F{$r22}");
        $ws->mergeCells("I{$r22}:L{$r22}");
        $ws->setCellValue("A{$r22}", 'Transporter :' . ($first->transporter_name ?? ''));
        $ws->setCellValue("I{$r22}", 'Truck # : ' . ($first->registration ?? ''));
        $ws->getStyle("A{$r22}")->applyFromArray($bold);
        $ws->getStyle("I{$r22}")->applyFromArray($bold);

        // ── Dispatch date / D-Note row (row 24) ───────────────────────────
        $r24 = $startRow + 23;
        $ws->mergeCells("A{$r24}:D{$r24}");
        $ws->mergeCells("H{$r24}:K{$r24}");
        $dispatchFmt = $first->dispatch_date
            ? \Carbon\Carbon::parse($first->dispatch_date)->format('d/m/Y')
            : '';
        $ws->setCellValue("A{$r24}", 'Dispatch Date : ' . $dispatchFmt);
        $ws->setCellValue("H{$r24}", 'D-Note : ' . $first->delivery_number);
        $ws->getStyle("A{$r24}")->applyFromArray($bold);
        $ws->getStyle("H{$r24}")->applyFromArray($bold);

        // ── Column headers (row 26) ───────────────────────────────────────
        $headerRow = $startRow + 25;
        $headers   = [
            'A' => ['Arrival Date',   Alignment::HORIZONTAL_LEFT],
            'B' => ['D/Note',         Alignment::HORIZONTAL_CENTER],
            'C' => ['Garden',         Alignment::HORIZONTAL_CENTER],
            'D' => ['Invoice Number', Alignment::HORIZONTAL_RIGHT],
            'E' => ['Grade',          Alignment::HORIZONTAL_LEFT],
            'F' => ['Pkgs',           Alignment::HORIZONTAL_RIGHT],
            'G' => ['Pkg Name',       Alignment::HORIZONTAL_LEFT],
            'H' => ['Pkg Net',        Alignment::HORIZONTAL_RIGHT],
            'I' => ['Pkg Tare',       Alignment::HORIZONTAL_RIGHT],
            'J' => ['Smpl Rcvd',      Alignment::HORIZONTAL_LEFT],
            'K' => ['Gross Wt',       Alignment::HORIZONTAL_RIGHT],
            'L' => ['Printed Net Wt', Alignment::HORIZONTAL_RIGHT],
            'M' => ['Gain / Loss',    Alignment::HORIZONTAL_RIGHT],
            'N' => ['Total Tare',     Alignment::HORIZONTAL_RIGHT],
            'O' => ['Plt Wt',         Alignment::HORIZONTAL_RIGHT],
            'P' => ['Actual Net Kgs', Alignment::HORIZONTAL_RIGHT],
            'Q' => ['Pallet Height',  Alignment::HORIZONTAL_LEFT],
            'R' => ['RA',             Alignment::HORIZONTAL_LEFT],
        ];

        foreach ($headers as $col => [$label, $align]) {
            $ws->setCellValue($col . $headerRow, $label);
            $ws->getStyle($col . $headerRow)->applyFromArray([
                'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9],
                'alignment' => ['horizontal' => $align],
            ]);
        }

        // ── Data rows ─────────────────────────────────────────────────────
        $dataStart = $headerRow + 1;
        $rowNum    = $dataStart;

        foreach ($groupRows as $rec) {
            $arrivalFmt = $rec->arrival_date
                ? \Carbon\Carbon::parse($rec->arrival_date)->format('d.m.Y')
                : '';
            $pkgName = match ((int) $rec->package) { 1 => 'PB', 2 => 'PS', default => '' };

            $ws->setCellValue('A' . $rowNum, $arrivalFmt);
            $ws->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $ws->setCellValue('B' . $rowNum, $rec->delivery_number);
            $ws->setCellValue('C' . $rowNum, $rec->garden_name);
            $ws->setCellValue('D' . $rowNum, $rec->invoice_number);
            $ws->setCellValue('E' . $rowNum, $rec->grade_name);
            $ws->setCellValue('F' . $rowNum, (float) ($rec->packages ?? 0));
            $ws->setCellValue('G' . $rowNum, $pkgName);
            $ws->setCellValue('H' . $rowNum, (float) ($rec->unit_weight ?? 0));
            $ws->setCellValue('I' . $rowNum, (float) ($rec->package_tare ?? 0));
            $ws->setCellValue('J' . $rowNum, $rec->sample_received ?? '');
            $ws->getStyle('J' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->setCellValue('K' . $rowNum, (float) ($rec->gross_weight ?? 0));
            $ws->setCellValue('L' . $rowNum, (float) ($rec->printed_weight ?? 0));
            $ws->setCellValue('M' . $rowNum, (float) ($rec->gain_loss ?? 0));
            $ws->getStyle('M' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->setCellValue('N' . $rowNum, (float) ($rec->total_tare ?? 0));
            $ws->getStyle('N' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->setCellValue('O' . $rowNum, (float) ($rec->pallet_weight ?? 0));
            $ws->setCellValue('P' . $rowNum, (float) ($rec->actual_net ?? 0));
            $ws->setCellValue('Q' . $rowNum, $rec->pallet_height ?? '');
            $ws->getStyle('Q' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->setCellValue('R' . $rowNum, $rec->ra ?? '');

            // Apply normal font to data row
            $ws->getStyle("A{$rowNum}:R{$rowNum}")->applyFromArray($normal);

            $rowNum++;
        }

        // ── Totals row ────────────────────────────────────────────────────
        $lastData = $rowNum - 1;
        $ws->setCellValue('F' . $rowNum, "=SUM(F{$dataStart}:F{$lastData})");
        $ws->setCellValue('K' . $rowNum, "=SUM(K{$dataStart}:K{$lastData})");
        $ws->setCellValue('L' . $rowNum, "=SUM(L{$dataStart}:L{$lastData})");
        $ws->setCellValue('N' . $rowNum, "=SUM(N{$dataStart}:N{$lastData})");
        $ws->setCellValue('P' . $rowNum, "=SUM(P{$dataStart}:P{$lastData})");
        $ws->getStyle("F{$rowNum}:P{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
        ]);

        // ── Remarks row (2 rows after totals) ────────────────────────────
        $remarksRow = $rowNum + 2;
        $ws->mergeCells("A{$remarksRow}:R{$remarksRow}");
        $ws->setCellValue("A{$remarksRow}", 'Remarks : Teas received intact and in good condition');
        $ws->getStyle("A{$remarksRow}")->applyFromArray($normal);

        // Return the next available start row (with 8 gap rows between blocks)
        return $remarksRow + 8;
    }

    protected function setColumnWidths(Worksheet $ws): void
    {
        $widths = [
            'A' => 12, 'B' => 20, 'C' => 12, 'D' => 15, 'E' => 7,
            'F' => 7,  'G' => 8,  'H' => 8,  'I' => 8,  'J' => 9,
            'K' => 10, 'L' => 13, 'M' => 10, 'N' => 10, 'O' => 8,
            'P' => 13, 'Q' => 12, 'R' => 5,
        ];
        foreach ($widths as $col => $width) {
            $ws->getColumnDimension($col)->setWidth($width);
        }
    }

    public function download(): StreamedResponse
    {
        $rows = $this->getData();

        // Group by delivery_number + registration (one block per truck per delivery)
        $groups = $rows->groupBy(fn($r) => $r->delivery_number . '|||' . ($r->registration ?? ''));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $spreadsheet->removeSheetByIndex(0);

        // All blocks go on ONE sheet, stacked vertically (matching template)
        $ws = new Worksheet($spreadsheet, 'Direct Deliveries');
        $spreadsheet->addSheet($ws, 0);

        $this->setColumnWidths($ws);
        $ws->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $ws->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);

        $currentRow = 1;

        foreach ($groups as $groupRows) {
            $currentRow = $this->writeBlock($ws, $currentRow, $groupRows);
        }

        $filename = 'direct_deliveries_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }
}
