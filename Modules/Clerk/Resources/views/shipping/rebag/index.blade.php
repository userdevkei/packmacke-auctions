@extends('clerk::layouts.default')
<style>
    .fs-xs {
        font-size: 11px !important;
        font-weight: 600;
    }
</style>
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Rebag Jobs </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @if(@canuser('rebag.create'))
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('clerk.createRebagJob') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Create Job</span></a>
                        @endif
                    </div>
                </div>

                {{-- CONTAINER LOGISTICS MODAL --}}
                <div class="modal fade" id="logisticsModal" tabindex="-1" aria-labelledby="logisticsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="logisticsModalLabel">CONTAINER LOGISTICS</h5>
                                </div>

                                <div class="container p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="mb-0 fs-xs text-uppercase">Container Details</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="logManageContainersBtn">
                                            <span class="fas fa-boxes me-1"></span> Manage Containers
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0 fs-sm table-striped" id="datatable1">
                                            <thead class="bg-200">
                                            <tr>
                                                <th class="fs-xs">#</th>
                                                <th class="fs-xs">Container NO</th>
                                                <th class="fs-xs">Seal NO</th>
                                                <th class="fs-xs">Tare Weight (kg)</th>
                                                <th class="fs-xs">Pallet Weight</th>
                                                <th class="fs-xs">Packages</th>
                                                <th class="fs-xs">Weight</th>
                                                <th class="fs-xs">Transporter</th>
                                                <th class="fs-xs">Escorted</th>
                                                <th class="fs-xs">Agent</th>
                                                <th class="fs-xs">Driver Details</th>
                                                <th class="fs-xs">Status</th>
                                                <th class="fs-xs">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody id="logContainersWrapper">
                                            </tbody>
                                        </table>
                                    </div>
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
                    <table class="table mb-0 table-bordered table-striped fs-sm w-100" id="datatable">
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
                        @foreach($sheets as $transfer)
                            @php
                                $manageContainersUrl = route('clerk.manageBlendContainers', $transfer->blend_id);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</td>
                                <td nowrap="">{{ $transfer->client_name }}</td>
                                <td>{{ $transfer->blend_number }}</td>
                                <td>{{ $transfer->vessel_name }}</td>
                                <td nowrap="">{{ $transfer->port_name }}</td>
                                <td>{{ $transfer->station_name }}</td>
                                <td>
                                    {!! $transfer->status == 0 ? '<span class="badge bg-warning"> Job Created </span>'
                                        : ($transfer->status == 1 ? '<span class="badge bg-dark"> Initiated </span>'
                                        : ($transfer->status == 2 ? '<span class="badge bg-info"> 1st Approved </span>'
                                        : ($transfer->status == 3 ? '<span class="badge bg-secondary"> Outturn Updated </span>'
                                        : ($transfer->status == 4 && ($transfer->blend_shipped == null) ? '<span class="badge bg-primary"> Outturn Approved </span>'
                                        : '<span class="badge bg-success"> Shipped </span>')))) !!}
                                </td>
                                @php
                                    $user = auth()->user();
                                    $canAccess =
                                        canuser('rebag.create') ||
                                        (
                                            $transfer->location_id == $user->station->location->location_id
                                            && $user->role_id == 3
                                        );
                                    $canApprove = @canuser('rebag.approve');
                                    $finalApproval = @canuser('rebag.finalblendsheetapproval');
                                    $canManageLogistics = @canuser('rebag.amendtransportdetails');
                                    $canShipBlend = @canuser('rebag.markasshipped');
                                @endphp
                                <td nowrap="">
                                    <div class="d-flex align-items-center">
                                        @if($transfer->status == 0)
                                            @if($canAccess)
                                                <a class="link text-warning" onclick="return confirm('Are you sure you want to initiate this job?')" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to initiate job" href="{{ route('clerk.initiateRebagSheet', $transfer->blend_id) }}"><span class="fa-solid fa-share-from-square"></span></a>
                                            @else
                                                <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Job pending initiation"><span class="fa-solid fa-spinner"></span></a>
                                            @endif
                                        @elseif($transfer->status == 1)
                                            @if($canApprove)
                                                <a class="link text-warning" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to give first approval" onclick="return confirm('Are you sure you want to approve this job?')" href="{{ route('clerk.approveRebagFirst', $transfer->blend_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                            @else
                                                <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Job pending first approval"><span class="fa-regular fa-hourglass-half"></span></a>
                                            @endif
                                        @elseif($transfer->status == 2)
                                            @if($canAccess)
                                                <a class="link text-info" data-bs-placement="left" title="Click to complete OutTurn Report" data-bs-toggle="tooltip" href="{{ route('clerk.updateRebagJobOutTurnReport', $transfer->blend_id) }}"><span class="fa-solid fa-pen-to-square"></span></a>
                                            @else
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="1st approved, pending outturn report"><span class="fa-solid fa-spinner"></span></a>
                                            @endif
                                        @elseif($transfer->status == 3)
                                            @if($finalApproval)
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Approve Outturn Report" onclick="return confirm('Are you sure you want to approve Rebag Outturn Report?')" href="{{ route('clerk.approveRebagFinal', $transfer->blend_id) }}"><span class="fa-regular fa-thumbs-up"></span></a>
                                            @else
                                                <a class="link dark__text-warning" data-bs-toggle="tooltip" data-bs-placement="left" title="Outturn complete, pending final approval"><span class="fa-regular fa-hourglass-half"></span></a>
                                            @endif
                                        @elseif($transfer->status == 4 && ($transfer->blend_shipped ==null))
                                            @if($transfer->release_status == 1)
                                                @if($finalApproval && $canShipBlend)
                                                    <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Mark job as Shipped" onclick="return confirm('Are you sure you want to mark this rebag job as shipped?')" href="{{ route('clerk.markRebagShipped', $transfer->blend_id) }}"><span class="fa-solid fa-truck-ramp-box"></span></a>
                                                @else
                                                    <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Ready to ship, pending permission"><span class="fa-solid fa-check-double"></span></a>
                                                @endif
                                            @elseif($canManageLogistics)
                                                <a class="link text-warning" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#logisticsModal"
                                                   data-blend-id="{{ $transfer->blend_id }}"
                                                   data-manage-containers-url="{{ $manageContainersUrl }}"
                                                   title="Complete logistics info before shipping"><span class="fa-solid fa-truck-fast"></span></a>
                                            @else
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Approved, pending logistics"><span class="fa-solid fa-spinner"></span></a>
                                            @endif
                                        @else
                                            <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Rebag shipped, stock updated"><span class="fa-solid fa-check-double"></span></a>
                                        @endif
                                        <div class="dropdown font-sans-serif position-static">
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('clerk.addRebagJobTeas', $transfer->blend_id) }}">View Rebag Job</a>
                                                    @if($user->role_id == 2 && $transfer->status <= 4 || @canuser('rebag.edit') && $transfer->status <= 4)
                                                        <a class="dropdown-item text-warning" href="{{ route('clerk.editRebagJob', $transfer->blend_id) }}">Edit Rebag Job</a>
                                                    @endif
                                                    @if(@canuser('rebag.amendOutturn') && $transfer->status >= 3 && $transfer->status <= 4)
                                                        <a class="dropdown-item text-danger" href="{{ route('clerk.updateRebagJobOutTurnReport', $transfer->blend_id) }}">Amend OutTurn</a>
                                                    @endif
                                                    @if($canManageLogistics && $transfer->status >= 4)
                                                        <a class="dropdown-item text-purple" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#logisticsModal"
                                                           data-blend-id="{{ $transfer->blend_id }}"
                                                           data-manage-containers-url="{{ $manageContainersUrl }}">View Logistics</a>
                                                    @endif
                                                    @if($transfer->status >= 3)
                                                        <a class="dropdown-item text-primary" href="{{ route('clerk.downloadRebagJob', $transfer->blend_id) }}" target="_blank">Rebag Job</a>
                                                        <a class="dropdown-item text-dark" href="{{ route('clerk.downloadOutturReport', $transfer->blend_id) }}" target="_blank"> Outturn Report</a>
                                                    @endif
                                                    @if($transfer->status >= 4)
                                                        <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadRebagJobPackingList', base64_encode($transfer->blend_id.":".$transfer->package_type)) }}" target="_blank"> Packing List</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadRebagJobPackingListExcel', base64_encode($transfer->blend_id.":".$transfer->package_type)) }}" target="_blank"> Packing List (Excel)</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadRebagJobPackingListCont', base64_encode($transfer->si_number.":".$transfer->package_type)) }}" target="_blank"> Packing List (Cont.)</a>
                                                        <a class="dropdown-item text-secondary" href="{{ route('clerk.downloadRebagJobPackingListContExcel', base64_encode($transfer->si_number.":".$transfer->package_type)) }}" target="_blank"> Packing List (Cont.) (Excel)</a>
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
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>

