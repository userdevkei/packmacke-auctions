<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADJUSTMENT JOURNAL #{{ $journal->reference_code }} </title>
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
        .text-centered {
            text-align: center !important;
        }
    </style>
</head>
<body>
<div class="company-info">
    <span><img class="logo" src="{{ 'assets/img/favicons/icon.png' }}"></span>
    <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
    <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
    <p>PIN NO: P051685374V</p>
</div>
<div class="header">ADJUSTMENT JOURNAL #{{ $journal->reference_code }}<hr></div>
<br>
<table class="table">
    <tr>
        <th>#</th>
        <th>DATE</th>
        <th>TRANSACTION</th>
        <th>DEBIT</th>
        <th>CREDIT</th>
    </tr>
    @foreach($journals as $jv)
        @php
            $credit = 120; $debit = 0;
                if ($journal->priority == 1 && $jv->priority == 2){
                    $debit = $jv->debit * $jv->exchange_rate;
                    $credit = $jv->credit * $jv->exchange_rate;
                }elseif ($journal->priority == 2 && $jv->priority == 1){
                    $debit = $jv->debit / $jv->exchange_rate;
                    $credit = $jv->credit / $jv->exchange_rate;
                }else{
                    $debit = $jv->debit;
                    $credit = $jv->credit;
                }
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ \Carbon\Carbon::createFromTimestamp($jv->date_adjusted)->format('Y-m-d') }}</td>
            <td>{{ $jv->client_account_name }}</td>
            <td>{{ number_format($debit, 2) }}</td>
            <td>{{ number_format($credit, 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="5" class="text-centered text-center">{{ $journal->description }}</td>
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
