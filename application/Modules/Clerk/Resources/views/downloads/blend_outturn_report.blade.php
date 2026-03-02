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
        .h-center{
            text-align: center;
            font-weight: bold;
            padding: 0;
            font-size: 11px;
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
<div class="header">BLEND OUTTURN REPORT<hr></div>
<br>
<table class="table">
    <tr>
        <td style="width: 50%!important;"> BLEND DATE </td>
        <td style="width: 50% !important;"> {{ $sheet->blend_date }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SHIPPER </td>
        <td style="width: 35% !important;"> {{ $sheet->vessel_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> CONSIGNEE </td>
        <td style="width: 35% !important;"> {{ $sheet->consignee }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> SI/BLEND NUMBER </td>
        <td style="width: 35% !important;"> {{ $sheet->blend_number }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> DESTINATION </td>
        <td style="width: 35% !important;"> {{ $sheet->port_name }} </td>
    </tr>
    <tr>
        <td style="width: 15% !important;"> STANDARD DETAILS </td>
        <td style="width: 35% !important;"> {{ $sheet->standard_details }} </td>
    </tr>

</table>
<p class="h-center">BLEND SUMMARY </p>
<table class="table">
    <tr>
    <th>Item</th>
    <th>Packages</th>
    <th>Unit Weight</th>
    <th>Total Weight</th>
    </tr>
    <tbody>
    @php $totalPackages = 0; $totalWeight = 0; $totalRemnant = 0; @endphp
{{--    @dd(!$blendSummaries)--}}
    @if($blendSummaries->count() > 0)
        @foreach($blendSummaries as $key => $blendSummary)
            <tr>
                <td>SHIPMENT TOTAL {{ ++$key }}</td>
                <td>{{ number_format($blendSummary->blended_packages) }}</td>
                <td>{{ number_format($blendSummary->unit_weight, 2) }}</td>
                <td>{{ number_format($blendSummary->net_weight, 2) }}</td>
            </tr>
            @php $totalPackages += $blendSummary->blended_packages; $totalWeight += $blendSummary->net_weight; @endphp
        @endforeach
        @if($blendSummary && $blendSummary->weight_variance > 0)
            <tr>
                <td>TARE WEIGHT VARIANCE</td>
                <td>{{ number_format($totalPackages) }}</td>
                <td>{{ number_format($blendSummary->weight_variance, 2) }}</td>
                <td>{{ number_format($blendSummary->weight_variance * $totalPackages, 2) }}</td>
            </tr>
        @endif
    @else
        <tr>
            <td>SHIPMENT TOTAL 1</td>
            <td>{{ number_format(0) }}</td>
            <td>{{ number_format(0, 2) }}</td>
            <td>{{ number_format(0, 2) }}</td>
        </tr>
    @endif

    @foreach($blendBalances as $key => $blendBalance)
        <tr>
            <td>BLEND REMNANT {{ ++$key }}</td>
            <td>{{ $blendBalance->ex_packages }}</td>
            <td>{{ number_format($blendBalance->unit_weight, 2) }}</td>
            <td>{{ number_format($blendBalance->net_weight, 2) }}</td>

            @php $totalRemnant += $blendBalance->net_weight; @endphp
        </tr>
    @endforeach
    <tr>
        <td>SIEVED DUST</td>
        <td>1</td>
        <td>{{ number_format($sheet->b_dust, 2) }}</td>
        <td>{{ number_format($sheet->b_dust, 2) }}</td>
    </tr>
    <tr>
        <td>CYCLONE/DUST</td>
        <td>1</td>
        <td>{{ number_format($sheet->c_dust, 2) }}</td>
        <td>{{ number_format($sheet->c_dust, 2) }}</td>
    </tr>
    <tr>
        <td>FIBRE</td>
        <td>1</td>
        <td>{{ number_format($sheet->fibre, 2) }}</td>
        <td>{{ number_format($sheet->fibre, 2) }}</td>
    </tr>
    <tr>
        <td>SWEEPINGS</td>
        <td>1</td>
        <td>{{ number_format($sheet->sweepings, 2) }}</td>
        <td>{{ number_format($sheet->sweepings, 2) }}</td>
    </tr>
    <tr>
        <td colspan="3">TOTAL BLEND INPUT</td>
        <td>{{ number_format($input->input_weight, 2) }}</td>
    </tr>
    @if($blendSummaries->count() > 0)
        @php
            $totalOutput =  floatval($totalRemnant) + floatval($totalWeight) + floatval($blendBalance->sweepings) + floatval($blendBalance->fibre) + floatval($blendBalance->c_dust) + floatval($blendBalance->b_dust) + floatval($blendSummary->weight_variance * $totalPackages);
        @endphp
    <tr>
        <td colspan="3">TOTAL BLEND OUTPUT</td>
        <td>{{ number_format($totalOutput, 2) }}</td>
    </tr>
    <tr>
        <td colspan="3">BLEND GAIN/LOSS</td>
        <td>{{ number_format($totalOutput - $sheet->input_weight, 2) }} ({{ number_format((($totalOutput - $sheet->input_weight)/$sheet->input_weight)*100,2) }}%)</td>
    </tr>
    @else
        @php
            $totalOutput =  floatval($totalRemnant) + floatval($totalWeight) + floatval($blendBalance->sweepings) + floatval($blendBalance->fibre) + floatval($blendBalance->c_dust) + floatval($blendBalance->b_dust);
        @endphp

        <tr>
            <td colspan="3">TOTAL BLEND OUTPUT</td>
            <td>{{ number_format($totalOutput, 2) }}</td>
        </tr>
        <tr>
            @php $inputWeight = \App\Models\BlendTea::where('blend_id', $blendBalance->blend_id)->sum('blended_weight'); @endphp
            <td colspan="3">BLEND GAIN/LOSS (%)</td>
            <td>{{ number_format($totalOutput - floatval($inputWeight), 2) }} ({{ number_format((($totalOutput-$inputWeight)/$inputWeight) * 100, 2) }}%)</td>
        </tr>
    @endif
    </tbody>
</table>
<p class="h-center">NEW MATERIALS ISSUED</p>
<table class="table">
    <tr>
        <th>PAPER SACK</th>
        <th>POLY BAG</th>
        <th>SMALL POUCH</th>
        <th>PALLETS </th>
        <th>GUMMY BAGS</th>
    </tr>
    <tr>
        <td>{{ $materials->where('condition', 1)->where('material_type', 1)->first() == null ? 0: $materials->where('condition', 1)->where('material_type', 1)->first()->total }}</td>
        <td>{{ $materials->where('condition', 1)->where('material_type', 2)->first() == null ? 0: $materials->where('condition', 1)->where('material_type', 2)->first()->total }}</td>
        <td>{{ $materials->where('condition', 1)->where('material_type', 3)->first() == null ? 0: $materials->where('condition', 1)->where('material_type', 3)->first()->total }}</td>
        <td>{{ $materials->where('condition', 1)->where('material_type', 4)->first() == null ? 0: $materials->where('condition', 1)->where('material_type', 4)->first()->total }}</td>
        <td>{{ $materials->where('condition', 1)->where('material_type', 5)->first() == null ? 0: $materials->where('condition', 1)->where('material_type', 5)->first()->total }}</td>
    </tr>
</table>
<p class="h-center">RETRIEVALS</p>
<table class="table">
    <tr>
        <th>Item</th>
        <th>PAPER SACK</th>
        <th>POLY BAG</th>
        <th>PALLETS</th>
        <th>GUMMY BAGS</th>
    </tr>
    <tr>
        <th>USED MATERIAL RETRIEVALS</th>
        <td>{{ $materials->where('condition', 2)->where('material_type', 1)->first() == null ? 0: $materials->where('condition', 2)->where('material_type', 1)->first()->total }}</td>
        <td>{{ $materials->where('condition', 2)->where('material_type', 2)->first() == null ? 0: $materials->where('condition', 2)->where('material_type', 2)->first()->total }}</td>
        <td>{{ $materials->where('condition', 2)->where('material_type', 3)->first() == null ? 0: $materials->where('condition', 2)->where('material_type', 3)->first()->total }}</td>
        <td>{{ $materials->where('condition', 2)->where('material_type', 4)->first() == null ? 0: $materials->where('condition', 2)->where('material_type', 4)->first()->total }}</td>
    </tr>
    <tr>
        <th>IN USE/DAMAGED MATERIALS </th>
        <td>{{ $materials->where('condition', 3)->where('material_type', 1)->first() == null ? 0: $materials->where('condition', 3)->where('material_type', 1)->first()->total }}</td>
        <td>{{ $materials->where('condition', 3)->where('material_type', 2)->first() == null ? 0: $materials->where('condition', 3)->where('material_type', 2)->first()->total }}</td>
        <td>{{ $materials->where('condition', 3)->where('material_type', 3)->first() == null ? 0: $materials->where('condition', 3)->where('material_type', 3)->first()->total }}</td>
        <td>{{ $materials->where('condition', 3)->where('material_type', 4)->first() == null ? 0: $materials->where('condition', 3)->where('material_type', 4)->first()->total }}</td>
    </tr>
    <tr>
        <th>NET USED MATERIALS </th>
        <td>{{ floatval($materials->where('condition', 2)->where('material_type', 1)->first()->total) - floatval($materials->where('condition', 3)->where('material_type', 1)->first()->total) }}</td>
        <td>{{ floatval($materials->where('condition', 2)->where('material_type', 2)->first()->total) - floatval($materials->where('condition', 3)->where('material_type', 2)->first()->total) }}</td>
        <td>{{ floatval($materials->where('condition', 2)->where('material_type', 3)->first()->total) - floatval($materials->where('condition', 3)->where('material_type', 3)->first()->total) }}</td>
        <td>{{ floatval($materials->where('condition', 2)->where('material_type', 4)->first()->total) - floatval($materials->where('condition', 3)->where('material_type', 4)->first()->total) }}</td>
    </tr>
</table>
<p class="h-center">OFFICERS INVOLVED</p>
<table class="table">
    <tr>
        <th style="width: 25% !important;">COMPLIED BY</th>
        <th style="width: 25% !important;">MACHINE OPERATOR(S)</th>
        <th style="width: 25% !important;">BLEND SUPERVISOR(S)</th>
        <th style="width: 25% !important;">3rd PARTY INSPECTION CLERKS(S)</th>
    </tr>
    <tr>
        <td>{{ $user == null ? '' : $user }}</td>
        <td>{{ $supervisors->where('supervisor_type', 1)->first() == null ? '' :$supervisors->where('supervisor_type', 1)->first()['supervisor_name'] }}</td>
        <td>{{ $supervisors->where('supervisor_type', 2)->first() == null ? '' : $supervisors->where('supervisor_type', 2)->first()['supervisor_name'] }}</td>
        <td>{{ $supervisors->where('supervisor_type', 3)->first() == null ? '' : $supervisors->where('supervisor_type', 3)->first()['supervisor_name'] }}</td>
    </tr>
    <tr>
        <td>.</td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
</body>
</html>
