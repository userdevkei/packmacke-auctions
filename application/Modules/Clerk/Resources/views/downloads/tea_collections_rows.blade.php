@foreach($orders as $i => $order)
    <tr>
        <td>{{ $startIndex + $i + 1 }}</td>
        <td>{{ $order->delivery_type == 1 ? 'DO Entry' : 'Direct Del' }}</td>
        <td>{{ $order->invoice_number }}</td>
        <td>{{ $order->garden_name }}</td>
        <td>{{ $order->grade_name }}</td>
        <td>{{ $order->order_number }}</td>
        <td>{{ $order->lot_number }}</td>
        <td>{{ $order->sale_number }}</td>
        <td>{{ number_format($order->packet) }}</td>
        <td>{{ $order->unit_weight == null ? number_format($order->weight, 2) : number_format($order->unit_weight, 2) }}</td>
        <td>{{ number_format($order->sample_weight, 2) }}</td>
        <td>{{ $order->difference == null ? '--' : $order->difference }}</td>
        <td>{{ $order->warehouse_name }}</td>
        <td>{{ \Carbon\Carbon::createFromTimestamp($order->date_received)->format('Y-m-d') }}</td>
        <td>{{ $order->status == null || $order->status == 1 ? 'Under Collection' : 'Collected' }}</td>
    </tr>
@endforeach