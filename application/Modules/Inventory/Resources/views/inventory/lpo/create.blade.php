@extends('account::layouts.default')
<style>
    body {
        background-color: #f8f9fa;
        padding: 20px 0;
    }

    .lpo-container {
        max-width: 1200px;
        margin: 0 auto;
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
        border-left: 3px solid #0d6efd;
    }

    .item-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

    .summary-card {
        background: linear-gradient(135deg, #044f24 0%, #088a19 100%);
        color: white;
        border-radius: 10px;
        position: sticky;
        top: 20px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .summary-row:last-child {
        border-bottom: none;
        font-size: 1.2rem;
        font-weight: bold;
        padding-top: 15px;
    }

    .vat-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 3px;
    }

    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }

    .price-input {
        font-weight: 500;
    }

    .item-total {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0d6efd;
    }
</style>

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        <i class="fa fa-file"></i>
                        {{ isset($lpo) ? 'Edit' : 'Create' }} Local Purchase Order
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <a href="{{ route('lpos.view') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-4">
            <div class="row">
                <!-- Main Form Section -->
                <div class="col-lg-9">
                    <form id="lpoForm">
                        @csrf
                        <input type="hidden" id="lpo_id" name="lpo_id" value="{{ $lpo->id ?? '' }}">

                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle"></i> Basic Information
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="date" class="form-label">Date <span class="required">*</span></label>
                                        <input type="date" class="form-control" id="date" name="date"
                                               value="{{ $lpo->date ?? '' }}" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="supplier" class="form-label">Supplier <span class="required">*</span></label>
                                        <select class="form-select" id="supplier" name="supplier" required>
                                            <option value="">Select Supplier</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}"
                                                    {{ (isset($lpo) && $lpo->supplier_id == $supplier->id) ? 'selected' : '' }}>
                                                    {{ $supplier->supplier_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="text-primary mb-3">
                                    <i class="fa fa-box"></i> Add Items
                                </h6>

                                <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                    <div class="col-md-4">
                                        <label for="item" class="form-label">Item <span class="required">*</span></label>
                                        <select class="form-select" id="item" name="item">
                                            <option value="">Select Item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-unit="{{ $item->unit_label }}">
                                                    {{ $item->item_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="quantity" class="form-label">Quantity <span class="required">*</span></label>
                                        <input type="number" class="form-control" id="quantity" name="quantity"
                                               placeholder="0" min="0.01" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="unit" class="form-label">Unit</label>
                                        <input type="text" class="form-control" id="unit" name="unit"
                                               placeholder="Unit" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="unit_price" class="form-label">Unit Price <span class="required">*</span></label>
                                        <input type="number" class="form-control price-input" id="unit_price"
                                               name="unit_price" placeholder="0.00" min="0" step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_vatable" name="is_vatable">
                                            <label class="form-check-label" for="is_vatable">
                                                VATable
                                            </label>
                                        </div>
                                        <button type="button" class="btn btn-primary w-100 mt-2" onclick="addItem()">
                                            <i class="fas fa-plus-circle"></i> Add
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Items List -->
                                <div id="itemsList">
                                    <div class="empty-state border rounded">
                                        <svg width="60" height="60" fill="currentColor" class="text-muted mb-3" viewBox="0 0 16 16">
                                            <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                                            <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/>
                                        </svg>
                                        <p class="text-muted mb-0">No items added yet. Select items above to add them.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-message"></i> Notes / Additional Information
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"
                                          placeholder="Enter any additional notes or special instructions...">{{ $lpo->notes ?? '' }}</textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-success w-75">
                                <i class="fas fa-check-circle"></i> {{ isset($lpo) ? 'Update' : 'Submit' }} Purchase Order
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary Sidebar -->
                <div class="col-lg-3">
                    <div class="summary-card p-4">
                        <h5 class="mb-4 text-white">
                            <i class="fas fa-calculator"></i> Order Summary
                        </h5>

                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotal">KES 0.00</span>
                        </div>

                        <div class="summary-row">
                            <span>VAT (16%):</span>
                            <span id="vat">KES 0.00</span>
                        </div>

                        <div class="summary-row">
                            <span>Total Amount:</span>
                            <span id="total">KES 0.00</span>
                        </div>

                        <div class="mt-4 pt-3 border-top border-white border-opacity-25">
                            <small class="d-block mb-2">
                                <i class="fas fa-box"></i> Total Items: <strong id="itemCount">0</strong>
                            </small>
                            <small class="d-block">
                                <i class="fas fa-tag"></i> VATable Items: <strong id="vatableCount">0</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedItems = [];
            const VAT_RATE = 0.16;

            // Initialize form for edit mode
            @if(isset($lpo) && isset($lpo->items))
                selectedItems = @json($lpo->items).map(item => ({
                name: item.item_name,
                value: item.item_id,
                quantity: parseFloat(item.quantity),
                unit: item.unit,
                unit_price: parseFloat(item.unit_price),
                total_price: parseFloat(item.total_price),
                is_vatable: item.is_vatable
            }));
            renderItems();
            updateSummary();
            @endif

            // Set today's date if creating new
            const dateInput = document.getElementById('date');
            if (dateInput && !dateInput.value) {
                dateInput.valueAsDate = new Date();
            }

            // Update unit when item is selected
            const itemSelect = document.getElementById('item');
            const unitInput = document.getElementById('unit');

            if (itemSelect && unitInput) {
                itemSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const unit = selectedOption.getAttribute('data-unit') || '';
                    unitInput.value = unit;
                });
            }

            // Add item function
            window.addItem = function() {
                const itemSelect = document.getElementById('item');
                const quantity = parseFloat(document.getElementById('quantity').value);
                const unitPrice = parseFloat(document.getElementById('unit_price').value);
                const unit = document.getElementById('unit').value;
                const isVatable = document.getElementById('is_vatable').checked;

                if (!itemSelect.value || !quantity || quantity <= 0 || !unitPrice || unitPrice < 0) {
                    alert('Please select an item and enter valid quantity and unit price');
                    return;
                }

                const itemName = itemSelect.options[itemSelect.selectedIndex].text;
                const itemValue = itemSelect.value;
                const totalPrice = quantity * unitPrice;

                // Check if item already exists
                const existingIndex = selectedItems.findIndex(item => item.value === itemValue);
                if (existingIndex !== -1) {
                    // Update existing item
                    selectedItems[existingIndex].quantity = quantity;
                    selectedItems[existingIndex].unit_price = unitPrice;
                    selectedItems[existingIndex].total_price = totalPrice;
                    selectedItems[existingIndex].is_vatable = isVatable;
                } else {
                    // Add new item
                    selectedItems.push({
                        name: itemName,
                        value: itemValue,
                        quantity: quantity,
                        unit: unit,
                        unit_price: unitPrice,
                        total_price: totalPrice,
                        is_vatable: isVatable
                    });
                }

                renderItems();
                updateSummary();

                // Reset form
                itemSelect.value = '';
                document.getElementById('quantity').value = '';
                document.getElementById('unit').value = '';
                document.getElementById('unit_price').value = '';
                document.getElementById('is_vatable').checked = false;
            };

            // Edit item function
            window.editItem = function(index) {
                const item = selectedItems[index];

                document.getElementById('item').value = item.value;
                document.getElementById('quantity').value = item.quantity;
                document.getElementById('unit').value = item.unit;
                document.getElementById('unit_price').value = item.unit_price;
                document.getElementById('is_vatable').checked = item.is_vatable;

                // Scroll to top of form
                document.getElementById('item').scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            // Remove item function
            window.removeItem = function(index) {
                if (confirm('Are you sure you want to remove this item?')) {
                    selectedItems.splice(index, 1);
                    renderItems();
                    updateSummary();
                }
            };

            function formatCurrency(amount) {
                return 'KES ' + parseFloat(amount).toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function renderItems() {
                const itemsList = document.getElementById('itemsList');

                if (selectedItems.length === 0) {
                    itemsList.innerHTML = `
                        <div class="empty-state border rounded">
                            <svg width="60" height="60" fill="currentColor" class="text-muted mb-3" viewBox="0 0 16 16">
                                <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                                <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/>
                            </svg>
                            <p class="text-muted mb-0">No items added yet. Select items above to add them.</p>
                        </div>
                    `;
                    return;
                }

                itemsList.innerHTML = selectedItems.map((item, index) => `
                    <div class="card item-card mb-3 fade-in">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <h6 class="mb-1">${item.name}</h6>
                                    ${item.is_vatable ? '<span class="badge bg-success vat-badge">VAT 16%</span>' : '<span class="badge bg-secondary vat-badge">Non-VAT</span>'}
                                </div>
                                <div class="col-md-5">
                                    <div class="row g-2 small text-muted">
                                        <div class="col-6">
                                            <strong>Qty:</strong> ${item.quantity} ${item.unit}
                                        </div>
                                        <div class="col-6">
                                            <strong>Unit Price:</strong> ${formatCurrency(item.unit_price)}
                                        </div>
                                        <div class="col-12">
                                            <strong>Total:</strong> <span class="item-total">${formatCurrency(item.total_price)}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editItem(${index})" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})" title="Remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            function updateSummary() {
                let subtotal = 0;
                let vatAmount = 0;
                let vatableCount = 0;

                selectedItems.forEach(item => {
                    subtotal += parseFloat(item.total_price);
                    if (item.is_vatable) {
                        vatAmount += parseFloat(item.total_price) * VAT_RATE;
                        vatableCount++;
                    }
                });

                const total = subtotal + vatAmount;

                document.getElementById('subtotal').textContent = formatCurrency(subtotal);
                document.getElementById('vat').textContent = formatCurrency(vatAmount);
                document.getElementById('total').textContent = formatCurrency(total);
                document.getElementById('itemCount').textContent = selectedItems.length;
                document.getElementById('vatableCount').textContent = vatableCount;
            }

            // Form submission
            const lpoForm = document.getElementById('lpoForm');
            if (lpoForm) {
                lpoForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (selectedItems.length === 0) {
                        alert('Please add at least one item to the purchase order');
                        return;
                    }

                    const supplierSelect = document.getElementById('supplier');
                    const supplierValue = supplierSelect.value;
                    let supplierName = supplierSelect.options[supplierSelect.selectedIndex].text;

                    // Calculate totals
                    let subtotal = 0;
                    let vatAmount = 0;
                    selectedItems.forEach(item => {
                        subtotal += parseFloat(item.total_price);
                        if (item.is_vatable) {
                            vatAmount += parseFloat(item.total_price) * VAT_RATE;
                        }
                    });

                    const formData = {
                        lpo_id: document.getElementById('lpo_id').value,
                        date: document.getElementById('date').value,
                        supplier: supplierValue,
                        supplier_name: null,
                        items: selectedItems,
                        notes: document.getElementById('notes').value,
                        subtotal: subtotal,
                        vat_amount: vatAmount,
                        total_amount: subtotal + vatAmount
                    };

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

                    // Get CSRF token
                    const csrfToken = document.querySelector('input[name="_token"]').value;

                    // Determine route based on create or edit
                    const route = formData.lpo_id ? '{{ route("update.lpo") }}' : '{{ route("store.lpo") }}';

                    // Send data to Laravel backend
                    fetch(route, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(formData)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toastr.options = {
                                    "closeButton": true,
                                    "progressBar": true,
                                    "closeDuration": 10000
                                };
                                toastr.success(data.message);
                                setTimeout(() => {
                                    window.location.href = '{{ route("lpos.view") }}';
                                }, 1000);
                            } else {
                                toastr.options = {
                                    "closeButton": true,
                                    "progressBar": true,
                                    "closeDuration": 10000
                                };
                                toastr.error(data.message || 'Failed to save LPO');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalBtnText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.options = {
                                "closeButton": true,
                                "progressBar": true,
                                "closeDuration": 10000
                            };
                            toastr.error('Network error. Please try again.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        });
                });
            }
        });
    </script>
@endsection
