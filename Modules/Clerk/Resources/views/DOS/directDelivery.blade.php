{{--@extends('clerk::layouts.default')--}}
{{--@section('clerk::dashboard')--}}
{{--    <div class="card">--}}
{{--        <div class="card-header">--}}
{{--            <div class="row flex-between-center">--}}
{{--                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">--}}
{{--                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Direct Deliveries </h5>--}}
{{--                </div>--}}
{{--                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">--}}
{{--                        <div id="table-simple-pagination-replace-element">--}}
{{--                            @if (in_array(auth()->user()->role_id, [2, 3, 5]) || @canuser('direct-deliver-teas.add'))--}}
{{--                                <a class="btn btn-falcon-default btn-sm" href="{{ route('clerk.addDirectDelivery') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New</span></a>--}}
{{--                            @endif--}}
{{--                            @if(in_array(auth()->user()->role_id, [2, 3, 5]))--}}
{{--                                <a class="btn btn-falcon-danger btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-upload" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Import Teas</span></a>--}}
{{--                            @endif--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--            </div>--}}
{{--            <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">--}}
{{--                <div class="modal-dialog modal-lg mt-6" role="document">--}}
{{--                    <div class="modal-content border-0">--}}
{{--                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">--}}
{{--                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>--}}
{{--                        </div>--}}
{{--                        <div class="modal-body p-0">--}}
{{--                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">--}}
{{--                                <h5 class="mb-1" id="staticBackdropLabel">Import Direct Delivery From Excel</h5>--}}
{{--                            </div>--}}
{{--                            <div class="p-4">--}}
{{--                                <div class="row">--}}
{{--                                    <div class="mt-2 mb-3">--}}
{{--                                        <a class="btn btn-sm btn-info" href="{{ route('clerk.downloadTemplate') }}"><i class="fa fa-file-download"></i> Download Template</a>--}}
{{--                                    </div>--}}
{{--                                    <div class="alert bg-danger text-white">Production and Expiry Date Format to be in the format of 31/01/2020</div>--}}
{{--                                    --}}{{--<form method="POST" action="{{ route('clerk.previewImport') }}" enctype="multipart/form-data">--}}
{{--                                        @csrf--}}
{{--                                        <div class="row row-cols-sm-1 g-2 mt-3">--}}
{{--                                            <div class="mb-3">--}}
{{--                                                <label for="organizerSingle">CLIENT</label>--}}
{{--                                                <select class="form-select js-choice" name="clientId" size="1" required data-options='{"removeItemButton":true,"placeholder":true}'>--}}
{{--                                                    <option selected disabled value="">Select Client...</option>--}}
{{--                                                    @foreach($clients as $client)--}}
{{--                                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                            <div class="mb-2">--}}
{{--                                                <label for="organizerSingle">PHML WAREHOUSE</label>--}}
{{--                                                <select class="form-select js-choice" id="selectWarehouse" size="1" name="stationId" required data-options='{"removeItemButton":true,"placeholder":true}'>--}}
{{--                                                    <option disabled selected value="">Select PHML Warehouse...</option>--}}
{{--                                                    @foreach($stations as $station)--}}
{{--                                                        <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}

{{--                                            <div class="mb-3">--}}
{{--                                                <label for="organizerSingle">PHML WAREHOUSE BAYS</label>--}}
{{--                                                <select class="form-select" id="selectWarehouseBay" size="1" name="bayId" style="height: 60% !important;" required data-options='{"removeItemButton":true,"placeholder":true}'>--}}
{{--                                                    <option disabled selected value="">Select Warehouse Bay...</option>--}}
{{--                                                </select>--}}
{{--                                            </div>--}}

{{--                                            <div class="mb-2">--}}
{{--                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">EXCEL FILE</label>--}}
{{--                                                <input type="file" name="uploadFile" class="form-control" required placeholder="--" style="height: 64% !important;">--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="d-flex justify-content-center mt-4">--}}
{{--                                            <button type="submit" id="submitButton" class="btn btn-success col-8">UPLOAD STOCK</button>--}}
{{--                                        </div>--}}
{{--                                    </form>--}}

{{--                                    --}}{{-- In your existing modal, keep all fields the same but change the form: --}}
{{--                                    <form id="importForm" enctype="multipart/form-data">--}}
{{--                                        @csrf--}}
{{--                                        <div class="row row-cols-sm-1 g-2 mt-3">--}}
{{--                                            <div class="mb-3">--}}
{{--                                                <label>CLIENT</label>--}}
{{--                                                <select class="form-select js-choice" name="clientId" required--}}
{{--                                                        data-options='{"removeItemButton":true,"placeholder":true}'>--}}
{{--                                                    <option selected disabled value="">Select Client...</option>--}}
{{--                                                    @foreach($clients as $client)--}}
{{--                                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                            <div class="mb-2">--}}
{{--                                                <label>PHML WAREHOUSE</label>--}}
{{--                                                <select class="form-select js-choice" id="selectWarehouse" name="stationId" required--}}
{{--                                                        data-options='{"removeItemButton":true,"placeholder":true}'>--}}
{{--                                                    <option disabled selected value="">Select PHML Warehouse...</option>--}}
{{--                                                    @foreach($stations as $station)--}}
{{--                                                        <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                            <div class="mb-3">--}}
{{--                                                <label>PHML WAREHOUSE BAYS</label>--}}
{{--                                                <select class="form-select" id="selectWarehouseBay" name="bayId" required>--}}
{{--                                                    <option disabled selected value="">Select Warehouse Bay...</option>--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                            <div class="mb-2">--}}
{{--                                                <label class="fw-bold fs-xs">EXCEL FILE</label>--}}
{{--                                                <input type="file" name="uploadFile" id="importFile" class="form-control"--}}
{{--                                                       required accept=".xlsx,.xls">--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div id="importError" class="alert alert-danger mt-2 d-none"></div>--}}
{{--                                        <div class="d-flex justify-content-center mt-4">--}}
{{--                                            <button type="button" id="previewBtn" class="btn btn-success col-8">--}}
{{--                                                <span id="previewBtnText">PREVIEW RECORDS</span>--}}
{{--                                                <span id="previewBtnSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>--}}
{{--                                            </button>--}}
{{--                                        </div>--}}
{{--                                    </form>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            @if (session('importErrors'))--}}
{{--                <div class="alert alert-warning mt-2">--}}
{{--                    <ol>--}}
{{--                        @foreach (session('importErrors') as $error)--}}
{{--                            <li>{{ $error }}</li>--}}
{{--                        @endforeach--}}
{{--                    </ol>--}}
{{--                </div>--}}
{{--            @endif--}}
{{--        </div>--}}
{{--        <div class="card-body overflow-hidden p-lg-3">--}}
{{--            <div class="row align-items-center">--}}
{{--                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">--}}
{{--                    <table class="table mb-0 table-bordered table-striped" id="datatable">--}}
{{--                        <thead class="bg-200">--}}
{{--                        <tr>--}}
{{--                            <th>#</th>--}}
{{--                            <th>Delivery #</th>--}}
{{--                            <th>Client Name</th>--}}
{{--                            <th>Tea Type</th>--}}
{{--                            <th>Packaging</th>--}}
{{--                            <th>Packages </th>--}}
{{--                            <th>Net Weight</th>--}}
{{--                            <th>Producer Whs</th>--}}
{{--                            <th>Destination</th>--}}
{{--                            <th>Status</th>--}}
{{--                            <th nowrap=""></th>--}}
{{--                        </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                        @foreach($orders as $order)--}}
{{--                            <tr>--}}
{{--                                <td>{{ $loop->iteration }}</td>--}}
{{--                                <td>{{ $order->delivery_number }}</td>--}}
{{--                                <td>{{ $order->client_name }}</td>--}}
{{--                                <td>{{ $order->tea_id == 1 ? 'AUCTION TEA' : ($order->tea_id  == 2 ? 'PRIVATE TEA' : ($order->tea_id  == 3 ? 'FACTORY TEA' : 'BLEND REMNANTS')) }}</td>--}}
{{--                                <td>{{ $order->packet == 1 ? 'PB' : 'PS' }}</td>--}}
{{--                                <td>{{ $order->total_packages }}</td>--}}
{{--                                <td>{{ $order->total_net_weight }}</td>--}}
{{--                                <td>{{ $order->warehouse_name }}</td>--}}
{{--                                <td>{{ $order->station_name }}</td>--}}
{{--                                <td>{!! $order->order_status == null ? '<span class="text-danger">Pending <span>' : '<span class="text-success">Stocked</span>' !!}</td>--}}
{{--                                <td nowrap="">--}}
{{--                                    @if($order->order_status > 0)--}}
{{--                                        <a onclick="return false;" class="link-success fs-sm mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Teas received and stock updated">--}}
{{--                                            <span class="fas fa-check"></span>--}}
{{--                                        </a>--}}
{{--                                    @else--}}
{{--                                        <a onclick="return confirm('Are you sure you want to receive all teas under this delivery?')" class="link-danger d-inline-block fs-sm mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Receive teas under this delivery" href="{{ route('clerk.receiveDirectDeliveries', base64_encode($order->delivery_number)) }}"> <span class="fas fa-compress-alt"></span> </a>--}}
{{--                                        <a class="link-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"> <span class="fas fa-compress-alt"></span><span class="d-none d-sm-inline-block ms-1"></span></a>--}}

{{--                                        <div class="modal fade" id="staticBackdrop-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">--}}
{{--                                            <div class="modal-dialog modal-lg mt-6" role="document">--}}
{{--                                                <div class="modal-content border-0">--}}
{{--                                                    <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">--}}
{{--                                                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>--}}
{{--                                                    </div>--}}
{{--                                                    <div class="modal-body p-0">--}}
{{--                                                        <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">--}}
{{--                                                            <h5 class="mb-1" id="staticBackdropLabel">Upload Delivery Note Image to Del #{{ $order->delivery_number }}</h5>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="p-4">--}}
{{--                                                            <form method="POST" action="{{ route('clerk.receiveDirectDeliveries', base64_encode($order->delivery_number)) }}" enctype="multipart/form-data">--}}
{{--                                                                @csrf--}}
{{--                                                                <div class="row row-cols-sm-1 g-2">--}}
{{--                                                                    <div class="mb-0">--}}
{{--                                                                        <label class="my-1 fs-xs fw-bold">Delivery Note</label>--}}
{{--                                                                        <input type="file" class="form-control" name="delivery_note" required style="height: 62% !important;" accept="image/png,image/jpeg,image/jpg,application/pdf">--}}
{{--                                                                    </div>--}}
{{--                                                                </div>--}}
{{--                                                                <div class="d-flex justify-content-center mt-4 mb-2">--}}
{{--                                                                    <button type="submit" id="submitButton" class="btn btn-success col-8">Update Details</button>--}}
{{--                                                                </div>--}}
{{--                                                            </form>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    @endif--}}

{{--                                    @if(@canuser('transporter-details.update') || @canuser('direct-deliver-transport-details.update') || $order->user_id == auth()->user()->user_id)--}}
{{--                                        <a class="link-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"><span class="fas fa-edit"></span><span class="d-none d-sm-inline-block ms-1"></span></a>--}}

{{--                                        <div class="modal fade" id="staticBackdrop{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">--}}
{{--                                                <div class="modal-dialog modal-xl mt-6" role="document">--}}
{{--                                                    <div class="modal-content border-0">--}}
{{--                                                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">--}}
{{--                                                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="modal-body p-0">--}}
{{--                                                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">--}}
{{--                                                                <h5 class="mb-1" id="staticBackdropLabel">Update Del #{{ $order->delivery_number }} Transporter Details</h5>--}}
{{--                                                            </div>--}}
{{--                                                            <div class="p-4">--}}
{{--    --}}{{--                                                            <div class="row">--}}

{{--                                                                    <form method="POST" action="{{ route('clerk.updateTransporterDetails', base64_encode($order->delivery_number)) }}" enctype="multipart/form-data">--}}
{{--                                                                        @csrf--}}
{{--                                                                            <div class="row row-cols-sm-3 g-2">--}}
{{--                                                                                <div class="mb-0">--}}
{{--                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">TRANSPORTER</label>--}}
{{--                                                                                    <select name="transporter" id="transporterSelect2" class="form-select js-choice" required onchange="toggleOtherInput(this, 'otherTransporterInput2')">--}}
{{--                                                                                        <option selected disabled value="">-- select transporter --</option>--}}
{{--                                                                                        @foreach($transporters as $transporter)--}}
{{--                                                                                            <option @selected($transporter->transporter_id == $order->transporter_id) value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>--}}
{{--                                                                                        @endforeach--}}
{{--                                                                                        <option value="other">Other</option>--}}
{{--                                                                                    </select>--}}
{{--                                                                                    <input type="text" name="transporter_other" id="otherTransporterInput2" class="form-control mt-2 d-none" placeholder="Enter other transporter name" style="height: 34% !important;">--}}
{{--                                                                                </div>--}}

{{--                                                                                <div class="mb-0">--}}
{{--                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>--}}
{{--                                                                                    <input class="form-control" value="{{ $order->registration }}" name="registration" type="text" placeholder="-- plate number --" required  style="height: 46%;">--}}
{{--                                                                                </div>--}}

{{--                                                                                <div class="mb-0">--}}
{{--                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>--}}
{{--                                                                                    <input id="idSelect" type="text" list="idList" name="idNumber" class="form-control idSelect" placeholder="-- Driver's ID Number --" required style="height: 48% !important;" value="{{ $order->id_number }}">--}}
{{--                                                                                    <datalist id="idList">--}}
{{--                                                                                        @foreach($users as $user)--}}
{{--                                                                                            <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>--}}
{{--                                                                                        @endforeach--}}
{{--                                                                                    </datalist>--}}
{{--                                                                                </div>--}}

{{--                                                                                <div class="mb-0">--}}
{{--                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>--}}
{{--                                                                                    <input type="text" value="{{ $order->driver_name }}" name="driverName" id="driverName" class="form-control driverName" required style="height: 67% !important;">--}}
{{--                                                                                </div>--}}

{{--                                                                                <div class="mb-0">--}}
{{--                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>--}}
{{--                                                                                    <input type="text" name="driverPhone" value="{{ $order->phone }}" id="driverPhone" class="form-control driverPhone" required style="height: 67% !important;">--}}
{{--                                                                                </div>--}}
{{--                                                                            </div>--}}
{{--                                                                            <div class="d-flex justify-content-center mt-4 mb-2">--}}
{{--                                                                                <button type="submit" id="submitButton" class="btn btn-success col-8">Update Details</button>--}}
{{--                                                                            </div>--}}
{{--                                                                    </form>--}}
{{--    --}}{{--                                                            </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                    @endif--}}

{{--                                    <a class="link-info fs-sm" href="{{ route('clerk.viewDirectDeliveryOrder', base64_encode($order->delivery_number)) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="View Direct Del Details" ><span class="fa fa-info"></span> </a>--}}
{{--                                    <a class="text-secondary mx-2" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" title="Tally of goods received" href="{{ route('clerk.downloadDirectDeliveries', base64_encode($order->delivery_number . ':' . '1')) }}">--}}
{{--                                        <span class="fas fa-print"></span>--}}
{{--                                    </a>--}}
{{--                                        @if($order->path)--}}
{{--                                            <a class="text-secondary mx-2" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" title="Delivery Note Document" href="{{ route('clerk.downloadDeliveryNote', base64_encode($order->delivery_number)) }}">--}}
{{--                                                <span class="fas fa-file"></span>--}}
{{--                                            </a>--}}
{{--                                        @endif--}}
{{--                                </td>--}}

{{--                            </tr>--}}
{{--                        @endforeach--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--    </div>--}}
{{--    <script>--}}
{{--        $(document).ready(function() {--}}
{{--            $('#datatable').DataTable( {--}}
{{--                order: [ 0, 'asc' ],--}}
{{--                pageLength: 50--}}
{{--            } );--}}

{{--            $('#selectedWarehouse').on('change', function() {--}}
{{--                var selectedStation = $(this).val();--}}
{{--                console.log(selectedStation)--}}
{{--                $.ajax({--}}
{{--                    type: 'GET',--}}
{{--                    url: '{{ route('clerk.filterWarehouseBay') }}',--}}
{{--                    data: {--}}
{{--                        selectedStation--}}
{{--                    },--}}
{{--                    success: function(response) {--}}
{{--                        console.log(response)--}}

{{--                        $('#warehouseBay').empty();--}}

{{--                        // Append the default option--}}
{{--                        $('#warehouseBay').append('<option disabled selected class="text-center" value="">-- Select warehouse bay --</option>' );--}}

{{--                        // Populate the select element with options from the response--}}
{{--                        $.each(response, function(index, bay) {--}}
{{--                            $('#warehouseBay').append('<option value="' + bay.bay_id + '">' + bay.bay_name + '</option>');--}}
{{--                        });--}}
{{--                    }--}}

{{--                });--}}

{{--            });--}}

{{--            $('#selectWarehouse').on('change', function() {--}}
{{--                var selectedStation = $(this).val();--}}
{{--                console.log(selectedStation)--}}
{{--                $.ajax({--}}
{{--                    type: 'GET',--}}
{{--                    url: '{{ route('clerk.filterWarehouseBay') }}',--}}
{{--                    data: {--}}
{{--                        selectedStation--}}
{{--                    },--}}
{{--                    success: function(response) {--}}
{{--                        console.log(response)--}}

{{--                        $('#selectWarehouseBay').empty();--}}

{{--                        // Append the default option--}}
{{--                        $('#selectWarehouseBay').append('<option disabled selected class="text-center" value="">-- Select warehouse bay --</option>' );--}}

{{--                        // Populate the select element with options from the response--}}
{{--                        $.each(response, function(index, bay) {--}}
{{--                            $('#selectWarehouseBay').append('<option value="' + bay.bay_id + '">' + bay.bay_name + '</option>');--}}
{{--                        });--}}
{{--                    }--}}

{{--                });--}}

{{--            });--}}

{{--            $('.idSelect').on('change', function () {--}}

{{--                var idNumber = $(this).val();--}}

{{--                $.ajax({--}}
{{--                    url: '{{ route('clerk.fetchIdNumber') }}',--}}
{{--                    method: 'GET',--}}
{{--                    data: {idNumber},--}}
{{--                    dataType: 'json',--}}
{{--                    success: function (response) {--}}
{{--                        console.log('Success:', response.driver_name);--}}

{{--                        $('.driverName').val(response.driver_name)--}}
{{--                        $('.driverPhone').val(response.driver_phone)--}}
{{--                    },--}}
{{--                    error: function (xhr, status, error) {--}}
{{--                        // Function to handle errors--}}
{{--                        console.error('Error:', error);--}}
{{--                        $('#driverName').val('')--}}
{{--                        $('#driverPhone').val('')--}}
{{--                    }--}}
{{--                });--}}
{{--            });--}}
{{--        });--}}
{{--    </script>--}}
{{--    <script>--}}
{{--        function toggleOtherInput(selectEl, inputId) {--}}
{{--            const inputEl = document.getElementById(inputId);--}}
{{--            if (selectEl.value === 'other') {--}}
{{--                inputEl.classList.remove('d-none');--}}
{{--                inputEl.required = true;--}}
{{--            } else {--}}
{{--                inputEl.classList.add('d-none');--}}
{{--                inputEl.required = false;--}}
{{--            }--}}
{{--        }--}}
{{--    </script>--}}
{{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>--}}
{{--    <script>--}}
{{--        document.getElementById('previewBtn').addEventListener('click', function () {--}}
{{--            const fileInput  = document.getElementById('importFile');--}}
{{--            const clientId   = document.querySelector('[name="clientId"]').value;--}}
{{--            const stationId  = document.querySelector('[name="stationId"]').value;--}}
{{--            const bayId      = document.getElementById('selectWarehouseBay').value;--}}
{{--            const errorBox   = document.getElementById('importError');--}}
{{--            const btnText    = document.getElementById('previewBtnText');--}}
{{--            const spinner    = document.getElementById('previewBtnSpinner');--}}

{{--            errorBox.classList.add('d-none');--}}

{{--            if (!clientId || !stationId || !bayId) {--}}
{{--                errorBox.textContent = 'Please select Client, Warehouse and Bay before previewing.';--}}
{{--                errorBox.classList.remove('d-none');--}}
{{--                return;--}}
{{--            }--}}
{{--            if (!fileInput.files.length) {--}}
{{--                errorBox.textContent = 'Please select an Excel file.';--}}
{{--                errorBox.classList.remove('d-none');--}}
{{--                return;--}}
{{--            }--}}

{{--            btnText.textContent = 'Reading file...';--}}
{{--            spinner.classList.remove('d-none');--}}

{{--            const reader = new FileReader();--}}
{{--            reader.onload = function (e) {--}}
{{--                try {--}}
{{--                    const workbook  = XLSX.read(e.target.result, { type: 'binary', cellDates: true });--}}
{{--                    const sheet     = workbook.Sheets[workbook.SheetNames[0]]; // Sheet1--}}
{{--                    const rows      = XLSX.utils.sheet_to_json(sheet, { defval: '', raw: false });--}}

{{--                    if (!rows.length) {--}}
{{--                        errorBox.textContent = 'The Excel file has no data rows.';--}}
{{--                        errorBox.classList.remove('d-none');--}}
{{--                        btnText.textContent = 'PREVIEW RECORDS';--}}
{{--                        spinner.classList.add('d-none');--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    // POST JSON to controller--}}
{{--                    fetch('{{ route('clerk.previewImport') }}', {--}}
{{--                        method: 'POST',--}}
{{--                        headers: {--}}
{{--                            'Content-Type': 'application/json',--}}
{{--                            'X-CSRF-TOKEN': '{{ csrf_token() }}',--}}
{{--                            'Accept': 'application/json',--}}
{{--                        },--}}
{{--                        body: JSON.stringify({--}}
{{--                            clientId,--}}
{{--                            stationId,--}}
{{--                            bayId,--}}
{{--                            records: rows,--}}
{{--                        }),--}}
{{--                    })--}}
{{--                        .then(res => res.json())--}}
{{--                        .then(data => {--}}
{{--                            if (data.success) {--}}
{{--                                // Redirect to preview page--}}
{{--                                window.location.href = data.redirect;--}}
{{--                            } else {--}}
{{--                                errorBox.textContent = data.message ?? 'Something went wrong.';--}}
{{--                                errorBox.classList.remove('d-none');--}}
{{--                            }--}}
{{--                        })--}}
{{--                        .catch(() => {--}}
{{--                            errorBox.textContent = 'Network error. Please try again.';--}}
{{--                            errorBox.classList.remove('d-none');--}}
{{--                        })--}}
{{--                        .finally(() => {--}}
{{--                            btnText.textContent = 'PREVIEW RECORDS';--}}
{{--                            spinner.classList.add('d-none');--}}
{{--                        });--}}

{{--                } catch (err) {--}}
{{--                    errorBox.textContent = 'Failed to read Excel file: ' + err.message;--}}
{{--                    errorBox.classList.remove('d-none');--}}
{{--                    btnText.textContent = 'PREVIEW RECORDS';--}}
{{--                    spinner.classList.add('d-none');--}}
{{--                }--}}
{{--            };--}}
{{--            reader.readAsBinaryString(fileInput.files[0]);--}}
{{--        });--}}
{{--    </script>--}}
{{--@endsection--}}

@extends('clerk::layouts.default')
@section('clerk::dashboard')

    <div class="card mb-3">
        <div class="card-header py-3">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0">Direct Deliveries</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0 d-flex gap-2 justify-content-end">
                    @if(in_array(auth()->user()->role_id, [2, 3, 5]) || @canuser('direct-deliver-teas.add'))
                        <a class="btn btn-falcon-default btn-sm" href="{{ route('clerk.addDirectDelivery') }}">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">New</span>
                        </a>
                    @endif
                    @if(in_array(auth()->user()->role_id, [2, 3, 5]))
                        <a class="btn btn-falcon-danger btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                            <span class="fas fa-upload" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Import Teas</span>
                        </a>
                        {{-- Export respects current filters --}}
                        <a class="btn btn-falcon-success btn-sm"
                           href="{{ route('clerk.viewDirectDeliveries', array_merge(request()->query(), ['export' => 1])) }}">
                            <span class="fas fa-file-excel" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Export</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ FILTER BAR ═══ --}}
        <div class="card-body border-bottom py-3 bg-body-tertiary">
            <form method="GET" action="{{ route('clerk.viewDirectDeliveries') }}" id="filterForm">
                <div class="row g-2 align-items-end">

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">DELIVERY #</label>
                        <input type="text" name="delivery_number" class="form-control form-control-sm"
                               placeholder="Search..." value="{{ request('delivery_number') }}">
                    </div>

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">CLIENT</label>
                        <select name="client_id" class="form-select form-select-sm">
                            <option value="">All Clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}" @selected(request('client_id') == $client->client_id)>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">TRANSPORTER</label>
                        <select name="transporter_id" class="form-select form-select-sm">
                            <option value="">All Transporters</option>
                            @foreach($transporters as $t)
                                <option value="{{ $t->transporter_id }}" @selected(request('transporter_id') == $t->transporter_id)>
                                    {{ $t->transporter_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">DISPATCH FROM</label>
                        <input type="date" name="dispatch_from" class="form-control form-control-sm"
                               value="{{ request('dispatch_from') }}">
                    </div>

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">DISPATCH TO</label>
                        <input type="date" name="dispatch_to" class="form-control form-control-sm"
                               value="{{ request('dispatch_to') }}">
                    </div>

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">ARRIVAL FROM</label>
                        <input type="date" name="arrival_from" class="form-control form-control-sm"
                               value="{{ request('arrival_from') }}">
                    </div>

                    <div class="col-sm-6 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold fs-xs mb-1">ARRIVAL TO</label>
                        <input type="date" name="arrival_to" class="form-control form-control-sm"
                               value="{{ request('arrival_to') }}">
                    </div>

                    <div class="col-sm-6 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <span class="fas fa-filter me-1"></span> Filter
                        </button>
                        <a href="{{ route('clerk.viewDirectDeliveries') }}" class="btn btn-falcon-default btn-sm px-3">
                            <span class="fas fa-times me-1"></span> Clear
                        </a>
                    </div>

                </div>
            </form>
        </div>

        {{-- ═══ IMPORT ERRORS ═══ --}}
        @if(session('importErrors'))
            <div class="alert alert-warning mx-3 mt-3 mb-0">
                <strong>Import completed with errors:</strong>
                <ol class="mb-0 mt-1">
                    @foreach(session('importErrors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success mx-3 mt-3 mb-0">{{ session('success') }}</div>
        @endif

        {{-- ═══ TABLE ═══ --}}
        <div class="card-body overflow-hidden p-lg-3">
            <table class="table mb-0 table-bordered table-striped table-hover fs-xs" id="datatable">
                <thead class="bg-200">
                <tr>
                    <th>#</th>
                    <th>Delivery #</th>
                    <th>Client</th>
{{--                    <th>Tea Type</th>--}}
{{--                    <th>Pkg</th>--}}
                    <th>Packages</th>
                    <th>Net Weight</th>
                    <th>Producer Whs</th>
                    <th>Destination</th>
                    <th>Dispatch</th>
                    <th>Arrival</th>
                    <th>Transporter</th>
                    <th>Status</th>
                    <th class="text-end" nowrap></th>
                </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $order->delivery_number }}</td>
                        <td>{{ $order->client_name }}</td>
{{--                        <td>{{ match((int)$order->tea_id) { 1 => 'AUCTION TEA', 2 => 'PRIVATE TEA', 3 => 'FACTORY TEA', default => 'BLEND REMNANTS' } }}</td>--}}
{{--                        <td>{{ $order->package == 1 ? 'PB' : 'PS' }}</td>--}}
                        <td>{{ number_format($order->total_packages) }}</td>
                        <td>{{ number_format($order->total_net_weight, 1) }}</td>
                        <td>{{ $order->warehouse_name }}</td>
                        <td>{{ $order->station_name }}</td>
                        <td>{{ $order->dispatch_date ? \Carbon\Carbon::parse($order->dispatch_date)->format('d/m/Y') : '—' }}</td>
                        <td>{{ $order->arrival_date ? \Carbon\Carbon::parse($order->arrival_date)->format('d/m/Y') : '—' }}</td>
                        <td>{{ $order->transporter_name ?? '—' }} {{ $order->registration ? '(' . $order->registration .')' : '' }}</td>
                        <td>
                            @if($order->order_status > 0)
                                <span class="badge bg-success">Stocked</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td nowrap class="text-end">
                            {{-- Receive / upload delivery note --}}
                            @if($order->order_status > 0)
                                <a onclick="return false;" class="link-success fs-sm mx-1"
                                   data-bs-toggle="tooltip" title="Teas received and stock updated">
                                    <span class="fas fa-check"></span>
                                </a>
                            @else
                                <a class="link-danger mx-1" data-bs-toggle="modal"
                                   data-bs-target="#noteModal-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"
                                   data-bs-toggle="tooltip" title="Upload Delivery Note">
                                    <span class="fas fa-compress-alt"></span>
                                </a>
                            @endif

                            {{-- Edit transporter --}}
                            @if(@canuser('transporter-details.update') || @canuser('direct-deliver-transport-details.update') || $order->user_id == auth()->user()->user_id)
                                <a class="link-warning mx-1" data-bs-toggle="modal"
                                   data-bs-target="#transportModal-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"
                                   data-bs-toggle="tooltip" title="Edit Transporter">
                                    <span class="fas fa-edit"></span>
                                </a>
                            @endif

                            {{-- View --}}
                            <a class="link-info mx-1" href="{{ route('clerk.viewDirectDeliveryOrder', base64_encode($order->delivery_number)) }}"
                               data-bs-toggle="tooltip" title="View Details">
                                <span class="fa fa-info"></span>
                            </a>

                            {{-- Print --}}
                            <a class="text-secondary mx-1" target="_blank"
                               data-bs-toggle="tooltip" title="Print Tally"
                               href="{{ route('clerk.downloadDirectDeliveries', base64_encode($order->delivery_number . ':1')) }}">
                                <span class="fas fa-print"></span>
                            </a>

                            {{-- Delivery note file --}}
                            @if($order->path)
                                <a class="text-secondary mx-1" target="_blank"
                                   data-bs-toggle="tooltip" title="Delivery Note Document"
                                   href="{{ route('clerk.downloadDeliveryNote', base64_encode($order->delivery_number)) }}">
                                    <span class="fas fa-file"></span>
                                </a>
                            @endif
                        </td>
                    </tr>

                    {{-- Delivery Note Upload Modal --}}
                    @if($order->order_status == 0)
                        <div class="modal fade" id="noteModal-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"
                             data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1">
                            <div class="modal-dialog modal-lg mt-6" role="document">
                                <div class="modal-content border-0">
                                    <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                            <h5 class="mb-1">Upload Delivery Note — Del #{{ $order->delivery_number }}</h5>
                                        </div>
                                        <div class="p-4">
                                            <form method="POST" action="{{ route('clerk.receiveDirectDeliveries', base64_encode($order->delivery_number)) }}" enctype="multipart/form-data">
                                                @csrf
                                                <div class="mb-3">
                                                    <label class="fw-bold fs-xs">Delivery Note</label>
                                                    <input type="file" class="form-control" name="delivery_note" required
                                                           accept="image/png,image/jpeg,image/jpg,application/pdf">
                                                </div>
                                                <div class="d-flex justify-content-center mt-3">
                                                    <button type="submit" class="btn btn-success col-8">Update Details</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Transporter Update Modal --}}
                    @if(@canuser('transporter-details.update') || @canuser('direct-deliver-transport-details.update') || ($order->user_id ?? null) == auth()->user()->user_id)
                        <div class="modal fade" id="transportModal-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"
                             data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1">
                            <div class="modal-dialog modal-xl mt-6" role="document">
                                <div class="modal-content border-0">
                                    <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                            <h5 class="mb-1">Update Del #{{ $order->delivery_number }} — Transporter Details</h5>
                                        </div>
                                        <div class="p-4">
                                            <form method="POST" action="{{ route('clerk.updateTransporterDetails', base64_encode($order->delivery_number)) }}" enctype="multipart/form-data">
                                                @csrf
                                                <div class="row row-cols-sm-3 g-3">
                                                    <div>
                                                        <label class="fw-bold fs-xs">TRANSPORTER</label>
                                                        <select name="transporter" class="form-select js-choice" required
                                                                onchange="toggleOtherInput(this, 'otherTransporter-{{ base64_encode($order->delivery_number) }}')">
                                                            <option disabled selected value="">-- select --</option>
                                                            @foreach($transporters as $t)
                                                                <option value="{{ $t->transporter_id }}"
                                                                    @selected($t->transporter_id == $order->transporter_id)>
                                                                    {{ $t->transporter_name }}
                                                                </option>
                                                            @endforeach
                                                            <option value="other">Other</option>
                                                        </select>
                                                        <input type="text" name="transporter_other"
                                                               id="otherTransporter-{{ base64_encode($order->delivery_number) }}"
                                                               class="form-control mt-2 d-none"
                                                               placeholder="Enter transporter name">
                                                    </div>
                                                    <div>
                                                        <label class="fw-bold fs-xs">VEHICLE REGISTRATION</label>
                                                        <input class="form-control" value="{{ $order->registration }}"
                                                               name="registration" type="text" required placeholder="Plate number">
                                                    </div>
                                                    <div>
                                                        <label class="fw-bold fs-xs">DRIVER'S ID NUMBER</label>
                                                        <input type="text" list="idList-{{ $loop->index }}"
                                                               name="idNumber" class="form-control idSelect"
                                                               placeholder="Driver's ID Number" value="{{ $order->id_number }}">
                                                        <datalist id="idList-{{ $loop->index }}">
                                                            @foreach($users as $user)
                                                                <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                                                            @endforeach
                                                        </datalist>
                                                    </div>
                                                    <div>
                                                        <label class="fw-bold fs-xs">DRIVER'S NAME</label>
                                                        <input type="text" value="{{ $order->driver_name }}"
                                                               name="driverName" class="form-control driverName" required>
                                                    </div>
                                                    <div>
                                                        <label class="fw-bold fs-xs">DRIVER'S PHONE</label>
                                                        <input type="text" name="driverPhone" value="{{ $order->phone }}"
                                                               class="form-control driverPhone" required>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center mt-4">
                                                    <button type="submit" class="btn btn-success col-8">Update Details</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ IMPORT MODAL ═══ --}}
    <div class="modal fade" id="importModal" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg mt-6" role="document">
            <div class="modal-content border-0">
                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                        <h5 class="mb-1">Import Direct Delivery From Excel</h5>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <a class="btn btn-sm btn-info" href="{{ route('clerk.downloadTemplate') }}">
                                <i class="fa fa-file-download"></i> Download Template
                            </a>
                        </div>
                        <div class="alert bg-danger text-white py-2">
                            Production and Expiry Date Format must be <strong>31/01/2020</strong>
                        </div>
                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="row row-cols-sm-1 g-3 mt-1">
                                <div>
                                    <label class="fw-semibold fs-xs">CLIENT</label>
                                    <select class="form-select js-choice" name="clientId" required
                                            data-options='{"removeItemButton":true,"placeholder":true}'>
                                        <option selected disabled value="">Select Client...</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="fw-semibold fs-xs">PHML WAREHOUSE</label>
                                    <select class="form-select js-choice" id="selectWarehouse" name="stationId" required
                                            data-options='{"removeItemButton":true,"placeholder":true}'>
                                        <option disabled selected value="">Select PHML Warehouse...</option>
                                        @foreach($stations as $station)
                                            <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="fw-semibold fs-xs">PHML WAREHOUSE BAY</label>
                                    <select class="form-select" id="selectWarehouseBay" name="bayId" required>
                                        <option disabled selected value="">Select Warehouse Bay...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="fw-semibold fs-xs">EXCEL FILE</label>
                                    <input type="file" name="uploadFile" id="importFile" class="form-control"
                                           required accept=".xlsx,.xls">
                                </div>
                            </div>
                            <div id="importError" class="alert alert-danger mt-3 d-none"></div>
                            <div class="d-flex justify-content-center mt-4">
                                <button type="button" id="previewBtn" class="btn btn-success col-8">
                                    <span id="previewBtnText">PREVIEW RECORDS</span>
                                    <span id="previewBtnSpinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // DataTable
            $('#datatable').DataTable({
                order: [[0, 'asc']],
                pageLength: 50,
                columnDefs: [{ targets: -1, orderable: false, searchable: false }]
            });

            // Warehouse bay AJAX
            $('#selectWarehouse').on('change', function () {
                var selectedStation = $(this).val();
                $.ajax({
                    type: 'GET',
                    url: '{{ route('clerk.filterWarehouseBay') }}',
                    data: { selectedStation },
                    success: function (response) {
                        $('#selectWarehouseBay').empty()
                            .append('<option disabled selected value="">-- Select warehouse bay --</option>');
                        $.each(response, function (i, bay) {
                            $('#selectWarehouseBay').append(
                                '<option value="' + bay.bay_id + '">' + bay.bay_name + '</option>'
                            );
                        });
                    }
                });
            });

            // Driver auto-fill
            $(document).on('change', '.idSelect', function () {
                var idNumber = $(this).val();
                var $row = $(this).closest('form');
                $.ajax({
                    url: '{{ route('clerk.fetchIdNumber') }}',
                    method: 'GET',
                    data: { idNumber },
                    dataType: 'json',
                    success: function (res) {
                        $row.find('.driverName').val(res.driver_name ?? '');
                        $row.find('.driverPhone').val(res.driver_phone ?? '');
                    }
                });
            });

            // Tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        function toggleOtherInput(selectEl, inputId) {
            const inputEl = document.getElementById(inputId);
            if (!inputEl) return;
            const isOther = selectEl.value === 'other';
            inputEl.classList.toggle('d-none', !isOther);
            inputEl.required = isOther;
            if (!isOther) inputEl.value = '';
        }
    </script>

    {{-- SheetJS + Preview button logic --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById('previewBtn').addEventListener('click', function () {
            const fileInput = document.getElementById('importFile');
            const clientId  = document.querySelector('[name="clientId"]').value;
            const stationId = document.querySelector('[name="stationId"]').value;
            const bayId     = document.getElementById('selectWarehouseBay').value;
            const errorBox  = document.getElementById('importError');
            const btnText   = document.getElementById('previewBtnText');
            const spinner   = document.getElementById('previewBtnSpinner');

            errorBox.classList.add('d-none');

            if (!clientId || !stationId || !bayId) {
                errorBox.textContent = 'Please select Client, Warehouse and Bay before previewing.';
                errorBox.classList.remove('d-none');
                return;
            }
            if (!fileInput.files.length) {
                errorBox.textContent = 'Please select an Excel file.';
                errorBox.classList.remove('d-none');
                return;
            }

            btnText.textContent = 'Reading file...';
            spinner.classList.remove('d-none');

            const reader = new FileReader();
            reader.onload = function (e) {
                try {
                    const workbook = XLSX.read(e.target.result, { type: 'binary', cellDates: true });
                    const sheet    = workbook.Sheets[workbook.SheetNames[0]];
                    const rows     = XLSX.utils.sheet_to_json(sheet, { defval: '', raw: false });

                    if (!rows.length) {
                        errorBox.textContent = 'The Excel file has no data rows.';
                        errorBox.classList.remove('d-none');
                        btnText.textContent = 'PREVIEW RECORDS';
                        spinner.classList.add('d-none');
                        return;
                    }

                    fetch('{{ route('clerk.previewImport') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ clientId, stationId, bayId, records: rows }),
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = data.redirect;
                            } else {
                                errorBox.textContent = data.message ?? 'Something went wrong.';
                                errorBox.classList.remove('d-none');
                            }
                        })
                        .catch(() => {
                            errorBox.textContent = 'Network error. Please try again.';
                            errorBox.classList.remove('d-none');
                        })
                        .finally(() => {
                            btnText.textContent = 'PREVIEW RECORDS';
                            spinner.classList.add('d-none');
                        });

                } catch (err) {
                    errorBox.textContent = 'Failed to read Excel file: ' + err.message;
                    errorBox.classList.remove('d-none');
                    btnText.textContent = 'PREVIEW RECORDS';
                    spinner.classList.add('d-none');
                }
            };
            reader.readAsBinaryString(fileInput.files[0]);
        });
    </script>

@endsection
