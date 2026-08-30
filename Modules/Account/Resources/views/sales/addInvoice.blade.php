@extends('account::layouts.default')
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<style>
    .invoice-container {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 10px;
        margin: 10px;
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

    .is-required {
        border: 2px solid #dc3545 !important;
        background-color: #fff0f0;
    }

</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Add Invoice</h5>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel"
                     aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494"
                     id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <form id="form" method="POST" action="{{ route('accounts.storeInvoice') }}">
                        @csrf
                        {{-- <div class="container-fluid invoice-container">
                            <div class="invoice-header mb-4">
                                <div class="row row-cols-sm-3 g-1 mb-2">
                                    <div>
                                        <label for="" class="form-label fs-6 fw-bold"
                                               style="font-size: 85% !important;">INVOICE TYPE </label>
                                        <select class="form-select invoiceType js-choice" id="invoiceType" required>
                                            <option value="">-- select type --</option>
                                            <option value="1">Shipment Invoice</option>
                                            <option value="2">Non-Shipment Invoice</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="" class="form-label fs-6 fw-bold"
                                               style="font-size: 85% !important;">FINANCIAL YEAR </label>
                                        <select class="form-select financialYear js-choice" id="financialYear"
                                                name="financialYear" required>
                                            <option value="">-- select FY --</option>
                                            @foreach($financialYears as $fy)
                                                <option
                                                    value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="invoiceDate" class="form-label fs-6 fw-bold"
                                               style="font-size: 85% !important;">INVOICE DATE </label>
                                        <input type="date" name="invoiceDate" class="form-control" id="invoiceDate"
                                               value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                               style="height: 62% !important;">
                                    </div>
                                    <div>
                                        <label for="invoiceDate" class="form-label fs-6 fw-bold"
                                               style="font-size: 85% !important;">INVOICE DUE DATE</label>
                                        <input type="date" name="dueDate" class="form-control" id="dueDate"
                                               value="{{ \Carbon\Carbon::now()->addDay(30)->format('Y-m-d') }}"
                                               style="height: 62% !important;">
                                    </div>

                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold"
                                               style="font-size: 85% !important;">ACCOUNT TO INVOICE</label>
                                        <select class="form-select  js-choice" name="accountId" id="accountId" required>
                                            <option disabled value="" selected>-- select account to bill --</option>
                                            @foreach($debtors as $account)
                                                <option
                                                    value="{{ $account->client_account_id }}">{{ $account->client_account_name }}
                                                    - {{ $account->currency_symbol }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="" class="form-label fs-6 fw-bold"
                                               style="font-size: 85% !important;">SI NUMBER </label>
                                        <input text="date" name="siNumber" class="form-control" id="siNumber" required
                                               value="" style="height: 62% !important;">
                                    </div>

                                    <div>
                                        <label for="" class="form-label fw-bold" style="font-size: 85% !important;">CONTAINER
                                            TYPE</label>
                                        <input type="text" name="container" class="form-control" required
                                               style="height: 62% !important;">
                                    </div>

                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold"
                                               style="font-size: 85% !important;">DESTINATION NAME</label>
                                        <select class="form-select  js-choice" name="destination" id="destinationId"
                                                required>
                                            <option disabled value="" selected>-- select destination name --</option>
                                            @foreach($destinations as $destination)
                                                <option
                                                    value="{{ $destination->destination_id }}">{{ $destination->port_name }}</option>
                                            @endforeach
                                              <option value="other">Other Destination (Type name)</option>
                                        </select>

                                         <input type="text" class="form-control mt-2 fade-in" id="newDestination" name="destination_name" placeholder="Enter destination name"></div>
                                    </div>
                                    <div>
                                        <label for="invoiceNumber" class="form-label fw-bold"
                                               style="font-size: 85% !important;">CONSIGNEE</label>
                                        <input type="text" name="consignee" class="form-control"
                                               placeholder="consignee name" style="height: 62% !important;">
                                    </div>

                                </div>
                            </div> --}}

                            <div class="container-fluid invoice-container">
    <div class="invoice-header mb-4">
        <div class="row row-cols-sm-3 g-1 mb-2">
            <div>
                <label for="" class="form-label fs-6 fw-bold"
                       style="font-size: 85% !important;">INVOICE TYPE </label>
                <select class="form-select invoiceType js-choice" id="invoiceType" required>
                    <option value="">-- select type --</option>
                    <option value="1">Shipment Invoice</option>
                    <option value="2">Non-Shipment Invoice</option>
                </select>
            </div>
            <div>
                <label for="" class="form-label fs-6 fw-bold"
                       style="font-size: 85% !important;">FINANCIAL YEAR </label>
                <select class="form-select financialYear js-choice" id="financialYear"
                        name="financialYear" required>
                    <option value="">-- select FY --</option>
                    @foreach($financialYears as $fy)
                        <option
                            value="{{ $fy['financial_year_id'] }}">{{ $fy['financial_year'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="invoiceDate" class="form-label fs-6 fw-bold"
                       style="font-size: 85% !important;">INVOICE DATE </label>
                <input type="date" name="invoiceDate" class="form-control" id="invoiceDate"
                       value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                       style="height: 62% !important;">
            </div>
            <div>
                <label for="dueDate" class="form-label fs-6 fw-bold"
                       style="font-size: 85% !important;">INVOICE DUE DATE</label>
                <input type="date" name="dueDate" class="form-control" id="dueDate"
                       value="{{ \Carbon\Carbon::now()->addDay(30)->format('Y-m-d') }}"
                       style="height: 62% !important;">
            </div>

            <div>
                <label for="accountId" class="form-label fw-bold"
                       style="font-size: 85% !important;">ACCOUNT TO INVOICE</label>
                <select class="form-select js-choice" name="accountId" id="accountId" required>
                    <option disabled value="" selected>-- select account to bill --</option>
                    @foreach($debtors as $account)
                        <option
                            value="{{ $account->client_account_id }}">{{ $account->client_account_name }}
                            - {{ $account->currency_symbol }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="siNumber" class="form-label fs-6 fw-bold"
                       style="font-size: 85% !important;">SI NUMBER </label>
                <input type="text" name="siNumber" class="form-control" id="siNumber" required
                       value="" style="height: 62% !important;">
            </div>

            <div>
                <label for="container" class="form-label fw-bold" style="font-size: 85% !important;">CONTAINER
                    TYPE</label>
                <input type="text" name="container" class="form-control" required
                       style="height: auto !important;">
            </div>

            <div>
                <label for="destinationId" class="form-label fw-bold"
                       style="font-size: 85% !important;">DESTINATION NAME</label>
                <select class="form-select js-choice" name="destination" id="destinationId"
                        required>
                    <option disabled value="" selected>-- select destination name --</option>
                    @foreach($destinations as $destination)
                        <option
                            value="{{ $destination->destination_id }}">{{ $destination->port_name }}</option>
                    @endforeach
                    <option value="other">Other Destination (Type name)</option>
                </select>
                <input type="text" class="form-control mt-0" id="newDestination" name="destination_name" 
                       placeholder="Enter destination name" style="display: none; height: auto !important;">
            </div>

            <div>
                <label for="consignee" class="form-label fw-bold"
                       style="font-size: 85% !important;">CONSIGNEE</label>
                <input type="text" name="consignee" class="form-control"
                       placeholder="consignee name" style="height: auto !important;">
            </div>

        </div>
                      <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-8">
                                    <select id="itemSelect" class="form-control form-select col-7 js-choice">
                                        <option disabled value="" selected>-- select item to add --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button id="addItemButton" class="btn btn-md btn-outline-success col-5"
                                            style="height: 100% !important;"><i class="fa fa-plus"></i> Add Item
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <table class="table table-striped invoice-table table-sm fs-sm table-bordered">
                                    <thead>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>HS Code</th>
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

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="summary-section" style="font-size: 85% !important;">
                                        <h6 class="my-2"><u>ACCOUNT SUMMARY</u></h6>
                                        <p id="account"
                                           style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT
                                            CATEGORY</p>
                                        <p id="subAccount"
                                           style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT
                                            SUBCATEGORY</p>
                                        <p id="chartAccount"
                                           style="font-style: italic !important; font-family: Cambria,cursive;">CHART
                                            ACCOUNT</p>
                                        <p id="accountName"
                                           style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT
                                            NAME</p>
                                        <p id="accountCurrency"
                                           style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNTS
                                            CURRENCY</p>
                                        <p id="openingDate"
                                           style="font-style: italic !important; font-family: Cambria,cursive;">ACCOUNT
                                            OPENING DATE</p>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="mb-3">
                                        <label for="customerMessage" class="form-label">Customer Message</label>
                                        <textarea type="text" class="form-control" id="customerMessage"
                                                  name="customerMessage"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="customerTaxCode" class="form-label">Tax Code</label>
                                        <select class="form-select taxBracket" id="taxBracket" name="taxBracket">
                                            @foreach($taxes->where('effect', 1) as $tax)
                                                <option value="{{ $tax->tax_bracket_id }}"
                                                        data-rate="{{ $tax->tax_rate }}"
                                                        data-name="{{ $tax->tax_name }}">{{ $tax->tax_name }} {{ $tax->tax_rate }}
                                                    %
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <p>Tax</p>
                                        <p id="vatValue">VAT </p>
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
                                        <p id="totalDue"></p>
                                        <input type="hidden" id="amountDue" name="amountDue" value="">
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button id="submitButton" class="btn btn-success me-2">Save & Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
document.getElementById('destinationId').addEventListener('change', function() {
    const newDestinationInput = document.getElementById('newDestination');
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
@endsection
<script>
    $(document).ready(function () {
        $('#accountId').on('change', function () {
            var account = $('#accountId').val();

            $.ajax({
                type: 'GET',
                url: '{{ route('accounts.fetchAccount') }}',
                data: {account},
                success: function (data) {

                    $('#account').text(data.account_number + ' - ' + data.account_name)
                    $('#subAccount').text(data.sub_category_number + ' - ' + data.sub_account_name)
                    $('#chartAccount').text(data.chart_number + ' - ' + data.chart_name)
                    $('#accountName').text(data.client_account_number + ' - ' + data.client_account_name)
                    $('#accountCurrency').text(data.currency_name + ' (' + data.currency_symbol + ')')

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
        });

        $(document).on('change', '.vatable', function () {
            const row = $(this).closest('tr'); // Get the current row
            const descriptionField = row.find('input[name*="[description]"]'); // Find the description input in the same row

            if ($(this).val() == 0) {
                descriptionField.val('0001.12.00');
            } else {
                descriptionField.val('');
            }
        });

        $(document).on('click', '.removeItemButton', function () {
            $(this).closest('tr').remove();
            updateSerialNumbers();
            calculateTotal();
        });

        $(document).on('input', '.quantity, .rate, .vatable, .taxBracket', function () {
            const taxRate = parseFloat($('#taxBracket').find('option:selected').data('rate'));
            const taxName = $('#taxBracket').find('option:selected').data('name');
            const row = $(this).closest('tr');
            const quantity = row.find('.quantity').val();
            const rate = row.find('.rate').val();
            const amount = quantity * rate;
            row.find('.amount').text(amount.toFixed(2));
            calculateTotal();
            $('#vatValue').text(taxName + ' ' + taxRate + '%')
        });

        function updateSerialNumbers() {
            itemIndex = 1;
            $('#invoiceItems tr').each(function () {
                $(this).find('td:first').text(itemIndex);
                itemIndex++;
            });
        }

        function calculateTotal() {
            let total = 0;
            let tax = 0;
            let amountDue = 0;
            // let taxRate = 0;
            $('#invoiceItems tr').each(function () {
                const amount = parseFloat($(this).find('.amount').text());
                let taxRate = parseFloat($('#taxBracket').find('option:selected').data('rate'));
                total += amount;

                const totalTax = parseFloat($(this).find('.vatable').val());
                tax += totalTax * amount * parseFloat(taxRate) / 100;
            });
            // Update total in the UI, if needed

            amountDue = total + tax;

            $('#total').text(total.toFixed(2));
            $('#amountDue').val(amountDue.toFixed(2));
            $('#totalVat').text(tax.toFixed(2))
            $('#taxTotal').val(tax.toFixed(2))
            $('#totalDue').text(amountDue.toFixed(2));
        }

        const taxRate = parseFloat($('#taxBracket').find('option:selected').data('rate'));
        const taxName = $('#taxBracket').find('option:selected').data('name');
        $('#vatValue').text(taxName + ' ' + taxRate + '%')

        $('#form').on('submit', function (event) {
            // event.preventDefault(); // Prevents the default form submission

            var form = $(this);
            var submitButton = $('#submitButton');

            // Simulate form submission process
            setTimeout(function () {
                // Assuming the form submission is successful, disable the button
                submitButton.prop('disabled', true);

                // You can also display a success message or perform other actions here
                // alert('Form submitted successfully!');
            }, 10); // Simulate a delay for the form submission process
        });

    });

    let itemSelectChoices; // Global scope
    let itemIndex = 1;

    document.addEventListener('DOMContentLoaded', function () {
        const itemSelectElement = document.getElementById('itemSelect');

        // Initialize Choices.js for single select
        itemSelectChoices = new Choices(itemSelectElement, {
            shouldSort: false,
            searchEnabled: true,
            placeholder: true,
            placeholderValue: '-- Select item to add --',
            noResultsText: 'No items found',
            itemSelectText: '',
            maxItemCount: 1
        });

        // Load options via AJAX
        function loadIncomeStreams(account) {
            $.ajax({
                type: 'GET',
                url: '{{ route('accounts.getIncomeStreams') }}',
                data: {account},
                success: function (response) {
                    const choices = response.map(item => ({
                        value: item.client_account_id,
                        label: item.client_account_name,
                        selected: false,
                        disabled: false,
                        customProperties: {
                            rate: item.rate,
                            name: item.client_account_name,
                            account: item.client_account_id,
                        },
                    }));
                    itemSelectChoices.clearChoices();
                    itemSelectChoices.setChoices(choices, 'value', 'label', false);
                },
                error: function () {
                    itemSelectChoices.clearChoices();
                    itemSelectChoices.setChoices([
                        {
                            value: '',
                            label: 'Failed to load items',
                            disabled: true,
                        },
                    ], 'value', 'label', false);
                }
            });
        }

        // Example trigger: load on page load with a default account value
        const account = 1; // Replace with actual dynamic account ID
        loadIncomeStreams(account);
    });

    // Wait for full page (and libraries) to be ready
    $(document).ready(function () {
        // Add item to table
        $(document).on('click', '#addItemButton', function (event) {
            event.preventDefault();

            if (!itemSelectChoices) {
                alert('Choices is not initialized.');
                return;
            }

            const selected = itemSelectChoices.getValue();
            if (!selected || (Array.isArray(selected) && selected.length === 0)) {
                alert('Please select an item to add.');
                return;
            }

            // Handle both array or single object return
            const selectedItem = Array.isArray(selected) ? selected[0] : selected;

            const itemId = selectedItem.value;
            const itemName = selectedItem.label;
            const custom = selectedItem.customProperties || {};

            const perCost = custom.rate || 0;
            const clientId = custom.account || '';
            const itemRate = perCost;

            const uniqueIndex = Date.now(); // For uniqueness in inputs

            const row = `
                <tr data-item-id="${itemId}-${uniqueIndex}">
                    <td>${itemIndex}</td>
                    <td contenteditable="true">${itemName}</td>
                    <td>
                        <input type="text" class="form-control" value="0001.12.00"
                            name="items[${clientId}][${uniqueIndex}][description]" readonly />
                    </td>
                    <td>
                        <input type="number" step="0.0001" class="form-control quantity"
                            name="items[${clientId}][${uniqueIndex}][quantity]" value="1" required />
                    </td>
                    <td>
                        <input type="number" step="0.0001" class="form-control rate"
                            name="items[${clientId}][${uniqueIndex}][rate]" value="${perCost}" required />
                    </td>
                    <td>
                        <select class="form-control form-control-sm vatable"
                            name="items[${clientId}][${uniqueIndex}][vatable]">
                            <option value="0">Non-Vatable</option>
                            <option value="1">Vatable</option>
                        </select>
                    </td>
                    <td class="amount">${itemRate}</td>
                    <td>
                        <a class="btn-link text-danger btn-sm removeItemButton" title="Remove item">
                            <span class="fa-solid fa-trash-can"></span>
                        </a>
                    </td>
                </tr>
            `;

            $('#invoiceItems').append(row);
            itemIndex++;
            calculateTotal();
        });

        // Remove item
        $(document).on('click', '.removeItemButton', function () {
            $(this).closest('tr').remove();
            calculateTotal();
        });

        // Recalculate total on input change
        $(document).on('input', '.rate, .quantity', function () {
            calculateTotal();
        });

        function calculateTotal() {
            let total = 0;
            $('#invoiceItems tr').each(function () {
                const rate = parseFloat($(this).find('.rate').val()) || 0;
                const quantity = parseFloat($(this).find('.quantity').val()) || 1;
                const amount = rate * quantity;
                $(this).find('.amount').text(amount.toFixed(2));
                total += amount;
            });
            $('#totalAmount').text(total.toFixed(2));
        }
    });

    document.querySelectorAll('.js-choice').forEach(function (element) {
        new Choices(element, {
            searchEnabled: true,
            shouldSort: false, // keeps order as provided
            itemSelectText: '', // no extra "Select" text
            placeholder: true,
            placeholderValue: '-- Select --'
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const invoiceType = document.getElementById('invoiceType');
        const siNumber = document.getElementById('siNumber');
        const destination = document.getElementById('destinationId');
        const container = document.querySelector('input[name="container"]');

        function toggleShipmentRequirements() {
            const isShipment = invoiceType.value === "1";

            // Toggle required attribute
            siNumber.required = isShipment;
            destination.required = isShipment;
            container.required = isShipment;

            // Optionally add visual cue
            if (isShipment) {
                siNumber.classList.add('is-required');
                destination.classList.add('is-required');
                container.classList.add('is-required');
            } else {
                siNumber.classList.remove('is-required');
                destination.classList.remove('is-required');
                container.classList.remove('is-required');
            }
        }

        // Attach event listener
        invoiceType.addEventListener('change', toggleShipmentRequirements);

        // Initial call in case of prefilled value
        toggleShipmentRequirements();
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

