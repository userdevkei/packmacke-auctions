@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-11 mb-0 text-nowrap py-0 py-xl-0">Delivery Orders </h5>
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
                            <th>Inv No</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Lot No</th>
                            <th>Packages</th>
                            <th>Weight</th>
                            <th>Producer Whs</th>
                            <th></th>
                        </tr>
                        </thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $order->invoice_number }}</td>
                            <td>{{ $order->garden_name }}</td>
                            <td nowrap="">{{ $order->grade_name }}</td>
                            <td>{{ $order->lot_number }}</td>
                            <td>{{ $order->packet }}</td>
                            <td>{{ $order->weight }}</td>
                            <td>{{ $order->warehouse_name }}</td>
                            <td>
                                <a class="link-info d-block fs-sm" href="{{ route('client.traceTea', $order->delivery_id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="Trace Tea"><span class="fa fa-info"></span> </a>
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
    $(document).ready(function() {
        $('#datatable').DataTable( {
            order: [ 0, 'asc' ],
            pageLength: 100
        } );
    } );
</script>

@endsection
