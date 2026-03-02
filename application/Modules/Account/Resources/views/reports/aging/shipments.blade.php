@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<style>
    /* Container wrapper for Choices.js */

    /* Inner field (the visible select) */
    #month, .choices__inner {
        font-size: 0.7rem !important;
        padding: 0 0.5rem !important;
        min-height: 0.05rem !important;
        height: auto !important;
        line-height: 0.25 !important;
        min-width: 8vw !important;
        max-width: none !important;
    }

    /* Ensure dropdown matches content width */
    #month, .choices__list--dropdown {
        width: max-content !important;
        min-width: 100% !important;
    }
    /* Dropdown list items */
    #month, .choices__list--dropdown .choices__item {
        font-size: 0.5rem !important;
    }
    body .month.choices__list--dropdown .choices__item {
        font-size: 0.5rem !important;
    }
</style>

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0"> {{ \Carbon\Carbon::parse($selectedMonth)->format('M Y') }} Shipments </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <div id="table-simple-pagination-replace-element">
                            <button class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#stockReport"><span class="fas fa-file-download" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Download</span></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="stockReport" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl mt-6" role="document">
                    <div class="modal-content border-0">
                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                <h5 class="mb-1" id="staticBackdropLabel">GENERATE SHIPMENT REPORT</h5>
                            </div>
                            <div class="p-4">
                                <form method="post" action="{{ route('accounts.shipmentReport') }}" target="_blank">
                                    @csrf
                                    <div class="row">
                                        @php
                                            $clients = $sheets->unique('client_id');
                                        @endphp
{{--                                        @dd($clients)--}}
                                        <div class="col-6 mb-2">
                                            <label> CLIENT NAME</label>
                                            <select class="form-select js-choice" name="client">
                                                <option value="" selected>-- client name --</option>
                                                @foreach($clients as $client)
                                                    <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-6 mb-2">
                                            <label> SHIPMENT TYPE </label>
                                            <select class="form-select js-choice" name="type">
                                                <option value="" selected>-- shipment type --</option>
                                                @foreach($sheets->groupBy('type') as $type => $sheet)
                                                    <option value="{{ $type }}">{{ $type }}</option>
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
                                    </div>
                                    <div class="mt-2 fs-sm d-flex justify-content-center">
                                        <input class="mx-2" type="radio" name="report" value="1"> <span class="text-primary fw-bolder">PDF</span>
                                        <input class="mx-2" type="radio" name="report" value="2"> <span class="text-secondary fw-bolder">EXCEL </span>
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
        </div>

        <div class="card-body overflow-hidden p-lg-3">
            <div class="row justify-content-start mb-0">
                <div class="col-auto">
                    <form method="GET" action="{{ route('accounts.viewShipments') }}" id="monthFilterForm" class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <label for="month" class="form-label mb-0">Filter:</label>
                            <select name="month" id="month" class="form-select form-select-sm month js-choice fs-sm">
                                <option value="">-- select month --</option>
                                @foreach($availableMonths as $month)
                                    <option style="font-size: x-small !important;" value="{{ $month }}" {{ $month == $selectedMonth ? 'selected' : '' }}>
                                        {{ strtoupper(\Carbon\Carbon::parse($month . '-01')->format('M Y')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 mt-0 table-bordered table-striped fs-sm" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Type </th>
                            <th>Client Name</th>
                            <th>Shipping Number </th>
                            <th>Container</th>
                            <th>Transporter</th>
                            <th>Vessel Name</th>
                            <th>Destination</th>
                            <th>Warehouse</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sheets as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $transfer->type }}</td>
                                <td>{{ $transfer->client_name }}</td>
                                <td style="white-space: normal; word-break: break-word;">{{ $transfer->shipping_number }}</td>
                                <td>{{ $transfer->total_containers }} * {{ $transfer->container_size == 1 ? '20 FT' : ($transfer->container_size == 2 ? '40 FT' : '40 FTHC') }}</td>
                                <td >{{ $transfer->transporter_name }}</td>
                                <td style="white-space: normal; word-break: break-word;">{{ $transfer->vessel_name }}</td>
                                <td >{{ $transfer->port_name }}</td>
                                <td style="white-space: normal; word-break: break-word;">{{ $transfer->station_name }}</td>
                                <td>
                                    <span class="badge bg-success"> Shipped </span>
                                </td>
                                <td nowrap="">
                                    <a class="link link-info" href="{{ route('accounts.downloadShipment', base64_encode($transfer->shipping_id.':'.$transfer->type)) }}" target="_blank"><i class="fa fa-cloud-download-alt"></i> </a>
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
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        document.getElementById('month').addEventListener('change', function () {
            document.getElementById('monthFilterForm').submit();
        });
    });
</script>
