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
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('admin.createSI') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Create SI</span></a>
                            <a class="btn btn-falcon-danger btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-cloud-download-alt" data-fa-transform=""></span><span class="d-none d-sm-inline-block ms-1">Report</span></a>
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
                                    {!! $transfer->status == 0 ? '<span class="badge bg-warning"> SI Created </span>'
                                        : ($transfer->status == 1 ? '<span class="badge bg-dark"> Initiated </span>'
                                        : ($transfer->status == 2 ? '<span class="badge bg-info"> 1st Approved </span>'
                                        : ($transfer->status == 3 ? '<span class="badge bg-primary"> Pend. Final Approval </span>'
                                        : '<span class="badge bg-success"> Shipped </span>'))) !!}
                                </td>
                                <td nowrap="">
                                    <div class="d-flex align-items-center">
                                        @if($transfer->status == 0)
                                            <a class="link text-warning"  onclick="return confirm('Are you sure you want to initiate SI?')" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to initiate SI" href="{{ route('admin.initateSI', $transfer->shipping_id) }}"><span class="fa-solid fa-share-from-square" ></span></a>
                                        @elseif($transfer->status == 1)
                                            <a class="link text-warning" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to give first approval" onclick="return confirm('Are you sure you want to approve this SI?')" href="{{ route('admin.approveSIFirst', $transfer->shipping_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                        @elseif($transfer->status == 2)
                                            <a class="link text-info js-open-transport-modal" href="javascript:void(0)" data-bs-placement="left" title="Click to update transport details and submit for approval"
                                               data-shipping-id="{{ $transfer->shipping_id }}"
                                               data-shipping-number="{{ $transfer->shipping_number }}"
                                               data-container-number="{{ $transfer->container_number ?? '' }}"
                                               data-container-tare="{{ $transfer->container_tare ?? '' }}"
                                               data-seal-number="{{ $transfer->seal_number ?? '' }}"
                                               data-escorted="{{ $transfer->escort ?? '' }}"
                                               data-agent-id="{{ $transfer->agent_id ?? '' }}"
                                               data-transporter-id="{{ $transfer->transporter_id ?? '' }}"
                                               data-registration="{{ $transfer->registration ?? '' }}"
                                               data-id-number="{{ $transfer->id_number ?? '' }}"
                                               data-driver-name="{{ $transfer->driver_name ?? '' }}"
                                               data-driver-phone="{{ $transfer->phone ?? '' }}"
                                               data-action-url="{{ route('admin.updateShippingInstructionDetails', $transfer->shipping_id) }}"
                                            ><span class="fa-solid fa-file-pen"></span></a>
                                        @elseif($transfer->status == 3)
                                            <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Give final approval — marks as shipped" onclick="return confirm('Are you sure you want to approve this SI? This will mark SI as shipped')" href="{{ route('admin.approveSIFinal', $transfer->shipping_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                        @else
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

                                                    @if($transfer->status >= 2)
                                                        <a class="dropdown-item text-info js-open-transport-modal" href="javascript:void(0)"
                                                           data-shipping-id="{{ $transfer->shipping_id }}"
                                                           data-shipping-number="{{ $transfer->shipping_number }}"
                                                           data-container-number="{{ $transfer->container_number ?? '' }}"
                                                           data-container-tare="{{ $transfer->container_tare ?? '' }}"
                                                           data-seal-number="{{ $transfer->seal_number ?? '' }}"
                                                           data-escorted="{{ $transfer->escort ?? '' }}"
                                                           data-agent-id="{{ $transfer->agent_id ?? '' }}"
                                                           data-transporter-id="{{ $transfer->transporter_id ?? '' }}"
                                                           data-registration="{{ $transfer->registration ?? '' }}"
                                                           data-id-number="{{ $transfer->id_number ?? '' }}"
                                                           data-driver-name="{{ $transfer->driver_name ?? '' }}"
                                                           data-driver-phone="{{ $transfer->phone ?? '' }}"
                                                           data-action-url="{{ route('admin.updateShippingInstructionDetails', $transfer->shipping_id) }}"
                                                        >Amend Transport Details</a>
                                                    @endif

                                                    @if($transfer->status < 4)
                                                        <a class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete SI Number {{ $transfer->shipping_number }}?')" href="{{ route('admin.deleteShippingInstruction', $transfer->shipping_id) }}"> Delete SI</a>
                                                    @endif
                                                    @if($transfer->status >= 2)
                                                        <a class="dropdown-item text-primary" href="{{ route('admin.downloadSIDocument', $transfer->shipping_id) }}" target="_blank">Download SI</a>
                                                        <a class="dropdown-item text-dark" href="{{ route('admin.downloadDriverClearance', $transfer->shipping_id) }}" target="_blank"> Port Delivery Note</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIPackingList', base64_encode($transfer->shipping_id.':'.$transfer->load_type)) }}" target="_blank">Packing List</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIPackingListExcel', base64_encode($transfer->shipping_id.':'.$transfer->load_type)) }}" target="_blank">Packing List (Excel)</a>
                                                        @if($transfer->load_type == 2 || $transfer->load_type == 3)
                                                            <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIContinuedPackingList', base64_encode($transfer->si_number ?? $transfer->shipping_number)) }}" target="_blank">Packing List (Cont.) </a>
                                                            <a class="dropdown-item text-secondary" href="{{ route('admin.downloadSIContinuedPackingListExcel', base64_encode($transfer->si_number ?? $transfer->shipping_number)) }}" target="_blank">Packing List (Cont.) (Excel) </a>
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
    {{-- ==========================================================================
         Shared datalists — rendered ONCE for the whole page, not per row.
    ========================================================================== --}}
    <datalist id="optionsList">
        @foreach($registrations as $registration)
            <option value="{{ $registration }}">{{ $registration }}</option>
        @endforeach
    </datalist>
    <datalist id="idList">
        @foreach($users as $user)
            <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
        @endforeach
    </datalist>

    {{-- ==========================================================================
         ONE shared modal for every row, instead of one modal per shipping
         instruction (previously rendered inside the row @foreach, causing
         PHP memory exhaustion on large tables and thousands of duplicate
         Choices.js instances freezing the browser — same issue fixed on the
         clerk side). Clicking either trigger above fills these fields via
         JS instead of the server rendering a brand new modal per row.
    ========================================================================== --}}
    <div class="modal fade" id="transportModal" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="transportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered mt-6" role="document">
            <div class="modal-content border-0">
                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                        <h5 class="mb-1" id="transportModalLabel">UPDATE SHIPPING INSTRUCTION SI NO: <span id="transportModalSiNumber"></span></h5>
                    </div>
                    <div class="p-4">
                        <form class="form" method="POST" id="transportModalForm" action="">
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
                                    <select name="agent" class="form-select" required>
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
                                    <select name="escort" class="form-select" required>
                                        <option selected disabled value="">-- select option -- </option>
                                        <option value="1">YES</option>
                                        <option value="2">NO</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">TRANSPORTER <sup class="text-danger">*</sup></label>
                                    <select name="transporter" class="form-select" required>
                                        <option selected disabled value="">-- select transporter -- </option>
                                        @foreach($transporters as $transporter)
                                            <option value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>
                                    <input class="form-control form-control-lg js-registration-input" type="text" name="registration" list="optionsList" placeholder="-- plate number --" required>
                                </div>
                                <div class="mb-2">
                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>
                                    <input type="text" class="form-control form-control-lg js-id-input" name="idNumber" list="idList" placeholder="-- driver's ID Number --" required>
                                </div>
                                <div class="mb-2">
                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>
                                    <input type="text" name="driverName" class="form-control form-control-lg js-driver-name" required>
                                </div>
                                <div class="mb-4">
                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>
                                    <input type="text" name="driverPhone" class="form-control form-control-lg js-driver-phone" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-2">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Once submitted this SI will be sent for final approval. Are you sure you want to proceed?')">SAVE & SUBMIT FOR APPROVAL</button>
                            </div>
                        </form>
                    </div>
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
            pageLength: 50
        });
    });

    // Instantiate Choices.js ourselves for the 3 modal selects, once, and
    // keep direct references. These selects intentionally do NOT carry the
    // "js-choice" class or "data-options" attribute that theme.js's global
    // auto-init looks for — if they did, theme.js would create a SECOND
    // Choices instance wrapping the same <select>, and our setChoiceByValue
    // calls below would silently update the wrong (invisible) instance.
    // (The report modal's client/station selects above are unaffected by
    // this — they're not duplicated per row, so theme.js's normal auto-init
    // is fine for those and was left untouched.)
    var agentChoices, escortChoices, transporterChoices;

    $(document).ready(function () {
        var choicesOptions = { removeItemButton: true, placeholder: true };
        agentChoices = new Choices(document.querySelector('#transportModal [name="agent"]'), choicesOptions);
        escortChoices = new Choices(document.querySelector('#transportModal [name="escort"]'), choicesOptions);
        transporterChoices = new Choices(document.querySelector('#transportModal [name="transporter"]'), choicesOptions);
    });

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-open-transport-modal');
        if (!trigger) return;

        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap JS is not loaded yet — check that the Bootstrap bundle script tag loads before this script, or move this script to load after it.');
            return;
        }

        var transportModalEl = document.getElementById('transportModal');
        var transportModal = bootstrap.Modal.getOrCreateInstance(transportModalEl);

        var d = trigger.dataset;

        document.getElementById('transportModalSiNumber').textContent = d.shippingNumber || '';
        document.getElementById('transportModalForm').action = d.actionUrl;

        var form = document.getElementById('transportModalForm');
        form.querySelector('[name="containerNumber"]').value = d.containerNumber || '';
        form.querySelector('[name="tare"]').value = d.containerTare || '';
        form.querySelector('[name="seal"]').value = d.sealNumber || '';
        form.querySelector('.js-registration-input').value = d.registration || '';
        form.querySelector('.js-id-input').value = d.idNumber || '';
        form.querySelector('.js-driver-name').value = d.driverName || '';
        form.querySelector('.js-driver-phone').value = d.driverPhone || '';

        setChoiceValue(agentChoices, d.agentId);
        setChoiceValue(escortChoices, d.escorted);
        setChoiceValue(transporterChoices, d.transporterId);

        transportModal.show();
    });

    function setChoiceValue(choicesInstance, value) {
        if (!choicesInstance) return;
        choicesInstance.removeActiveItems();
        if (value) {
            choicesInstance.setChoiceByValue(value);
        }
    }

    // Auto-fill driver name/phone when a driver ID is picked in the modal.
    $(document).on('change', '#transportModal .js-id-input', function () {
        var $modal = $(this).closest('.modal');
        var idNumber = $(this).val();

        $.ajax({
            url: '{{ route('admin.fetchIdNumber') }}',
            method: 'GET',
            data: {idNumber},
            dataType: 'json',
            success: function (response) {
                $modal.find('.js-driver-name').val(response.driver_name);
                $modal.find('.js-driver-phone').val(response.driver_phone);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                $modal.find('.js-driver-name').val('');
                $modal.find('.js-driver-phone').val('');
            }
        });
    });
</script>
