@extends('account::layouts.default')
<style>
    .info-label { font-weight: 600; color: #6c757d; }
    .info-value { color: #212529; }
    .status-card { border-left: 4px solid; }
    .status-pending { border-left-color: #ffc107; }
    .status-completed { border-left-color: #28a745; }
    .status-fulfilled { border-left-color: #28a745; }
    .status-cancelled { border-left-color: #dc3545; }
    @media print {
        .no-print { display: none; }
    }
</style>

@php
    $permissions = [
        'transfer_out' => [
            'edit'    => 'inventory.editItemsTransfer',
            'approve' => 'inventory.approveItemsTransfer',
        ],
        'release' => [
            'edit'    => 'inventory.editItemsRelease',
            'approve' => 'inventory.approveItemsRelease'
        ],
        'requisition' => [
            'edit'    => 'inventory.editRequisition',
            'approve' => 'inventory.approveRequisition'
        ],
    ];
@endphp

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        {{ ucfirst(str_replace('_', ' ', $type)) }} Details
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0 no-print">
                    <a href="{{ route('inventory.' . $type . 's.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    @if ($transaction->status === 'pending' && isset($permissions[$type]))
                        @canuser($permissions[$type]['edit'])
                        <a href="{{ route('inventory.' . $type . 's.edit', $transaction->id) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        @endcanuser
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <!-- Status Card -->
            <div class="card status-card status-{{ $transaction->status }} mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <span class="info-label">Transaction Number:</span><br>
                            <strong class="info-value fs-11">
                                @if($type === 'transfer_in')
                                    {{ $transaction->transfer_in_number }}
                                @elseif($type === 'transfer_out')
                                    {{ $transaction->transfer_out_number }}
                                @elseif($type === 'release')
                                    {{ $transaction->release_number }}
                                @else
                                    {{ $transaction->requisition_number }}
                                @endif
                            </strong>
                        </div>
                        <div class="col-md-3">
                            <span class="info-label">Date:</span><br>
                            <span class="info-value">
                            @if($type === 'transfer_in')
                                    {{ $transaction->transfer_date }}
                                @elseif($type === 'transfer_out')
                                    {{ $transaction->transfer_date }}
                                @elseif($type === 'release')
                                    {{ $transaction->release_date }}
                                @else
                                    {{ $transaction->requisition_date }}
                                @endif
                        </span>
                        </div>
                        <div class="col-md-3">
                            <span class="info-label">Status:</span><br>
                            @php
                                $statusClass = match($transaction->status) {
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-info',
                                    'completed' => 'bg-success',
                                    'fulfilled' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-11">
                            {{ ucfirst($transaction->status) }}
                        </span>
                        </div>
                        <div class="col-md-3">
                            <span class="info-label">Created By:</span><br>
                            <span class="info-value">{{ $transaction->user->first_name.' '. $transaction->user->surname ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Transaction Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="info-label">Client:</span><br>
                                <span class="info-value">{{ $transaction->client->client_name ?? 'N/A' }}</span>
                            </div>

                            @if($type === 'transfer_in' || $type === 'transfer_out')
                                <div class="mb-3">
                                    <span class="info-label">{{ $type === 'transfer_in' ? 'From' : 'To' }} Client:</span><br>
                                    <span class="info-value">{{ $transaction->recipient->client_name ?? 'External' }}</span>
                                </div>
                            @endif

                            @if($type === 'release')
                                <div class="mb-3">
                                    <span class="info-label">Released To:</span><br>
                                    <span class="info-value">{{ $transaction->released_to }}</span>
                                </div>
                            @endif

                            @if($type === 'requisition')
                                <div class="mb-3">
                                    <span class="info-label">For SI Number:</span><br>
                                    <span class="info-value">{{ $transaction->si_number }}</span>
                                </div>
                                @if($transaction->purpose)
                                    <div class="mb-3">
                                        <span class="info-label">Purpose:</span><br>
                                        <span class="info-value">{{ $transaction->purpose }}</span>
                                    </div>
                                @endif
                            @endif

                            @if($transaction->approved_by)
                                <div class="mb-3">
                                    <span class="info-label">Approved By:</span><br>
                                    <span class="info-value">{{ $transaction->approvedBy->first_name.' '.$transaction->approvedBy->surname }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Additional Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="info-label">Created:</span><br>
                                <span class="info-value">{{ $transaction->created_at->format('M d, Y H:i A') }}</span>
                            </div>
                            <div class="mb-3">
                                <span class="info-label">Last Updated:</span><br>
                                <span class="info-value">{{ $transaction->updated_at->format('M d, Y H:i A') }}</span>
                            </div>
                            @if($transaction->notes)
                                <div class="mb-3">
                                    <span class="info-label">Notes:</span><br>
                                    <span class="info-value">{{ $transaction->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 fs-sm">
                            <thead class="bg-200">
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Unit</th>
                                <th class="text-end">Quantity</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transaction->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ ucwords(strtolower($item->item->item_name)) ?? 'N/A' }}</td>
                                    <td>{{ $item->item->unit_label ?? 'N/A' }}</td>
                                    <td class="text-end">
                                        <strong>{{ number_format($item->quantity) }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td colspan="3" class="text-end"><strong>Total Items:</strong></td>
                                <td class="text-end">
                                    <strong>{{ $transaction->items->count() }} items</strong>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            @if ($transaction->status === 'pending' && isset($permissions[$type]))
                @canuser($permissions[$type]['approve'])
                    <div class="mt-4 no-print">
                        <hr>
                        <div class="d-flex gap-2 justify-content-end">
                            <button onclick="approveTransaction()" class="btn btn-success">
                                <i class="fa fa-check"></i> Approve Transaction
                            </button>
                            <button onclick="cancelTransaction()" class="btn btn-danger">
                                <i class="fa fa-times"></i> Cancel Transaction
                            </button>
                        </div>
                    </div>
                @endcanuser
            @endif
        </div>
    </div>

    <script>
        function approveTransaction() {
            if (!confirm('Are you sure you want to approve this transaction? This will affect inventory stock levels.')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('inventory') }}/{{ $type }}s/{{ $transaction->id }}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        location.reload();
                    } else {
                        toastr.error(data.message || 'Failed to approve transaction');
                    }
                })
                .catch(error => {
                    toastr.error('Network error: ' + error.message);
                });
        }

        function cancelTransaction() {
            if (!confirm('Are you sure you want to cancel this transaction?')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('inventory') }}/{{ $type }}s/{{ $transaction->id }}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        location.reload();
                    } else {
                        toastr.error(data.message || 'Failed to cancel transaction');
                    }
                })
                .catch(error => {
                    toastr.error('Network error: ' + error.message);
                });
        }
    </script>
@endsection
