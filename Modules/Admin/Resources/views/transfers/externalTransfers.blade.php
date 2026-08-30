@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.2/css/buttons.dataTables.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .filter-panel {
        background: #f9fafb;
        border: 1px solid #edf2f9;
        border-radius: .5rem;
        padding: 1rem 1rem .75rem;
        margin-bottom: 1rem;
    }
    .filter-panel .form-label {
        font-size: .72rem;
        font-weight: 600;
        color: #5e6e82;
        text-transform: uppercase;
        letter-spacing: .02em;
        margin-bottom: .25rem;
    }
    .filter-panel .choices { margin-bottom: 0; font-size: .875rem; }
    .filter-panel .choices__inner {
        min-height: calc(1.5em + 1rem + 2px);
        padding: .3rem .75rem;
        background-color: #fff;
        border-radius: .375rem;
    }
    .filter-panel .form-control,
    .filter-panel .form-select {
        font-size: .875rem;
    }
    .active-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: #e7edff;
        color: #2054C9;
        border-radius: 2rem;
        padding: .2rem .6rem .2rem .75rem;
        font-size: .75rem;
        font-weight: 500;
        margin: 0 .35rem .35rem 0;
    }
    .active-filter-chip .chip-x {
        cursor: pointer;
        font-weight: 700;
        opacity: .6;
    }
    .active-filter-chip .chip-x:hover { opacity: 1; }
    .table-responsive::-webkit-scrollbar { height: 6px; }
    .table-responsive::-webkit-scrollbar-thumb { background: #d8e2ef; border-radius: 3px; }
    .table-responsive::-webkit-scrollbar-track { background: transparent; }
    .dt-buttons .btn { font-size: .8rem; }
    #activeFilters:empty { display: none; }

    /* Filters toggle + export buttons share one row */
    .toolbar-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: .5rem;
    }
    .toolbar-row .toolbar-left {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .toolbar-row .toolbar-right {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    /* Self-contained loading overlay — doesn't rely on DataTables' own
       .dataTables_processing element, which can get lost in nested
       positioned/overflow ancestors depending on the admin theme. */
    .dt-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, .88);
        display: none;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        z-index: 20;
        font-size: .9rem;
        color: #5e6e82;
    }
    .dt-loading-overlay.show {
        display: flex;
    }

    .filter-panel .form-control,
    .filter-panel .form-select {
        font-size: .875rem;
        height: calc(1.5em + .75rem + 2px); /* Bootstrap 5 default (md) height */
    }
    .filter-panel .choices__inner {
        min-height: calc(1.5em + .75rem + 2px);
        padding: .375rem .75rem;
        background-color: #fff;
        border-radius: .375rem;
    }
</style>

