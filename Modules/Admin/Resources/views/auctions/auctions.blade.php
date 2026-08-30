@extends('admin::layouts.default')
{{-- Fix Choices.js height to match Bootstrap sm inputs --}}
<style>
    .choices__inner {
        min-height: unset !important;
        padding: 0.25rem 2rem 0.25rem 0.5rem !important;
        font-size: 0.75rem !important;
        line-height: 1.5 !important;
        border-radius: 0.2rem !important;
        border-color: #ced4da !important;
        background-color: #fff !important;
    }

    .choices__input--cloned {
        font-size: 0.75rem !important;
        margin-bottom: 0 !important;
        padding: 0 !important;
    }

    .choices[data-type*="select-one"] .choices__inner {
        padding-bottom: 0.25rem !important;
    }

    .choices[data-type*="select-one"]::after {
        right: 0.6rem !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        border-width: 4px 4px 0 !important;
    }

    .choices[data-type*="select-one"].is-open::after {
        border-width: 0 4px 4px !important;
        margin-top: 0 !important;
    }

    .choices__list--single {
        padding: 0 !important;
        line-height: 1.5 !important;
    }

    .choices__placeholder {
        opacity: 0.6 !important;
    }
</style>
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">View Auction Teas</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                {{-- Filter, Reset & Export Bar --}}
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ request()->url() }}" id="filterForm">
                            @csrf
                            <div class="row g-2 align-items-end">

                                {{-- Warrant / Invoice Search --}}
                                <div class="col-md-2 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Warrant / Invoice</label>
                                    <input type="text"
                                           name="warrant_invoice"
                                           class="form-control"
                                           placeholder="Search..."
                                           value="{{ request('warrant_invoice') }}">
                                </div>

                                {{-- Garden --}}
                                <div class="col-md-2 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Garden</label>
                                    <select name="garden" class="form-select form-select-sm js-choice">
                                        <option value="">All Gardens</option>
                                        @foreach($gardens ?? [] as $garden)
                                            <option value="{{ $garden->garden_id }}" {{ request('garden') == $garden->garden_id ? 'selected' : '' }}>
                                                {{ $garden->garden_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Grade --}}
                                <div class="col-md-2 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Grade</label>
                                    <select name="grade" class="form-select form-select-sm js-choice">
                                        <option value="">All Grades</option>
                                        @foreach($grades ?? [] as $grade)
                                            <option value="{{ $grade->grade_id }}" {{ request('grade') == $grade->grade_id ? 'selected' : '' }}>
                                                {{ $grade->grade_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Broker --}}
                                <div class="col-md-2 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Broker</label>
                                    <select name="broker" class="form-select form-select-sm js-choice">
                                        <option value="">All Brokers</option>
                                        @foreach($brokers ?? [] as $broker)
                                            <option value="{{ $broker->broker_id }}" {{ request('broker') == $broker->broker_id ? 'selected' : '' }}>
                                                {{ $broker->broker_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Buyer --}}
                                <div class="col-md-2 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Buyer</label>
                                    <select name="buyer" class="form-select form-select-sm js-choice">
                                        <option value="">All Buyers</option>
                                        @foreach($buyers ?? [] as $buyer)
                                            <option value="{{ $buyer->client_id }}" {{ request('buyer') == $buyer->client_id ? 'selected' : '' }}>
                                                {{ $buyer->client_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Sale Status --}}
                                <div class="col-md-1 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Sale Status</label>
                                    <select name="sale_status" class="form-select form-select-sm">
                                        <option value="">All</option>
                                        <option value="0" {{ request('sale_status') === '0' ? 'selected' : '' }}>On Sale</option>
                                        <option value="1" {{ request('sale_status') === '1' ? 'selected' : '' }}>Sold</option>
                                    </select>
                                </div>

                                {{-- Release Status --}}
                                <div class="col-md-1 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Release</label>
                                    <select name="release_status" class="form-select form-select-sm">
                                        <option value="">All</option>
                                        <option value="released" {{ request('release_status') === 'released' ? 'selected' : '' }}>Released</option>
                                        <option value="pending"  {{ request('release_status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                                    </select>
                                </div>

                                {{-- Buyer --}}
                                <div class="col-md-1 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Sale</label>
                                    <select name="sale" class="form-select form-select-sm js-choice">
                                        <option value="">All Sales</option>
                                        @foreach($sales ?? [] as $sale)
                                            <option value="{{ $sale->sale }}" {{ request('sale') == $sale->sale ? 'selected' : '' }}>
                                                {{ $sale->sale }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Sale Date Range --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Sale Date</label>
                                    <div class="input-group">
                                        <input type="date" name="sale_date_from" class="form-control"
                                               placeholder="From" value="{{ request('sale_date_from') }}" title="From">
                                        <span class="input-group-text px-1">–</span>
                                        <input type="date" name="sale_date_to" class="form-control"
                                               placeholder="To" value="{{ request('sale_date_to') }}" title="To">
                                    </div>
                                </div>

                                {{-- Prompt Date Range --}}
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Prompt Date</label>
                                    <div class="input-group">
                                        <input type="date" name="prompt_date_from" class="form-control"
                                               value="{{ request('prompt_date_from') }}" title="From">
                                        <span class="input-group-text px-1">–</span>
                                        <input type="date" name="prompt_date_to" class="form-control"
                                               value="{{ request('prompt_date_to') }}" title="To">
                                    </div>
                                </div>

                                {{-- Warehouse --}}
                                <div class="col-md-2 col-sm-6">
                                    <label class="form-label fw-semibold fs-sm-11 mb-1">Producer Whs</label>
                                    <select name="warehouse" class="form-select form-select-sm js-choice">
                                        <option value="">All Warehouses</option>
                                        @foreach($warehouses ?? [] as $wh)
                                            <option value="{{ $wh->warehouse_id }}" {{ request('warehouse') == $wh->warehouse_id ? 'selected' : '' }}>
                                                {{ $wh->warehouse_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="col-md-auto col-sm-12 d-flex gap-2 flex-wrap mt-1">
                                    <button type="submit" class="btn btn-primary btn-sm px-3">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>

                                    <a href="{{ request()->url() }}" class="btn btn-outline-secondary btn-sm px-3">
                                        <i class="fas fa-times me-1"></i> Reset
                                    </a>

                                    {{-- Export Dropdown --}}
                                    <div class="dropdown">
                                        <button class="btn btn-success btn-sm px-3 dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-download me-1"></i> Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item fs-sm-11"
                                                   href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                                                    <i class="fas fa-file-excel text-success me-2"></i> Export to Excel
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fs-sm-11"
                                                   href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
                                                    <i class="fas fa-file-csv text-secondary me-2"></i> Export to CSV
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fs-sm-11"
                                                   href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">
                                                    <i class="fas fa-file-pdf text-danger me-2"></i> Export to PDF
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <div class="table-responsive">
                        <table class="table mb-0 table-bordered table-striped fs-sm-11" id="datatable">
                            <thead class="bg-200">
                            <tr>
                                <th>#</th>
                                <th>Client Name</th>
                                <th>Warrant / Invoice</th>
                                <th>Garden / Grade</th>
                                <th>Pks / Weight</th>
                                <th>Broker / Buyer</th>
                                <th>Producer Whs/ Sale</th>
                                <th>Sale / Prompt</th>
                                <th>Release Number / Date</th>
                                <th>Statuses</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($auctions as $transfer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $transfer->client_name }}</td>
                                    <td>
                                        <div class="mb-1"><small class="text-muted">WR:</small> {{ $transfer->warrant_number }}</div>
                                        <div><small class="text-muted">INV:</small> {{ $transfer->invoice_number }}</div>
                                    </td>
                                    <td>
                                        <div class="mb-1">{{ $transfer->garden_name }}</div>
                                        <div><small class="text-muted">{{ $transfer->grade_name }}</small></div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><small class="text-muted">Pks:</small> {{ number_format($transfer->total_pallets, 0) }}</div>
                                        <div><small class="text-muted">Wt:</small> {{ number_format($transfer->net_weight, 2) }}</div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><small class="text-muted">Bkr:</small> {{ $transfer->broker_name }}</div>
                                        <div><small class="text-muted">Byr:</small> {{ $transfer->buyer_name }}</div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><small class="text-muted">Whs:</small> {{ $transfer->warehouse_name }}</div>
                                        <div><small class="text-muted">Sale:</small> {{ $transfer->sale }}</div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><small class="text-muted">Sale:</small> {{ $transfer->sale_date }}</div>
                                        <div><small class="text-muted">Prompt:</small> {{ $transfer->prompt_date }}</div>
                                    </td>

                                    <td>
                                        <div class="mb-1"><small class="text-muted">Del #:</small> {{ $transfer->delivery_number }}</div>
                                        <div><small class="text-muted">Release:</small> {{ $transfer->release_date }}</div>
                                    </td>
                                    <td>
                                        <div class="mb-1"><small class="text-muted">Sl : </small>{!! $transfer->status == 0 ? '<span class="badge bg-info">On Sale</span>' : '<span class="badge bg-primary">Sold</span>' !!}</div>
                                        <div><small class="text-muted">Rl : </small>{!! $transfer->release_date ? '<span class="badge bg-primary">Released</span>' : '<span class="badge bg-info">Pending</span>' !!}</div>

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>

    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 100
        });
    });

</script>
