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
        <th>Client</th>
        <th>Packages</th>
        <th>Net Weight</th>
        <th>From</th>
        <th>Destination</th>
        <th>Sale Type</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $row)
        <tr>
            <td>{{ $startIndex + $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/y') }}</td>
            <td>{{ $row->delivery_number . $row->lot }}</td>
            <td>{{ $row->client_name }}</td>
            <td>{{ number_format($row->transferred_palettes, 0) }}</td>
            <td>{{ number_format($row->transferred_weight, 2) }}</td>
            <td>{{ $row->station_name }}</td>
            <td>{{ $row->warehouse_name }}</td>
            <td>{{ $row->sale_type }}</td>
            <td>{{ $statusLabels[$row->status] ?? 'Released' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>