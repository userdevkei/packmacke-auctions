@extends('clerk::layouts.default')
@section('clerk::dashboard')
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
                            'internalTransfers' => count($internalTransfers),
                            'externalTransfers' => count($externalTransfers),
                            'tcis' => count($tcis),
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
                                                    <a class="link-info" href="{{ route('clerk.traceTea', $tea->delivery_id) }}"><i class="fa fa-info"></i> view tea</a>
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
                                                    <a class="link-info" href="{{ route('clerk.addBlendTeas', $blend->blend_id) }}">View</a> |
                                                    <a class="link-primary" href="{{ route('clerk.downloadBlendSheet', $blend->blend_id) }}" target="_blank">Download Bs</a> |
                                                    <a class="link-dark {{ $blend->status < 3 ? 'disabled text-muted' : '' }}"
                                                        href="{{ $blend->status >= 3 ? route('clerk.downloadOutturReport', $blend->blend_id) : '#' }}"
                                                        @if($blend->status < 3) onclick="return false;" @endif
                                                        target="_blank" > Download Outturn </a> |
                                                    <a class="link-secondary" href="{{ route('clerk.downloadBlendDriverClearance', $blend->blend_id) }}" target="_blank"> Port Clearance</a>
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
                                                    <a class="link-info" href="{{ route('clerk.addShipmentTeas', $straight->shipping_id) }}">View SI</a> |
                                                    <a class="link-primary" href="{{ route('clerk.downloadSIDocument', $straight->shipping_id) }}" target="_blank">Download SI</a> |
                                                    <a class="link-dark" href="{{ route('clerk.downloadDriverClearance', $straight->shipping_id) }}" target="_blank"> Driver Clearance</a>
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
                                                    <a class="link-info" href="{{ route('clerk.traceBlendBalance', $blendBalance->blend_id) }}"><i class="fa fa-info"></i> view tea</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($internalTransfers))
                                <div class="tab-pane fade @if($firstActiveTab === 'internalTransfers') show active @endif"
                                     id="internalTransfers" role="tabpanel" aria-labelledby="internalTransfers-tab">
                                    <table class="table mb-0 table-striped table-sm table-bordered fs-sm datatable">
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
                                        @foreach($internalTransfers as $internalTransfer)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $internalTransfer->delivery_number }}</td>
                                                <td>{{ $internalTransfer->client_name }}</td>
                                                <td>{{ $internalTransfer->station_name }}</td>
                                                <td>{{ $internalTransfer->destination_name }}</td>
                                                <td>{{ $internalTransfer->transporter_name }}</td>
                                                <td>{{ $internalTransfer->total_palettes }}</td>
                                                <td>{{ $internalTransfer->total_weight }}</td>
                                                <td>{!! $internalTransfer->status === null ? '<span class="badge bg-warning"> Created </span>' : ($internalTransfer->status == 0 ? '<span class="badge bg-dark">  Initiated <span>' : ($internalTransfer->status == 1 ? '<span class="badge bg-info"> Approved <span>' : ($internalTransfer->status == 2 ? '<span class="badge bg-danger"> Released <span>' : '<span class="badge bg-success"> Received <span>'))) !!}</td>
                                                <td>
                                                    <a class="link-info" href="{{ route('clerk.viewInternalTransferDetails', base64_encode($internalTransfer->delivery_number)) }}">View Transfer</a> |
                                                    <a class="link-primary" href="{{ route('clerk.downloadInterDelNote', base64_encode($internalTransfer->delivery_number)) }}" target="_blank">Download Transfer</a>
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
                                                        <!-- Dropdown Icon -->
                                                    <div class="dropdown font-sans-serif position-static" >
                                                        <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                            <span class="fas fa-ellipsis-h fs-10"></span>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-end border py-0">
                                                            <div class="py-2">
                                                                <a class="dropdown-item text-info" href="{{ route('clerk.viewExternalTransferDetails', base64_encode($externalTransfer->delivery_number)) }}">View Transfer</a>
                                                                @if($externalTransfer->buyer_name == null)
                                                                    <a class="dropdown-item text-primary" href="{{ route('clerk.downloadExtraDelNote', base64_encode($externalTransfer->delivery_number.':'.$externalTransfer->lot)) }}" target="_blank">Download Transfer</a>
                                                                @else
                                                                    @if (in_array(auth()->user()->role_id, [2, 3, 5]))
                                                                        <a class="dropdown-item text-danger" href="{{ route('clerk.downloadDelNote', base64_encode($externalTransfer->delivery_number.':'.$externalTransfer->lot)) }}" target="_blank">Download Del Note</a>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(count($tcis))
                                    <div class="tab-pane fade @if($firstActiveTab === 'tcis') show active @endif"
                                         id="tcis" role="tabpanel" aria-labelledby="tcis-tab">
                                        <table class="table mb-0 table-sm table-striped table-bordered fs-sm datatable">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Delivery Number</th>
                                                <th>Client Name</th>
                                                <th>Station Name</th>
                                                <th>Transfer From</th>
                                                <th>Sub-Warehouse</th>
                                                <th>Transporter Name</th>
                                                <th>Total Packages</th>
                                                <th>Net Weight</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($tcis as $tci)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $tci->loading_number }}</td>
                                                    <td>{{ $tci->client_name }}</td>
                                                    <td>{{ $tci->station_name }}</td>
                                                    <td>{{ $tci->warehouse_name }}</td>
                                                    <td style="white-space: normal !important; word-wrap: break-word !important; word-break: break-word !important;">{{ $tci->sub_warehouse_name }}</td>
                                                    <td style="white-space: normal !important; word-wrap: break-word !important; word-break: break-word !important;">{{ $tci->transporter_name }}</td>
                                                    <td>{{ number_format($tci->packages, 0) }}</td>
                                                    <td>{{ number_format($tci->net_weight, 2) }}</td>
                                                    <td> {!! $tci->status === 2 ? '<span class="badge bg-success"> Collected </span>' : '<span class="badge bg-info"> Under Collection <span>' !!}</td>
                                                    <td>
                                                        <a class="link-info mx-1 fs-sm" data-bs-toggle="tooltip" data-bs-placement="left" title="View TCI Details" href="{{ route('clerk.viewTciDetails', base64_encode($tci->loading_number)) }}">View TCI</a> |
                                                        <a class="text-secondary mx-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Download TCI" target="_blank" href="{{ route('clerk.downloadLLI', base64_encode($tci->loading_number.':'.'1')) }}">
                                                            Download TCI
                                                        </a>
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
