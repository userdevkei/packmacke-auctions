@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Search Results For <span
                            class="fw-bold text-success">{{ $searchTerm }}</span></h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel"
                     aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494"
                     id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    @php
                        $tabs = [
                            'ledgers' => count($ledgers),
                            'sales' => count($sales),
                            'purchases' => count($purchases),
                            'receipts' => count($receipts),
                            'payments' => count($payments),
                        ];
                        $firstActiveTab = collect($tabs)->filter(fn($v) => $v > 0)->keys()->first(); // 'ledgers', 'sales', etc.
                    @endphp


                    @if(array_sum($tabs) > 0)
                        {{-- Nav tabs --}}
                        <ul class="nav nav-tabs mb-3" id="searchTabs" role="tablist">
                            @foreach($tabs as $key => $count)
                                @if($count > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if($firstActiveTab === $key) active @endif"
                                                id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}"
                                                type="button" role="tab" aria-controls="{{ $key }}"
                                                aria-selected="{{ $firstActiveTab === $key ? 'true' : 'false' }}">
                                            {{ ucfirst($key) }} ({{ $count }})
                                        </button>
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                        <div class="tab-content" id="searchTabsContent">
                            @if(count($ledgers))
                                <div class="tab-pane fade @if($firstActiveTab === 'ledgers') show active @endif"
                                     id="ledgers" role="tabpanel" aria-labelledby="ledgers-tab">
                                    <table class="table mb-0 table-striped table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Account Number</th>
                                            <th>Account Name</th>
                                            <th>Sub Group</th>
                                            <th>Financial Year</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($ledgers as $ledger)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $ledger->client_account_number }}</td>
                                                <td>{{ $ledger->client_account_name }}</td>
                                                <td>{{ $ledger->chart_name }}</td>
                                                <td>{{ $ledger->financial_year }}</td>
                                                <td>
                                                    <a class="link link-dark" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="VIew Ledger Statement"
                                                       href="{{ route('accounts.viewLedgerStatement', base64_encode($ledger->client_account_id.':'.$ledger->financial_year_id)) }}"><span
                                                            class="fas fa-folder-open"></span> </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($sales))
                                <div class="tab-pane fade @if($firstActiveTab === 'sales') show active @endif"
                                     id="sales" role="tabpanel" aria-labelledby="sales-tab">
                                    <table class="table mb-0 table-striped table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice Number</th>
                                            <th>Client Name</th>
                                            <th>Financial Year</th>
                                            <th>Invoice Amount</th>
                                            <th>Invoice Date</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($sales as $invoice)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->client_account_name }}</td>
                                                <td>{{ $invoice->financial_year }}</td>
                                                <td>{{ number_format($invoice->amount_due, 2) }}</td>
                                                <td>{{ \Carbon\Carbon::createFromTimestamp($invoice->date_invoiced)->format('d-m-Y') }}</td>
                                                <td>
                                                    <a class="link text-primary mx-2" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="View Invoice"
                                                       href="{{ route('accounts.viewInvoice', $invoice->invoice_id) }}">
                                                        <span class="fas fa-folder-open"></span>
                                                    </a>
                                                    <a class="link text-dark mx-2" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="Download Invoice"
                                                       href="{{ route('accounts.downloadInvoice', $invoice->invoice_id) }}"
                                                       target="_blank">
                                                        <span class="fa-solid fa-file-download"></span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($purchases))
                                <div class="tab-pane fade @if($firstActiveTab === 'purchases') show active @endif"
                                     id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                                    <table class="table mb-0 table-striped table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Voucher Number</th>
                                            <th>Invoice Number</th>
                                            <th>Supplier Name</th>
                                            <th>Financial Year</th>
                                            <th>Invoice Amount</th>
                                            <th>Invoice Date</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($purchases as $invoice)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $invoice->voucher_number }}</td>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->client_account_name }}</td>
                                                <td>{{ $invoice->financial_year }}</td>
                                                <td>{{ number_format($invoice->amount_due, 2) }}</td>
                                                <td>{{ \Carbon\Carbon::createFromTimestamp($invoice->date_invoiced)->format('d-m-Y') }}</td>
                                                <td>
                                                    <a class="link text-secondary mx-2" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="View invoice"
                                                       href="{{ route('accounts.viewPurchaseInvoice', $invoice->purchase_id) }}">
                                                        <span class="fas fa-folder-open"></span>
                                                    </a>
                                                    <a class="link text-dark mx-2" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="Download Invoice"
                                                       href="{{ route('accounts.downloadPurchaseVoucher', $invoice->purchase_id) }}"
                                                       target="_blank">
                                                        <span class="fa-solid fa-file-download"></span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($receipts))
                                <div class="tab-pane fade @if($firstActiveTab === 'receipts') show active @endif"
                                     id="receipts" role="tabpanel" aria-labelledby="receipts-tab">
                                    <table class="table mb-0 table-striped table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice Number</th>
                                            <th>Supplier Name</th>
                                            <th>Bank Account</th>
                                            <th>Financial Year</th>
                                            <th>Amount Received</th>
                                            <th>Date Received</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($receipts as $receipt)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $receipt->invoice_number }}</td>
                                                <td>{{ $receipt->clientName }}</td>
                                                <td>{{ $receipt->bankName }}</td>
                                                <td>{{ $receipt->financial_year }}</td>
                                                <td>{{ number_format($receipt->amount_received, 2) }}</td>
                                                <td>{{ Carbon\Carbon::createFromTimestamp($receipt->date_received)->format('d-m-Y') }}</td>
                                                <td>
                                                    <a class="link text-success" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="Download Payment Voucher"
                                                       href="{{ route('accounts.downloadPurchaseReceipt', $receipt->transaction_id) }}"
                                                       target="_blank"> <span class="fa-solid fa-file-download"></span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if(count($payments))
                                <div class="tab-pane fade @if($firstActiveTab === 'payments') show active @endif"
                                     id="payments" role="tabpanel" aria-labelledby="payments-tab">
                                    <table class="table mb-0 table-striped table-bordered fs-sm datatable">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice Number</th>
                                            <th>Supplier Name</th>
                                            <th>Bank Account</th>
                                            <th>Financial Year</th>
                                            <th>Amount Received</th>
                                            <th>Date Received</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($payments as $payment)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $payment->invoice_number }}</td>
                                                <td>{{ $payment->clientName }}</td>
                                                <td>{{ $payment->bankName }}</td>
                                                <td>{{ $payment->financial_year }}</td>
                                                <td>{{ number_format($payment->amount_received, 2) }}</td>
                                                <td>{{ Carbon\Carbon::createFromTimestamp($payment->date_received)->format('d-m-Y') }}</td>
                                                <td>
                                                    <a class="link text-success" data-bs-toggle="tooltip"
                                                       data-bs-placement="left" title="Download Payment Voucher"
                                                       href="{{ route('accounts.downloadPaymentReceipt', $payment->payment_id) }}"
                                                       target="_blank"> <span class="fa-solid fa-file-download"></span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-info">
                            No results found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

            {{-- JS --}}
            <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
            <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
            <script>
                $(document).ready(function () {
                    $('.datatable').DataTable({
                        pageLength: 50
                    });
                });
            </script>
@endsection
