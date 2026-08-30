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

    .header-section {
        background: linear-gradient(135deg, #1dd975 0%, #34ef8c 100%);
        color: white;
        padding: 24px;
        border-radius: 8px 8px 0 0;
        margin: -1rem -1rem 0 -1rem;
    }

    .info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .info-card h6 {
        color: #10b981;
        font-weight: 600;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .info-value {
        color: #212529;
        font-weight: 500;
    }

    .items-section {
        margin-top: 24px;
    }

    .item-card {
        background: white;
        border-left: 4px solid #10b981;
        padding: 16px 20px;
        margin-bottom: 12px;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        transition: all 0.2s;
    }

    .item-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 12px;
    }

    .item-name {
        font-weight: 600;
        font-size: 1rem;
        color: #212529;
        margin-bottom: 4px;
    }

    .item-badge {
        background: #10b981;
        color: white;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .item-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .item-detail {
        display: flex;
        flex-direction: column;
    }

    .item-detail-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #9ca3af;
        margin-bottom: 2px;
    }

    .item-detail-value {
        font-weight: 600;
        color: #374151;
    }

    .item-total {
        font-size: 1.05rem;
        font-weight: 700;
        color: #10b981;
    }

    .summary-box {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .summary-box h6 {
        color: white;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.1rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 0.95rem;
    }

    .summary-row.grand-total {
        border-top: 2px solid rgba(255,255,255,0.3);
        margin-top: 8px;
        padding-top: 12px;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .summary-footer {
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid rgba(255,255,255,0.2);
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .status-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
        display: inline-block;
    }

    .notes-box {
        background: #fff9e6;
        border-left: 4px solid #fbbf24;
        padding: 16px;
        border-radius: 4px;
        margin-top: 20px;
    }

    .notes-box h6 {
        color: #92400e;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .notes-box p {
        color: #78350f;
        margin: 0;
        line-height: 1.6;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white;
        }

        .header-section {
            background: #667eea !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .summary-box {
            background: #10b981 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

@section('account::dashboard')
    <div class="card">
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Local Purchase Order</h4>
                        <p class="mb-0 opacity-90">{{ $lpo->lpo_number }}</p>
                    </div>
                    <div class="no-print">
                        <a href="{{ route('lpos.view') }}" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        @if($lpo->status == 'draft')
                        <a href="{{ route('lpos.edit', $lpo->id) }}" class="btn btn-warning btn-sm me-2">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Basic Info -->
                        <div class="info-card">
                            <h6><i class="bi bi-info-circle"></i> Basic Information</h6>
                            <div class="info-row">
                                <span class="info-label">LPO Number</span>
                                <span class="info-value">{{ $lpo->lpo_number }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Date</span>
                                <span class="info-value">{{ \Carbon\Carbon::parse($lpo->date)->format('d M, Y') }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status</span>
                                <span>
                                <span class="status-badge bg-{{ match($lpo->status) {
                                    'draft' => 'secondary text-white',
                                    'pending' => 'warning text-dark',
                                    'approved' => 'success text-white',
                                    'rejected' => 'danger text-white',
                                    'completed' => 'info text-white',
                                    'cancelled' => 'dark text-white',
                                    default => 'secondary text-white'
                                } }}">
                                    {{ ucfirst($lpo->status) }}
                                </span>
                            </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Supplier</span>
                                <span class="info-value">{{ $lpo->supplier->supplier_name ?? 'N/A' }}</span>
                            </div>
                            @if($lpo->created_by)
                                <div class="info-row">
                                    <span class="info-label">Created By</span>
                                    <span class="info-value">{{ $lpo->createdBy->first_name.' '.$lpo->createdBy->surname ?? 'N/A' }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Items -->
                        <div class="items-section">
                            <h5 class="mb-3" style="color: #10b981; font-weight: 600;">
                                <i class="bi bi-box-seam"></i> Items ({{ $lpo->items->count() }})
                            </h5>

                            @if($lpo->items->count() > 0)
                                @foreach($lpo->items as $item)
                                    <div class="item-card">
                                        <div class="item-header">
                                            <div>
                                                <div class="item-name">{{ $item->item_name }}</div>
                                                <span class="item-badge">Line {{ $item->line_number }}</span>
                                            </div>
                                            <div class="item-total">
                                                KES {{ number_format($item->gross_amount, 2) }}
                                            </div>
                                        </div>

                                        <div class="item-details">
                                            <div class="item-detail">
                                                <span class="item-detail-label">Quantity</span>
                                                <span class="item-detail-value">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</span>
                                            </div>
                                            <div class="item-detail">
                                                <span class="item-detail-label">Unit Price</span>
                                                <span class="item-detail-value">KES {{ number_format($item->unit_price, 2) }}</span>
                                            </div>
                                            <div class="item-detail">
                                                <span class="item-detail-label">Total (ex VAT)</span>
                                                <span class="item-detail-value">KES {{ number_format($item->total_price, 2) }}</span>
                                            </div>
                                            @if($item->is_vatable)
                                                <div class="item-detail">
                                                    <span class="item-detail-label">VAT ({{ $item->vat_rate }}%)</span>
                                                    <span class="item-detail-value">KES {{ number_format($item->vat_amount, 2) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No items in this LPO</p>
                                </div>
                            @endif
                        </div>

                        <!-- Notes -->
                        @if($lpo->notes)
                            <div class="notes-box">
                                <h6><i class="bi bi-sticky"></i> Notes / Additional Information</h6>
                                <p>{{ $lpo->notes }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Right Column - Summary -->
                    <div class="col-lg-4">
                        <div class="summary-box">
                            <h6><i class="bi bi-calculator"></i> Order Summary</h6>

                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>KES {{ number_format($lpo->subtotal, 2) }}</span>
                            </div>

                            <div class="summary-row">
                                <span>VAT (16%):</span>
                                <span>KES {{ number_format($lpo->vat_amount, 2) }}</span>
                            </div>

                            <div class="summary-row grand-total">
                                <span>Total Amount:</span>
                                <span>KES {{ number_format($lpo->total_amount, 2) }}</span>
                            </div>

                            <div class="summary-footer">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Total Items:</small>
                                    <small><strong>{{ $lpo->items->count() }}</strong></small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small>Vatable Items:</small>
                                    <small><strong>{{ $lpo->items->where('is_vatable', true)->count() }}</strong></small>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Info -->
                        @if($lpo->approved_by && $lpo->approved_at)
                            <div class="info-card mt-3">
                                <h6><i class="bi bi-check-circle"></i> Approval Details</h6>
                                <div class="info-row">
                                    <span class="info-label">Approved By</span>
                                    <span class="info-value">{{  $lpo->approvedBy->first_name.' '.$lpo->approvedBy->surname  ?? 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Approved At</span>
                                    <span class="info-value">{{ \Carbon\Carbon::parse($lpo->approved_at)->format('d M, Y - h:i A') }}</span>
                                </div>
                            </div>
                        @endif

                        <!-- Timestamps -->
                        <div class="info-card mt-3">
                            <h6><i class="bi bi-clock-history"></i> Timeline</h6>
                            <div class="info-row">
                                <span class="info-label">Created</span>
                                <span class="info-value" style="font-size: 0.8rem;">
                                {{ $lpo->created_at->format('d M, Y - h:i A') }}
                            </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Last Updated</span>
                                <span class="info-value" style="font-size: 0.8rem;">
                                {{ $lpo->updated_at->format('d M, Y - h:i A') }}
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
