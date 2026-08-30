@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Rebagging Job For SI/Blend NO. {{ $data->shipping_number ?? $data->blend_number }} </h5>
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
                    <table class="table mb-0 table-bordered fs-sm table-sm table-striped datatable" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Invoice Number</th>
                            <th>Garden Name</th>
                            <th>Grade Name</th>
                            <th>Packages</th>
                            <th>Weight</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($lines as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $transfer->invoice_number }}</td>
                                <td>{{ $transfer->garden_name }}</td>
                                <td>{{ $transfer->grade_name }}</td>
                                <td>{{ number_format($transfer->packages, 0) }}</td>
                                <td>{{ number_format($transfer->weight, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 100
        });
    });
</script>
