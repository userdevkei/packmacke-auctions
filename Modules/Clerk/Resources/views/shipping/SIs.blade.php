@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Straight Line Jobs </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @if(@canuser('straightline.create'))
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('clerk.createSI') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Create SI</span></a>
                        @endif
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
                                            @if($transfer->location_id == auth()->user()->station->location->location_id && auth()->user()->role_id == 3 || @canuser('straightline.create'))
                                                <a class="link text-warning" onclick="return confirm('Are you sure you want to initiate SI?')" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to initiate SI" href="{{ route('clerk.initateSI', $transfer->shipping_id) }}"><span class="fa-solid fa-share-from-square"></span></a>
                                            @else
                                                <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="SI pending initiation"><span class="fa-solid fa-spinner"></span></a>
                                            @endif
                                        @elseif($transfer->status == 1)
                                            @if(auth()->user()->role_id == 2 || @canuser('straightline.approve'))
                                                <a class="link text-warning" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to give first approval" onclick="return confirm('Are you sure you want to approve this SI?')" href="{{ route('clerk.approveSIFirst', $transfer->shipping_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                            @else
                                                <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="SI pending first approval"><span class="fa-regular fa-hourglass-half"></span></a>
                                            @endif
                                        @elseif($transfer->status == 2)
                                            @if($transfer->location_id == auth()->user()->station->location->location_id && auth()->user()->role_id == 3 || @canuser('straightline.create'))
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
                                                   data-action-url="{{ route('clerk.updateShippingInstructionDetails', $transfer->shipping_id) }}"
                                                ><span class="fa-solid fa-file-pen"></span></a>
                                            @else
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="1st approved, pending transport details"><span class="fa-solid fa-spinner"></span></a>
                                            @endif
                                        @elseif($transfer->status == 3)
                                            @if(auth()->user()->role_id == 2 || @canuser('straightline.finalapproval'))
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Give final approval — marks as shipped" onclick="return confirm('Are you sure you want to approve this SI? This will mark SI as shipped')" href="{{ route('clerk.approveSIFinal', $transfer->shipping_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                            @else
                                                <a class="link dark__text-warning" data-bs-toggle="tooltip" data-bs-placement="left" title="SI pending final approval and shipping"><span class="fa-regular fa-hourglass-half"></span></a>
                                            @endif
                                        @else
                                            <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="SI shipped, stock updated"><span class="fa-solid fa-check-double"></span></a>
                                        @endif

                                        <div class="dropdown font-sans-serif position-static" >
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('clerk.addShipmentTeas', $transfer->shipping_id) }}">View SI</a>
                                                    @if(@canuser('straightline.edit') && $transfer->status < 4)
                                                        <a class="dropdown-item text-warning" href="{{ route('clerk.editSI', $transfer->shipping_id) }}">Edit SI Sheet</a>
                                                    @endif

                                                    @if(@canuser('straightline.amendtransportdetails') && $transfer->status >= 2)
                                                        <a class="dropdown-item text-info js-open-transport-modal" href="javascript:void(0)"
                                                           data-shipping-id="{{ $transfer->shipping_id }}"
                                                           data-shipping-number="{{ $transfer->shipping_number }}"
                                                           data-container-number="{{ $transfer->container_number ?? '' }}"
                                                           data-container-tare="{{ $transfer->container_tare ?? '' }}"
                                                           data-seal-number="{{ $transfer->seal_number ?? '' }}"
                                                           data-escorted="{{ $transfer->escort ?? '' }}"
                                                           data-agent-id="{{ $transfer->clearing_agent ?? '' }}"
                                                           data-transporter-id="{{ $transfer->transporter_id ?? '' }}"
                                                           data-registration="{{ $transfer->registration ?? '' }}"
                                                           data-id-number="{{ $transfer->id_number ?? '' }}"
                                                           data-driver-name="{{ $transfer->driver_name ?? '' }}"
                                                           data-driver-phone="{{ $transfer->phone ?? '' }}"
                                                           data-action-url="{{ route('clerk.updateShippingInstructionDetails', $transfer->shipping_id) }}"
                                                        >Amend Transport Details</a>
                                                    @endif

                                                    @if(@canuser('straightline.delete') && $transfer->status < 4)
                                                        <a class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this straight line job?')" href="{{ route('clerk.editSI', $transfer->shipping_id) }}">Delete Straightline Job</a>
                                                    @endif
                                                    @if($transfer->status >= 1)
                                                        <a class="dropdown-item text-primary" href="{{ route('clerk.downloadSIDocument', $transfer->shipping_id) }}" target="_blank">Download SI</a>
                                                        @if(auth()->user()->role_id == 2 || auth()->user()->role_id !== 2 && $transfer->status > 3)
                                                            <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadSIPackingList', base64_encode($transfer->shipping_id.':'.$transfer->load_type)) }}" target="_blank">Packing List</a>
                                                            <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadSIPackingListExcel', base64_encode($transfer->shipping_id.':'.$transfer->load_type)) }}" target="_blank">Packing List (Excel)</a>
                                                        @endif
                                                        @if($transfer->load_type == 2 || $transfer->load_type == 3)
                                                            @if(auth()->user()->role_id == 2 || auth()->user()->role_id !== 2 && $transfer->status > 3)
                                                               <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadSIContinuedPackingList', base64_encode($transfer->si_number ?? $transfer->shipping_number)) }}" target="_blank">Packing List (Cont.) </a>
                                                                <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadSIContinuedPackingListExcel', base64_encode($transfer->si_number ?? $transfer->shipping_number)) }}" target="_blank">Packing List (Cont.) (Excel) </a>
                                                            @endif
                                                        @endif
                                                    @endif
                                                    @if($transfer->status >= 4)
                                                        <a class="dropdown-item text-dark" href="{{ route('clerk.downloadDriverClearance', $transfer->shipping_id) }}" target="_blank"> Port Delivery Note</a>
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
         instruction. Previously this was rendered inside the row @foreach,
         which meant hundreds/thousands of copies of this form (and its 3
         Choices.js selects) were built on every page load — that's what was
         exhausting PHP memory and, once fixed there, still leaving thousands
         of Choices.js instances on the page that froze the browser tab
         (7,617 "allowHTML" warnings = ~2,500 rows x 3 selects each).
         Clicking any "update transport details" link now just fills these
         same fields via JS instead of the server rendering a new modal.
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
    // Instantiate Choices.js ourselves for the 3 modal selects and keep
    // direct references to each instance. We do this instead of relying on
    // theme.js's choicesInit() helper because that helper doesn't expose the
    // instance it creates back to us in a predictable way, so we had no
    // reliable handle to call setChoiceByValue() on afterwards.
    var agentChoices, escortChoices, transporterChoices;

    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

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

        // Choices.js wraps the real <select> with its own UI, so we must use
        // its API to change the visible selection — setting select.value
        // directly does not update what Choices.js displays.
        setChoiceValue(agentChoices, d.agentId);
        setChoiceValue(escortChoices, d.escorted);
        setChoiceValue(transporterChoices, d.transporterId);

        transportModal.show();
    });

    function setChoiceValue(choicesInstance, value) {
        if (!choicesInstance) return;
        // Clear any previous selection first so re-opening the modal for a
        // different row (or a row with no saved value) doesn't leave the
        // last row's selection showing.
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
            url: '{{ route('clerk.fetchIdNumber') }}',
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
