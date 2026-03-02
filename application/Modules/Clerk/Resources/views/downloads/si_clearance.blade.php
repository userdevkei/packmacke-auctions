<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shipment->shipping_number }}</title>
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
            font-size: 12px !important;
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
<div class="header">PORT DRIVER CLEARANCE<hr></div>
<br>
<p>Receive the following shipment in good order and condition;</p>
<br>
<br>
<table class="table">
    <tr>
        <td style="width: 15%!important;"> SI NUMBER </td>
        <td style="width: 35% !important;"> {{ $shipment->shipping_number }} </td>
        <td style="width: 20%!important;"> CONTAINER NUMBERS </td>
        <td style="width: 30% !important;"> {{ $shipment->container_number }} ({{ $shipment->container_size == 1 ? '20 FT' : ($shipment->container_size == 2 ? '40 FT' : '40 FTHC') }}) </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> DESTINATION </td>
        <td style="width: 35% !important;"> {{ $shipment->port_name }} </td>
        <td style="width: 20% !important;"> SHIPPING AGENT </td>
        <td style="width: 30% !important;"> {{ $shipment->agent_name }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> CONSIGNEE </td>
        <td style="width: 35% !important;"> {{ $shipment->consignee }} </td>
        <td style="width: 20% !important;"> TRANSPORTER </td>
        <td style="width: 30% !important;"> {{ $shipment->transporter_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SHIPPING MARK </td>
        <td style="width: 35% !important;"> {{ $shipment->shipping_mark }} </td>
        <td style="width: 20% !important;"> TRUCK REGISTRATION </td>
        <td style="width: 30% !important;"> {{ $shipment->registration }}</td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> VESEL NAME </td>
        <td style="width: 35% !important;"> {{ $shipment->vessel_name }} </td>
        <td style="width: 20% !important;"> DRIVER NAME </td>
        <td style="width: 30% !important;"> {{ $shipment->driver_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SEAL NUMBER </td>
        <td style="width: 35% !important;"> {{ $shipment->seal_number }} </td>
        <td style="width: 20% !important;"> DRIVER PHONE </td>
        <td style="width: 30% !important;"> {{ $shipment->phone }}</td>
    </tr>

</table>

<br>
<br>
<p><strong>Total Packages : </strong> {{ number_format($shipment->totalPackages) }} </p>
<p><strong>Total Weight : </strong> {{ number_format($shipment->totalWeight, 2) }}</p>
<p><strong>Loading Type : </strong> {{ $shipment->loading_type == 1 ? 'LOOSE LOADING' : 'PALLETIZED LOADING' }}</p>
<br>
<br>
<p>Shipment left the warehouse in good order and condition;</p>
<br>

<table class="table2">
    <tr>
        <td colspan="2" style="width: 20% !important;"><i class="logistics">DRIVER DETAILS</i></td>
        <td colspan="2" style="width: 20% !important;"><i class="logistics">OFFICER DETAILS</i></td>
    </tr>
    <tr>
        <td style="width: 10% !important;">Driver Name</td>
        <td style="width: 23% !important;">{{ $shipment->driver_name }}<hr class="dotted-hr"></td>
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
<br>
<br>
<p>Goods received in good order and condition;</p>
<br>
<p>RECEIVING OFFICER : ____________________________________________</p>
<br>
<p>OFFICER SIGNATURE : ___________________________________________</p>
<br>
<p>DATE RECEIVED : _________________________________________________</p>
<br>
<br>
<p><i><strong>Printed On:</strong> {{ $date }}</i></p>
</body>
</html>
