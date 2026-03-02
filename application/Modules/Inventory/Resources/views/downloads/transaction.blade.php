<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type }} #{{ $transaction->release_number ?? $transaction->requisition_number ?? $transaction->transfer_out_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 10px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header Section using table */
        .header-table {
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 3px solid #2c5f2d;
            padding-bottom: 10px;
        }

        .logo {
            height: 60px;
            width: 60px;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c5f2d;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 9px;
            color: #666;
            line-height: 1.5;
        }

        .po-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c5f2d;
            text-align: right;
        }

        .po-number {
            font-size: 13px;
            color: #666;
            text-align: right;
            margin-top: 3px;
        }

        /* Information Section using table */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .info-table td {
            vertical-align: top;
            padding: 12px;
        }

        .info-box-left {
            width: 50%;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .info-box-right {
            width: 50%;
            padding-left: 15px;
        }

        .info-label {
            font-weight: bold;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 12px;
            color: #333;
            margin-bottom: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            background-color: #28a745;
            color: white;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background-color: #ffc107;
        }

        .status-badge.cancelled {
            background-color: #dc3545;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .items-table thead {
            background-color: #2c5f2d;
            color: white;
        }

        .items-table th {
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #2c5f2d;
        }

        .items-table td {
            padding: 8px;
            border: 1px solid #dee2e6;
            font-size: 12px;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Signatures Section using table */
        .signatures-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            padding: 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 25px;
            padding-top: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .signature-role {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 8px;
            color: #666;
            line-height: 1.5;
        }

        .notes-section {
            margin-top: 15px;
            padding: 12px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .notes-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .notes-content {
            font-size: 9px;
            color: #666;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 65%; vertical-align: middle;">
                <img class="logo" src="{{ 'assets/img/favicons/icon.png' }}" alt="Company Logo">
                <div class="company-name">PACKMAC HOLDINGS LIMITED</div>
                <div class="company-details">
                    Chai Street Shimanzi High Level, Mombasa<br>
                    P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)<br>
                    Email: info@packmac.co.ke | Phone: +254 729 999777
                </div>
            </td>
            <td style="width: 35%; vertical-align: middle; text-align: right;">
                <div class="po-title">{{ $type }}</div>
                <div class="po-number">#{{ $transaction->release_number ?? $transaction->requisition_number ?? $transaction->transfer_out_number }}</div>
                <br>
                <span>Printed On: {{ now() }}</span>
            </td>
        </tr>
    </table>

    <!-- Information Section -->
    <table class="info-table">
        <tr>
            <td class="info-box-left">
                <span class="info-label">Client : </span>
                <span class="info-value">{{ strtoupper($transaction->client->client_name) }}</span>

                <br>
                <br>
                <span class="info-label"> {{ $type == 'Transfer' ? 'Transfer To' : ($type == 'Requisition' ? 'SI Number' : 'Release To') }} : </span>
                <span class="info-value">{{ strtoupper( $transaction->recipient->client_name ?? $transaction->released_to ?? $transaction->si_number.' - '. $transaction->warehouse?->station_name) }}</span>

            </td>
            <td class="info-box-right">
                <span class="info-label"> {{ $type }} Date : </span>
                <span class="info-value">{{ \Carbon\Carbon::parse($transaction->transfer_date ?? $transaction->release_date ?? $transaction->requisition_date)->format('d M, Y') }}</span>
                <br>
                <br>

                <span class="info-label">Status : </span>
                <span class="info-value">
                    <span class="status-badge {{ strtolower($transaction->status) }}">{{ ucwords($transaction->status) }}</span>
                </span>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th>ITEM DESCRIPTION</th>
            <th style="width: 80px;" class="text-center">UNIT</th>
            <th style="width: 80px;" class="text-right">QUANTITY</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($transaction->items as $key => $item)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td>{{ ucwords(strtolower($item->item->item_name)) }}</td>
                <td class="text-center">{{ $item->item->unit_label }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" class="text-right">
                Total Items - {{ $transaction->items->count() }}
            </td>
        </tr>
        </tbody>
    </table>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">Driver Details </div>
                <br>
                <div class="signature-role">{{ $transaction->driver_name.' | '.$transaction->phone_number }}</div>
            </td>
            <td>
                <div class="signature-line">Vehicle Registration</div>
                <br>
                <div class="signature-role">.{{ $transaction->registration_number }} </div>
            </td>
        </tr>
    </table>

    <!-- Signatures Section -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">Prepared By</div>
                <br>
                <div class="signature-role">{{ $transaction->user?->first_name.' '.$transaction->user?->surname }}</div>
            </td>
            <td>
                <div class="signature-line">Signature</div>
                <br>
                <div class="signature-role">___________________________________________________</div>
            </td>
        </tr>
    </table>

    <!-- Signatures Section -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">Approved By</div>
                <br>
                <div class="signature-role">{{ $transaction->approvedBy?->first_name.' '.$transaction->approvedBy?->surname }}</div>
            </td>
            <td>
                <div class="signature-line">Signature</div>
                <br>
                <div class="signature-role">___________________________________________________</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <strong>PACKMAC HOLDINGS LIMITED</strong><br>
        This is a computer-generated document. Signature may not be required.<br>
    </div>
</div>
</body>
</html>
