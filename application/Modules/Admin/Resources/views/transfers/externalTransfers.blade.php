@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">External Tea Transfers </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Request</span></a>
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
                                    <form method="POST" id="myForm" action="{{ route('admin.prepareExternalTransfer') }}">
                                        @csrf
                                        <div class="row row-cols-sm-2 g-2">
                                            @php
                                                $stations = \App\Models\Station::where('status', 1)->get();
                                            @endphp
                                            <div class=" mb-4">
                                                <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> RECEIVING WAREHOUSE </label>
                                                <select name="location" class="form-select js-choice" id="selectWarehouse">
                                                    <option disabled selected>-- select station --</option>
                                                    @foreach($stations as $station)
                                                        <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class=" mb-4">
                                                <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> CLIENT NAME</label>
                                                <select name="client" class="form-select" id="selectClients" style="height: 58% !important;">
                                                    <option disabled selected>-- select client --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-center mt-1">
                                            <button id="submitButton" type="submit" class="btn btn-success col-8">PREPARE TRANSFER REQUEST </button>
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
            <div class="">
                <form method="POST" action="">
                    @csrf
                    <div class="row row-cols-3">
                        <div class="">
                            <input type="date" class="form-control" name="from" value="{{ Carbon\Carbon::parse($from)->format('Y-m-d') }}">
                        </div>
                        <div class="">
                            <input type="date" class="form-control" name="to" value="{{ Carbon\Carbon::parse($to)->format('Y-m-d') }}">
                        </div>
                        <div class="">
                            <button type="submit" class="btn btn-sm btn-info">filter</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Date Initiated </th>
                            <th>Delivery Number </th>
                            <th>Client Name</th>
                            <th>Packages</th>
                            <th>Net Weight</th>
                            <th>Transfer From</th>
                            <th>Destination</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transfers as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/y') }}</td>
                                <td>{{ $transfer->delivery_number }}</td>
                                <td nowrap="">{{ $transfer->client_name }}</td>
                                <td>{{ number_format($transfer->total_palettes, 0) }}</td>
                                <td>{{ number_format($transfer->total_weight, 2) }}</td>
                                <td nowrap="">{{ $transfer->station_name }}</td>
                                <td nowrap="">{{ $transfer->warehouse_name }}</td>
                                <td>
                                    {!! $transfer->status === 0 ? '<span class="badge bg-warning"> Created </span>' : ($transfer->status == 1 ?  '<span class="badge bg-danger"> Pending Approval </span>' :($transfer->status == 2 ? '<span class="badge bg-info"> Approved (Ops) </span>' : ($transfer->status == 3 ? '<span class="badge bg-dark"> Approved (Fin) </span>' : '<span class="badge bg-success"> Released </span>'))) !!}
                                </td>
                                <td nowrap="">
                                    <div class="d-flex align-items-center">
                                        <!-- Trace Tea Icon -->
                                            @if($transfer->status === 0)
                                            <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Initiate external transfer" onclick="return confirm('Are you sure you want to initiate this transfer request?')" href="{{ route('admin.initiateExternalTransfer', base64_encode($transfer->delivery_number)) }}"> <span class="fa-solid fa-check-circle"> </span></a>
                                            @elseif($transfer->status == 1)
                                            <a class="link text-dark" data-bs-toggle="tooltip" data-bs-placement="left" title="Approve transfer, operations dept" onclick="return confirm('Are you sure you want to approve this transfer request?')" href="{{ route('admin.approveExternalTransfer', base64_encode($transfer->delivery_number)) }}"> <span class="fa-solid fa-check"> </span></a>
                                            @elseif($transfer->status == 2)
                                            <a class="link text-secondary" data-bs-toggle="tooltip" data-bs-placement="left" title="Approve transfer, finance dept" onclick="return confirm('Are you sure you want to approve this transfer request?')" href="{{ route('admin.approveExternalTransfer', base64_encode($transfer->delivery_number)) }}"> <span class="fa-solid fa-check-double"> </span></a>
                                            @elseif($transfer->status == 3)
                                                <a class="link text-danger" title="Transfer pending release" data-bs-toggle="modal" data-bs-target="#staticBackdrop-{{ $transfer->delivery_number }}"> <span class="fa-solid fa-truck-arrow-right"> </span></a>
                                            @else
                                                <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer released, and stock updated"> <span class="fa-solid fa-truck-fast"></span> </a>
                                            @endif
                                        <!-- Dropdown Icon -->
                                        <div class="dropdown font-sans-serif position-static" >
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('admin.viewExternalTransferDetails', base64_encode($transfer->delivery_number)) }}">View Transfer</a>
                                                    @if($transfer->buyer_name == null)
                                                        <a class="dropdown-item text-primary" href="{{ route('admin.downloadExtraDelNote', base64_encode($transfer->delivery_number.':'.$transfer->lot)) }}" target="_blank">Download Transfer</a>
                                                    @else
                                                        <a class="dropdown-item text-danger" href="{{ route('admin.downloadDelNote', base64_encode($transfer->delivery_number.':'.$transfer->lot)) }}" target="_blank">Download Del Note</a>
                                                    @endif
                                                </div>
                                               {{-- <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('admin.viewExternalTransferDetails', base64_encode($transfer->delivery_number)) }}">View Transfer</a>
                                                    <a class="dropdown-item text-primary" href="{{ route('admin.downloadExtraDelNote', base64_encode($transfer->delivery_number)) }}" target="_blank">Download Transfer</a>
                                                </div>--}}
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Modal -->
                                    <div class="modal fade" id="staticBackdrop-{{ $transfer->delivery_number }}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-1" id="staticBackdropLabel">Release {{ $transfer->delivery_number }} - {{ $transfer->client_name }}</h1>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="{{ route('admin.releaseExternalTransfer', base64_encode($transfer->delivery_number)) }}">
                                                        @csrf
                                                        <div class="row row-cols-2 mb-3">
                                                            <div class="mb-2">
                                                                <label>Destination</label>
                                                                <select class="form-select js-choice" name="warehouse_id" required>
                                                                    @foreach($warehouses as $warehouse)
                                                                        <option
                                                                            @selected($transfer && $warehouse->warehouse_id == $transfer->warehouse_id)
                                                                            value="{{ $warehouse->warehouse_id }}">
                                                                            {{ $warehouse->warehouse_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="mb-2">
                                                                <label>Transporter</label>
                                                                <select class="form-select js-choice" name="transporter_id" required>
                                                                    {{--                                                            <option value="" selected >--select transporter--</option>--}}
                                                                    @foreach($transporters as $transporter)
                                                                        <option
                                                                            @selected($transfer && $transporter->transporter_id == $transfer->transporter_id)
                                                                            value="{{ $transporter->transporter_id }}">
                                                                            {{ $transporter->transporter_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="mb-2">
                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>
                                                                <input id="idSelect" type="text" list="idList" name="idNumber" class="form-control idSelect" placeholder="-- Driver's ID Number --" required style="height: 67% !important;" value="{{ $transfer->id_number }}">
                                                                <datalist id="idList">
                                                                    @foreach($users as $user)
                                                                        <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                                                                    @endforeach
                                                                </datalist>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>
                                                                <input class="form-control" name="registration" type="text" placeholder="-- plate number --" required value="{{ $transfer->registration }}" style="height: 67%;">
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>
                                                                <input type="text" name="driverName" id="driverName" class="form-control driverName" value="{{ $transfer->driver_name }}" required style="height: 67% !important;">
                                                            </div>

                                                            <div class="mb-2">
                                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>
                                                                <input type="text" name="driverPhone" id="driverPhone" class="form-control driverPhone" value="{{ $transfer->phone }}" required style="height: 67% !important;">
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-center">
                                                            <button type="submit" class="btn btn-danger col-7" onclick="return confirm('Are you sure you want to proceed?')">UPDATE & RELEASE</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $('#selectWarehouse').change(function () {
            var warehouseId = $(this).val();
            $.ajax({
                type: 'GET',
                url: '{{ route('admin.selectClients') }}',
                data: { warehouseId },
                success:function (response) {
                    console.log(response)

                    $('#selectClients').empty();

                    $('#selectClients').append('<option value="" selected disabled class="text-center"> -- select client --');

                    $.each(response, function (clientName, clients) {
                        $('#selectClients').append('<option value="'+ clients[0]. client_id +'">'+ clientName +'</option>');
                    })
                }
            })

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
</script>
