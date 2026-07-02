@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Private Sales</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @if(in_array(auth()->user()->role_id, [2, 5]) || @canuser('private.create'))
                            <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Create Private Sale</span></a>
                        @endif
                    </div>
                </div>
                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">Prepare Private Sale List</h5>
                                </div>
                                <div class="p-4">
                                    <form class="needs-validation" novalidate method="POST" id="myForm" action="{{ route('clerk.preparePrivateSaleList') }}">
                                        @csrf
                                        <div class="row row-cols-sm-1 g-2">

                                            <div class=" mb-4">
                                                <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> CLIENT NAME</label>
                                                <select name="client" class="form-select js-choice" id="selectClients" style="height: 57% !important;" required>
                                                    <option disabled selected value="">-- select client --</option>
                                                    @foreach($clients as $client)
                                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-center mt-1">
                                            <button id="submitButton" type="submit" class="btn btn-success col-8">Show Client Teas </button>
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
                    <table class="table mb-0 table-bordered table-responsive table-striped fs-sm " id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th style="text-align: left !important;">#</th>
                            <th>Sale </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($auctions as $transfer)
                            <tr>
                                <td style="text-align: left !important;">{{ $loop->iteration }}</td>
                                <td>{{ $transfer->sale }}</td>
                                <td>
                                    <a class="link link-info" href="{{ route('clerk.viewPrivateSale', base64_encode($transfer->sale)) }}">view sale</a> |
                                    <a class="link link-secondary" href="{{ route('clerk.downloadPrivateSaleSheet', base64_encode($transfer->sale.':'.'1')) }}" target="_blank"><i class="fa fa-file-pdf"></i> pdf</a> |
                                    <a class="link link-dark" href="{{ route('clerk.downloadPrivateSaleSheet', base64_encode($transfer->sale.':'.'2')) }}"><i class="fa fa-file-excel"></i> excel</a> |
                                    <a class="link link-danger" href="{{ route('clerk.downloadPrivateSaleSheetReport', base64_encode($transfer->sale)) }}" target="_blank"><i class="fa fa-file-pdf"></i> sale report</a>
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

        $('#selectWarehouse').change(function () {
            var stationId = $('#selectWarehouse').val();

            $.ajax({
                type: 'GET',
                url: '{{ route('clerk.selectStation') }}',
                data: { stationId },
                success:function (response) {
                    console.log(response)
                    $('#selectStation').empty();

                    $('#selectStation').append('<option value="" selected disabled class="text-center"> -- select client --');

                    response.forEach(function (client) {
                        $('#selectStation').append('<option value="'+ client.station_id +'">'+ client.station_name +'</option>');
                    })
                }
            })

        });

        $('#selectStation').change(function () {
            var warehouseId = $(this).val();
            $.ajax({
                type: 'GET',
                url: '{{ route('clerk.selectClients') }}',
                data: { warehouseId },
                success:function (response) {
                    console.log(response)
                    $('#selectClients').empty();

                    $('#selectClients').append('<option value="" selected disabled class="text-center"> -- select client --');

                    $.each(response, function (index, client) {
                        $('#selectClients').append('<option value="' + client.client_id + '">' + client.client_name + '</option>');
                    });

                }
            })

        });
    });
</script>
