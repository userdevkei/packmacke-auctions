@extends('clerk::layouts.default')
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0"> {{ $client->client_name }}'s Unbilled Teas </h5>
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
                            <th>Invoice Number</th>
                            <th>DO Number</th>
                            <th>Garden Name</th>
                            <th>Grade Name</th>
                            <th>Lot Number</th>
                            <th>Packages</th>
                            <th>Net Weight</th>
                            <th>Producer Warehouse</th>
                            <th>Warehouse Location</th>
                            <th>select</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($teas as $tea)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $tea->invoice_number }}</td>
                                <td>{{ $tea->order_number }}</td>
                                <td>{{ $tea->garden_name }}</td>
                                <td>{{ $tea->grade_name }}</td>
                                <td>{{ $tea->lot_number }}</td>
                                <td>{{ number_format($tea->packages, 0) }}</td>
                                <td>{{ number_format($tea->weight, 2) }}</td>
                                <td>{{ $tea->warehouse_name }}</td>
                                <td>{{ $tea->sub_warehouse_name }}</td>
                                <td nowrap="">
                                    <input
                                        type="checkbox"
                                        class="deliveryId"
                                        data-id="{{ $tea->delivery_id }}"
                                        {{ $tea->billed == 1 ? 'checked' : '' }}
                                    >
                                </td>
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
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        let deliveryId = $(this).data('id');
        let status = $(this).is(':checked') ? 1 : 0;

        {{--$(document).on('change', '.deliveryId', function () {--}}
        {{--    $.ajax({--}}
        {{--        url: "{{ route('accounts.updateBillStatus') }}",--}}
        {{--        method: "POST",--}}
        {{--        data: {--}}
        {{--            delivery_id: deliveryId,--}}
        {{--            status: status,--}}
        {{--            _token: $('meta[name="csrf-token"]').attr('content')--}}
        {{--        },--}}
        {{--        success: function (res) {--}}
        {{--            console.log("Updated", res);--}}
        {{--        },--}}
        {{--        error: function (err) {--}}
        {{--            console.error("Error updating", err);--}}
        {{--            alert("Failed to update. Try again.");--}}
        {{--        }--}}
        {{--    });--}}
        {{--});--}}
        $(document).on('change', '.deliveryId', function () {
            let deliveryId = $(this).data('id');
            let status = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                // url: "/update-delivery-selection",
                url: "{{ route('accounts.updateBillStatus') }}",
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    delivery_id: deliveryId,
                    status: status
                },
                success: function (res) {
                    console.log("Updated", res);
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    alert("Failed to update. CSRF issue.");
                }
            });
        });
    });
</script>
