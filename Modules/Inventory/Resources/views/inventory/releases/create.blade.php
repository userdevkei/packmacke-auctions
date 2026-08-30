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
                        {{ isset($release) ? 'Edit' : 'Create' }} External Utilization
                    </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <a href="{{ route('inventory.releases.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form id="releaseForm">
                @csrf
                @if(isset($release))
                    <input type="hidden" name="release_id" value="{{ $release->id }}">
                @endif

                <!-- Basic Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="release_date" class="form-label">Release Date <span class="required">*</span></label>
                        <input type="date" class="form-control" id="release_date" name="release_date"
                               value="{{ $release->release_date ?? date('Y-m-d') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="client_id" class="form-label">Client <span class="required">*</span></label>
                        <select class="form-select" id="client_id" name="client_id" required>
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->client_id }}"
                                    {{ (isset($release) && $release->client_id == $client->client_id) ? 'selected' : '' }}>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="released_to" class="form-label">Released To <span class="required">*</span></label>
                        <input type="text" class="form-control" id="released_to" name="released_to"
                               value="{{ $release->released_to ?? '' }}"
                               placeholder="Person/Department" required>
                    </div>

                    <div class="col-md-4">
                        <label for="driver_name" class="form-label">Driver Name<span class="required">*</span></label>
                        <input type="text" class="form-control" id="driver_name" name="driver_name"
                               value="{{ $release->driver_name ?? '' }}"
                               placeholder="Driver Name">
                    </div>

                    <div class="col-md-4">
                        <label for="phone_number" class="form-label">Phone Number<span class="required">*</span></label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number"
                               value="{{ $release->phone_number ?? '' }}"
                               placeholder="Driver Phone Number">
                    </div>

                    <div class="col-md-4">
                        <label for="registration_number" class="form-label">Registration Number<span class="required">*</span></label>
                        <input type="text" class="form-control" id="registration_number" name="registration_number"
                               value="{{ $release->registration_number ?? '' }}"
                               placeholder="Vehicle Registration Number">
                    </div>
                </div>

                <!-- Items Section -->
                <hr class="my-4">
                <h5 class="text-danger mb-3">
                    <i class="fa fa-arrow-circle-down"></i> Items to Release
                </h5>

                <div class="card border-danger mb-3">
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
                    <h6 class="text-secondary mb-3">Items to be Released</h6>
                    <div id="itemsList">
                        <div class="empty-state border rounded text-center py-5">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No items added yet. Select items above to add them.</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label for="notes" class="form-label">Notes / Purpose</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"
                              placeholder="Enter release purpose or additional notes...">{{ $release->notes ?? '' }}</textarea>
                </div>

                <!-- Submit Button -->
                <div class="d-grid gap-2 d-flex justify-content-center">
                    <button type="submit" class="btn btn-danger btn-md col-5">
                        <i class="fa fa-check-circle"></i> {{ isset($release) ? 'Update' : 'Create' }} Release
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $existingItems = isset($release) ? $release->items->map(function($item) {
            return [
                'id' => (string) $item->id, // Convert to string - REMOVED 'existing' property
                'item_id' => $item->item_id,
                'item_name' => ucwords(strtolower($item->item->item_name)),
                'unit' => $item->item->unit,
                'quantity' => $item->quantity,
                'existing' => true
            ];
        }) : [];
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const baseUrl = '{{ url('/') }}';
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

                    if (stock === 0) {
                        quantityInput.disabled = true;
                        toastr.warning('This item is out of stock');
                    } else {
                        quantityInput.disabled = false;
                    }
                } else {
                    stockInput.value = '';
                    quantityInput.max = '';
                    quantityInput.disabled = true;
                }
            });

            quantityInput.addEventListener('input', function() {
                const max = parseInt(this.max);
                const value = parseInt(this.value);

                if (value > max) {
                    this.value = max;
                    toastr.warning(`Maximum available quantity is ${max}`);
                }

                if (value < 1) {
                    this.value = 1;
                }
            });

            @if(isset($release))
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

                const availableStock = parseInt(selectedOption.dataset.stock);
                if (quantity > availableStock) {
                    alert(`Insufficient stock! Available: ${availableStock}`);
                    return;
                }

                const itemName = selectedOption.dataset.name;
                const unit = selectedOption.dataset.unit;

                console.log('Before adding - selectedItems:', JSON.stringify(selectedItems, null, 2));

                const existingIndex = selectedItems.findIndex(item => item.item_id === itemId);
                console.log('Existing index:', existingIndex);

                if (existingIndex !== -1) {
                    const newQuantity = quantity;
                    if (newQuantity > availableStock) {
                        alert(`Total quantity exceeds available stock (${availableStock})`);
                        return;
                    }
                    console.log('Updating existing item at index:', existingIndex);
                    selectedItems[existingIndex].quantity = newQuantity;
                } else {
                    console.log('Adding new item');
                    selectedItems.push({
                        item_id: itemId,
                        item_name: itemName,
                        unit: unit,
                        quantity: quantity,
                        max_stock: availableStock,
                    });
                }

                console.log('After adding - selectedItems:', JSON.stringify(selectedItems, null, 2));

                renderItems();
                itemSelect.value = '';
                quantityInput.value = '';
                stockInput.value = '';
                quantityInput.disabled = true;
            };

            window.removeItem = function(index) {
                const item = selectedItems[index];
                // Only add to itemsToDelete if it has an ID (exists in database)
                if (item.id) {
                    if (confirm('Are you sure you want to remove this item?')) {
                        itemsToDelete.push(item.id);
                        selectedItems.splice(index, 1);
                        renderItems();
                    }
                } else {
                    // New item not yet saved, just remove from array
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
                <div class="card item-card mb-2 fade-in border-danger">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${item.item_name}</h6>
                                <small class="text-muted">
                                    <strong>Quantity:</strong> ${item.quantity} ${item.unit}
                                    ${item.max_stock ? `<span class="badge bg-info ms-2">Max: ${item.max_stock}</span>` : ''}
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

            const releaseForm = document.getElementById('releaseForm');
            if (releaseForm) {
                releaseForm.addEventListener('submit', function(e) {
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
                        release_date: document.getElementById('release_date').value,
                        client_id: currentClientId,
                        released_to: document.getElementById('released_to').value,
                        driver_name: document.getElementById('driver_name').value,
                        phone_number: document.getElementById('phone_number').value,
                        registration_number: document.getElementById('registration_number').value,
                        notes: document.getElementById('notes').value,
                        items: selectedItems.map(item => ({
                            id: item.id || null,  // Include ID if it exists
                            item_id: item.item_id,
                            quantity: item.quantity
                        })),
                        items_to_delete: itemsToDelete
                    };

                    @if(isset($release))
                        formData.release_id = '{{ $release->id }}';
                    @endif

                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                    const url = '{{ isset($release) ? route("inventory.releases.update", $release->id) : route("inventory.releases.store") }}';
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
                                toastr.error(data.message || 'Failed to save release');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalBtnText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('Network error: ' + error.message);
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        });
                });
            }
        });
    </script>
@endsection
