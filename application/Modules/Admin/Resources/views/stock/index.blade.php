@extends('admin::layouts.default')

@section('admin::dashboard')
    <style>
        /* Hide child rows from DataTables counting */
        tr.child-row {
            display: none !important;
        }

        tr.child-row.show {
            display: table-row !important;
        }

        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.03);
        }

        .clickable-row.shown {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .toggle-icon {
            transition: transform 0.3s;
            font-size: 12px;
            color: #6c757d;
        }

        tr.shown .toggle-icon {
            transform: rotate(90deg);
            color: #007bff;
        }

        /* Child row styling - full width */
        #datatable tbody tr > td.child-row-td {
            border-top: none !important;
            padding: 0 !important;
            background-color: #f8f9fa;
        }

        /* Child content container */
        .child-content-wrapper {
            padding: 2rem 3rem;
            background: #f8f9fa;
        }

        /* Tab headers */
        .child-tabs {
            display: flex;
            gap: 2rem;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }

        .child-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .child-tab.active {
            color: #17a2b8;
            border-bottom-color: #17a2b8;
        }

        .child-tab i {
            font-size: 1rem;
        }

        .child-tab.stock-tab i {
            color: #17a2b8;
        }

        .child-tab.allocation-tab i {
            color: #fd7e14;
        }

        /* Tab content */
        .child-tab-content {
            display: none;
        }

        .child-tab-content.active {
            display: block;
        }

        /* Stock Details Table */
        .stock-details-table {
            background: transparent;
            font-size: 0.9rem;
        }

        .stock-details-table tr {
            border: none;
        }

        .stock-details-table td {
            padding: 0.5rem 0;
            border: none;
            background: transparent;
        }

        .stock-details-table td:first-child {
            color: #6c757d;
            font-weight: 400;
            width: 150px;
        }

        .stock-details-table td:last-child {
            color: #212529;
            font-weight: 600;
        }

        /* Allocation Alert */
        .allocation-alert {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #0c5460;
            font-size: 0.9rem;
        }

        .allocation-alert i {
            color: #17a2b8;
            font-size: 1.1rem;
        }

        /* Allocation Cards */
        .allocation-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .allocation-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.2s ease;
        }

        .allocation-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .allocation-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .allocation-card-type {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35em 0.75em;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .allocation-card-type.shipping {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }

        .allocation-card-type.transfer {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .allocation-card-type.blend {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .allocation-card-type.external_transfer {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .allocation-card-number {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }

        .allocation-card-details {
            color: #495057;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .allocation-card-details span {
            color: #212529;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .child-content-wrapper {
                padding: 1.5rem;
            }

            .child-tabs {
                gap: 1rem;
            }

            .allocation-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Teas In Stock </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Receive Teas</span></a>

                        <button class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#stockReport"><span class="fas fa-file-download" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Stock Report</span></button>

                        <button class="btn btn-falcon-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#transporter"><span class="fas fa-file-download" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Transport Report</span></button>
                    </div>
                </div>

                <!-- Transporter Modal -->
                <div class="modal fade" id="transporter" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">GENERATE TRANSPORTER REPORT</h5>
                                </div>
                                <div class="p-4">
                                    <form method="POST" action="{{ route('admin.exportTransportReport') }}">
                                        @csrf
                                        <div class="row row-cols-sm-2 g-1">
                                            <div class="col-6 mb-2">
                                                <label> CLIENT NAME</label>
                                                <select class="form-select js-choice" name="transporter" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option value="" selected>-- all transporters --</option>
                                                    @foreach($transporters as $transporter)
                                                        <option value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-6 mb-2">
                                                <label> DELIVERY TYPE</label>
                                                <select class="form-select js-choice" name="report" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option value="" selected>-- all deliveries --</option>
                                                    <option value="1">COLLECTIONS</option>
                                                    <option value="2">TRANSFERS</option>
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

                                        <div class="mt-4 d-flex justify-content-center">
                                            <button type="submit" class="btn btn-success col-7">DOWNLOAD REPORT</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Report Modal -->
                <div class="modal fade" id="stockReport" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">GENERATE CUSTOM REPORT</h5>
                                </div>
                                <div class="p-4">
                                    <form method="post" action="{{ route('admin.StockReport') }}" target="_blank">
                                        @csrf
                                        <div class="row">
                                            <div class="col-6 mb-2">
                                                <label> CLIENT NAME</label>
                                                <select class="form-select js-choice" name="client" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option value="" selected>-- all clients --</option>
                                                    @foreach($stocks->groupBy('client_name') as $clientName => $client)
                                                        <option value="{{ $client[0]->client_id }}">{{ $clientName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-6 mb-2">
                                                <label> WAREHOUSES </label>
                                                <select class="form-select js-choice" name="station" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option value="" selected>-- all warehouses --</option>
                                                    @foreach($stocks->groupBy('stocked_at') as $warehouseName => $warehouse)
                                                        <option value="{{ $warehouse[0]->station_id }}">{{ $warehouseName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-6 mb-2 date-input-container">
                                                <label> DATE FROM </label>
                                                <input type="date" class="form-control form-control-lg" value="{{ Carbon\Carbon::today()->subDays(30)->format('Y-m-d') }}" name="from" placeholder="--">
                                            </div>

                                            <div class="col-6 mb-2">
                                                <label> DATE TO</label>
                                                <input type="date" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}" class="form-control form-control-lg" name="to" placeholder="--">
                                            </div>

                                            <div class="mt-2 fs-sm d-flex justify-content-center">
                                                <input class="mx-2" type="radio" name="report" value="1"> <span class="text-primary fw-bolder">PDF</span>
                                                <input class="mx-2" type="radio" name="report" value="2"> <span class="text-secondary fw-bolder">EXCEL </span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-center mt-4">
                                            <button type="submit" class="btn col-8 btn-md btn-falcon-success">DOWNLOAD REPORT</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receive Teas Modal -->
                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">Provide DO Number/TCI Number To Receive Teas</h5>
                                </div>
                                <div class="p-4">
                                    <form method="post" action="{{ route('admin.getDoNumber') }}">
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-12 d-flex justify-content-center">
                                                <div class="flex-1 form-floating">
                                                    <input type="text" class="form-control form-control-lg" name="doNumber" required placeholder="--">
                                                    <label> DO/TCI Number</label>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-center mt-4">
                                                <button type="submit" class="btn col-8 btn-md btn-falcon-success">PROCEED</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body overflow-hidden mb-3">
            <div class="">
                <form method="POST" action="">
                    @csrf
                    <div class="row row-cols-3">
                        <div class="">
                            <input type="date" class="form-control" name="from" value="{{ Carbon\Carbon::parse($from)->format('Y-m-d') }}">
                        </div>
                        <div class="">
                            <input type="date" class="form-control" name="to" value="{{ Carbon\Carbon::parse($to)->format('Y-m-d') }}">
                        </div>
                        <div class="">
                            <button type="submit" class="btn btn-sm btn-info">filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="row align-items-center mt-3">
                <div class="tab-pane preview-tab-pane active" role="tabpanel">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th width="30"></th>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>Order #</th>
                            <th>Inv #</th>
                            <th>Lot #</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Origin</th>
                            <th>Current Stock</th>
                            <th>Allocated</th>
                            <th>Available</th>
                            <th nowrap="">Date Rc'd</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($stocks as $stock)
                            <tr class="clickable-row cursor-pointer parent-row" data-stock-id="{{ $stock->stock_id }}">
                                <td class="text-center">
                                    <i class="fas fa-chevron-right toggle-icon" id="icon-{{ $stock->stock_id }}"></i>
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $stock->client_name }}</td>
                                <td>{{ $stock->order_number }}</td>
                                <td>{{ $stock->invoice_number }}</td>
                                <td>{{ $stock->lot_number }}</td>
                                <td>{{ $stock->garden_name }}</td>
                                <td>{{ $stock->grade_name }}</td>
                                <td>{{ $stock->tea_type ?? 'Local' }}</td>
                                <td>
                                    <strong>{{ $stock->current_stock }}</strong> pkgs<br>
                                    <small class="text-muted">{{ number_format($stock->current_weight, 2) }} kg</small>
                                </td>
                                <td>
                                    @php
                                        $jsonString = preg_replace('/"transaction_id":([^,}\]]+)/', '"transaction_id":"$1"', $stock->allocated_job ?? '');
                                        $jobs = json_decode($jsonString, true);
                                        $totalAllocatedPkgs = is_array($jobs) ? array_sum(array_column($jobs, 'packages')) : 0;
                                        $totalAllocatedWeight = is_array($jobs) ? array_sum(array_column($jobs, 'weight')) : 0;
                                    @endphp
                                    @if($totalAllocatedPkgs > 0)
                                        <span class="badge badge-soft-warning">
                                            {{ $totalAllocatedPkgs }} pkgs<br>
                                            <small>{{ number_format($totalAllocatedWeight, 2) }} kg</small>
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $available = $stock->current_stock;
                                        $availableWeight = $stock->current_weight;
                                    @endphp
                                    <strong class="{{ $available > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $available }} pkgs<br>
                                        <small>{{ number_format($availableWeight, 2) }} kg</small>
                                    </strong>
                                </td>
                                <td nowrap="">{{ \Carbon\Carbon::createFromTimestamp($stock->date_received)->format('d/m/Y') }}</td>
                                <td>{{ $stock->stocked_at }} - {{ $stock->bay_name }}</td>
                                <td>
                                    @if($available <= 0 && $totalAllocatedPkgs <= 0)
                                        <span class="badge badge-soft-danger">Depleted</span>
                                    @elseif($totalAllocatedPkgs > 0)
                                        <span class="badge badge-soft-warning">Allocated</span>
                                    @else
                                        <span class="badge badge-soft-success">Available</span>
                                    @endif
                                </td>
                                <td nowrap="">
                                    <div class="dropdown font-sans-serif position-static">
                                        <a class="link text-info mx-1" href="{{ route('admin.traceTea', $stock->delivery_id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="Trace Tea">
                                            <span class="fa fa-info"></span>
                                        </a>
                                        <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                            <span class="fas fa-ellipsis-h fs-10"></span>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end border py-0">
                                            <div class="py-2">
                                                <a class="dropdown-item text-info" href="{{ route('admin.editStock', $stock->stock_id) }}">Edit Tea</a>
                                                <a class="dropdown-item text-success" href="{{ route('admin.withdrawSample', $stock->stock_id) }}">Obtain Sample</a>
                                                @if($stock->used == 0)
                                                    <a class="dropdown-item text-danger" onclick="return confirm('Are you sure you want archive Invoice Number {{ $stock->invoice_number }}')" href="{{ route('admin.deleteTea', $stock->stock_id) }}">Archive Tea</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <!-- Child rows container outside the table -->
                    <div id="child-rows-container" style="display: none;">
                        @foreach($stocks as $stock)
                            @php
                                $jsonString = preg_replace('/"transaction_id":([^,}\]]+)/', '"transaction_id":"$1"', $stock->allocated_job ?? '');
                                $jobs = json_decode($jsonString, true);
                            @endphp
                            <div class="child-row-content" id="child-content-{{ $stock->stock_id }}">
                                <div class="child-content-wrapper">
                                    <!-- Tabs -->
                                    <div class="child-tabs">
                                        <div class="child-tab stock-tab active" data-tab="stock-{{ $stock->stock_id }}">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Stock Details</span>
                                        </div>
                                        <div class="child-tab allocation-tab" data-tab="allocations-{{ $stock->stock_id }}">
                                            <i class="fas fa-list-ul"></i>
                                            <span>Allocations</span>
                                        </div>
                                    </div>

                                    <!-- Stock Details Tab Content -->
                                    <div class="child-tab-content active" id="stock-{{ $stock->stock_id }}">
                                        <table class="stock-details-table">
                                            <tr>
                                                <td>Production Date:</td>
                                                <td>{{ $stock->production_date ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td>Expiry Date:</td>
                                                <td>{{ $stock->expiry_date ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td>Total Received:</td>
                                                <td>{{ $stock->total_pallets }} packages</td>
                                            </tr>
                                            <tr>
                                                <td>Total Weight:</td>
                                                <td>{{ number_format($stock->net_weight, 2) }} kg</td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Allocations Tab Content -->
                                    <div class="child-tab-content" id="allocations-{{ $stock->stock_id }}">
                                        @if(is_array($jobs) && count($jobs) > 0)
                                            <div class="allocation-cards">
                                                @foreach($jobs as $job)
                                                    <div class="allocation-card">
                                                        <div class="allocation-card-header">
                                                            <span class="allocation-card-type {{ $job['type'] }}">
                                                                {{ str_replace('_', ' ', $job['type']) }}
                                                            </span>
                                                            <span class="allocation-card-number">#{{ $job['transaction_number'] }}</span>
                                                        </div>
                                                        <div class="allocation-card-details">
                                                            <span>{{ $job['packages'] }}</span> packages |
                                                            <span>{{ number_format($job['weight'], 2) }}</span> kg
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="allocation-alert">
                                                <i class="fas fa-check-circle"></i>
                                                <span>No allocations - Stock fully available</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            'use strict';

            // Set date inputs
            function setDateInputs() {
                var currentDate = new Date();
                currentDate.setHours(currentDate.getHours() + 3);
                var formattedDateTime = currentDate.toISOString().slice(0, -8);

                var todayInput = document.getElementById('todayDate');
                if (todayInput) {
                    todayInput.value = formattedDateTime;
                }

                var today = new Date();
                var oneMonthAgo = new Date(today);
                oneMonthAgo.setMonth(today.getMonth() - 1);

                var year = oneMonthAgo.getFullYear();
                var month = (oneMonthAgo.getMonth() + 1).toString().padStart(2, '0');
                var day = oneMonthAgo.getDate().toString().padStart(2, '0');
                var hours = oneMonthAgo.getHours().toString().padStart(2, '0');
                var minutes = oneMonthAgo.getMinutes().toString().padStart(2, '0');
                var formattedMonthAgo = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;

                var monthAgoInput = document.getElementById("monthAgo");
                if (monthAgoInput) {
                    monthAgoInput.value = formattedMonthAgo;
                }
            }

            // Initialize DataTable
            function initializeDataTable() {
                if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
                    setTimeout(initializeDataTable, 100);
                    return;
                }

                jQuery(function($) {
                    // Destroy existing instance if present
                    if ($.fn.DataTable.isDataTable('#datatable')) {
                        $('#datatable').DataTable().destroy();
                    }

                    // Initialize DataTable
                    var table = $('#datatable').DataTable({
                        "columnDefs": [{
                            "targets": 0,
                            "orderable": false,
                            "searchable": false
                        }],
                        "order": [[1, 'asc']],
                        "pageLength": 100
                    });

                    // Handle row expansion using DataTables child rows
                    $('#datatable tbody').on('click', 'tr.parent-row', function(e) {
                        if ($(e.target).closest('a, button, .dropdown').length) {
                            return;
                        }

                        var tr = $(this);
                        var row = table.row(tr);
                        var stockId = tr.data('stock-id');
                        var icon = $('#icon-' + stockId);

                        if (row.child.isShown()) {
                            // Hide child row
                            row.child.hide();
                            tr.removeClass('shown');
                            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                        } else {
                            // Show child row - wrap in td with proper class and colspan
                            var childContent = $('#child-content-' + stockId).html();
                            var columnCount = $('#datatable thead tr th').length;
                            row.child('<td colspan="' + columnCount + '" class="child-row-td">' + childContent + '</td>').show();
                            tr.addClass('shown');
                            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                        }
                    });

                    // Handle tab switching in child rows
                    $(document).on('click', '.child-tab', function() {
                        var targetTab = $(this).data('tab');
                        var $wrapper = $(this).closest('.child-content-wrapper');

                        // Remove active class from all tabs and content
                        $wrapper.find('.child-tab').removeClass('active');
                        $wrapper.find('.child-tab-content').removeClass('active');

                        // Add active class to clicked tab and corresponding content
                        $(this).addClass('active');
                        $('#' + targetTab).addClass('active');
                    });

                    console.log('DataTable initialized successfully');
                });
            }

            // Run on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setDateInputs();
                    initializeDataTable();
                });
            } else {
                setDateInputs();
                initializeDataTable();
            }
        })();
    </script>
@endsection
