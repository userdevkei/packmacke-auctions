<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEA COLLECTION REPORT</title>
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
<br>
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
        <th style="width: 6% !important;">Del. Type</th>
        <th style="width: 8% !important;">Invoice #</th>
        <th style="width: 11% !important;">Garden</th>
        <th style="width: 7% !important;">Grade</th>
        <th style="width: 7% !important;">DO #</th>
        <th style="width: 7% !important;">Lot #</th>
        <th style="width: 7% !important;">Sale #</th>
        <th style="width: 6% !important;">Packages</th>
        <th style="width: 7% !important;">Weight</th>
        <th style="width: 23% !important;">Producer Whs</th>
        <th style="width: 9% !important;">Status</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $totalPackets = 0;
    $netWeight = 0;
    ?>
    @foreach($orders as $order)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->delivery_type == 1 ? 'DO Entry' : 'Direct Del' }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->lot_number }}</td>
            <td>{{ $order->sale_number }}</td>
            <td>{{ number_format($order->packet) }}</td>
            <td>{{ number_format(str_replace([',', '.00'], '', $order->weight), 2) }}</td>
            <td>{{ $order->warehouse_name }}</td>
            <td>{{ $order->load_status == null || $order->load_status == 1 ? 'Under Collection' : 'Collected' }}</td>
        </tr>
            <?php
            $totalPackets += $order->packet;
            $netWeight += str_replace([',', '.00'], '', $order->weight);
            ?>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="8" style="border: none !important;"></td>
        <td>{{ number_format($totalPackets, 0) }}</td>
        <td>{{ number_format($netWeight, 2) }}</td>
        <td></td>
        <td></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
