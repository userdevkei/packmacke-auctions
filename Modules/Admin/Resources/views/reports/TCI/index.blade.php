@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">TCIs Under Collection (Location Wise) </h5>
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
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Location Name</th>
                            <th>Total Packages</th>
                            <th>Total Weight</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tcis as $tci)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $tci->location_name }}</td>
                                <td> {{ number_format($tci->total_packages, 0) }}</td>
                                <td> {{ number_format($tci->total_weight, 2) }}</td>
                                <td>

                                    <div class="dropdown font-sans-serif position-static" >
                                        <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                            <span class="fas fa-ellipsis-h fs-10"></span>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end border py-0">
                                            <div class="py-2">
                                                <a class="dropdown-item text-info" href="{{ route('admin.viewLocationPendingTCIs', $tci->location_id) }}">View Location</a>
                                                <a class="dropdown-item text-primary" href="{{ route('admin.downloadLocationPendingTCIs',  base64_encode($tci->location_id.':'.'1')) }}" target="_blank">Download PDF</a>
                                                <a class="dropdown-item text-secondary" href="{{ route('admin.downloadLocationPendingTCIs', base64_encode($tci->location_id.':'.'2')) }}">Download Excel</a>
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
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
