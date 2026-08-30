@extends('account::layouts.default')
<style>
    .item-card { transition: all 0.2s ease; }
    .item-card:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
    .required { color: #dc3545; }
    .fade-in { animation: fadeIn 0.3s ease-in; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">
                        {{ isset($requisition) ? 'Edit' : 'Create' }} Internal Usage
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <a href="{{ route('inventory.requisitions.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form id="requisitionForm">
                @csrf
                @if(isset($requisition))
                    <input type="hidden" name="requisition_id" value="{{ $requisition->id }}">
                @endif

                <!-- Basic Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label for="requisition_date" class="form-label">Requisition Date <span class="required">*</span></label>
                        <input type="date" class="form-control" id="requisition_date" name="requisition_date"
                               value="{{ $requisition->requisition_date ?? date('Y-m-d') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="client_id" class="form-label">Client <span class="required">*</span></label>
                        <select class="form-select" id="client_id" name="client_id" required>
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}"
                                    {{ (isset($requisition) && $requisition->client_id == $client->client_id) ? 'selected' : '' }}>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="si_number" class="form-label">SI NUMBER <span class="required">*</span></label>
                        <input type="text" class="form-control" id="si_number" name="si_number"
                               value="{{ $requisition->si_number ?? '' }}"
                               placeholder="Person name" required>
                    </div>

                    <div class="col-md-4">
                        <label for="warehouse_id" class="form-label">Client <span class="required">*</span></label>
                        <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->station_id }}"
                                    {{ (isset($requisition) && $requisition->warehouse_id == $warehouse->station_id) ? 'selected' : '' }}>
                                    {{ $warehouse->station_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="driver_name" class="form-label">Driver Name<span class="required">*</span></label>
                        <input type="text" class="form-control" id="driver_name" name="driver_name"
                               value="{{ $requisition->driver_name ?? '' }}"
                               placeholder="Driver Name">
                    </div>

                    <div class="col-md-4">
                        <label for="phone_number" class="form-label">Phone Number<span class="required">*</span></label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number"
                               value="{{ $requisition->phone_number ?? '' }}"
                               placeholder="Driver Phone Number">
                    </div>

                    <div class="col-md-4">
                        <label for="registration_number" class="form-label">Registration Number<span class="required">*</span></label>
                        <input type="text" class="form-control" id="registration_number" name="registration_number"
                               value="{{ $requisition->registration_number ?? '' }}"
                               placeholder="Vehicle Registration Number">
                    </div>

                </div>

                <!-- Items Section -->
                <hr class="my-4">
                <h5 class="text-info mb-3">
                    <i class="fa fa-clipboard-list"></i> Items Requested
                </h5>

                <div class="card border-info mb-3">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label for="item_id" class="form-label">Item <span class="required">*</span></label>
                                <select class="form-select" id="item_id" disabled>
                                    <option value="">Select client first</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="available_stock" class="form-label">Available Stock</label>
                                <input type="text" class="form-control bg-light" id="available_stock" readonly placeholder="-">
                            </div>
                            <div class="col-md-2">
                                <label for="quantity" class="form-label">Quantity <span class="required">*</span></label>
                                <input type="number" class="form-control" id="quantity" placeholder="0" min="1">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="addItem()">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Items List -->
                <div class="mb-4">
                    <h6 class="text-secondary mb-3">Requested Items</h6>
                    <div id="itemsList">
                        <div class="empty-state border rounded text-center py-5">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No items added yet. Select items above to add them.</p>
                        </div>
                    </div>
                </div>

                <!-- Purpose & Notes -->
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label for="purpose" class="form-label">Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2"
                                  placeholder="Enter the purpose of this requisition...">{{ $requisition->purpose ?? '' }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"
                                  placeholder="Enter any additional notes...">{{ $requisition->notes ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid gap-2 d-flex justify-content-center">
                    <button type="submit" class="btn btn-info btn-md col-6 text-white">
                        <i class="fa fa-paper-plane"></i> {{ isset($requisition) ? 'Update' : 'Submit' }} Requisition
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $existingItems = isset($requisition) ? $requisition->items->map(function($item) {
            return [
                'id' => (string) $item->id,
                'item_id' => $item->item_id,
                'item_name' => ucwords(strtolower($item->item->item_name)),
                'unit' => $item->item->unit,
                'quantity' => $item->quantity,
            ];
        }) : [];
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedItems = @json($existingItems);
            let itemsToDelete = [];
            let availableItems = [];
            let currentClientId = null;

            const clientSelect = document.getElementById('client_id');
            const itemSelect = document.getElementById('item_id');
            const quantityInput = document.getElementById('quantity');
            const stockInput = document.getElementById('available_stock');

            clientSelect.addEventListener('change', function() {
                const clientId = this.value;
                currentClientId = clientId;

                if (!clientId) {
                    itemSelect.innerHTML = '<option value="">Select client first</option>';
                    itemSelect.disabled = true;
                    stockInput.value = '';
                    quantityInput.value = '';
                    return;
                }

                itemSelect.innerHTML = '<option value="">Loading...</option>';
                itemSelect.disabled = true;

                const url = '{{ route("inventory.client.items", ":clientId") }}'.replace(':clientId', clientId);

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        availableItems = data.items || [];
                        itemSelect.innerHTML = '<option value="">Select Item</option>';

                        if (availableItems.length === 0) {
                            itemSelect.innerHTML = '<option value="">No items available</option>';
                            itemSelect.disabled = true;
                            toastr.warning('This client has no items in stock');
                            return;
                        }

                        const toTitleCase = str =>
                            str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase());

                        availableItems.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.item_id;
                            option.textContent = `${toTitleCase(item.item_name)} (${toTitleCase(item.unit)})`;
                            option.dataset.stock = item.current_balance;
                            option.dataset.unit = item.unit;
                            option.dataset.name = item.item_name;
                            itemSelect.appendChild(option);
                        });

                        itemSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading items:', error);
                        toastr.error('Failed to load available items');
                        itemSelect.innerHTML = '<option value="">Error loading items</option>';
                        itemSelect.disabled = true;
                    });
            });

            itemSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                quantityInput.value = '';

                if (selectedOption.value) {
                    const stock = parseInt(selectedOption.dataset.stock) || 0;
                    const unit = selectedOption.dataset.unit || '';
                    stockInput.value = `${stock} ${unit}`;
                    quantityInput.max = stock;
                    quantityInput.min = 1;
                    quantityInput.disabled = false;
                } else {
                    stockInput.value = '';
                    quantityInput.max = '';
                    quantityInput.disabled = true;
                }
            });

            quantityInput.addEventListener('input', function() {
                const max = parseInt(this.max);
                const value = parseInt(this.value);

                if (max > 0 && value > max) {
                    toastr.info(`Requested quantity (${value}) exceeds available stock (${max}). Approval may be required.`);
                }

                if (value < 1) {
                    this.value = 1;
                }
            });

            @if(isset($requisition))
            if (clientSelect.value) {
                clientSelect.dispatchEvent(new Event('change'));
            }
            @endif

                window.addItem = function() {
                if (!currentClientId) {
                    alert('Please select a client first');
                    return;
                }

                const itemId = itemSelect.value;
                const quantity = parseInt(quantityInput.value);
                const selectedOption = itemSelect.options[itemSelect.selectedIndex];

                if (!itemId || !quantity || quantity <= 0) {
                    alert('Please select an item and enter a valid quantity');
                    return;
                }

                const availableStock = parseInt(selectedOption.dataset.stock) || 0;
                const itemName = selectedOption.dataset.name;
                const unit = selectedOption.dataset.unit;

                const existingIndex = selectedItems.findIndex(item => item.item_id === itemId);
                if (existingIndex !== -1) {
                    selectedItems[existingIndex].quantity = quantity;
                } else {
                    selectedItems.push({
                        item_id: itemId,
                        item_name: itemName,
                        unit: unit,
                        quantity: quantity,
                        available_stock: availableStock,
                    });
                }

                renderItems();
                itemSelect.value = '';
                quantityInput.value = '';
                stockInput.value = '';
                quantityInput.disabled = true;
            };

            window.removeItem = function(index) {
                const item = selectedItems[index];
                if (item.id) {
                    if (confirm('Are you sure you want to remove this item?')) {
                        itemsToDelete.push(item.id);
                        selectedItems.splice(index, 1);
                        renderItems();
                    }
                } else {
                    selectedItems.splice(index, 1);
                    renderItems();
                }
            };

            function renderItems() {
                const itemsList = document.getElementById('itemsList');

                if (selectedItems.length === 0) {
                    itemsList.innerHTML = `
                <div class="empty-state border rounded text-center py-5">
                    <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No items added yet. Select items above to add them.</p>
                </div>
            `;
                    return;
                }

                itemsList.innerHTML = selectedItems.map((item, index) => `
            <div class="card item-card mb-2 fade-in border-info">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${item.item_name}</h6>
                            <small class="text-muted">
                                <strong>Requested:</strong> ${item.quantity} ${item.unit}
                                ${item.available_stock ? `<span class="badge ${item.quantity > item.available_stock ? 'bg-warning' : 'bg-success'} ms-2">Available: ${item.available_stock}</span>` : ''}
                            </small>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${index})">
                            <i class="fa fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
            }

            renderItems();

            document.getElementById('requisitionForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (!currentClientId) {
                    alert('Please select a client');
                    return;
                }

                if (selectedItems.length === 0) {
                    alert('Please add at least one item');
                    return;
                }

                const formData = {
                    requisition_date: document.getElementById('requisition_date').value,
                    client_id: currentClientId,
                    si_number: document.getElementById('si_number').value,
                    warehouse_id: document.getElementById('warehouse_id').value,
                    driver_name: document.getElementById('driver_name').value,
                    phone_number: document.getElementById('phone_number').value,
                    registration_number: document.getElementById('registration_number').value,
                    purpose: document.getElementById('purpose').value,
                    notes: document.getElementById('notes').value,
                    items: selectedItems.map(item => ({
                        id: item.id || null,
                        item_id: item.item_id,
                        quantity: item.quantity
                    })),
                    items_to_delete: itemsToDelete
                };

                @if(isset($requisition))
                    formData.requisition_id = '{{ $requisition->id }}';
                @endif

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                const url = '{{ isset($requisition) ? route("inventory.requisitions.update", $requisition->id) : route("inventory.requisitions.store") }}';
                const csrfToken = document.querySelector('input[name="_token"]').value;

                fetch(url, {
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
                            toastr.success(data.message);
                            window.location.href = '{{ route("inventory.utilization") }}';
                        } else {
                            toastr.error(data.message || 'Failed to save requisition');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                    })
                    .catch(error => {
                        toastr.error('Network error: ' + error.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    });
            });
        });
    </script>
@endsection
