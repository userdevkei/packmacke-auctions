<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $detail->order_number }}</title>
    <style>
        body {
            /*font-family: "Times New Roman", sans-serif;*/
            font-size: 12px;
            line-height: 0.9; /* Adjust line spacing */
            padding: 0 !important;
            margin: 0 !important;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
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
            padding: 4px;
            text-align: left;
            font-size: 11px !important;
            /*padding: 5px !important;*/
        }

        .logistics {
            margin-top: 20px;
            font-weight: bold;
        }

        .heading {
            color: green;
            font-size: 12px !important;
            font-weight: bold !important;
        }
        .tfooter {
            font-weight: bold !important;
        }

        .footer-content .left {
            text-align: left;
            width: 33%;
        }

        .footer-content .center {
            text-align: center;
            width: 33%;
        }

        .footer-content .right {
            text-align: right;
            width: 33%;
        }
        .logo {
            height: 50px !important;
            width: 50px !important;
            padding: 0 !important;
        }
    </style>
</head>
<body>
<div class="company-info">
    {{-- <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span> --}}
    <span>
    <img class="logo" src="{{ asset('assets/img/favicons/icon.png') }}" alt="Logo">
</span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">TALLY OF RECEIVED GOODS <hr></div>
<table>
    <tr>
        <td style="width: 70% !important;"> ACCOUNT OF : <strong> {{ $detail->client_name }} </strong></td>
        <td style="width: 30% !important;"> ORDER NUMBER : <strong>{{ $detail->order_number }}</strong> </td>
    </tr>
    <tr>
        <td style="width: 70% !important;"> RECEIVED AT: <strong> {{ $detail->station_name }} </strong></td>
        <td style="width: 30% !important;"> DATE RECEIVED : <strong> : {{ Carbon\Carbon::createFromTimestamp($detail->date_received)->format('Y-m-d') }}</strong></td>
    </tr>
</table>
<br>
<table class="table">

    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 14% !important;">Garden Name</th>
        <th style="width: 13% !important;">Grade Name</th>
        <th style="width: 10% !important;">Inv No</th>
        <th style="width: 10% !important;">Packages</th>
        <th style="width: 10% !important;">Gross Weight</th>
        <th style="width: 10% !important;">Net Weight</th>
        <th style="width: 14% !important;">Producer Warehouse</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $receivedPackages = 0;
    $grossWeight = 0;
    $netWeights = 0;
    ?>
        @foreach($orders as $order)
            <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->total_pallets }}</td>
            <td>{{ number_format($order->total_weight, 2) }}</td>
            <td>{{ number_format($order->net_weight, 2) }}</td>
            <td>{{ $order->warehouse_name }}</td>
            </tr>
            <?php
                $receivedPackages += $order->total_pallets;
                $grossWeight += $order->total_weight;
                $netWeights += $order->net_weight;

            ?>
        @endforeach
    </tbody>-->
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="4" style="border: none !important;"></td>
        <td>{{ $receivedPackages }}</td>
        <td>{{ number_format($grossWeight, 2) }}</td>
        <td>{{ number_format($netWeights, 2) }}</td>
        <td></td>
    </tr>
</table>
<br>
<i>Printed On: {{ \Carbon\Carbon::today()->format('Y-m-d') }} </i>
<br>
<p><strong>Remarks</strong> : ____________________________________________________________________________________________________________________________ </p>
<br>
<table class="table2">
    <tr>
        <td colspan="2" style="width: 50% !important;"><i class="logistics">TRANSPORTER DETAILS</i></td>
        <td colspan="2" style="width: 50% !important;"><i class="logistics">DELIVERY DETAILS</i></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Transporter Name</td>
        <td style="width: 23% !important;">{{ $detail->transporter_name }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Received By </td>
        <td style="width: 23% !important;"> {{ $staffName }} <hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Name</td>
        <td style="width: 23% !important;">{{ $detail->driver_name }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver IDNO</td>
        <td style="width: 23% !important;">{{ $detail->id_number }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Signature</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Phone:</td>
        <td style="width: 23% !important;">{{ $detail->phone }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 24% !important;"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Signature</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 24% !important;"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 24% !important;"></td>
    </tr>
</table>
</body>
</html>
