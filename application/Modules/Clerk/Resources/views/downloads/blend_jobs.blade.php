<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLEND JOBS</title>
    <style>
        body {
            font-size: 12px;
            line-height: 1.0;
            padding: 0px !important;
            margin: 0px !important;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .company-info {
            text-align: center;
            margin: 0px !important;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table, .table th, .table td {
            border: 1px solid black;
        }
        .table th, .table td {
            padding: 5px;
            text-align: left;
            font-size: 9px !important;
        }
        .heading {
            color: green;
            font-size: 12px !important;
            font-weight: bold !important;
        }
        .tfooter {
            font-weight: bold !important;
        }
        .logo {
            height: 70px !important;
            width: 70px !important;
            padding-left: 0px !important;
        }
    </style>
</head>
<body>
<table>
    <tr>
        <td style="width: 80% !important;"> CLIENT NAME: <strong> {{ $clientName }} </strong></td>
    </tr>
</table>
<br>

<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 9% !important;">SI Number</th>
        <th style="width: 12% !important;">Shipping Agent</th>
        <th style="width: 11% !important;">Destination</th>
        <th style="width: 18% !important;">Consignee</th>
        <th style="width: 18% !important;">Vessel Name</th>
        <th style="width: 9% !important;">Shipping Mark</th>
        <th style="width: 5% !important;">Pckgs</th>
        <th style="width: 7% !important;">Weight</th>
        <th style="width: 7% !important;">Date Ship'd</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $totalPackets = 0;
    $netWeight = 0;
    $grossWeight = 0;
    ?>
    @foreach($orders as $order)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->blend_number }}</td>
            <td>{{ $order->agent_name }}</td>
            <td>{{ $order->port_name }}</td>
            <td>{{ $order->consignee }}</td>
            <td>{{ $order->vessel_name }}</td>
            <td>{{ $order->shipping_mark }}</td>
            <td>{{ number_format($order->blendedPackages) }}</td>
            <td>{{ number_format($order->blendedWeight, 2) }}</td>
            <td>{{ $order->blend_shipped == null ? 'Pending' : Carbon\Carbon::createFromTimestamp($order->blend_shipped)->format('Y-m-d') }}</td>
        </tr>
            <?php
            $totalPackets += $order->blendedPackages;
            $netWeight += $order->blendedWeight;
            ?>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="7" style="border: none !important;"></td>
        <td>{{ number_format($totalPackets, 0) }}</td>
        <td>{{ number_format($netWeight, 2) }}</td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
