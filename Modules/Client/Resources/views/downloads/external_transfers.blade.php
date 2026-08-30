<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXTERNAL TRANSFERS</title>
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
            font-size: 11px !important;
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
        <th style="width: 10% !important;">Delivery Number</th>
        <th style="width: 10% !important;">Order Number</th>
        <th style="width: 10% !important;">Invoice Number</th>
        <th style="width: 9% !important;">Lot Number</th>
        <th style="width: 10% !important;">Garden Name</th>
        <th style="width: 7% !important;">Grade</th>
        <th style="width: 5% !important;">Packages</th>
        <th style="width: 7% !important;">Weight</th>
        <th style="width: 20% !important;">Destination</th>
        <th style="width: 10% !important;">Date Transferred</th>
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
            <td>{{ $order->delivery_number }}</td>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->lot_number }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ number_format($order->transferred_palettes) }}</td>
            <td>{{ number_format($order->transferred_weight, 2) }}</td>
            <td>{{ $order->warehouse_name }}</td>
            <td>{{ $order->status <= 2 ? 'Pending Release' : Carbon\Carbon::parse($order->updated_at)->format('Y-m-d') }}</td>
        </tr>
            <?php
            $totalPackets += $order->transferred_palettes;
            $netWeight += $order->transferred_weight;
            ?>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="7" style="border: none !important;"></td>
        <td>{{ number_format($totalPackets, 0) }}</td>
        <td>{{ number_format($netWeight, 2) }}</td>
        <td></td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
