@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<style>
    .filter-panel { background: #f9fafb; border: 1px solid #edf2f9; border-radius: .5rem; padding: 1rem 1rem .75rem; margin-bottom: 1rem; }
    .filter-panel .form-label { font-size: .72rem; font-weight: 600; color: #5e6e82; text-transform: uppercase; letter-spacing: .02em; margin-bottom: .25rem; }
    .filter-panel .form-control, .filter-panel .form-select { font-size: .875rem; height: calc(1.5em + .75rem + 2px); }
    .active-filter-chip { display: inline-flex; align-items: center; gap: .35rem; background: #e7edff; color: #2054C9; border-radius: 2rem; padding: .2rem .6rem .2rem .75rem; font-size: .75rem; font-weight: 500; margin: 0 .35rem .35rem 0; text-decoration: none; }
    .active-filter-chip .chip-x { font-weight: 700; opacity: .6; }
    .active-filter-chip .chip-x:hover { opacity: 1; }
    .toolbar-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; margin-bottom: .5rem; }
    .toolbar-row .toolbar-left, .toolbar-row .toolbar-right { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
</style>
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Straight Line Shipping </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
{{--                        @if(auth()->user()->role_id == 3)--}}
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('admin.createSI') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Create SI</span></a>
                            <a class="btn btn-falcon-danger btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-cloud-download-alt" data-fa-transform=""></span><span class="d-none d-sm-inline-block ms-1">Report</span></a>
{{--                        @endif--}}
                    </div>
                </div>
                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">GENERATE STRAIGHTLINE REPORT</h5>
                                </div>
                                <div class="p-4">
                                    <form method="GET" action="{{ route('admin.exportSTLReport') }}">
                                        @csrf
                                            <div class="row row-cols-sm-2 g-2">

                                                <div class="mb-2">
                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">CLIENT NAME</label>
                                                    <select name="client" id="clientInput" class="form-select js-choice">
                                                        <option selected disabled value="">-- select client --</option>
                                                        @foreach($clients as $clientName => $client)
                                                            <option value="{{ $client[0]->client_id }}">{{ $clientName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">STATION NAME</label>
                                                    <select name="station" id="stationInput" class="form-select js-choice">
                                                        <option selected disabled value="">-- select station --</option>
                                                        @foreach($stations as $stationName => $station)
                                                            <option value="{{ $station[0]->station_id }}">{{ $stationName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>


                                                <div class="mb-2 date-input-container">
                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DATE FROM</label>
                                                    <input type="date" id="monthAgo" value="" name="from" class="form-control date-input" style="height: 62% !important;">
                                                </div>

                                                <div class="mb-2 date-input-container">
                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DATE TO</label>
                                                    <input type="date"  id="todayDate" name="to" class="form-control date-input" style="height: 62% !important;">
                                                </div>
                                            </div>

                                            <div class="mt-2 fs-sm d-flex justify-content-center">
                                                <input class="mx-2" type="radio" name="report" value=""> <span class="text-info fw-bolder">ALL STL</span>
                                                <input class="mx-2" type="radio" name="report" value="1"> <span class="text-primary fw-bolder">STL PENDING</span>
                                                <input class="mx-2" type="radio" name="report" value="2"> <span class="text-secondary fw-bolder">STL SHIPPED </span>
                                            </div>
                                            <div class="mt-2 d-flex justify-content-center">
                                                <button type="submit" class="btn btn-dark col-7">DOWNLOAD</button>
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
                    <div class="toolbar-row">
                        <div class="toolbar-left">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#siFilterPanelCollapse">
                                <span class="fas fa-filter me-1"></span>Filters
                                <span class="fas fa-chevron-down ms-1"></span>
                            </button>
                            @foreach($activeFilters as $chip)
                                <a class="active-filter-chip" href="{{ route('admin.viewShippingInstructions', collect($filters)->except($chip['key'])->toArray()) }}">
                                    <span>{{ $chip['label'] }}: {{ $chip['display'] }}</span>
                                    <span class="chip-x">&times;</span>
                                </a>
                            @endforeach
                        </div>
                        <div class="toolbar-right">
                            @php
                                $csvUrl = route('admin.exportShippingLines', array_merge($filters, ['format' => 'csv']));
                                $pdfUrl = route('admin.exportShippingLines', array_merge($filters, ['format' => 'pdf']));
                                $csvUrlConfirmed = route('admin.exportShippingLines', array_merge($filters, ['format' => 'csv', 'confirm_full' => 1]));
                                $pdfUrlConfirmed = route('admin.exportShippingLines', array_merge($filters, ['format' => 'pdf', 'confirm_full' => 1]));
                                $noFilters = empty($filters);
                            @endphp
                            <a href="{{ $noFilters ? $csvUrlConfirmed : $csvUrl }}" target="_blank" class="btn btn-sm btn-outline-success"
                               @if($noFilters) onclick="return confirm('No filters are applied — this will export every shipping line, which can be slow. Continue anyway?')" @endif>
                                <span class="fas fa-file-excel me-1"></span>Export Excel/CSV
                            </a>
                            <a href="{{ $noFilters ? $pdfUrlConfirmed : $pdfUrl }}" target="_blank" class="btn btn-sm btn-outline-danger"
                               @if($noFilters) onclick="return confirm('No filters are applied — this will export every shipping line, which can be slow. Continue anyway?')" @endif>
                                <span class="fas fa-file-pdf me-1"></span>Export PDF
                            </a>
                        </div>
                    </div>

                    <div class="collapse @if(!empty($filters)) show @endif" id="siFilterPanelCollapse">
                        <form method="GET" action="{{ route('admin.viewShippingInstructions') }}" class="filter-panel">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Shipping Number</label>
                                    <input type="text" name="shipping_number" class="form-control" placeholder="e.g. SH1234" value="{{ $filters['shipping_number'] ?? '' }}">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Client Name</label>
                                    <select name="client_id" class="form-select js-choice">
                                        <option value="">All Clients</option>
                                        @foreach($clients as $clientName => $client)
                                            <option value="{{ $client[0]->client_id }}" @selected(($filters['client_id'] ?? null) == $client[0]->client_id)>{{ $clientName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select js-choice">
                                        <option value="">All Statuses</option>
                                        @foreach($statusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected(($filters['status'] ?? null) == $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Source (Station)</label>
                                    <select name="source" class="form-select js-choice">
                                        <option value="">All Sources</option>
                                        @foreach($stations as $stationName => $station)
                                            <option value="{{ $station[0]->station_id }}" @selected(($filters['source'] ?? null) == $station[0]->station_id)>{{ $stationName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Destination</label>
                                    <select name="destination_id" class="form-select js-choice">
                                        <option value="">All Destinations</option>
                                        @foreach($destinations as $destination)
                                            <option value="{{ $destination->destination_id }}" @selected(($filters['destination_id'] ?? null) == $destination->destination_id)>{{ $destination->port_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.viewShippingInstructions') }}" class="btn btn-sm btn-falcon-default">
                                        <span class="fas fa-undo me-1"></span>Reset
                                    </a>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <span class="fas fa-filter me-1"></span>Apply Filters
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Date Initiated </th>
                            <th>Client Name</th>
                            <th>Shipping Number </th>
                            <th>Vessel Name</th>
                            <th>Destination</th>
                            <th>Warehouse</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($shipping as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</td>
                                <td>{{ $transfer->client_name }}</td>
                                <td>{{ $transfer->shipping_number }}</td>
                                <td>{{ $transfer->vessel_name }}</td>
                                <td>{{ $transfer->port_name }}</td>
                                <td>{{ $transfer->station_name }}</td>
                                <td>
                                    {!! $transfer->status == 0 ? '<span class="badge bg-warning"> SI Created </span>' : ($transfer->status == 1 ? '<span class="badge bg-info"> Teas Updated </span>' : ($transfer->status == 2 ? '<span class="badge bg-secondary"> SI Updated </span>' : ($transfer->status == 3 ? '<span class="badge bg-dark"> Pend. Approval </span>' : '<span class="badge bg-success"> Shipped </span>'))) !!}
                                </td>
                                <td nowrap="">
                                    <div class="d-flex align-items-center">
                                        @if($transfer->status == 0)
                                            <a class="link text-warning"  onclick="return confirm('Are you sure you want to initiate SI?')" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to initiate SI" href="{{ route('admin.initateSI', $transfer->shipping_id) }}"><span class="fa-solid fa-share-from-square" ></span></a>
                                        @elseif($transfer->status == 1)
                                                <a class="link text-info" data-bs-placement="left" title="Click to update SI" data-bs-toggle="modal" data-bs-target="#staticBackdrop-{{ $transfer->shipping_id }}"><span class="fa-solid fa-file-pen"></span></a>

                                                <div class="modal fade" id="staticBackdrop-{{ $transfer->shipping_id }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl modal-dialog-centered mt-6" role="document">
                                                        <div class="modal-content border-0">
                                                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body p-0">
                                                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                                    <h5 class="mb-1" id="staticBackdropLabel">UPDATE SHIPPING INSTRUCTION SI NO: {{ $transfer->shipping_number }}</h5>
                                                                </div>
                                                                <div class="p-4">
                                                                    <form class="form" method="POST" action="{{ route('admin.updateShippingInstructionDetails', $transfer->shipping_id) }}">
                                                                        @csrf
                                                                        <div class="row row-cols-sm-3 g-2">
                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">CONTAINER NUMBER</label>
                                                                                <input type="text" name="containerNumber" class="form-control form-control-lg" placeholder="--" style="height: 62% !important;" required>
                                                                            </div>
                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">CONTAINER TARE</label>
                                                                                <input type="number" step="0.01" name="tare" class="form-control form-control-lg" placeholder="--" style="height: 62% !important;" required>
                                                                            </div>
                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">CLEARING AGENT</label>
                                                                                <select name="agent" class="form-select js-choice" required data-options='{"removeItemButton":true,"placeholder":true}'>
                                                                                    <option selected disabled value="">-- select clearing agent -- </option>
                                                                                    @foreach($agents as $agent)
                                                                                        <option value="{{ $agent->agent_id }}">{{ $agent->agent_name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>

                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">SEAL NUMBER</label>
                                                                                <input type="text" name="seal" class="form-control form-control-lg" placeholder="--" style="height: 62% !important;" required>
                                                                            </div>

                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">CARGO ESCORTED?</label>
                                                                                <select name="escort" class="form-select js-choice" required data-options='{"removeItemButton":true,"placeholder":true}'>
                                                                                    <option selected disabled value="">-- select option -- </option>
                                                                                    <option value="1">YES</option>
                                                                                    <option value="2">NO</option>
                                                                                </select>
                                                                            </div>

                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">TRANSPORTER</label>
                                                                                <select name="transporter" class="form-select js-choice" required data-options='{"removeItemButton":true,"placeholder":true}'>
                                                                                    <option selected disabled value="">-- select transporter -- </option>
                                                                                    @foreach($transporters as $transporter)
                                                                                        <option value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>

                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>
                                                                                <input class="form-control form-control-lg" name="registration" id="editableSelect" type="text" list="optionsList" placeholder="-- plate number --" required>
                                                                                <datalist id="optionsList">
                                                                                    @foreach($registrations as $registration => $transporter)
                                                                                        <option value="{{ $registration }}">{{ $registration }} </option>
                                                                                    @endforeach
                                                                                </datalist>
                                                                            </div>

                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>
                                                                                <input id="idSelect" type="text" list="idList" name="idNumber" class="form-control form-control-lg idSelect" placeholder="-- driver's ID Number --" required>
                                                                                <datalist id="idList">
                                                                                    @foreach($users as $user)
                                                                                        <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                                                                                    @endforeach
                                                                                </datalist>
                                                                            </div>

                                                                            <div class="mb-2">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>
                                                                                <input type="text" name="driverName" id="driverName" class="form-control form-control-lg driverName" required>
                                                                            </div>

                                                                            <div class="mb-4">
                                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>
                                                                                <input type="text" name="driverPhone" id="driverPhone" class="form-control form-control-lg driverPhone" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="d-flex justify-content-center mt-2">
                                                                            <button type="submit" class="btn btn-success" onclick="return confirm('Once submitted you can not change shipping instruction. Are you sure you want to proceed?')">UPDATE SHIPPING INSTRUCTION</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        {{--@elseif($transfer->status == 2)
                                            @if($transfer->location_id == auth()->user()->station->location->location_id && auth()->user()->role_id == 3)
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Send SI for approval" onclick="return confirm('Are you sure you want to send this SI for approval?')" href="{{ route('admin.updateShippingInstruction', $transfer->shipping_id) }}"><span class="fa-regular fa-paper-plane"></span></a>
                                            @else
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="SI updated, pending submission for approval"><span class="fa-solid fa-spinner"></span></a>
                                            @endif--}}

                                        @elseif($transfer->status == 2 || $transfer->status == 3)
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="SI pending confirmation and shipping" onclick="return confirm('Are you sure you want to approve this SI? This will mark SI as shipped')" href="{{ route('admin.markAsShipped', $transfer->shipping_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                        @elseif($transfer->status == 4)
                                            <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="SI shipped, stock updated"><span class="fa-solid fa-check-double"></span></a>
                                        @endif
                                        <div class="dropdown font-sans-serif position-static" >
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('admin.addShipmentTeas', $transfer->shipping_id) }}">View SI</a>
                                                    <a class="dropdown-item text-warning" href="{{ route('admin.editSI', $transfer->shipping_id) }}">Edit SI Sheet</a>
                                                    @if($transfer->status < 4)
                                                        <a class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete SI Number {{ $transfer->shipping_number }}?')" href="{{ route('admin.deleteShippingInstruction', $transfer->shipping_id) }}"> Delete SI</a>
                                                    @endif
                                                    @if($transfer->status >= 1)
                                                        <a class="dropdown-item text-primary" href="{{ route('admin.downloadSIDocument', $transfer->shipping_id) }}" target="_blank">Download SI</a>
                                                        <a class="dropdown-item text-dark" href="{{ route('admin.downloadDriverClearance', $transfer->shipping_id) }}" target="_blank"> Port Delivery Note</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIPackingList', base64_encode($transfer->shipping_id.':'.$transfer->load_type)) }}" target="_blank">Packing List</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIPackingListExcel', base64_encode($transfer->shipping_id.':'.$transfer->load_type)) }}" target="_blank">Packing List (Excel)</a>
                                                        @if($transfer->load_type == 2)
                                                            <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIContinuedPackingList', base64_encode($transfer->si_number) ?? base64_encode($transfer->shipping_number)) }}" target="_blank">Packing List (Cont.) </a>
                                                            <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIContinuedPackingListExcel', base64_encode($transfer->si_number) ?? base64_encode($transfer->shipping_number)) }}" target="_blank">Packing List (Cont.) (Excel)</a>
                                                        @endif
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
{{--<script src="https://code.jquery.com/jquery-3.7.1.js"></script>--}}
{{-- <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script> --}}
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

    });

        document.addEventListener("DOMContentLoaded", function () {
            var input = document.getElementById("editableSelect");
            new Awesomplete(input, {
                list: "#optionsList",
                autoFirst: false, // Automatically select the first option
                minChars: 2,
                filter: function (text, input) {
                    return Awesomplete.FILTER_CONTAINS(text, input.match(/[^,]*$/)[0]);
                },
                replace: function (text) {
                    this.input.value = text; // Replace entire input value with selected option
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            var input = document.getElementById("idSelect");
            new Awesomplete(input, {
                list: "#idList",
                autoFirst: true, // Automatically select the first option
                minChars: 3,
                filter: function (text, input) {
                    return Awesomplete.FILTER_CONTAINS(text, input.match(/[^,]*$/)[0]);
                },
                replace: function (text) {
                    this.input.value = text; // Replace entire input value with selected option
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            var input = document.getElementById("editableSelected");
            new Awesomplete(input, {
                list: "#optionsListed",
                autoFirst: false, // Automatically select the first option
                minChars: 2,
                filter: function (text, input) {
                    return Awesomplete.FILTER_CONTAINS(text, input.match(/[^,]*$/)[0]);
                },
                replace: function (text) {
                    this.input.value = text; // Replace entire input value with selected option
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            var input = document.getElementById("idSelected");
            new Awesomplete(input, {
                list: "#idListed",
                autoFirst: true, // Automatically select the first option
                minChars: 3,
                filter: function (text, input) {
                    return Awesomplete.FILTER_CONTAINS(text, input.match(/[^,]*$/)[0]);
                },
                replace: function (text) {
                    this.input.value = text; // Replace entire input value with selected option
                }
            });
        });

        $(document).ready(function () {
            $('.idSelect').on('change', function () {

                var idNumber = $(this).val();

                $.ajax({
                    url: '{{ route('admin.fetchIdNumber') }}',
                    method: 'GET',
                    data: {idNumber},
                    dataType: 'json',
                    success: function (response) {
                        console.log('Success:', response.driver_name);

                        $('.driverName').val(response.driver_name)
                        $('.driverPhone').val(response.driver_phone)
                    },
                    error: function (xhr, status, error) {
                        // Function to handle errors
                        console.error('Error:', error);
                        $('#driverName').val('')
                        $('#driverPhone').val('')
                    }
                });
            });
        });
</script>
