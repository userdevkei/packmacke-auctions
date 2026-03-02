<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sheet->blend_number }}</title>
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
<div class="header">BLEND SHEET REPORT<hr></div>
<table>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> CLIENT NAME </td>
        <td style="width: 35% !important;"> :  {{ $sheet->client_name }} </td>
        <td style="width: 15%!important;"> BLEND NO. </td>
        <td style="width: 35% !important;"> : {{ $sheet->blend_number }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> GARDEN NAME </td>
        <td style="width: 35% !important;"> :  {{ $sheet->garden }} </td>
        <td style="width: 15%!important;"> GRADE NANE </td>
        <td style="width: 35% !important;"> : {{ $sheet->grade }} </td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> CONTRACT NO. </td>
        <td style="width: 35% !important;"> : {{ $sheet->contract }} </td>
        <td style="width: 15% !important;"> SHIPP. AGENT </td>
        <td style="width: 35% !important;"> :  {{ $sheet->agent_name }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> DESTINATION </td>
        <td style="width: 35% !important;"> :  {{ $sheet->port_name }} </td>
        <td style="width: 15% !important;"> TRANSPORTER </td>
        <td style="width: 35% !important;"> : {{ $sheet->transporter_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> CONSIGNEE </td>
        <td style="width: 35% !important;"> :  {{ $sheet->consignee }} </td>
        <td style="width: 15% !important;"> TRUCK REG. </td>
        <td style="width: 35% !important;"> :  {{ $sheet->registration }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SHIPPING MARK </td>
        <td style="width: 35% !important;"> :  {{ $sheet->shipping_mark }} </td>
        <td style="width: 15% !important;"> DRIVER NAME </td>
        <td style="width: 35% !important;"> : {{ $sheet->driver_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> VESEL NAME </td>
        <td style="width: 35% !important;"> :  {{ $sheet->vessel_name }} </td>
        <td style="width: 15% !important;"> DRIVER TEL. </td>
        <td style="width: 35% !important;"> :  {{ $sheet->driver_phone }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SEAL NUMBER </td>
        <td style="width: 35% !important;"> : {{ $sheet->seal_number }} </td>
        <td style="width: 15% !important;"> STATUS </td>
        <td style="width: 35% !important;"> : {{ $sheet->blend_shipped == null ? 'Being Processed' : 'Shipped On :'. Carbon\Carbon::createFromTimestamp($sheet->blend_shipped)->format('d-m-Y') }} </td>
    </tr>
</table>
<br>
<table>
    <tr>
        <td style="width: 100% !important;" colspan="4"> CONTAINER NOS. :  {{ implode(', ', $containers) }} </td>
    </tr>
    <tr>
        <td style="width: 100% !important;" colspan="4"> STANDARD DETAILS :  {{ $sheet->standard_details }} </td>
    </tr>
</table>
<hr>
<table class="table">
    <thead>
    <tr>
        <th style="width: 5% !important;">#</th>
        <th style="width: 17% !important;">Garden Name</th>
        <th style="width: 8% !important;">Grade</th>
        <th style="width: 11% !important;">Sale Number</th>
        <th style="width: 14% !important;">Inv Number</th>
        <th style="width: 6% !important;">Pcks</th>
        <th style="width: 9% !important;">Weight</th>
        <th style="width: 9% !important;">Aging Date</th>
        <th style="width: 23% !important;">Producer Whs</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $receivedPackages = 0;
    $netWeights = 0;
    ?>
        @foreach($teas as $order)
            <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->garden_name ?? $order->garden }}</td>
            <td>{{ $order->grade_name ?? $order->grade }}</td>
            <td>{{ $order->sale_number ?? 'Blend Rem' }}</td>
            <td>{{ $order->invoice_number ?? $order->blend_number }}</td>
            <td>{{ $order->blended_packages }}</td>
            <td>{{ number_format(str_replace([',', '.00'], '', $order->blended_weight), 2) }}</td>
                <td>
                    {{
                        app('\App\Services\AppClass')->getAgingDays(
                            $order->delivery_id,
                            strtotime($order->blend_date)
                        )
                    }} days
                </td>
                <td>{{ $order->warehouse_name }}</td>
            </tr>
            <?php
                $receivedPackages += $order->blended_packages;
                $netWeights += floatval(str_replace([',', '.00'], '', $order->blended_weight));
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
<br>
<table class="table">
    <tr>
        <td>Input Packages</td>
        <td>{{ number_format($sheet->input_packages) }}</td>
        <td>Shipped Packages</td>
        <td>{{ number_format($sheet->output_packages) }}</td>
        <td>Balance Packages</td>
        <td>{{ number_format($balance->balPacks) }}</td>
    </tr>

    <tr>
        <td>Input Weight</td>
        <td>{{ number_format($sheet->input_weight, 2) }}</td>
        <td>Shipped Weight</td>
        <td>{{ number_format($sheet->output_weight, 2) }}</td>
        <td>Balance Weight</td>
        <td>{{ number_format($balance->balWeight, 2) }}</td>
    </tr>

</table>
<br>
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
