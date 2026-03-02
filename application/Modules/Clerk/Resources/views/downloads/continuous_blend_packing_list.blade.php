<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing List</title>
    <style>
        body {
            font-size: 12px;
            line-height: 0.9;
            padding: 0 !important;
            margin: 0 !important;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }
        .company-info {
            text-align: center;
            margin: 0 !important;
            padding: 0 !important;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table, .table th, .table td {
            border: 1px solid black;
        }
        .table th, .table td {
            padding: 6px;
            text-align: left;
            font-size: 11px !important;
        }
        .heading {
            color: green;
            font-size: 14px !important;
            font-weight: bold !important;
        }
        .tfooter {
            font-weight: bold !important;
        }
        .logo {
            height: 50px !important;
            width: 50px !important;
            padding: 0 !important;
        }
        .container-header {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: right;
            padding: 8px !important;
        }
        .totals-row {
            font-weight: bold;
            background-color: #e8e8e8;
        }
        .grand-totals-row {
            font-weight: bold;
            background-color: #d0d0d0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@php
    // Get the first sheet for header information
    $firstSheet = $sheets->first();

    // Parse consignee address
    $consigneeAddr = json_decode($firstSheet->consignee_address, true);

    // Initialize grand totals across all sheets
    $grandTotalPackages = 0;
    $grandTotalNet = 0;
    $grandTotalTare = 0;
    $grandTotalPallet = 0;
    $grandTotalGross = 0;
@endphp

    <!-- Single Header Section -->
<div class="company-info">
    <h1 class="heading">{{ $firstSheet->client_name }}</h1>
    <p>{{ $firstSheet->address }}</p>
</div>
<div class="header">PACKING LIST<hr></div>
<table>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> INVOICE NUMBER </td>
        <td style="width: 40% !important;"> : {{ $firstSheet->blend_number }} </td>
        <td style="width: 20%!important;"> PORT OF LOADING </td>
        <td style="width: 25% !important;"> : MOMBASA, KENYA </td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> CONSIGNEE </td>
        <td style="width: 40% !important;"> : {{ $firstSheet->consignee }} </td>
        <td style="width: 20% !important;"> DESTINATION PORT </td>
        <td style="width: 25% !important;"> : {{ $firstSheet->port_name }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;">  </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['box'] ?? '' }} </td>
        <td style="width: 20% !important;"> BOOKING NUMBER</td>
        <td style="width: 25% !important;"> : {{ $firstSheet->booking_number }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;">  </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['address'] ?? '' }} </td>
        <td style="width: 20% !important;"> DATE </td>
        <td style="width: 25% !important;"> : {{ \Carbon\Carbon::parse($firstSheet->blend_date)->format('d/m/Y') }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['state'] ?? '' }} </td>
        <td style="width: 20% !important;"> SHIPPING MARKS </td>
        <td style="width: 25% !important;"> : {{ $firstSheet->shipping_mark }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['mobile'] ?? '' }} </td>
        <td style="width: 20% !important;"> OCEAN VESSEL </td>
        <td style="width: 25% !important;"> : {{ $firstSheet->vessel_name }} </td>
    </tr>
</table>

<hr>

<!-- Container Tables Section -->
@foreach($sheets as $sheetIndex => $sheet)
    @php
        // Get containers for this blend
        $blendContainers = $sheet->containers;

        // Calculate per container values
        $containerCount = $blendContainers->count();
        $packagesPerContainer = $containerCount > 0 ? floor($sheet->output_packages / $containerCount) : 0;
        $weightPerContainer = $containerCount > 0 ? $sheet->output_weight / $containerCount : 0;

        // Calculate tare per container
        $tarePerContainer = $packagesPerContainer * $sheet->packet_tare;

        // Check if we should show pallet weight (package_type = 4)
        $showPalletWeight = isset($sheet->package_type) && ($sheet->package_type !== 4);

        // Blend totals for this sheet
        $blendTotalPackages = 0;
        $blendTotalNet = 0;
        $blendTotalTare = 0;
        $blendTotalPallet = 0;
        $blendTotalGross = 0;
    @endphp

    @foreach($blendContainers as $index => $container)
        @php
            // Calculate container totals
            $palletWeight = $showPalletWeight ? ($container->pallet_weight ?? 0) : 0;
            $grossPerContainer = $weightPerContainer + $tarePerContainer + $palletWeight;

            // Update blend totals
            $blendTotalPackages += $packagesPerContainer;
            $blendTotalNet += $weightPerContainer;
            $blendTotalTare += $tarePerContainer;
            $blendTotalPallet += $palletWeight;
            $blendTotalGross += $grossPerContainer;
        @endphp

        <div class="container-header">
            <span style="margin-right: 200px;">Cont No: {{ $container->container_number }}</span>
            <span>Seal No: {{ $container->seal_number }}</span>
        </div>

        <table class="table">
            <thead>
            <tr>
                <th style="width: 12% !important;">Garden</th>
                <th style="width: 12% !important;">Invoice No</th>
                <th style="width: 8% !important;">Grade</th>
                <th style="width: 8% !important;">Qty</th>
                <th style="width: 10% !important;">Nett Kgs</th>
                <th style="width: 10% !important;">TARE</th>
                @if($showPalletWeight)
                    <th style="width: 10% !important;">Plt Wtt</th>
                @endif
                <th style="width: 10% !important;">Gross Kgs</th>
                <th style="width: 10% !important;">REMARKS</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $sheet->garden }}</td>
                <td>{{ $sheet->blend_number }}</td>
                <td>{{ $sheet->grade }}</td>
                <td>{{ $packagesPerContainer }}</td>
                <td>{{ number_format($weightPerContainer, 2) }}</td>
                <td>{{ number_format($tarePerContainer, 2) }}</td>
                @if($showPalletWeight)
                    <td>{{ number_format($palletWeight, 2) }}</td>
                @endif
                <td>{{ number_format($grossPerContainer, 2) }}</td>
                <td></td>
            </tr>
            <tr class="totals-row">
                <td colspan="2"></td>
                <td><strong>TOTALS</strong></td>
                <td>{{ $packagesPerContainer }}</td>
                <td>{{ number_format($weightPerContainer, 2) }}</td>
                <td>{{ number_format($tarePerContainer, 2) }}</td>
                @if($showPalletWeight)
                    <td>{{ number_format($palletWeight, 2) }}</td>
                @endif
                <td>{{ number_format($grossPerContainer, 2) }}</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="{{ $showPalletWeight ? 9 : 8 }}"><br></td>
            </tr>
            </tbody>
        </table>
    @endforeach

    @php
        // Update grand totals
        $grandTotalPackages += $blendTotalPackages;
        $grandTotalNet += $blendTotalNet;
        $grandTotalTare += $blendTotalTare;
        $grandTotalPallet += $blendTotalPallet;
        $grandTotalGross += $blendTotalGross;
    @endphp
