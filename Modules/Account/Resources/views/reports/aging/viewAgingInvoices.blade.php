@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">{{ $invoices[0]->client_name }} Aging Invoices Analysis </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Report</span></a>
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
                                    <h5 class="mb-1" id="staticBackdropLabel">CREATE A CUSTOM REPORT</h5>
                                </div>
                                <div class="p-4">

                                    <form method="POST" action="{{ route('accounts.downloadAccountAgingReport', base64_encode($invoices[0]->client_id.':'. $id)) }}">
                                        @csrf
                                        <div class="row row-cols-sm-1 g-2">
                                            <div class="mb-2">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">REPORT PERIOD</label>
                                                <select name="period" class="form-select  js-choice">
                                                    <option value="" selected>-- select report period --</option>
                                                    @foreach($invoices->groupBy('aging_category') as $period => $periods)
                                                        <option value="{{ $period }}">{{ $period }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold">REPORT FILTER</label>
                                                <select class="form-control js-choice" name="reportFilter">
                                                    <option value="">-- select report type --</option>
                                                    <option value="1">Fully Paid</option>
                                                    <option value="2">Partially Paid</option>
                                                    <option value="3">Not Paid</option>
                                                    <option value="4">Pending Payment</option>
                                                </select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">REPORT FORMAT</label>
                                                <select name="reportType" class="form-select js-choice">
                                                    <option value="" selected>-- select report format --</option>
                                                    <option value="1">PDF DOCUMENT</option>
                                                    <option value="2">EXCEL DOCUMENT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center mt-1">
                                            <button type="submit" class="btn btn-success col-8">DOWNLOAD REPORT </button>
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
                            <th>INVOICE DATE</th>
                            <th>INVOICE NUMBER</th>
                            <th>INVOICE AMOUNT</th>
                            <th>AMOUNT SETTLED</th>
                            <th>OUTSTANDING BAL</th>
                            <th>AGING DATE</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $totalInvoice = 0; $totalPayment = 0; $totalOutstanding = 0; @endphp
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</td>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td class="text-end">{{ number_format($invoice->amount_due, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->total_payments, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->outstanding_balance, 2) }}</td>
                                    <td>{{ $invoice->aging_category }}</td>
                                </tr>
                                @php $totalInvoice += $invoice->amount_due; $totalPayment += $invoice->total_payments; $totalOutstanding += $invoice->outstanding_balance; @endphp
                            @endforeach
                        </tbody>
                        <tr>
                            <td colspan="3" class="text-center fw-bold">TOTALS </td>
                            <td class="text-end fw-bold fst-italic">{{ $invoice->currency_symbol }} {{ number_format($totalInvoice, 2) }}</td>
                            <td class="text-end fw-bold fst-italic">{{ $invoice->currency_symbol }} {{ number_format($totalPayment, 2) }}</td>
                            <td class="text-end fw-bold fst-italic">{{ $invoice->currency_symbol }} {{ number_format($totalOutstanding, 2) }}</td>
                            <td></td>
                        </tr>
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
