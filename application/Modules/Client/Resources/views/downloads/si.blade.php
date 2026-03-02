<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sheet->shipping_number }}</title>
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
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">STRAIGHT LINE REPORT<hr></div>
<table>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> CLIENT NAME </td>
        <td style="width: 35% !important;"> :  {{ $sheet->client_name }} </td>
        <td style="width: 20%!important;"> CONTAINER NO. </td>
        <td style="width: 30% !important;"> : {{ $sheet->container_number }} ({{ $sheet->container_size == 1 ? '20 FT' : ($sheet->container_size == 2 ? '40 FT' : '40 FTHC') }}) </td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> SHIPPING NO. </td>
        <td style="width: 35% !important;"> : {{ $sheet->shipping_number }} </td>
        <td style="width: 20% !important;"> SHIPP. AGENT </td>
        <td style="width: 30% !important;"> :  {{ $sheet->agent_name }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> DESTINATION </td>
        <td style="width: 35% !important;"> :  {{ $sheet->port_name }} </td>
        <td style="width: 20% !important;"> TRANSPORTER </td>
        <td style="width: 30% !important;"> : {{ $sheet->transporter_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> CONSIGNEE </td>
        <td style="width: 35% !important;"> :  {{ $sheet->consignee }} </td>
        <td style="width: 20% !important;"> TRUCK REG. </td>
        <td style="width: 30% !important;"> :  {{ $sheet->registration }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SHIPPING MARK </td>
        <td style="width: 35% !important;"> :  {{ $sheet->shipping_mark }} </td>
        <td style="width: 20% !important;"> DRIVER NAME </td>
        <td style="width: 30% !important;"> : {{ $sheet->driver_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> VESEL NAME </td>
        <td style="width: 35% !important;"> :  {{ $sheet->vessel_name }} </td>
        <td style="width: 20% !important;"> DRIVER TEL. </td>
        <td style="width: 30% !important;"> :  {{ $sheet->phone }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SEAL NUMBER </td>
        <td style="width: 35% !important;"> :  {{ $sheet->seal_number }} </td>
        <td style="width: 20% !important;"> STATUS </td>
        <td style="width: 30% !important;"> : {{ $sheet->ship_date == null ? 'Being Processed' : 'Shipped On :'. Carbon\Carbon::createFromTimestamp($sheet->ship_date)->toDateString() }}  </td>
    </tr>
</table>
<br>
<table>
    <tr>
        <td style="width: 100% !important;" colspan="4"> SHIPPING INSTRUCTION :  {{ $sheet->shipping_instructions }} </td>
    </tr>
</table>
<hr>
<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 15% !important;">Garden Name</th>
        <th style="width: 10% !important;">Grade</th>
        <th style="width: 10% !important;">Sale Number</th>
        <th style="width: 13% !important;">Inv Number</th>
        <th style="width: 7% !important;">Pcks</th>
        <th style="width: 11% !important;">Weight</th>
        <th style="width: 9% !important;">Aging Date</th>
        <th style="width: 25% !important;">Producer Whs</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $receivedPackages = 0;
    $netWeights = 0;
    ?>
        @foreach($shippings as $order)
            <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ $order->sale_number }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->shipped_packages }}</td>
            <td>{{ number_format(str_replace([',', '.00'], '', $order->shipped_weight), 2) }}</td>
            <td>
                {{
                        app('\App\Services\AppClass')->getAgingDays(
                            $order->delivery_id,
                            $order->ship_date
                        )
                    }} days
            </td>
                <td>{{ $order->warehouse_name }}</td>
            </tr>
            <?php
                $receivedPackages += $order->shipped_packages;
                $netWeights += floatval(str_replace([',', '.00'], '', $order->shipped_weight));
            ?>
        @endforeach
    </tbody>-->
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="5" style="border: none !important;"></td>
        <td>{{ number_format($receivedPackages) }}</td>
        <td>{{ number_format($netWeights, 2) }}</td>
    </tr>
</table>
<br>

<table class="table2">
    <tr>
        <td colspan="2" style="width: 20% !important;"><i class="logistics">DRIVER DETAILS</i></td>
        <td colspan="2" style="width: 20% !important;"><i class="logistics">OFFICER DETAILS</i></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Name</td>
        <td style="width: 23% !important;">{{ $sheet->driver_name }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Prepared By </td>
        <td style="width: 23% !important;"> {{ $staffName }} <hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Signature</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Signature</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
</table>
<p><i><strong>Printed On:</strong> {{ $date }}</i></p>
</body>
</html>
