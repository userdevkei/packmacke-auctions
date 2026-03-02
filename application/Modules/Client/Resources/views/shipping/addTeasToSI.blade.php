@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">SI NUMBER {{ $si->shipping_number }} <span class="text-danger"></span> <span class="text-success"> </span></h5>
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
                        <table class="table mb-0 table-bordered table-striped" id="datatable">
                            <thead class="bg-200">
                            <tr>
                                <th>#</th>
                                <th>Garden Name</th>
                                <th>Grade Name</th>
                                <th>Invoice Number </th>
                                <th>Lot Number</th>
                                <th>Packages</th>
                                <th>Net Weight</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $inputPacks = 0; $inputWeight = 0; ?>
                            @foreach($teas as $transfer)
                                @php
                                $inputPacks +=  str_replace(',', '',$transfer->shipped_packages);
                                $inputWeight += str_replace(',', '', $transfer->shipped_weight);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $transfer->garden_name }}</td>
                                    <td>{{ $transfer->grade_name }}</td>
                                    <td>{{ $transfer->invoice_number }}</td>
                                    <td>{{ $transfer->lot_number }}</td>
                                    <td>{{ $transfer->shipped_packages }}</td>
                                    <td>{{ $transfer->shipped_weight }} </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align: center !important;">Totals</td>
                                    <td>{{ number_format($inputPacks, 2) }}</td>
                                    <td>{{ number_format($inputWeight, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                </div>
            </div>
            <h5 class="mt-3">SHIPMENT DETAILS</h5>
            <div class="row g-3 font-sans-serif mt-1">
                <div class="col-sm-4">
                    <div class="rounded-3 border p-3 h-100">
                        <div class="d-flex align-items-center mb-4"><span class="dot bg-info bg-opacity-25"></span>
                            <h6 class="mb-0 fw-bold">Client Details</h6>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-100"></span>
                                <p class="lh-sm mb-0 text-700">Client Name :<span class="text-900 ps-2">{{ $si->client_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Client Email :<span class="text-900 ps-2">{{ $si->email == null ? 'Not updated' : $si->email }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Client Phone :<span class="text-900 ps-2">{{ $si->client_phone == null ? 'Not updated' : $si->client_phone }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-25"></span>
                                <p class="lh-sm mb-0 text-700">Client Address :<span class="text-900 ps-2">{{ $si->address == null ? 'Not updated' : $si->address }}</span></p>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="rounded-3 border p-3 h-100">
                        <div class="d-flex align-items-center mb-4"><span class="dot bg-primary"></span>
                            <h6 class="mb-0 fw-bold">Shipment Details</h6>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">SI Number :<span class="text-900 ps-2">{{ $si->shipping_number }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Load Type :<span class="text-900 ps-2">{{ $si->load_type == 1 ? 'LOOSE LOADING' : 'PALLETIZED LOADING'}}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Vessel Name :<span class="text-900 ps-2">{{ $si->vessel_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Destination :<span class="text-900 ps-2">{{ $si->port_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Consignee :<span class="text-900 ps-2">{{ $si->consignee }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Container Size :<span class="text-900 ps-2">{{ $si->container_size == 1 ? '20 FT' :($si->conatiner_size == 2 ? '40 FT' : '40 FTHC') }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Shipping Mark :<span class="text-900 ps-2">{{ $si->shipping_mark }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Shipping Instruction :<span class="text-900 ps-2">{{ $si->shipping_instructions }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Status :<span class="text-900 ps-2">
                                        {!! $si->status == 0 ? '<span class="badge bg-warning"> SI Created </span>' : ($si->status == 1 ? '<span class="badge bg-info"> Teas Updated </span>' : ($si->status == 2 ? '<span class="badge bg-secondary"> SI Updated </span>' : ($si->status == 3 ? '<span class="badge bg-dark"> Pend. Approval </span>' : '<span class="badge bg-success"> Shipped on'. \Carbon\Carbon::createFromTimestamp($si->ship_date)->format('D, d M Y H:i') .'</span>'))) !!}
                                    </span>
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="rounded-3 border p-3 h-100">
                        <div class="d-flex align-items-center mb-4"><span class="dot bg-primary"></span>
                            <h6 class="mb-0 fw-bold">Logistics</h6>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Transporter :<span class="text-900 ps-2">{{ $si->transporter_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Clearing Agent :<span class="text-900 ps-2">{{ $si->agent_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Driver Name :<span class="text-900 ps-2">{{ $si->driver_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Driver Phone :<span class="text-900 ps-2">{{ $si->phone }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Vehicle Reg :<span class="text-900 ps-2">{{ $si->registration }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Container No :<span class="text-900 ps-2">{{ $si->container_number }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Container Tare :<span class="text-900 ps-2">{{ $si->container_tare }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Seal Number : <span class="text-900 ps-2">{{ $si->seal_number }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Cargo Escorted :<span class="text-900 ps-2">{{ $si->escort == 1 ? 'Yes' : ($si->escort == 2 ? 'No' : null) }}</span></p>
                            </li>
                        </ul>
                    </div>
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
            pageLength: 100
        });
    });

</script>

