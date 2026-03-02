@extends('account::layouts.default')
<style>
    .account-type-header {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .bg-soft-assets {
        background-color: rgba(66, 180, 92, 0.1) !important;
        color: #0df342;
    }

    .bg-soft-liabilities {
        background-color: rgba(218, 88, 100, 0.1) !important;
        color: #f11f33;
    }

    .bg-soft-equity {
        background-color: rgba(23, 162, 184, 0.1) !important;
        color: #09d3f3;
    }

    .bg-soft-revenue {
        background-color: rgba(29, 142, 234, 0.1) !important;
        color: #0970f6;
    }
    .bg-soft-income {
        background-color: rgba(108, 117, 125, 0.1) !important;
        color: #6c757d;
    }

    .bg-soft-expenses {
        background-color: rgba(255, 193, 7, 0.1) !important;
        color: #ffc107;
    }

    .main-row {
        cursor: pointer;
    }

    .main-row:hover {
        background-color: #f8f9fa !important;
    }

    .ledger-details {
        display: none;
    }

    .ledger-details.active {
        display: table-row;
    }

    .toggle-icon.rotated {
        transform: rotate(90deg);
    }

    .text-debit {
        color: #28a745;
    }

    .text-credit {
        color: #dc3545;
    }

    .hover-highlight:hover {
        background-color: #f1f8ff !important;
    }

    .account-subtotal {
        border-top: 2px solid #dee2e6 !important;
    }

    .grand-total {
        border-top: 2px solid #212529 !important;
        border-bottom: 2px solid #212529 !important;
    }

    .truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 300px;
        display: inline-block;
    }

    @media print {
        .account-type-header {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ledger-details {
            display: table-row !important;
        }
        .fw-bold {
            font-size: 90% !important;
        }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0"><i class="fas fa-balance-scale me-2"></i> Trial Balance Report ({{ Carbon\Carbon::parse($financial->year_starting)->format('Y') }}) </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <div>
                            <a class="btn btn-sm btn-success me-2" href="{{ route('accounts.downloadTrialBalance', $financial->financial_year_id) }}">
                                <i class="fas fa-file-excel me-1"></i> Export to Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered " id="datatable">
                    <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 5%;">#</th>
                        <th style="width: 45%;">Account</th>
                        <th class="text-end" style="width: 25%;">Debit (KES)</th>
                        <th class="text-end" style="width: 25%;">Credit (KES)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $totalDebits = 0;
                        $totalCredits = 0;
                    @endphp

                    @foreach($orderedAccounts as $account)
                        <!-- Account Type Header Row -->
                        <tr class="account-type-header bg-soft-{{ strtolower($account['account_name']) }}">
                            <td colspan="5" class="fw-bold text-uppercase">
                                <i class="fas
                                    @if($account['account_name'] === 'ASSETS') fa-wallet
                                    @elseif($account['account_name'] === 'LIABILITIES') fa-hand-holding-usd
                                    @elseif($account['account_name'] === 'EQUITY') fa-landmark
                                    @elseif($account['account_name'] === 'REVENUE') fa-money-bill-wave
                                    @elseif($account['account_name'] === 'EXPENSES') fa-receipt
                                    @endif me-2"></i>
                                {{ $account['account_name'] }}
                            </td>
                        </tr>

                        @foreach($account['charts'] as $index => $chart)
                            @php
                                $balance = $chart['balance'];
                                $debit = $balance >= 0 ? $balance : 0;
                                $credit = $balance < 0 ? abs($balance) : 0;

                                $totalDebits += $debit;
                                $totalCredits += $credit;
                            @endphp

                                <!-- Main Chart Row (Clickable) -->
                            <tr class="main-row" onclick="toggleRow('{{ $account['account_name'] }}-{{ $index }}')">
                                <td class="text-center">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-caret-right me-2 toggle-icon" id="icon-{{ $account['account_name'] }}-{{ $index }}"></i>
                                        <span class="truncate">{{ ucwords(strtolower($chart['chart_name'])) }}</span>
                                        <span class="badge bg-light text-dark ms-2">{{ $chart['chart_number'] }}</span>
                                    </div>
                                </td>
                                <td class="text-end text-debit fw-bold">{{ number_format($debit, 2) }}</td>
                                <td class="text-end text-credit fw-bold">{{ number_format($credit, 2) }}</td>
                            </tr>

                            <!-- Ledger Details Row -->
                            <tr id="chart-{{ $account['account_name'] }}-{{ $index }}" class="ledger-details">
                                <td colspan="5" class="p-0 border-0">
                                    <div class="p-2 bg-light">
                                        <table class="table table-sm table-responsive table-borderless mb-0 datatable table-striped fs-sm--1">
                                            <thead>
                                            <tr class="text-muted small">
                                                <th class="text-center" style="width: 5%;">#</th>
                                                <th style="width: 60%;">Account Name</th>
                                                <th class="text-center" style="width: 5%;">Currency</th>
                                                <th class="text-end" style="width: 10%;">Debit</th>
                                                <th class="text-end" style="width: 10%;">Credit</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($chart['ledgers'] as $ledger)
                                                <tr onclick="window.location.href='{{ route('accounts.viewLedgerStatement', base64_encode($ledger['client_account_id'].':'.$fy)) }}'"
                                                    class="hover-highlight">
                                                    <td class="text-center">{{ $loop->iteration }}</td>
                                                    <td class="text-primary" nowrap>
                                                        <i class="far fa-user-circle me-1"></i>
                                                        {{ ucwords(strtolower($ledger['client_account_name'])) }}
                                                    </td>
                                                    <td class="text-center">{{ $ledger['currency_symbol'] }}</td>
                                                    <td class="text-end text-debit">{{ number_format($ledger['debit'], 2) }}</td>
                                                    <td class="text-end text-credit">{{ number_format($ledger['credit'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        @php
                            $accountSubtotal = $account['debit'] - $account['credit'];
                        @endphp
                        <tr class="account-subtotal">
                            <td colspan="2" class="text-end fw-bold">Subtotal</td>
                            <td class="text-end text-debit fw-bold">{{ $accountSubtotal >= 0 ? number_format($accountSubtotal, 2) : '0.00' }}</td>
                            <td class="text-end text-credit fw-bold">{{ $accountSubtotal < 0 ? number_format(abs($accountSubtotal), 2) : '0.00' }}</td>
                        </tr>
                    @endforeach

                    <!-- Grand Total Row -->
                    <tr class="grand-total bg-light">
                        <td colspan="2" class="text-end fw-bold">GRAND TOTAL (KES)</td>
                        <td class="text-end text-debit fw-bold">{{ number_format($totalDebits, 2) }}</td>
                        <td class="text-end text-credit fw-bold">{{ number_format($totalCredits, 2) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function () {
        $('.datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 25
        });

    });

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all ledger details as collapsed
        document.querySelectorAll('.ledger-details').forEach(row => {
            row.classList.remove('active');
        });

        // Add click event to all main rows
        document.querySelectorAll('.main-row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Get the index from the row's onclick attribute
                const index = this.getAttribute('onclick').match(/toggleRow\('(.*?)'\)/)[1];
                const detailsRow = document.getElementById(`chart-${index}`);
                const icon = document.getElementById(`icon-${index}`);

                // Toggle visibility
                detailsRow.classList.toggle('active');
                icon.classList.toggle('rotated');

                // Smooth scroll to keep the expanded content in view
                if (detailsRow.classList.contains('active')) {
                    detailsRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    });

    // Enhanced Export to Excel Function
    function exportToExcel() {
        const wb = XLSX.utils.book_new();
        const accounts = @json($orderedAccounts);
        let exportData = [];

        // Add title and date
        exportData.push(['Trial Balance Report']);
        exportData.push(['Generated on: ' + new Date().toLocaleString()]);
        exportData.push(['']); // Empty row

        // Helper function to capitalize each word
        function capitalize(str) {
            return str.replace(/\b\w/g, char => char.toUpperCase());
        }

        // Process each account type
        accounts.forEach(account => {
            // Add account type header
            exportData.push([account.account_name, '', '', '', '']);

            // Add headers for charts
            exportData.push(['Chart Number', 'Chart Name', 'Debit', 'Credit', 'Balance']);

            // Process each chart
            account.charts.forEach(chart => {
                // Add chart row
                exportData.push([
                    chart.chart_number || '',
                    capitalize(chart.chart_name),
                    chart.debit,
                    chart.credit,
                    account.account_name === 'ASSETS' ? chart.debit - chart.credit : chart.credit - chart.debit
                ]);

                // Add ledger rows if they exist
                if (chart.ledgers && chart.ledgers.length > 0) {
                    exportData.push(['Client Accounts', 'Currency', 'Debit', 'Credit', 'Balance']);

                    chart.ledgers.forEach(ledger => {
                        const balance = account.account_name === 'ASSETS'
                            ? ledger.debit - ledger.credit
                            : ledger.credit - ledger.debit;

                        exportData.push([
                            '- ' + capitalize(ledger.client_account_name),
                            ledger.currency_symbol,
                            ledger.debit,
                            ledger.credit,
                            balance
                        ]);
                    });
                }

                // Add empty row between charts
                exportData.push(['', '', '', '', '']);
            });

            // Add account subtotal
            exportData.push([
                'Subtotal',
                '',
                account.debit,
                account.credit,
                account.account_name === 'ASSETS' ? account.debit - account.credit : account.credit - account.debit
            ]);

            // Add separator between account types
            exportData.push(['', '', '', '', '']);
        });

        // Add grand totals
        const totalDebits = {{ $totalDebits }};
        const totalCredits = {{ $totalCredits }};
        exportData.push([
            'GRAND TOTAL',
            '',
            totalDebits,
            totalCredits,
            totalDebits - totalCredits
        ]);

        // Create worksheet
        const ws = XLSX.utils.aoa_to_sheet(exportData);

        // Add styling through column widths
        ws['!cols'] = [
            {wch: 30}, // Account/Chart names
            {wch: 15}, // Chart numbers/currency
            {wch: 15}, // Debit
            {wch: 15}, // Credit
            {wch: 15}  // Balance
        ];

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, "Trial Balance");

        // Generate and download Excel file
        const date = new Date().toISOString().slice(0,10);
        XLSX.writeFile(wb, `Trial_Balance_${date}.xlsx`);
    }
</script>


