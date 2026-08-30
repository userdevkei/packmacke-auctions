@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Closing Stock </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div id="table-simple-pagination-replace-element" class="d-flex align-items-center">
                    <!-- Filter Button -->
                    <button class="btn btn-falcon-default btn-sm me-2" type="button" data-bs-toggle="modal" data-bs-target="#stockReport">
                        <span class="fas fa-filter" data-fa-transform="shrink-3 down-2"></span>
                        <span class="d-none d-sm-inline-block ms-1">Filter</span>
                    </button>

                    <!-- Export Form -->
                    <form method="post" class="m-0">
                        @csrf
                        <input type="hidden" name="action" value="download">
                        <button class="btn btn-falcon-default btn-sm" type="submit" formtarget="_blank">
                            <input type="hidden" name="client" value="{{ $client }}">
                            <input type="hidden" name="station" value="{{ $station }}">
                            <input type="hidden" name="to" value="{{ $dateTo }}">
                            <input type="hidden" name="zero_balance" value="{{ $zeroBalance }}">
                            <span class="fas fa-file-export" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Export</span>
                        </button>
                    </form>
                </div>

                    </div>

                 <div class="modal fade" id="stockReport" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">Filter Closing Stock</h5>
                                </div>
                                <div class="p-4">
                                    <form method="post">
                                        @csrf
                                        <div class="row">
                                                <div class="col-12 mb-2">
                                                    <label> CLIENT NAME</label>
                                                    <select class="form-select js-choice" name="client">
                                                        <option value="">-- all clients --</option>
                                                        @foreach($clients as $clientName)
                                                            <option value="{{ $clientName }}" {{ $client == $clientName ? 'selected' : '' }}>{{ $clientName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-12 mb-2">
                                                    <label> WAREHOUSES </label>
                                                    <select class="form-select js-choice" name="station">
                                                        <option value="">-- all warehouses --</option>
                                                        @foreach($stations as $warehouseName)
                                                            <option {{ $station == $warehouseName ? 'selected' : '' }} value="{{ $warehouseName }}">{{ $warehouseName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-12 mb-2">
                                                    <label> INCLUDE ZERO BALANCE</label>
                                                    <select class="form-select js-choice" name="zero_balance">
                                                        <option value="">-- select --</option>
                                                        <option value="1" {{ $zeroBalance == 1 ? 'selected' : '' }}>Yes</option>
                                                        <option value="0" {{ $zeroBalance == 0 ? 'selected' : '' }}>No</option>
                                                    </select>
                                                </div>

                                                <div class="col-12 mb-2">
                                                    <label> DATE TO</label>
                                                    <input type="date" value="{{ Carbon\Carbon::parse($dateTo)->format('Y-m-d') }}" class="form-control form-control-lg" name="to" placeholder="--">
                                                </div>
                                        </div>
                                            <div class="d-flex justify-content-center mt-4">
                                                <button type="submit" class="btn col-4 btn-md btn-falcon-success">FILTER REPORT</button>
                                                <a class="btn btn-secondary col-4 mx-3" href="{{ route('accounts.closingStockReport') }}">RESET</a>
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
                    <table class="table mb-0 table-bordered table-striped fs-sm" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>Order #</th>
                            <th>Inv #</th>
                            <th>Lot #</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Origin</th>
                            <th>Packages</th>
                            <th>Weight</th>
                            <th nowrap="">Closing Date</th>
                            <th>Stocked at</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $stock->client_name }}</td>
                                    <td>{{ $stock->order_number }}</td>
                                    <td>{{ $stock->invoice_number }}</td>
                                    <td>{{ $stock->lot_number }}</td>
                                    <td>{{ $stock->garden_name }}</td>
                                    <td>{{ $stock->grade_name }}</td>
                                    <td>{{ $stock->tea_type ?? 'Local' }} Tea</td>
                                    <td>{{ $stock->display_stock }}</td>
                                    <td>{{ number_format($stock->display_weight, 2) }}</td>
                                    <td nowrap="">{{ $stock->closing_date != null ? \Carbon\Carbon::parse($stock->closing_date)->format('d/m/Y') : '' }}</td>
                                    <td>{{ $stock->stocked_at }} - {{ $stock->bay_name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.1.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable( {
            order: [ 0, 'asc' ],
            pageLength: 50
        } );
    } );
</script>
