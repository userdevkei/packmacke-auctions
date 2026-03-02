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
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Edit Invoice #{{ $invoice[0]->invoice_number }}</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        {{ $invoice[0]->client_name }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form method="POST" action="{{ route('accounts.updateSalesInvoice', $invoice[0]->invoice_id) }}" id="form">
                        @csrf
                        <div class="container-fluid invoice-container">
                            {{-- <div class="invoice-header mb-4">
                                <div class="row row-cols-sm-3 g-1 mb-2">
                                    <div>
                                        <label for="" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">FINANCIAL YEAR </label>
                                        <select class="form-select financialYear js-choice" id="financialYear" name="financialYear" required>
                                            <option value="">-- select FY --</option>
                                            @foreach($financialYears as $fy)
                                                <option @if( $fy['financial_year_id'] ==  $invoice[0]->financial_year_id) selected @endif value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="invoiceDate" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">INVOICE DATE </label>
                                        <input type="date" name="invoiceDate" class="form-control invoiceDate" id="invoiceDate" value="{{ \Carbon\Carbon::createFromTimestamp($invoice[0]->date_invoiced)->format('Y-m-d') }}" style="height: 62% !important;">
                                    </div>
                                    <div>
                                        <label for="invoiceDate" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">INVOICE DUE DATE</label>
                                        <input type="date" name="dueDate" class="form-control dueDate" id="dueDate" value="{{ \Carbon\Carbon::createFromTimestamp($invoice[0]->due_date)->format('Y-m-d') }}" style="height: 62% !important;">
                                    </div>
                                </div>

                                <div class="row row-cols-sm-3 g-2">
                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold" style="font-size: 85% !important;">ACCOUNT TO BILL</label>
                                        <select class="form-select clientsId js-choice" name="accountId" id="clientsId" required>
                                            @foreach($clients as $account)
                                                <option @if($account->client_account_id == $invoice[0]->client_id) selected @endif value="{{ $account->client_account_id }}">{{ $account->client_account_name }} - {{ $account->currency_symbol }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                        <div>
                                        <label for="" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">SI NUMBER </label>
                                        <input type="text" name="siNumber" class="form-control siNumber" id="siNumber" value="{{ $invoice[0]->si_number }}" style="height: 62% !important;">
                                    </div>

                                    <div>
                                        <label for="" class="form-label fw-bold" style="font-size: 85% !important;">CONTAINER TYPE</label>
                                        <input type="text" value="{{ $invoice[0]->container_type }}" name="container" class="form-control containerId" style="height: 62% !important;">
                                    </div>

                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold" style="font-size: 85% !important;">DESTINATION NAME</label>
                                        <select class="form-select js-choice destination" name="destination" id="accountId">
                                            <option disabled value="" selected>-- select destination name --</option>
                                            @foreach($destinations as $destination)
                                                <option @if($invoice[0]->destination_id == $destination->destination_id) selected @endif value="{{ $destination->destination_id }}">{{ $destination->port_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold" style="font-size: 85% !important;">CONSIGNEE</label>
                                        <input type="text" name="consignee" class="form-control consignee" placeholder="consignee name" style="height: 62% !important;" value="{{ $invoice[0]->consignee }}">
                                    </div>

                                </div>
                            </div> --}}
                            <div class="invoice-header mb-4">
    <div class="row row-cols-sm-3 g-1 mb-2">
        <div>
            <label for="" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">FINANCIAL YEAR </label>
            <select class="form-select financialYear js-choice" id="financialYear" name="financialYear" required>
                <option value="">-- select FY --</option>
                @foreach($financialYears as $fy)
                    <option @if( $fy['financial_year_id'] ==  $invoice[0]->financial_year_id) selected @endif value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="invoiceDate" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">INVOICE DATE </label>
            <input type="date" name="invoiceDate" class="form-control invoiceDate" id="invoiceDate" value="{{ \Carbon\Carbon::createFromTimestamp($invoice[0]->date_invoiced)->format('Y-m-d') }}" style="height: 62% !important;">
        </div>
        <div>
            <label for="dueDate" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">INVOICE DUE DATE</label>
            <input type="date" name="dueDate" class="form-control dueDate" id="dueDate" value="{{ \Carbon\Carbon::createFromTimestamp($invoice[0]->due_date)->format('Y-m-d') }}" style="height: 62% !important;">
        </div>
    </div>

    <div class="row row-cols-sm-3 g-2">
        <div>
            <label for="clientsId" class="form-label fw-bold" style="font-size: 85% !important;">ACCOUNT TO BILL</label>
            <select class="form-select clientsId js-choice" name="accountId" id="clientsId" required>
                @foreach($clients as $account)
                    <option @if($account->client_account_id == $invoice[0]->client_id) selected @endif value="{{ $account->client_account_id }}">{{ $account->client_account_name }} - {{ $account->currency_symbol }} </option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label for="siNumber" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">SI NUMBER </label>
            <input type="text" name="siNumber" class="form-control siNumber" id="siNumber" value="{{ $invoice[0]->si_number }}" style="height: 62% !important;">
        </div>

        <div>
            <label for="container" class="form-label fw-bold" style="font-size: 85% !important;">CONTAINER TYPE</label>
            <input type="text" value="{{ $invoice[0]->container_type }}" name="container" class="form-control containerId" style="height: 62% !important;">
        </div>

        <div>
            <label for="destinationId" class="form-label fw-bold" style="font-size: 85% !important;">DESTINATION NAME</label>
            <select class="form-select js-choice destination" name="destination" id="destinationId">
                <option disabled value="">-- select destination name --</option>
                @foreach($destinations as $destination)
                    <option @if($invoice[0]->destination_id == $destination->destination_id) selected @endif value="{{ $destination->destination_id }}">{{ $destination->port_name }}</option>
                @endforeach
                <option value="other">Other Destination (Type name)</option>
            </select>
            <input type="text" class="form-control mt-2" id="newDestinationEdit" name="destination_name" 
                   placeholder="Enter destination name" style="display: none;">
        </div>

        <div>
            <label for="consignee" class="form-label fw-bold" style="font-size: 85% !important;">CONSIGNEE</label>
            <input type="text" name="consignee" class="form-control consignee" placeholder="consignee name" value="{{ $invoice[0]->consignee }}">
        </div>

    </div>
</div>

<script>
document.getElementById('destinationId').addEventListener('change', function() {
    const newDestinationInput = document.getElementById('newDestinationEdit');
    if (this.value === 'other') {
        newDestinationInput.style.display = 'block';
        newDestinationInput.required = true;
    } else {
        newDestinationInput.style.display = 'none';
        newDestinationInput.required = false;
        newDestinationInput.value = '';
    }
});
</script>
                                <div class="table-responsive mb-3">
                                    <table class="table table-striped credit-note-table table-bordered" id="datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Rate</th>
                                            <th>Tax</th>
                                            <th>Total Invoice</th>
                                            <th>HS Code</th>
                                            <th>New Qty</th>
                                            <th>New Rate</th>
                                            <th>VAT</th>
                                            <th>New Total</th>
                                            <th>New Tax</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody id="creditNoteItems">

                                        <?php $totalInvoice = 0; ?>
                                            <!-- Load the items from the invoice that can be credited -->
                                        @foreach($invoice as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <select class="form-select form-select-sm ledgerId" name="creditItems[{{ $item->invoice_item_id }}][ledger_id]">
                                                        @foreach($invoiceItems as $invoiceItem)
                                                            <option @if($item->ledger_id == $invoiceItem->client_account_id) selected @endif value="{{ $invoiceItem->client_account_id }}">{{ $invoiceItem->client_account_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $item->unit_price }}</td>
                                                <td>{{ $item->tax_rate == null ? 0 : $item->tax_rate }}%</td>
                                                <td>{{ number_format(($item->unit_price * $item->quantity) + ($item->unit_price * $item->quantity) * $item->tax_rate / 100, 2) }}</td>
                                                <td style="width: 12vh !important;"><input type="text" value="{{ $item->description }}" class="form-control form-control-sm new-description" name="creditItems[{{ $item->invoice_item_id }}][description]" readonly placeholder="Enter credit quantity"></td>
                                                <td style="width: 12vh !important;"><input type="number" value="{{ $item->quantity }}" step="0.001" class="form-control form-control-sm new-qty" name="creditItems[{{ $item->invoice_item_id }}][credit_quantity]" data-rate="{{ $item->unit_price }}" data-tax="{{ $item->tax_rate == null ? 0 : $item->tax_rate }}" placeholder="Enter credit quantity"></td>
                                                <td style="width: 20vh !important;"><input type="number" step="0.001" class="form-control form-control-sm new-rate" name="creditItems[{{ $item->invoice_item_id }}][credit_rate]" data-quantity="{{ $item->quantity }}" placeholder="Enter new rate" value="{{ $item->unit_price }}"></td>
                                                <input type="hidden" value="{{ $item->tax_id }}" name="creditItems[{{ $item->invoice_item_id }}][credit_tax]" class="credit-tax">
                                                <td>
                                                    <select id="vatable" class="form-select form-select-sm vat" name="creditItems[{{ $item->invoice_item_id }}][vat]">
                                                        <option @if($item->tax_rate == null) selected @endif value="0">Non-Vatable</option>
                                                        <option @if($item->tax_rate !== null) selected @endif value="1" data-tax-id="{{ $taxRates->tax_bracket_id }}">Vatable</option>
                                                    </select>
                                                </td>
                                                <td class="new-total">{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                                                <td class="new-tax">{{ number_format(($item->unit_price * $item->quantity) * $item->tax_rate / 100, 2) }}</td>
                                                <td><a class="btn btn-sm text-danger" onclick="return confirm('Are you sure you want to remove this item from the invoice?')" href="{{ route('accounts.deleteInvoiceItem', $item->invoice_item_id) }}"><span class="fa fa-trash-alt"></span> </a></td>
                                                    <?php $totalInvoice += ($item->unit_price * $item->quantity) + ($item->unit_price * $item->quantity) * $item->tax_rate / 100; ?>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-sm btn-success" id="addNewItem">Add New Item</button>
                                </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="summary-section">
                                        <h6 class="my-2"><u>INVOICE SUMMARY</u></h6>
                                        <p id="account">ACCOUNT: {{ $invoice[0]->client_name }}</p>
                                        <p id="invoiceNumber">INVOICE NUMBER: {{ $invoice[0]->invoice_number }}</p>
                                        <p id="">INVOICE AMOUNT: {{ number_format($totalInvoice, 2) }}</p>
                                        <p id="totalCreditAmount">TOTAL AMOUNT: {{ $invoice[0]->currency_symbol }} <span id="totalCreditAmountDisplay"> 0.00 </span></p>
                                        <p >TOTAL TAX : {{ $invoice[0]->currency_symbol }} <span id="totalTaxAmountDisplay"> 0.00 </span></p>
                                        <p >TOTAL INVOICE AMOUNT : {{ $invoice[0]->currency_symbol }} <span id="totalAmountDisplay"> 0.00 </span></p>
                                    </div>
                                </div>
                                <input type="hidden" id="totalInvoiceAmount" name="totalAmount">
                                <input type="hidden" id="totalInvoiceTax" name="totalTaxAmount">
                                <div class="col-md-6">
                                    <label for="reason" class="form-label fs-sm fw-bold">REASON FOR EDITING INVOICE</label>
                                    <textarea name="reason" class="form-control" id="reason" rows="2">{{ $invoice[0]->customer_message }}</textarea>
                                </div>
                            </div>
                            <input type="hidden" name="taxRateId" id="taxRateId">
                            <div class="form-group text-end mt-3">
                                <button type="submit" class="btn btn-danger" id="updateInvoiceBtn" disabled
                                        onclick="return confirm('Are you sure you want to update this invoice?')">
                                    Update Invoice
                                </button>
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
        // Function to calculate totals
        function calculateTotals() {
            let totalCreditAmount = 0;
            let totalTaxAmount = 0;
            let totalInvoice = 0;
            const vatTaxRate = @json($taxRates);

            $('#creditNoteItems tr').each(function() {
                const $row = $(this);
                const newQty = parseFloat($row.find('.new-qty').val()) || 0;
                const newRate = parseFloat($row.find('.new-rate').val()) || 0;
                const vat = parseInt($row.find('.vat').val());

                // Check if the row is vatable and apply tax accordingly
                let newTotal = newQty * newRate;
                let newTax = 0;

                if (vat === 1) { // Vatable
                    const taxRate = vatTaxRate['tax_rate'];
                    newTax = newTotal * (taxRate / 100);

                    // Update the hidden input for credit_tax with tax_id if vatable
                    const taxId = $row.find('.vat option:selected').data('tax-id');
                    $row.find('.credit-tax').val(taxId);
                } else { // Non-Vatable
                    // Set credit_tax to 0 if non-vatable
                    $row.find('.credit-tax').val(0);
                }

                // Display new totals
                $row.find('.new-total').text(newTotal.toFixed(2));
                $row.find('.new-tax').text(newTax.toFixed(2));

                // Add to the total credit and tax
                totalCreditAmount += newTotal;
                totalTaxAmount += newTax;
                totalInvoice = totalCreditAmount + totalTaxAmount;
            });

            // Update total displays
            $('#totalCreditAmountDisplay').text(totalCreditAmount.toFixed(2));
            $('#totalTaxAmountDisplay').text(totalTaxAmount.toFixed(2));
            $('#totalInvoiceTax').val(totalTaxAmount.toFixed(2));
            $('#totalAmountDisplay').text(totalInvoice.toFixed(2));
            $('#totalInvoiceAmount').val(totalCreditAmount.toFixed(2));
        }

        // Event listener to calculate totals
        // $(document).on('input', '.new-qty, .new-rate, .vat', calculateTotals);
        $(document).on('change input', '.new-qty, .new-rate, .vat, .financialYear, .invoiceDate, .dueDate, .siNumber, .containerId, .destination, .ledgerId, .clientsId, .consignee', calculateTotals);

        let itemCounter = 1; // Counter for unique item IDs

        $('#addNewItem').on('click', function () {
            const newRow = `
        <tr>
            <td>#</td>
            <td>
                <select class="form-select form-select-sm ledgerId" name="creditItems[${itemCounter}][ledger_id]">
                    @foreach($invoiceItems as $invoiceItem)
            <option value="{{ $invoiceItem->client_account_id }}">{{ $invoiceItem->client_account_name }}</option>
                    @endforeach
            </select>
        </td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td><input type="text" class="form-control form-control-sm new-description" name="creditItems[${itemCounter}][description]" value="0001.12.00" readonly></td>
        <td><input type="number" step="0.001" class="form-control form-control-sm new-qty" name="creditItems[${itemCounter}][credit_quantity]" placeholder="Enter quantity"></td>
            <td><input type="number" step="0.001" class="form-control form-control-sm new-rate" name="creditItems[${itemCounter}][credit_rate]" placeholder="Enter rate"></td>
            <input type="hidden" name="creditItems[${itemCounter}][credit_tax]" class="credit-tax" value="0">
            <td>
                <select class="form-select form-select-sm vat" name="creditItems[${itemCounter}][vat]">
                    <option value="0">Non-Vatable</option>
                    <option value="1" data-tax-id="{{ $taxRates->tax_bracket_id }}">Vatable</option>
                </select>
            </td>
            <td class="new-total">0.00</td>
            <td class="new-tax">0.00</td>
            <td><a class="btn btn-sm text-danger remove-item"><span class="fa fa-trash-alt"></span></button></td>
        </tr>
    `;
            $('#creditNoteItems').append(newRow);
            calculateTotals(); // Recalculate totals after adding a new row
            // $('#updateInvoiceBtn').disabled = false;
            itemCounter++; // Increment the counter for the next row
        });

// Remove row functionality
        $(document).on('click', '.remove-item', function () {
            $(this).closest('tr').remove();
            calculateTotals(); // Recalculate totals after removing a row
            // $('#updateInvoiceBtn').disabled = false;
        });

        $(document).on('change', '.vat', function() {
            const row = $(this).closest('tr'); // Get the current row
            const descriptionField = row.find('input[name*="[description]"]'); // Find the description input in the same row

            if ($(this).val() == 0) {
                descriptionField.val('0001.12.00');
            } else {
                descriptionField.val('');
            }
        });

        // Remove item functionality
        $(document).on('click', '.remove-item', function () {
            $(this).closest('tr').remove();
            calculateTotals();
            // $('#updateInvoiceBtn').disabled = false;
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form');
        const submitBtn = document.getElementById('updateInvoiceBtn');
        let formChanged = false;

        // Enable the button on any input/select/textarea change
        const enableSubmitOnChange = () => {
            if (!formChanged) {
                formChanged = true;
                submitBtn.disabled = false;
            }
        };

        // Listen to existing inputs
        form.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('input', enableSubmitOnChange);
            el.addEventListener('change', enableSubmitOnChange);
        });

        // If you're dynamically adding items (e.g., with "Add New Item" button),
        // reattach change listeners after new elements are inserted.
        document.getElementById('addNewItem')?.addEventListener('click', function () {
            // Give DOM time to render new row
            setTimeout(() => {
                form.querySelectorAll('input, select, textarea').forEach(el => {
                    if (!el.dataset.bound) {
                        el.addEventListener('input', enableSubmitOnChange);
                        el.addEventListener('change', enableSubmitOnChange);
                        el.dataset.bound = true;
                    }
                });
            }, 100); // Wait 100ms to allow DOM update
        });
    });
</script>

