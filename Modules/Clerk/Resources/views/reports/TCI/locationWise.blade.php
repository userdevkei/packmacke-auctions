@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">TCIs Under Collection - {{ $teas[0]->location_name }} </h5>
                </div>
               {{-- <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-primary btn-sm" href="{{ route('admin.downloadLocationPendingTCIs', base64_encode($teas[0]->location_id.':'.'1')) }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Export Pdf</span></a>
                        <a class="btn btn-falcon-danger btn-sm" href="{{ route('admin.downloadLocationPendingTCIs', base64_encode($teas[0]->location_id.':'.'2')) }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Export Excel</span></a>
                    </div>
                </div>--}}
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Report</span></a>
                    </div>
                </div>

                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">CREATE A CUSTOM REPORT</h5>
                                </div>
                                <div class="p-4">

                                    <form method="POST" action="{{ route('clerk.downloadLocationPendingTCIs', base64_encode($teas[0]->location_id.':'.'1')) }}" target="_blank">
                                        @csrf
                                        <div class="row row-cols-sm-1 g-2">
                                            <div class="mb-4">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">TCI NUMBER</label>
                                                <select name="loadingNumber[]" class="form-select js-choice " multiple style="height: 61% !important;">
                                                    @foreach($teas->groupBy('loading_number') as $tciNumber => $tcis)
                                                        <option value="{{ $tciNumber }}">{{ $tciNumber }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">REPORT FORMAT</label>
                                                <select name="reportType" class="form-select js-choice">
                                                    <option value="" disabled selected>-- select report format --</option>
                                                    <option value="1">PDF DOCUMENT</option>
                                                    <option value="2">EXCEL DOCUMENT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center mt-1">
                                            <button type="submit" class="btn btn-success col-8">DOWNLOAD REPORT </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
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
                            <th>TCI Number</th>
                            <th>Client Name</th>
                            <th>Invoice Number</th>
                            <th>Lot Number</th>
                            <th>Grade Name</th>
                            <th>Garden Name</th>
                            <th>Total Packages</th>
                            <th>Total Weight</th>
                            <th>Destination</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($teas as $tea)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $tea->loading_number }}</td>
                                <td> {{ $tea->client_name }}</td>
                                <td> {{ $tea->invoice_number }}</td>
                                <td> {{ $tea->lot_number }}</td>
                                <td> {{ $tea->grade_name }}</td>
                                <td> {{ $tea->garden_name }}</td>
                                <td> {{ number_format($tea->packet, 0) }}</td>
                                <td> {{ number_format($tea->weight, 2) }}</td>
                                <td> {{ $tea->station_name }}</td>
                                <td>
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
