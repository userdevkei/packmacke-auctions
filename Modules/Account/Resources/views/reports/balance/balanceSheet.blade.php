@php use Illuminate\Support\Str; @endphp
@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Balance Sheet ({{ Carbon\Carbon::parse($fy->year_starting)->format('Y') }} FY)</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-sm btn-falcon-primary" href="{{ route('accounts.exportBalanceSheet', $fy->financial_year_id) }}"><i class="fas fa-file-excel"></i> Export</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                   @php
                       // Initialize totals with proper error handling
                       $totalAssets = 0;
                       $totalLiabilities = 0;
                       $totalEquity = 0;
                       $sectionErrors = [];

                       // Debug mode flag
                       $debugMode = config('app.debug') || request()->has('debug');
                   @endphp

                   @foreach ($balancesheet as $index => $section)
                       @php
                           $name = strtoupper(trim($section['account_name'] ?? ''));
                           $validSections = ['ASSETS', 'LIABILITIES', 'EQUITY'];

                           if (!in_array($name, $validSections)) {
        //                       $sectionErrors[] = "Invalid section name: {$name}";
                               continue;
                           }

                           $groupCharts = $section['charts'] ?? [];
                           $groupBalance = $section['balance'] ?? 0;
                           $calculatedGroupBalance = 0;

                           // Calculate section totals
                           foreach ($groupCharts as $chart) {
                               $accounts = $chart['ledgers'] ?? [];
                               if ($name === 'ASSETS') {
                                   $calculatedGroupBalance += collect($accounts)->sum(fn($a) => ($a['debit'] ?? 0) - ($a['credit'] ?? 0));
                               } else {
                                   $calculatedGroupBalance += collect($accounts)->sum(fn($a) => ($a['credit'] ?? 0) - ($a['debit'] ?? 0));
                               }
                           }

                           // Check for calculation discrepancies
                           if (abs($groupBalance - $calculatedGroupBalance) > 0.01) {
                               $sectionErrors[] = "{$name} section balance mismatch: Stored {$groupBalance} vs Calculated {$calculatedGroupBalance}";
                           }

                           // Accumulate to main totals
                           switch ($name) {
                               case 'ASSETS':
                                   $totalAssets += $groupBalance;
                                   break;
                               case 'LIABILITIES':
                                   $totalLiabilities += $groupBalance;
                                   break;
                               case 'EQUITY':
                                   $totalEquity += $groupBalance;
                                   break;
                           }
                       @endphp

                       <div class="card mb-4 shadow-sm">
                           <div class="card-header
                                @if($name == 'ASSETS') bg-primary
                                @elseif($name == 'LIABILITIES') bg-warning
                                @else bg-success
                                @endif text-white d-flex justify-content-between py-2">
                               <h6 class="mb-0">{{ $name }}</h6>
                               <div>
                                   @if($debugMode)
                                       <span class="badge bg-info text-white mx-1">Balance: {{ number_format($calculatedGroupBalance, 2) }}</span>
                                   @endif
                               </div>
                           </div>

                           <div class="card-body">
                               @forelse ($groupCharts as $chartIndex => $chart)
                                   @php
                                       $chartName = $chart['chart_name'] ?? 'Unnamed Chart '.($chartIndex + 1);
                                       $accounts = $chart['ledgers'] ?? [];
                                       $collapseId = 'collapse-'.Str::slug($name.'-'.$chartIndex);
                                       $chartTotal = $name == 'ASSETS'
                                           ? collect($accounts)->sum(fn($a) => ($a['debit'] ?? 0) - ($a['credit'] ?? 0))
                                           : collect($accounts)->sum(fn($a) => ($a['credit'] ?? 0) - ($a['debit'] ?? 0));
                                   @endphp

                                   <div class="mb-2 border-bottom pb-2">
                                       <div class="d-flex justify-content-between align-items-center"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $collapseId }}"
                                            style="cursor: pointer;">
                                           <div class="d-flex align-items-center">
                                               <i class="me-2 bi bi-caret-right-fill collapsed-icon" id="icon-{{ $collapseId }}"></i>
                                               <strong>{{ $chartName }}</strong>
                                           </div>
                                           <span class="fw-semibold">{{ number_format($chartTotal, 2) }}</span>
                                       </div>

                                       <div id="{{ $collapseId }}" class="collapse mt-2">
                                           <table class="table table-sm table-striped table-bordered mt-2 fs-sm datatable" id="datatable">
                                               <thead class="table-light">
                                               <tr>
                                                   <th>#</th>
                                                   <th>Account Name</th>
                                                   <th class="text-end">Debit</th>
                                                   <th class="text-end">Credit</th>
                                                   <th class="text-end">Balance</th>
                                               </tr>
                                               </thead>
                                               <tbody>
                                               @foreach ($accounts as $acc)
                                                   @php
                                                       $accountBalance = $acc['balance'] ?? 0;
                                                       $calculatedBalance = $name == 'ASSETS'
                                                           ? ($acc['debit'] ?? 0) - ($acc['credit'] ?? 0)
                                                           : ($acc['credit'] ?? 0) - ($acc['debit'] ?? 0);
                                                   @endphp
                                                   <tr class="link-primary" onclick="window.location.href='{{ route('accounts.viewLedgerStatement', base64_encode(($acc['client_account_id'] ?? '').':'.($fy->financial_year_id ?? ''))) }}'"
                                                       style="cursor:pointer;"
                                                       @if($debugMode && abs($accountBalance - $calculatedBalance) > 0.01)
                                                           class="table-danger"
                                                       @endif>

                                                       <td>{{ $loop->iteration }}</td>
                                                       <td class="text-capitalize" nowrap="">{{ ucwords(strtolower($acc['client_account_name'] ?? 'N/A')) }}</td>
                                                       <td class="text-end">{{ number_format($acc['debit'] ?? 0, 2) }}</td>
                                                       <td class="text-end">{{ number_format($acc['credit'] ?? 0, 2) }}</td>
                                                       <td class="text-end fw-semibold {{ ($accountBalance >= 0) ? 'text-success' : 'text-danger' }}">
                                                           {{ number_format($accountBalance, 2) }}
                                                       </td>
                                                   </tr>
                                               @endforeach
                                               </tbody>
                                           </table>
                                       </div>
                                   </div>
                               @empty
                                   <div class="alert alert-warning">No accounts found in this section</div>
                               @endforelse
                           </div>
                       </div>
                   @endforeach

                   @php
                       $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;
                       $difference = $totalAssets - $totalLiabilitiesEquity;
                       $isBalanced = abs($difference) < 0.01;
                   @endphp

                       <!-- Debug Information (visible in debug mode) -->
                   @if($debugMode && count($sectionErrors))
                       <div class="card border-danger mb-4">
                           <div class="card-header bg-danger text-white">
                               <h6 class="mb-0">System Warnings</h6>
                           </div>
                           <div class="card-body">
                               <ul class="mb-0">
                                   @foreach($sectionErrors as $error)
                                       <li>{{ $error }}</li>
                                   @endforeach
                               </ul>
                           </div>
                       </div>
                   @endif

                   <!-- Balance Sheet Summary -->
                   <div class="card shadow-sm mt-4">
                       <div class="card-header bg-info text-white">
                           <h6 class="mb-0">Balance Sheet Summary</h6>
                       </div>
                       <div class="card-body">
                           <div class="row mb-2">
                               <div class="col-md-6"><strong>Total Assets:</strong></div>
                               <div class="col-md-6 text-end">{{ number_format($totalAssets, 2) }}</div>
                           </div>
                           <div class="row mb-2">
                               <div class="col-md-6"><strong>Total Liabilities:</strong></div>
                               <div class="col-md-6 text-end">{{ number_format($totalLiabilities, 2) }}</div>
                           </div>
                           <div class="row mb-2">
                               <div class="col-md-6"><strong>Total Equity:</strong></div>
                               <div class="col-md-6 text-end">{{ number_format($totalEquity, 2) }}</div>
                           </div>
                           <div class="row mb-3">
                               <div class="col-md-6"><strong>Liabilities + Equity:</strong></div>
                               <div class="col-md-6 text-end">{{ number_format($totalLiabilitiesEquity, 2) }}</div>
                           </div>
                           <hr>
                           <div class="row mb-3">
                               <div class="col-md-6"><strong>Difference:</strong></div>
                               <div class="col-md-6 text-end {{ $isBalanced ? 'text-success' : 'text-danger' }}">
                                   {{ number_format($difference, 2) }}
                                   @if(!$isBalanced)
                                       <i class="bi bi-exclamation-triangle-fill ms-1"></i>
                                   @endif
                               </div>
                           </div>
                           <div class="text-center mt-3">
                               @if ($isBalanced)
                                   <span class="badge bg-success text-white px-4 py-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> Balanced
                                    </span>
                               @else
                                   <span class="badge bg-danger text-white px-3 py-2">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Not Balanced
                                    </span>
                                   <div class="mt-2 text-muted fs-sm">
                                       <a href="#" data-bs-toggle="modal" data-bs-target="#balanceHelpModal">
                                           <i class="bi bi-question-circle"></i> Help me fix this
                                       </a>
                                   </div>
                               @endif
                           </div>
                       </div>
                   </div>
                </div>
            </div>
        </div>
    </div>

   <!-- Help Modal -->
   <div class="modal fade" id="balanceHelpModal" tabindex="-1" aria-hidden="true">
       <div class="modal-dialog modal-lg">
           <div class="modal-content">
               <div class="modal-header bg-primary text-white">
                   <h5 class="modal-title">Balance Sheet Troubleshooting</h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                   <h6>Common reasons for imbalance:</h6>
                   <ol>
                       <li>Missing transactions in one or more accounts</li>
                       <li>Incorrect account classification (asset vs liability vs equity)</li>
                       <li>Data entry errors in debit/credit amounts</li>
                       <li>Rounding errors accumulating across many transactions</li>
                       <li>Opening balances not properly carried forward</li>
                   </ol>

                   <h6 class="mt-3">Debugging steps:</h6>
                   <ul>
                       <li>Add <code>?debug=1</code> to your URL to see detailed calculations</li>
                       <li>Check each account's balance matches (debit - credit) for assets or (credit - debit) for liabilities/equity</li>
                       <li>Verify all transactions are properly recorded with equal debits and credits</li>
                   </ul>

                   <div class="alert alert-info mt-3">
                       <strong>Current difference:</strong> {{ number_format($difference, 2) }}<br>
                       This amount might help identify the missing or incorrect entry.
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  {{-- <a --}}{{--href="{{ route('accounts.audit') }}" --}}{{--class="btn btn-primary">
                       <i class="bi bi-search"></i> Run Account Audit
                   </a>--}}
               </div>
           </div>
       </div>
   </div>

@endsection

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function () {
        $('.datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 25
        });

    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(toggle => {
            const icon = toggle.querySelector('.collapsed-icon');
            const target = document.querySelector(toggle.getAttribute('data-bs-target'));
            if (!target) return;

            target.addEventListener('show.bs.collapse', () => {
                icon.classList.remove('bi-caret-right-fill');
                icon.classList.add('bi-caret-down-fill');
            });
            target.addEventListener('hide.bs.collapse', () => {
                icon.classList.remove('bi-caret-down-fill');
                icon.classList.add('bi-caret-right-fill');
            });
        });
    });
</script>
