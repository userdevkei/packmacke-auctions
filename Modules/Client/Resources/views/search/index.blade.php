@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Search Results For <span
                            class="fw-bold text-success">{{ $searchTerm }}</span></h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    @php
                        $tabs = [
                            'teas' => count($teas),
                            'blends' => count($blends),
                            'straightLine' => count($straightLine),
                            'blendBalances' => count($blendBalances),
                            'externalTransfers' => count($externalTransfers),
                        ];
                        $firstActiveTab = collect($tabs)->filter(fn($v) => $v > 0)->keys()->first(); // 'teas', 'blends', etc.
                    @endphp


                    @if(array_sum($tabs) > 0)
                        {{-- Nav tabs --}}
                        <ul class="nav nav-tabs mb-3" id="searchTabs" role="tablist">
                            @foreach($tabs as $key => $count)
                                @if($count > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if($firstActiveTab === $key) active @endif"
                                                id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}"
                                                type="button" role="tab" aria-controls="{{ $key }}"
                                                aria-selected="{{ $firstActiveTab === $key ? 'true' : 'false' }}">
                                            {{ ucfirst($key) }} ({{ $count }})
                                        </button>
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                        <div class="tab-content" id="searchTabsContent">

                            @if(count($teas))
                                <div class="tab-pane fade @if($firstActiveTab === 'teas') show active @endif"
                                     id="teas" role="tabpanel" aria-labelledby="teas-tab">
                                    <table class="table mb-0 table-sm table-striped table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice Number</th>
                                            <th>Client Name</th>
                                            <th>Garden Name</th>
                                            <th>Grade Name</th>
                                            <th>Lot Number</th>
                                            <th>Order Number</th>
                                            <th>Packages</th>
                                            <th>Net Weight</th>
                                            <th>Pcks Bal</th>
                                            <th>Wght Bal</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teas as $tea)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $tea->invoice_number }}</td>
                                                <td>{{ $tea->client_name }}</td>
                                                <td>{{ $tea->garden_name }}</td>
                                                <td>{{ $tea->grade_name }}</td>
                                                <td>{{ $tea->lot_number }}</td>
                                                <td>{{ $tea->order_number }}</td>
                                                <td>{{ $tea->packet }}</td>
                                                <td>{{ $tea->weight }}</td>
                                                <td>{{ $tea->current_stock }}</td>
                                                <td>{{ $tea->current_weight }}</td>
                                                <td>
                                                    <a class="link-info" href="{{ route('client.traceTea', $tea->delivery_id) }}"><i class="fa fa-info"></i> view tea</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($blends))
                                <div class="tab-pane fade @if($firstActiveTab === 'blends') show active @endif"
                                     id="blends" role="tabpanel" aria-labelledby="blends-tab">
                                    <table class="table mb-0 table-striped  table-sm table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Shipping Number</th>
                                            <th>Client Name</th>
                                            <th>Vessel Name</th>
                                            <th>Warehouse</th>
                                            <th>Destination Port</th>
                                            <th>T. Pkgs</th>
                                            <th>T. Wght</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($blends as $blend)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $blend->blend_number }}</td>
                                                <td>{{ $blend->client_name }}</td>
                                                <td>{{ $blend->vessel_name }}</td>
                                                <td>{{ $blend->station_name }}</td>
                                                <td>{{ $blend->port_name }}</td>
                                                <td>{{ number_format($blend->output_packages, 0) }}</td>
                                                <td>{{ number_format($blend->output_weight, 2) }}</td>
                                                <td>
                                                    {!! $blend->status == 0 ? '<span class="badge bg-warning"> Blend Created </span>' : ($blend->status == 1 ? '<span class="badge bg-info"> Teas Updated </span>' : ($blend->status == 2 ? '<span class="badge bg-secondary"> Blend Updated </span>' : ($blend->status == 3 ? '<span class="badge bg-dark"> Pend. Approval </span>' : '<span class="badge bg-success"> Shipped </span>'))) !!}
                                                </td>
                                                <td>
                                                    <a class="link-info" href="{{ route('client.addBlendTeas', $blend->blend_id) }}">View</a> |
                                                    <a class="link-primary" href="{{ route('client.downloadBlendSheet', $blend->blend_id) }}" target="_blank">Download Bs</a> |
                                                    <a class="link-dark {{ $blend->status < 3 ? 'disabled text-muted' : '' }}"
                                                        href="{{ $blend->status >= 3 ? route('client.downloadOutturReport', $blend->blend_id) : '#' }}"
                                                        @if($blend->status < 3) onclick="return false;" @endif
                                                        target="_blank" > Download Outturn </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($straightLine))
                                <div class="tab-pane fade @if($firstActiveTab === 'straightLine') show active @endif"
                                     id="straightLine" role="tabpanel" aria-labelledby="straightLine-tab">
                                    <table class="table mb-0 table-striped table-bordered table-sm fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Shipping Number</th>
                                            <th>Client Name</th>
                                            <th>Container Number</th>
                                            <th>Vessel Name</th>
                                            <th>Warehouse</th>
                                            <th>Destination Port</th>
                                            <th>T. Pkgs</th>
                                            <th>T. Wght</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($straightLine as $straight)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $straight->shipping_number }}</td>
                                                <td>{{ $straight->client_name }}</td>
                                                <td>{{ $straight->container_number }}</td>
                                                <td>{{ $straight->vessel_name }}</td>
                                                <td>{{ $straight->station_name }}</td>
                                                <td>{{ $straight->port_name }}</td>
                                                <td>{{ number_format($straight->total_packages, 0) }}</td>
                                                <td>{{ number_format($straight->total_weight, 2) }}</td>
                                                <td>
                                                    {!! $straight->status == 0 ? '<span class="badge bg-warning"> SI Created </span>' : ($straight->status == 1 ? '<span class="badge bg-info"> Teas Updated </span>' : ($straight->status == 2 ? '<span class="badge bg-secondary"> SI Updated </span>' : ($straight->status == 3 ? '<span class="badge bg-dark"> Pend. Approval </span>' : '<span class="badge bg-success"> Shipped </span>'))) !!}
                                                </td>
                                                <td>
                                                    <a class="link-info" href="{{ route('client.addShipmentTeas', $straight->shipping_id) }}">View SI</a> |
                                                    <a class="link-primary" href="{{ route('client.downloadSIDocument', $straight->shipping_id) }}" target="_blank">Download SI</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($blendBalances))
                                <div class="tab-pane fade @if($firstActiveTab === 'blendBalances') show active @endif"
                                     id="blendBalances" role="tabpanel" aria-labelledby="blendBalances-tab">
                                    <table class="table mb-0 table-striped table-sm table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Blend Number</th>
                                            <th>Client Name</th>
                                            <th>Garden Name</th>
                                            <th>Grade Name</th>
                                            <th>Warehouse</th>
                                            <th>B/Rem Pkgs</th>
                                            <th>B/Rem Whgt</th>
                                            <th>Packages</th>
                                            <th>Net Weight</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($blendBalances as $blendBalance)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $blendBalance->blend_number }}</td>
                                                <td>{{ $blendBalance->client_name }}</td>
                                                <td>{{ $blendBalance->garden }}</td>
                                                <td>{{ $blendBalance->grade }}</td>
                                                <td>{{ $blendBalance->station_name }}</td>
                                                <td>{{ number_format($blendBalance->blend_packages, 0) }}</td>
                                                <td>{{ number_format($blendBalance->blend_weight, 2) }}</td>
                                                <td>{{ number_format($blendBalance->balance_packages, 0) }}</td>
                                                <td>{{ number_format($blendBalance->balance_weight, 2) }}</td>
                                                <td>
                                                    <a class="link-info" href="{{ route('client.traceBlendBalance', $blendBalance->blend_id) }}"><i class="fa fa-info"></i> view tea</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($externalTransfers))
                                    <div class="tab-pane fade @if($firstActiveTab === 'externalTransfers') show active @endif"
                                         id="externalTransfers" role="tabpanel" aria-labelledby="externalTransfers-tab">
                                        <table class="table mb-0 table-sm table-striped table-bordered fs-sm datatable">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Delivery Number</th>
                                                <th>Client Name</th>
                                                <th>Transfer From</th>
                                                <th>Transfer To</th>
                                                <th>Transporter Name</th>
                                                <th>Total Packages</th>
                                                <th>Net Weight</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($externalTransfers as $externalTransfer)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $externalTransfer->delivery_number }}</td>
                                                    <td>{{ $externalTransfer->client_name }}</td>
                                                    <td>{{ $externalTransfer->station_name }}</td>
                                                    <td>{{ $externalTransfer->warehouse_name }}</td>
                                                    <td>{{ $externalTransfer->transporter_name }}</td>
                                                    <td>{{ $externalTransfer->total_palettes }}</td>
                                                    <td>{{ $externalTransfer->total_weight }}</td>
                                                    <td> {!! $externalTransfer->status === 0 ? '<span class="badge bg-warning"> Created </span>' : ($externalTransfer->status == 1 ?  '<span class="badge bg-danger"> Pending Approval <span>' :($externalTransfer->status == 2 ? '<span class="badge bg-info"> Approved <span>' : '<span class="badge bg-success"> Released <span>')) !!}</td>
                                                    <td>
                                                        <a class="link-info" href="{{ route('client.viewExternalTransferDetails', base64_encode($externalTransfer->delivery_number)) }}">View Transfer</a> |
                                                        <a class="link-primary" href="{{ route('client.downloadExtraDelNote', base64_encode($externalTransfer->delivery_number)) }}" target="_blank">Download Transfer</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                        </div>
                    @else
                        <div class="alert alert-info">
                            No results found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('.datatable').DataTable({
                pageLength: 50
            });
        });
    </script>
@endsection
