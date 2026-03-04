@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">External Tea Transfers From <span class="text-danger">{!! $transfers[0]->station_name !!}</span> To <span class="text-success">{!! $transfers[0]->warehouse_name !!} </span></h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Add Tea</span></a>
                    </div>
                </div>

                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">FILTER TEAS FOR TRANSFER</h5>
                                </div>
                                <div class="p-4">
                                    <form method="POST" id="myForm" action="{{ route('admin.amendRegisteredExternalRequest', base64_encode($transfers[0]->delivery_number)) }}">
                                        @csrf
                                        <div class="row">
                                           <table class="table mb-0 table-bordered table-striped datatable fs-sm">
                                                <thead class="bg-200">
                                                <tr>
                                                    <th>#</th>
                                                    <th>&#10003;</th>
                                                    <th>Garden</th>
                                                    <th>Grade</th>
                                                    <th>Inv Number </th>
                                                    <th>Lot Number</th>
                                                    <th>In Stock</th>
                                                    <th>Request Pkgs</th>
                                                    <th nowrap=''>Request Weight</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($teas as $tea)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>
                                                            <input type="checkbox" class="select-checkbox" name="deliveries[{{ $tea->stock_id }}][deliveryId]" value="{{ $tea->stock_id }}">
                                                        </td>
                                                        <td>{{ $tea->garden_name }}</td>
                                                        <td>{{ $tea->grade_name }}</td>
                                                        <td>{{ $tea->invoice_number }}</td>
                                                        <td>{{ $tea->lot_number }}</td>
                                                        <td>{{ $tea->current_stock }}</td>
                                                        <td>
                                                            <input id="currentPackages" type="hidden" value="{{ $tea->current_stock }}">
                                                            <input type="number" step="0.1" name="deliveries[{{ $tea->stock_id }}][palette]" class="form-control form-control-sm" max="{{ $tea->current_stock }}" id="tPackages">
                                                        </td>
                                                        <td>
                                                            <input id="currentWeight" type="hidden" value="{{ $tea->current_weight }}">
                                                            <input type="number" step="0.01" name="deliveries[{{ $tea->stock_id }}][weight]" class="form-control" max="{{ $tea->current_weight }}" readonly id="tWeight">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <input type="hidden" id="allDeliveries" name="allDeliveries">
                                        <div class="d-flex justify-content-center mt-3">
                                            <button id="submitButton" type="submit" class="btn btn-success col-8">ADD TEAS TO TRANSFER </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
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
                                <th>Requested Pcks</th>
                                <th>Requested Weight</th>
                                <th>Release Date</th>
                                <th>Release Lot</th>
                                {{-- @if($transfers[0]->status < 4) --}}
                                    <th></th>
                                {{-- @endif --}}
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transfers as $transfer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $transfer->garden_name }}</td>
                                    <td>{{ $transfer->grade_name }}</td>
                                    <td>{{ $transfer->invoice_number }}</td>
                                    <td>{{ $transfer->lot_number }}</td>
                                    <td>{{ number_format($transfer->transferred_palettes, 0) }}</td>
                                    <td>{{ number_format($transfer->transferred_weight, 2) }}</td>
                                    <td>{{ $transfer->release_date ? $transfer->release_date->format('d-m-Y') : null }}{{ $transfer->lot ? '('.$transfer->lot.')' : null }}</td>
                                    <td>
                                       @if($transfer->status == 3)
                                        <input class="release-checkbox" data-id="{{ $transfer->ex_transfer_id }}" @if($transfer->release_date) checked @else @endif type="checkbox" value="{{ $transfer->ex_transfer_id }}">
                                       @endif 
                                    </td>
                                    {{-- @if($transfer->status < 4) --}}
                                        <td>
                                            <a class="link-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Remove line from transfer request" onclick="return confirm('Are you sure you want to remove selected line from the transfer?')" href="{{ route('admin.removeExTransferRequestTea', $transfer->ex_transfer_id) }}"><span class="fa fa-trash-alt"></span></a>
                                        </td>
                                    {{-- @endif --}}
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable, .datatable').DataTable({
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

        $('#datatable').on('click', '.release-checkbox', function(e) {
            var releaseId = $(this).attr('data-id');
            var status = $(this).is(':checked');

            $.ajax({
                url: '{{ route("admin.releaseTransfer") }}',
                method: "POST",
                data: {
                    releaseId: releaseId,
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        // Use your existing toast function
                        // Adjust the function name to match your setup
                        toastr.success(response.message);

                        // Optional: Update the release date in the table without reload
                        if(response.lot_number) {
                            $('input[data-id="' + releaseId + '"]')
                                .closest('tr')
                                .find('td:eq(7)') // Release Date column (adjust index if needed)
                                .text(response.release_date + ' (Lot ' + response.lot_number + ')');
                        } else {
                            // If unchecked, clear the release date
                            $('input[data-id="' + releaseId + '"]')
                                .closest('tr')
                                .find('td:eq(7)')
                                .text('');
                        }
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });
   
        const selectedItems = {}; // Object to track selected items

        // Event binding for changes in the palette input and checkbox
        $('.datatable').on('change input', 'input[name*=palette], .select-checkbox', function () {
            const $row = $(this).closest('tr');
            const stockId = $row.find('.select-checkbox').val(); // Get stock ID from the checkbox
            const isChecked = $row.find('.select-checkbox').prop('checked'); // Check if the checkbox is selected

            // Retrieve palette value using the correct selector
            let paletteValue = parseFloat($row.find('input[name="deliveries[' + stockId + '][palette]"]').val()) || 0;

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
                    weight: updatedWeight.toFixed(2)
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

