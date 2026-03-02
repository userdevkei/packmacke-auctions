<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bank->client_account_name }} BANK RECONCILIATION STATEMENT</title>
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
        table { border-collapse: collapse; width: 100%; }
        .table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #ccc; }
        .right-align { text-align: right; }
        .left-align { text-align: left; }
        .bold { font-weight: bold; }
        .spacer-row td { border: none; padding: 15px 0; }
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


    </style>
</head>
<body>
<div class="company-info">
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">{{ $bank->client_account_name }} BANK RECONCILIATION STATEMENT<hr></div>
<br>
<table class="table">
    <thead>
    <tr>
        <th>#</th>
        <th>INVOICE NO.</th>
        <th>CLIENT NAME</th>
        <th>BANK DATE</th>
        <th>DEBIT</th>
        <th>CREDIT</th>
    </tr>
    </thead>
    <tbody>
    @php
        $reconciledDebit = 0;
        $reconciledCredit = 0;
        $unreconciledDebit = 0;
        $unreconciledCredit = 0;
    @endphp

    @foreach ($reconciled as $r)
        @php
            $reconciledDebit += $r->debit;
            $reconciledCredit += $r->credit;
        @endphp
    @endforeach

    @foreach ($unreconciled as $key => $statement)
        @php
            $unreconciledDebit += $statement->debit;
            $unreconciledCredit += $statement->credit;
        @endphp
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $statement->invoice_number }}</td>
            <td class="left-align">{{ $statement->client_account_name }}</td>
            <td class="left-align">{{ \Carbon\Carbon::parse($statement->date_received)->format('d/m/y') }}</td>
            <td class="left-align">{{ number_format($statement->debit, 2) }}</td>
            <td class="left-align">{{ number_format($statement->credit, 2) }}</td>
        </tr>
    @endforeach

    <tr>
        <td colspan="3" class="bold">UNRECONCILED TOTALS IN ({{ $bank->currency_symbol }}) AS OF</td>
        <td class="left-align">{{ $date }}</td>
        <td class="bold left-align">{{ number_format($unreconciledDebit, 2) }}</td>
        <td class="bold left-align">{{ number_format($unreconciledCredit, 2) }}</td>
    </tr>

    <tr class="spacer-row"><td colspan="6"></td></tr>

    <tr>
        <td colspan="4" class="right-align bold">Balance as per company books</td>
        <td colspan="2" class="bold">
            {{ $bank->currency_symbol . ' ' . number_format($lastBalance + $reconciledDebit + $balance - $reconciledCredit, 2) }}
        </td>
    </tr>

    <tr>
        <td colspan="4" class="right-align bold">Amount not reflected in bank</td>
        <td colspan="2" class="bold">
            {{ $bank->currency_symbol . ' ' . number_format($unreconciledCredit, 2) }}
        </td>
    </tr>

    <tr class="spacer-row"><td colspan="6"></td></tr>

    @php
        $bankBalance = $lastBalance + $reconciledDebit + $balance + $unreconciledCredit - $reconciledCredit;
    @endphp

    <tr>
        <td colspan="4" class="right-align bold">Balance as per bank</td>
        <td colspan="2" class="bold">
            {{ $bank->currency_symbol . ' ' . number_format($bankBalance, 2) }}
        </td>
    </tr>
    </tbody>
</table>
<br>
<table class="table">
    <tr>
        <td class="no-border" style="width: 12% !important;">Approved By : </td>
        <td class="no-border underline" style="width: 25% !important;" >PRATESH KUMAR</td>
        <td class="no-border" style="width: 10% !important;" >Signature</td>
        <td style="width: 23% !important;" class="no-border underline"></td>
        <td class="no-border" style="width: 8% !important;">Date </td>
        <td style="width: 22% !important;" class="no-border underline"></td>
    </tr>
</table>
</body>
</html>
