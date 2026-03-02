@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Blend Jobs </h5>
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
                            <th>Date Initiated </th>
                            <th>Shipping Number </th>
                            <th>Vessel Name</th>
                            <th>Destination</th>
                            <th>Shipped Packages</th>
                            <th>Shipped Weight</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sheets as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</td>
                                <td>{{ $transfer->blend_number }}</td>
                                <td>{{ $transfer->vessel_name }}</td>
                                <td>{{ $transfer->port_name }}</td>
                                <td>{{ number_format($transfer->blended_packages, 2) }}</td>
                                <td>{{ number_format($transfer->blended_weight, 2) }}</td>
                                <td>
                                    {!! $transfer->status < 3 ? '<span class="badge bg-dark"> Pending </span>' : '<span class="badge bg-success"> Shipped </span>' !!}
                                </td>
                                <td nowrap="">
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown font-sans-serif position-static" >
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('client.addBlendTeas', $transfer->blend_id) }}">View Blend Sheet</a>
                                                    <a class="dropdown-item text-primary" href="{{ route('client.downloadBlendSheet', $transfer->blend_id) }}" target="_blank">Download Blend Sheet</a>
                                                    @if($transfer->status >= 2)
                                                        <a class="dropdown-item text-dark" href="{{ route('client.downloadOutturReport', $transfer->blend_id) }}" target="_blank"> Download Outturn Report</a>
                                                    @endif
                                                </div>
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
