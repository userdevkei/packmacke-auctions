@extends('account::layouts.default')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">{{ $bank->client_account_name }} ({{ $bank->currency_symbol }}) Reconciled Statement</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fa fa-cloud-download-alt" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Report</span></a>
                    </div>
                </div>
                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <span class="h5 mb-1" id="staticBackdropLabel">SELECT MONTH</span>
                                </div>
                                <div class="p-4">
                                    <form method="post" action="{{ route('accounts.downloadReconciledBankStatement', $bank->client_account_id) }}" target="_blank">
                                        @csrf
                                        <div class="mb-4">
                                            <label>MONTHS</label>
                                            <select class="form-select js-choice" id="basic-example" name="month" data-options='{"shouldSort":"false"}'>
                                                <option selected disabled value="">-- select month --</option>
                                                @foreach($months as $month)
                                                    <option value="{{ $month }}">{{ $month }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label>REPORT FORMAT</label>
                                            <select class="form-select js-choice" name="report">
                                                <option selected disabled value="">-- select format --</option>
                                                <option value="1">PDF Report</option>
                                                <option value="2">Excel Report</option>
                                            </select>
                                        </div>
                                        <div class="d-flex justify-content-center">
                                            <button type="submit" class="btn btn-md col-md-7 btn-secondary">Download Report</button>
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
                            <th>VOUCHER NUMBER</th>
                            <th>CLIENT NAME</th>
                            <th>AMOUNT RECEIVED</th>
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
                                <td>{{ number_format($statement->amount_received, 2) }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp($statement->date_received)->format('d-m-Y') }}</td>
                                <td>
                                    @if(auth()->user()->role_id == 7)
                                    <input type="date" class="form-control form-control-sm date-input" value="{{ $statement->bank_date ? Carbon\Carbon::createFromTimestamp($statement->bank_date)->format('Y-m-d') : '' }}" data-id="{{ $statement->transaction_id }}">
                                    @else
                                    {{ Carbon\Carbon::createFromTimestamp($statement->bank_date)->format('Y-m-d') }}
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
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });

    $(document).on('change', '.date-input', function () {
        const dateValue = $(this).val(); // Get the selected date
        const recordId = $(this).data('id'); // Get the record ID from the data attribute

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
