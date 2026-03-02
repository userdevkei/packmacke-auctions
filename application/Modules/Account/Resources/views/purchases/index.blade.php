@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Purchase Vouchers </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        {{-- @if(auth()->user()->role_id == 9 || auth()->user()->role_id == 7) --}}
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('accounts.addPurchaseInvoice') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Voucher</span></a>
                        {{-- @endif --}}
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
                            <th>VOUCHER NUMBER</th>
                            <th>INVOICE NUMBER</th>
                            <th>FINANCIAL YEAR</th>
                            <th>SUPPLIER</th>
                            <th>TOTAL INVOICED</th>
                            <th>DUE DATE</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $invoice->voucher_number }} </td>
                                <td> {{ $invoice->invoice_number }} </td>
                                <td> {{ Carbon\Carbon::parse($invoice->year_starting)->format('Y') == Carbon\Carbon::parse($invoice->year_ending)->format('Y') ? Carbon\Carbon::parse($invoice->year_starting)->format('Y') : Carbon\Carbon::parse($invoice->year_starting)->format('Y').'/'.Carbon\Carbon::parse($invoice->year_ending)->format('y') }} </td>
                                <td> {{ strtoupper($invoice->clientAccount) }} </td>
                                <td> {{ $invoice->currency_symbol }} {{ number_format($invoice->amount_due, 2) }} </td>
                                <td> {{ \Carbon\Carbon::createFromTimestamp($invoice->due_date)->format('D, d/m/y') }} </td>
                                <td>
                                    @php
                                        // Retrieve and debug timestamps
                                        $dueDateTimestamp = $invoice->due_date;
                                        $dateInvTimestamp = $invoice->date_invoiced;

                                        $dueDate = \Carbon\Carbon::createFromTimestamp($dueDateTimestamp);
                                        $dateInv = \Carbon\Carbon::createFromTimestamp($dateInvTimestamp);
                                        $today = \Carbon\Carbon::today();

                                        // Calculate the difference in days
                                        $dateDiff = $dateInv->diffInDays($dueDate, false);
                                        $daysToGo = $today->diffInDays($dueDate, false);

                                        // Avoid division by zero
                                        if ($dateDiff != 0) {
                                            $percentage = round(($daysToGo / abs($dateDiff)) * 100, 2);
                                        } else {
                                            $percentage = 0; // Set a default value when due date = invoice date
                                        }

                                        if ($dueDate->lt($today)) {
                                            $daysToGo = -$daysToGo;
                                        }
                                    @endphp


                                    {!! $invoice->status == 1 ? '<span class="badge bg-success">Paid </span>' : ($invoice->status == 2 ? '<span class="badge bg-info"> Partially Paid</span>' : ($percentage > 75 ? '<span class="badge bg-secondary">'. $daysToGo. ' Days To Payment'. '</span>': ($percentage >= 50 ? '<span class="badge bg-info">'. $daysToGo. ' Days To Payment'. '</span>': ($percentage >= 25 ? '<span class="badge bg-warning">'. $daysToGo. ' Days To Payment'. '</span>': ($percentage >= 0 ? '<span class="badge bg-dark">'. $daysToGo. ' Days To Payment'. '</span>': '<span class="badge bg-danger"> Late By '. $daysToGo. ' Days'. '</span>'))))) !!}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if(auth()->user()->role_id == 9 || auth()->user()->role_id == 7)
                                            @if($invoice->posted >= 1)
                                                <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Voucher Posted"> <span class="fa-solid fa-check-double"></span> </a>
                                            @else
                                                <a onclick="return confirm('Are you sure you want to post this purchase voucher?')" class="link-primary" title="Post Voucher" data-bs-toggle="tooltip" data-bs-placement="left" href="{{ route('accounts.postPurchaseInvoice', $invoice->purchase_id) }}"><span class="fa-regular fa-share-from-square"></span></a>
                                            @endif
                                        @endif
                                        <div class="dropdown font-sans-serif position-static" >
                                            <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                                <span class="fas fa-ellipsis-h fs-10"></span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end border py-0">
                                                <div class="py-2">
                                                    <a class="dropdown-item text-secondary" href="{{ route('accounts.viewPurchaseInvoice', $invoice->purchase_id) }}">View Voucher</a>
                                                    @if($invoice->posted >= 1)
                                                        @if(auth()->user()->role_id == 7 || auth()->user()->role_id == 9)
                                                            @if($invoice->type === 1)
                                                                <a class="dropdown-item text-danger" href="{{ route('accounts.createDebitNote', $invoice->purchase_id) }}">Debit Note</a>
                                                            @endif
                                                        @endif
                                                    @else
                                                        @if(auth()->user()->role_id == 7)
                                                            <a class="dropdown-item text-info" href="{{ route('accounts.editPurchaseVoucher', $invoice->purchase_id) }}">Edit Voucher</a>
                                                            <a class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete selected invoice? Invoice Number: {{ $invoice->voucher_number }}')" href="{{ route('accounts.deletePurchaseInvoice', $invoice->purchase_id) }}">Nullify Voucher</a>
                                                       @endif
                                                       {{-- @if(auth()->user()->role_id == 9)
                                                                <a class="dropdown-item text-info" href="{{ route('accounts.editPurchaseVoucher', $invoice->purchase_id) }}">Edit Voucher</a>
                                                            @endif--}}
                                                    @endif
                                                    <a class="dropdown-item text-dark" data-bs-toggle="tooltip" data-bs-placement="left" title="Download Invoice" href="{{ route('accounts.downloadPurchaseVoucher', $invoice->purchase_id) }}" target="_blank"> Download Voucher </a>
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
    });
</script>
