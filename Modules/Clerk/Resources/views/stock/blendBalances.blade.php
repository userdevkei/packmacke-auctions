@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Blend Balances In Stock </h5>
                </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div id="table-simple-pagination-replace-element">
                            <button class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#stockReport"><span class="fas fa-file-download" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Download</span></button>
                        </div>
                    </div>
            </div>
            <div class="modal fade" id="stockReport" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl mt-6" role="document">
                    <div class="modal-content border-0">
                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                <h5 class="mb-1" id="staticBackdropLabel">GENERATE CUSTOM REPORT</h5>
                            </div>
                            <div class="p-4">
                                <form method="post" action="{{ route('clerk.blendBalanceReport') }}" target="_blank">
                                    @csrf
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <label> CLIENT NAME</label>
                                            <select class="form-select js-choice" name="client">
                                                <option value="" selected>-- all clients --</option>
                                                @foreach($balances->groupBy('client_id') as $clientId => $client)
                                                    <option value="{{ $clientId }}">{{ $client->first()->client_name ?? 'Unknown Client' }} </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-6 mb-2">
                                            <label> WAREHOUSES </label>
                                            <select class="form-select js-choice" name="station">
                                                <option value="" selected>-- all warehouses --</option>
                                                @foreach($balances->groupBy('station_id') as $stationId => $station)
                                                    <option value="{{ $stationId }}"> {{ $station->first()->station_name }} </option>
                                                @endforeach

                                            </select>
                                        </div>

{{--                                        <div class="col-6 mb-2 date-input-container">--}}
{{--                                            <label> DATE FROM </label>--}}
{{--                                            <input type="date" class="form-control form-control-lg" value="{{ Carbon\Carbon::today()->subDays(30)->format('Y-m-d') }}" name="from" placeholder="--">--}}
{{--                                        </div>--}}

{{--                                        <div class="col-6 mb-2">--}}
{{--                                            <label> DATE TO</label>--}}
{{--                                            <input type="date" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}" class="form-control form-control-lg" name="to" placeholder="--">--}}
{{--                                        </div>--}}
                                    </div>

{{--                                    @if(auth()->user()->role_id == 2)--}}
{{--                                        <div class="mt-2 fs-sm d-flex justify-content-center">--}}
{{--                                            --}}{{-- <input class="mx-2" type="radio" name="report" value=""> <span class="text-info fw-bolder">ALL STL</span> --}}
{{--                                            <input class="mx-2" type="radio" name="report" value="1"> <span class="text-primary fw-bolder">PDF</span>--}}
{{--                                            <input class="mx-2" type="radio" name="report" value="2"> <span class="text-secondary fw-bolder">EXCEL </span>--}}
{{--                                        </div>--}}
{{--                                    @endif--}}

                                    <div class="d-flex justify-content-center mt-4">
                                        <button type="submit" class="btn col-8 btn-md btn-falcon-success">DOWNLOAD REPORT</button>
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
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th>Line Type</th>
                            <th>Garden Name</th>
                            <th>Grade</th>
                            <th>Blend Number</th>
                            <th>Packages</th>
                            <th>Weight</th>
                            <th>Resides In</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($balances as $index => $balance)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $balance->client_name }} </td>
                                <td> {{ $balance->type }} </td>
                                <td> {{ $balance->garden }} </td>
                                <td> {{ $balance->grade }} </td>
                                <td> {{ $balance->blend_number }} </td>
                                <td> {{ $balance->current_packages }} </td>
                                <td> {{ $balance->current_weight }} </td>
                                <td> {{ $balance->station_name }} </td>
                                <td>
                                    @if(auth()->user()->role_id == 2)
                                        <a href="{{ route('clerk.deleteBlendBalance', $balance->blend_balance_id) }}" class="link link-danger" onclick="return confirm('Are you sure you want to delete this blend balance?');"><i class="fas fa-trash"></i></a>
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
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable( {
            order: [ 0, 'asc' ],
            pageLength: 50
        } );
    } );
</script>
