@extends('account::layouts.default')
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Internal Tea Transfers </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
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
                                <td>{{ $transfer->station_name }}</td>
                                <td nowrap="">{{ $transfer->destination_name }}</td>
                                <td>
                                    {!! $transfer->status === null || $transfer->status <= 1 ? '<span class="badge bg-info"> Approved (Op) </span>' : ($transfer->status == 2 ? '<span class="badge bg-secondary"> Approved (Fin) <span>' : ( $transfer->status <= 3 ? '<span class="badge bg-dark"> Released </span>' : '<span class="badge bg-success"> Received <span>')) !!}
                                </td>
                                <td nowrap="">
                                    <div class="d-flex align-items-center">
                                        <!-- Trace Tea Icon -->
                                        @if(auth()->user()->role_id == 7)
                                            @if($transfer->status == null || $transfer->status == 0)
                                                <a class="link text-danger" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer created & initiated"> <span class="fa-solid fa-spinner"></span></a>
                                            @elseif($transfer->status == 1)
                                                <a class="link text-warning" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to approve this transfer" onclick="return confirm('Are you sure you want to approve this transfer request?')" href="{{ route('accounts.approveInternalTransfer', base64_encode($transfer->delivery_number)) }}" ><span class="fa-regular fa-thumbs-up"></span></a>
                                            @elseif($transfer->status == 2)
                                                <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer Approved By Finance"> <span class="fa-solid fa-check"></span> </a>
                                            @elseif($transfer->status == 3)
                                                <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer released"> <span class="fa-solid fa-truck-arrow-right"></span> </a>
                                            @else
                                                <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer received"> <span class="fa-solid fa-check-double"></span> </a>
                                            @endif
                                        @endif
                                        <!-- Dropdown Icon -->
                                        <div class="dropdown font-sans-serif position-static" >
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-info" href="{{ route('accounts.viewInternalTransferDetails', base64_encode($transfer->delivery_number)) }}">View Transfer</a>
                                                    <a class="dropdown-item text-primary" href="{{ route('accounts.downloadInterDelNote', base64_encode($transfer->delivery_number)) }}" target="_blank">Download Transfer</a>
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
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
