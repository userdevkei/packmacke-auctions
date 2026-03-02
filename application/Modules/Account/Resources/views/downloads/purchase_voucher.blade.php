<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PURCHASE VOUCHER #{{ $values->voucher_number }} </title>
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
            font-size: 12px !important;
        }
        .no-border { border: none; }
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
            margin: 5px !important;
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
        .bold {
            font-weight: bold;
        }
        .right-align {
            text-align: right;
        }
        .center-align{
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
<div class="header">PURCHASE VOUCHER #{{ $values->voucher_number }}<hr></div>
<p><span class="bold">SUPPLIER NAME:</span> {{ strtoupper($account->client_account_name) }}</p>
<p><span class="bold">INVOICE NUMBER:</span> {{ $values->invoice_number }}</p>
<table class="table no-border">
    <tbody class="no-border">
        <tr class="no-border">
            <td class="bold no-border">FINANCIAL YEAR</td>
            <td class="bold no-border">VOUCHER NUMBER</td>
            <td class="bold no-border">INVOICE DATE</td>
            <td class="bold no-border">INVOICE DUE DATE</td>
        </tr>
        <tr class="no-border">
            <td class="no-border">{{ $fYear }}</td>
            <td class="no-border">{{ $values->voucher_number }}</td>
            <td class="no-border">{{ \Carbon\Carbon::createFromTimestamp($values->date_invoiced)->format('d-m-Y') }}</td>
            <td class="no-border">{{ \Carbon\Carbon::createFromTimestamp($values->due_date)->format('d-m-Y') }}</td>
        </tr>
    </tbody>
</table>
<hr>
<br>
<table class="table">
    <thead>
    <tr>
        <th>#</th>
        <th>Invoice Item</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    @php
        $totalTax = 0;
        $amountDue = 0;
    @endphp

    @foreach ($purchases as $key => $purchase)
        @php
            $lineTotal = $purchase['quantity'] * $purchase['unit_price'];
            $taxRate = $purchase['tax_rate'] ?? 0;
            $lineTax = ($taxRate / 100) * $lineTotal;

            $totalTax += $lineTax;
            $amountDue += $lineTotal;
        @endphp
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $purchase->account_name }}</td>
            <td>{{ $account->currency_symbol }} {{ number_format($lineTotal, 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <td>{{ count($purchases) + 1 }}</td>
        <td class="">VALUE ADDED TAX</td>
        <td class="">{{ $account->currency_symbol }} {{ number_format($totalTax, 2) }}</td>
    </tr>
    <tr>
        <td colspan="2" class="bold center-align">TOTAL AMOUNT DUE</td>
        <td class="bold">{{ $account->currency_symbol }} {{ number_format($totalTax + $amountDue, 2) }}</td>
    </tr>
    <tr>
        <td colspan='3'><b>Narration : </b>{{ $purchase->customer_message }} </td>
    </tr>
    </tbody>
</table>
<p class="right-align">
    <span class="bold">INVOICE TO BE PAID BY OR BEFORE, </span> {{ \Carbon\Carbon::createFromTimestamp($values->due_date)->format('D, d/m/Y') }}
</p>
</body>
</html>
