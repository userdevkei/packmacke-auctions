@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Opening Balances</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @if(auth()->user()->role_id == 7)
                            <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Period</span></a>
                        @endif
                    </div>
                </div>
                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="staticBackdropLabel">ADD NEW CURRENCY</h5>
                                </div>
                                <div class="p-4">
                                    <div class="row">
                                        <form method="POST" action="{{ route('accounts.storeOpeningBalance') }}">
                                            @csrf
                                            <div class="mb-4">
                                                <label>FINANCIAL YEAR </label>
                                                <select class="form-control" name="financialYear" required style="height: 11% !important;">
                                                    <option selected disabled value="">-- select financial year --</option>
                                                    @foreach($years as $year)
                                                        <option value="{{ $year['financial_year_id'] }}">{{ $year['financial_year'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label> CLIENT ACCOUNT</label>
                                                <select class="form-control js-choice" name="clientId" required style="height: 11% !important;">
                                                    <option selected disabled value="">-- select ledger --</option>
                                                    @foreach($accounts as $account)
                                                        <option value="{{ $account->client_account_id }}">{{ $account->client_account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label> TRANSACTION TYPE</label>
                                                <select class="form-control" name="type" required style="height: 11% !important;">
                                                    <option selected disabled value="">-- select transaction type --</option>
                                                    <option value="1">DEBIT</option>
                                                    <option value="2">CREDIT</option>
                                                </select>
                                            </div>

                                            <div class="mb-4">
                                                <label>AMOUNT</label>
                                                <input type="number" class="form-control" min="0.01" step="0.01" name="amount" required style="height: 11% !important;">
                                            </div>

                                            <div class="d-flex justify-content-center mt-2 mb-4">
                                                <button type="submit" class="btn col-md-8 btn-success">SAVE OPENING BALANCE</button>
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
                            <th>Financial Year</th>
                            <th>Client Name</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            @if(auth()->user()->role_id == 7)
                            <th>Action</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($balances as $balance)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($balance->year_starting)->format('Y') }}</td>
                                <td>{{ $balance->client_account_name }}</td>
                                <td>{{ number_format($balance->debit, 2) }}</td>
                                <td>{{ number_format($balance->credit, 2) }}</td>
                                @if(auth()->user()->role_id == 7)
                                <td>
                                    <a class="link-danger" onclick="return confirm('Are you sure you want to delete this entry?')" href="{{ route('accounts.deleteOpeningBalance', $balance->opening_balance_id) }}"><i class="fa fa-trash-alt"></i> delete </a>
                                </td>
                                @endif
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
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
