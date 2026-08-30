@extends('account::layouts.default')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    :root {
        --primary: #4361ee;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #4895ef;
        --light: #f8f9fa;
        --dark: #212529;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 24px;
        /*transition: transform 0.3s ease;*/
    }

    .card-header {
        /*background: linear-gradient(120deg, #4361ee, #3a0ca3);*/
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 16px 24px;
        font-weight: 600;
        font-size: 1.25rem;
    }

    .table-container {
        overflow-x: auto;
        border-radius: 12px;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    table {
        min-width: 1200px;
        border-collapse: separate;
        border-spacing: 0;
    }

    thead th {
        background-color: #eef2ff;
        color: var(--primary);
        font-weight: 600;
        padding: 12px 16px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    tbody td {
        padding: 12px 16px;
        vertical-align: middle;
        border-top: 1px solid #edf2f7;
    }

    .section-header {
        background-color: #f0f7ff;
        font-weight: 600;
        font-size: 1.1rem;
        color: #1e40af;
    }

    .revenue-header {
        background-color: #f0fdf4;
        color: #166534;
    }

    .expense-header {
        background-color: #fff7ed;
        color: #9a3412;
    }

    .profit-header {
        background-color: #f0f9ff;
        color: #0c4a6e;
    }

    .main-row {
        cursor: pointer;
        /*transition: background-color 0.2s;*/
    }

    .main-row:hover {
        background-color: #f8fafc !important;
    }

    .toggle-icon {
        /*transition: transform 0.3s ease;*/
        margin-right: 10px;
        color: #64748b;
    }

    .toggle-icon.rotated {
        transform: rotate(90deg);
    }

    .ledger-details {
        display: none;
    }

    .ledger-details.active {
        display: table-row;
    }

    .ledger-table {
        background-color: #f8fafc;
        /*border-radius: 8px;*/
    }

    .ledger-table tbody tr {
        cursor: pointer;
        /*transition: background-color 0.2s;*/
    }

    .ledger-table tbody tr:hover {
        background-color: #eef2ff !important;
    }

    .text-debit {
        color: #166534;
        font-weight: 500;
    }

    .text-credit {
        color: #b91c1c;
        font-weight: 500;
    }

    .profit-positive {
        color: #166534;
        background-color: #f0fdf4;
        font-weight: 600;
    }

    .profit-negative {
        color: #b91c1c;
        background-color: #fef2f2;
        font-weight: 600;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .summary-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 10px 0;
    }

    .chart-container {
        height: 300px;
        margin-top: 20px;
    }

    .btn-export {
        background: linear-gradient(120deg, #4361ee, #3a0ca3);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        /*transition: all 0.3s ease;*/
    }

    .btn-export:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .highlight-card {
        border-left: 4px solid #4361ee;
    }

    @media (max-width: 768px) {
        .table-container {
            border-radius: 8px;
        }

        .btn-export {
            width: 100%;
            margin-bottom: 10px;
        }

        .summary-card {
            margin-bottom: 20px;
        }
    }
</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0" style="font-size: 80% !important; font-weight: normal !important;">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0"> P&L Statement </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop1"><span class="fas fa-file-download" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Report</span></a>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="staticBackdrop1" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl mt-6" role="document">
                    <div class="modal-content border-0">
                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                <h5 class="mb-1" id="staticBackdropLabel">FILTER BY DATE </h5>
                            </div>
                            <div class="p-4">
                                <div class="row">
                                    <form method="POST" action="{{ route('accounts.downloadPlStatement', base64_encode($financial.':'.'1')) }}" target="_blank">
                                        <div class="row row-cols-sm-3 g-1">
                                            @csrf
                                            <div class="mb-2">
                                                <label class="fw-bold">DATE FROM</label>
                                                <input type="date" class="form-control" name="dateFrom" min="{{ $fy->year_starting }}" max="{{ $fy->year_ending }}" style="height: 67% !important;">
                                            </div>

                                            <div class="mb-2">
                                                <label class="fw-bold">DATE TO</label>
                                                <input type="date" class="form-control" name="dateTo" min="{{ $fy->year_starting }}" max="{{ $fy->year_ending }}" style="height: 67% !important;">
                                            </div>

                                            <div class="mb-2">
                                                <label class="fw-bold">REPORT FORMAT</label>
                                                <select class="form-control js-choice" name="reportFormat">
                                                    <option value="">-- select report type --</option>
                                                    <option value="1">Pdf Report</option>
                                                    <option value="2">Excel Report</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center mt-2">
                                            <button type="submit" class="btn btn-success col-md-7">DOWNLOAD REPORT</button>
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
            <div class="table-container">
                <table class="table mb-0 table-hover table-sm fs-sm" id="datatable">
                    <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 4%;">Acc. #</th>
                        <th style="width: 18%;">Account Name</th>
                        <th style="width: 6%; text-align: center !important;">Jan</th>
                        <th style="width: 6%; text-align: center !important;">Feb</th>
                        <th style="width: 6%; text-align: center !important;">Mar</th>
                        <th style="width: 6%; text-align: center !important;">Apr</th>
                        <th style="width: 6%; text-align: center !important;">May</th>
                        <th style="width: 6%; text-align: center !important;">Jun</th>
                        <th style="width: 6%; text-align: center !important;">Jul</th>
                        <th style="width: 6%; text-align: center !important;">Aug</th>
                        <th style="width: 6%; text-align: center !important;">Sep</th>
                        <th style="width: 6%; text-align: center !important;">Oct</th>
                        <th style="width: 6%; text-align: center !important;">Nov</th>
                        <th style="width: 6%; text-align: center !important;">Dec</th>
                        <th style="width: 9%; text-align: center !important;" class="bg-light">Total (KES)</th>
                    </tr>
                    </thead>

                    <?php
                    $totalRevenue = 0;
                    $totalExpense = 0;
                    $monthlyTotalsRevenue = array_fill(1, 12, 0);
                    $monthlyTotalsExpense = array_fill(1, 12, 0);
                    ?>
                        <!-- REVENUE SECTION -->
                    <tbody>
                    <tr class="section-header revenue-header mt-2">
                        <td colspan="16" class="fw-bold fs-sm">
                            <i class="fas fa-money-bill-wave me-2"></i>REVENUE
                        </td>
                    </tr>

                    @foreach(collect($revenues)->groupBy(['chart_name', 'currency_symbol']) as $chartName => $currencyGroups)
                        @foreach($currencyGroups as $currencySymbol => $revenueGroup)
                            <tr class="main-row" data-target="revenues-{{ $loop->parent->iteration }}-{{ $loop->iteration }}">
                                <td>{{ $loop->parent->iteration }}</td>
                                <td>{{ $revenueGroup[0]['chart_number'] }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-caret-right toggle-icon"
                                           id="icon-revenues-{{ $loop->parent->iteration }}-{{ $loop->iteration }}"></i>
                                        <span>{{ $chartName }} ({{ $currencySymbol }})</span>
                                    </div>
                                </td>

                                @for ($month = 1; $month <= 12; $month++)
                                        <?php
                                        $monthlySum = $revenueGroup->sum(function($revenue) use ($month) {
                                            $monthKey = strtolower(date('M', mktime(0, 0, 0, $month, 1)));
                                            return $revenue[$monthKey] ?? 0;
                                        });
                                        $monthlyTotalsRevenue[$month] += $monthlySum;
                                        ?>
                                    <td class="text-end text-debit">{{ number_format($monthlySum, 2) }}</td>
                                @endfor

                                <td class="text-end fw-bold bg-light">{{ number_format($revenueGroup->sum('total_amount_due'), 2) }}</td>
                            </tr>

                            <!-- Ledger Details -->
                            <tr id="revenues-{{ $loop->parent->iteration }}-{{ $loop->iteration }}" class="ledger-details">
                                <td colspan="16" class="p-0">
                                    <div class="p-3">
                                        <table class="table table-sm fs-sm mb-0 ledger-table">
                                            <thead>
                                            <tr>
                                                <th style="width: 2.5%;">#</th>
                                                <th style="width: 4%;">Acc #</th>
                                                <th style="width: 13%;">Ledger Name</th>
                                                @for ($month = 1; $month <= 12; $month++)
                                                    <th style="width: 5%; text-align: center !important;">{{ strtoupper(date('M', mktime(0, 0, 0, $month, 1))) }}</th>
                                                @endfor
                                                <th style="width: 6%;">Total</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($revenueGroup as $revenue)
                                                <tr onclick="window.location.href='{{ route('accounts.viewLedgerStatement', base64_encode($revenue['client_account_id'].':'.$financial)) }}'">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <a href="{{ route('accounts.viewLedgerStatement', base64_encode($revenue['client_account_id'].':'.$financial)) }}"
                                                           class="text-primary">
                                                            {{ $revenue['client_account_number'] }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('accounts.viewLedgerStatement', base64_encode($revenue['client_account_id'].':'.$financial)) }}"
                                                           class="text-primary">
                                                            {{ Str::ucfirst($revenue['ledger_name']) }}
                                                        </a>
                                                    </td>
                                                    @for ($month = 1; $month <= 12; $month++)
                                                            <?php
                                                            $monthKey = strtolower(date('M', mktime(0, 0, 0, $month, 1)));
                                                            $value = $revenue[$monthKey] ?? 0;
                                                            ?>
                                                        <td class="text-end">{{ number_format($value, 2) }}</td>
                                                    @endfor
                                                    <td class="text-end fw-bold">{{ number_format($revenue['total_amount_due'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                                <?php
                                $groupTotal = $revenueGroup->sum(function($revenue) {
                                    return $revenue['total_amount_due'] ?? 0;
                                });
                                $totalRevenue += $groupTotal;
                                ?>
                        @endforeach
                    @endforeach

                    <!-- TOTAL REVENUE -->
                    <tr class="highlight-card">
                        <td colspan="3" class="fw-bold">Total Revenue</td>
                        @for ($month = 1; $month <= 12; $month++)
                            <td class="text-end fw-bold">{{ number_format($monthlyTotalsRevenue[$month], 2) }}</td>
                        @endfor
                        <td class="text-end fw-bold bg-light">{{ number_format($totalRevenue, 2) }}</td>
                    </tr>
                    </tbody>

                    <tr colspan="16">
                        <td>.</td>
                    </tr>
                    <!-- EXPENSES SECTION -->
                    <tbody>
                    <tr class="section-header expense-header mt-2">
                        <td colspan="16" class="fw-bold">
                            <i class="fas fa-receipt me-2"></i>EXPENSES
                        </td>
                    </tr>
                    @foreach(collect($expenses)->groupBy(['chart_name', 'currency_symbol']) as $chartName => $currencyGroups)
                        @foreach($currencyGroups as $currencySymbol => $expenseGroup)
                            <tr class="main-row" data-target="expenses-{{ $loop->parent->iteration }}-{{ $loop->iteration }}">
                                <td>{{ $loop->parent->iteration }}</td>
                                <td>{{ $expenseGroup[0]['chart_number'] }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-caret-right toggle-icon"
                                           id="icon-expenses-{{ $loop->parent->iteration }}-{{ $loop->iteration }}"></i>
                                        <span>{{ ucfirst(strtolower($chartName)) }} ({{ $currencySymbol }})</span>
                                    </div>
                                </td>

                                @for ($month = 1; $month <= 12; $month++)
                                        <?php
                                        $monthlySum = collect($expenseGroup)->sum(function ($expense) use ($month) {
                                            $monthKey = strtolower(date('M', mktime(0, 0, 0, $month, 1)));
                                            return $expense[$monthKey] ?? 0;
                                        });
                                        $monthlyTotalsExpense[$month] += $monthlySum;
                                        ?>
                                    <td class="text-end text-credit">{{ number_format($monthlySum, 2) }}</td>
                                @endfor

                                <td class="text-end fw-bold bg-light">{{ number_format(collect($expenseGroup)->sum('total_amount_due'), 2) }}</td>
                            </tr>

                            <!-- Ledger Details -->
                            <tr id="expenses-{{ $loop->parent->iteration }}-{{ $loop->iteration }}" class="ledger-details">
                                <td colspan="16" class="p-0">
                                    <div class="p-3">
                                        <table class="table table-sm fs-sm mb-0 ledger-table">
                                            <thead>
                                            <tr>
                                                <th style="width: 2.5%;">#</th>
                                                <th style="width: 4%;">Acc #</th>
                                                <th style="width: 13%;">Ledger Name</th>
                                                @for ($month = 1; $month <= 12; $month++)
                                                    <th style="width: 5%; text-align: center !important;">{{ strtoupper(date('M', mktime(0, 0, 0, $month, 1))) }}</th>
                                                @endfor
                                                <th style="width: 6%;">Total</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($expenseGroup as $expense)
                                                <tr onclick="window.location.href='{{ route('accounts.viewLedgerStatement', base64_encode($expense['client_account_id'].':'.$financial)) }}'">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <a href="{{ route('accounts.viewLedgerStatement', base64_encode($expense['client_account_id'].':'.$financial)) }}"
                                                           class="text-primary">
                                                            {{ $expense['client_account_number'] }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('accounts.viewLedgerStatement', base64_encode($expense['client_account_id'].':'.$financial)) }}"
                                                           class="text-primary">
                                                            {{ Str::ucfirst(strtolower($expense['ledger_name'])) }}
                                                        </a>
                                                    </td>
                                                    @for ($month = 1; $month <= 12; $month++)
                                                            <?php
                                                            $monthKey = strtolower(date('M', mktime(0, 0, 0, $month, 1)));
                                                            $value = $expense[$monthKey] ?? 0;
                                                            ?>
                                                        <td class="text-end">{{ number_format($value, 2) }}</td>
                                                    @endfor
                                                    <td class="text-end fw-bold">{{ number_format($expense['total_amount_due'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                                <?php
                                $groupTotal = collect($expenseGroup)->sum(function ($expense) {
                                    return $expense['total_amount_due'] ?? 0;
                                });
                                $totalExpense += $groupTotal;
                                ?>
                        @endforeach
                    @endforeach
                    <!-- TOTAL EXPENSES -->
                    <tr class="highlight-card">
                        <td colspan="3" class="fw-bold">Total Expenses</td>
                        @for ($month = 1; $month <= 12; $month++)
                            <td class="text-end fw-bold">{{ number_format($monthlyTotalsExpense[$month], 2) }}</td>
                        @endfor
                        <td class="text-end fw-bold bg-light">{{ number_format($totalExpense, 2) }}</td>
                    </tr>
                    </tbody>

                    <!-- NET PROFIT/LOSS SECTION -->
                    <tbody>
                    <tr class="section-header profit-header">
                        <td colspan="16" class="fw-bold">
                            <i class="fas fa-calculator me-2"></i>NET PROFIT/LOSS
                        </td>
                    </tr>
                    <tr class="{{ ($totalRevenue - $totalExpense) >= 0 ? 'profit-positive' : 'profit-negative' }}">
                        <td colspan="3" class="fw-bold text-center">Net Profit/Loss</td>
                        @for ($month = 1; $month <= 12; $month++)
                                <?php
                                $netProfit = $monthlyTotalsRevenue[$month] - $monthlyTotalsExpense[$month];
                                ?>
                            <td class="text-end fw-bold {{ $netProfit < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($netProfit, 2) }}
                            </td>
                        @endfor
                        <td class="text-end fw-bold bg-light {{ ($totalRevenue - $totalExpense) < 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($totalRevenue - $totalExpense, 2) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    // Fix for expandable sections
    $(document).ready(function() {
        // Toggle ledger details when clicking on main rows
        $('.main-row').click(function() {
            const targetId = $(this).data('target');
            const detailsRow = $('#' + targetId);
            const icon = $('#icon-' + targetId);

            detailsRow.toggleClass('active');
            icon.toggleClass('rotated');

            // Smooth scroll to keep the expanded content in view
            if (detailsRow.hasClass('active')) {
                $('html, body').animate({
                    scrollTop: detailsRow.offset().top - 100
                }, 300);
            }
        });

        // Expand All toggle
        $('#expandToggle').change(function() {
            const isChecked = $(this).is(':checked');
            $('.ledger-details').toggleClass('active', isChecked);
            $('.toggle-icon').toggleClass('rotated', isChecked);
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Initialize performance chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Revenue',
                        data: <?php echo json_encode(array_values($monthlyTotalsRevenue)); ?>,
                        borderColor: '#166534',
                        backgroundColor: 'rgba(22, 101, 52, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode(array_values($monthlyTotalsExpense)); ?>,
                        borderColor: '#b91c1c',
                        backgroundColor: 'rgba(185, 28, 28, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Profit/Loss',
                        data: <?php
                              $profitData = [];
                              for ($i = 1; $i <= 12; $i++) {
                                  $profitData[] = $monthlyTotalsRevenue[$i] - $monthlyTotalsExpense[$i];
                              }
                              echo json_encode($profitData);
                              ?>,
                        borderColor: '#0c4a6e',
                        backgroundColor: 'rgba(12, 74, 110, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': KES ' + context.parsed.y.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            }
                        }
                    }
                }
            }
        });
    });

    // Export to Excel Function
    function exportToExcel() {
        const wb = XLSX.utils.book_new();
        const table = document.getElementById('datatable');

        // Clone table to modify for export
        const cloneTable = table.cloneNode(true);

        // Remove hidden ledger details from export
        $(cloneTable).find('.ledger-details').remove();

        // Remove icons from export
        $(cloneTable).find('.toggle-icon').remove();

        const ws = XLSX.utils.table_to_sheet(cloneTable);

        // Add styling through column widths
        ws['!cols'] = [
            {wch: 5},  // #
            {wch: 8},  // Acc #
            {wch: 25}, // Account Name
            ...Array(12).fill({wch: 10}),  // Months
            {wch: 15}  // Total
        ];

        XLSX.utils.book_append_sheet(wb, ws, "Financial Report");

        const date = new Date().toISOString().slice(0,10);
        XLSX.writeFile(wb, `Financial_Report_${date}.xlsx`);
    }
</script>
