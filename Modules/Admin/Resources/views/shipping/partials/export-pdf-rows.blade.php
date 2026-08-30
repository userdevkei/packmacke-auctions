@foreach($rows as $i => $row)
    <tr>
        <td>{{ $startIndex + $i + 1 }}</td>
        <td>{{ $row->shipping_number }}</td>
        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/y') }}</td>
        <td>{{ $row->invoice_number }}</td>
        <td>{{ $row->garden_name }}</td>
        <td>{{ $row->grade_name }}</td>
        <td>{{ $row->client_name }}</td>
        <td class="num">{{ number_format(str_replace(',', '', $row->shipped_packages), 0) }}</td>
        <td class="num">{{ number_format(str_replace(',', '', $row->shipped_weight), 2) }}</td>
        <td>{{ $row->station_name }}</td>
        <td>{{ $row->destination_name }}</td>
        <td>{{ $statusLabels[$row->status] ?? $row->status }}</td>
        <td class="num">{{ $agingDaysFor($row) }}</td>
    </tr>
@endforeach
