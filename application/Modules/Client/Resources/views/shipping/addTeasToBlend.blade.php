@extends('client::layouts.default')
@section('client::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">SI NUMBER {{ $bs->blend_number }} <span class="text-danger"></span> <span class="text-success"> </span></h5>
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
                                <th>GARDEN NAME</th>
                                <th>GRADE</th>
                                <th>INVOICE NO.</th>
                                <th>SALE</th>
                                <th>PROMPT DATE</th>
                                <th>PKGS</th>
                                <th>WEIGHT</th>

                            </tr>
                            </thead>
                            <?php $inputPacks = 0; $inputWeight = 0; ?>

                            <tbody>
                            @foreach($teas as $shipping)
                                    <?php
                                    $inputPacks += $shipping->blended_packages;
                                    $inputWeight += $shipping->blended_weight;
                                    ?>
                                <tr>
                                    <td> {{ $loop->iteration }} </td>
                                    <td> {{ $shipping->garden_name == null ? $shipping->garden : $shipping->garden_name  }} </td>
                                    <td> {{ $shipping->grade_name == null ? $shipping->grade : $shipping->grade_name }} </td>
                                    <td> {{ $shipping->invoice_number == null ? $shipping->blend_number : $shipping->invoice_number }} </td>
                                    <td> {{ $shipping->sale_number == null ? 'B/RM' : $shipping->sale_number }} </td>
                                    <td> {{ $shipping->prompt_date == null ? $shipping->blend_date : $shipping->prompt_date }} </td>
                                    <td> {{ $shipping->blended_packages }} </td>
                                    <td> {{ $shipping->blended_weight }} </td>
                                </tr>

                            @endforeach
                            </tbody>
                            <tr>
                                <td colspan="6" style="text-align: center !important;">TOTALS</td>
                                <td style="text-align: right !important;">{{ number_format($inputPacks, 2) }}</td>
                                <td style="text-align: right !important;">{{ number_format($inputWeight, 2) }}</td>
                            </tr>
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
                                <p class="lh-sm mb-0 text-700">Client Name :<span class="text-900 ps-2">{{ $bs->client_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Client Email :<span class="text-900 ps-2">{{ $bs->email == null ? 'Not updated' : $bs->email }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Client Phone :<span class="text-900 ps-2">{{ $bs->client_phone == null ? 'Not updated' : $bs->client_phone }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-info bg-opacity-25"></span>
                                <p class="lh-sm mb-0 text-700">Client Address :<span class="text-900 ps-2">{{ $bs->address == null ? 'Not updated' : $bs->address }}</span></p>
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
                                <p class="lh-sm mb-0 text-700">SI Number :<span class="text-900 ps-2">{{ $bs->blend_number }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Load Type :<span class="text-900 ps-2">{{ $bs->load_type == 1 ? 'LOOSE LOADING' : 'PALLETIZED LOADING'}}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Vessel Name :<span class="text-900 ps-2">{{ $bs->vessel_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Destination :<span class="text-900 ps-2">{{ $bs->port_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Shipping Mark :<span class="text-900 ps-2">{{ $bs->shipping_mark }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Shipping Instruction :<span class="text-900 ps-2">{{ $bs->standard_details }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Input Packages :<span class="text-900 ps-2">{{ number_format($inputPacks, 2) }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Input Weight :<span class="text-900 ps-2">{{ number_format($inputWeight, 2) }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Output Packages :<span class="text-900 ps-2">{{ $bs->outputPackages }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Output Weight :<span class="text-900 ps-2">{{ $bs->outputWeight }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Status :<span class="text-900 ps-2">
                                        {!! $bs->status == 0 ? '<span class="badge bg-warning"> Blend Created </span>' : ($bs->status == 1 ? '<span class="badge bg-info"> Teas Updated </span>' : ($bs->status == 2 ? '<span class="badge bg-secondary"> Blend Updated </span>' : ($bs->status == 3 ? '<span class="badge bg-dark"> Pend. Approval </span>' : '<span class="badge bg-success"> Shipped on'. \Carbon\Carbon::createFromTimestamp($bs->ship_date)->format('D, d M Y H:i') .'</span>'))) !!}
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
                                <p class="lh-sm mb-0 text-700">Transporter :<span class="text-900 ps-2">{{ $bs->transporter_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Clearing Agent :<span class="text-900 ps-2">{{ $bs->agent_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Driver Name :<span class="text-900 ps-2">{{ $bs->driver_name }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Driver Phone :<span class="text-900 ps-2">{{ $bs->phone }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Vehicle Reg :<span class="text-900 ps-2">{{ $bs->registration }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Consignee :<span class="text-900 ps-2">{{ $bs->consignee }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Container Size :<span class="text-900 ps-2">{{ $bs->container_size == 1 ? '20 FT' :($bs->conatiner_size == 2 ? '40 FT' : '40 FTHC') }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Container No :<span class="text-900 ps-2">{{ \App\Models\ShipmentContainer::where('blend_id', $bs->blend_id)->count() }} CONTAINER(S) </span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Container Tare :<span class="text-900 ps-2">{{ $bs->container_tare }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-75"></span>
                                <p class="lh-sm mb-0 text-700">Seal Number : <span class="text-900 ps-2">{{ $bs->seal_number }}</span></p>
                            </li>
                            <li class="d-flex align-items-center fs-11 fw-medium pt-1 mb-3"><span class="dot bg-primary bg-opacity-50"></span>
                                <p class="lh-sm mb-0 text-700">Cargo Escorted :<span class="text-900 ps-2">{{ $bs->escort == 1 ? 'Yes' : ($bs->escort == 2 ? 'No' : null) }}</span></p>
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
        $('#datatable1').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $('#datatable2').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });

</script>