@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">External Tea Transfers</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Request</span></a>
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
                                    <h5 class="mb-1" id="staticBackdropLabel">FILTER TEAS FOR TRANSFER</h5>
                                </div>
                                <div class="p-4">
                                    <form method="POST" id="myForm" action="{{ route('admin.prepareExternalTransfer') }}">
                                        @csrf
                                        <div class="row row-cols-sm-2 g-2">
                                            <div class="mb-4">
                                                <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;">RECEIVING WAREHOUSE</label>
                                                <select name="location" class="form-select js-choice" id="selectWarehouse">
                                                    <option disabled selected>-- select station --</option>
                                                    @foreach($stations as $station)
                                                        <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;">CLIENT NAME</label>
                                                <select name="client" class="form-select" id="selectClients" style="height: 58% !important;">
                                                    <option disabled selected>-- select client --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-center mt-1">
                                            <button id="submitButton" type="submit" class="btn btn-success col-8">PREPARE TRANSFER REQUEST</button>
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

            <!-- FILTERS TOGGLE + EXPORT BUTTONS — inline, always visible -->
            <div class="toolbar-row">
                <div class="toolbar-left">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanelCollapse" aria-expanded="false" aria-controls="filterPanelCollapse">
                        <span class="fas fa-filter me-1"></span>Filters
                        <span class="fas fa-chevron-down ms-1" id="filterChevron"></span>
                    </button>
                    <div id="activeFiltersOutside" class="d-flex flex-wrap"></div>
                </div>
                <div class="toolbar-right">
                    <button id="btn-export-csv" class="btn btn-sm btn-outline-success">
                        <span class="fas fa-file-excel me-1"></span>Export Excel/CSV
                    </button>
                    <button id="btn-export-pdf" class="btn btn-sm btn-outline-danger">
                        <span class="fas fa-file-pdf me-1"></span>Export PDF
                    </button>
                </div>
            </div>

            <div class="collapse" id="filterPanelCollapse">
            <div class="filter-panel">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" id="filter-date-from" class="form-control" value="{{ $from }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" id="filter-date-to" class="form-control" value="{{ $to }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Delivery Number</label>
                        <input type="text" id="filter-delivery-number" class="form-control" placeholder="e.g. DN1234">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Client / Buyer Name</label>
                        <select id="filter-client-name" class="form-select js-choice" data-placeholder="All Clients">
                            <option value="">All Clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}" data-client-name="{{ $client->client_name }}">{{ $client->client_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter-status" class="form-select js-choice" data-placeholder="All Statuses">
                            <option value="">All Statuses</option>
                            <option value="0">Created</option>
                            <option value="1">Initiated</option>
                            <option value="2">1st Approved</option>
                            <option value="3">Released</option>
                            <option value="4">Completed</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Source (Station)</label>
                        <select id="filter-source" class="form-select js-choice" data-placeholder="All Sources">
                            <option value="">All Sources</option>
                            @foreach($stations as $station)
                                <option value="{{ $station->location_id ?? $station->station_id }}">{{ $station->station_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Destination</label>
                        <select id="filter-destination" class="form-select js-choice" data-placeholder="All Destinations">
                            <option value="">All Destinations</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->warehouse_id }}">{{ $warehouse->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Sold Via</label>
                        <select id="filter-sale-type" class="form-select js-choice" data-placeholder="All">
                            <option value="">All</option>
                            <option value="auction">Auction</option>
                            <option value="private">Private</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
                        <div id="activeFilters" class="d-flex flex-wrap"></div>
                        <div class="d-flex gap-2 ms-auto">
                            <button id="btn-reset-filter" class="btn btn-sm btn-falcon-default">
                                <span class="fas fa-undo me-1"></span>Reset
                            </button>
                            <button id="btn-filter" class="btn btn-sm btn-primary">
                                <span class="fas fa-filter me-1"></span>Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel">
                    <div class="table-responsive position-relative">
                        <div id="dtLoadingOverlay" class="dt-loading-overlay">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span>Loading transfers…</span>
                        </div>
                        <table class="table mb-0 table-bordered table-striped" id="datatable" style="width:100%">
                            <thead class="bg-200">
                            <tr>
                                <th>#</th>
                                <th>Date Initiated</th>
                                <th>Delivery Number</th>
                                <th>Client Name</th>
                                <th>Packages</th>
                                <th>Net Weight</th>
                                <th>Transfer From</th>
                                <th>Destination</th>
                                <th>Sold Via</th>
                                <th nowrap="">Status</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="releaseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-1" id="releaseModalLabel">Release Transfer</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="releaseModalBody">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

<script>
    // Avoid loading a second jQuery / DataTables instance if the admin layout already includes them —
    // duplicate jQuery is the #1 cause of "DataTable looks static, no processing indicator" symptoms,
    // since $('#datatable').DataTable(...) can end up attached to a different jQuery than the DOM uses.
    if (typeof window.jQuery === 'undefined') {
        document.write('<script src="https://code.jquery.com/jquery-3.7.1.js"><\/script>');
    }
</script>
<script>
    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
        document.write('<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"><\/script>');
    }
