<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $details->delivery_number }}</title>
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
<div class="header">INTERNAL TRANSFER NOTE <hr></div>
<table>
    <tr>
        <td style="width: 70% !important;"> ACCOUNT OF : <strong> {{ $details->client_name }} </strong></td>
        <td style="width: 30% !important;"> DELIVERY NUMBER : <strong>{{ $details->delivery_number }}</strong> </td>
    </tr>
    <tr>
        <td style="width: 70% !important;"> Please receive the below listed goods from <strong> {{ $details->station_name }} </strong></td>
        <td style="width: 30% !important;"> DATE PRINTED : <strong> : {{ Carbon\Carbon::today()->format('Y-m-d') }}</strong></td>
    </tr>
</table>
<br>
<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 16% !important;">Garden Name</th>
        <th style="width: 10% !important;">Grade</th>
        <th style="width: 13% !important;">DO No</th>
        <th style="width: 14% !important;">Inv No</th>
        <th style="width: 13% !important;">Lot No</th>
        <th style="width: 12% !important;">Sale No</th>
        <th style="width: 6% !important;">Pkgs</th>
        <th style="width: 9% !important;">Weight</th>
        <th style="width: 11% !important;">Date Recv'd</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $receivedPackages = 0;
    $netWeights = 0;
    ?>
        @foreach($orders as $order)
            <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->garden_name ?? $order->garden }}</td>
            <td>{{ $order->grade_name ?? $order->grade }}</td>
            <td>{{ $order->order_number ?? $order->blend_number }}</td>
            <td>{{ $order->invoice_number ?? $order->blend_number }}</td>
            <td>{{ $order->lot_number ?? $order->blend_number }}</td>
            <td>{{ $order->sale_number ?? ucfirst(strtolower($order->type)) }}</td>
            <td>{{ $order->requested_palettes }}</td>
            <td>{{ number_format($order->requested_weight, 2) }}</td>
            <td>{{ $order->date_received ? Carbon\Carbon::createFromTimestamp($order->date_received)->format('Y-m-d') : Carbon\Carbon::parse($order->blend_date)->format('Y-m-d') }}</td>
            </tr>
            <?php
                $receivedPackages += $order->requested_palettes;
                $netWeights += $order->requested_weight;
            ?>
        @endforeach
    </tbody>-->
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="7" style="border: none !important;"></td>
        <td>{{ $receivedPackages }}</td>
        <td>{{ number_format($netWeights, 2) }}</td>
        <td></td>
    </tr>
</table>
<br>
<p><strong>Remarks</strong> : ____________________________________________________________________________________________________________________________ </p>
<br>
<table class="table2">
    <tr>
        <td colspan="2" style="width: 50% !important;"><i class="logistics">DRIVER DETAILS</i></td>
        <td colspan="2" style="width: 50% !important;"><i class="logistics">DELIVERY DETAILS</i></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Transporter</td>
        <td style="width: 23% !important;">{{ $details->transporter_name }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Destination</td>
        <td style="width: 23% !important;"> {{ $details->destination_name }} <hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Reg. Number</td>
        <td style="width: 23% !important;">{{ $details->registration }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Prepared By </td>
        <td style="width: 23% !important;"> {{ $user }} <hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Name</td>
        <td style="width: 23% !important;">{{ $details->driver_name }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Driver Phone:</td>
        <td style="width: 23% !important;">{{ $details->phone }}<hr class="dotted-hr"></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver IDNO</td>
        <td style="width: 23% !important;">{{ $details->id_number }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Signature & Date</td>
        <td style="width: 23% !important;"><hr class="dotted-hr"></td>
    </tr>
    <tr>
</table>
<table class="table2">
    @foreach($approvals as $key => $approval)
            <?php
            $image = $approval->signature;

            if (empty($image)) {
                if ($key === 0) {
                    $signatory = $signatories->first(function ($s) {
                        return auth()->user()->hasPermission('transfer.internal.approve') == true || is_string($s->department_name) && stripos($s->department_name, 'Stock') !== false;
                    });

                    dd($signatory);
                    $image = $signatory?->signature;
                } elseif ($key === 1) {
                    $signatory = $signatories->first(function ($s) {
                        return is_string($s->department_name) && stripos($s->department_name, 'Finance') !== false;
                    });
                    $image = $signatory?->signature;
                }
            }
            ?>
        <tr>
            <td style="width: 10% !important;">Name : </td>
            <td>{{ $approval->full_name }}</td>
            <td style="width: 10% !important;">Signature : </td>
            <td>
                @if(!empty($image))
                    <img src="{{ url('Files/uploads/signatures/'.$image) }}" style="max-height:50px; width:auto; object-fit:contain;">
                @else
                    <span>No signature</span>
                @endif
            </td>
            <td style="width: 10% !important;">Date : </td>
            <td>{{ \Carbon\Carbon::parse($approval->approval_date)->format('d-m-Y') }}</td>
        </tr>
    @endforeach

</table>
</body>
</html>
