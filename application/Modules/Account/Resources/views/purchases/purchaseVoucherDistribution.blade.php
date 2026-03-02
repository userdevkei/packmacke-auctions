@extends('account::layouts.default')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .fa.disabled {
        pointer-events: none;
        opacity: 0.5;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        {{ $transaction->invoice_number }} - {{ $transaction->client_account_name }}
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-info btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-search" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Fetch Invoices</span></a>

                       {{-- @if($transactions !== null)
                            <a class="btn btn-falcon-success btn-sm" href="{{ route('accounts.downloadInvoice', $transactions[0]->purchase_id) }}" target="_blank"><span class="fas fa-cloud-download-alt" ></span><span class="d-none d-sm-inline-block ms-1">Download Invoice</span></a>
                        @endif--}}
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
                                    <span class="h5 mb-1" id="staticBackdropLabel">SELECT INVOICES TO SETTLE</span> (<span class="text-right">Unused Balance: <span id="unused-balance">{{ number_format($transaction->unused_balance, 3) }}</span></span>)

                                </div>
                                <div class="p-4">
                                    <form action="{{ route('accounts.processPayment') }}" method="POST">
                                        @csrf
                                    <div class="row">
                                        <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                                            <table class="table mb-0 table-sm table-bordered table-striped" id="datatable1">
                                                <thead class="bg-200">
                                                <tr>
                                                    <th>#</th>
                                                    <th>INV NUMBER</th>
                                                    <th>F/YEAR</th>
                                                    <th>INV DATE</th>
                                                    <th>AMT DUE</th>
                                                    <th>WHT TAX</th>
                                                    <th>AMT PAID</th>
                                                    <th>STATUS</th>
                                                    <th>NET</th>
                                                    <th>FULL</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($invoices as $inv)
                                                    <tr>
                                                        <td> {{ $loop->iteration }} </td>
                                                        <td> {{ $inv->voucher_number }}</td>
                                                        <td> {{ \Carbon\Carbon::parse($inv->year_starting)->format('Y') ==  \Carbon\Carbon::parse($inv->year_ending)->format('Y') ? \Carbon\Carbon::parse($inv->year_starting)->format('Y') : \Carbon\Carbon::parse($inv->year_starting)->format('Y').'/'.\Carbon\Carbon::parse($inv->year_ending)->format('y') }} </td>
                                                        <td> {{ \Carbon\Carbon::createFromTimestamp($inv->date_invoiced)->format('d/m/y') }}</td>
                                                        <td> {{ number_format($inv->amount_due, 2) }}</td>
                                                        <td> {{ number_format($inv->total_tax, 2) }}</td>
                                                        <td> {{ number_format($inv->amount_settled, 2) }}</td>
                                                        <td> {!! $inv->amount_due == $inv->amount_settled ? '<span class="badge bg-success"> Fully Settled </span>' : ($inv->amount_settled > 0 && $inv->amount_settled !== null ? '<span class="badge bg-info"> Partially Settled </span>' : '<span class="badge bg-danger"> Pending </span>') !!} </td>
                                                        <td>
                                                            <input type="radio" name="payment_type[{{ $inv->purchase_id }}]" value="1"
                                                                   @if($inv->total_tax == 0) disabled @endif
                                                                   class="payment-radio"
                                                                   data-invoice-id="{{ $inv->purchase_id }}">
                                                        </td>
                                                        <td>
                                                            <input type="radio" name="payment_type[{{ $inv->purchase_id }}]" value="2"
                                                                   class="payment-radio"
                                                                   data-invoice-id="{{ $inv->purchase_id }}">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                            <input type="hidden" id="form_data" name="form_data" value="{}">
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                    let formDataInput = document.getElementById("form_data");
                                                    let selectedItems = formDataInput.value ? JSON.parse(formDataInput.value) : {};
                                                    let lastSelected = {}; // Track the last selected radio per invoice ID

                                                    document.addEventListener("click", function (event) {
                                                        if (event.target.classList.contains("payment-radio")) {
                                                            let invoiceId = event.target.dataset.invoiceId;
                                                            let paymentType = event.target.value;

                                                            // Check if this radio button was already selected
                                                            if (lastSelected[invoiceId] === event.target) {
                                                                // If so, deselect it
                                                                event.target.checked = false;
                                                                delete selectedItems[invoiceId]; // Remove from the selection
                                                                lastSelected[invoiceId] = null; // Reset tracking
                                                            } else {
                                                                // Otherwise, register the selection
                                                                selectedItems[invoiceId] = paymentType;
                                                                lastSelected[invoiceId] = event.target; // Track selected radio
                                                            }

                                                            // Update hidden input field
                                                            formDataInput.value = JSON.stringify(selectedItems);
                                                        }
                                                    });

                                                    // Restore selections when the table updates
                                                    function restoreSelections() {
                                                        document.querySelectorAll(".payment-radio").forEach((radio) => {
                                                            let invoiceId = radio.dataset.invoiceId;
                                                            if (selectedItems[invoiceId] === radio.value) {
                                                                radio.checked = true;
                                                                lastSelected[invoiceId] = radio; // Track the last selected radio
                                                            }
                                                        });
                                                    }

                                                    // Restore selections when the page loads
                                                    restoreSelections();

                                                    // Ensure form submits only selected items
                                                    document.querySelector("form").addEventListener("submit", function () {
                                                        formDataInput.value = JSON.stringify(selectedItems);
                                                    });

                                                    // Restore selections when DataTables updates
                                                    $('#datatable1').on('draw.dt', function () {
                                                        restoreSelections();
                                                    });
                                                });
                                            </script>

                                            <input type="hidden" value="{{ $transaction->payment_id }}" name="paymentId">
                                        </div>
                                    </div>
                                        <div class="d-flex justify-content-center mt-3 mb-4">
                                            <button type="submit" class="btn btn-md btn-success col-md-7">Process Payments</button>
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
                    <table class="table mb-0 table-sm table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>FY</th>
                            <th>INV NUMBER</th>
                            <th>CURRENCY</th>
                            <th>INV TOTAL</th>
                            <th>NET INV</th>
                            <th>WHT TOTAL</th>
                            <th>AMOUNT SETTLED</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $totalAmountDue = 0; $totalAmountSettled = 0; ?>
                        @foreach($transactions as $invoice)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ \Carbon\Carbon::parse($invoice->year_starting)->format('Y') ==  \Carbon\Carbon::parse($invoice->year_ending)->format('Y') ? \Carbon\Carbon::parse($invoice->year_starting)->format('Y') : \Carbon\Carbon::parse($invoice->year_starting)->format('Y').'/'.\Carbon\Carbon::parse($invoice->year_ending)->format('y') }} </td>
                                <td> {{ $invoice->voucher_number }}</td>
                                <td> {{ $invoice->currency_symbol }}</td>
                                <td> {{ number_format($invoice->amount_due, 2) }}</td>
                                <td> {{ number_format($invoice->amount_due - $invoice->wht, 2) }}</td>
                                <td> {{ number_format($invoice->wht, 2) }}</td>
                                <td> {{ number_format($invoice->amount_settled, 2) }}</td>
                                <td> {!! number_format($invoice->amount_due, 2) === number_format($invoice->amount_settled, 2) ? '<span class="badge bg-success"> Fully Settled </span>' : '<span class="badge bg-info"> Partially Settled </span>' !!} </td>
                                <td>
                                    <a class="text-danger" onclick="return confirm('Are sure you want to remove this allocation?')" href="{{ route('accounts.removePaymentItem', $invoice->payment_item_id) }}"><span class="fa fa-trash"></span> </a>
                                </td>
                            </tr>
                                <?php
                                $totalAmountDue += $invoice->amount_due;
                                $totalAmountSettled += $invoice->amount_settled;
                                ?>
                        @endforeach
                        </tbody>

                        @if(!\PHPUnit\Framework\isEmpty($transactions))
                            <tr>
                                <td colspan="4" class="fw-bold">SUBTOTAL </td>
                                <td class="fw-bold">{{  $transactions[0]->currency_symbol }} {{ number_format($totalAmountDue, 2) }}</td>
                                <td class="fw-bold">{{  $transactions[0]->currency_symbol }} {{ number_format($totalAmountSettled, 2) }}</td>
                                <td> {!! $totalAmountSettled == $totalAmountDue ? '<span class="badge bg-success"> Fully Settled </span>' : '<span class="badge bg-info"> Partially Settled </span>' !!} </td>
                            </tr>
                        @endif

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
            pageLength: 100
        });

        $('#datatable1').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