<script>
    $(document).ready(function () {
        $('#datatable1').DataTable({
            order: [0, 'asc'],
            pageLength: 50,
            searching: false,
            paging: false,
            info: false
        });
    });
</script>

<script>
    const containerItemBaseUrl = "{{ url('clerk/blend-containers-item') }}";
    const csrfToken = "{{ csrf_token() }}";

    // Set per modal-load from the JSON response's `canApproveContainer` flag —
    // this is a permission check, not a per-container property, so it can't
    // be read off each row like status can.
    let canApproveContainer = false;

    // NOTE: these per-container download endpoints are assumed — point them at
    // whatever routes actually generate the clearance note / packing list for
    // a single container on the backend.
    function logContainerDownloadUrls(containerId) {
        return {
            clearance: `${containerItemBaseUrl}/${containerId}/clearance`,
            packingPdf: `${containerItemBaseUrl}/${containerId}/packing-list-pdf`,
            packingExcel: `${containerItemBaseUrl}/${containerId}/packing-list-excel`,
        };
    }

    function logRenderContainerRow(c, index) {
        const isPending = c.status === 'pending';
        const isApproved = c.status === 'approved';
        const badgeColor = isApproved ? 'success' : c.status === 'declined' ? 'danger' : 'warning';
        const urls = logContainerDownloadUrls(c.container_id);
        const driverDetails = [c.driver_name, c.id_number].filter(Boolean).join(' - ');
        const escort = c.escort ? c.escort.replace(/\b\w/g, ch => ch.toUpperCase()) : '';

        const approveDeclineHtml = (canApproveContainer && isPending) ? `
                <a class="link text-success me-2" style="cursor:pointer;" data-container-action="approve" data-container-id="${c.container_id}" title="Approve"><span class="fas fa-check"></span></a>
                <a class="link text-danger me-2" style="cursor:pointer;" data-container-action="decline" data-container-id="${c.container_id}" title="Decline"><span class="fas fa-times"></span></a>
        ` : '';

        const downloadsHtml = isApproved ? `
                <a class="link text-secondary me-2" href="${urls.clearance}" target="_blank" title="Port Delivery Note"><span class="fas fa-file"></span></a>
                <a class="link text-secondary me-2" href="${urls.packingPdf}" target="_blank" title="Packing List (PDF)"><span class="fas fa-file-pdf"></span></a>
                <a class="link text-secondary" href="${urls.packingExcel}" target="_blank" title="Packing List (Excel)"><span class="fas fa-file-excel"></span></a>
        ` : '';

        return `
        <tr data-container-row="${c.container_id}" data-raw='${JSON.stringify(c)}'>
            <td>${index + 1}</td>
            <td>${c.container_number ?? ''}</td>
            <td>${c.seal_number ?? ''}</td>
            <td>${c.tare_weight ?? '0.00'}</td>
            <td>${c.pallet_weight ?? '0.00'}</td>
            <td>${c.packages ?? '0'}</td>
            <td>${c.weight ?? '0.00'}</td>
            <td>${c.transporter_name ?? ''}</td>
            <td>${escort}</td>
            <td>${c.agent_name ?? ''}</td>
            <td>${driverDetails}</td>
            <td class="log-status-cell"><span class="badge bg-${badgeColor}">${c.status ?? ''}</span></td>
            <td nowrap>${approveDeclineHtml}${downloadsHtml}</td>
        </tr>`;
    }

    // ---- Logistics modal: load containers on open ----
    document.addEventListener('show.bs.modal', function (event) {
        if (event.target.id !== 'logisticsModal') return;

        const trigger = event.relatedTarget;
        const blendId = trigger.getAttribute('data-blend-id');

        document.getElementById('logManageContainersBtn').dataset.url = trigger.getAttribute('data-manage-containers-url') || '#';

        const wrapper = document.getElementById('logContainersWrapper');
        wrapper.innerHTML = `<tr><td colspan="13" class="text-center text-500 py-3">Loading...</td></tr>`;

        fetch(`{{ url('clerk/rebags/logistics') }}/${blendId}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                canApproveContainer = !!data.canApproveContainer;
                const containers = data.containers ?? [];
                wrapper.innerHTML = containers.length
                    ? containers.map(logRenderContainerRow).join('')
                    : `<tr><td colspan="13" class="text-center text-500 py-3">No containers yet</td></tr>`;
            })
            .catch(() => {
                wrapper.innerHTML = `<tr><td colspan="13" class="text-center text-danger py-3">Failed to load containers</td></tr>`;
            });
    });

    // ---- Manage Containers button: full-page redirect ----
    document.addEventListener('click', function (e) {
        if (e.target.closest('#logManageContainersBtn')) {
            const url = document.getElementById('logManageContainersBtn').dataset.url;
            if (url && url !== '#') window.location.href = url;
        }
    });

    // ---- Container approve/decline (inside modal) ----
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-container-action]');
        if (!btn) return;

        const action = btn.getAttribute('data-container-action');
        const containerId = btn.getAttribute('data-container-id');
        if (!confirm(`${action === 'approve' ? 'Approve' : 'Decline'} this container?`)) return;

        fetch(`${containerItemBaseUrl}/${containerId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ action }),
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return alert('Failed to update container status.');
                const row = document.querySelector(`[data-container-row="${containerId}"]`);
                const index = Array.from(row.parentNode.children).indexOf(row);
                const updated = { ...JSON.parse(row.dataset.raw || '{}'), status: res.status };
                // re-render the row so it picks up the new status: approve/decline
                // buttons drop away, and downloads appear once status is 'approved'.
                row.outerHTML = logRenderContainerRow(updated, index);
            })
            .catch(() => alert('Failed to update container status.'));
    });
</script>
