<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ $type }} #{{ $invNumber }}</title>
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
    <p>PIN NO: P051685374V</p>
</div>
<div class="header"> {{ $type }} #{{ $invNumber }} <hr></div>
<br>
<table class="table no-border">
    <tr class="no-border">
        <th colspan="3" class="right-align no-border">{{ $type }}</th>
    </tr>
    <tr class="no-border">
        <td rowspan="6" >
            {{ $action }}: {{ $clientName  }}
        </td>
        <td class="right-align bold no-border">PAYMENT VOUCHER NUMBER :</td>
        <td class="left-align no-border">{{ $invNumber }}</td>
    </tr>
    <tr class="no-border">
        <td class="right-align bold no-border">FINANCIAL YEAR :</td>
        <td class="left-align no-border">{{ $fYear }}</td>
    </tr>
    <tr class="no-border">
        <td class="right-align bold no-border">DATE PAID :</td>
        <td class="left-align no-border">{{ $invDate }}</td>
    </tr>
    <tr class="no-border">
        <td class="right-align bold no-border">PAYMENT METHOD :</td>
        <td class="left-align no-border">{{ $invMethod }}</td>
    </tr>
    <tr class="no-border">
        <td class="right-align bold no-border">INSTRUMENT NUMBER :</td>
        <td class="left-align no-border">{{ $transCode }}</td>
    </tr>
    <tr class="no-border">
        <td class="right-align no-border bold">TOTAL PAID :</td>
        <td class="left-align no-border">{{ $invAmount }}</td>
    </tr>
</table>
<p><span class="bold"> Amount In Words : </span>{{ $amount }}</p>
<p><span class="bold"> Payment Description : </span>{{ $description }}</p>
<br>
<table class="table">
    <tr>
        <td class="no-border" style="width: 12% !important;">Prepared By : </td>
        <td class="no-border underline" style="width: 25% !important;" >{{ $user }}</td>
        <td class="no-border" style="width: 10% !important;" >Signature</td>
        <td style="width: 23% !important;" class="no-border underline"></td>
        <td class="no-border" style="width: 8% !important;">Date </td>
        <td style="width: 22% !important;" class="no-border underline"></td>
    </tr>
    <tr>
        <td class="no-border" style="width: 12% !important;">Approved By : </td>
        <td class="no-border underline" style="width: 25% !important;" >PRATESH KUMAR</td>
        <td class="no-border" style="width: 10% !important;" >Signature</td>
        <td style="width: 23% !important;" class="no-border underline"></td>
        <td class="no-border" style="width: 8% !important;">Date </td>
        <td style="width: 22% !important;" class="no-border underline"></td>
    </tr>
    <tr>
        <td class="no-border" style="width: 12% !important;">Received By : </td>
        <td class="no-border underline" style="width: 25% !important;" ></td>
        <td class="no-border" style="width: 10% !important;" >Signature</td>
        <td style="width: 23% !important;" class="no-border underline"></td>
        <td class="no-border" style="width: 8% !important;">Date </td>
        <td style="width: 22% !important;" class="no-border underline"></td>
    </tr>
</table>
</body>
</html>
