@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Rebagging Job For SI/Blend NO. {{ $data->shipping_number ?? $data->blend_number }} </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @if(auth()->user()->role_id == 2)
                            <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Add Teas</span></a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl mt-2" role="document">
                    <div class="modal-content border-0">
                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                <h5 class="mb-1" id="staticBackdropLabel">CREATE A REBAG JOB</h5>
                            </div>
                            <div class="p-4">
                                <form method="POST" id="myForm" action="{{ route('clerk.storeRebaggingRequest', $data->shipping_id ?? $data->blend_id) }}">
                                    @csrf
                                    <table class="table mb-0 table-bordered table-sm table-striped fs-sm datatable" id="datatable">
                                        <thead class="bg-200">
                                        <tr>
                                            <th>#</th>
                                            <th>&#10003;</th>
                                            <th>Garden Name</th>
                                            <th>Grade Name</th>
                                            <th>Invoice Number </th>
                                            <th>Lot Number</th>
                                            <th>Pkgs</th>
                                            <th>Weight</th>
                                            <th>Requested Weight</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teas as $transfer)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <input type="checkbox" class="select-checkbox" name="deliveries[{{ $transfer->stock_id }}][deliveryId]" value="{{ $transfer->stock_id }}">
                                                </td>
                                                <td>{{ $transfer->garden_name }}</td>
                                                <td>{{ $transfer->grade_name }}</td>
                                                <td>{{ $transfer->invoice_number }}</td>
                                                <td>{{ $transfer->lot_number }}</td>
                                                <td>{{ number_format($transfer->current_stock, 0) }}</td>
                                                <td>{{ number_format($transfer->current_weight, 2) }}</td>
                                                <td nowrap="">
                                                    <input id="currentPackages" class="current-packages" type="hidden" value="{{ $transfer->current_stock }}">
                                                    <input class="current-weight" id="currentWeight" type="hidden" value="{{ $transfer->current_weight }}">
                                                    <input type="number" step="0.01" name="deliveries[{{ $transfer->stock_id }}][weight]" class="form-control weight-input form-control-sm" max="{{ $transfer->current_weight }}" id="tWeight">
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                    <input type="hidden" id="allDeliveries" name="allDeliveries">
                                    <div class="d-flex justify-content-center">
                                        <button type="submit" id="submitButton" class="btn btn-success col-8">SAVE REBAG</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered fs-sm table-sm table-striped datatable" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Date Initiated </th>
                            <th>Client Name</th>
                            <th>Shipping Number </th>
                            <th>Vessel Name</th>
                            <th>Destination</th>
                            <th>Warehouse</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
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
        $('.datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

       /* $(document).on('change', '#tPackages', function() {
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
        });*/

    });

    $(document).ready(function () {
        const selectedItems = {}; // Object to track selected items

        // Event binding for changes in the checkbox or weight input
        $('#datatable').on('change input', '.select-checkbox, .weight-input', function () {
            const $row = $(this).closest('tr');
            const stockId = $row.find('.select-checkbox').val(); // Get stock ID from the checkbox
            const currentWeight = $row.find('.current-weight').val(); // Get stock ID from the checkbox
            const currentPackages = $row.find('.current-packages').val(); // Get stock ID from the checkbox
            const isChecked = $row.find('.select-checkbox').prop('checked'); // Check if the checkbox is selected

            // Retrieve current weight from hidden input or weight-input
            let updatedWeight = parseFloat($row.find('.weight-input').val()) || 0;

            // Update selectedItems based on checkbox state
            if (isChecked && updatedWeight > 0 && currentWeight > 0) {
                selectedItems[stockId] = {
                    deliveryId: stockId,
                    currentWeight: currentWeight,
                    currentPackages: currentPackages,
                    weight: updatedWeight.toFixed(2)
                };
            } else {
                delete selectedItems[stockId]; // Remove if unchecked or weight is invalid
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
    });
</script>
