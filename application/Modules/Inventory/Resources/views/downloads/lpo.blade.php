<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCAL PURCHASE ORDER #{{ $lpo->lpo_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c5f2d;
            padding-bottom: 15px;
        }

        .logo {
            height: 70px;
            width: 70px;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c5f2d;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.7;
        }

        .lpo-title {
            font-size: 15px;
            font-weight: bold;
            color: #2c5f2d;
            text-align: right;
        }

        .lpo-number {
            font-size: 15px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }

        /* Information Section */
        .info-section {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .info-box {
            border: 2px solid #dee2e6;
            padding: 18px;
            background-color: #f8f9fa;
            vertical-align: top;
        }

        .info-box-title {
            font-weight: bold;
            font-size: 13px;
            color: #2c5f2d;
            text-transform: uppercase;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c5f2d;
            letter-spacing: 0.5px;
        }

        .info-row {
            margin-bottom: 10px;
            line-height: 1.8;
        }

        .info-label {
            font-weight: bold;
            font-size: 11px;
            color: #555;
            text-transform: uppercase;
            display: inline-block;
            width: 120px;
            vertical-align: top;
        }

        .info-value {
            font-size: 12px;
            color: #333;
            display: inline-block;
            width: calc(100% - 130px);
            vertical-align: top;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            background-color: #28a745;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background-color: #ffc107;
            color: #333;
        }

        .status-badge.rejected {
            background-color: #dc3545;
        }

        .status-badge.cancelled {
            background-color: #6c757d;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .items-table thead {
            background-color: #2c5f2d;
            color: white;
        }

        .items-table th {
            padding: 12px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #2c5f2d;
        }

        .items-table td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
            line-height: 1.5;
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

        /* Totals Section */
        .totals-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 15px;
        }

        .totals-row {
            text-align: right;
            font-size: 12px;
        }

        .totals-label {
            font-weight: bold;
            color: #666;
            width: 70%;
        }

        .totals-value {
            font-weight: bold;
            color: #333;
            border-top: 1px solid #dee2e6;
            width: 30%;
            font-size: 13px;
        }

        .grand-total {
            background-color: #2c5f2d;
            color: white !important;
            font-size: 15px;
            padding: 12px 15px !important;
        }

        /* Notes Section */
        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
        }

        .notes-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
            color: #856404;
        }

        .notes-content {
            font-size: 11px;
            color: #666;
            line-height: 1.7;
        }

        /* Signatures Section */
        .signatures-table {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            padding: 15px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding-top: 8px;
            font-size: 12px;
            font-weight: bold;
        }

        .signature-role {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
        }

        .signature-date {
            font-size: 10px;
            color: #999;
            margin-top: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
            line-height: 1.7;
        }

        .terms-section {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
        }

        .terms-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            color: #2c5f2d;
        }

        .terms-content {
            font-size: 10px;
            color: #666;
            line-height: 1.8;
        }

        .terms-content ul {
            margin-left: 20px;
            margin-top: 6px;
        }

        .terms-content li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 65%; vertical-align: middle;">
                <img class="logo" src="{{ url('assets/img/favicons/icon.png') }}" alt="Company Logo">
                <div class="company-name">PACKMAC HOLDINGS LIMITED</div>
                <div class="company-details">
                    Chai Street Shimanzi High Level, Mombasa<br>
                    P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)<br>
                    Email: info@packmac.co.ke | Phone: +254 729 999777 | VAT: P051234567X
                </div>
            </td>
            <td style="width: 35%; vertical-align: middle; text-align: right;">
                <div class="lpo-title">LOCAL PURCHASE ORDER</div>
                <div class="lpo-number">#{{ $lpo->lpo_number }}</div>
                <br>
                <span style="font-size: 10px; color: #999;">Printed: {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</span>
            </td>
        </tr>
    </table>

    <!-- Information Section -->
    <table class="info-section">
        <tr>
            <!-- Supplier Information -->
            <td class="info-box" style="width: 50%; padding-right: 10px;">
                <div class="info-box-title">SUPPLIER INFORMATION</div><br>
                <div class="info-row">
                    <span class="info-label">Supplier:</span>
                    <span class="info-value">{{ strtoupper($lpo->supplier->supplier_name) }}</span>
                </div>
                @if($lpo->supplier->po_box)
                    <div class="info-row">
                        <span class="info-label">P.O. Box:</span>
                        <span class="info-value">{{ $lpo->supplier->po_box }}</span>
                    </div>
                @endif
                @if($lpo->supplier->street)
                    <div class="info-row">
                        <span class="info-label">Street:</span>
                        <span class="info-value">{{ $lpo->supplier->street }}</span>
                    </div>
                @endif
                @if($lpo->supplier->town)
                    <div class="info-row">
                        <span class="info-label">Town/City:</span>
                        <span class="info-value">{{ $lpo->supplier->town }}</span>
                    </div>
                @endif
                @if($lpo->supplier->phone_number)
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $lpo->supplier->phone_number }}</span>
                    </div>
                @endif
                @if($lpo->supplier->email)
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $lpo->supplier->email }}</span>
                    </div>
                @endif
            </td>

            <!-- Order Information -->
            <td class="info-box" style="width: 50%; padding-left: 10px;">
                <div class="info-box-title">ORDER DETAILS</div><br>
                <div class="info-row">
                    <span class="info-label">LPO Number:</span>
                    <span class="info-value">{{ $lpo->lpo_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($lpo->date)->format('d M, Y') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 5% !important;">#</th>
            <th style="width: 25% !important;">ITEM DESCRIPTION</th>
            <th style="width: 10% !important;" class="text-center">UNIT</th>
            <th style="width: 10% !important;" class="text-right">QUANTITY</th>
            <th style="width: 10% !important;" class="text-right">UNIT PRICE</th>
            <th style="width: 15% !important;" class="text-right">TOTAL</th>
            <th style="width: 10% !important;" class="text-right">VAT (16%)</th>
            <th style="width: 15% !important;" class="text-right">GROSS AMT</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($lpo->items as $key => $item)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td>{{ $item->item_name }}</td>
                <td class="text-center">{{ $item->uom->unit }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->vat_amount, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($item->gross_amount, 2) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totals Section -->
    <table class="totals-table">
        <tr class="totals-row">
            <td class="totals-label">Total Items:</td>
            <td class="totals-value">{{ $lpo->items->count() }}</td>
        </tr>
        <tr class="totals-row">
            <td class="totals-label">Subtotal:</td>
            <td class="totals-value">KES {{ number_format($lpo->subtotal, 2) }}</td>
        </tr>
        <tr class="totals-row">
            <td class="totals-label">VAT (16%):</td>
            <td class="totals-value">KES {{ number_format($lpo->vat_amount, 2) }}</td>
        </tr>
        <tr class="totals-row">
            <td class="grand-total">GRAND TOTAL:</td>
            <td class="grand-total">KES {{ number_format($lpo->total_amount, 2) }}</td>
        </tr>
    </table>

    <!-- Notes Section -->
    @if($lpo->notes)
        <div class="notes-section">
            <div class="notes-title">NOTES / SPECIAL INSTRUCTIONS:</div>
            <div class="notes-content">{{ $lpo->notes }}</div>
        </div>
    @endif

{{--    <!-- Terms and Conditions -->--}}
{{--    <div class="terms-section">--}}
{{--        <div class="terms-title">TERMS AND CONDITIONS:</div>--}}
{{--        <div class="terms-content">--}}
{{--            <ul>--}}
{{--                <li>Payment terms: Net 30 days from delivery date</li>--}}
{{--                <li>All goods must be delivered to the address specified above</li>--}}
{{--                <li>Goods must be as per specifications and quality standards</li>--}}
{{--                <li>Supplier must provide delivery note and tax invoice</li>--}}
{{--                <li>Any discrepancies must be reported within 48 hours of delivery</li>--}}
{{--                <li>This LPO is valid for 30 days from the date of issue</li>--}}
{{--            </ul>--}}
{{--        </div>--}}
{{--    </div>--}}

    <!-- Signatures Section -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line">Prepared By</div>
                <div class="signature-role">{{ $lpo->createdBy->first_name ?? 'N/A' }} {{ $lpo->createdBy->surname ?? '' }}</div>
                <div class="signature-date">{{ \Carbon\Carbon::parse($lpo->created_at)->format('d M Y') }}</div>
            </td>
            <td>
                <div class="signature-line">Approved By</div>
                <div class="signature-role">
                    @if($lpo->approved_at)
                        {{ $lpo->approvedBy->first_name ?? 'N/A' }} {{ $lpo->approvedBy->surname ?? '' }}
                    @else
                        Pending Approval
                    @endif
                </div>
                <div class="signature-date">
                    @if($lpo->approved_at)
                        {{ \Carbon\Carbon::parse($lpo->approved_at)->format('d M Y') }}
                    @endif
                </div>
            </td>
            <td>
                <div class="signature-line">Supplier Acknowledgment</div>
                <div class="signature-role">Signature & Stamp</div>
                <div class="signature-date">Date: _________________</div>
            </td>
        </tr>
    </table>

{{--    <!-- Footer -->--}}
{{--    <div class="footer">--}}
{{--        <strong>PACKMAC HOLDINGS LIMITED</strong><br>--}}
{{--        For queries, please contact: procurement@packmac.co.ke | Tel: +254 729 999777--}}
{{--    </div>--}}
</div>
</body>
</html>
