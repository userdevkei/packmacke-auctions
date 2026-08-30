@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Prepare Auction List For</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <span class="text-info">{!! $client->client_name !!}</span>
                    </div>
                </div>

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form method="POST" id="myForm" action="{{ route('clerk.storeAuctionList') }}">
                        @csrf
                        <table class="table mb-0 table-bordered table-striped fs-sm-10" id="datatable">
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
                                <th>Broker</th>
                                <th>Sale</th>
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
                                    <td>{{ $transfer->order_number }}</td>
                                    <td>{{ $transfer->lot_number }}</td>
                                    <td>{{ number_format($transfer->current_stock, 0) }}</td>
                                    <td>{{ number_format($transfer->current_weight, 2) }}</td>
                                    <td>
                                        <div class="d-flex">
                                        <select class="form-select fs-sm brokerId" name="deliveries[{{ $transfer->stock_id }}][broker_id]" style="height: 40% !important;">
                                            <option disabled selected value="">-- select broker --</option>
                                            @foreach($brokers as $broker)
                                                <option value="{{ $broker->broker_id }}">{{ $broker->broker_name }}</option>
                                            @endforeach
                                        </select>
                                        </div>
                                    </td>
                                    <td>
                                        {{-- <select class="form-select fs-sm saleNumber" name="deliveries[{{ $transfer->stock_id }}][saleNumber]" id="saleNumber">
                                            <option disabled selected value="">-- select sale number --</option>
                                            @for($i = 1; $i<=54; $i++)
                                                <option value="{{ $i.'/'.date('y') }}">{{ $i.'/'.date('y') }}</option>
                                            @endfor
                                        </select> --}}
                                        <select class="form-select fs-sm saleNumber" name="deliveries[{{ $transfer->stock_id }}][saleNumber]" id="saleNumber">
                                            <option disabled selected value="">-- select sale number --</option>

                                            @php
                                                $currentYear = date('y');        // e.g., 25
                                                $nextYear    = date('y', strtotime('+1 year')); // e.g., 26
                                            @endphp

                                            {{-- Current year numbers (1–54) --}}
                                            @for($i = 1; $i <= 54; $i++)
                                                <option value="{{ $i.'/'.$currentYear }}">
                                                    {{ $i.'/'.$currentYear }}
                                                </option>
                                            @endfor

                                            {{-- Next year numbers (1–10) --}}
                                            @for($i = 1; $i <= 10; $i++)
                                                <option value="{{ $i.'/'.$nextYear }}">
                                                    {{ $i.'/'.$nextYear }}
                                                </option>
                                            @endfor
                                        </select>

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <input type="hidden" id="selectedItems" name="selectedItems">

                        </table>
                        @if($teas->count() > 0)
                            <div class="d-flex justify-content-center mt-4">
                                <button type="submit" id="submitButton" class="btn btn-success col-8">MAKE REQUEST</button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>

    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        const selectedItems = {}; // Object to track selected items

        $('#datatable').on('change input', '.select-checkbox, .brokerId, .saleNumber', function () {
            const $row = $(this).closest('tr');
            const stockId = $row.find('.select-checkbox').val(); // Get stock ID
            const isChecked = $row.find('.select-checkbox').prop('checked'); // Checkbox state

            // Get brokerId and saleNumber from selects in the row
            const brokerId = $row.find('.brokerId').val();
            const saleNumber = $row.find('.saleNumber').val();

            // Update selectedItems
            if (isChecked && brokerId && saleNumber) {
                selectedItems[stockId] = {
                    deliveryId: stockId,
                    brokerId: brokerId,
                    saleNumber: saleNumber
                };
            } else {
                delete selectedItems[stockId];
            }

            // Store JSON string in hidden field
            $('#selectedItems').val(JSON.stringify(selectedItems));
            console.log('Selected items:', selectedItems);
        });


        // Form submission
        $('#myForm').submit(function (event) {
            event.preventDefault();

            const dataToSubmit = {
                deliveries: Object.values(selectedItems)
            };

            console.log('Data to submit:', dataToSubmit);

            $('#allDeliveries').val(JSON.stringify(dataToSubmit));

            this.submit();
        });

        $('#myForm').on('submit', function(event) {
            var submitButton = $('#submitButton');
            setTimeout(function() {
                submitButton.prop('disabled', true);
            }, 10);
        });
    });

</script>
