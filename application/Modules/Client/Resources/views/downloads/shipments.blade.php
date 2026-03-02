<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHIPMENT REPORT</title>
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
            height: 50px !important;
            width: 50px !important;
            padding-left: 0px !important;
        }
    </style>
</head>
<body>
<div class="company-info">
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">SHIPMENTS REPORT {{ $period }} <hr></div>;
<br>
<table class="table">
    <thead>
    <tr>
        <th style="width: 3% !important;">#</th>
        <th style="width: 8% !important;">SI Number</th>
        <th style="width: 3% !important;">Type</th>
        <th style="width: 11% !important;">Client Name</th>
        <th style="width: 10% !important;">Shipping Agent</th>
        <th style="width: 8% !important;">Warehouse</th>
        <th style="width: 8% !important;">Destination</th>
        <th style="width: 10% !important;">Transporter</th>
        <th style="width: 15% !important;">Consignee</th>
        <th style="width: 11% !important;">Vessel Name</th>
        <th style="width: 8% !important;">Shipping Mark</th>
        <th style="width: 6% !important;">Container</th>
        <th style="width: 4% !important;">Pckgs</th>
        <th style="width: 6% !important;">Weight</th>
        <th style="width: 6% !important;">Date Ship'd</th>
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
            <td>{{ $order->shipping_number }}</td>
            <td>{{ $order->type }}</td>
            <td>{{ $order->client_name }}</td>
            <td>{{ $order->agent_name }}</td>
            <td>{{ $order->station_name }}</td>
            <td>{{ $order->port_name }}</td>
            <td>{{ $order->transporter_name }}</td>
            <td>{{ $order->consignee }}</td>
            <td>{{ $order->vessel_name }}</td>
            <td>{{ $order->shipping_mark }}</td>
            <td>{{ $order->total_containers }} * {{ $order->container_size == 1 ? '20 FT' : ($order->container_size == 2 ? '40 FT' : '40 FTHC') }}</td>
            <td>{{ number_format($order->output_packages, 0, '',',') }}</td>
            <td>{{ number_format($order->output_weight, 2) }}</td>
            <td>{{ $order->shipment_date == null ? 'Pending' : Carbon\Carbon::createFromTimestamp($order->shipment_date)->format('Y-m-d') }}</td>
        </tr>
            <?php
            $totalPackets += $order->output_packages;
            $netWeight += $order->output_weight;
            ?>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="8" style="border: none !important;"></td>
        <td>{{ number_format($totalPackets, 0) }}</td>
        <td>{{ number_format($netWeight, 2) }}</td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
