@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Stock Collections </h5>
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
                            <input type="hidden" name="from" value="{{ Carbon\Carbon::createFromTimestamp($from)->format('Y-m-d') }}">
                            <input type="hidden" name="to" value="{{ Carbon\Carbon::createFromTimestamp($to)->format('Y-m-d') }}">
                            <input type="hidden" name="delivery_type" value="{{ $deliveryType }}">
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
                                    <h5 class="mb-1" id="staticBackdropLabel">Filter Stock Collections</h5>
                                </div>
                                <div class="p-4">
                                   <form method="post">
                                    @csrf
                                    <div class="row-cols-1 g-3">
                                        <div class="mb-2">
                                            <label> CLIENT NAME</label>
                                            <select class="form-select js-choice" name="client">
                                                <option value="">-- all clients --</option>
                                                @foreach($clients as $clientItem)
                                                    <option value="{{ $clientItem->client_id }}"
                                                        {{ $client == $clientItem->client_id ? 'selected' : '' }}>
                                                        {{ $clientItem->client_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label> DELIVERY TYPE</label>
                                            <select class="form-select js-choice" name="delivery_type">
                                                <option value="">-- all types --</option>
                                                <option value="1" {{ $deliveryType == 1 ? 'selected' : '' }}>TCI Deliveries</option>
                                                <option value="2" {{ $deliveryType == 2 ? 'selected' : '' }}>Direct Deliveries</option>
                                            </select>
                                        </div>

                                        <div class="mb-2 date-input-container">
                                            <label> DATE FROM </label>
                                            <input type="date"
                                                class="form-control form-control-lg"
                                                value="{{ request('from', Carbon\Carbon::parse($from)->format('Y-m-d')) }}"
                                                name="from"
                                                placeholder="--">
                                        </div>

                                        <div class="mb-2">
                                            <label> DATE TO</label>
                                            <input type="date"
                                                value="{{ request('to', Carbon\Carbon::parse($to)->format('Y-m-d')) }}"
                                                class="form-control form-control-lg"
                                                name="to"
                                                placeholder="--">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center mt-4">
                                        <button type="submit" class="btn col-4 btn-md btn-falcon-success">FILTER REPORT</button>
                                        <a class="btn btn-secondary col-4 mx-3" href="{{ route('accounts.stockCollectionReport') }}">RESET</a>
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
                            <th>TCI/Del Number</th>
                            <th>Client Name</th>
                            <th>Order #</th>
                            <th>Inv #</th>
                            <th>Lot #</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Packages</th>
                            <th>Weight</th>
                            <th nowrap="">Prompt Date</th>
                            <th nowrap="">Date DO Created</th>
                            <th>Producer Warehouse</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($collections as $stock)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $stock->tci_number }}</td>
                                    <td>{{ $stock->client_name }}</td>
                                    <td>{{ $stock->order_number }}</td>
                                    <td>{{ $stock->invoice_number }}</td>
                                    <td>{{ $stock->lot_number }}</td>
                                    <td>{{ $stock->garden_name }}</td>
                                    <td>{{ $stock->grade_name }}</td>
                                    <td>{{ $stock->total_pallets }}</td>
                                    <td>{{ number_format($stock->net_weight, 2) }}</td>
                                    <td>{{ $stock->prompt_date }}</td>
                                    <td nowrap="">{{ \Carbon\Carbon::parse($stock->date_received)->format('d/m/Y') }}</td>
                                    <td>{{ $stock->warehouse_name }}</td>
                                    <td>{{ $stock->collection }}</td>
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
