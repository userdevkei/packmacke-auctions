@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">External Tea Transfers For <span class="text-info">{!! $client->client_name !!}</span> From <span class="text-danger">{!! $station->station_name !!}</span> </h5>
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
                    <form method="POST" id="myForm" action="{{ route('admin.registerExternalRequest') }}">
                        @csrf
                        <table class="table mb-0 table-bordered table-striped" id="datatable">
                            <thead class="bg-200">
                            <tr>
                                <th>#</th>
                                <th>&#10003;</th>
                                <th>Garden Name</th>
                                <th>Grade Name</th>
                                <th>Invoice Number </th>
                                <th>Order Number</th>
                                <th>Lot Number</th>
                                <th>Packages</th>
                                <th>Weight</th>
                                <th>Request Pkgs</th>
                                <th>Request Weight</th>
                                <th>Buyer Name</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transfers as $transfer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <input type="checkbox" class="select-checkbox" name="deliveries[{{ $transfer->stock_id }}][deliveryId]" value="{{ $transfer->stock_id }}">
                                    </td>
                                    <td>{{ $transfer->garden_name }}</td>
                                    <td>{{ $transfer->grade_name }}</td>
                                    <td>{{ $transfer->invoice_number }}</td>
                                    <td>{{ $transfer->order_number }}</td>
                                    <td>{{ $transfer->lot_number }}</td>
                                    <td>{{ number_format($transfer->current_stock, 0) }}</td>
                                    <td>{{ number_format($transfer->current_weight, 2) }}</td>
                                    <td>
                                        <input id="currentPackages" type="hidden" value="{{ $transfer->current_stock }}">
                                        <input type="number" step="0.1" name="deliveries[{{ $transfer->stock_id }}][palette]" class="form-control form-control-sm" max="{{ $transfer->current_stock }}" id="tPackages">
                                    </td>
                                    <td>
                                        <input id="currentWeight" type="hidden" value="{{ $transfer->current_weight }}">
                                        <input type="number" step="0.01" name="deliveries[{{ $transfer->stock_id }}][weight]" class="form-control form-control-sm" max="{{ $transfer->current_weight }}" @if($transfer->type === 1) readonly @endif id="tWeight">
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm buyerId" id="buyerId" name="deliveries[{{ $transfer->stock_id }}][buyer]">
                                            <option value="" selected>-- select client --</option>
                                            @foreach(\App\Models\Client::where('client_id', '!=', $client->client_id)->get() as $cl)
                                                <option value="{{ $cl->client_id }}">{{ $cl->client_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="row row-cols-sm-3 g-2">

                            <div class="mb-0">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">WAREHOUSE/DESTINATION</label>
                                <select name="warehouse" id="transporterSelect1" class="form-select js-choice" required onchange="toggleOtherInput(this, 'otherTransporterInput1')">
                                    <option selected disabled value="">-- select transporter --</option>
                                    @foreach($destinations as $warehouse)
                                        <option value="{{ $warehouse->warehouse_id }}">{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                    <option value="other">Other</option>
                                </select>
                                <input type="text" name="warehouse_other" id="otherTransporterInput1" class="form-control mt-2 d-none" placeholder="Enter other transporter name" style="height: 34% !important;">
                            </div>

                            <div class="mb-0">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">TRANSPORTER</label>
                                <select name="transporter" id="transporterSelect2" class="form-select js-choice" required onchange="toggleOtherInput(this, 'otherTransporterInput2')">
                                    <option selected disabled value="">-- select transporter --</option>
                                    @foreach($transporters as $transporter)
                                        <option value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>
                                    @endforeach
                                    <option value="other">Other</option>
                                </select>
                                <input type="text" name="transporter_other" id="otherTransporterInput2" class="form-control mt-2 d-none" placeholder="Enter other transporter name" style="height: 34% !important;">
                            </div>

                            <div class="mb-0">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>
                                <input class="form-control" name="registration" id="editableSelect" type="text" list="optionsList" placeholder="-- plate number --" required style="height: 47% !important;">
                                <datalist id="optionsList">
                                    @foreach($registrations as $registration => $transporter)
                                        <option value="{{ $registration }}">{{ $registration }} </option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="mb-2">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>
                                <input id="idSelect" type="text" list="idList" name="idNumber" class="form-control idSelect" placeholder="-- driver's ID Number --" required style="height: 72% !important;">
                                <datalist id="idList">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="mb-0">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>
                                <input type="text" name="driverName" id="driverName" class="form-control driverName" required style="height: 67% !important;">
                            </div>

                            <div class="mb-0">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>
                                <input type="text" name="driverPhone" id="driverPhone" class="form-control driverPhone" required style="height: 67% !important;">
                            </div>
                            <div class="mb-5">
                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">LOADING NUMBER</label>
                                <input type="text" name="loading_number" class="form-control" style="height: 67% !important;">
                            </div>
                        </div>
                        <input type="hidden" id="allDeliveries" name="allDeliveries">
                        <input type="hidden" name="location" value="{{ $station->station_id }}">
{{--                        <input type="hidden" name="station" value="{{ $destination->warehouse_id }}">--}}

                        <div class="d-flex justify-content-center">
                            <button type="submit" id="submitButton" class="btn btn-success col-8">MAKE REQUEST</button>
                        </div>
                    </form>


                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $(document).on('change', '#tPackages', function() {
            console.log('hey')
            var totalPackages = parseInt($(this).val(), 10);
            var currentStock = parseInt($(this).closest('tr').find('#currentPackages').val(), 10);
            var currentWeight = parseFloat($(this).closest('tr').find('#currentWeight').val());

            // Check if the entered value is valid
            if (isNaN(totalPackages) || totalPackages < 0 || totalPackages > currentStock) {
                // Handle invalid input (e.g., display error message)
                return;
            }

            // Calculate the new weight based on the number of packages
            var newWeight = (totalPackages * currentWeight) / currentStock;

            // Update the weight input field
            $(this).closest('tr').find('#tWeight').val(newWeight.toFixed(2));
        });

        $('.idSelect').on('change', function () {

            var idNumber = $(this).val();

            $.ajax({
                url: '{{ route('admin.fetchIdNumber') }}',
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

    $(document).ready(function () {
        const selectedItems = {}; // Object to track selected items

        // Event binding for changes in the palette input and checkbox
        $('#datatable').on('change input', 'input[name*=palette], .select-checkbox, .weight-input, .buyerId', function () {
            const $row = $(this).closest('tr');
            const stockId = $row.find('.select-checkbox').val(); // Get stock ID from the checkbox
            const isChecked = $row.find('.select-checkbox').prop('checked'); // Check if the checkbox is selected

            // Retrieve palette value using the correct selector
            let paletteValue = parseFloat($row.find('input[name="deliveries[' + stockId + '][palette]"]').val()) || 0;
            let buyerId = $row.find('.buyerId').val();


            // Retrieve current packages and current weight from hidden inputs
            let currentPackages = parseFloat($row.find('#currentPackages').val()) || 0;
            let currentWeight = parseFloat($row.find('#currentWeight').val()) || 0;

            // Calculate updated weight based on the palette value
            let updatedWeight = 0;
            if (paletteValue !== 0 && currentPackages !== 0) {
                updatedWeight = (currentWeight / currentPackages) * paletteValue;
            }

            // Update selectedItems based on checkbox state
            if (isChecked && paletteValue > 0 && updatedWeight > 0) {
                selectedItems[stockId] = {
                    deliveryId: stockId,
                    palette: paletteValue,
                    weight: updatedWeight.toFixed(2),
                    buyerId: buyerId
                };
            } else {
                delete selectedItems[stockId]; // Remove if unchecked
            }

            console.log('Selected items:', selectedItems); // Debug: Check selected items
        });

        // Form submission handling
        $('#myForm').submit(function (event) {
            // Prevent default submission behavior
            event.preventDefault();

            // Prepare data to submit (only selected items)
            const dataToSubmit = {
                deliveries: Object.values(selectedItems) // Include only selected items
            };

            console.log('Data to submit:', dataToSubmit); // Debug: Check data to submit

            // Update the hidden input with the selected items
            $('#allDeliveries').val(JSON.stringify(dataToSubmit)); // Set hidden input with selected items

            // Proceed with the form submission
            this.submit();
        });

        $('#myForm').on('submit', function(event) {
            // event.preventDefault(); // Prevents the default form submission

            var form = $(this);
            var submitButton = $('#submitButton');

            // Simulate form submission process
            setTimeout(function() {
                // Assuming the form submission is successful, disable the button
                submitButton.prop('disabled', true);

                // You can also display a success message or perform other actions here
                // alert('Form submitted successfully!');
            }, 10); // Simulate a delay for the form submission process
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
