@foreach($rows as $i => $row)
    <tr>
        <td>{{ $startIndex + $i + 1 }}</td>
        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/y') }}</td>
        <td>{{ $row->delivery_number . $row->lot }}</td>
        <td>{{ $row->invoice_number }}</td>
        <td>{{ $row->garden_name }}</td>
        <td>{{ $row->grade_name }}</td>
        <td>{{ $row->client_name }}</td>
        <td>{{ number_format($row->transferred_palettes, 0) }}</td>
        <td>{{ number_format($row->transferred_weight, 2) }}</td>
        <td>{{ $row->station_name }}</td>
        <td>{{ $row->warehouse_name }}</td>
        <td>{{ $row->sale_type }}</td>
        <td>{{ $statusLabels[$row->status] ?? 'Released' }}</td>
        <td>{{ $agingDaysFor($row) }} days</td>
    </tr>
@endforeach