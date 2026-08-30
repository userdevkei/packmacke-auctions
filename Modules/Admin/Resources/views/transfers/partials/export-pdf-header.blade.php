<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        h3 { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
<h3>External Tea Transfers — Line Detail</h3>
<p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
@if(!empty($truncated) && $truncated)
    <p style="color:#a94442; background:#f2dede; border:1px solid #ebccd1; padding:6px 8px;">
        This export was capped at {{ number_format($rowCap) }} rows. Narrow your date range or filters to get a complete export, or use the CSV export for large datasets.
    </p>
@endif
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Date</th>
        <th>Delivery No.</th>
        <th>Invoice No.</th>
        <th>Garden</th>
        <th>Grade</th>
        <th>Client</th>
        <th>Packages</th>
        <th>Net Weight</th>
        <th>From</th>
        <th>Destination</th>
        <th>Sale Type</th>
        <th>Status</th>
        <th>Aging</th>
    </tr>
    </thead>
    <tbody>