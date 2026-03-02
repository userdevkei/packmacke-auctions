@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Direct Deliveries </h5>
                </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div id="table-simple-pagination-replace-element">
                            @if (in_array(auth()->user()->role_id, [2, 3, 5]) || @canuser('direct-deliver-teas.add'))
                                <a class="btn btn-falcon-default btn-sm" href="{{ route('clerk.addDirectDelivery') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New</span></a>
                            @endif
                            @if(in_array(auth()->user()->role_id, [2, 3, 5]))
                                <a class="btn btn-falcon-danger btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-upload" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Import Teas</span></a>
                            @endif
                        </div>
                    </div>

            </div>
            <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg mt-6" role="document">
                    <div class="modal-content border-0">
                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                <h5 class="mb-1" id="staticBackdropLabel">Import Direct Delivery From Excel</h5>
                            </div>
                            <div class="p-4">
                                <div class="row">
                                    <div class="mt-2 mb-3">
                                        <a class="btn btn-sm btn-info" href="{{ route('clerk.downloadTemplate') }}"><i class="fa fa-file-download"></i> Download Template</a>
                                    </div>
                                    <div class="alert bg-danger text-white">Production and Expiry Date Format to be in the format of 31/01/2020</div>
                                    <form method="POST" action="{{ route('clerk.importStock') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row row-cols-sm-1 g-2 mt-3">
                                            <div class="mb-3">
                                                <label for="organizerSingle">CLIENT</label>
                                                <select class="form-select js-choice" name="clientId" size="1" required data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option selected disabled value="">Select Client...</option>
                                                    @foreach($clients as $client)
                                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label for="organizerSingle">PHML WAREHOUSE</label>
                                                <select class="form-select js-choice" id="selectWarehouse" size="1" name="stationId" required data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option disabled selected value="">Select PHML Warehouse...</option>
                                                    @foreach($stations as $station)
                                                        <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="organizerSingle">PHML WAREHOUSE BAYS</label>
                                                <select class="form-select" id="selectWarehouseBay" size="1" name="bayId" style="height: 60% !important;" required data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    <option disabled selected value="">Select Warehouse Bay...</option>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">EXCEL FILE</label>
                                                <input type="file" name="uploadFile" class="form-control" required placeholder="--" style="height: 64% !important;">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center mt-4">
                                            <button type="submit" id="submitButton" class="btn btn-success col-8">UPLOAD STOCK</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if (session('importErrors'))
                <div class="alert alert-warning mt-2">
                    <ol>
                        @foreach (session('importErrors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Delivery #</th>
                            <th>Client Name</th>
                            <th>Tea Type</th>
                            <th>Packaging</th>
                            <th>Packages </th>
                            <th>Net Weight</th>
                            <th>Producer Whs</th>
                            <th>Destination</th>
                            <th>Status</th>
                            <th nowrap=""></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order->delivery_number }}</td>
                                <td>{{ $order->client_name }}</td>
                                <td>{{ $order->tea_id == 1 ? 'AUCTION TEA' : ($order->tea_id  == 2 ? 'PRIVATE TEA' : ($order->tea_id  == 3 ? 'FACTORY TEA' : 'BLEND REMNANTS')) }}</td>
                                <td>{{ $order->packet == 1 ? 'PB' : 'PS' }}</td>
                                <td>{{ $order->total_packages }}</td>
                                <td>{{ $order->total_net_weight }}</td>
                                <td>{{ $order->warehouse_name }}</td>
                                <td>{{ $order->station_name }}</td>
                                <td>{!! $order->order_status == null ? '<span class="text-danger">Pending <span>' : '<span class="text-success">Stocked</span>' !!}</td>
                                <td nowrap="">
                                    @if($order->order_status > 0)
                                        <a onclick="return false;" class="link-success fs-sm mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Teas received and stock updated">
                                            <span class="fas fa-check"></span>
                                        </a>
                                    @else
{{--                                        <a onclick="return confirm('Are you sure you want to receive all teas under this delivery?')" class="link-danger d-inline-block fs-sm mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Receive teas under this delivery" href="{{ route('clerk.receiveDirectDeliveries', base64_encode($order->delivery_number)) }}"> <span class="fas fa-compress-alt"></span> </a>--}}
                                        <a class="link-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"> <span class="fas fa-compress-alt"></span><span class="d-none d-sm-inline-block ms-1"></span></a>

                                        <div class="modal fade" id="staticBackdrop-{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg mt-6" role="document">
                                                <div class="modal-content border-0">
                                                    <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-0">
                                                        <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                            <h5 class="mb-1" id="staticBackdropLabel">Upload Delivery Note Image to Del #{{ $order->delivery_number }}</h5>
                                                        </div>
                                                        <div class="p-4">
                                                            <form method="POST" action="{{ route('clerk.receiveDirectDeliveries', base64_encode($order->delivery_number)) }}" enctype="multipart/form-data">
                                                                @csrf
                                                                <div class="row row-cols-sm-1 g-2">
                                                                    <div class="mb-0">
                                                                        <label class="my-1 fs-xs fw-bold">Delivery Note</label>
                                                                        <input type="file" class="form-control" name="delivery_note" required style="height: 62% !important;" accept="image/png,image/jpeg,image/jpg,application/pdf">
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex justify-content-center mt-4 mb-2">
                                                                    <button type="submit" id="submitButton" class="btn btn-success col-8">Update Details</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(@canuser('transporter-details.update') || @canuser('direct-deliver-transport-details.update') || $order->user_id == auth()->user()->user_id)
                                        <a class="link-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}"><span class="fas fa-edit"></span><span class="d-none d-sm-inline-block ms-1"></span></a>

                                        <div class="modal fade" id="staticBackdrop{{ str_replace(['=', '/'], ['_', '-'], base64_encode($order->delivery_number)) }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-xl mt-6" role="document">
                                                    <div class="modal-content border-0">
                                                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-0">
                                                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                                <h5 class="mb-1" id="staticBackdropLabel">Update Del #{{ $order->delivery_number }} Transporter Details</h5>
                                                            </div>
                                                            <div class="p-4">
    {{--                                                            <div class="row">--}}

                                                                    <form method="POST" action="{{ route('clerk.updateTransporterDetails', base64_encode($order->delivery_number)) }}" enctype="multipart/form-data">
                                                                        @csrf
                                                                            <div class="row row-cols-sm-3 g-2">
                                                                                <div class="mb-0">
                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">TRANSPORTER</label>
                                                                                    <select name="transporter" id="transporterSelect2" class="form-select js-choice" required onchange="toggleOtherInput(this, 'otherTransporterInput2')">
                                                                                        <option selected disabled value="">-- select transporter --</option>
                                                                                        @foreach($transporters as $transporter)
                                                                                            <option @selected($transporter->transporter_id == $order->transporter_id) value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>
                                                                                        @endforeach
                                                                                        <option value="other">Other</option>
                                                                                    </select>
                                                                                    <input type="text" name="transporter_other" id="otherTransporterInput2" class="form-control mt-2 d-none" placeholder="Enter other transporter name" style="height: 34% !important;">
                                                                                </div>

                                                                                <div class="mb-0">
                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>
                                                                                    <input class="form-control" value="{{ $order->registration }}" name="registration" type="text" placeholder="-- plate number --" required  style="height: 46%;">
                                                                                </div>

                                                                                <div class="mb-0">
                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>
                                                                                    <input id="idSelect" type="text" list="idList" name="idNumber" class="form-control idSelect" placeholder="-- Driver's ID Number --" required style="height: 48% !important;" value="{{ $order->id_number }}">
                                                                                    <datalist id="idList">
                                                                                        @foreach($users as $user)
                                                                                            <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                                                                                        @endforeach
                                                                                    </datalist>
                                                                                </div>

                                                                                <div class="mb-0">
                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>
                                                                                    <input type="text" value="{{ $order->driver_name }}" name="driverName" id="driverName" class="form-control driverName" required style="height: 67% !important;">
                                                                                </div>

                                                                                <div class="mb-0">
                                                                                    <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>
                                                                                    <input type="text" name="driverPhone" value="{{ $order->phone }}" id="driverPhone" class="form-control driverPhone" required style="height: 67% !important;">
                                                                                </div>
                                                                            </div>
                                                                            <div class="d-flex justify-content-center mt-4 mb-2">
                                                                                <button type="submit" id="submitButton" class="btn btn-success col-8">Update Details</button>
                                                                            </div>
                                                                    </form>
    {{--                                                            </div>--}}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    @endif

                                    <a class="link-info fs-sm" href="{{ route('clerk.viewDirectDeliveryOrder', base64_encode($order->delivery_number)) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="View Direct Del Details" ><span class="fa fa-info"></span> </a>
                                    <a class="text-secondary mx-2" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" title="Tally of goods received" href="{{ route('clerk.downloadDirectDeliveries', base64_encode($order->delivery_number . ':' . '1')) }}">
                                        <span class="fas fa-print"></span>
                                    </a>
                                        @if($order->path)
                                            <a class="text-secondary mx-2" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" title="Delivery Note Document" href="{{ route('clerk.downloadDeliveryNote', base64_encode($order->delivery_number)) }}">
                                                <span class="fas fa-file"></span>
                                            </a>
                                        @endif
                                </td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable( {
                order: [ 0, 'asc' ],
                pageLength: 50
            } );

            $('#selectedWarehouse').on('change', function() {
                var selectedStation = $(this).val();
                console.log(selectedStation)
                $.ajax({
                    type: 'GET',
                    url: '{{ route('clerk.filterWarehouseBay') }}',
                    data: {
                        selectedStation
                    },
                    success: function(response) {
                        console.log(response)

                        $('#warehouseBay').empty();

                        // Append the default option
                        $('#warehouseBay').append('<option disabled selected class="text-center" value="">-- Select warehouse bay --</option>' );

                        // Populate the select element with options from the response
                        $.each(response, function(index, bay) {
                            $('#warehouseBay').append('<option value="' + bay.bay_id + '">' + bay.bay_name + '</option>');
                        });
                    }

                });

            });

            $('#selectWarehouse').on('change', function() {
                var selectedStation = $(this).val();
                console.log(selectedStation)
                $.ajax({
                    type: 'GET',
                    url: '{{ route('clerk.filterWarehouseBay') }}',
                    data: {
                        selectedStation
                    },
                    success: function(response) {
                        console.log(response)

                        $('#selectWarehouseBay').empty();

                        // Append the default option
                        $('#selectWarehouseBay').append('<option disabled selected class="text-center" value="">-- Select warehouse bay --</option>' );

                        // Populate the select element with options from the response
                        $.each(response, function(index, bay) {
                            $('#selectWarehouseBay').append('<option value="' + bay.bay_id + '">' + bay.bay_name + '</option>');
                        });
                    }

                });

            });

            $('.idSelect').on('change', function () {

                var idNumber = $(this).val();

                $.ajax({
                    url: '{{ route('clerk.fetchIdNumber') }}',
                    method: 'GET',
                    data: {idNumber},
                    dataType: 'json',
                    success: function (response) {
                        console.log('Success:', response.driver_name);

                        $('.driverName').val(response.driver_name)
                        $('.driverPhone').val(response.driver_phone)
                    },
                    error: function (xhr, status, error) {
                        // Function to handle errors
                        console.error('Error:', error);
                        $('#driverName').val('')
                        $('#driverPhone').val('')
                    }
                });
            });
        });
    </script>
    <script>
        function toggleOtherInput(selectEl, inputId) {
            const inputEl = document.getElementById(inputId);
            if (selectEl.value === 'other') {
                inputEl.classList.remove('d-none');
                inputEl.required = true;
            } else {
                inputEl.classList.add('d-none');
                inputEl.required = false;
            }
        }
    </script>

@endsection
