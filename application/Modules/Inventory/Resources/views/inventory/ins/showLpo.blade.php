@extends('account::layouts.default')
<style>
    body {
        background-color: #f8f9fa;
        padding: 20px 0;
    }

    .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        border-bottom: 2px solid #0d6efd;
    }

    .info-row {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        color: #212529;
        font-size: 0.95rem;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    .items-table th {
        background: #f0f4ff;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 10px;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #495057;
        font-weight: 600;
    }

    .items-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .items-table tbody tr:hover {
        background: #f8f9fa;
    }

    .items-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 400;
        text-transform: capitalize;
        display: inline-block;
    }

    .notes-box {
        background: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 16px;
        border-radius: 4px;
        margin-top: 20px;
    }

    .notes-box h6 {
        margin-bottom: 8px;
        color: #495057;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .notes-box p {
        margin: 0;
        color: #6c757d;
        line-height: 1.3;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white;
            padding: 0;
        }

        .card {
            box-shadow: none;
            border: 1px solid #dee2e6;
        }
    }
</style>

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        Purchase Order Details
                        <span class="status-badge ms-2 bg-{{ match($lpo->status) {
                        'pending' => 'warning text-dark',
                        'approved' => 'success text-white',
                        'rejected' => 'danger text-white',
                        default => 'secondary text-white'
                    } }}">
                        {{ ucfirst($lpo->status) }}
                    </span>
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0 no-print">
                    <div class="action-buttons justify-content-end">
                        <a href="{{ route('purchases.view') }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        @if($lpo->status !== 'received')
                            <a href="{{ route('purchases.edit', $lpo->id) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Basic Information -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6 class="text-primary mb-3">
                        <i class="bi bi-info-circle"></i> Purchase Order Information
                    </h6>
                </div>

                <div class="col-md-3">
                    <div class="info-row">
                        <div class="info-label">LPO Number</div>
                        <div class="info-value">{{ $lpo->lpo_number }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-row">
                        <div class="info-label">PO Number</div>
                        <div class="info-value">{{ $lpo->purchase_order_number }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-row">
                        <div class="info-label">Date</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($lpo->date)->format('d M, Y') }}</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="info-row">
                        <div class="info-label">Created By</div>
                        <div class="info-value">{{ $lpo->user->first_name.' '.$lpo->user->surname ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Client & Supplier -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-row">
                        <div class="info-label">Client</div>
                        <div class="info-value">{{ $lpo->client->client_name ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-row">
                        <div class="info-label">Supplier</div>
                        <div class="info-value">{{ $lpo->supplier->supplier_name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <hr class="my-4">
            <h6 class="text-primary mb-3">
                <i class="bi bi-box-seam"></i> Items ({{ $lpo->items->count() }})
            </h6>

            @if($lpo->items->count() > 0)
                <div class="table table-sm table-stripped fs-sm table-responsive">
                    <table class="items-table">
                        <thead>
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 50%">Item Name</th>
                            <th style="width: 20%">Unit</th>
                            <th style="width: 25%" class="text-end">Quantity</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($lpo->items as $index => $item)
                            <tr>
                                <td class="text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->uom->item_name }}</strong>
                                </td>
                                <td>{{ $item->uom->unit_label }}</td>
                                <td class="text-end">
                                    <strong>{{ number_format($item->quantity, 2) }}</strong>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">No items in this purchase order</p>
                </div>
            @endif

            <!-- Notes -->
            @if($lpo->notes)
                <div class="notes-box">
                    <h6><i class="bi bi-sticky"></i> Notes / Additional Information</h6>
                    <p>{{ $lpo->notes }}</p>
                </div>
            @endif

            <!-- Timestamps -->
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <div class="info-label">Created At</div>
                        <div class="info-value text-muted" style="font-size:0.85rem;">
                            {{ $lpo->created_at->format('d M, Y - h:i A') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value text-muted" style="font-size:0.85rem;">
                            {{ $lpo->updated_at->format('d M, Y - h:i A') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
