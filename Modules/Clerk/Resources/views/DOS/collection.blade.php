@extends('clerk::layouts.default')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header mb-0">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Tea Collection </h5>
                </div>
                @if(auth()->user()->role_id == 5 || auth()->user()->role_id == 2)
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div id="table-simple-pagination-replace-element">
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('clerk.addTCI') }}" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New</span></a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 fs-sm table-sm table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>TCI #</th>
                            <th>Client Name</th>
                            <th>Packages</th>
                            <th>Total Weight</th>
                            <th>Producer Warehouse</th>
                            <th>SubWarehouse</th>
                            <th >Whs Location</th>
                            <th>Transporter</th>
                            @canuser('do.issueToDriver')
                            <th>Transporter Issued</th>
                            @endcanuser
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($instructions as $tci => $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order->loading_number }}</td>
                                <td>{{ $order->client_name }}</td>
                                <td>{{ number_format($order->packages, 0) }}</td>
                                <td>{{ number_format($order->weight, 2) }}</td>
                                <td>{{ $order->warehouse_name }}</td>
                                <td style="white-space: normal; word-wrap: break-word; word-break: break-word;">{{ $order->sub_warehouse_name }}</td>
                                <td> {{ $order->locality == 1 ? 'ISLAND' : ($order->locality == 2 ? 'CHANGAMWE' : ($order->locality == 3 ? 'JOMVU' : ($order->locality == 4 ? 'BONJE' : 'MIRITINI'))) }} </td>
                                <td style="white-space: normal; word-wrap: break-word; word-break: break-word;">{{ $order->transporter_name }}</td>
                                @canuser('do.issueToDriver')
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <input class="form-check-input m-0 collection-toggle" id="tciIssue{{ $order->loading_number }}" type="checkbox" @checked($order->collection !== 'in_hand') @disabled($order->collection === 'collected') data-loading-number="{{ $order->loading_number }}">
                                        <span class="badge {{ $order->collection === 'in_hand' ? 'bg-warning' : 'bg-success' }}"> {{ $order->collection === 'in_hand' ? 'Not Issued' : 'Issued' }}</span>
                                    </div>
                                </td>
                                @endcanuser
                                <td nowrap="">
                                    <a class="link-info mx-1 fs-sm" data-bs-toggle="tooltip" data-bs-placement="left" title="View TCI Details" href="{{ route('clerk.viewTciDetails', base64_encode($order->loading_number)) }}"><span class="fa fa-info"></span> </a>
                                    <a class="text-secondary mx-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Download TCI" target="_blank" href="{{ route('clerk.downloadLLI', base64_encode($order->loading_number.':'.'1')) }}">
                                        <span class="fas fa-print"></span>
                                    </a>
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
            issueOrder: "{{ route('clerk.orders.issue', ':loadingNumber') }}"
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

            $(document).on('change', '.collection-toggle', function () {
                const checkbox = $(this);
                const loadingNumber = checkbox.data('loading-number');
                const url = window.routes.issueOrder.replace(':loadingNumber', loadingNumber);

                console.log(checkbox.is(':checked'))

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        issued: checkbox.is(':checked')
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
