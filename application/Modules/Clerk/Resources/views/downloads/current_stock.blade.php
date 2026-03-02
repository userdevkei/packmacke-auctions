<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CURRENT STOCK</title>
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
        <th style="width: 3% !important;">#</th>
        <th style="width: 7% !important;">Inv No</th>
        <th style="width: 9% !important;">Garden</th>
        <th style="width: 5% !important;">Grade</th>
        <th style="width: 5% !important;">Origin</th>
        <th style="width: 5% !important;">Sale No</th>
        <th style="width: 4% !important;">Gross</th>
        <th style="width: 4% !important;">Tare</th>
        <th style="width: 5% !important;">Net</th>
        <th style="width: 5% !important;">Pkgs</th>
        <th style="width: 6% !important;">Nt Wght</th>
        <th style="width: 6% !important;">Gr Wght</th>
        <th style="width: 5% !important;">Alloc. Pkgs</th>
        <th style="width: 6% !important;">Alloc. Wght</th>
        <th style="width: 7% !important;">TCI NO</th>
        <th style="width: 6% !important;">DO. NO</th>
        <th style="width: 6% !important;">Date Rcvd</th>
        <th style="width: 15% !important;">Producer Whs</th>
        <th style="width: 6% !important;">Aging Date</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $totalPackets = 0;
    $netWeight = 0;
    $grossWeight = 0;
    $totalAllocatedPackages = 0;
    $totalAllocatedWeight = 0;
    ?>
    @foreach($orders as $order)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ $order->tea_type ?? 'Local' }}</td>
            <td>{{ $order->sale_number }}</td>
            <td>{{ number_format(floatval($order->current_weight/$order->current_stock), 2) }}</td>
            <td>{{ number_format(floatval($order->package_tare * $order->current_stock + $order->pallet_weight), 2) }}</td>
            <td>{{ number_format(floatval($order->current_weight/$order->current_stock), 2) }}</td>
            <td>{{ $order->current_stock }}</td>
            <td>{{ number_format($order->current_weight, 2) }}</td>
            <td>{{ number_format(floatval($order->current_weight + ($order->package_tare * $order->current_stock + $order->pallet_weight)), 2) }}</td>
            <td>{{ number_format($order->allocated_packages, 0) }}</td>
            <td>{{ number_format($order->allocated_weight, 2) }}</td>
            <td>{{ $order->loading_number }}</td>
            <td>{{ $order->order_number }}</td>
            <td>{{ \Carbon\Carbon::createFromTimestamp($order->date_received)->format('d-m-y') }}</td>
            <td>{{ $order->warehouse_name }}</td>
            <td>
                {{
                        app('\App\Services\AppClass')->getAgingDays(
                            $order->delivery_id,
                            time()
                        )
                    }} days
            </td>
        </tr>
            <?php
            $totalPackets += $order->current_stock;
            $netWeight += $order->current_weight;
            $grossWeight += floatval($order->current_weight + ($order->package_tare * $order->current_stock + $order->pallet_weight));
            $totalAllocatedPackages += floatval($order->allocated_packages);
            $totalAllocatedWeight += floatval($order->allocated_weight);
            ?>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="9" style="border: none !important;"></td>
        <td>{{ number_format($totalPackets, 0) }}</td>
        <td>{{ number_format($netWeight, 2) }}</td>
        <td>{{ number_format($grossWeight, 2) }}</td>
        <td>{{ number_format($totalAllocatedPackages, 0) }}</td>
        <td>{{ number_format($totalAllocatedWeight, 2) }}</td>
        <td colspan="4"></td>
    </tr>
    </tfoot>
</table>
<h3>Total Packages (In stock + Allocated) : {{ number_format($totalAllocatedPackages + $totalPackets, 0) }} </h3>
<h3>Total Net Weight (In stock + Allocated) : {{ number_format($totalAllocatedWeight + $netWeight, 2) }} Kgs</h3>
</body>
</html>
