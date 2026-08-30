@extends('account::layouts.default')
<style>
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .stat-card.received { border-left-color: #28a745; }
    .stat-card.issued { border-left-color: #dc3545; }
    .stat-card.available { border-left-color: #17a2b8; }
    .stat-card.transactions { border-left-color: #ffc107; }

    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 150px;
    }

    label {
        font-size: 14px;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
    }

    input[type="date"],
    select {
        padding: 7px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: white;
        color: #333;
        width: 100%;
    }

    input[type="date"]:focus,
    select:focus {
        outline: none;
        border-color: #0632f5;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    button {
        padding: 7px 24px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .btn-reset {
        padding: 7px 24px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
        white-space: nowrap;
        text-decoration: none !important;
    }

    .quantity-in {
        color: #28a745;
        font-weight: 600;
    }

    .quantity-out {
        color: #dc3545;
        font-weight: 600;
    }

    .balance-positive {
        color: #28a745;
        font-weight: 700;
    }

    .balance-negative {
        color: #dc3545;
        font-weight: 700;
    }

    .item-header {
        background: linear-gradient(135deg, #088a19 0%, #0ae33b 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .form-group {
            min-width: 100%;
        }

        button {
            width: 100%;
        }
    }
</style>

@section('account::dashboard')
    <!-- Item Header -->
    <div class="item-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ ucwords(strtolower($item->item_name)) }}</h4>
                <p class="mb-0 opacity-75">
                    <i class="fa fa-building"></i> {{ $client->client_name }}
                    <span class="mx-2">|</span>
                    <i class="fa fa-box"></i> Unit: {{ $item->unit_label }}
                </p>
            </div>
            <a href="{{ route('inventory.client.summary', $client->client_id) }}" class="btn btn-light btn-sm">
                <i class="fa fa-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card received h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Received</h6>
                            <h3 class="mb-0 text-success">{{ number_format($summary['total_received'], 2) }}</h3>
                            <small class="text-muted">{{ $item->unit_label }}</small>
                        </div>
                        <div>
                            <i class="fa fa-arrow-down fa-2x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card issued h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Issued</h6>
                            <h3 class="mb-0 text-danger">{{ number_format($summary['total_issued'], 2) }}</h3>
                            <small class="text-muted">{{ $item->unit_label }}</small>
                        </div>
                        <div>
                            <i class="fa fa-arrow-up fa-2x text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card available h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Current Balance</h6>
                            <h3 class="mb-0 text-info">{{ number_format($currentStock->current_balance ?? 0, 2) }}</h3>
                            <small class="text-muted">{{ $item->unit_label }}</small>
                        </div>
                        <div>
                            <i class="fa fa-warehouse fa-2x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card transactions h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Transactions</h6>
                            <h3 class="mb-0 text-warning">{{ number_format($summary['transactions_count']) }}</h3>
                            <small class="text-muted">Movements</small>
                        </div>
                        <div>
                            <i class="fa fa-exchange-alt fa-2x text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fa fa-filter"></i> Filter Movements</h6>
        </div>
        <div class="card-body">
            <form method="POST" class="filter-form">
                @csrf
                <div class="form-group">
                    <label for="dateFrom">Date From</label>
                    <input type="date" id="dateFrom" name="dateFrom" value="{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') : '' }}">
                </div>
                <div class="form-group">
                    <label for="dateTo">Date To</label>
                    <input type="date" id="dateTo" name="dateTo" value="{{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('Y-m-d') : '' }}">
                </div>
                <div class="form-group">
                    <label for="transaction_type">Transaction Type</label>
                    <select id="transaction_type" name="transaction_type">
                        <option value="">All Types</option>
                        <option value="lpo" @selected(($transactionType ?? '') === 'lpo')>Received Lpo</option>
                        <option value="requisition" @selected(($transactionType ?? '') === 'requisition')>Internal Usage</option>
                        <option value="release" @selected(($transactionType ?? '') === 'release')>External Usage</option>
                    </select>
                </div>
                <button type="submit" class="btn-info">Filter</button>
                <a href="{{ route('inventory.item.movements', [$client->client_id, $item->id]) }}" class="btn-reset btn-danger">Reset</a>
                <button type="submit" name="export" value="1" class="btn-secondary">Export</button>
            </form>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="fa fa-list"></i> Movement History</h6>
        </div>
        <div class="card-body p-3">
            <table class="table table-bordered table-striped mb-0 fs-sm" id="movementsTable">
                <thead class="bg-200">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Transaction Type</th>
                    <th>Transaction #</th>
                    <th class="text-end">Quantity In</th>
                    <th class="text-end">Quantity Out</th>
                    <th class="text-end">Running Balance</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($movementsWithBalance as $movement)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($movement->transaction_date)->format('M d, Y') }}</td>
                        <td>
                            @php
                                $typeConfig = match($movement->transaction_type) {
                                    'lpo' => ['class' => 'success', 'icon' => 'fa-truck', 'label' => 'Received LPO'],
                                    'transfer_in' => ['class' => 'success', 'icon' => 'fa-arrow-down', 'label' => 'Transfer In'],
                                    'release' => ['class' => 'danger', 'icon' => 'fa-sign-out-alt', 'label' => 'External Usage'],
                                    'requisition' => ['class' => 'info', 'icon' => 'fa-clipboard-list', 'label' => 'Internal Usage'],
                                    'transfer_out' => ['class' => 'warning', 'icon' => 'fa-arrow-up', 'label' => 'Transfer Out'],
                                    default => ['class' => 'secondary', 'icon' => 'fa-exchange-alt', 'label' => 'Other']
                                };
                            @endphp
                            <span class="badge bg-{{ $typeConfig['class'] }}">
                                <i class="fa {{ $typeConfig['icon'] }}"></i> {{ $typeConfig['label'] }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('inventory.' . $movement->transaction_type . 's.show', $movement->transaction_id) }}"
                               class="link link-primary">
                                {{ $movement->transaction_number }}
                            </a>
                        </td>
                        <td class="text-end">
                            @if($movement->quantity_in > 0)
                                <span class="quantity-in">+{{ number_format($movement->quantity_in, 2) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($movement->quantity_out > 0)
                                <span class="quantity-out">-{{ number_format($movement->quantity_out, 2) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="{{ $movement->running_balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                {{ number_format($movement->running_balance, 2) }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $movement->notes ?? '—' }}</small>
                        </td>
                        <td>
                            <a href="{{ route('inventory.' . $movement->transaction_type . 's.show', $movement->transaction_id) }}"
                               class="link link-dark" title="View Transaction">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="bg-light">
                <tr>
                    <th colspan="4" class="text-end">Totals:</th>
                    <th class="text-end">
                        <span class="quantity-in">+{{ number_format($summary['total_received'], 2) }}</span>
                    </th>
                    <th class="text-end">
                        <span class="quantity-out">-{{ number_format($summary['total_issued'], 2) }}</span>
                    </th>
                    <th class="text-end">
                            <span class="{{ $summary['current_balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                {{ number_format($summary['current_balance'], 2) }}
                            </span>
                    </th>
                    <th colspan="2"></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('#movementsTable').DataTable({
                order: [[1, 'desc']], // Sort by date descending
                pageLength: 50,
                columnDefs: [
                    { orderable: false, targets: [8] }
                ]
            });
        });
    </script>
@endsection
