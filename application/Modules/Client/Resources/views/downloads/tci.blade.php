<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $details->loading_number }}</title>
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
            line-height: 0.9 !important;
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
            font-size: 12px !important;
            /*padding: 5px !important;*/
        }

        .logistics {
            margin-top: 10px;
            font-weight: bold;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            z-index: -1;
        }

        .heading {
            color: green;
            font-size: 13px !important;
            font-weight: bold !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .tfooter {
            font-weight: bold !important;
        }

        .footer {
            font-size: 10px;
            text-align: center;
            position: fixed;
            bottom: 10px;
            width: 100%;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
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
           padding-bottom: 2px !important;
        }
    </style>
</head>
<body>
<div class="company-info">
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">TEA COLLECTION INSTRUCTIONS <hr></div>
<table>
    <tr>
        <td style="width: 80% !important;"> The Go-down Keeper: <strong> {{ $details->warehouse_name }} </strong></td>
        <td style="width: 20% !important;"> TCI Number: <strong>{{ $details->loading_number }}</strong> </td>
    </tr>
    <tr>
        <td style="width: 80% !important;"> Account of: <strong> {{ $details->client_name }} </strong></td>
        <td style="width: 20% !important;"> Date Printed <strong> : {{ \Carbon\Carbon::now()->format('Y-m-d H:s')  }}</strong></td>
    </tr>
    <tr>
        <td colspan="2"> Please arrange to deliver the following; </td>
    </tr>
</table>
<br>
<table class="table">

    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 14% !important;">Garden Name</th>
        <th style="width: 9% !important;">Grade Name</th>
        <th style="width: 8% !important;">DO No</th>
        <th style="width: 9% !important;">Inv No</th>
        <th style="width: 7% !important;">Lot No</th>
        <th style="width: 7% !important;">Sale No</th>
        <th style="width: 7% !important;">Packages</th>
        <th style="width: 9% !important;">Weight</th>
        <th style="width: 9% !important;">Prompt Dte</th>
        <th style="width: 8% !important;">Pkgs Rec/d</th>
        <th style="width: 10% !important;">Weight Rec/d</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $totalPackects = 0;
    $totalWeights = 0;
    $receivedPackages = 0;
    $receivedWeight = 0;
    ?>
        @foreach($orders as $order)
            <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->lot_number }}</td>
            <td>{{ $order->sale_number }}</td>
            <td>{{ $order->packet }}</td>
            <td>{{ number_format($order->weight, 2) }}</td>
            <td>{{ $order->prompt_date }}</td>
            <td>{{ number_format($order->total_pallets, 0) }}</td>
            <td>{{ number_format($order->total_weight, 2) }}</td>
            </tr>
            <?php
                $totalPackects += $order->packet;
                $totalWeights += $order->weight;
                $receivedPackages += $order->total_pallets;
                $receivedWeight += $order->total_weight;
            ?>
        @endforeach
    </tbody>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="7" style="border: none !important;"></td>
        <td>{{ $totalPackects }}</td>
        <td>{{ number_format($totalWeights, 2) }}</td>
        <td></td>
        <td>{{ number_format($receivedPackages, 0) }}</td>
        <td>{{ number_format($receivedWeight, 2) }}</td>
    </tr>

    {{--<tr class="tfooter">
        <td colspan="7" style="border: 0px !important;"></td>
        <td>{{ $totalPackects }}</td>
        <td>{{ number_format($totalWeights, 2) }}</td>
        <td></td>
        <td>{{ number_format($receivedPackages, 0) }}</td>
        <td>{{ number_format($receivedWeight, 2) }}</td>
    </tr>--}}
</table>
<br>
<p><strong>Remarks</strong> : ____________________________________________________________________________________________________________________________________________________________________ </p>
<br>
<table class="table2">
    <tr>
        <td colspan="2" style="width: 33% !important;"><i class="logistics">DRIVER DETAILS</i></td>
        <td colspan="2" style="width: 34% !important;"><i class="logistics">TRANSPORTER DETAILS</i></td>
        <td colspan="2" style="width: 33% !important;"><i class="logistics">DELIVERY DETAILS</i></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Name</td>
        <td style="width: 23% !important;">{{ $details->driver_name }} <hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Transporter</td>
        <td style="width: 24% !important;"> {{ $details->transporter_name }} <hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Packmac Station </td>
        <td style="width: 23% !important;"> {{ $details->station_name }} <hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver IDNO</td>
        <td style="width: 23% !important;">{{ $details->id_number }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Vehicle Reg</td>
        <td style="width: 24% !important;">{{ $details->registration }}  <hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Received By</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Phone:</td>
        <td style="width: 23% !important;">{{ $details->phone }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 24% !important;"></td>
        <td style="width: 10% !important;">Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Signature</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 24% !important;"></td>
        <td style="width: 10% !important;">Signature</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 24% !important;"></td>
        <td style="width: 10% !important;"></td>
        <td style="width: 23% !important;"></td>
    </tr>
</table>
</body>
</html>
