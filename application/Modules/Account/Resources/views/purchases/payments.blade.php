@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Payment Vouchers </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        {{-- @if(auth()->user()->role_id == 9 || auth()->user()->role_id == 7) --}}
                            <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Voucher</span></a>
                        {{-- @endif --}}
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
                                    <h5 class="mb-1" id="staticBackdropLabel">ADD NEW PAYMENT VOUCHER</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row">
                                        <form id="form" class="form" method="POST" action="{{ route('accounts.storePurchasePaymentInvoice') }}">
                                            @csrf
                                            <div class="card p-4 shadow-sm">

                                                {{-- Section: Transaction Details --}}
                                                <h5 class="mb-3 text-primary">Transaction Details</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="financialYear" class="form-label">Financial Year</label>
                                                        <select class="form-select js-choice" id="financialYear" name="financialYear" required>
                                                            <option value="">-- Select financial year --</option>
                                                            @foreach($years as $fy)
                                                                <option value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="account" class="form-label">Payment Method</label>
                                                        <select class="form-select js-choice" id="method" name="account" required>
                                                            <option value="">-- Select account to pay with --</option>
                                                            @foreach($methods as $method)
                                                                <option value="{{ $method->client_account_id }}" data-currency-id="{{ $method->currency_id }}"> {{ $method->client_account_name }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="transaction" class="form-label">Cheque/Transaction Number</label>
                                                        <input type="text" name="transaction" class="form-control" id="transaction" required>
                                                        <span class="mt-2" id="transaction_code" style="margin-top: 15px !important;"></span>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="dateReceived" class="form-label">Date Paid</label>
                                                        <input type="date" name="dateReceived" class="form-control" id="dateReceived" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="si_number" class="form-label">SI Number</label>
                                                        <input type="text" name="si_number" class="form-control" id="si_number">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="amountPaid" class="form-label">Total Amount Paid</label>
                                                        <input type="text" name="amountPaid" class="form-control" id="amountPaid">
                                                    </div>
                                                </div>

                                                <hr class="my-4">

                                                {{-- Section: Add Debit Account --}}
                                                <h5 class="mb-3 text-primary">Accounts to Debit</h5>
                                                <div class="row g-2 align-items-end mb-3">
                                                    <div class="col-md-8">
                                                        <label for="addDebitAccount" class="form-label">Select Account to Debit</label>
                                                        <select class="form-select js-choice" id="addDebitAccount">
                                                            <option value="" disabled selected>-- Select account to debit --</option>
                                                            @foreach($accounts as $account)
                                                                <option value="{{ $account->client_account_id }}" data-name="{{ $account->client_account_name }}" data-currency-id="{{ $account->currency_id }}" data-currency-symbol="{{ $account->currency_symbol }}" > {{ $account->client_account_name }} </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="button" id="appendAccountRow" class="btn btn-outline-primary mt-3">+ Add Debit Account</button>
                                                    </div>
                                                </div>

                                                {{-- Appended Debit Accounts --}}
                                                <div id="debitAccountsContainer" class="mb-3"></div>

                                                {{-- Description --}}
                                                <div class="mb-4">
                                                    <label for="description" class="form-label">Payment Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                                </div>

                                                {{-- Submit --}}
                                                <div class="text-center mt-3">
                                                    <button id="submitButton" type="submit" class="btn btn-success px-5 submitButton">Save Payment Invoice</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
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
                            <th>INVOICE #</th>
                            <th>F. YEAR</th>
                            <th>SUPPLIER NAME</th>
                            <th>ACCOUNT PAID</th>
                            <th>TXT/CHEQUE #</th>
                            <th>AMT RECEIVED</th>
                            <th>AMT USED</th>
                            <th>DATE RECEIVED</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($transactions as $invoice)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $invoice->invoice_number }} </td>
                                <td> {{ Carbon\Carbon::parse($invoice->year_starting)->format('Y') == Carbon\Carbon::parse($invoice->year_ending)->format('Y') ? Carbon\Carbon::parse($invoice->year_starting)->format('Y') : Carbon\Carbon::parse($invoice->year_starting)->format('Y').'/'.Carbon\Carbon::parse($invoice->year_ending)->format('y') }} </td>
                                <td> {{ strtoupper($invoice->client_account_name) }} </td>
                                <td> {{ $invoice->account }} </td>
                                <td> {{ $invoice->transaction_code }} </td>
                                <td> {{ $invoice->currency_symbol }} {{ number_format($invoice->amount_received, 2) }} </td>
                                <td> {{ $invoice->currency_symbol }} {{ number_format($invoice->amount_settled, 2) }} </td>
                                <td> {{ \Carbon\Carbon::createFromTimestamp($invoice->date_received)->format('D, d/m/y') }} </td>
                                <td>
                                    <a class="link text-secondary mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="View Payment Distribution" href="{{ route('accounts.purchaseVoucherDistribution', $invoice->payment_id) }}"> <span class="fas fa-folder-open"> </span> </a> |
                                   @if(auth()->user()->role_id == 7)
                                        <a href="#"
                                           @if($invoice->amount_settled == 0)
                                           class="edit-btn link link-falcon-primary m-2"
                                           @else class="edit-btn link link-danger m-2" @endif
                                           data-id="{{ $invoice->payment_id }}"
                                           data-route="{{ route('accounts.updatePurchasePaymentInvoice', ['id' => '__ID__']) }}"
                                           data-financial-year="{{ $invoice->financial_year_id }}"
                                           data-client-account-id="{{ $invoice->client_id }}"
                                           data-account-id="{{ $invoice->account_id }}"
                                           data-amount="{{ $invoice->amount_received }}"
                                           data-code="{{ $invoice->transaction_code }}"
                                           data-date="{{ \Carbon\Carbon::createFromTimestamp($invoice->date_received)->format('Y-m-d') }}"
                                           data-description="{{ $invoice->description }}"
                                           data-si="{{ $invoice->si_number ?? '' }}"
                                           data-rate="{{ $invoice->exchange_rate ?? '' }}"
                                           data-bs-toggle="modal"
                                           data-bs-target="#editModal">
                                            <span class="fas fa-pen"></span>
                                        </a> |
                                       <a onclick="return confirm('Are you sure you want to delete this payment invoice?')" class="link link-danger mx-2" href="{{ route('accounts.deletePurchasePaymentInvoice', $invoice->payment_id) }}"><i class="fa fa-trash-alt"></i> </a> |
                                    @endif
                                        <a class="link text-success mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Download Payment Voucher" href="{{ route('accounts.downloadPaymentReceipt', $invoice->payment_id) }}" target="_blank"> <span class="fa-solid fa-file-download"></span> </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel">
        <div class="modal-dialog modal-xl mt-6" role="document">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title">Update Payment Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" class="form">
                        @csrf
                        <div class="row row-cols-sm-2 g-2">
                            <div class="mb-4">
                                <label> FINANCIAL YEAR</label>
                                <select class="form-select" id="financialYearModal" size="1" name="financialYear" data-options='{"removeItemButton":true,"placeholder":true}'>
                                    <option value="">-- select financial year --</option>
                                    @foreach($years as $fy)
                                        <option value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label> SUPPLIER NAME</label>
                                <select class="form-select" id="clientAccountModal" size="2" name="clientAccount" data-options='{"removeItemButton":true,"placeholder":true}' style="height: 125% !important;">
                                    <option selected disabled value="" class="text-center">-- select an account to debit --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->client_account_id }}">{{ $account->client_account_name }} {{ $account->currency_symbol }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label> PAYMENT METHOD</label>
                                <select class="form-select" id="accountModal" name="account" required>
                                    <option value="">-- select account to pay to --</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->client_account_id }}">{{ $account->client_account_name }} {{ $account->currency_symbol }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label> AMOUNT PAID</label>
                                <input type="number" step="0.01" id="amountReceived" name="amountReceived" class="form-control" placeholder="--" style="height: 62% !important;">
                            </div>

                            <div class="mb-4">
                                <label> CHEQUE/TRANSACTION NUMBER</label>
                                <input type="text" id="transactionCode" name="transaction" class="form-control" placeholder="--" required style="height: 62% !important;">
                                <span class="d-none" id="status"></span>
                            </div>

                            <div class="mb-4">
                                <label>DATE PAID</label>
                                <input type="date" id="editDateReceived" name="dateReceived" class="form-control" placeholder="--" required style="height: 62% !important;">
                            </div>

                            <div class="mb-4">
                                <label>SI NUMBER</label>
                                <input type="text" id="siNumber" name="si_number" class="form-control" placeholder="--" style="height: 62% !important;">
                            </div>

                            <div class="mb-4">
                                <label>EXCHANGE RATE (OPTIONAL)</label>
                                <input id="exchangeRate" class="form-control" name="exchangeRate" placeholder="exchange rate" style="height: 62% !important;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label>DESCRIPTION</label>
                            <textarea id="editDescription" class="form-control" style="height: 60px !important;" name="description" required></textarea>
                        </div>

                        <div class="d-flex justify-content-center mt-2">
                            <button  type="submit" class="btn btn-success col-md-7 submitButton">UPDATE PAYMENT INVOICE</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable( {
            order: [ 0, 'asc' ],
            pageLength: 50
        } );

        $('#clientAccount').on('change', function () {
            var clientAccount = $(this).val();
            console.log(clientAccount)
            $.ajax({
                type: 'GET',
                url: '{{ route('accounts.getPaymentMethods') }}',
                data: { clientAccount },
                success: function (data) {
                    console.log(data)
                    var $select = $('#account'); // Replace with the actual ID of your <select> element

                    // Clear existing options
                    $select.empty();

                    // Add a default placeholder option
                    $select.append('<option value="">Select a payment method</option>');

                    // Populate new options
                    $.each(data, function(index, paymentMethod) {
                        $select.append('<option value="' + paymentMethod.client_account_id + '">' + paymentMethod.client_account_name +' - '+ paymentMethod.currency_symbol + '</option>');
                    });
                }
            });
        });

        $(document).on('change', '.clientAccount', function () { // Use a class selector and event delegation
            var clientAccount = $(this).val();
            var $row = $(this).closest('tr'); // Get the current row
            var $select = $row.find('.account'); // Find the select within the row

            console.log(clientAccount, $row, $select)

            $.ajax({
                type: 'GET',
                url: '{{ route('accounts.getPaymentMethods') }}',
                data: { clientAccount: clientAccount }, // Important to include the clientAccount
                success: function (data) {

                    // Clear existing options
                    $select.empty();

                    // Add a default placeholder option
                    $select.append('<option value="">Select a payment method</option>');

                    // Populate new options
                    $.each(data, function(index, paymentMethod) {
                        $select.append('<option value="' + paymentMethod.client_account_id + '">' + paymentMethod.client_account_name +' - '+ paymentMethod.currency_symbol + '</option>');
                    });
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    $select.empty();
                    $select.append('<option value="">Error loading</option>');
                }
            });
        });

        $('.form').on('submit', function(event) {
            // event.preventDefault(); // Prevents the default form submission

            var form = $(this);
            var submitButton = $('.submitButton');

            // Simulate form submission process
            setTimeout(function() {
                // Assuming the form submission is successful, disable the button
                submitButton.prop('disabled', true);

                // You can also display a success message or perform other actions here
                // alert('Form submitted successfully!');
            }, 10); // Simulate a delay for the form submission process
        });
    });

    const choicesInstances = {};

    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.edit-btn');
        const form = document.getElementById('editForm');
        const modalElement = document.getElementById('editModal');
        const modal = new bootstrap.Modal(modalElement);

        // Initialize Choices.js
        choicesInstances['financialYearModal'] = new Choices('#financialYearModal', {
            removeItemButton: true,
            shouldSort: false,
        });

        choicesInstances['clientAccountModal'] = new Choices('#clientAccountModal', {
            removeItemButton: true,
            shouldSort: false,
        });

        choicesInstances['accountModal'] = new Choices('#accountModal', {
            removeItemButton: true,
            shouldSort: false,
        });

        editButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const dataset = this.dataset;

                const id = dataset.id;
                const finalRoute = dataset.route.replace('__ID__', id);
                form.action = finalRoute;

                // Set select inputs using Choices.js
                setChoicesValue('financialYearModal', dataset.financialYear);
                setChoicesValue('clientAccountModal', dataset.clientAccountId);
                setChoicesValue('accountModal', dataset.accountId);

                // Set normal form inputs
                document.getElementById('amountReceived').value = dataset.amount ?? '';
                document.getElementById('transactionCode').value = dataset.code ?? '';
                document.getElementById('editDateReceived').value = dataset.date ?? '';
                document.getElementById('siNumber').value = dataset.si?.trim() ?? '';
                document.getElementById('exchangeRate').value = dataset.rate?.trim() ?? '';
                document.getElementById('editDescription').value = dataset.description?.trim() ?? '';

                // Debug log
                console.log('Description:', dataset.description);
                console.log('Exchange Rate:', dataset.rate?.trim());
                console.log('SI Number:', dataset.si?.trim());
                console.log('Date Received:', dataset.date?.trim());

                // Show the modal
                modal.show();
            });
        });

        // Helper to set the value for Choices.js dropdowns
        function setChoicesValue(fieldId, value) {
            const instance = choicesInstances[fieldId];
            if (instance && value !== undefined && value !== null) {
                instance.setChoiceByValue(value);
            }
        }
    });


    $(document).ready(function () {
        // Hide submit button initially
        $('#submitButton').hide();

        // Create account data map
        const accountDataMap = {};
        @foreach($accounts as $account)
            accountDataMap['{{ $account->client_account_id }}'] = {
            name: '{{ $account->client_account_name }}',
            currencyId: '{{ $account->currency_id }}',
            currencySymbol: '{{ $account->currency_symbol }}'
        };
        @endforeach

        // Create payment method data map
        const paymentMethodMap = {};
        @foreach($methods as $method)
            paymentMethodMap['{{ $method->client_account_id }}'] = {
            currencyId: '{{ $method->currency_id }}',
            currencySymbol: '{{ $method->currency_symbol }}'
        };
        @endforeach

        function calculateTotalDebits() {
            let total = 0;
            $('.amount-input').each(function() {
                const value = parseFloat($(this).val()) || 0;
                console.log(value)
                total += value;
            });
            return total;
        }

        function validateAmounts() {
            const amountPaid = parseFloat($('#amountPaid').val()) || 0;
            const totalDebits = calculateTotalDebits();

            // Allow small floating point differences (0.01 tolerance)
            const isValid = amountPaid > 0 && Math.abs(amountPaid - totalDebits) < 0.01;

            // Show/hide submit button based on validation
            $('#submitButton').toggle(isValid);

            // Highlight fields if invalid
            if (amountPaid > 0 && !isValid) {
                $('#amountPaid').addClass('is-invalid');
            } else {
                $('#amountPaid').removeClass('is-invalid');
            }

            return isValid;
        }

        // Validate when amount paid changes
        $('#amountPaid').on('input', validateAmounts);

        // Validate when any debit amount changes
        $(document).on('input', '.amount-input', validateAmounts);

        document.getElementById('appendAccountRow').addEventListener('click', function () {
            const debitSelector = document.getElementById('addDebitAccount');
            const selectedValue = debitSelector.value;

            if (!selectedValue) return;

            const accountData = accountDataMap[selectedValue];
            if (!accountData) return;

            // Get payment method details
            const paymentMethod = document.getElementById('method');
            const paymentMethodValue = paymentMethod.value;

            if (!paymentMethodValue) {
                alert("Please select a payment method first.");
                return;
            }

            const paymentData = paymentMethodMap[paymentMethodValue];
            if (!paymentData) return;

            const accountId = selectedValue;
            const accountName = accountData.name;
            const accountCurrencyId = accountData.currencyId;
            const accountCurrencySymbol = accountData.currencySymbol;
            const paymentCurrencyId = paymentData.currencyId;

            const container = document.getElementById('debitAccountsContainer');
            const row = document.createElement('div');
            row.classList.add('row', 'g-2', 'debit-account-row', 'mb-2', 'border', 'p-2', 'rounded');

            // Determine if we need exchange rate field
            const needsExchangeRate = accountCurrencyId !== paymentCurrencyId;

            // Generate a unique index for this row
            const rowIndex = Date.now();

            let html = `
            <input type="hidden" name="accounts[${rowIndex}][account_id]" value="${accountId}">
            <div class="col-md-5">
                <label class="form-label">Account</label>
                <input type="text" class="form-control" value=" ${accountName} " disabled>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="accounts[${rowIndex}][amount]" class="form-control amount-input" required>
            </div>
        `;

            if (needsExchangeRate) {
                html += `
                <div class="col-md-3">
                    <label class="form-label">Exchange Rate <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" name="accounts[${rowIndex}][exchange_rate]" class="form-control" required>
                </div>
            `;
            } else {
                html += `<input type="hidden" name="accounts[${rowIndex}][exchange_rate]" value="1">`;
            }

            html += `
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
            </div>
        `;

            row.innerHTML = html;
            container.appendChild(row);

            // Reset the selector
            debitSelector.value = '';
            const choiceInstance = debitSelector._choicejs;
            if (choiceInstance) {
                choiceInstance.setChoiceByValue('');
            }

            // Revalidate amounts after adding new row
            validateAmounts();
        });

        // Remove appended rows
        document.getElementById('debitAccountsContainer').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('.debit-account-row').remove();
                validateAmounts();
            }
        });

        // Final validation before form submission
        $('#form').on('submit', function(e) {
            if (!validateAmounts()) {
                e.preventDefault();
                alert('Total amount paid must equal the sum of all debit amounts');
            }

            // Convert the accounts data to the required format
            // const accountsData = [];
            // $('.debit-account-row').each(function() {
            //     const accountId = $(this).find('input[name*="[account_id]"]').val();
            //     const amount = parseFloat($(this).find('input[name*="[amount]"]').val()) || 0;
            //     const exchangeRate = parseFloat($(this).find('input[name*="[exchange_rate]"]').val()) || 1;
            //
            //     accountsData.push({
            //         account_id: accountId,
            //         amount: amount,
            //         exchange_rate: exchangeRate
            //     });
            // });
            //
            // // Create a hidden input with the formatted data or modify the form data
            // $('#form').append(`<input type="hidden" name="formatted_accounts" value='${JSON.stringify(accountsData)}'>`);
        });

        $('#transaction').on('input', function() {
            var code = $(this).val();

            if(code.length > 0) {
                $.ajax({
                    'type': 'GET',
                    'url': '{{ route('accounts.uniqueTransactionCode') }}',
                    'data': { code: code },
                    'success': function(response) {
                        console.log(response);
                        if (response.length > 0) {
                            $('#transaction_code').text('Transaction/Cheque number already exists').css('color', 'red', 'margin-top', '3px');
                            $('.save').prop('disabled', true);
                        } else {
                            $('#transaction_code').text('Transaction/Cheque number is available').css('color', 'green');
                            $('.save').prop('disabled', false);
                        }
                    },
                    error: function() {
                        $('#transaction_code').text('Error checking Transaction/Cheque number').css('color', 'orange');
                    }
                })
            }else {
                $('#transaction_code').text('');
            }
        });

    });

    document.querySelectorAll('.js-choice').forEach(function(element) {
        new Choices(element, {
            searchEnabled: true,
            shouldSort: false, // keeps order as provided
            itemSelectText: '', // no extra "Select" text
            placeholder: true,
            placeholderValue: '-- Select --'
        });
    });

</script>
