<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $details->delivery_number }}</title>
    <style>
        body {
            font-size: 12px;
            line-height: 0.9;
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
            font-size: 12px !important;
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
        .footer-content .left { text-align: left; width: 33%; }
        .footer-content .center { text-align: center; width: 33%; }
        .footer-content .right { text-align: right; width: 33%; }
        .logo {
            height: 50px !important;
            width: 50px !important;
            padding: 0 !important;
        }
        .warning-note {
            text-align: center;
            font-weight: bold;
            font-size: 10px !important;
            padding: 4px 0 !important;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .info-grid td {
            border: none;
            padding: 3px 6px !important;
            font-size: 11px !important;
        }
        .info-grid .label {
            font-weight: bold;
            width: 16%;
        }
        .ack-statement {
            font-weight: bold;
            font-size: 11px !important;
            margin: 22px 0 6px 0;
        }
        .ack-line {
            font-size: 11px !important;
            margin: 0 0 17px 0;
        }
        .dotted-hr {
            border: none;
            border-top: 1px dotted #000;
            margin: 2px 0 0 0;
        }
    </style>
</head>
<body>
<div class="company-info">
    <span>
        <img class="logo" src="{{ asset('assets/img/favicons/icon.png') }}" alt="Logo">
    </span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>

@php
    $type = 'LOCAL DELIVERY NOTE';
@endphp
<div class="header">
    {{ $type }}
    <hr>
</div>

<table style="width:100%; border-collapse:collapse; margin-bottom:4px;">
    <tr>
        <td style="border:none; width:70%;"></td>
        <td style="border:none; width:30%; text-align:right; font-size:10px;">
            <b> PREPARED BY: </b> {{ $by ?? '' }}
        </td>
    </tr>
</table>

<table class="info-grid">
    <tr>
        <td class="label">CLIENT NAME</td>
        <td>{{ $details->buyer_name }}</td>
        <td class="label">DELIVERY NUMBER</td>
        <td>{{ $details->delivery_number }}{{ $details->lot }}</td>
    </tr>
    <tr>
        <td class="label">ACCOUNT OF</td>
        <td>{{ $details->client_name }}</td>
        <td class="label">DATE RELEASED</td>
        <td>{{ !empty($details->release_date) ? \Carbon\Carbon::parse($details->release_date)->format('d-m-Y') : '' }}</td>
    </tr>
    <tr>
        <td class="label">DISPATCH WHS</td>
        <td>{{ $details->station_name ?? '' }}</td>
        <td class="label">DESTINATION WHS</td>
        <td>{{ $details->warehouse_name }}</td>
    </tr>
</table>

<br>
<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 7% !important;">Sale</th>
        <th style="width: 7% !important;">DO Number</th>
        <th style="width: 7% !important;">Lot No</th>
        <th style="width: 7% !important;">Garden</th>
        <th style="width: 7% !important;">Grade</th>
        <th style="width: 7% !important;">Invoice No</th>
        <th style="width: 7% !important;">Packages</th>
        <th style="width: 7% !important;">Net Weight</th>
        <th style="width: 7% !important;">Pallet Weight</th>
        <th style="width: 7% !important;">Gross Weight</th>
        <th style="width: 7% !important;">Arrival Date</th>
    </tr>
    </thead>
    <tbody>
    <?php
        $receivedPackages = 0;
        $netWeights = 0;
        $grossWeight = 0;
        $grossPalletWeight = 0;
    ?>
    @foreach($orders as $order)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $order->sale }}</td>
            <td>{{ $order->do_number }}</td>
            <td>{{ $order->lot_number }}</td>
            <td>{{ $order->garden_name }}</td>
            <td>{{ $order->grade_name }}</td>
            <td>{{ $order->invoice_number }}</td>
            <td>{{ $order->transferred_palettes }}</td>
            <td>{{ number_format($order->transferred_weight, 2) }}</td>
            <td>{{ number_format($order->pallet_weight, 2) }}</td>
            <td>{{ number_format(str_replace(',', '', $order->pallet_weight) + str_replace(',', '', $order->transferred_weight) + $order->transferred_palettes, 2) }}</td>
            <td>
                {{ app('\App\Services\AppClass')->arrivalDate($order->delivery_id) }}
            </td>
        </tr>
            <?php
            $receivedPackages += $order->transferred_palettes;
            $netWeights += $order->transferred_weight;
            $grossWeight += str_replace(',', '', $order->pallet_weight) + str_replace(',', '', $order->transferred_weight) + $order->transferred_palettes;
            $grossPalletWeight += str_replace(',', '', $order->pallet_weight);
            ?>
    @endforeach
    </tbody>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="7" style="border: none !important;"></td>
        <td>{{ $receivedPackages }}</td>
        <td>{{ number_format($netWeights, 2) }}</td>
        <td>{{ number_format($grossPalletWeight, 2) }}</td>
        <td>{{ number_format($grossWeight, 2) }}</td>
        <td colspan="1" style="border: none !important;"></td>
    </tr>
</table>

<br>
<p><strong>Remarks</strong> : ____________________________________________________________________________________________________________________________ </p>
<p class="ack-statement">I hereby acknowledge the receipt of the above mentioned goods in good order and condition.</p>

<table class="table2">
    <tr>
        <td colspan="4" style="width: 50% !important;"><i class="logistics">DRIVER DETAILS</i></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Transporter</td>
        <td style="width: 23% !important;">{{ $details->transporter_name }}<hr class="dotted-hr"></td>
        <td style="width: 10% !important;">Reg. Number</td>
        <td style="width: 23% !important;">{{ $details->registration }}<hr class="dotted-hr"></td>
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
        <td style="width: 10% !important;">Signature &amp; Date</td>
        <td style="width: 23% !important;">,<hr class="dotted-hr"></td>
    </tr>
</table>
<br>

<p class="ack-line">
    <strong>Operator </strong> &nbsp; Name: ___________________________________________________ &nbsp;&nbsp; Signature: _____________________ &nbsp;&nbsp; Date &amp; Time: ___________________
</p>
<p class="ack-line">
    <strong>Loading Clerk</strong> &nbsp; Name: _____________________________________________ &nbsp;&nbsp; Signature: _____________________ &nbsp;&nbsp; Date &amp; Time: ___________________
</p>
<p class="ack-line">
    <strong>Whs Officer</strong> &nbsp; Name: ________________________________________________ &nbsp;&nbsp; Signature: _____________________ &nbsp;&nbsp; Date &amp; Time: ___________________
</p>
<p class="ack-line">
    <strong>Guard</strong> &nbsp; Name: ______________________________________________________ &nbsp;&nbsp; Signature: _____________________ &nbsp;&nbsp; Date &amp; Time: ___________________
</p>

<table class="table2">
    @foreach($approvals as $key => $approval)
            <?php
            $image = $approval->signature;

            if (empty($image)) {
                if ($key === 0) {
                    $signatory = $signatories->first(function ($s) {
                        return is_string($s->department_name) && stripos($s->department_name, 'Stock') !== false;
                    });
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