</script>
<script>
    $(document).ready(function () {
        console.log('[external-transfers] jQuery version in use:', $.fn.jquery);
        console.log('[external-transfers] DataTables loaded:', typeof $.fn.DataTable !== 'undefined');

        // ---------- CHOICES.JS INIT FOR FILTER DROPDOWNS ----------
        var choicesInstances = {};
        document.querySelectorAll('.filter-panel select.js-choice').forEach(function (el) {
            choicesInstances[el.id] = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: el.dataset.placeholder || 'All',
            });
        });

        function setFilterValue(selector, value) {
            var id = selector.replace('#', '');
            if (choicesInstances[id]) {
                choicesInstances[id].setChoiceByValue(value || '');
            } else {
                $(selector).val(value || '').trigger('change');
            }
        }

        // Safely (re)initialise a Choices.js instance on an element, destroying any
        // existing instance first. Fixes "Trying to initialise Choices on element
        // already initialised" — happens when the release modal reopens and its
        // .js-choice elements get a fresh `new Choices(el)` without cleanup.
        function initChoicesSafely(el, options) {
            if (el.choicesInstance) {
                el.choicesInstance.destroy();
            }
            var instance = new Choices(el, options || { searchEnabled: true, itemSelectText: '' });
            el.choicesInstance = instance;
            return instance;
        }

        // ---------- FILTER STATE HELPERS ----------
        function getFilters() {
            return {
                date_from: $('#filter-date-from').val(),
                date_to: $('#filter-date-to').val(),
                delivery_number: $('#filter-delivery-number').val(),
                client_id: $('#filter-client-name').val(),
                client_name: $('#filter-client-name option:selected').data('client-name')
                    || $('#filter-client-name option:selected').text().trim(),
                status: $('#filter-status').val(),
                source: $('#filter-source').val(),
                destination: $('#filter-destination').val(),
                sale_type: $('#filter-sale-type').val(),
            };
        }

        function filterLabels() {
            return {
                date_from: 'From',
                date_to: 'To',
                delivery_number: 'Delivery #',
                client_name: 'Client',
                status: 'Status',
                source: 'Source',
                destination: 'Destination',
                sale_type: 'Sold Via',
            };
        }

        function optionText(selector, value) {
            return $(selector + ' option[value="' + value + '"]').text() || value;
        }

        function renderActiveFilters() {
            var filters = getFilters();
            var labels = filterLabels();
            var $container = $('#activeFilters').empty();
            var $outside = $('#activeFiltersOutside').empty();

            Object.keys(filters).forEach(function (key) {
                if (key === 'client_id') return;
                var val = filters[key];
                if (!val) return;

                var display = val;
                if (key === 'status') display = optionText('#filter-status', val);
                if (key === 'source') display = optionText('#filter-source', val);
                if (key === 'destination') display = optionText('#filter-destination', val);
                if (key === 'sale_type') display = optionText('#filter-sale-type', val);

                var $chip = $('<span class="active-filter-chip"></span>')
                    .append('<span>' + labels[key] + ': ' + display + '</span>')
                    .append('<span class="chip-x" data-key="' + key + '">&times;</span>');
                $container.append($chip);

                var $chipOutside = $chip.clone(true);
                $outside.append($chipOutside);
            });
        }

        $('#filterPanelCollapse').on('shown.bs.collapse', function () {
            $('#filterChevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        });
        $('#filterPanelCollapse').on('hidden.bs.collapse', function () {
            $('#filterChevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
        });

        var fieldMap = {
            date_from: '#filter-date-from',
            date_to: '#filter-date-to',
            delivery_number: '#filter-delivery-number',
            client_name: '#filter-client-name',
            status: '#filter-status',
            source: '#filter-source',
            destination: '#filter-destination',
            sale_type: '#filter-sale-type',
        };

        $(document).on('click', '.chip-x', function () {
            var key = $(this).data('key');
            var selector = fieldMap[key];
            if (selector === '#filter-date-from' || selector === '#filter-date-to' || selector === '#filter-delivery-number') {
                $(selector).val('').trigger('change');
            } else {
                setFilterValue(selector, '');
            }
            table.ajax.reload();
            renderActiveFilters();
        });

        // ---------- DATATABLE ----------
        var table;
        try {
table = $('#datatable').DataTable({
    processing: true,
    // serverSide removed — sorting, searching and paging now happen entirely
    // in the browser against the rows this ajax call returns. The ajax only
    // re-fires when panel filters change (Apply Filters / Reset / chip-x),
    // not on every keystroke or column-header click.
    pageLength: 50,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
    order: [[1, 'desc']],
    dom: '<"row align-items-center mb-2"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"t>><"row align-items-center mt-2"<"col-sm-6"i><"col-sm-6"p>>',
    ajax: {
        url: "{{ route('admin.externalTransfersData') }}",
        data: function (d) {
            // Only panel filters go to the server now — no draw/start/length/
            // order/search.value to send, since those are client-side concerns.
            return getFilters();
        },
        dataSrc: 'data',
        error: function (xhr) {
            console.error('[external-transfers] AJAX load failed:', xhr.status, xhr.responseText);
            $('#datatable-error-banner').remove();
            $('#datatable_wrapper').before(
                '<div id="datatable-error-banner" class="alert alert-danger">Failed to load transfers (HTTP ' + xhr.status + '). Check console for details.</div>'
            );
        }
    },
    columnDefs: [{
        targets: 0,
        orderable: false,
        searchable: false,
        render: function (data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
        }
    }],
    columns: [
        { data: null },
        {
            data: 'created_at_sort',
            render: function (data, type, row) {
                return type === 'display' ? row.date_initiated : data;
            }
        },
        { data: 'delivery_number' },
        { data: 'client_name' },
        {
            data: 'total_palettes_sort',
            render: function (data, type, row) {
                return type === 'display' ? row.packages : data;
            }
        },
        {
            data: 'total_weight_sort',
            render: function (data, type, row) {
                return type === 'display' ? row.net_weight : data;
            }
        },
        { data: 'transfer_from' },
        { data: 'destination' },
        { data: 'sale_type' },
        { data: 'status', orderable: false, searchable: false },
        { data: 'actions', orderable: false, searchable: false },
    ],
    language: {
        emptyTable: "No transfers match your filters.",
        zeroRecords: "No transfers match your filters. Try adjusting them.",
        processing: '',
        search: '',
        searchPlaceholder: 'Quick search…',
        lengthMenu: 'Show _MENU_ entries',
    }
});
        } catch (err) {
            console.error('[external-transfers] DataTable init threw an error:', err);
            $('#datatable').before(
                '<div class="alert alert-danger">Could not initialize the data table (' + err.message + '). This usually means jQuery/DataTables loaded twice or a JS error ran earlier on the page — check the browser console.</div>'
            );
        }

        // Self-contained overlay — independent of DataTables' own .dataTables_processing
        // element so it can't get lost in nested positioned/overflow ancestors, and
        // won't get missed even on very fast queries since we control it directly.
        if (table) {
            table.on('processing.dt', function (e, settings, processing) {
                $('#dtLoadingOverlay').toggleClass('show', !!processing);
            });
        }

        function safeReload() {
            if (table && table.ajax) {
                table.ajax.reload();
            } else {
                console.warn('[external-transfers] Reload skipped — table failed to initialize.');
            }
        }

        renderActiveFilters();

        $('.filter-panel select.js-choice, #filter-date-from, #filter-date-to, #filter-delivery-number').on('change', function () {
            renderActiveFilters();
        });

        $('#btn-filter').on('click', function () {
            safeReload();
            renderActiveFilters();
        });

        // Enter key in text inputs triggers filter too
        $('#filter-delivery-number, #filter-client-name').on('keyup', function (e) {
            if (e.key === 'Enter') {
                safeReload();
                renderActiveFilters();
            }
        });

        $('#btn-reset-filter').on('click', function () {
            $('#filter-date-from, #filter-date-to, #filter-delivery-number').val('');
            ['filter-client-name', 'filter-status', 'filter-source', 'filter-destination', 'filter-sale-type'].forEach(function (id) {
                setFilterValue('#' + id, '');
            });
            safeReload();
            renderActiveFilters();
        });

        // ---------- EXPORT (opens in a new tab) ----------
        function buildExportUrl(format, confirmFull) {
            var filters = getFilters();
            var params = new URLSearchParams(filters);
            params.set('format', format);
            if (confirmFull) params.set('confirm_full', '1');
            return "{{ route('admin.externalTransfersExport') }}?" + params.toString();
        }

        function hasAnyFilter() {
            var f = getFilters();
            return Object.values(f).some(function (v) { return !!v; });
        }

        function runExport(format) {
            if (!hasAnyFilter()) {
                var proceed = confirm('No filters are applied — this will export the entire table, which can be slow. Continue anyway?');
                if (!proceed) return;
                window.open(buildExportUrl(format, true), '_blank');
                return;
            }
            window.open(buildExportUrl(format, false), '_blank');
        }

        $('#btn-export-csv').on('click', function () {
            runExport('csv');
        });

        $('#btn-export-pdf').on('click', function () {
            runExport('pdf');
        });

        // ---------- EXISTING PAGE BEHAVIOUR (unchanged) ----------
        $('#selectWarehouse').change(function () {
            var warehouseId = $(this).val();
            $.ajax({
                type: 'GET',
                url: '{{ route('admin.selectClients') }}',
                data: { warehouseId },
                success: function (response) {
                    $('#selectClients').empty();
                    $('#selectClients').append('<option value="" selected disabled class="text-center"> -- select client --');

                    var seen = {};
                    $.each(response, function (clientName, rows) {
                        // rows is an array of duplicate records for this client — just take the first
                        var row = Array.isArray(rows) ? rows[0] : rows;
                        if (!row || !row.client_id || seen[row.client_id]) return;
                        seen[row.client_id] = true;
                        $('#selectClients').append('<option value="' + row.client_id + '">' + (row.client_name || clientName) + '</option>');
                    });
                }
            });
        });

        $(document).on('input', '.idSelect', function () {
            var idNumber = $(this).val();
            $.ajax({
                url: '{{ route('admin.fetchIdNumber') }}',
                method: 'GET',
                data: { idNumber },
                dataType: 'json',
                success: function (response) {
                    $('.driverName').val(response.driver_name);
                    $('.driverPhone').val(response.driver_phone);
                },
                error: function () {
                    $('.driverName').val('');
                    $('.driverPhone').val('');
                }
            });
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.release-btn');
            if (!btn) return;

            e.preventDefault();

            const delivery = btn.dataset.delivery;
            const client = btn.dataset.client;
            const modal = new bootstrap.Modal(document.getElementById('releaseModal'));
            const url = btn.dataset.url;

            document.getElementById('releaseModalLabel').textContent = `Release ${delivery} - ${client}`;
            document.getElementById('releaseModalBody').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading...</p>
            </div>`;

            modal.show();

            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('releaseModalBody').innerHTML = html;
                    document.querySelectorAll('#releaseModalBody .js-choice').forEach(function (el) {
                        // Guarded init — prevents the "already initialised" console
                        // error if this modal is opened more than once per page load.
                        const choicesInstance = initChoicesSafely(el, {
                            searchEnabled: true,
                            itemSelectText: '',
                        });

                        if (el.id === 'transporterSelect2') {
                            el.addEventListener('change', function () {
                                const otherInput = document.getElementById('otherTransporterInput2');
                                if (this.value === 'other') {
                                    otherInput.classList.remove('d-none');
                                    otherInput.required = true;
                                } else {
                                    otherInput.classList.add('d-none');
                                    otherInput.required = false;
                                    otherInput.value = '';
                                }
                            });
                        }
                    });
                })
                .catch(() => {
                    document.getElementById('releaseModalBody').innerHTML = '<p class="text-danger">Failed to load form.</p>';
                });
        });
    });
</script>
