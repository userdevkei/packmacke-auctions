@extends('clerk::layouts.default')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Foreign Teas Validation </h5>
                </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div id="table-simple-pagination-replace-element">

                        </div>
                    </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped table-responsive-sm fs-sm" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>Inv No</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Origin</th>
                            <th>Lot No</th>
                            <th>Producer Whs</th>
                            <th>Sub-Warehouse</th>
                            <th>TCI #</th>
                            <th class="text-center">Entries Status</th>
                            <th></th>
                        </tr>
                        </thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td nowrap="">{{ $order->client_name }}</td>
                            <td>{{ $order->invoice_number }}</td>
                            <td>{{ $order->garden_name }}</td>
                            <td nowrap="">{{ $order->grade_name }}</td>
                            <td>{{ $order->tea_type }}</td>
                            <td>{{ $order->lot_number }}</td>
                            <td>{{ $order->warehouse_name }}</td>
                            <td style="white-space: normal; word-wrap: break-word; word-break: break-word;">{{ $order->sub_warehouse_name }}</td>
                            <td>{{ $order->loading_number }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input m-0 received-toggle" id="receive{{ $order->delivery_id }}" type="checkbox" @checked($order->received == 'received' && $order->received !== null) @disabled($order->validated === 'validated') data-delivery="{{ $order->delivery_id }}">
                                    <span class="badge {{ $order->received === 'not_received' || $order->received == null ? 'bg-warning' : 'bg-success' }}"> {{ $order->received === 'not_received' || $order->received == null ? 'Not Received' : 'Received' }}</span>

                                    <input class="form-check-input m-0 validated-toggle" id="validate{{ $order->delivery_id }}" type="checkbox" @checked($order->validated == 'validated' && $order->validated !== null) @disabled($order->collection === 'collected' || $order->received == 'not_received' || $order->received == null) data-delivery="{{ $order->delivery_id }}">
                                    <span class="badge {{ $order->validated === 'not_validated' || $order->validated == null ? 'bg-warning' : 'bg-success' }}"> {{ $order->validated == null || $order->validated === 'not_validated' ? 'Not Validated' : 'Validated' }}</span>
                                </div>
                            </td>

                            <td>
                                <a class="link-info d-block fs-sm" href="{{ route('clerk.traceTea', $order->delivery_id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="Trace Tea"><span class="fa fa-info"></span> </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>

    </div>
    <script>
        window.routes = {
            receivedEntry: "{{ route('clerk.receive-entry', ':deliveryId') }}",
            validatedEntry: "{{ route('clerk.validate-entry', ':deliveryId') }}",
        };

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

    </script>

    <script>
        $(document).ready(function() {
            $('#datatable').DataTable( {
                order: [ 0, 'asc' ],
                pageLength: 100
            } );

            $(document).on('change', '.received-toggle', function () {
                const checkbox = $(this);
                const deliveryId = checkbox.data('delivery');
                const url = window.routes.receivedEntry.replace(':deliveryId', deliveryId);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        received: checkbox.is(':checked')
                    },
                    success(response) {
                        console.log(response);
                        window.location.reload();
                    },
                    error(xhr) {
                        alert(xhr.responseJSON?.message ?? 'Request failed');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    }
                });
            });

            $(document).on('change', '.validated-toggle', function () {
                const checkbox = $(this);
                const deliveryId = checkbox.data('delivery');
                const url = window.routes.validatedEntry.replace(':deliveryId', deliveryId);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        validated: checkbox.is(':checked')
                    },
                    success(response) {
                        console.log(response);
                        window.location.reload();
                    },
                    error(xhr) {
                        alert(xhr.responseJSON?.message ?? 'Request failed');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    }
                });
            });
        } );
    </script>
@endsection
