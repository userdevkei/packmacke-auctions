@extends('account::layouts.default')
<style>
    .remove-credit-entry {
        border: none;
        background: transparent;
        font-size: 20px; /* Adjust size of the icon */
        cursor: pointer;
    }
    .remove-credit-entry:hover {
        color: #dc3545; /* Hover color */
    }

</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Edit {{ $petty->reference_code }} </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3 h-100">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form method="POST" action="{{ route('accounts.updatePettyCash', $petty->petty_cash_id) }}">
                        @csrf
                        <div class="container-fluid">
                            <div class="invoice-header">
                                <div class="row row-cols-sm-3 g-2 mb-3">
                                    <div class="mb-2">
                                        <label for="debitAccount">Credit Account</label>
                                        <select class="form-select" name="debitAccount" id="debitAccount" required>
                                            @foreach($ledgers as $ledger)
                                                <option @if($ledger->client_account_id == $petty->ledger_id) selected @endif value="{{ $ledger->client_account_id }}" data-currency="{{ $ledger->currency_id }}">{{ $ledger->client_account_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="debitAmount">Credit Amount</label>
                                        <input type="number" value="{{ $petty->amount }}" class="form-control" name="debitAmount" id="debitAmount" style="height: 60% !important;">
                                    </div>
                                    <div class="mb-2">
                                        <label for="paymentDate">Payment Date</label>
                                        <input type="date" value="{{ \Carbon\Carbon::createFromTimestamp($petty->date_invoiced)->format('Y-m-d') }}" class="form-control" name="paymentDate" style="height: 60% !important;">
                                    </div>
                                </div>

                                <div id="credit-entries-container">
                                    <?php $totalCredits = 0; $creditCounter = 0; ?>
                                    @foreach($journals->where('type', 2) as $jv)
                                        <div class="row row-cols-sm-4 g-2 mb-3 credit-entry" data-entry-id="{{ $jv->petty_cash_id }}">
                                            <div class="mb-2">
                                                <label for="creditAccount{{ ++$creditCounter }}">Debit Account {{ $creditCounter }}</label>
                                                <select class="form-select creditAccount" name="credits[{{ $jv->petty_cash_id }}][creditAccount]" required>
                                                    @foreach($ledgers as $ledger)
                                                        <option @if($ledger->client_account_id == $jv->ledger_id) selected @endif value="{{ $ledger->client_account_id }}" data-currency="{{ $ledger->currency_id }}">{{ $ledger->client_account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-2 amount-field">
                                                <label for="creditAmount{{ $creditCounter }}">Debit Amount {{ $creditCounter }}</label>
                                                <input type="number" step="0.01" value="{{ $jv->amount }}" class="form-control creditAmount" style="height:  60% !important;" name="credits[{{ $jv->petty_cash_id }}][creditAmount]" required>
                                            </div>
                                            <div class="mb-2 exchange-rate-field" @if($petty->currency_id == $jv->currency_id) style="display:none;" @endif>
                                                <label for="exchangeRate{{ $creditCounter }}">Exchange Rate {{ $creditCounter }}</label>
                                                <input type="number" style="height: 60% !important;" step="0.01" class="form-control exchangeRate" value="{{ $jv->exchange_rate }}" name="credits[{{ $jv->petty_cash_id }}][exchangeRate]" @if($petty->currency_id != $jv->currency_id) required @endif>
                                            </div>
                                            <div class="mb-2 position-relative">
                                                <label for="description{{ $creditCounter }}">Narration {{ $creditCounter }}</label>
                                                <input type="text" style="height: 60% !important;" name="credits[{{ $jv->petty_cash_id }}][description]" value="{{ $jv->description }}" class="form-control" required>

                                                <!-- Remove button as Font Awesome Icon -->
                                                <a class="link link-danger remove-credit-entry position-absolute top-0 end-0 translate-middle mt-1 me-2 fs-sm" title="remove this item">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>

                                        </div>
                                            <?php $totalCredits += $jv->amount; ?>
                                    @endforeach
                                </div>

                                <a id="add-credit-entry" class="btn text-success"><i class="fa fa-plus-circle"></i> credit entry</a>

                            </div>

                            <div class="d-flex justify-content-center mt-3">
                                <button type="submit" class="btn btn-md btn-success col-md-7">UPDATE JOURNAL</button>
                            </div>
                        </div>
                    </form>

                    <script>
                        $(document).ready(function() {
                            // Initialize the form to be disabled initially
                            const submitButton = $('button[type="submit"]');
                            submitButton.prop('disabled', true); // Disable the submit button initially

                            // Function to calculate total credit amount
                            function calculateTotalCredits() {
                                let totalCredits = 0;
                                $('.credit-entry').each(function() {
                                    const creditAmount = $(this).find('.creditAmount').val();
                                    if (creditAmount) {
                                        totalCredits += parseFloat(creditAmount);
                                    }
                                });
                                return totalCredits;
                            }

                            // Update the submit button based on the debit amount and total credit amount
                            function updateSubmitButton() {
                                const debitAmount = parseFloat($('#debitAmount').val());
                                const totalCredits = calculateTotalCredits();

                                // If the debit amount equals total credits, enable the submit button
                                if (debitAmount === totalCredits) {
                                    submitButton.prop('disabled', false);
                                } else {
                                    submitButton.prop('disabled', true);
                                }
                            }

                            // Handle changes in Debit Account selection (for currency comparison)
                            $('#debitAccount').change(function() {
                                const selectedOption = this.selectedOptions[0]; // Get the selected option for debit account
                                const debitCurrency = $(selectedOption).data('currency'); // Access data-currency of the selected option

                                // Loop through all credit entries and update exchange rate fields visibility
                                $('.credit-entry').each(function() {
                                    const creditAccountSelect = $(this).find('.creditAccount')[0]; // Find the credit account select
                                    const selectedCreditOption = creditAccountSelect.selectedOptions[0]; // Get the selected option from the credit account
                                    const creditCurrency = $(selectedCreditOption).data('currency'); // Access data-currency from the credit option

                                    const exchangeRateField = $(this).find('.exchange-rate-field');
                                    const amountField = $(this).find('.amount-field');

                                    toggleExchangeRateField(debitCurrency, creditCurrency, exchangeRateField, amountField); // Toggle exchange rate based on currency match
                                });
                            });

                            // Handle changes in Credit Account selection (for currency comparison)
                            $('#credit-entries-container').on('change', '.creditAccount', function() {
                                const creditAccountSelect = this;
                                const selectedCreditOption = creditAccountSelect.selectedOptions[0]; // Get the selected option from the credit account
                                const creditCurrency = $(selectedCreditOption).data('currency'); // Access data-currency from the credit option

                                // Access the debit currency directly from the debit account select element
                                const debitCurrency = $('#debitAccount').find(':selected').data('currency');

                                const exchangeRateField = $(this).closest('.credit-entry').find('.exchange-rate-field');
                                const amountField = $(this).closest('.credit-entry').find('.amount-field');

                                toggleExchangeRateField(debitCurrency, creditCurrency, exchangeRateField, amountField); // Toggle exchange rate based on currency match
                            });

                            // Toggle exchange rate visibility based on currency match
                            function toggleExchangeRateField(debitCurrency, creditCurrency, exchangeRateField, amountField) {
                                if (!debitCurrency || !creditCurrency) return; // Safeguard against null values

                                if (debitCurrency === creditCurrency) {
                                    exchangeRateField.hide();
                                    amountField.removeClass('col-sm-3').addClass('col-sm-4');
                                    exchangeRateField.find('.exchangeRate').prop('required', false);
                                } else {
                                    exchangeRateField.show();
                                    amountField.removeClass('col-sm-4').addClass('col-sm-3');
                                    exchangeRateField.find('.exchangeRate').prop('required', true);
                                }
                            }

                            // Event listener for changes in debit amount
                            $('#debitAmount').on('input', function() {
                                updateSubmitButton();
                            });

                            // Event listener for changes in credit amounts
                            $('#credit-entries-container').on('input', '.creditAmount', function() {
                                updateSubmitButton();
                            });

                            // Event listener for adding new credit entry
                            $('#add-credit-entry').click(function() {
                                const creditCounter = $('.credit-entry').length + 1; // Calculate new entry ID
                                let newEntry = `
            <div class="row row-cols-sm-4 g-2 mb-3 credit-entry" data-entry-id="new-${creditCounter}">
                <div class="mb-2">
                    <label for="creditAccount${creditCounter}">Credit Account ${creditCounter}</label>
                    <select class="form-select js-choice creditAccount" name="credits[new-${creditCounter}][creditAccount]" required>
                        @foreach($ledgers as $ledger)
                                <option value="{{ $ledger->client_account_id }}" data-currency="{{ $ledger->currency_id }}">{{ $ledger->client_account_name }}</option>
                        @endforeach
                                </select>
                            </div>
                            <div class="mb-2 amount-field">
                                <label for="creditAmount${creditCounter}">Credit Amount ${creditCounter}</label>
                    <input type="number" class="form-control creditAmount" style="height: 60% !important;" name="credits[new-${creditCounter}][creditAmount]" required>
                </div>
                <div class="mb-2 exchange-rate-field" style="display:none;">
                    <label for="exchangeRate${creditCounter}">Exchange Rate ${creditCounter}</label>
                    <input type="number" style="height: 60% !important;" step="0.01" class="form-control exchangeRate" name="credits[new-${creditCounter}][exchangeRate]">
                </div>
                <div class="mb-2 position-relative">
                    <label for="forDescription${creditCounter}">Narration ${creditCounter}</label>
                    <input type="text" style="height: 60% !important;" name="credits[new-${creditCounter}][description]" class="form-control" required>
                    <!-- Remove button as Font Awesome Icon -->
                    <a class="link link-danger remove-credit-entry position-absolute top-0 end-0 translate-middle mt-1 me-2 fs-sm" title="remove this item">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
            </div>
        `;
                                $('#credit-entries-container').append(newEntry);
                                updateSubmitButton(); // Recalculate after adding a new entry
                            });

                            // Handle removing a credit entry
                            $('#credit-entries-container').on('click', '.remove-credit-entry', function() {
                                $(this).closest('.credit-entry').remove();
                                updateSubmitButton(); // Recalculate after removing an entry
                            });
                        });

                    </script>
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



 /*       $('#chartId').on('change', function () {
           var purchaseId = $(this).val();

           $.ajax({
               type: 'get',
               url: '{{ route('accounts.fetchLedgerToScheduleJournal') }}',
               data: {purchaseId},
               success: function (response) {
                   console.log(response);
                   if (response) {
                       // Clear previous options except for the placeholder
                       $('#accountId').val('');
                       $('#accountName').val('');
                       $('#amountDue').val('');

                       $('#accountId').val(response.purchase_id);
                       $('#accountName').val(response.clientName);
                       $('#amountDue').val(response.amount_due);
                   }
               }
           });
        });

        $('#durationId').change(function () {
           var duration = $(this).val();
           var amountDue = $('#amountDue').val();
           var installment = (amountDue / duration).toFixed(2)

            $('#installmentsId').val(installment)

        });


// Attach change event handler to dynamically generated duration dropdowns
        $(document).on('change', '.duration-select', function () {
            let rowId = $(this).data('row-id'); // Get the unique row ID
            let duration = $(this).val();
            let amountDue = $(`#amountDue-${rowId}`).val();
            let installment = (amountDue / duration).toFixed(2);

            console.log(rowId, duration, amountDue, installment)

            // Update the installment field in the specific row
            $(`#installmentsId-${rowId}`).val(installment);
        });
*/
    });
</script>
