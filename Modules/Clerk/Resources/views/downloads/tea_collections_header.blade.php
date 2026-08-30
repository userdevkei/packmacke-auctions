<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEA COLLECTIONS</title>
    <style>
        body {
            font-size: 12px;
            line-height: 1.0;
            padding: 0px !important;
            margin: 0px !important;
        }
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .company-info {
            text-align: center;
            margin: 0px !important;
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
            font-size: 11px !important;
        }
        .heading {
            color: green;
            font-size: 12px !important;
            font-weight: bold !important;
        }
        .tfooter {
            font-weight: bold !important;
        }
        .logo {
            height: 70px !important;
            width: 70px !important;
            padding-left: 0px !important;
        }
    </style>
</head>
<body>
<table>
    <tr>
        <td style="width: 80% !important;"> CLIENT NAME: <strong> {{ $clientName }} </strong></td>
    </tr>
</table>
<br>

<table class="table">
    <thead>
    <tr>
        <th style="width: 4% !important;">#</th>
        <th style="width: 9% !important;">Del. Type</th>
        <th style="width: 8% !important;">Invoice #</th>
        <th style="width: 9% !important;">Garden</th>
        <th style="width: 5% !important;">Grade</th>
        <th style="width: 7% !important;">DO #</th>
        <th style="width: 7% !important;">Lot #</th>
        <th style="width: 6% !important;">Sale #</th>
        <th style="width: 5% !important;">Pckgs</th>
        <th style="width: 7% !important;">Weight</th>
        <th style="width: 7% !important;">Wht Loss</th>
        <th style="width: 7% !important;">Loss Type</th>
        <th style="width: 22% !important;">Producer Whs</th>
        <th style="width: 11% !important;">Collected On</th>
        <th style="width: 9% !important;">Status</th>
    </tr>
    </thead>
    <tbody>