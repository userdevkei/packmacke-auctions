<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
{{--    <title>{{ $shipment->blend_number }}</title>--}}
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
<?php
$consigneeAddr = json_decode($shipment->consignee_address, true);
$showPalletWeight = isset($shipment->package_type) && ($shipment->package_type !== 4);

$grossPerContainer = $showPalletWeight ? $shipment->weight + $shipment->tare_weight + $shipment->pallet_weight : $shipment->weight + $shipment->tare_weight;
?>
<body>
<div class="company-info">
    <span>
        <img class="logo" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/img/favicons/icon.png'))) }}" alt="Logo">
    </span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>

<div class="header">PACKING LIST<hr></div>
<table>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> SHIPPER </td>
        <td style="width: 85% !important;">
            <h4> : {{ $shipment->client_name }}</h4>
            <p>: {{ $shipment->address }}</p>
        </td>
    </tr>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> SI NUMBER </td>
        <td style="width: 40% !important;"> : {{ $shipment->blend_number }} </td>
        <td style="width: 20%!important;"> PORT OF LOADING </td>
        <td style="width: 25% !important;"> : MOMBASA, KENYA </td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> PO/INV NUMBER </td>
        <td style="width: 40% !important;"> : </td>

        <td style="width: 20% !important;"> DESTINATION PORT </td>
        <td style="width: 25% !important;"> : {{ $shipment->port_name }}</td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> CONSIGNEE </td>
        <td style="width: 40% !important;"> : {{ $shipment->consignee }} </td>
        <td style="width: 20% !important;"> BOOKING NUMBER</td>
        <td style="width: 25% !important;"> : {{ $shipment->booking_number }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> CONSIGNEE PO BOX </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['box'] ?? '' }} </td>
        <td style="width: 20% !important;"> DATE </td>
        <td style="width: 25% !important;"> : {{ \Carbon\Carbon::parse($shipment->blend_date)->format('d/m/Y') }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> CONSIGNEE ADDRESS </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['address'] ?? '' }} </td>
        <td style="width: 20% !important;"> SHIPPING MARKS </td>
        <td style="width: 25% !important;"> : {{ $shipment->shipping_mark }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> STATE/COUNTRY </td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['state'] ?? '' }} </td>
        <td style="width: 20% !important;"> OCEAN VESSEL </td>
        <td style="width: 25% !important;"> : {{ $shipment->vessel_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> PHONE NUMBER</td>
        <td style="width: 40% !important;"> : {{ $consigneeAddr['mobile'] ?? '' }} </td>
    </tr>
</table>

<hr>

    <table class="table">
        <thead>
        <tr>
            <th style="width: 12% !important;">Garden</th>
            <th style="width: 12% !important;">Invoice No</th>
            <th style="width: 8% !important;">Grade</th>
            <th style="width: 8% !important;">Qty</th>
            <th style="width: 10% !important;">Nett Kgs</th>
            <th style="width: 10% !important;">Tare</th>
            @if($showPalletWeight)
                <th style="width: 10% !important;">Plt Wtt</th>
            @endif
            <th style="width: 10% !important;">Gross Kgs</th>
            <th style="width: 10% !important;">REMARKS</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ $shipment->garden }}</td>
            <td>{{ $shipment->blend_number }}</td>
            <td>{{ $shipment->grade }}</td>
            <td>{{ $shipment->packages }}</td>
            <td>{{ number_format($shipment->weight, 2) }}</td>
            <td>{{ number_format($shipment->tare_weight, 2) }}</td>
            @if($showPalletWeight)
                <td>{{ number_format($shipment->pallet_weight, 2) }}</td>
            @endif
            <td>{{ number_format($grossPerContainer, 2) }}</td>
            <td></td>
        </tr>
        <tr class="totals-row">
            <td colspan="2"></td>
            <td><strong>TOTALS</strong></td>
            <td>{{ $shipment->packages }}</td>
            <td>{{ number_format($shipment->weight, 2) }}</td>
            <td>{{ number_format($shipment->tare_weight, 2) }}</td>
            @if($showPalletWeight)
                <td>{{ number_format($shipment->pallet_weight, 2) }}</td>
            @endif
            <td>{{ number_format($grossPerContainer, 2) }}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="{{ $showPalletWeight ? 9 : 8 }}"><br></td>
        </tr>
        </tbody>
    </table>

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
