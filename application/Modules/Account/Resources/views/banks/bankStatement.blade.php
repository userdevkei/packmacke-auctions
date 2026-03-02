@extends('account::layouts.default')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">{{ $bank->client_account_name }} ({{ $bank->currency_symbol }}) Unreconciled Statement</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm mx-2" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Report</span></a>
                        <a class="btn btn-falcon-info btn-sm" onclick="return confirm('Are you sure you want to reconcile updated statement?')" href="{{ route('accounts.reconcileBankStatement') }}"><span class="fas fa-save" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Confirm</span></a>
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
                                    <h5 class="mb-1" id="staticBackdropLabel">EXPORT UNRECONCILED TRANSACTION</h5>
                                </div>
                                <div class="p-4">
                                    <form method="POST" action="{{ route('accounts.exportUnreconciledTransactions', $bank->client_account_id) }}">
                                        @csrf
                                        <div class="row row-cols-sm-2 g-1">
                                            <div class="mb-2 date-input-container">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DATE FROM</label>
                                                <input type="date" id="monthAgo" value="" name="from" class="form-control date-input" style="height: 62% !important;">
                                            </div>

                                            <div class="mb-2 date-input-container">
                                                <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DATE TO</label>
                                                <input type="date"  id="todayDate" name="to" class="form-control date-input" style="height: 62% !important;">
                                            </div>
                                        </div>

                                        <div class="mt-4 d-flex justify-content-center">
                                            <button type="submit" class="btn btn-success col-7">DOWNLOAD REPORT</button>
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
                            <th>REFERENCE NUMBER</th>
                            <th>CLIENT NAME</th>
                            <th>DEBIT</th>
                            <th>CREDIT</th>
                            <th>DATE RECEIVED</th>
                            <th>BANK DATE</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($statements as $statement)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $statement->invoice_number }}</td>
                                <td>{{ strtoupper($statement->client_account_name) }}</td>
                                <td>{{ $statement->type == 'DEBIT' ? number_format($statement->amount_received, 2) : '0.00' }}</td>
                                <td>{{ $statement->type == 'CREDIT' ? number_format($statement->amount_received, 2) : '0.00' }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp($statement->date_received)->format('d-m-Y') }}</td>
                                <td><input type="date" class="form-control form-control-sm date-input" value="{{ $statement->bank_date ? Carbon\Carbon::createFromTimestamp($statement->bank_date)->format('Y-m-d') : '' }}" data-id="{{ $statement->transaction_id }}"></td>
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

    $(document).on('change', '.date-input', function () {
        const dateValue = $(this).val() ?? null; // Get the selected date
        const recordId = $(this).data('id'); // Get the record ID from the data attribute

        console.log(dateValue)

        // Send the updated date to the server
        $.ajax({
            url: '{{ route('accounts.updateBankDate') }}', // Update this to match your Laravel route
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                id: recordId, // Record ID
                date: dateValue // Selected date
            },
            success: function (response) {
                // Handle success (e.g., show a success message)
                // alert('Date updated successfully!');
            },
            error: function (xhr, status, error) {
                // Handle error (e.g., show an error message)
                alert('Failed to update date: ' + error);
            }
        });
    });
</script>
