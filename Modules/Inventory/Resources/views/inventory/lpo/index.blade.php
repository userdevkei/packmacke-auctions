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

    .amount-cell {
        text-align: right;
        font-weight: 600;
        color: #2c3e50;
    }

    .vat-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
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
                        <i class="fa fa-file-invoice"></i> Local Purchase Orders
                    </h5>
                </div>

                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    @canuser('inventory.addLpo')
                    <a href="{{ route('lpos.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> New LPO
                    </a>
                    @endcanuser
                </div>
            </div>
        </div>

        <div class="card-body p-3">
            <!-- Filters Section -->
            <div class="mb-3">
                <form method="POST" class="filter-form" id="filterForm">
                    @csrf
                    <div class="form-group">
                        <label for="dateFrom">Date From</label>
                        <input type="date" id="dateFrom" name="dateFrom"
                               value="{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="form-group">
                        <label for="dateTo">Date To</label>
                        <input type="date" id="dateTo" name="dateTo"
                               value="{{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="form-group">
                        <label for="supplier">Supplier</label>
                        <select id="supplier" name="supplier">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $supplierOption)
                                <option value="{{ $supplierOption->id }}"
                                    {{ ($supplier ?? '') == $supplierOption->id ? 'selected' : '' }}>
                                    {{ $supplierOption->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ ($status ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending" {{ ($status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ ($status ?? '') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ ($status ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="completed" {{ ($status ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ ($status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lpo_number">LPO Number</label>
                        <input type="text" name="lpo_number" id="lpo_number"
                               value="{{ $lpoNumber ?? old('lpo_number') }}"
                               placeholder="LPO number">
                    </div>

                    <button type="submit" class="btn-filter btn-info">
                        <i class="fa fa-filter"></i> Filter
                    </button>

                    <a href="{{ route('lpos.view') }}" class="btn-reset btn-danger">
                        <i class="fa fa-undo"></i> Reset
                    </a>

                    <button type="submit" name="export" value="1" class="btn-export btn-secondary">
                        <i class="fa fa-file-excel"></i> Export
                    </button>
                </form>
            </div>

            <!-- LPO Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0 fs-sm" id="lpoTable">
                    <thead class="bg-200">
                    <tr>
                        <th class="text-center">#</th>
                        <th>LPO Number</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">VAT</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($lpos as $lpo)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $lpo->lpo_number }}</strong>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($lpo->date)->format('d/m/Y') }}</td>
                            <td>{{ $lpo->supplier_display_name }}</td>
                            <td>
                                    <span class="badge bg-secondary">
                                        {{ $lpo->items->count() }} {{ Str::plural('item', $lpo->items->count()) }}
                                    </span>
                            </td>
                            <td class="amount-cell">{{ number_format($lpo->subtotal, 2) }}</td>
                            <td class="amount-cell">
                                @if($lpo->vat_amount > 0)
                                    <span class="badge bg-success vat-badge">{{ number_format($lpo->vat_amount, 2) }}</span>
                                @else
                                    <span class="text-muted">0.00</span>
                                @endif
                            </td>
                            <td class="amount-cell">
                                <strong>{{ number_format($lpo->total_amount, 2) }}</strong>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($lpo->status) {
                                        'draft' => 'bg-secondary',
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'completed' => 'bg-info',
                                        'cancelled' => 'bg-dark',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                        {{ ucfirst($lpo->status) }}
                                    </span>
                            </td>
                            <td class="text-center" nowrap>
                                <!-- View -->
                                <a href="{{ route('lpos.show', $lpo->id) }}"
                                   class="link link-dark mx-1"
                                   title="View Details">
                                    <i class="fa fa-eye"></i>
                                </a>

                                <!-- Edit (only for draft/rejected) -->
                                @if($lpo->canEdit())
                                    @canuser('inventory.editLpo')
                                    <a href="{{ route('lpos.edit', $lpo->id) }}"
                                       class="link link-info mx-1"
                                       title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    @endcanuser
                                @endif

                                <!-- Approve/Reject (only for pending) -->
                                @if($lpo->status === 'draft')
                                    @canuser('inventory.approveLpo')
                                    <a onclick="approveLpo('{{ $lpo->id }}')"
                                       class="link link-success mx-1"
                                       title="Approve">
                                        <i class="fa fa-check"></i>
                                    </a>

                                    <a onclick="rejectLpo('{{ $lpo->id }}')"
                                       class="link link-danger mx-1"
                                       title="Reject">
                                        <i class="fa fa-times"></i>
                                    </a>
                                    @endcanuser
                                @endif

                                <!-- Delete (only for draft/rejected) -->
                                @if($lpo->canDelete())
                                    @canuser('inventory.deleteLpo')
                                    <a onclick="deleteLpo('{{ $lpo->id }}')"
                                       class="link link-danger mx-1"
                                       title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                    @endcanuser
                                @endif

                                <!-- Download PDF -->
                                <a href="{{ route('lpos.export-pdf', $lpo->id) }}"
                                   class="link link-dark mx-1"
                                   title="Download PDF"
                                   target="_blank">
                                    <i class="fas fa-file-pdf text-danger"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                    <tr>
                        <th colspan="5" class="text-end">Totals:</th>
                        <th class="amount-cell text-end">{{ number_format($lpos->sum('subtotal'), 2) }}</th>
                        <th class="amount-cell text-end">{{ number_format($lpos->sum('vat_amount'), 2) }}</th>
                        <th class="amount-cell text-end">
                            <strong>{{ number_format($lpos->sum('total_amount'), 2) }}</strong>
                        </th>
                        <th colspan=""></th>
                        <th colspan=""></th>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            @if($lpos->hasPages())
                <div class="mt-3">
                    {{ $lpos->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>

    <script>
        $(document).ready(function() {
            $('#lpoTable').DataTable({
                order: [[2, 'desc']], // Sort by date descending
                pageLength: 50,
                columnDefs: [
                    { orderable: false, targets: [5, 9] } // Disable sorting on Items and Actions columns
                ],
                language: {
                    search: "Search LPOs:",
                    lengthMenu: "Show _MENU_ LPOs per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ LPOs",
                    infoEmpty: "No LPOs available",
                    infoFiltered: "(filtered from _MAX_ total LPOs)",
                    zeroRecords: "No matching LPOs found"
                }
            });
        });

        function approveLpo(id) {
            if (!confirm('Are you sure you want to approve this LPO? This action cannot be undone.')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('lpos') }}/${id}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    status: 'approved'
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.options = {
                            "closeButton": true,
                            "progressBar": true,
                            "closeDuration": 3000
                        };
                        toastr.success(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(data.message || 'Failed to approve LPO');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Network error: ' + error.message);
                });
        }

        function rejectLpo(id) {
            const remarks = prompt('Please enter rejection reason (optional):');

            if (remarks === null) {
                return; // User cancelled
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('lpos') }}/${id}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    status: 'rejected',
                    remarks: remarks
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.options = {
                            "closeButton": true,
                            "progressBar": true,
                            "closeDuration": 3000
                        };
                        toastr.success(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(data.message || 'Failed to reject LPO');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Network error: ' + error.message);
                });
        }

        function deleteLpo(id) {
            if (!confirm('Are you sure you want to delete this LPO? This action cannot be undone.')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                '{{ csrf_token() }}';

            fetch(`{{ url('lpos') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.options = {
                            "closeButton": true,
                            "progressBar": true,
                            "closeDuration": 3000
                        };
                        toastr.success(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(data.message || 'Failed to delete LPO');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Network error: ' + error.message);
                });
        }
    </script>
@endsection
