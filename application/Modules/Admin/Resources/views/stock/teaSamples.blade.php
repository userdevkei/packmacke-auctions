@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Tea Weight Discrepancies </h5>
                </div>

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered fs-sm table-sm table-striped" id="datatable" style="width: 100% !important;">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>Inv No</th>
                            <th>Lot Number</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Palletes</th>
                            <th>Weight</th>
                            <th>Reason </th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($samples as $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order->client_name }}</td>
                                <td>{{ $order->invoice_number }}</td>
                                <td>{{ $order->lot_number }}</td>
                                <td>{{ $order->garden_name }}</td>
                                <td>{{ $order->grade_name }}</td>
                                <td>{{ $order->sample_palletes }}</td>
                                <td>{{ $order->sample_weight }}</td>
                                <td>{!! $order->type == 1 ? 'Withdrawn Sample' : ($order->type == 2 ? 'Damaged Bags' : ($order->type == 3 ? 'Weight Loss' : ($order->type = 4 ? 'Received Partially' : ''))) !!}</td>
                                <td>
                                    <a class="link link-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Restore to stock" onclick="confirm('Are you sure you want to restore this line to stock')" href="{{ route('clerk.restoreSample', $order->sample_id) }}"><i class="fa fa-undo"></i> </a>
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
        $(document).ready(function() {
            $('#datatable').DataTable( {
                order: [ 0, 'asc' ],
                pageLength: 50
            } );
        } );
    </script>

@endsection
