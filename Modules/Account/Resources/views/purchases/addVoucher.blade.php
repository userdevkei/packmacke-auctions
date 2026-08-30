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
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Add Payment Voucher</h5>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form class="needs-validation" novalidate id="form" method="POST" action="{{ route('accounts.storePurchaseInvoice') }}">
                        @csrf
                        <div class="container-fluid invoice-container">
                            <div class="invoice-header">
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="mx-2" style="width: 15vw !important;">
                                        <label>TRANSACTION FINANCIAL YEAR</label>
                                        <select class="form-select financialYear js-choice" id="financialYear" name="financialYear" required>
                                            <option disabled selected value="">-- select FY --</option>
                                            @foreach($financialYears as $fy)
                                                <option value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row row-cols-sm-3 g-1">
                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold" style="font-size: 85% !important;">SUPPLIER/CREDITOR</label>
                                        <select class="form-select js-choice" name="accountId" id="accountId" required>
                                            <option disabled value="" selected>-- select account to bill --</option>
                                            @foreach($debtors as $account)
                                                <option value="{{ $account->client_account_id }}">{{ $account->client_account_name }} - {{ $account->currency_symbol }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="invoiceDate" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">INVOICE DATE </label>
                                        <input type="date" name="invoiceDate" class="form-control" id="invoiceDate" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" style="height: 62% !important;">
                                    </div>
                                    <div>
                                        <label for="invoiceDate" class="form-label fs-6 fw-bold" style="font-size: 85% !important;">INVOICE DUE DATE</label>
                                        <input type="date" name="dueDate" class="form-control" id="dueDate" value="{{ \Carbon\Carbon::now()->addDay(30)->format('Y-m-d') }}" style="height: 62% !important;">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-start mb-3" style="white-space: nowrap !important;">
                                <div class="mb-3">
                                    <div class="btn-group">
                                        <select id="itemSelect" class="form-control form-select col-9" style="height: 4vh !important;">
                                            <option disabled value="" selected>-- select item to add --</option>
                                        </select>
                                        <button id="addItemButton" class="btn btn-sm btn-success col-3" style="width: 20vw !important;">Add Item</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-striped table-sm fs-sm invoice-table table-bordered">
                                    <thead>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Tax</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                    </thead>
                                    <tbody id="invoiceItems">
                                    <!-- Rows for items will be added dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="row row-cols-sm-2 g-1">
                                <div class="mb-3">
                                    <label for="invoiceNumber" class="form-label">Invoice Number</label>
                                    <input type="text" class="form-control form-control-lg" name="invoiceNumber" id="invoice_number" required>
                                    <span id="invoice_status"></span>
                                </div>
                                <div class="mb-3">
                                    <label for="customerMessage" class="form-label">Customer Message</label>
                                    <input type="text" class="form-control form-control-lg" id="customerMessage" name="customerMessage" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="summary-section mt-1" style="font-size: 85% !important;">
                                        <h6 class="my-2"><u>ACCOUNT SUMMARY</u></h6>
                                        <p id="account" style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT CATEGORY</p>
                                        <p id="subAccount" style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT SUBCATEGORY</p>
                                        <p id="chartAccount" style="font-style: italic !important; font-family: Cambria,cursive;">CHART ACCOUNT</p>
                                        <p id="accountName" style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT NAME</p>
                                        <p id="accountCurrency" style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNTS CURRENCY</p>
                                        <p id="openingDate" style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT OPENING DATE</p>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="mb-3">
                                        <select class="form-select taxBracket" id="taxBracket" name="taxBracket">
                                            @foreach($taxes->where('effect', 1) as $tax)
                                                <option selected value="{{ $tax->tax_bracket_id }}" data-rate="{{ $tax->tax_rate }}" data-name="{{ $tax->tax_name }}">{{ $tax->tax_name }} {{ $tax->tax_rate }}%</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <p>Subtotal</p>
                                        <p id="total">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <p>Total VAT</p>
                                        <p id="totalVat">0.00</p>
                                        <input type="hidden" id="taxTotal" value="" name="totalTax">
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <p>Amount Due</p>
                                        <p id="totalDue"> </p>
                                        <input type="hidden" id="amountDue" name="amountDue" value="">
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button id="submitButton" class="btn btn-success me-2 save">Save & Close</button>
{{--                                        <button class="btn btn-success save">Save & New</button>--}}
                                    </div>
                                </div>
                            </div>
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
    $(document).ready(function () {
        $('#accountId').on('change', function () {
            var account = $('#accountId').val();

            $.ajax({
                type: 'GET',
                url: '{{ route('accounts.fetchAccount') }}',
                data: { account },
                success: function (data) {

                    $('#account').text(data.account_number+' - '+data.account_name)
                    $('#subAccount').text(data.sub_category_number+' - '+data.sub_account_name)
                    $('#chartAccount').text(data.chart_number+' - '+data.chart_name)
                    $('#accountName').text(data.client_account_number+' - '+data.client_account_name)
                    $('#accountCurrency').text(data.currency_name +' ('+data.currency_symbol+')')

                    var openingDate = new Date(data.opening_date * 1000); // assuming opening_date is in seconds
                    var formattedDate = openingDate.getFullYear() + '-' +
                        ('0' + (openingDate.getMonth() + 1)).slice(-2) + '-' +
                        ('0' + openingDate.getDate()).slice(-2) + ' ' +
                        ('0' + openingDate.getHours()).slice(-2) + ':' +
                        ('0' + openingDate.getMinutes()).slice(-2) + ':' +
                        ('0' + openingDate.getSeconds()).slice(-2);

                    $('#openingDate').text(formattedDate)
                }
            });

            $.ajax({
                type: 'GET',
                url: '{{ route('accounts.getExpenseItems') }}',
                data: { account },
                success: function (response) {

                    $('#itemSelect').find('option:not(:first)').remove();
                    $('#invoiceItems').empty();
                    // $('#itemSelect').append(' <option disabled value="" selected>-- select item to add --</option>');
                    response.forEach(function (item) {
                        $('#itemSelect').append(
                            $('<option>', {
                                value: item.client_account_id,
                                'data-account': item.client_account_id,
                                'data-name': item.client_account_name,
                                'data-rate': item.rate,
                                text: item.client_account_name
                            })
                        );
                    });

                }
            });

        });

        let itemIndex = 1;

        $('#addItemButton').on('click', function() {
            event.preventDefault();
            const selectedItem = $('#itemSelect').find('option:selected');
            const itemName = selectedItem.data('name');
            const perCost = selectedItem.data('rate');
            const clientId = selectedItem.data('account');
            const itemId = selectedItem.val();

            const itemRate = (perCost === '' || typeof perCost === 'undefined') ? 0 : perCost;

            if (!itemId) {
                alert('Please select an item to add.');
                return;
            }

            // Check if the item already exists in the table
            //const isItemAlreadyAdded = $(`#invoiceItems tr[data-item-id="${itemId}"]`).length > 0;
            //if (isItemAlreadyAdded) {
            //    alert('This item has already been added.');
            //    return;
            //}

            const row = `
        <tr data-item-id="${itemId}">
            <td>${itemIndex}</td>
            <input type="hidden" value="${clientId}" name="items[${itemIndex}][client_id]">
            <td contenteditable="true">${itemName}</td>
            <td><input type="text" class="form-control" name="items[${itemIndex}][description]" /></td>
            <td><input type="number" min="1" step="0.01" class="form-control quantity" name="items[${itemIndex}][quantity]" value="1" required /></td>
            <td><input type="number" min="1" step="0.01" class="form-control rate" name="items[${itemIndex}][rate]" value="${perCost}" required /></td>
            <td><select class="form-control form-control-sm vatable" name="items[${itemIndex}][vatable]"><option value="0">Non-Vatable</option><option value="1">Vatable</option></select></td>
            <td class="amount">${itemRate}</td>
            <td><a class="btn-link text-danger btn-sm removeItemButton" title="remove item"><span class="fa-solid fa-trash-can"></span></a></td>
        </tr>
    `;

            $('#invoiceItems').append(row);
            itemIndex++;
            calculateTotal();
        });


        $(document).on('click', '.removeItemButton', function() {
            $(this).closest('tr').remove();
            updateSerialNumbers();
            calculateTotal();
        });

        $(document).on('input', '.quantity, .rate, .vatable, .taxBracket', function() {
            const row = $(this).closest('tr');
            const quantity = row.find('.quantity').val();
            const rate = row.find('.rate').val();
            const amount = quantity * rate;
            row.find('.amount').text(amount.toFixed(2));
            calculateTotal();
        });

        function updateSerialNumbers() {
            itemIndex = 1;
            $('#invoiceItems tr').each(function() {
                $(this).find('td:first').text(itemIndex);
                itemIndex++;
            });
        }

        function calculateTotal() {
            let total = 0;
            let tax = 0;
            let amountDue = 0;
            let whtTax = 0;
            $('#invoiceItems tr').each(function() {
                const amount = parseFloat($(this).find('.amount').text());
                let taxRate = parseFloat($('#taxBracket').find('option:selected').data('rate'));
                total += amount;
                const totalTax = parseFloat($(this).find('.vatable').val());
                tax += totalTax * amount * parseFloat(taxRate)/100;
            });
            // Update total in the UI, if needed

            const withHolding = whtTax;
            amountDue = total + tax;

            // console.log(withHolding)

            $('#total').text(total.toFixed(2));
            $('#amountDue').val(amountDue.toFixed(2));
            $('#totalVat').text(tax.toFixed(2))
            $('#taxTotal').val(tax.toFixed(2))
            // $('#taxWithholdingTotal').val(withHolding.toFixed(2));
            // $('#totalWithholdingTax').text(withHolding.toFixed(2));
            $('#totalDue').text(amountDue.toFixed(2));
            // $('#withHoldingTaxTotal').text(withHolding.toFixed(2));
        }

        $('#accountId').on('change', function() {
            $('#invoice_number').val(''); // Clear the input field
        });


        $('#invoice_number').on('input', function() {
            let invoiceNumber = $(this).val();
            let clientId = $('#accountId').val();

            if (invoiceNumber.length > 0) {
                $.ajax({
                    url: '{{ route('accounts.fetchPurchaseInvNumber') }}',
                    method: 'GET',
                    data: {invoiceNumber, clientId},
                    success: function(response) {
                        console.log(response)
                        if (response.exists) {
                            $('#invoice_status').text('Invoice number already exists').css('color', 'red');
                            $('.save').prop('disabled', true);
                        } else {
                            $('#invoice_status').text('Invoice number is available').css('color', 'green');
                            $('.save').prop('disabled', false);
                        }
                    },
                    error: function() {
                        $('#invoice_status').text('Error checking invoice number').css('color', 'orange');
                    }
                });
            } else {
                $('#invoice_status').text('');
            }
        });
        $('#form').on('submit', function(event) {
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
<script>
    $(document).ready(function () {
        const invoiceInput = document.getElementById('invoiceDate');
        const dueInput = document.getElementById('dueDate');

        const addDays = (dateStr, days) => {
            const date = new Date(dateStr);
            date.setDate(date.getDate() + days);
            return date.toISOString().split('T')[0]; // returns YYYY-MM-DD
        };

        const subtractDays = (dateStr, days) => {
            const date = new Date(dateStr);
            date.setDate(date.getDate() - days);
            return date.toISOString().split('T')[0];
        };

        invoiceInput.addEventListener('change', () => {

            if (invoiceInput.value) {
                dueInput.value = addDays(invoiceInput.value, 30);
            }
        });

        dueInput.addEventListener('change', () => {
            if (dueInput.value) {
                invoiceInput.value = subtractDays(dueInput.value, 30);
            }
        });
    });
</script>
