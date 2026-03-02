@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Edit SI {{ $si->shipping_number }} </h5>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form method="POST" action="{{ route('clerk.updateSI', $si->shipping_id) }}">
                        @csrf
                        <div class="row g-2 needs-validation" novalidate="">
                            <div class="col-md-4 mb-3">
                                <label for="organizerSingle">CLIENTS</label>
                                <select class="form-select js-choice" name="client" data-options='{"removeItemButton":true,"placeholder":true}' @if($si->status > 0 || $siTeas > 0) disabled @endif onchange="updateHiddenClient(this)>
                                    @foreach($clients as $client)
                                        <option @if($si->client_id == $client->client_id) selected @endif value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" id="hiddenClient" name="client_hidden" value="{{ $si->client_id }}">

                            <div class="col-md-4 mb-3">
                                <label for="organizerSingle">PREPARED AT</label>
                                <select class="form-select js-choice" name="station" data-options='{"removeItemButton":true,"placeholder":true}' @if($si->status > 0 || $siTeas > 0) disabled @endif onchange="updateHiddenInput(this)">
                                    <option disabled selected value="">Select PHML Warehouse...</option>
                                    @foreach($stations as $station)
                                        <option @if($station->station_id == $si->station_id) selected @endif value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="hiddenStation" name="station_hidden" value="{{ $si->station_id }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="organizerSingle">DESTINATION PORT</label>
                                <select class="form-select js-choice" id="selectedWarehouse" size="1" name="destination" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option disabled selected value="">Select Port ...</option>
                                    @foreach($ports as $port)
                                        <option @if($port->destination_id == $si->destination_id) selected @endif value="{{ $port->destination_id }}">{{ $port->port_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="organizerSingle">CONTAINER SIZE</label>
                                <select class="form-select js-choice" name="containerSize" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option disabled selected value="">Select Tea Type...</option>
                                    <option @if($si->container_size == 1) selected @endif value="1">20 FT</option>
                                    <option @if($si->container_size == 2) selected @endif value="2">40 FT TEAS</option>
                                    <option @if($si->container_size == 3) selected @endif value="3">40 FTHC</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="organizerSingle">LOADING TYPE</label>
                                <select class="form-select js-choice" name="package" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option disabled selected value="">Select Tea Type...</option>
                                    <option @if($si->load_type == 1) selected @endif value="1">LOOSE LOADING</option>
                                    <option @if($si->load_type == 2) selected @endif value="2">PALLETIZED LOADING</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="floatingInputValid">SI NUMBER</label>
                                <input class="form-control" value="{{ $si->shipping_number }}" type="text" name="shipmentNumber" placeholder="provide total net weight" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="floatingInputValid">SHIPPING MARK</label>
                                <input class="form-control" value="{{ $si->shipping_mark }}" id="totalWeight" type="text" name="mark" placeholder="provide total net weight" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="floatingInputValid">CONSIGNEE</label>
                                <input class="form-control" value="{{ $si->consignee }}" id="totalWeight" type="text" name="consignee" placeholder="provide total net weight" style="height: 58% !important;"/>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="floatingInputValid">VESSEL NAME</label>
                                <input class="form-control" value="{{ $si->vessel_name }}" type="text" name="vessel" placeholder="provide total net weight" style="height: 58% !important;"/>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="floatingInputValid">SHIPPING INSTRUCTIONS</label>
                            <textarea type="text" class="form-control" name="shippingInstruction" placeholder="shipping instruction" required>{{ $si->shipping_instructions }}</textarea>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-md btn-success col-6">UPDATE SHIPPING INSTRUCTION</button>
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

        function updateHiddenInput(selectElement) {
            document.getElementById('hiddenStation').value = selectElement.value;
        }

        function updateHiddenClient(selectElement) {
            document.getElementById('hiddenClient').value = selectElement.value;
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