@endforeach

<!-- Single Blend Summary at the End -->
<table class="table">
    <tr class="grand-totals-row">
        <td colspan="2" style="width: 24% !important;"><strong>BLEND SUMMARY</strong></td>
        <td style="width: 8% !important;"><strong>TOTALS</strong></td>
        <td style="width: 8% !important;"><strong>{{ $grandTotalPackages }}</strong></td>
        <td style="width: 10% !important;"><strong>{{ number_format($grandTotalNet, 2) }}</strong></td>
        <td style="width: 10% !important;"><strong>{{ number_format($grandTotalTare, 2) }}</strong></td>
        @if($firstSheet->package_type !== 4)
            <td style="width: 10% !important;"><strong>{{ number_format($grandTotalPallet, 2) }}</strong></td>
        @endif
        <td style="width: 10% !important;"><strong>{{ number_format($grandTotalGross, 2) }}</strong></td>
        <td style="width: 10% !important;"></td>
    </tr>
</table>

<br>
<br>
<table class="table2" style="width: 100%; border: none;">
    <tr>
        <td style="width: 8% !important; border: none;">Prepared By</td>
        <td style="width: 38% !important; border: none;"><hr style="border-top: 1px dotted #000;"></td>
        <td style="width: 4% !important; border: none;"></td>
        <td style="width: 8% !important; border: none;">Checked By </td>
        <td style="width: 40% !important; border: none;"><hr style="border-top: 1px dotted #000;"></td>
    </tr>
</table>
<p><i><strong>Printed On:</strong> {{ now()->format('d/m/Y H:i:s') }} by {{ $staffName }}</i></p>
</body>
</html>
