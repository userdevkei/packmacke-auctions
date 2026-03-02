@extends('admin::layouts.default')
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">View Sale {{ $sale }}</h5>
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
                    <table class="table mb-0 table-bordered table-striped fs-sm-10" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Warrant NO</th>
                            <th>Garden</th>
                            <th>Grade </th>
                            <th>Inv NO </th>
                            <th>Pks</th>
                            <th>Weight</th>
                            <th>Broker</th>
                            <th>Buyer</th>
                            <th>Producer Whs</th>
                            <th>Sale Date</th>
                            <th>Prompt Date</th>
                            <th>Release Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($teas as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $transfer->warrant_number }}</td>
                                <td>{{ $transfer->garden_name }}</td>
                                <td>{{ $transfer->grade_name }}</td>
                                <td>{{ $transfer->invoice_number }}</td>
                                <td>{{ number_format($transfer->current_stock, 0) }}</td>
                                <td>{{ number_format($transfer->current_weight, 2) }}</td>
                                <td>{{ $transfer->broker_name }} </td>
                                <td>{{ $transfer->buyer_name }} </td>
                                <td>{{ $transfer->warehouse_name }} </td>
                                <td>{{ $transfer->sale_date }} </td>
                                <td>{{ $transfer->prompt_date }} </td>
                                <td>{{ $transfer->release_date }} </td>
                                <td>{!! $transfer->status == 0 ? '<span class="badge bg-info">On Sale</span>' : '<span class="badge bg-primary">Sold</span>' !!}</td>
                                <td>
                                    <a class="link link-primary" data-bs-toggle="modal" data-bs-target="#staticBackdrop{{ $transfer->auction_id }}"><span class="fas fa-edit" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1"></span></a>
                                    @if($transfer->status == 0)
                                        | <a class="link link-danger" onclick="return confirm('Are you sure you want to delete this line from this sale?')" href="{{ route('admin.removeLineFromSale', $transfer->auction_id) }}"><i class="fa fa-trash-alt"></i> </a>
                                    @endif
                                    <div class="modal fade" id="staticBackdrop{{ $transfer->auction_id }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg mt-6" role="document">
                                            <div class="modal-content border-0">
                                                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                        <h5 class="mb-1" id="staticBackdropLabel">Edit Warrant Number {{ $transfer->warrant_number }}</h5>
                                                    </div>
                                                    <div class="p-4">
                                                        <form class="needs-validation" novalidate method="POST" id="myForm" action="{{ route('admin.updateAuctionList', $transfer->auction_id) }}">
                                                            @csrf
                                                            <div class="row row-cols-sm-1 g-2">

                                                                <div class=" mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> Broker Name</label>
                                                                    <select name="broker" class="form-select js-choice" style="height: 57% !important;" required>
                                                                        <option disabled selected value="">-- select broker --</option>
                                                                        @foreach($brokers as $broker)
                                                                            <option @selected($broker->broker_id == $transfer->broker_id) value="{{ $broker->broker_id }}">{{ $broker->broker_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class=" mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> Sale</label>
                                                                    <select name="sale" class="form-select js-choice" style="height: 57% !important;" required>
                                                                        <option disabled selected value="{{ $transfer->sale }}">{{ $transfer->sale }}</option>
                                                                        @for($i = 1; $i<=54; $i++)
                                                                            <option @selected($i.'/'.date('y') == $transfer->sale) value="{{ $i.'/'.date('y') }}">{{ $i.'/'.date('y') }}</option>
                                                                        @endfor
                                                                    </select>
                                                                </div>

                                                                <div class=" mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> Buyer Name</label>
                                                                    <select name="buyer" class="form-select js-choice" style="height: 57% !important;">
                                                                        <option disabled selected value="">-- select buyer --</option>
                                                                        @foreach($clients as $client)
                                                                            <option @selected($client->client_id == $transfer->client_id) value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div class=" mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;"> Producer Warehouse</label>
                                                                    <select name="warehouse_id" class="form-select js-choice" style="height: 57% !important;">
                                                                        <option disabled selected value="">-- select buyer --</option>
                                                                        @foreach($warehouses as $warehouse)
                                                                            <option @selected($warehouse->warehouse_id == $transfer->warehouse_id) value="{{ $warehouse->warehouse_id }}">{{ $warehouse->warehouse_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>


                                                                <div class="mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;">Sale Date</label>
                                                                    <input type="date" class="form-control" value="{{ $transfer->sale_date !== null ? Carbon\Carbon::parse($transfer->sale_date)->format('Y-m-d') : '' }}" name="sale_date" style="height: 62% !important;">
                                                                </div>

                                                                <div class="mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;">Prompt Date</label>
                                                                    <input type="date" class="form-control" value="{{ $transfer->sale_date !== null ? Carbon\Carbon::parse($transfer->prompt_date)->format('Y-m-d') : '' }}" name="prompt_date" style="height: 62% !important;">
                                                                </div>

                                                                <div class="mb-2">
                                                                    <label class="fs-sm fw-bold my-2" style="font-size: 85% !important;">Sale Status</label>
                                                                    <select name="status" class="form-select js-choice">
                                                                        <option selected value="">-- select status </option>
                                                                        <option @selected($transfer->status == 0) value="0">On Auction</option>
                                                                        <option @selected($transfer->status == 1) value="1">Sold</option>
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

    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 100
        });
    });

</script>
