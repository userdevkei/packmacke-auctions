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
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Payment</span></a>
                        {{-- @endif --}}
                    </div>
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
                                <h5 class="mb-1" id="staticBackdropLabel">ADD NEW PETTY CASH PAYMENT</h5>
                            </div>
                            <div class="p-4">
                                <div class="row">
                                    <form method="POST" action="{{ route('accounts.storePettyCashPurchase') }}" id="journalForm">
                                        @csrf
                                        <div class="row row-cols-2 g-3">
                                            <!-- Step 1: Select Debit Account -->
                                            <div id="step1" class="mb-3">
                                                <label for="debtor" class="form-label">Credit Account</label>
                                                <select class="form-select js-choice" name="debtor" id="debtor" required style="min-width: 800px !important;">
                                                    <option value="" disabled selected>-- Select Credit Account --</option>
                                                    @foreach($accounts as $debtor)
                                                        <option value="{{ $debtor->client_account_id }}">{{ $debtor->client_account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="debitAmount" class="form-label">Credit Amount</label>
                                                <input type="number" step="0.01" class="form-control" style="height: 62% !important;" name="debitAmount" placeholder="Credit Amount">
                                            </div>
                                        </div>

                                        <!-- Debit Account Details -->
                                        <div id="debitDetails" class="mb-3 d-none">
                                            <h6>Credit Account Currency: <span id="debitCurrency"></span></h6>
                                        </div>
                                        <!-- Step 2: Add Credit Accounts -->
                                        <div id="step2" class="mb-3 d-none">
                                            <h6>Debit Accounts</h6>
                                            <div class="d-flex align-items-stretch" style="width: 100%;">
                                                <select class="form-select choice" id="creditAccountSelect" style="border-radius: 0; border-top-left-radius: 0.375rem; border-bottom-left-radius: 0.375rem;">
                                                    <option value="" disabled selected>-- Select Debit Account --</option>
                                                </select>
                                                <button type="button" class="btn btn-info" id="addCreditAccount" style="border-radius: 0; border-top-right-radius: 0.375rem; border-bottom-right-radius: 0.375rem; height: 4.5vh !important;">
                                                    <span class="fa fa-plus-circle"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Credit Accounts Container -->
                                        <div id="creditAccountsContainer" class="mb-3"></div>

                                        <div class="mb-3">
                                            <h6>Total Debit Amount: <span id="totalCredits">0.00</span></h6>
                                        </div>
                                        <div id="dateAdjustedField" class="mt-3 mb-4">
                                            <label for="dateAdjusted">SI NUMBER/INVOICE NUMBER</label>
                                            <input type="text" class="form-control" name="si_number">
                                        </div>
                                        <div id="dateAdjustedField" class="mt-3 mb-4">
                                            <label for="dateAdjusted">Date Invoiced</label>
                                            <input type="date" class="form-control" name="date_adjusted" required>
                                        </div>
                                        <!-- Submit Button -->
                                        <div id="submitButton" class="d-none d-flex justify-content-center mt-5">
                                            <button type="submit" class="btn btn-success btn-md col-md-7">Save Journal</button>
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
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>DATE</th>
                            <th>REF NUMBER</th>
                            <th>LEDGER</th>
                            <th>SI NUMBER</th>
                            <th>NARRATION</th>
                            <th>DEBIT</th>
                            <th>CREDIT</th>
                            <th>ACTION</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transactions as $journal)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp($journal->date_invoiced)->format('d-m-Y') }}</td>
                                <td>{{ $journal->reference_code }}</td>
                                <td>{{ ucfirst(strtolower($journal->ledger_name)) }}</td>
                                <td>{{ $journal->si_number }}</td>
                                <td>{{ $journal->description }}</td>
                                <td>{{ $journal->type == 1 ? number_format($journal->amount, 2) : '0.00' }}</td>
                                <td>{{ $journal->type == 2 ? number_format($journal->amount, 2) : '0.00' }}</td>
                                <td>
                                    @if($journal->type == 1)
                                        @if(auth()->user()->role_id == 7)
                                            @if($journal->type == 1)
                                            <a class="link link-info m-1" href="{{ route('accounts.editPettyCash', $journal->petty_cash_id) }}"><i class="fa fa-pen"></i> </a> |
                                            <a class="link link-danger m-1" onclick="return confirm('Are you sure you want to delete the selected entry?')" href="{{ route('accounts.deletePettyCash', base64_encode($journal->reference_code)) }}"><i class="fa fa-trash-alt"></i> </a> |
                                            @endif
                                        @endif
                                            <a class="link link-dark m-1" href="{{ route('accounts.downloadPettyCashPayment', $journal->petty_cash_id) }}" target="_blank"><i class="fa fa-cloud-download-alt"></i> </a>
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
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 100
        });

        // Function to calculate total credits
        function calculateTotalCredits() {
            let totalCredits = 0;

            // Iterate through each credit account and calculate total
            $('.creditor-fields').each(function () {
                const creditorId = $(this).attr('id').replace('creditorFields-', ''); // Extract full creditor ID
                const amount = parseFloat($(`#amount-${creditorId}`).val()) || 0;
                const exchangeRate = parseFloat($(`#exchangeRate-${creditorId}`).val()) || 1; // Default exchange rate is 1

                console.log(`creditorId: ${creditorId}, Amount: ${amount}, Exchange Rate: ${exchangeRate}`);
                totalCredits += amount;
            });

            // Display the total credits
            $('#totalCredits').text(totalCredits.toFixed(2));

            // Check if total credits matches the debit amount
            const debitAmount = parseFloat($('input[name="debitAmount"]').val()) || 0;
            if (totalCredits === debitAmount && totalCredits > 0) {
                $('#submitButton').removeClass('d-none');
                $('#dateAdjustedField').removeClass('d-none');
            } else {
                $('#submitButton').addClass('d-none');
                $('#dateAdjustedField').addClass('d-none');
            }
        }

        // Attach event listener to update total credits when fields change
        $('#creditAccountsContainer').on('input', '.creditor-fields input', calculateTotalCredits);

        // Initialize Choices.js for credit accounts dropdown
        let creditAccountCurrencyMap = {};
        let creditAccountSelect = document.getElementById('creditAccountSelect');
        let creditAccountChoices = new Choices(creditAccountSelect, {
            placeholder: true,
            searchPlaceholderValue: 'Search Credit Account...',
        });
        creditAccountChoices.containerOuter.element.style.width = '700px'; // Set width

        // Fetch debit account details and populate credit account options
        $('#debtor').change(function () {
            const clientId = $(this).val();
            if (clientId) {
                $.ajax({
                    url: '{{ route('accounts.fetchPettyCreditAccount') }}',
                    type: 'GET',
                    data: { clientId },
                    dataType: 'json',
                    success: function (response) {
                        const debtor = response.debtor;
                        $('#debitAccountNumber').text(debtor.client_account_name);
                        $('#debitAccountLedger').text(debtor.sub_account_name);
                        $('#debitSubGroup').text(debtor.chart_name);
                        $('#debitCurrency').text(debtor.currency_name);
                        $('#debitDetails').removeClass('d-none');

                        // Populate Credit Account Dropdown
                        const creditors = response.creditors;
                        creditAccountCurrencyMap = {}; // Reset mapping
                        let options = '<option value="" disabled selected>-- Select Debit Account --</option>';
                        creditors.forEach(creditor => {
                            options += `<option value="${creditor.client_account_id}">${toSentenceCaseMulti(creditor.client_account_name)}</option>`;
                            creditAccountCurrencyMap[creditor.client_account_id] = creditor.currency_name;
                        });

                        // Destroy and reinitialize Choices.js with new options
                        creditAccountChoices.destroy();
                        $('#creditAccountSelect').html(options);
                        creditAccountChoices = new Choices(creditAccountSelect, {
                            placeholder: true,
                            searchPlaceholderValue: 'Search Credit Account...',
                        });

                        creditAccountChoices.containerOuter.element.style.width = '700px';
                        $('#step2').removeClass('d-none');
                    },
                    error: function () {
                        alert('An error occurred while fetching credit accounts. Please try again.');
                    }
                });
            }
        });

        // Add Credit Account Fields
        $('#addCreditAccount').click(function () {
            const creditorId = $('#creditAccountSelect').val();
            const creditorName = $('#creditAccountSelect option:selected').text();
            const creditorCurrency = creditAccountCurrencyMap[creditorId];
            const debitCurrency = $('#debitCurrency').text();

            if (!creditorId) {
                alert('Please select a credit account.');
                return;
            }

            // Check if the account is already added
            if ($(`#creditorFields-${creditorId}`).length) {
                alert('This credit account is already added.');
                return;
            }

            // Determine if exchange rate field is required
            const isDifferentCurrency = creditorCurrency !== debitCurrency;

            // Generate HTML for the credit account fields
            let fieldHTML = `
            <div class="creditor-fields mb-3" id="creditorFields-${creditorId}">
                <h6>Details for ${creditorName}</h6>
                <p>Currency: ${creditorCurrency}</p>
                <input type="hidden" name="account[${creditorId}][creditor_id]" value="${creditorId}">
                <div class="row row-cols-3 g-2">
                    <div>
                        <label for="amount-${creditorId}" class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0" name="account[${creditorId}][amount]" id="amount-${creditorId}" class="form-control" placeholder="Enter amount" required>
                    </div>
                    ${isDifferentCurrency ? `
                    <div>
                        <label for="exchangeRate-${creditorId}" class="form-label">Exchange Rate</label>
                        <input type="number" step="0.01" min="0" name="account[${creditorId}][exchange_rate]" id="exchangeRate-${creditorId}" class="form-control" placeholder="Enter exchange rate" required>
                    </div>
                    ` : `<input type="hidden" name="account[${creditorId}][exchange_rate]" id="exchangeRate-${creditorId}">`}
                    <div>
                        <label for="description-${creditorId}" class="form-label">Description</label>
                        <input type="text" name="account[${creditorId}][description]" id="description-${creditorId}" class="form-control" placeholder="Enter description" required>
                    </div>
                </div>
                <button class="btn btn-default btn-sm mt-3 float-end text-danger remove-creditor" data-id="${creditorId}">
                    <span class="fa fa-times mx-2"></span>Remove
                </button>
            </div>
        `;

            $('#creditAccountsContainer').append(fieldHTML);
            calculateTotalCredits();
        });

        // Remove Credit Account Fields
        $('#creditAccountsContainer').on('click', '.remove-creditor', function () {
            const creditorId = $(this).data('id');
            $(`#creditorFields-${creditorId}`).remove();
            calculateTotalCredits();
        });

        // Recalculate total credits when the debit amount is updated
        $('input[name="debitAmount"]').on('input', calculateTotalCredits);

        // Initialize the total credits display
        calculateTotalCredits();

        function toSentenceCaseMulti(text) {
            return text
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        $('#journalForm').on('submit', function(event) {
            // event.preventDefault(); // Prevents the default form submission

            var form = $(this);
            var submitButton = $('#submitButton');

            // Simulate form submission process
            setTimeout(function() {
                // Assuming the form submission is successful, disable the button
                submitButton.prop('disabled', true);

                // You can also display a success message or perform other actions here
                // alert('Form submitted successfully!');
            }, 10); // Simulate a delay for the form submission process
        });

    });

</script>
