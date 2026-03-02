@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Day Book Reports </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a href="{{ route('accounts.exportDayBook', ['start_date' => request('start_date', now()->format('Y-m-d')), 'end_date' => request('end_date', request('start_date')), 'type' => request('type', null )]) }}" class="btn btn-falcon-info btn-sm"><span class="fas fa-file-excel" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Export Excel </span></a>
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-filter" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Filter Report</span></a>
                    </div>
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
                                <h5 class="mb-1" id="staticBackdropLabel">Filter DayBook Report</h5>
                            </div>
                            <div class="p-4">
                                <div class="row">
                                    <form method="POST" action="{{ route('accounts.dayBook') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">Start Date</label>
                                            <select name="type" class="form-select">
                                                <option selected value="">-- select transaction type -- </option>
                                                <option value="Sales">Sales</option>
                                                <option value="Purchases">Purchases</option>
                                                <option value="Receipt">Receipts</option>
                                                <option value="Payment">Payments</option>
                                                <option value="Journal Entry">Journal Entry</option>
                                                <option value="Petty Cash">Petty Cash</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">Start Date</label>
                                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date', $startDate->format('Y-m-d')) }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">End Date</label>
                                            <input type="date" id="end_date" name="end_date" class="form-control" {{-- value="{{ request('end_date', $endDate->format('Y-m-d')) }}"--}} disabled>
                                        </div>

                                        <div class="d-flex justify-content-center mt-2 mb-3">
                                            <button type="submit" class="btn btn-success">Filter Report</button>
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
                            <th>Txn Date</th>
                            <th>Txn Reference</th>
                            <th>Txn Type</th>
                            <th>Txn Account</th>
                            <th>Description</th>
                            <th>Dr Amount</th>
                            <th>Cr Amount</th>
                            <th>Initiator</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($daybook as $transaction)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td>{{ $transaction->transaction_date }}</td>
                                <td>{{ $transaction->ref_number }}</td>
                                <td>{{ $transaction->transaction_type }}</td>
                                <td>{{ $transaction->ledger_name }}</td>
                                <td style="white-space: normal; word-wrap: break-word; word-break: break-word;">{{ $transaction->description }}</td>
                                <td>{{ number_format($transaction->debit, 2) }}</td>
                                <td>{{ number_format($transaction->credit, 2) }}</td>
                                <td>{{ ucwords(strtolower($transaction->user_name)) }}</td>
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

    document.addEventListener("DOMContentLoaded", function () {
            const startDate = document.getElementById("start_date");
            const endDate = document.getElementById("end_date");

            startDate.addEventListener("change", function () {
            if (startDate.value) {
            endDate.removeAttribute("disabled");
            endDate.setAttribute("min", startDate.value);
            } else {
                endDate.setAttribute("disabled", "true");
                endDate.value = "";
            }
        });
    });
</script>
