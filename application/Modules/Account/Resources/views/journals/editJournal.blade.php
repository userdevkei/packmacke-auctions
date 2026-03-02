@extends('account::layouts.default')
<style>
    /* Apply a fixed width to the Choices dropdown */
    .choices {
        width: 100% !important; /* Set your desired width */
    }

    .choices__input {
        width: 100% !important; /* Ensure input field inside the dropdown takes up the full width */
    }

    .entry-row {
        transition: all 0.3s ease;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
        background-color: rgba(255,255,255,0.9);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .entry-row:hover {
        background-color: #f8f9fa;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .remove-entry {
        cursor: pointer;
        transition: color 0.2s;
    }
    .remove-entry:hover {
        color: #dc3545 !important;
    }
    .form-select-lg, .btn-lg {
        height: calc(3.5rem + 2px);
    }
    .card {
        border-radius: 15px;
    }
    .card-header {
        border-radius: 15px 15px 0 0 !important;
    }
</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Edit {{ $journal->reference_code }} </h5>
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
                    <form method="POST" action="{{ route('accounts.updateAdjustmentJournal', $journal->adjustment_journal_id) }}" id="journalForm">
                        @csrf
                        <input type="hidden" name="reference_code" value="{{ $journal->reference_code }}">

                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <!-- Journal Header -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Journal Date</label>
                                        <input type="date" name="date_adjusted" class="form-control"
                                               value="{{ old('date_adjusted', Carbon\Carbon::createFromTimestamp($journal->date_adjusted)->format('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Description</label>
                                        <input type="text" name="description" class="form-control"
                                               value="{{ old('description', $journal->description) }}" required>
                                    </div>
                                </div>

                                <!-- Debit Entries -->
                                <div class="mb-4 p-3 border rounded bg-light">
                                    <h5 class="fw-bold text-primary mb-3">
                                        <i class="fas fa-arrow-circle-down me-2"></i> Debit Entries
                                        <small class="text-muted float-end">Total: <span id="totalDebits">{{ number_format($debitEntries->sum('amount'), 2) }}</span></small>
                                    </h5>

                                    <div id="debitEntriesContainer">
                                        @foreach($debitEntries as $entry)
                                            <div class="entry-row mb-3 debit-entry" data-entry-id="{{ $entry->adjustment_journal_id }}">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-5">
                                                        <select name="debit[{{ $entry->adjustment_journal_id }}][account_id]" class="form-select debit-account" required>
                                                            <option value="">Select Account</option>
                                                            @foreach($accounts as $account)
                                                                <option value="{{ $account->client_account_id }}"
                                                                        {{ $account->client_account_id == $entry->ledger_id ? 'selected' : '' }}
                                                                        data-currency="{{ $account->currency->currency_code ?? '' }}">
                                                                    {{ $account->client_account_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="number" name="debit[{{ $entry->adjustment_journal_id }}][amount]"
                                                               class="form-control debit-amount"
                                                               value="{{ old("debit.{$entry->adjustment_journal_id}.amount", $entry->amount) }}"
                                                               step="0.01" min="0.01" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" name="debit[{{ $entry->adjustment_journal_id }}][exchange_rate]"
                                                               class="form-control exchange-rate"
                                                               value="{{ old("debit.{$entry->adjustment_journal_id}.exchange_rate", $entry->exchange_rate) }}"
                                                               required>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <button type="button" class="btn btn-sm btn-danger remove-entry">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" class="btn btn-sm btn-primary mt-2" id="addDebitEntry">
                                        <i class="fas fa-plus me-1"></i> Add Debit Line
                                    </button>
                                </div>

                                <!-- Credit Entries -->
                                <div class="mb-4 p-3 border rounded bg-light">
                                    <h5 class="fw-bold text-warning mb-3">
                                        <i class="fas fa-arrow-circle-up me-2"></i> Credit Entries
                                        <small class="text-muted float-end">Total: <span id="totalCredits">{{ number_format($creditEntries->sum('amount'), 2) }}</span></small>
                                    </h5>

                                    <div id="creditEntriesContainer">
                                        @foreach($creditEntries as $entry)
                                            <div class="entry-row mb-3 credit-entry" data-entry-id="{{ $entry->adjustment_journal_id }}">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-5">
                                                        <select name="credit[{{ $entry->adjustment_journal_id }}][account_id]" class="form-select credit-account" required>
                                                            <option value="">Select Account</option>
                                                            @foreach($accounts as $account)
                                                                <option value="{{ $account->client_account_id }}"
                                                                        {{ $account->client_account_id == $entry->ledger_id ? 'selected' : '' }}
                                                                        data-currency="{{ $account->currency->currency_code ?? '' }}">
                                                                    {{ $account->client_account_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="number" name="credit[{{ $entry->adjustment_journal_id }}][amount]"
                                                               class="form-control credit-amount"
                                                               value="{{ old("credit.{$entry->adjustment_journal_id}.amount", $entry->amount) }}"
                                                               step="0.01" min="0.01" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" name="credit[{{ $entry->adjustment_journal_id }}][exchange_rate]"
                                                               class="form-control exchange-rate"
                                                               value="{{ old("credit.{$entry->adjustment_journal_id}.exchange_rate", $entry->exchange_rate) }}"
                                                               required>
                                                    </div>
                                                    <div class="col-md-1 text-center">
                                                        <button type="button" class="btn btn-sm btn-danger remove-entry">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" class="btn btn-sm btn-warning mt-2" id="addCreditEntry">
                                        <i class="fas fa-plus me-1"></i> Add Credit Line
                                    </button>
                                </div>

                                <!-- Balance Validation -->
                                <div id="balanceAlert" class="alert alert-danger d-none mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <span id="balanceMessage">Debit and Credit totals must be equal!</span>
                                </div>

                                <!-- Form Actions -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="{{ route('accounts.viewSystemJournals') }}" class="btn btn-danger">
                                        <i class="fas fa-arrow-left me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success" id="submitJournal">
                                        <i class="fas fa-save me-1"></i> Update Journal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!-- Template for new debit entry -->
                    <template id="debitEntryTemplate">
                        <div class="entry-row mb-3 debit-entry" data-entry-id="new-__TIMESTAMP__">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <select name="debit[new-__TIMESTAMP__][account_id]" class="form-select debit-account" required>
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->client_account_id }}"
                                                    data-currency="{{ $account->currency->currency_code ?? '' }}">
                                                {{ $account->client_account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="debit[new-__TIMESTAMP__][amount]"
                                           class="form-control debit-amount"
                                           value="0.00"
                                           step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="debit[new-__TIMESTAMP__][exchange_rate]"
                                           class="form-control exchange-rate"
                                           value="1.0000"
                                           required>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-entry">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Template for new credit entry -->
                    <template id="creditEntryTemplate">
                        <div class="entry-row mb-3 credit-entry" data-entry-id="new-__TIMESTAMP__">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <select name="credit[new-__TIMESTAMP__][account_id]" class="form-select credit-account" required>
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->client_account_id }}"
                                                    data-currency="{{ $account->currency->currency_code ?? '' }}">
                                                {{ $account->client_account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" name="credit[new-__TIMESTAMP__][amount]"
                                           class="form-control credit-amount"
                                           value="0.00"
                                           step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="credit[new-__TIMESTAMP__][exchange_rate]"
                                           class="form-control exchange-rate"
                                           value="1.00"
                                           step="0.01"
                                           required>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-entry">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function () {
    updateTotals();

    // Add new debit entry with unique timestamp
    $('#addDebitEntry').click(function() {
        const timestamp = Date.now();
        let template = $('#debitEntryTemplate').html();
        template = template.replace(/__TIMESTAMP__/g, timestamp);
        $('#debitEntriesContainer').append(template);
        updateTotals();
    });

    // Add new credit entry with unique timestamp
    $('#addCreditEntry').click(function() {
        const timestamp = Date.now();
        let template = $('#creditEntryTemplate').html();
        template = template.replace(/__TIMESTAMP__/g, timestamp);
        $('#creditEntriesContainer').append(template);
        updateTotals();
    });

    // Remove entry
    $(document).on('click', '.remove-entry', function() {
        $(this).closest('.entry-row').remove();
        updateTotals();
    });

    // Update totals when amounts change
    $(document).on('input', '.debit-amount, .credit-amount', updateTotals);

    // Form submission validation
    $('#journalForm').submit(function(e) {
        const debitTotal = parseFloat($('#totalDebits').text()) || 0;
        const creditTotal = parseFloat($('#totalCredits').text()) || 0;
        const difference = Math.abs(debitTotal - creditTotal);

        if ($('.debit-entry').length === 0 || $('.credit-entry').length === 0) {
            e.preventDefault();
            alert('You must have at least one debit and one credit entry');
            return false;
        }

        if (difference > 0.01) {
            e.preventDefault();
            alert('Debit and Credit totals must be equal before submitting');
            return false;
        }
    });

    // Update currency information when account changes
    $(document).on('change', '.debit-account, .credit-account', function() {
        const currency = $(this).find('option:selected').data('currency');
        $(this).closest('.row').find('.exchange-rate').val(currency === 'USD' ? '1.0000' : '');
    });

    function updateTotals() {
        let totalDr = 0;
        let totalCr = 0;

        $('.debit-amount').each(function() {
            totalDr += parseFloat($(this).val()) || 0;
        });

        $('.credit-amount').each(function() {
            totalCr += parseFloat($(this).val()) || 0;
        });

        $('#totalDebits').text(totalDr.toFixed(2));
        $('#totalCredits').text(totalCr.toFixed(2));

        checkBalance();
    }

    function checkBalance() {
        const debitTotal = parseFloat($('#totalDebits').text()) || 0;
        const creditTotal = parseFloat($('#totalCredits').text()) || 0;
        const difference = Math.abs(debitTotal - creditTotal);

        if (difference > 0.01) {
            $('#balanceAlert').removeClass('d-none');
            $('#balanceMessage').text(`Debit and Credit totals are unbalanced by ${difference.toFixed(2)}`);
        } else {
            $('#balanceAlert').addClass('d-none');
        }
    }
    });
</script>
