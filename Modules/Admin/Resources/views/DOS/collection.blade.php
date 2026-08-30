@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">

<meta name="csrf-token" content="{{ csrf_token() }}">

@section('admin::dashboard')
    <div class="card">
        <div class="card-header mb-0">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Tea Collection </h5>
                </div>
                @if(auth()->user()->role_id == 5)
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div id="table-simple-pagination-replace-element">
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('admin.addTCI') }}" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New</span></a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="">
                <form method="POST" action="">
                    @csrf
                    <div class="row row-cols-3">
                        <div class="">
                            <input type="date" class="form-control" name="from" value="{{ Carbon\Carbon::parse($from)->format('Y-m-d') }}">
                        </div>
                        <div class="">
                            <input type="date" class="form-control" name="to" value="{{ Carbon\Carbon::parse($to)->format('Y-m-d') }}">
                        </div>
                        <div class="">
                            <button type="submit" class="btn btn-sm btn-info">filter</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 fs-sm table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>TCI #</th>
                            <th>Client Name</th>
                            <th>Packages</th>
                            <th>Total Weight</th>
                            <th>Producer Warehouse</th>
                            <th>SubWarehouse</th>
                            <th>Whs Location</th>
                            <th>Transporter</th>
                            <th>Issued To Driver</th>
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
                                <td>{{ $order->transporter_name }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <input class="form-check-input m-0 collection-toggle" id="tciIssue{{ $order->loading_number }}" type="checkbox" @checked($order->collection !== 'in_hand') @disabled($order->collection === 'collected') data-loading-number="{{ $order->loading_number }}">
                                        <span class="badge {{ $order->collection === 'in_hand' ? 'bg-warning' : 'bg-success' }}"> {{ $order->collection === 'in_hand' ? 'Not Issued' : 'Issued' }}</span>
                                    </div>
                                </td>

                                <td>
                                   <div class="dropdown font-sans-serif position-static" >
                                        <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                            <span class="fas fa-ellipsis-h fs-10"></span>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end border py-0">
                                            <div class="py-2">
                                                <a class="dropdown-item text-info" href="{{ route('admin.viewTciDetails', base64_encode($order->loading_number)) }}">View TCI</a>
                                                <a class="dropdown-item text-warning" href="{{ route('admin.amendTciDetails', base64_encode($order->loading_number)) }}">Amend TCI</a>
                                                <a class="dropdown-item text-dark" href="{{ route('admin.downloadLLI', base64_encode($order->loading_number.':'.'1')) }}" target="_blank">Download PDF </a>
                                                <a class="dropdown-item text-secondary" href="{{ route('admin.downloadLLI', base64_encode($order->loading_number.':'.'2')) }}" target="_blank">Download Excel </a>
                                                @if($order->status < 2)
                                                    <a class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete TCI NO. {{ $order->loading_number }}?')" href="{{ route('admin.revertTCI', base64_encode($order->loading_number)) }}">Delete DO</a>
                                                @endif
                                            </div>
                                        </div>
                                   </div>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
    <script>
        window.routes = {
            issueOrder: "{{ route('admin.orders.issue', ':loadingNumber') }}"
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
