@extends('account::layouts.default')
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
        width: 100%;
        max-width: 1400px;
    }

    h1 {
        color: #333;
        margin-bottom: 30px;
        font-size: 28px;
        text-align: center;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
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
    input[type="text"],
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
    input[type="text"]:focus,
    select:focus {
        outline: none;
        border-color: #0632f5;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    input[type="text"]::placeholder {
        color: #999;
        opacity: 1;
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

    .btn-filter {
        color: white;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
    }

    .btn-export {
        color: white;
    }

    .btn-export:hover {
        transform: translateY(-2px);
    }

    button:active {
        transform: translateY(0);
    }

    .transaction-type-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    @media (max-width: 1024px) {
        .filter-form {
            gap: 12px;
        }

        .form-group {
            min-width: 140px;
        }

        button {
            padding: 12px 20px;
            font-size: 14px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 30px 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .filter-form {
            gap: 10px;
        }

        .form-group {
            min-width: 120px;
        }

        input[type="date"],
        select {
            padding: 10px 12px;
            font-size: 14px;
        }

        button {
            padding: 10px 18px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
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
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        All Utilization History
                    </h5>
                </div>

                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    @if(canUser('inventory.addItemsTransfer') || canUser('inventory.addItemsRelease') || canUser('inventory.addRequisition'))
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa fa-plus"></i> New Usage
                        </button>
                        <ul class="dropdown-menu">
                            @canuser('inventory.addRequisition')
                            <li><a class="dropdown-item" href="{{ route('inventory.requisitions.create') }}">
                                    <i class="fa fa-clipboard-list text-info"></i> Internally
                                </a></li>
                            @endcanuser

                            @canuser('inventory.addItemsRelease')
                            <li><a class="dropdown-item" href="{{ route('inventory.releases.create') }}">
                                    <i class="fa fa-sign-out-alt text-danger"></i> Externally
                                </a></li>
                            @endcanuser
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="mb-3">
                <form method="POST" class="filter-form" id="filterForm">
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
                        <label for="client">Client</label>
                        <select id="client" name="client">
                            <option value="">All Clients</option>
                            @foreach($clients as $clientOption)
                                <option value="{{ $clientOption->client_id }}" @selected($client == $clientOption->client_id)>
                                    {{ $clientOption->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transaction_type">Type</label>
                        <select id="transaction_type" name="transaction_type">
                            <option value="">All Types</option>
                            <option value="requisition" @selected(($transactionType ?? '') === 'requisition')>Requisition (Internal)</option>
                            <option value="release" @selected(($transactionType ?? '') === 'release')>Release (External)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transaction_number">Transaction Number</label>
                        <input type="text" name="transaction_number" id="transaction_number" value="{{ $transactionNumber ?? old('transaction_number') }}" placeholder="Transaction number">
                    </div>

                    <button type="submit" class="btn-filter btn-info">Filter</button>

                    <a href="{{ route('inventory.utilization') }}" class="btn-reset btn-danger">Reset</a>
                    <button type="submit" name="export" value="1" class="btn-export btn-secondary">Export</button>
                </form>
            </div>
            <table class="table table-bordered table-striped mb-0 fs-sm" id="transactionsTable">
                <thead class="bg-200">
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Transaction Number</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Additional Info</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if($transaction->transaction_type === 'requisition')
                                <span class="badge bg-info transaction-type-badge">
                                    <i class="fa fa-clipboard-list"></i> Internal
                                </span>
                            @else
                                <span class="badge bg-warning transaction-type-badge">
                                    <i class="fa fa-sign-out-alt"></i> External
                                </span>
                            @endif
                        </td>
                        <td>{{ $transaction->transaction_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') }}</td>
                        <td>{{ $transaction->client->client_name ?? 'N/A' }}</td>
                        <td>
                            @if($transaction->transaction_type === 'requisition')
                                <small class="text-muted">SI: {{ $transaction->si_number.' - '.$transaction->warehouse?->station_name ?? 'N/A' }}</small>
                            @else
                                <small class="text-muted">Released To: {{ $transaction->released_to ?? 'N/A' }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $transaction->items->count() }} items</span>
                        </td>
                        <td class="text-center">
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
                            <span class="badge {{ $statusClass }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td nowrap>
                            <a href="{{ route('inventory.' . $transaction->transaction_type . 's.show', $transaction->id) }}"
                               class="link link-dark mx-1" title="View">
                                <i class="fa fa-eye"></i>
                            </a>

                            @php
                                $permissions = [
                                    'release' => [
                                        'edit'    => 'inventory.editItemsRelease',
                                        'approve' => 'inventory.approveItemsRelease'
                                    ],
                                    'requisition' => [
                                        'edit'    => 'inventory.editRequisition',
                                        'approve' => 'inventory.approveRequisition'
                                    ],
                                ];
                                $currentType = $transaction->transaction_type;
                            @endphp

                            @if ($transaction->status === 'pending' && isset($permissions[$currentType]))
                                {{-- EDIT --}}
                                @canuser($permissions[$currentType]['edit'])
                                <a href="{{ route('inventory.' . $currentType . 's.edit', $transaction->id) }}"
                                   class="link link-info mx-1" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                @endcanuser

                                {{-- APPROVE --}}
                                @canuser($permissions[$currentType]['approve'])
                                <a onclick="approveTransaction('{{ $transaction->id }}', '{{ $currentType }}')"
                                   class="link link-success mx-1" title="Approve" style="cursor: pointer;">
                                    <i class="fa fa-check"></i>
                                </a>

                                <a onclick="cancelTransaction('{{ $transaction->id }}', '{{ $currentType }}')"
                                   class="link link-danger mx-1" title="Cancel" style="cursor: pointer;">
                                    <i class="fa fa-times"></i>
                                </a>
                                @endcanuser
                            @endif
                            <a class="link link-dark mx-2" href="{{ route('download.transaction', $transaction->id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="Download PDF" target="_blank">
                                <span class="fas fa-file-pdf text-danger"></span>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
    <script>
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                order: [[3, 'desc']], // Sort by date descending
                pageLength: 50
            });
        });

        function approveTransaction(id, type) {
            if (!confirm(`Are you sure you want to approve this ${type}? This will affect inventory stock levels.`)) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('inventory') }}/${type}s/${id}/approve`, {
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
                        toastr.error(data.message || `Failed to approve ${type}`);
                    }
                })
                .catch(error => {
                    toastr.error('Network error: ' + error.message);
                });
        }

        function cancelTransaction(id, type) {
            if (!confirm(`Are you sure you want to cancel this ${type}?`)) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('inventory') }}/${type}s/${id}/cancel`, {
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
                        toastr.error(data.message || `Failed to cancel ${type}`);
                    }
                })
                .catch(error => {
                    toastr.error('Network error: ' + error.message);
                });
        }
    </script>
@endsection
