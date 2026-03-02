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

    .item-card {
        transition: all 0.2s ease;
    }

    .item-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .required {
        color: #dc3545;
    }

    .empty-state {
        padding: 40px;
        text-align: center;
        color: #6c757d;
    }

    #supplierNameInput {
        display: none;
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .existing-item {
        border-left: 4px solid #0d6efd;
    }

    .new-item {
        border-left: 4px solid #198754;
    }

    .qty-input {
        width: 90px;
    }
</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        Edit Purchase Order
                        <span class="badge bg-{{ match($lpo->status) {
                            'pending' => 'warning text-dark',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary'
                        } }} ms-2" style="font-size:0.7rem;">
                            {{ ucfirst($lpo->status) }}
                        </span>
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <a href="{{ route('lpos.view') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel">
                    <div class="container-fluid">
                        <div class="card-body p-4">
                            <form id="lpoForm">
                                @csrf
                                <input type="hidden" name="po_id" value="{{ $lpo->id }}">

                                <!-- Basic Information -->
                                <div class="row g-3 mb-2">
                                    <div class="col-md-3">
                                        <label for="lpo_number" class="form-label">LPO Number</label>
                                        <input type="text" class="form-control" name="lpo_number" id="lpo_number" value="{{ $lpo->lpo_number }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date" class="form-label">Date <span class="required">*</span></label>
                                        <input type="date" class="form-control" id="date" name="date" value="{{ Carbon\Carbon::parse($lpo->date)->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="client" class="form-label">Client <span class="required">*</span></label>
                                        <select class="form-select" id="client" name="client" required>
                                            <option value="">Select Client</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->client_id }}" {{ $lpo->client_id == $client->client_id ? 'selected' : '' }}>
                                                    {{ $client->client_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="supplier" class="form-label">Supplier <span class="required">*</span></label>
                                        <select class="form-select" id="supplier" name="supplier" required>
                                            <option value="">Select Supplier</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ $lpo->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->supplier_name }}
                                                </option>
                                            @endforeach
                                            <option value="other">Other (Type name)</option>
                                        </select>
                                        <input type="text" class="form-control mt-2 fade-in" id="supplierNameInput" name="supplier_name" placeholder="Enter supplier name">
                                    </div>
                                </div>

                                <!-- Items Section -->
                                <hr class="my-4">
                                <h5 class="text-primary mb-1">
                                    <i class="bi bi-box-seam"></i> Manage Items
                                </h5>

                                <div class="card border-primary mb-2">
                                    <div class="card-body">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-5">
                                                <label for="item" class="form-label">Add Item <span class="required">*</span></label>
                                                <select class="form-select" id="item" name="item">
                                                    <option value="">Select Item</option>
                                                    @foreach($items as $item)
                                                        <option value="{{ $item->id }}" data-unit="{{ $item->unit_label }}" data-name="{{ $item->item_name }}">{{ $item->item_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="unit" class="form-label">Unit</label>
                                                <input type="text" class="form-control" id="unit" placeholder="Unit" readonly>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="quantity" class="form-label">Quantity <span class="required">*</span></label>
                                                <input type="number" class="form-control" id="quantity" placeholder="0" min="0.001" step="0.001">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-primary w-100" id="addItemBtn">
                                                    <i class="bi bi-plus-circle"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items List -->
                                <div class="mb-2">
                                    <h6 class="text-secondary mb-3">
                                        Items in this Purchase Order
                                        <small class="text-muted">(Blue = existing, Green = new)</small>
                                    </h6>
                                    <div id="itemsList"></div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes / Additional Information</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Enter any additional notes or special instructions...">{{ $lpo->notes }}</textarea>
                                </div>

                                <!-- Submit -->
                                <div class="d-grid gap-2 d-flex justify-content-center">
                                    <button type="submit" class="btn btn-success btn-lg col-md-7" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> Update Purchase Order
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /*
             * Seed existing items.
             * $item->inventoryItem is the belongs-to relationship on Inventory
             * that points to InventoryItem — that's where item_name and unit_label live.
             */
            @php
                $existingItems = $lpo->items->map(function ($item) {
                    return [
                        'id'       => $item->id,
                        'itemId'   => $item->item_id,
                        'itemName' => $item->uom->item_name,
                        'unit'     => $item->uom->unit_label,
                        'quantity' => (float) $item->quantity,
                        'existing' => true,
                    ];
                });
            @endphp

            let selectedItems  = @json($existingItems);
            let itemsToDelete  = [];

            /* ============================================================
               REFS
               ============================================================ */
            const supplierSelect    = document.getElementById('supplier');
            const supplierNameInput = document.getElementById('supplierNameInput');
            const itemSelect        = document.getElementById('item');
            const unitInput         = document.getElementById('unit');
            const quantityInput     = document.getElementById('quantity');
            const addItemBtn        = document.getElementById('addItemBtn');
            const itemsList         = document.getElementById('itemsList');

            /* ============================================================
               SUPPLIER toggle
               ============================================================ */
            supplierSelect.addEventListener('change', function () {
                const isOther = this.value === 'other';
                supplierNameInput.style.display = isOther ? 'block' : 'none';
                supplierNameInput.required = isOther;
                if (!isOther) supplierNameInput.value = '';
            });

            /* ============================================================
               ITEM SELECT — populate unit
               ============================================================ */
            itemSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                unitInput.value = opt.getAttribute('data-unit') || '';
            });

            /* ============================================================
               ADD ITEM
               ============================================================ */
            addItemBtn.addEventListener('click', function () {
                const itemId   = itemSelect.value;
                const qty      = parseFloat(quantityInput.value);
                const unit     = unitInput.value;

                if (!itemId || !qty || qty <= 0) {
                    alert('Please select an item and enter a valid quantity.');
                    return;
                }

                const itemName = itemSelect.options[itemSelect.selectedIndex].getAttribute('data-name');

                // if same item already in the list, just update its quantity
                const existing = selectedItems.find(i => i.itemId === itemId);
                if (existing) {
                    existing.quantity = qty;
                } else {
                    selectedItems.push({
                        id:       null,
                        itemId:   itemId,
                        itemName: itemName,
                        unit:     unit,
                        quantity: qty,
                        existing: false
                    });
                }

                renderItems();

                itemSelect.value    = '';
                unitInput.value     = '';
                quantityInput.value = '';
            });

            /* ============================================================
               REMOVE ITEM
               ============================================================ */
            function removeItem(index) {
                const item = selectedItems[index];

                if (item.existing && item.id) {
                    if (!confirm('Remove this item from the purchase order?')) return;
                    itemsToDelete.push(item.id);
                }

                selectedItems.splice(index, 1);
                renderItems();
            }

            /* ============================================================
               UPDATE QUANTITY inline
               ============================================================ */
            function updateQuantity(index, value) {
                const qty = parseFloat(value);
                if (!isNaN(qty) && qty > 0) {
                    selectedItems[index].quantity = qty;
                }
            }

            /* ============================================================
               RENDER - USE EVENT DELEGATION INSTEAD OF ONCLICK
               ============================================================ */
            function renderItems() {
                if (selectedItems.length === 0) {
                    itemsList.innerHTML = `
                <div class="empty-state border rounded">
                    <i class="bi bi-box-seam" style="font-size:2.8rem;color:#dee2e6;"></i>
                    <p class="text-muted mb-0 mt-2">No items in this purchase order.</p>
                </div>`;
                    return;
                }

                itemsList.innerHTML = selectedItems.map((item, index) => `
            <div class="card item-card mb-2 fade-in ${item.existing ? 'existing-item' : 'new-item'}">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                ${item.itemName}
                                ${item.existing
                    ? '<span class="badge bg-info ms-2">Existing</span>'
                    : '<span class="badge bg-success ms-2">New</span>'}
                            </h6>
                            <small class="text-muted"><strong>Unit:</strong> ${item.unit || '—'}</small>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted fw-semibold">Qty:</small>
                                <input type="number" readonly
                                       class="form-control form-control-sm qty-input"
                                       value="${item.quantity}"
                                       min="0.001"
                                       step="0.001"
                                       data-index="${index}">
                            </div>
                            <button type="button" class="btn btn-danger btn-sm remove-item-btn" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

                // Attach event listeners after rendering
                attachItemEventListeners();
            }

            /* ============================================================
               ATTACH EVENT LISTENERS TO DYNAMICALLY CREATED ELEMENTS
               ============================================================ */
            function attachItemEventListeners() {
                // Remove buttons
                document.querySelectorAll('.remove-item-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        removeItem(index);
                    });
                });

                // Quantity inputs
                document.querySelectorAll('.qty-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        updateQuantity(index, this.value);
                    });
                });
            }

            renderItems();

            /* ============================================================
               SUBMIT
               ============================================================ */
            document.getElementById('lpoForm').addEventListener('submit', function (e) {
                e.preventDefault();

                if (selectedItems.length === 0) {
                    alert('Please add at least one item.');
                    return;
                }

                const supplierValue = supplierSelect.value;
                const payload = {
                    po_id:           document.querySelector('input[name="po_id"]').value,
                    date:            document.getElementById('date').value,
                    lpo_number:      document.getElementById('lpo_number').value,
                    client:          document.getElementById('client').value,
                    supplier:        supplierValue === 'other' ? null : supplierValue,
                    supplier_name:   supplierValue === 'other' ? supplierNameInput.value : null,
                    items:           selectedItems,
                    items_to_delete: itemsToDelete,
                    notes:           document.getElementById('notes').value
                };

                const submitBtn       = document.getElementById('submitBtn');
                const originalBtnHTML = submitBtn.innerHTML;
                submitBtn.disabled    = true;
                submitBtn.innerHTML   = '<span class="spinner-border spinner-border-sm me-2"></span>Updating…';

                const csrfToken = document.querySelector('input[name="_token"]').value;

                fetch('{{ route("purchases.update", ["id" => $lpo->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                    .then(res => res.json())
                    .then(data => {
                        toastr.options = { closeButton: true, progressBar: true, closeDuration: 10000 };
                        if (data.success) {
                            toastr.success(data.message);
                            window.location.href = '{{ route("purchases.view") }}';
                        } else {
                            toastr.error(data.message || 'Failed to update.');
                            submitBtn.disabled  = false;
                            submitBtn.innerHTML = originalBtnHTML;
                        }
                    })
                    .catch(() => {
                        toastr.options = { closeButton: true, progressBar: true, closeDuration: 10000 };
                        toastr.error('Network error. Please try again.');
                        submitBtn.disabled  = false;
                        submitBtn.innerHTML = originalBtnHTML;
                    });
            });
        });
    </script>
@endsection
