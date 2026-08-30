@extends('account::layouts.default')
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<style>
    .invoice-container {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 20px;
        margin: 20px;
    }
    .invoice-header, .invoice-footer {
        /*background-color: #e9ecef;*/
        padding: 10px;
        border-radius: 5px;
    }
    .invoice-table th, .invoice-table td {
        vertical-align: middle;
    }
    .summary-section {
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 10px;
        background-color: #fff;
    }

    #invoiceNumber {
        background-color: #fff !important;
        border: none !important;
    }
</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Add Debit Note For {{ $invoice[0]->voucher_number }}</h5>
                </div>
                {{--                <div class="col-6 col-sm-auto ms-auto text-end ps-0">--}}
                {{--                    <div id="table-simple-pagination-replace-element">--}}
                {{--                        <a class="btn btn-falcon-default btn-sm" href="{{ route('accounts.addInvoice') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Invoice</span></a>--}}
                {{--                    </div>--}}
                {{--                </div>--}}
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form class="needs-validation" novalidate method="POST" action="{{ route('accounts.storeDebitNote', $invoice[0]->purchase_id) }}">
                        @csrf
                        <div class="container-fluid credit-note-container">
                            <div class="credit-note-header mb-4">
                                <div class="row row-cols-sm-3 g-1 mb-2">
                                    <div>
                                        <label for="creditNoteDate" class="form-label fs-sm fw-bold">DEBIT NOTE DATE</label>
                                        <input type="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" name="creditNoteDate" class="form-control" id="creditNoteDate" required {{--style="height: 62% !important;"--}}>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-striped credit-note-table table-bordered fs-sm table-sm">
                                    <thead>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Tax</th>
                                    <th>Total Invoice</th>
                                    <th>New Qty</th>
                                    <th>New Rate</th>
                                    <th>New Total</th>
                                    <th>New Tax</th>
                                    </thead>
                                    <tbody id="creditNoteItems">

                                    <?php $totalInvoice = 0; $netInvoice = 0; $totalTax = 0;?>
                                        <!-- Load the items from the invoice that can be credited -->
                                    @foreach($invoice as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->account_name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->unit_price }}</td>
                                            <td id>{{ $item->tax_rate == null ? 0 : $item->tax_rate }}%</td>
                                            <td>{{ number_format(($item->unit_price * $item->quantity) + ($item->unit_price * $item->quantity) * $item->tax_rate/100, 2) }}</td>
                                            <td><input type="number" value="{{ $item->quantity }}" max="{{ $item->quantity }}" min="0" step="0.001" class="form-control form-control-sm new-qty" name="creditItems[{{ $item->purchase_item_id }}][credit_quantity]" data-rate="{{ $item->unit_price }}" data-tax="{{ $item->tax_rate == null ? 0 : $item->tax_rate }}" placeholder="Enter credit quantity" style="height: 3vh !important;"></td>
                                            <td><input type="number" step="0.001" max="{{ $item->unit_price }}" class="form-control form-control-sm new-rate" name="creditItems[{{ $item->purchase_item_id }}][credit_rate]" data-quantity="{{ $item->quantity }}" value="{{ $item->unit_price }}" placeholder="Enter new rate" style="height: 3vh !important;"></td>
                                            <input type="hidden" value="{{ $item->tax_id }}" name="creditItems[{{ $item->purchase_item_id }}][credit_tax]">
                                            <input type="hidden" value="{{ $item->ledger_id }}" name="creditItems[{{ $item->purchase_item_id }}][ledger_id]">
                                            <td class="new-total">{{ $item->unit_price * $item->quantity }}</td>
                                            <td class="new-tax">{{ $item->tax_id == null ? '0.00' : ($item->unit_price * $item->quantity *  $item->tax_rate/100) }}</td>
                                                <?php
                                                    $totalInvoice += ($item->unit_price * $item->quantity) + ($item->unit_price * $item->quantity)  *  $item->tax_rate/100;
                                                    $netInvoice += ($item->unit_price * $item->quantity);
                                                    $totalTax += ($item->unit_price * $item->quantity)  *  $item->tax_rate/100;
                                                ?>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row fs-sm">
                                <div class="col-md-6">
                                    <div class="summary-section">
                                        <h6 class="my-2"><u>INVOICE SUMMARY</u></h6>
                                        <p id="account">ACCOUNT: {{ $invoice[0]->client_name }}</p>
                                        <p id="invoiceNumber">INVOICE NUMBER: {{ $invoice[0]->invoice_number }}</p>
                                        <p id="">INVOICE AMOUNT: {{ number_format($totalInvoice, 2) }}</p>
                                        <p id="totalCreditAmount">NET AMOUNT: {{ $invoice[0]->currency_symbol }} <span id="totalCreditAmountDisplay"> {{ $netInvoice }} </span></p>
                                        <p >TOTAL TAX : {{ $invoice[0]->currency_symbol }} <span id="totalTaxAmountDisplay"> {{ $totalTax }} </span></p>
                                        <p >TOTAL INVOICE AMOUNT : {{ $invoice[0]->currency_symbol }} <span id="totalAmountDisplay"> {{ $totalInvoice }} </span></p>
                                    </div>
                                </div>
                                <input type="hidden" id="totalInvoiceAmount" name="totalAmount" value="{{ $totalInvoice }}">
                                <input type="hidden" id="totalInvoiceTax" name="totalTaxAmount" value="{{ $totalTax }}">
                                <div class="col-md-6">
                                    <label for="reason" class="form-label fs-sm fw-bold">REASON FOR CREDIT NOTE</label>
                                    <textarea name="reason" class="form-control" id="reason" rows="2" required></textarea>
                                </div>
                            </div>

                            <div class="form-group text-end mt-3">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to proceed and create a credit note for this invoice')">Create Credit Note</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    $(document).ready(function () {
        function calculateTotals() {
            let totalCreditAmount = 0;
            let totalTaxAmount = 0;
            let totalInvoice = 0;

            $('#creditNoteItems tr').each(function() {
                const $row = $(this);
                const newQty = parseFloat($row.find('.new-qty').val()) || 0;
                const newRate = parseFloat($row.find('.new-rate').val()) || 0;
                const taxRate = parseFloat($row.find('.new-qty').data('tax'));

                // Calculate new total and tax for this row
                const newTotal = newQty * newRate;
                const newTax = newTotal * (taxRate / 100);

                $row.find('.new-total').text(newTotal.toFixed(2));
                $row.find('.new-tax').text(newTax.toFixed(2));

                // Add to the total credit and tax
                totalCreditAmount += newTotal;
                totalTaxAmount += newTax;
                totalInvoice = totalCreditAmount + totalTaxAmount;
            });

            // Update the total credit and tax display
            $('#totalCreditAmountDisplay').text(totalCreditAmount.toFixed(2));
            $('#totalTaxAmountDisplay').text(totalTaxAmount.toFixed(2));
            $('#totalInvoiceTax').val(totalTaxAmount.toFixed(2));
            $('#totalAmountDisplay').text(totalInvoice.toFixed(2));
            $('#totalInvoiceAmount').val(totalInvoice.toFixed(2));
        }

        // Trigger the calculation whenever input changes
        $(document).on('input', '.new-qty, .new-rate', calculateTotals);
    });
</script>
