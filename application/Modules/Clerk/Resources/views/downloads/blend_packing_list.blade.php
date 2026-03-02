<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sheet->blend_number }}</title>
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
<?php
// Calculate per container values
$containerCount = count($containers);
$packagesPerContainer = floor($sheet->output_packages / $containerCount);
$weightPerContainer = $sheet->output_weight / $containerCount;

// Parse consignee address
$consigneeAddr = json_decode($sheet->consignee_address, true);

// Calculate tare and pallet weights per container
$tarePerContainer = $packagesPerContainer * $sheet->packet_tare;

// Check if we should show pallet weight (load_type = 4)
$showPalletWeight = isset($sheet->package_type) && ($sheet->package_type !== 4);

// Grand totals
$grandTotalPackages = 0;
$grandTotalNet = 0;
$grandTotalTare = 0;
$grandTotalPallet = 0;
$grandTotalGross = 0;
?>

<div class="company-info">
    <h1 class="heading">{{ $sheet->client_name }}</h1>
    <p>{{ $sheet->address }}</p>
</div>
<div class="header">PACKING LIST<hr></div>
<table>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> INVOICE NUMBER </td>
        <td style="width: 40% !important;"> : {{ $sheet->blend_number }} </td>
        <td style="width: 20%!important;"> PORT OF LOADING </td>
        <td style="width: 25% !important;"> : MOMBASA, KENYA </td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> CONSIGNEE </td>
        <td style="width: 40% !important;"> : {{ $sheet->consignee }} </td>
        <td style="width: 20% !important;"> DESTINATION PORT </td>
        <td style="width: 25% !important;"> : {{ $sheet->port_name }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;">  </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['box'] ?? '' }} </td>
        <td style="width: 20% !important;"> BOOKING NUMBER</td>
        <td style="width: 25% !important;"> : {{ $sheet->booking_number }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;">  </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['address'] ?? '' }} </td>
        <td style="width: 20% !important;"> DATE </td>
        <td style="width: 25% !important;"> : {{ \Carbon\Carbon::parse($sheet->blend_date)->format('d/m/Y') }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['state'] ?? '' }} </td>
        <td style="width: 20% !important;"> SHIPPING MARKS </td>
        <td style="width: 25% !important;"> : {{ $sheet->shipping_mark }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['mobile'] ?? '' }} </td>
        <td style="width: 20% !important;"> OCEAN VESSEL </td>
        <td style="width: 25% !important;"> : {{ $sheet->vessel_name }} </td>
    </tr>
</table>

<hr>

@foreach($containers as $index => $containerNumber)
        <?php
        $palletWeight = $showPalletWeight ? ($containerNumber->pallet_weight ?? 0) : 0;
        $grossPerContainer = $weightPerContainer + $tarePerContainer + $palletWeight;

        // Update grand totals
        $grandTotalPackages += $packagesPerContainer;
        $grandTotalNet += $weightPerContainer;
        $grandTotalTare += $tarePerContainer;
        $grandTotalPallet += $palletWeight;
        $grandTotalGross += $grossPerContainer;
        ?>

    <div class="container-header">
        <span style="margin-right: 200px;">Cont No: {{ $containerNumber->container_number }}</span>
        <span>Seal No: {{ $containerNumber->seal_number }}</span>
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

<br>
<br>
<table class="table2">
    <tr>
        <td style="width: 8% !important;">Prepared By</td>
        <td style="width: 38% !important;"><hr class="dotted-hr"></td>
        <td style="width: 4% !important;"></td>
        <td style="width: 8% !important;">Checked By </td>
        <td style="width: 40% !important;"><hr class="dotted-hr"></td>
    </tr>
</table>
<p><i><strong>Printed On:</strong> {{ now()->format('d/m/Y H:i:s') }}</i></p>
</body>
</html>
