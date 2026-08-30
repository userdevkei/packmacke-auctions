@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Rebagging Jobs </h5>
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
                    <table class="table mb-0 table-bordered fs-sm table-sm table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>SI/Blend Number </th>
                            <th>Rebagging On</th>
                            <th>Client Name</th>
                            <th>Total Packages</th>
                            <th>Total Weight</th>
                            <th>Initiator</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bags as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $transfer->siNumber }}</td>
                                <td>{{ $transfer->type }}</td>
                                <td>{{ $transfer->clientName }}</td>
                                <td>{{ number_format($transfer->packages, 0) }}</td>
                                <td>{{ number_format($transfer->weight, 2) }}</td>
                                <td>{{ ucwords(strtolower($transfer->username)) }}</td>
                                <td>
                                    {!! $transfer->status <3 ? '<span class="badge bg-dark"> Pend. Shipping </span>' : '<span class="badge bg-success"> Shipped </span>' !!}
                                </td>
                                <td>
                                    <a class="link-primary" href="{{ route('client.viewRebaggedTeas', $transfer->shippingId) }}">view</a> |
                                </td>
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
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 100
        });
    });
</script>
