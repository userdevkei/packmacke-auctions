@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
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
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Journals </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        {{-- @if(auth()->user()->role_id == 7) --}}
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Journal</span></a>
                        <a class="btn btn-falcon-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop-excel"><span class="fas fa-file-upload" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Import Excel</span></a>
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
                                    <h5 class="mb-1" id="staticBackdropLabel">ADD NEW JOURNAL</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row">
                                        <form method="POST" action="{{ route('accounts.storeSystemJournals') }}" id="journalForm" class="needs-validation" novalidate>
                                            @csrf

                                            <div class="card shadow-sm mb-2">
                                                <div class="card-body">
                                                    <!-- Debit Entries Section -->
                                                    <div class="mb-2 p-3 border rounded bg-light">
                                                        <h6 class="fw-bold text-primary mb-3">
                                                            <i class="fas fa-arrow-circle-down me-2"></i>Debit Accounts
                                                        </h6>
                                                        <div class="input-group mb-3">
                                                            <div class="flex-grow-1">  <!-- New wrapper div -->
                                                                <select class="form-select js-choice" id="debitAccountSelect" size="1" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                                    <option value="" disabled selected>-- Select Debit Account --</option>
                                                                    @foreach($debtors as $acc)
                                                                        <option value="{{ $acc->client_account_id }}">{{ ucwords(strtolower($acc->client_account_name)) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <button type="button" class="btn btn-primary btn-md" id="addDebitAccount">
                                                                <i class="fas fa-plus-circle me-1"></i> Add Debit
                                                            </button>
                                                        </div>
                                                        <div id="debitAccountsContainer" class="mt-3"></div>
                                                    </div>

                                                    <!-- Credit Entries Section -->
                                                    <div class="mb-2 p-3 border rounded bg-light">
                                                        <h6 class="fw-bold text-warning mb-3">
                                                            <i class="fas fa-arrow-circle-up me-2"></i>Credit Accounts
                                                        </h6>
                                                        <div class="input-group mb-3">
                                                            <div class="flex-grow-1">
                                                                <select class="form-select js-choice" id="creditAccountSelect" size="1" data-options='{"removeItemButton":true,"placeholder":true}'>
                                                                    <option value="" disabled selected>-- Select Credit Account --</option>
                                                                    @foreach($debtors as $acc)
                                                                        <option value="{{ $acc->client_account_id }}">{{ ucwords(strtolower($acc->client_account_name)) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                                <button type="button" class="btn btn-warning" id="addCreditAccount">
                                                                    <i class="fas fa-plus-circle me-1"></i> Add Credit
                                                                </button>
                                                        </div>
                                                        <div id="creditAccountsContainer" class="mt-3"></div>
                                                    </div>

                                                    <!-- Totals & Validation -->
                                                    <div class="mb-2 p-3 border rounded bg-light">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="alert alert-success">
                                                                    <h6 class="fw-bold mb-0">Total Debit:</h6>
                                                                    <div class="fs-sm fw-bold" id="totalDebits">0.00</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="alert alert-info">
                                                                    <h6 class="fw-bold mb-0">Total Credit:</h6>
                                                                    <div class="fs-sm fw-bold" id="totalCredits">0.00</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div id="balanceAlert" class="alert alert-danger d-none">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <span id="balanceMessage">Debit and Credit totals must be equal!</span>
                                                        </div>
                                                    </div>

                                                    <!-- Date & Submit -->
                                                    <div id="dateAdjustedField" class="mb-2 p-3 border rounded bg-light d-none">
                                                        <label class="form-label fw-bold">Transaction Date</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                            <input type="date" name="date_adjusted" class="form-control form-control-lg" required>
                                                        </div>
                                                    </div>

                                                    <!-- Description Field -->
                                                    <div id="descriptionField" class="mb-3 p-3 border rounded bg-light d-none">
                                                        <label class="form-label fw-bold">Transaction Description</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                                            <input type="text" name="description" class="form-control" placeholder="Enter journal description..." required></input>
                                                        </div>
                                                    </div>

                                                    <div id="submitButton" class="d-none text-center mt-1">
                                                        <button class="btn btn-success btn-md col-md-6">
                                                            <i class="fas fa-check-circle me-2"></i> Submit Journal Entry
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="staticBackdrop-excel" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">IMPORT EXCEL</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row">
                                        <form method="POST" action="{{ route('accounts.importExcel') }}" id="journalForm" class="needs-validation" novalidate enctype="multipart/form-data">
                                            @csrf

                                            <div class="card shadow-sm mb-2">
                                                <div class="card-body">
                                                    <!-- Credit Entries Section -->
                                                    <div class="mb-2 p-3 border rounded bg-light">
                                                        <h6 class="fw-bold text-warning mb-3">
                                                            <i class="fas fa-arrow-circle-up me-2"></i>Excel File
                                                        </h6>
                                                        <div class="input-group mb-3">
                                                            <div class="flex-grow-1">
                                                                <input type="file" class="form-control" name="excelUpload" placeholder="select your excel" required  accept=".xlsx, .xls">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Date & Submit -->
                                                    <div id="dateAdjustedField" class="mb-2 p-3 border rounded bg-light">
                                                        <label class="form-label fw-bold">Transaction Date</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                                            <input type="date" name="date_adjusted" class="form-control form-control-lg" required>
                                                        </div>
                                                    </div>

                                                    <!-- Description Field -->
                                                    <div id="descriptionField" class="mb-3 p-3 border rounded bg-light">
                                                        <label class="form-label fw-bold">Transaction Description</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                                            <input type="text" name="description" class="form-control" placeholder="Enter journal description..." required></input>
                                                        </div>
                                                    </div>

                                                    <div id="submitButton" class="text-center mt-1">
                                                        <button class="btn btn-success btn-md col-md-6">
                                                            <i class="fas fa-check-circle me-2"></i> Submit Journal Entry
                                                        </button>
                                                    </div>
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
                    @if(session('importErrors'))
                        <div class="alert alert-danger">
                            <ul>
                                @foreach(session('importErrors') as $error)
                                    <li>Row {{ $error['row'] }}: {{ implode(', ', $error['errors']) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>DATE</th>
                            <th>REF NUMBER</th>
                            <th>LEDGER</th>
                            <th>DEBIT</th>
                            <th>CREDIT</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($journals as $journal)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::createFromTimestamp($journal->date_adjusted)->format('d-m-Y') }}</td>
                                    <td>{{ $journal->reference_code }}</td>
                                    <td>{{ ucfirst(strtolower($journal->ledger_name)) }}</td>
                                    <td>{{ $journal->type == 1 ? number_format($journal->debit, 2) : '0.00' }}</td>
                                    <td>{{ $journal->type == 2 ? number_format($journal->credit, 2) : '0.00' }}</td>
                                    <td>
                                        @if(auth()->user()->role_id == 7)
                                            @if($journal->type == 1)
                                                <a class="link link-primary m-1" data-fa-transform="shrink-3 down-2" href="{{ route('accounts.editJournal', $journal->adjustment_journal_id) }}"><span class="fa fa-pen"></span></a> |
                                                <a class="link link-danger m-1" onclick="return confirm('Are you sure you want to delete the selected entry?')" href="{{ route('accounts.deleteJournal', base64_encode($journal->reference_code)) }}"><i class="fa fa-trash-alt"></i> </a> |
                                            @endif
                                        @endif
                                            @if($journal->type == 1)
                                                <a class="link link-secondary mx-1" data-fa-transform="shrink-3 down-2" href="{{ route('accounts.downloadJournal', $journal->adjustment_journal_id) }}" target="_blank"><span class="fa fa-cloud-download-alt"></span></a>
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
       // Define checkBalance first so it's available to all other functions
       function checkBalance() {
           const debitTotal = parseFloat(document.getElementById('totalDebits').textContent.replace(/,/g, '')) || 0;
           const creditTotal = parseFloat(document.getElementById('totalCredits').textContent.replace(/,/g, '')) || 0;
           const roundedDebit = Math.round(debitTotal * 100) / 100;
           const roundedCredit = Math.round(creditTotal * 100) / 100;
           const difference = Math.abs(roundedDebit - roundedCredit);

           if (difference > 0.01) {
               document.getElementById('balanceAlert').classList.remove('d-none');
               document.getElementById('submitButton').classList.add('d-none');
               document.getElementById('balanceMessage').textContent =
                   `Totals are unbalanced by ${difference.toFixed(2)}! Please adjust amounts.`;
           } else if (roundedDebit > 0 && roundedCredit > 0) {
               document.getElementById('balanceAlert').classList.add('d-none');
               document.getElementById('submitButton').classList.remove('d-none');
               document.getElementById('dateAdjustedField').classList.remove('d-none');
               document.getElementById('descriptionField').classList.remove('d-none');
           } else {
               document.getElementById('balanceAlert').classList.add('d-none');
               document.getElementById('submitButton').classList.add('d-none');
               document.getElementById('dateAdjustedField').classList.add('d-none');
               document.getElementById('descriptionField').classList.add('d-none');
           }
       }

       function updateTotals() {
           let totalDr = 0;
           let totalCr = 0;

           document.querySelectorAll('.debit-amount').forEach(input => {
               totalDr += parseFloat(input.value) || 0;
           });

           document.querySelectorAll('.credit-amount').forEach(input => {
               totalCr += parseFloat(input.value) || 0;
           });

           document.getElementById('totalDebits').innerText = totalDr.toFixed(2);
           document.getElementById('totalCredits').innerText = totalCr.toFixed(2);

           checkBalance(); // Now this will work
       }

       function addAccountRow(containerId, type, accountId, accountName) {
           const alreadyExists = document.querySelector(
               `#${containerId} input[name='${type}s[][account_id]'][value='${accountId}']`
           ) || document.querySelector(
               `#${containerId} input[name^='${type}s'][value='${accountId}']`
           );

           if (alreadyExists) {
               alert(`This ${type} account is already added.`);
               return;
           }

           const uid = Date.now() + Math.floor(Math.random() * 1000);
           const html = `
            <div class="row g-2 align-items-end mb-2 ${type}-row" data-id="${uid}">
                <input type="hidden" name="${type}s[${uid}][account_id]" value="${accountId}">
                <div class="col-md-4">
                    <input type="text" class="form-control" value="${accountName}" disabled>
                </div>
                <div class="col-md-3">
                    <input type="number" name="${type}s[${uid}][amount]" class="form-control ${type}-amount" placeholder="Amount" step="0.01" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="${type}s[${uid}][exchange_rate]" class="form-control" placeholder="Exchange Rate" required value="1">
                </div>
                <div class="col-md-1 text-start">
                    <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
                </div>
            </div>
        `;
           document.getElementById(containerId).insertAdjacentHTML('beforeend', html);
       }

       // Event listeners
       document.getElementById('addDebitAccount').addEventListener('click', () => {
           const select = document.getElementById('debitAccountSelect');
           const accountId = select.value;
           const accountName = select.options[select.selectedIndex].text;

           if (!accountId) return alert('Please select a debit account.');
           addAccountRow('debitAccountsContainer', 'debit', accountId, accountName);
       });

       document.getElementById('addCreditAccount').addEventListener('click', () => {
           const select = document.getElementById('creditAccountSelect');
           const accountId = select.value;
           const accountName = select.options[select.selectedIndex].text;

           if (!accountId) return alert('Please select a credit account.');
           addAccountRow('creditAccountsContainer', 'credit', accountId, accountName);
       });

       document.addEventListener('input', (e) => {
           if (e.target.classList.contains('debit-amount') || e.target.classList.contains('credit-amount')) {
               updateTotals();
           }
       });

       document.addEventListener('click', (e) => {
           if (e.target.classList.contains('remove-row')) {
               e.target.closest('.row').remove();
               updateTotals();
           }
       });

       // Initialize MutationObserver after DOM is ready
       const observer = new MutationObserver(checkBalance);
       const config = { childList: true, subtree: true, characterData: true };
       observer.observe(document.getElementById('totalDebits'), config);
       observer.observe(document.getElementById('totalCredits'), config);

       // Initial check
       checkBalance();
   });
</script>
<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
