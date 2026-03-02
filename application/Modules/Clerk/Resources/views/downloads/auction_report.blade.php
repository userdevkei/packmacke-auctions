<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Sale Number{{ $sale }}</title>
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
            padding: 6px;
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
<div class="header">WEIGHT NOTES FOR AUCTION TEAS SALE {{ $sale }}<hr></div>
<br>
<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 20% !important;">Buyer Name</th>
        <th style="width: 15% !important;">Producer Warehouse</th>
        <th style="width: 9% !important;">Garden Name</th>
        <th style="width: 6% !important;">Grade</th>
        <th style="width: 8% !important;">Inv Number</th>
        <th style="width: 5% !important;">Pkgs</th>
        <th style="width: 6% !important;">Net Weight</th>
        <th style="width: 10% !important;">Warrant NO</th>
        <th style="width: 8% !important;">Sale Date</th>
        <th style="width: 8% !important;">Prompt Date</th>
        <th style="width: 8% !important;">Release Date</th>
        <th style="width: 7% !important;">Aging Date</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $receivedPackages = 0;
    $unitNetWeight = 0;
    $unitGrossWeight = 0;
    $netWeight = 0;
    $grossWeight = 0;
    ?>
        @foreach($teas as $order)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $order->buyer }}</td>
                <td>{{ $order->warehouse_name }}</td>
                <td>{{ $order->garden_name }}</td>
                <td>{{ $order->grade_name }}</td>
                <td>{{ $order->invoice_number }}</td>
                <td>{{ $order->packet }}</td>
                <td>{{ number_format($order->current_weight, 2) }}</td>
                <td>{{ $order->warrant_number }}</td>
                <td>{{ $order->sale_date }}</td>
                <td>{{ $order->prompt_date }}</td>
                <td>{{ $order->release_date }}</td>
                <td>
                    {{
                        app('\App\Services\AppClass')->getPromptAgingDays(
                            $order->delivery_id,
                            strtotime($order->release_date)
                        )
                    }} days
                </td>
            </tr>
            <?php
                $receivedPackages += $order->packet;
                $netWeight += floatval(str_replace([',', '.00'], '', $order->current_weight));
            ?>
        @endforeach
    </tbody>-->
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="6" style="border: none !important;"></td>
        <td>{{ number_format($receivedPackages) }}</td>
        <td>{{ number_format($netWeight, 2) }}</td>
    </tr>
</table>
<br>
<p><i><strong>Printed On:</strong> {{ now()->format('d M Y') }} <b>By</b> {{ auth()->user()->user->surname }} {{ auth()->user()->user->first_name }}</i></p>
</body>
</html>
