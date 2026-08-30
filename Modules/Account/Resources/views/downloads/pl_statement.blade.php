<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P&L STATEMENT FOR THE FINANCIAL YEAR {{ \Carbon\Carbon::parse($financial->year_starting)->format('Y') }} </title>
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
        .dotted-hr {
            margin-bottom: 0 !important;
            padding: 0 !important;
        }
        .underline {
            display: inline-block;
            border-bottom: 1px solid black; /* Adjust thickness & color */
            padding-bottom: 3px; /* Controls space between text & underline */
        }

        th {
            background-color: #cccccc;
        }
        .section-header {
            background-color: #cccccc;
            font-weight: bold;
            text-align: center !important;
        }

    </style>
</head>
<body>
<div class="company-info">
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">P&L STATEMENT FOR THE FINANCIAL YEAR {{ \Carbon\Carbon::parse($financial->year_starting)->format('Y') }} <hr></div>
<br>
<table class="table">
        <thead>
        <tr style="background-color: #4F81BD; color: white;">
            <th>#</th>
            <th>Category</th>
            <th style="text-align: right;">Amount (KES)</th>
        </tr>
        </thead>
        <tbody>

        <!-- REVENUE Section -->
        <tr>
            <td colspan="3" style="font-weight: bold; background-color: #f0f0f0;">REVENUE</td>
        </tr>
        @php $totalRevenue = 0; $rKey = 1; @endphp
        @foreach ($revenues as $chartName => $items)
            @php $chartTotal = $items->sum('total_amount_due'); @endphp
            <tr style="background-color: #d9e1f2;">
                <td>{{ $rKey++ }}</td>
                <td colspan="2"><strong>{{ strtoupper($chartName) }}</strong> ({{ $items->first()->currency_symbol ?? 'KES' }}) - {{ number_format($chartTotal, 2) }}</td>
            </tr>
            @foreach ($items->groupBy('ledger_name') as $ledger => $ledgerGroup)
                @php $amount = $ledgerGroup->sum('total_amount_due'); @endphp
                <tr>
                    <td></td>
                    <td>{{ $ledger }}</td>
                    <td style="text-align: right;">{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
            @php $totalRevenue += $chartTotal; @endphp
        @endforeach
        <tr style="font-weight: bold; background-color: #f0f0f0;">
            <td colspan="2">TOTAL REVENUE</td>
            <td style="text-align: right;">{{ number_format($totalRevenue, 2) }}</td>
        </tr>

        <!-- EXPENSES Section -->
        <tr>
            <td colspan="3" style="font-weight: bold; background-color: #f0f0f0;">EXPENSES</td>
        </tr>
        @php $totalExpenses = 0; $eKey = 1; @endphp
        @foreach ($expenses as $chartName => $items)
            @php $chartTotal = $items->sum('total_amount_due'); @endphp
            <tr style="background-color: #d9e1f2;">
                <td>{{ $eKey++ }}</td>
                <td colspan="2"><strong>{{ strtoupper($chartName) }}</strong> ({{ $items->first()->currency_symbol ?? 'KES' }}) - {{ number_format($chartTotal, 2) }}</td>
            </tr>
            @foreach ($items->groupBy('ledger_name') as $ledger => $ledgerGroup)
                @php $amount = $ledgerGroup->sum('total_amount_due'); @endphp
                <tr>
                    <td></td>
                    <td>{{ $ledger }}</td>
                    <td style="text-align: right;">{{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
            @php $totalExpenses += $chartTotal; @endphp
        @endforeach
        <tr style="font-weight: bold; background-color: #f0f0f0;">
            <td colspan="2">TOTAL EXPENSES</td>
            <td style="text-align: right;">{{ number_format($totalExpenses, 2) }}</td>
        </tr>

        <!-- NET PROFIT -->
        <tr style="font-weight: bold; background-color: #d4edda;">
            <td colspan="2">NET PROFIT</td>
            <td style="text-align: right;">{{ number_format($totalRevenue - $totalExpenses, 2) }}</td>
        </tr>

</table>
<br>
<br>
<table class="table2">
    <tr>
        <td style="width: 10% !important;">Approved By </td>
        <td style="width: 25% !important;" class="underline" > : PRATESH KUMAR</td>
        <td style="width: 10% !important;" >Signature</td>
        <td style="width: 23% !important;" class="underline"> : </td>
        <td style="width: 10% !important;">Date </td>
        <td style="width: 22% !important;" class="underline"> : </td>
    </tr>
</table>
</body>
</html>
