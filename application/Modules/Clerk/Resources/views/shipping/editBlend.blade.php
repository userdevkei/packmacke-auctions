@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Edit Blend No. {{ $sheet->blend_number }} </h5>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form method="POST" class="needs-validation" novalidate="" action="{{ route('clerk.updateBlend', $sheet->blend_id) }}">
                        @csrf
                        <div class="row g-2" >
                            <div class="col-md-4 mb-1">
                                <label for="organizerSingle">CLIENTS</label>
                                <select class="form-select js-choice" name="client" data-options='{"removeItemButton":true,"placeholder":true}' @if($sheet->status > 0 || $blendTeas > 0) disabled
                                @endif>
                                    <option selected disabled value="">Select Client...</option>
                                    @foreach($clients as $client)
                                        <option @if($sheet->client_id == $client->client_id) selected @endif value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                                @if($sheet->status > 0 || $blendTeas > 0)
                                    <input type="hidden" name="client" value="{{ $sheet->client_id }}">
                                @endif
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >GARDEN NAME</label>
                                <input class="form-control" value="{{ $sheet->garden }}"  type="text" name="gardenName" placeholder="provide garden name" style="height: 52% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >GRADE NAME</label>
                                <input class="form-control" value="{{ $sheet->grade }}" type="text" name="blendGrade" placeholder="provide grade name" style="height: 52% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="organizerSingle">PREPARED AT</label>
                                <select class="form-select js-choice" name="station" data-options='{"removeItemButton":true,"placeholder":true}' @if($sheet->status > 0 || $blendTeas > 0) disabled @endif>
                                    <option disabled selected value="">Select PHML Warehouse...</option>
                                    @foreach($stations as $station)
                                        <option @if($sheet->station_id == $station->station_id) selected @endif value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                    @endforeach
                                </select>
                                @if($sheet->status > 0 || $blendTeas > 0)
                                    <input type="hidden" name="station" value="{{ $sheet->station_id }}">
                                @endif
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="organizerSingle">DESTINATION PORT</label>
                                <select class="form-select js-choice" id="selectedWarehouse" size="1" name="destination" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option disabled selected value="">Select Port ...</option>
                                    @foreach($ports as $port)
                                        <option @if($port->destination_id == $sheet->destination_id) selected @endif value="{{ $port->destination_id }}">{{ $port->port_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="organizerSingle">CONTAINER SIZE</label>
                                <select class="form-select js-choice" name="containerSize" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option disabled selected value="">Select Tea Type...</option>
                                    <option @if($sheet->container_size == 1) selected @endif value="1">20 FT</option>
                                    <option @if($sheet->container_size == 2) selected @endif value="2">40 FT</option>
                                    <option @if($sheet->container_size == 3) selected @endif value="3">40 FTHC</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="organizerSingle">LOADING TYPE</label>
                                <select class="form-select js-choice" name="packagingType" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option disabled selected value="">Select Tea Type...</option>
                                    <option @if($sheet->package_type == 1) selected @endif value="1"> PALLETIZED CARDBOARD </option>
                                    <option @if($sheet->package_type == 2) selected @endif value="2"> PALLETIZED SLIPSHEET </option>
                                    <option @if($sheet->package_type == 3) selected @endif value="3"> PALLETIZED WOODEN </option>
                                    <option @if($sheet->package_type == 4) selected @endif value="4"> LOOSE LOADING </option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="floatingInputValid">BLEND NUMBER (PARENT)</label>
                                <input class="form-control"  type="text" name="shippingNumber" value="{{ $sheet->si_number }}" placeholder="provide SI number" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >BLEND NUMBER</label>
                                <input class="form-control" value="{{ $sheet->blend_number }}" type="text" name="shipmentNumber" placeholder="provide blend number" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="floatingInputValid">BOOKING NUMBER</label>
                                <input class="form-control" id="bookingNumber" type="text" name="bookingNumber" value="{{ $sheet->booking_number }}" placeholder="provide shipping mark" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >CONTRACT NUMBER</label>
                                <input class="form-control" value="{{ $sheet->contract }}" type="text" name="contract" placeholder="provide contract number" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >SHIPPING MARK</label>
                                <input class="form-control" id="totalWeight" type="text" value="{{ $sheet->shipping_mark }}" name="mark" placeholder="provide shipping mark" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >CONSIGNEE</label>
                                <input class="form-control" id="totalWeight" type="text" value="{{ $sheet->consignee }}" name="consignee" placeholder="provide consignee" style="height: 58% !important;"/>
                            </div>
                            <div class="col-md-4 mb-1">
                                <label for="floatingInputValid">P.O BOX</label>
                                <input class="form-control" id="totalWeight" type="text" name="box" value="{{ $sheet->address['box'] ?? old('box') }}" placeholder="consignee p.o box" required style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="floatingInputValid">ADDRESS LINE</label>
                                <input class="form-control" id="totalWeight" type="text" name="address" value="{{ $sheet->address['address'] ?? old('address') }}" placeholder="consignee address" required style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="floatingInputValid">STATE/COUNTRY</label>
                                <input class="form-control" id="totalWeight" type="text" name="state" value="{{ $sheet->address['state'] ?? old('state') }}" placeholder="consignee state and country" required style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label for="floatingInputValid">TELEPHONE NUMBER</label>
                                <input class="form-control" id="totalWeight" type="text" name="mobile" value="{{ $sheet->address['mobile'] ?? old('mobile') }}" placeholder="telephone number" required style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label >VESSEL NAME</label>
                                <input class="form-control" type="text" value="{{ $sheet->vessel_name }}" name="vessel" placeholder="provide vessel name" style="height: 58% !important;"/>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label >STANDARD DETAILS</label>
                            <textarea type="text" class="form-control" name="shippingInstruction" placeholder="shipping instruction" required> {{ $sheet->standard_details }}</textarea>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-md btn-success col-6">UPDATE BLEND SHEET</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

        <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script>

        function calcNetWeight(totalWeight, numberPackages, packageTare, palletWeight) {
            var grossWeight = (parseFloat(numberPackages) * parseFloat(packageTare)) + parseFloat(palletWeight);
            var netWeight = parseFloat(totalWeight) + grossWeight;
            // console.log(netWeight)
            $('#netWeight').val(isNaN(netWeight) ? null : netWeight);
        }

        $(document).ready(function() {
            // Attach change event handler to all input fields
            $(document).on('change', '#numberPackages, #totalWeight, #packageTare, #palletWeight', function() {
                var numberPackages = $('#numberPackages')
                    .val(); // Get the value of the numberPackages input
                var totalWeight = $('#totalWeight').val(); // Get the value of the totalWeight input
                var packageTare = $('#packageTare').val(); // Get the value of the packageTare input
                var palletWeight = $('#palletWeight').val(); // Get the value of the palletWeight input

                console.log(numberPackages)
                console.log(totalWeight)
                console.log(packageTare)
                console.log(palletWeight)
                calcNetWeight(totalWeight, numberPackages, packageTare, palletWeight);
            });

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
        });
    </script>

@endsection
