<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $account->client_account_name }} LEDGER STATEMENT</title>
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


    </style>
</head>
<body>
<div class="company-info">
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
</div>
<div class="header">{{ $account->client_account_name }} LEDGER STATEMENT FOR FINANCIAL YEAR {{ $fy[0]['fYear'] }} <hr></div>
<br>
<table class="table">

    <thead>
    <tr>
        <th style="width: 5% !important;">#</th>
        <th style="width: 7% !important;">Txn Type</th>
        <th style="width: 9% !important;">Date Invoiced</th>
        <th style="width: 9% !important;">Txn Number</th>
        <th style="width: 25% !important;">Ledger Name</th>
        {{-- <th style="width: 17% !important;">Description</th> --}}
        <th style="width: 9% !important;">Debit</th>
        <th style="width: 9% !important;">Credit</th>
        <th style="width: 9% !important;">Balance</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $balance = 0;
    $totalDebit = 0;
    $totalCredit = 0;
    ?>
        @foreach($statements as $statement)
                <?php
                $debit = $statement->debit ?? 0;
                $credit = $statement->credit ?? 0;
                $statement->type == 8 ? $balance += ($credit - $debit) : $balance += ($debit - $credit);
                ?>
            <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $statement->transaction_type ?? '--' }}</td>
            <td>{{ $statement->transaction_date == null || $statement->transaction_date == '--'  ? '--' : \Carbon\Carbon::createFromTimestamp($statement->transaction_date)->format('d-m-Y') }} </td>
            <td>{{ $statement->transaction_number }}</td>
            <td>{{ ucwords(strtolower($statement->ledger_name)) }}</td>
            {{-- <td>{{ ucwords(strtolower($statement->description)) }}</td> --}}
            <td>{{ number_format($statement->debit, 2) }}</td>
            <td>{{ number_format($statement->credit, 2) }}</td>
            <td>{{ number_format($balance, 2) }}</td> <!-- Running Balance -->

            </tr>
            <?php
                $totalDebit += $statement->debit;
                $totalCredit += $statement->credit;
            ?>
        @endforeach
    </tbody>
    <tr class="tfooter" style="font-weight: bold;">
        <td colspan="5" style="border: none !important;"></td>
        <td>{{ number_format($totalDebit, 2) }}</td>
        <td>{{ number_format($totalCredit, 2) }}</td>
        <td>{{ number_format($balance, 2) }}</td>
    </tr>
</table>
<br>
<table class="table2">
    <tr>
        <td style="width: 10% !important;">Approved By : </td>
        <td style="width: 25% !important;" class="underline" >PRATESH KUMAR</td>
        <td style="width: 10% !important;" >Signature</td>
        <td style="width: 23% !important;" class="underline"></td>
        <td style="width: 10% !important;">Date </td>
        <td style="width: 22% !important;" class="underline"></td>
    </tr>
</table>
</body>
</html>
