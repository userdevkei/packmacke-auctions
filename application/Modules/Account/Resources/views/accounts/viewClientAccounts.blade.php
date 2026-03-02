@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">All Ledgers </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-info btn-sm mx-2" type="button" data-bs-toggle="modal"
                           data-bs-target="#staticBackdrop-1"><span class="fas fa-cloud-download-alt"
                                                                    data-fa-transform="shrink-3 down-2"></span><span
                                class="d-none d-sm-inline-block ms-1">Download</span></a>
                        @if(auth()->user()->role_id == 7)
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Ledger</span></a>
                        @endif
                    </div>
                </div>
                <div class="modal fade" id="staticBackdrop-1" data-bs-keyboard="false" data-bs-backdrop="static"
                     tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base"
                                        data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">DOWNLOAD CHART OF ACCOUNT</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row">
                                        <form method="POST" action="{{ route('accounts.downloadChartOfAccounts') }}"
                                              target="_blank">
                                            @csrf
                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">MASTER
                                                    LEDGER</label> <br>
                                                <select name="accountId" class="form-select js-choice" id="accountId">
                                                    <option selected disabled value="">-- select master ledger --
                                                    </option>
                                                    @foreach($accounts as $account)
                                                        <option
                                                            value="{{ $account->account_id }}">{{ $account->account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">GROUP
                                                    LEDGER</label> <br>
                                                <select name="subAccountId" class="form-select" id="subAccountId"
                                                        style="height: 10% !important;">
                                                    <option selected disabled value="">-- select group ledger --
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">SUB-GROUP
                                                    LEDGER</label> <br>
                                                <select name="chartAccountId" class="form-select" id="chartAccountId"
                                                        style="height: 10% !important;">
                                                    <option selected disabled value="">-- select sub-group ledger --
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">REPORT
                                                    FORMAT</label>
                                                <select class="form-select js-choice" name="type" size="1"
                                                        data-options='{"removeItemButton":true,"placeholder":true}'
                                                        style="height: 6% !important;">
                                                    <option disabled selected value="">-- select report format --
                                                    </option>
                                                    <option value="1">PDF FORMAT</option>
                                                    <option value="2">EXCEL FORMAT</option>
                                                </select>
                                            </div>

                                            <div class="d-flex justify-content-center mt-2 mb-3">
                                                <button type="submit" class="btn btn-success">DOWNLOAD CHART OF
                                                    ACCOUNT
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static"
                     tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base"
                                        data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">ADD NEW LEDGER</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row">
                                        <form method="POST" action="{{ route('accounts.addClientAccount') }}">
                                            @csrf
                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">LEDGER
                                                    NAME</label> <br>
                                                <input id="editableSelect" type="text" list="clients"
                                                       name="account_name" class="form-control editableSelect"
                                                       placeholder="-- ledger name --" required
                                                       style="width: 38.5vw !important; height: 6% !important;">
                                                <datalist id="clients">
                                                    @foreach($clients as $client)
                                                        <option value="{{ $client }}">{{ $client }}</option>
                                                    @endforeach
                                                </datalist>
                                            </div>

                                            <div class="mb-3">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">CLIENT
                                                    ADDRESS (optional)</label>
                                                <input type="text" name="client_address" class="form-control"
                                                       style=" height:6% !important;">
                                            </div>

                                            <div class="mb-3">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">ACCOUNT
                                                    KRA PIN (optional)</label>
                                                <input type="text" name="kraPin" class="form-control"
                                                       style="height: 6% !important;">
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">ACCOUNT
                                                    TYPE</label>
                                                <select name="account_category" id="category"
                                                        class="form-select js-choice" required
                                                        style="height: 6% !important;">
                                                    <option disabled selected>-- select account --</option>
                                                    @foreach($categories as $category)
                                                        <option
                                                            value="{{ $category->chart_id }}">{{ $category->chart_number }}
                                                            - {{ $category->chart_name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">ACCOUNT
                                                    CURRENCY</label>
                                                <select name="account_currency" id="account_currency"
                                                        class="form-select choices" required
                                                        style="height: 6% !important;">
                                                    <option disabled selected>-- select currency --</option>
                                                    @foreach($currencies as $currency)
                                                        <option
                                                            value="{{ $currency->currency_id }}"> {{ $currency->currency_symbol }}
                                                            - {{ $currency->currency_name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">ACCOUNT
                                                    LEDGER TYPE</label>
                                                <select class="form-select js-choice" name="type" size="1"
                                                        data-options='{"removeItemButton":true,"placeholder":true}'
                                                        style="height: 6% !important;" required>
                                                    <option disabled selected value="">-- select ledger type --</option>
                                                    <option value="1">INCOME LEDGER</option>
                                                    <option value="2">EXPENSE LEDGER</option>
                                                    <option value="3">TAXES & DUTIES LEDGER</option>
                                                    <option value="4">PAYMENT LEDGER</option>
                                                    <option value="6">PREPAID ACCOUNTS</option>
                                                    <option value="7">DEBTOR LEDGER</option>
                                                    <option value="8">CREDITOR LEDGER</option>
                                                    <option value="5">BALANCES</option>
                                                    <option value="9">CURRENT LIABILITY</option>
                                                    <option value="10">CURRENT ASSESTS</option>
                                                    <option value="11">FIXED ASSETS</option>
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">ACCOUNT
                                                    DESCRIPTION</label>
                                                <textarea type="text" name="description" class="form-control" rows="3"
                                                          placeholder="ACCOUNT DESCRIPTION"></textarea>
                                                <label> </label>
                                            </div>

                                            <div class="d-flex justify-content-center mt-2 mb-3">
                                                <button type="submit" class="btn btn-success">SAVE INCOME/EXPENSE
                                                </button>
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
                <div class="tab-pane preview-tab-pane active" role="tabpanel"
                     aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494"
                     id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>ACCOUNT #</th>
                            <th>CLIENT NAME</th>
                            <th>ACCOUNT CATEGORY</th>
                            <th>ACCOUNT TYPE</th>
                            <th>CURRENCY USED</th>
                            <th>OPENED ON</th>
                            <th>STATUS</th>
                            @if(auth()->user()->role_id == 7)
                                <th></th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($clientsAccounts as $account)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $account->client_account_number }}</td>
                                <td>{{ $account->client_account_name }}</td>
                                <td>{{ $account->chart_name }}</td>
                                <td>{{ $account->sub_account_name }}</td>
                                <td>{{ $account->currency_name }} ({{ $account->currency_symbol }})</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp($account->opening_date)->format('D, d/m/y H:i') }}</td>
                                <td>
                                    @if($account->account_status == 0)
                                        <a class="link-danger"
                                           onclick="return confirm('Are you sure you want to activate this ledger?')"
                                           href="{{ route('accounts.activateClientAccount', $account->client_account_id) }}">Activate</a>
                                    @else
                                        {!! $account->closing_date == null ? '<span class="badge text-bg-success"> ACTIVE </span>' : '<span class="badge text-bg-danger"> ACCOUNT CLOSED </span>' !!}
                                    @endif
                                </td>

                                @if(auth()->user()->role_id == 7)
                                    <td>
                                        <a href="#"
                                           class="link-info mx-2 editAccountBtn"
                                           data-bs-toggle="modal"
                                           data-bs-target="#editAccountModal"
                                           data-id="{{ $account->client_account_id }}"
                                           data-name="{{ $account->client_account_name }}"
                                           data-number="{{ $account->client_account_number }}"
                                           data-chart_id="{{ $account->chart_id }}"
                                           data-address="{{ $account->client_address }}"
                                           data-kra="{{ $account->kra_pin }}"
                                           data-currency="{{ $account->currency_id }}"
                                           data-status="{{ $account->closing_date ? 2 : 1 }}"
                                           data-type="{{ $account->type }}"
                                           data-description="{{ $account->description }}"
                                           data-route="{{ route('accounts.updateClientAccount', '__ID__') }}"
                                        >
                                            <span class="fa-regular fa-pen-to-square"></span>
                                        </a>

                                        <a class="link-danger"
                                           title="Delete income/expense ledger"
                                           onclick="return confirm('Are you sure you want to delete this account?')"
                                           href="{{ route('accounts.deleteClientAccount', $account->client_account_id) }}">
                                            <span class="fa-regular fa-trash-can"></span>
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                    <!-- Reusable Edit Modal -->
                    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel"
                         aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form id="updateClientAccountForm" method="POST"
                                      action="{{ route('accounts.updateClientAccount', $account->client_account_id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h6 class="modal-title" id="editAccountModalLabel">UPDATE ACCOUNT DETAILS</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Fields (same as before) -->
                                        <!-- Replace values dynamically using JS -->
                                        <input type="hidden" name="account_id" id="edit_account_id">

                                        <div class="mb-4">
                                            <label class="fw-bold">CLIENT NAME</label>
                                            <input type="text" list="clients" name="account_name" id="edit_account_name"
                                                   class="form-control" required>
                                            <datalist id="clients">
                                                @foreach($clients as $client)
                                                    <option value="{{ $client }}">{{ $client }}</option>
                                                @endforeach
                                            </datalist>
                                        </div>

                                        <div class="mb-4">
                                            <label class="fw-bold">ACCOUNT TYPE</label>
                                            <select name="account_category" id="edit_account_category"
                                                    class="form-select js-choice" required>
                                                <option disabled selected>-- select account --</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->chart_id }}">{{ $category->chart_number }} - {{ $category->chart_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="fw-bold">CLIENT ADDRESS</label>
                                            <input type="text" name="client_address" id="edit_client_address"
                                                   class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label class="fw-bold">ACCOUNT KRA PIN</label>
                                            <input type="text" name="kraPin" id="edit_kra_pin" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label class="fw-bold">ACCOUNT PAID IN</label>
                                            <select name="account_currency" id="edit_account_currency"
                                                    class="form-select">
                                                @foreach($currencies as $currency)
                                                    <option
                                                        value="{{ $currency->currency_id }}">{{ $currency->currency_symbol }}
                                                        - {{ $currency->currency_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="fw-bold">ACCOUNT STATUS</label>
                                            <select name="account_status" id="edit_account_status" class="form-select"
                                                    required>
                                                <option value="1">ACTIVATE ACCOUNT</option>
                                                <option value="2">CLOSE ACCOUNT</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="fw-bold">LEDGER TYPE</label>
                                            <select name="type" id="edit_ledger_type" class="form-select" required>
                                                <option value="">-- select ledger type --</option>
                                                <option value="1">INCOME LEDGER</option>
                                                <option value="2">EXPENSE LEDGER</option>
                                                <option value="3">TAXES & DUTIES LEDGER</option>
                                                <option value="4">PAYMENT LEDGER</option>
                                                <option value="5">BALANCES</option>
                                                <option value="6">PREPAID ACCOUNTS</option>
                                                <option value="7">DEBTOR LEDGER</option>
                                                <option value="8">CREDITOR LEDGER</option>
                                                <option value="9">CURRENT LIABILITY</option>
                                                <option value="10">CURRENT ASSESTS</option>
                                                <option value="11">FIXED ASSETS</option>
                                            </select>
                                        </div>

                                        <div class="mb-4">
                                            <label class="fw-bold">DESCRIPTION</label>
                                            <textarea name="description" id="edit_description" rows="3"
                                                      class="form-control"></textarea>
                                        </div>

                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-success">UPDATE CLIENT ACCOUNT</button>
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
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $('#accountId').on('change', function () {
            var accountId = $(this).val();

            $.ajax({
                type: 'get',
                url: '{{ route('accounts.filterAccountsPerType') }}',
                data: {accountId},
                success: function (data) {
                    let $select = $('#subAccountId');
                    let $select1 = $('#chartAccountId');
                    $select.empty(); // clear existing options
                    $select1.empty(); // clear existing options
                    $select.append('<option value="">-- Select Group Ledger --</option>');
                    $select1.append('<option value="">-- Select Sub Group Ledger --</option>');

                    $.each(data, function (key, value) {
                        $select.append('<option value="' + value.sub_account_id + '">' + value.sub_account_name + '</option>');
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching sub accounts:', error);
                }
            })

        });

        $('#subAccountId').on('change', function () {
            var subAccountId = $(this).val();

            console.log(subAccountId)

            $.ajax({
                type: 'get',
                url: '{{ route('accounts.filterChartOfAccounts') }}',
                data: {subAccountId},
                success: function (data) {
                    console.log(data)
                    let $select = $('#chartAccountId');
                    $select.empty(); // clear existing options
                    $select.append('<option value="">-- Select Sub Group Ledger --</option>');

                    $.each(data, function (key, value) {
                        $select.append('<option value="' + value.chart_id + '">' + value.chart_name + '</option>');
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching sub accounts:', error);
                }
            })

        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.editAccountBtn');
        const form = document.getElementById('updateClientAccountForm');

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const rawRoute = this.dataset.route;
                const finalRoute = rawRoute.replace('__ID__', id);
                form.action = finalRoute;

                document.getElementById('edit_account_id').value = id;
                document.getElementById('edit_account_name').value = this.dataset.name;
                document.getElementById('edit_account_category').value = this.dataset.chart_id;
                document.getElementById('edit_client_address').value = this.dataset.address;
                document.getElementById('edit_kra_pin').value = this.dataset.kra;
                document.getElementById('edit_account_currency').value = this.dataset.currency;
                document.getElementById('edit_account_status').value = this.dataset.status;
                document.getElementById('edit_ledger_type').value = this.dataset.type;
                document.getElementById('edit_description').value = this.dataset.description;
            });
        });
    });
</script>
