@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Receipts </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Voucher</span></a>
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
                                        <form id="form" class="form" method="POST" action="{{ route('accounts.storePaymentInvoice') }}">
                                            <div class="row row-cols-sm-2 g-2">
                                                @csrf
                                                <div class="mb-4">
                                                    <select class="form-select js-choice" id="financialYear" size="1" name="financialYear" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                    {{--                                                    <select class="form-select financialYear" id="financialYear" name="financialYear" required>--}}
                                                        <option value="">-- select financial year --</option>
                                                        @foreach($years as $fy)
                                                            <option value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                                        @endforeach
                                                    </select>
{{--                                                    <label>TRANSACTION FINANCIAL YEAR</label>--}}
                                                </div>

                                                <div class="form-floating mb-4">
                                                    <select class="form-select js-choice" id="clientAccount" size="2" name="clientAccount" data-options='{"removeItemButton":true,"placeholder":true}' style="height: 125% !important;">
                                                    {{--                                                    <select class="form-select choices" id="clientAccount" name="clientAccount" required>--}}
                                                        <option selected disabled value="" class="text-center">-- select an account to credit --</option>
                                                        @foreach($accounts as $account)
                                                            <option value="{{ $account->client_account_id }}">{{ $account->client_account_name }} {{ $account->currency_symbol }}</option>
                                                        @endforeach
                                                    </select>
{{--                                                    <label>PAYMENT FOR ACCOUNT</label>--}}
                                                </div>

                                                <div class="form-floating mb-4">
                                                    <select class="form-select" id="account" name="account" required >
                                                        <option value="">-- select account to pay to --</option>
                                                    </select>
                                                    <label> PAYMENT METHOD</label>
                                                </div>

                                                <div class="form-floating mb-4">
                                                    <input type="number" step="0.01" name="amountReceived" class="form-control" placeholder="--" >
                                                    <label> AMOUNT RECEIVED</label>
                                                </div>

                                                <div class="form-floating mb-4">
                                                    <input type="text" name="transaction" class="form-control" placeholder="--" >
                                                    <label> CHEQUE/TRANSACTION NUMBER</label>
                                                </div>

                                                <div class="form-floating mb-4">
                                                    <input type="date" name="dateReceived" class="form-control" placeholder="--" required >
                                                    <label>DATE RECEIVED</label>
                                                </div>
                                                <div class="form-floating mb-4">
                                                    <input type="text" name="si_number" class="form-control" placeholder="--">
                                                    <label>SI NUMBER</label>
                                                </div>
                                                <div class="form-floating mb-4">
                                                    <input class="form-control" style="height: 60px !important;" name="exchangeRate" placeholder="exchange rate">
                                                    <label>EXCHANGE RATE (OPTIONAL)</label>
                                                </div>
                                            </div>
                                            <div class="form-floating mb-4">
                                                <textarea class="form-control" style="height: 60px !important;" name="description" required></textarea>
                                                <label>DESCRIPTION</label>
                                            </div>

                                            <div class="d-flex justify-content-center mt-2">
                                                <button id="submitButton" type="submit" class="btn btn-success submitButton">SAVE PAYMENT INVOICE</button>
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
                            <th>INVOICE NUMBER</th>
                            <th>FYR</th>
                            <th>CLIENT ACCOUNT NAME</th>
                            <th>ACCOUNT PAID</th>
                            <th>TR/CHEQUE #</th>
                            <th>AMT RECEIVED</th>
                            <th>AMT UTILIZED</th>
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
                                <td> {{ $invoice->currency_symbol }} {{ number_format($invoice->amount_settled, 2) }}</td>
                                <td> {{ \Carbon\Carbon::createFromTimestamp($invoice->date_received)->format('D, d/m/y') }} </td>
                                <td>
                                    <a class="link text-secondary m-2" data-bs-toggle="tooltip" data-bs-placement="left" title="View Payment Distribution" href="{{ route('accounts.salesInvoiceDistribution', $invoice->transaction_id) }}"> <span class="fas fa-folder-open"> </span> </a>
                                    @if(auth()->user()->role_id == 7)
                                    |
                                        <a href="#"
                                           @if($invoice->amount_settled == 0)
                                               class="edit-btn link link-falcon-primary m-2"
                                           @else class="edit-btn link link-danger m-2" @endif
                                           data-id="{{ $invoice->transaction_id }}"
                                           data-route="{{ route('accounts.updatePaymentInvoice', ['id' => '__ID__']) }}"
                                           data-financial-year="{{ $invoice->financial_year_id }}"
                                           data-client-account-id="{{ $invoice->client_id }}"
                                           data-account-id="{{ $invoice->account_id }}"
                                           data-amount="{{ $invoice->amount_received }}"
                                           data-code="{{ $invoice->transaction_code }}"
                                           data-date="{{ \Carbon\Carbon::createFromTimestamp($invoice->date_received)->format('Y-m-d') }}"
                                           data-description="{{ $invoice->description }}"
                                           data-si-number="{{ $invoice->si_number ?? '' }}"
                                           data-exchange-rate="{{ $invoice->exchange_rate ?? '' }}"
                                           data-bs-toggle="modal"
                                           data-bs-target="#editModal">
                                            <span class="fas fa-pen"></span>
                                        </a>

                                     | <a @if($invoice->amount_settled == 0) class="link text-danger m-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Delete receipt" href="{{ route('accounts.deleteReceipt', $invoice->transaction_id) }}" onclick="return confirm('Are you sure you want to delete selected receipt?')" @endif class="link text-secondary m-2 disabled"> <span class="fas fa-trash-alt"> </span> </a>
                                    @endif
                                    | <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Download Payment Voucher" href="{{ route('accounts.downloadPurchaseReceipt', $invoice->transaction_id) }}" target="_blank"> <span class="fa-solid fa-file-download"></span> </a>
                                    {{--<div class="modal fade" id="staticBackdrop_{{ $invoice->transaction_id }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl mt-6" role="document">
                                            <div class="modal-content border-0">
                                                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                        <h5 class="mb-1" id="staticBackdropLabel">UPDATE PAYMENT VOUCHER</h5>
                                                    </div>
                                                    <div class="p-4">
                                                        <div class="row">
                                                            <form method="POST" action="{{ route('accounts.updatePaymentInvoice', $invoice->transaction_id) }}">
                                                                <div class="row row-cols-sm-2 g-2">
                                                                    @csrf
                                                                    <div class="mb-4">
                                                                        <select class="form-select js-choice" id="financialYear" size="1" name="financialYear" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                                            <option > -- </option>
                                                                            @foreach($years as $fy)
                                                                                <option @if($invoice->financial_year_id == $fy['financial_year_id']) selected @endif value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                    <div class="form-floating mb-4">
                                                                        <select class="form-select js-choice clientAccount" size="2" name="clientAccount" data-options='{"removeItemButton":true,"placeholder":true}' style="height: 125% !important;">
                                                                            <option > -- </option>
                                                                            @foreach($accounts as $account)
                                                                                <option @if($invoice->client_id == $account->client_account_id) selected @endif value="{{ $account->client_account_id }}">{{ $account->client_account_name }} {{ $account->currency_symbol }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>

                                                                    <div class="form-floating mb-4">
                                                                        <select class="form-select account" name="account" required >
                                                                            <option value="{{ $invoice->account_id }}">{{ $invoice->account }}</option>
                                                                        </select>
                                                                        <label> PAYMENT METHOD</label>
                                                                    </div>

                                                                    <div class="form-floating mb-4">
                                                                        <input type="number" step="0.01" name="amountReceived" class="form-control" placeholder="--" value="{{ $invoice->amount_received }}">
                                                                        <label> AMOUNT RECEIVED</label>
                                                                    </div>

                                                                    <div class="form-floating mb-4">
                                                                        <input type="text" name="transaction" class="form-control" placeholder="--" value="{{ $invoice->transaction_code }}">
                                                                        <label> CHEQUE/TRANSACTION NUMBER</label>
                                                                    </div>

                                                                    <div class="form-floating mb-4">
                                                                        <input type="date" name="dateReceived" class="form-control" placeholder="--" required value="{{ \Carbon\Carbon::createFromTimestamp($invoice->date_received)->format('Y-m-d') }}">
                                                                        <label>DATE RECEIVED</label>
                                                                    </div>

                                                                    <div class="form-floating mb-4">
                                                                        <input type="text" name="si_number" class="form-control" placeholder="--" value="{{ $invoice->si_number }}">
                                                                        <label>SI NUMBER</label>
                                                                    </div>
                                                                    <div class="form-floating mb-4">
                                                                        <input class="form-control" style="height: 60px !important;" value="{{ $invoice->exchange_rate }}" name="exchangeRate" placeholder="exchange rate">
                                                                        <label>EXCHANGE RATE (OPTIONAL)</label>
                                                                    </div>
                                                                </div>
                                                                <div class="form-floating mb-4">
                                                                    <textarea class="form-control" style="height: 70px !important;" name="description" required>{{ $invoice->description }}</textarea>
                                                                    <label>DESCRIPTION</label>
                                                                </div>
                                                                <div class="d-flex justify-content-center mt-2">
                                                                    <button type="submit" class="btn btn-success">UPDATE PAYMENT INVOICE</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>--}}
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
                    <form id="editForm" class="form" method="POST">
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
                                <label> CLIENT NAME</label>
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
                            </div>

                            <div class="mb-4">
                                <label>DATE PAID</label>
                                <input type="date" id="dateReceived" name="dateReceived" class="form-control" placeholder="--" required style="height: 62% !important;">
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
                            <textarea id="description" class="form-control" style="height: 60px !important;" name="description" required></textarea>
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
<script>
    $(document).ready(function() {
        $('#datatable').DataTable( {
            order: [ 0, 'asc' ],
            pageLength: 50
        } );

        $('#clientAccount').on('change', function () {
            var clientAccount = $(this).val();

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

        // Initialize Choices
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
                e.preventDefault(); // 👈 prevents <a href="#"> from jumping or submitting

                const id = this.dataset.id;
                const rawRoute = this.dataset.route;

                // ✅ Replacing __ID__ with actual ID
                const finalRoute = rawRoute.replace('__ID__', id);
                form.action = finalRoute;

                setChoicesValue('financialYearModal', this.dataset.financialYear);
                setChoicesValue('clientAccountModal', this.dataset.clientAccountId);
                setChoicesValue('accountModal', this.dataset.accountId);

                // document.getElementById('accountModal').value = this.dataset.accountId;

                document.getElementById('amountReceived').value = this.dataset.amount;
                document.getElementById('transactionCode').value = this.dataset.code;
                document.getElementById('dateReceived').value = this.dataset.date;
                document.getElementById('siNumber').value = this.dataset.siNumber ?? '';
                document.getElementById('exchangeRate').value = this.dataset.exchangeRate ?? '';
                document.getElementById('description').value = this.dataset.description ?? '';

                // const modal = new bootstrap.Modal(document.getElementById('editModal'));
                // modal.show();
            });
        });

        function setChoicesValue(selectId, value) {
            if (!value) return;
            const instance = choicesInstances[selectId];
            const options = instance._currentState.choices.map(choice => choice.value);
            if (options.includes(value.toString())) {
                instance.setChoiceByValue(value.toString());
            } else {
                console.warn(`Value "${value}" not found in #${selectId}`);
            }
        }
    });
</script>
