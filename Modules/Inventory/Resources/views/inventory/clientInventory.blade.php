@extends('account::layouts.default')
{{--<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">--}}
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
    .low-stock { background-color: #fff3cd; }
    .out-of-stock { background-color: #f8d7da; }
</style>

@section('account::dashboard')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        Inventory Summary - {{ $client->client_name }}
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    @if(canUser('inventory.addItemsTransfer') || canUser('inventory.addItemsRelease') || canUser('inventory.addRequisition'))
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fa fa-plus"></i> New Usage
                            </button>
                            <ul class="dropdown-menu">
                                @canuser('inventory.addItemsRelease')
                                <li><a class="dropdown-item" href="{{ route('inventory.releases.create') }}?client={{ $client->client_id }}">
                                        <i class="fa fa-sign-out-alt text-danger"></i> Externally
                                    </a></li>
                                @endcanuser
                                @canuser('inventory.addRequisition')
                                <li><a class="dropdown-item" href="{{ route('inventory.requisitions.create') }}?client={{ $client->client_id }}">
                                        <i class="fa fa-clipboard-list text-info"></i> Internally
                                    </a></li>
                                @endcanuser
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card received h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Received</h6>
                            <h3 class="mb-0 text-success">{{ number_format($stockBalances->sum('total_received')) }}</h3>
                            <small class="text-muted">Items</small>
                        </div>
                        <div>
                            <i class="fa fa-arrow-down fa-3x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card issued h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Issued</h6>
                            <h3 class="mb-0 text-danger">{{ number_format($stockBalances->sum('total_issued')) }}</h3>
                            <small class="text-muted">Items</small>
                        </div>
                        <div>
                            <i class="fa fa-arrow-up fa-3x text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card available h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Current Balance</h6>
                            <h3 class="mb-0 text-info">{{ number_format($stockBalances->sum('current_balance')) }}</h3>
                            <small class="text-muted">Items</small>
                        </div>
                        <div>
                            <i class="fa fa-warehouse fa-3x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Items Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Stock Balance by Item</h6>
        </div>
        <div class="card-body p-3">
            <table class="table table-bordered table-striped mb-0 fs-sm" id="inventoryTable">
                <thead class="bg-200">
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Unit</th>
                    <th class="text-end">Total Received</th>
                    <th class="text-end">Total Issued</th>
                    <th class="text-end">Current Balance</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($stockBalances as $stock)
                    <tr class="{{ $stock->current_balance == 0 ? 'out-of-stock' : ($stock->current_balance < 10 ? 'low-stock' : '') }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ ucwords(strtolower($stock->item_name)) }}</strong>
                            @if($stock->current_balance == 0)
                                <span class="badge bg-danger ms-2">Out of Stock</span>
                            @elseif($stock->current_balance < 10)
                                <span class="badge bg-warning ms-2">Low Stock</span>
                            @endif
                        </td>
                        <td>{{ $stock->unit }}</td>
                        <td class="text-end">{{ number_format($stock->total_received) }}</td>
                        <td class="text-end">{{ number_format($stock->total_issued) }}</td>
                        <td class="text-end">
                            <strong class="{{ $stock->current_balance == 0 ? 'text-danger' : ($stock->current_balance < 10 ? 'text-warning' : 'text-success') }}">
                                {{ number_format($stock->current_balance) }}
                            </strong>
                        </td>
                        <td>
                            <a href="{{ route('inventory.item.movements', [$client->client_id, $stock->item_id]) }}"
                               class="link link-info"
                               title="View Movement History">
                                <i class="fa fa-history"></i> History
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No inventory found for this client</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0">Recent Inventory Movements</h6>
        </div>
        <div class="card-body p-3">
            <table class="table table-bordered table-striped mb-0 fs-sm" id="movementsTable">
                <thead class="bg-200">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Transaction Type</th>
                    <th>Transaction #</th>
                    <th>Item</th>
                    <th class="text-end">In</th>
                    <th class="text-end">Out</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentMovements as $movement)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($movement->transaction_date)->format('M d, Y') }}</td>
                        <td>
                            @php
                                $typeClass = match($movement->transaction_type) {
                                    'lpo', 'transfer_in' => 'success',
                                    'release' => 'danger',
                                    'requisition' => 'info',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $typeClass }}">
                                {{ $movement->transaction_type == 'lpo' ? 'Received Lpo' : ($movement->transaction_type == 'release' ? 'External Usage' : 'Internal Usage') }}
                            </span>
                        </td>
                        <td>{{ $movement->transaction_number }}</td>
                        <td>
                            @php
                                $item = DB::table('inventory_items')->where('id', $movement->item_id)->first();
                            @endphp
                            {{ ucwords(strtolower($item->item_name)) ?? 'N/A' }}
                        </td>
                        <td class="text-end">
                            @if($movement->quantity_in > 0)
                                <span class="text-success">+{{ number_format($movement->quantity_in) }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($movement->quantity_out > 0)
                                <span class="text-danger">-{{ number_format($movement->quantity_out) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <p class="text-muted mb-0">No recent movements found</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('#inventoryTable').DataTable({
                order: [[1, 'asc']], // Sort by item name
                pageLength: 25,
                columnDefs: [
                    { orderable: false, targets: 6 }
                ]
            });

            $('#movementsTable').DataTable({
                order: [[1, 'desc']], // Sort by date descending
                pageLength: 25
            });
        });
    </script>
@endsection
