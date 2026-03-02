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
        .subtotal-row {
            font-weight: bold !important;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
<div class="company-info">
    <h1 class="heading">{{ $sheet->client_name }}</h1>
    <p>{{ $sheet->client_address }}</p>
</div>
<div class="header">PACKING LIST<hr></div>
<table>
    <tr>
        <td style="width: 15% !important; font-weight: bold !important;"> INVOICE NUMBER </td>
        <td style="width: 40% !important;"> :  {{ $sheet->shipping_number }} </td>
        <td style="width: 20%!important;"> PORT OF LOADING </td>
        <td style="width: 25% !important;"> : MOMBASA, KENYA </td>
    </tr>
    <tr>
        <td style="width: 15%!important;"> CONSIGNEE </td>
        <td style="width: 40% !important;"> : {{ $sheet->consignee }} </td>
        <td style="width: 20% !important;"> DESTINATION PORT </td>
        <td style="width: 25% !important;"> :  {{ $sheet->port_name }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;">  </td>
        <td style="width: 40% !important;"> :  {{ $sheet->address['box'] ?? '' }} </td>
        <td style="width: 20% !important;"> BOOKING NUMBER</td>
        <td style="width: 25% !important;"> : {{ $sheet->booking_number }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;">  </td>
        <td style="width: 40% !important;"> :  {{ $sheet?->address['address'] ?? '' }} </td>
        <td style="width: 20% !important;"> DATE </td>
        <td style="width: 25% !important;"> : {{ $sheet->ship_date !== null ? Carbon\Carbon::createFromTimestamp($sheet->ship_date)->format('d/m/Y') : '' }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> </td>
        <td style="width: 40% !important;"> :  {{ $sheet?->address['state'] ?? '' }} </td>
        <td style="width: 20% !important;"> SHIPPING MARKS </td>
        <td style="width: 25% !important;"> : {{ $sheet->shipping_mark }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> </td>
        <td style="width: 40% !important;"> :  {{ $sheet?->address['mobile'] ?? '' }} </td>
        <td style="width: 20% !important;"> OCEAN VESSEL </td>
        <td style="width: 25% !important;"> :  {{ $sheet->vessel_name }} </td>
    </tr>
</table>

<hr>
<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 6% !important;">Lot No.</th>
        <th style="width: 12% !important;">Garden Mark</th>
        <th style="width: 12% !important;">Invoice Number</th>
        <th style="width: 7% !important;">Grade</th>
        <th style="width: 7% !important;">No. of Packages</th>
        <th style="width: 9% !important;">Net WT (Kgs)</th>
        <th style="width: 8% !important;">Tare WT (Kgs)</th>
        <th style="width: 8% !important;">Pallet WT (Kgs)</th>
        <th style="width: 8% !important;">Gross WT (Kgs)</th>
        <th style="width: 8% !important;">Prod. Date</th>
        <th style="width: 8% !important;">Expiry Date</th>
        <th style="width: 8% !important;">Pallet Height (CM)</th>
        <th style="width: 10% !important;">Container Number</th>
        <th style="width: 10% !important;">Seal Number</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $receivedPackages = 0;
    $netWeights = 0;
    $grossWeight = 0;
    $totalPalletW = 0;
    $totalTare = 0;

    // Group shippings by container and seal number
    $groupedShippings = $shippings->groupBy(function($item) {
        return $item->container_number . '|' . $item->seal_number;
    });

    $counter = 1;
    ?>
    @foreach($groupedShippings as $groupKey => $group)
            <?php
            $subtotalPackages = 0;
            $subtotalNetWeight = 0;
            $subtotalTareWeight = 0;
            $subtotalPalletWeight = 0;
            $subtotalGrossWeight = 0;
            ?>
        @foreach($group as $order)
            <tr>
                <td>{{ $counter++ }}</td>
                <td>{{ $order->lot_number }}</td>
                <td>{{ $order->garden_name }}</td>
                <td>{{ $order->invoice_number }}</td>
                <td>{{ $order->grade_name }}</td>
                <td>{{ $order->shipped_packages }}</td>
                <td>{{ number_format(str_replace([',', '.00'], '', $order->shipped_weight), 2) }}</td>
                <td>{{ $order->package_tare * $order->shipped_packages }}</td>
                <td>{{ $order->pallet_weight }}</td>
                <td>
                    {{
                        number_format(
                            (float) str_replace([',', '.00'], '', $order->shipped_weight)
                            + ((float) $order->package_tare * (int) $order->shipped_packages)
                            + (float) $order->pallet_weight,
                            2
                        )
                    }}
                </td>

                <td>{{ $order->production_date }}</td>
                <td>{{ $order->expiry_date }}</td>
                <td>{{ $order->height ?? 0 }}</td>
                <td>{{ $order->container_number }}</td>
                <td>{{ $order->seal_number }}</td>
                <?php
                $netWeight = floatval(str_replace([',', '.00'], '', $order->shipped_weight));
                $tareWeight = (float) $order->package_tare * (int) $order->shipped_packages;
                $palletWeight = (float) $order->pallet_weight;
                $currentGrossWeight = $netWeight + $tareWeight + $palletWeight;

                $receivedPackages += $order->shipped_packages;
                $netWeights += $netWeight;
                $totalTare += $tareWeight;
                $grossWeight += $currentGrossWeight;
                $totalPalletW += $palletWeight;

                $subtotalPackages += $order->shipped_packages;
                $subtotalNetWeight += $netWeight;
                $subtotalTareWeight += $tareWeight;
                $subtotalPalletWeight += $palletWeight;
                $subtotalGrossWeight += $currentGrossWeight;
                ?>
        @endforeach

        <tr class="subtotal-row">
            <td colspan="5" style="text-align: right;"></td>
            <td>{{ number_format($subtotalPackages) }}</td>
            <td>{{ number_format($subtotalNetWeight, 2) }}</td>
            <td>{{ number_format($subtotalTareWeight, 2) }}</td>
            <td>{{ number_format($subtotalPalletWeight, 2) }}</td>
            <td>{{ number_format($subtotalGrossWeight, 2) }}</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td colspan="15">.</td>
        </tr>
    @endforeach
    </tbody>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="5" style="border: none !important;"></td>
        <td>{{ number_format($receivedPackages) }}</td>
        <td>{{ number_format($netWeights, 2) }}</td>
        <td>{{ number_format($totalTare ,2) }}</td>
        <td>{{ number_format($totalPalletW ,2) }}</td>
        <td>{{ number_format($grossWeight ,2) }}</td>
    </tr>
</table>
<br>
<br>
<table class="table2">
    <tr>
        <td style="width: 8% !important;">Prepared By</td>
        <td style="width: 38% !important;">{{ $sheet->first_name.' '.$sheet->surname }}<hr class="dotted-hr"></td>
        <td style="width: 4% !important;"></td>
        <td style="width: 8% !important;">Checked By </td>
        <td style="width: 40% !important;"> {{ $staffName }} <hr class="dotted-hr"></td>
    </tr>
</table>
<p><i><strong>Printed On:</strong> {{ $date }}</i></p>
</body>
</html>
