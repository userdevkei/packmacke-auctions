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

    .required {
        color: #dc3545;
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ---------- LPO Number input wrapper ---------- */
    .lpo-input-wrapper {
        position: relative;
    }
    .lpo-input-wrapper .lpo-status-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.1rem;
        pointer-events: none;
        display: none;
    }
    .lpo-input-wrapper input {
        padding-right: 36px;
    }

    .fetch-hint {
        font-size: 0.8rem;
        min-height: 1.3em;
        margin-top: 5px;
    }
    .fetch-hint.found     { color: #198754; }
    .fetch-hint.notfound  { color: #0d6efd; }
    .fetch-hint.loading   { color: #6c757d; }
    .fetch-hint.error     { color: #dc3545; }

    /* ---------- Status badge ---------- */
    .lpo-status-badge {
        font-size: 0.72rem;
        padding: 2px 10px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: capitalize;
        vertical-align: middle;
    }

    /* ---------- Items table ---------- */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }
    .items-table th {
        background: #f0f4ff;
        border-bottom: 2px solid #dee2e6;
        padding: 9px 8px;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #495057;
        white-space: nowrap;
    }
    .items-table td {
        padding: 7px 6px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }
    .items-table tbody tr:last-child td {
        border-bottom: none;
    }
    .items-table input,
    .items-table select {
        font-size: 0.85rem;
        padding: 5px 8px;
    }

    /* ---------- Add-item row ---------- */
    .add-item-row td {
        background: #eef3ff;
        border-bottom: none !important;
        padding: 9px 6px !important;
    }

    /* ---------- Empty state ---------- */
    .empty-state {
        padding: 32px;
        text-align: center;
        color: #6c757d;
    }

    #supplierNameInput {
        display: none;
    }
</style>

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Receive Local Purchase Order</h5>
                </div>
            </div>
        </div>

        <div class="card-body overflow-hidden p-lg-3">
            <div class="container">
                <div class="card-body p-4">
                    <form id="lpoForm">
                        @csrf

                        <!-- ============ Header Fields ============ -->
                        <div class="row g-3 mb-2">

                            <!-- LPO Number — typing here triggers the fetch -->
                            <div class="col-md-3">
                                <label for="lpo_number" class="form-label">
                                    LPO Number <span class="required">*</span>
                                    <span id="lpoStatusBadge" class="ms-1" style="display:none;"></span>
                                </label>
                                <div class="lpo-input-wrapper">
                                    <input type="text" class="form-control" id="lpo_number" name="lpo_number" autocomplete="off" placeholder="e.g. LPO-20250115">
                                    <span class="lpo-status-icon" id="lpoStatusIcon"></span>
                                </div>
                                <div class="fetch-hint" id="fetchHint"></div>
                            </div>

                            <div class="col-md-3">
                                <label for="date" class="form-label">Date Received <span class="required">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>

                            <div class="col-md-3">
                                <label for="client" class="form-label">Client <span class="required">*</span></label>
                                <select class="form-select" id="client" name="client" required>
                                    <option value="">Select Client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="supplier" class="form-label">Supplier <span class="required">*</span></label>
                                <select class="form-select" id="supplier" name="supplier" required>
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                    @endforeach
                                    <option value="other">Other (Type name)</option>
                                </select>
                                <input type="text" class="form-control mt-2 fade-in" id="supplierNameInput" name="supplier_name" placeholder="Enter supplier name">
                            </div>
                        </div>

                        <!-- ============ Items Table ============ -->
                        <hr class="my-4">
                        <h5 class="text-primary mb-2">
                            <i class="bi bi-box-seam"></i> Items
                        </h5>

                        <div class="table-responsive">
                            <table class="items-table">
                                <thead>
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:52%">Item</th>
                                    <th style="width:18%">Unit</th>
                                    <th style="width:18%">Quantity</th>
                                    <th style="width:7%"></th>
                                </tr>
                                </thead>
                                <!-- existing / added items rendered here -->
                                <tbody id="itemsTableBody"></tbody>
                                <!-- add-item row (always visible at bottom) -->
                                <tbody>
                                <tr class="add-item-row">
                                    <td class="text-muted">+</td>
                                    <td>
                                        <select class="form-select" id="newItemSelect">
                                            <option value="">— Select item —</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-unit="{{ $item->unit_label }}" data-name="{{ $item->item_name }}">{{ $item->item_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="newItemUnit" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" id="newItemQty" placeholder="0" min="0.001" step="0.001">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm w-100" id="addItemBtn">
                                            <i class="fas fa-plus-circle"></i> Add
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ============ Notes ============ -->
                        <div class="mt-4 mb-3">
                            <label for="notes" class="form-label">Notes / Additional Information</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter any additional notes or special instructions..."></textarea>
                        </div>

                        <!-- ============ Submit ============ -->
                        <div class="d-grid d-flex justify-content-center">
                            <button type="submit" class="btn btn-success btn-lg col-md-8" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Receive Purchase Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ================================================================
               STATE
               ================================================================ */
            let selectedItems = [];       // { itemId, itemName, unit, quantity }
            let fetchedLpoId  = null;     // DB id of the fetched LPO (null = new)
            let searchTimeout = null;     // debounce handle

            /* ================================================================
               ELEMENT REFS
               ================================================================ */
            const lpoNumberInput    = document.getElementById('lpo_number');
            const lpoStatusIcon     = document.getElementById('lpoStatusIcon');
            const lpoStatusBadge    = document.getElementById('lpoStatusBadge');
            const fetchHint         = document.getElementById('fetchHint');
            const dateInput         = document.getElementById('date');
            const clientSelect      = document.getElementById('client');
            const supplierSelect    = document.getElementById('supplier');
            const supplierNameInput = document.getElementById('supplierNameInput');
            const notesInput        = document.getElementById('notes');
            const itemsTableBody    = document.getElementById('itemsTableBody');
            const newItemSelect     = document.getElementById('newItemSelect');
            const newItemUnit       = document.getElementById('newItemUnit');
            const newItemQty        = document.getElementById('newItemQty');
            const addItemBtn        = document.getElementById('addItemBtn');

            /* ================================================================
               INIT
               ================================================================ */
            dateInput.valueAsDate = new Date();

            /* ================================================================
               SUPPLIER "Other" toggle
               ================================================================ */
            supplierSelect.addEventListener('change', function () {
                const isOther = this.value === 'other';
                supplierNameInput.style.display = isOther ? 'block' : 'none';
                supplierNameInput.required = isOther;
                if (!isOther) supplierNameInput.value = '';
            });

            /* ================================================================
               NEW ITEM ROW — populate unit on select
               ================================================================ */
            newItemSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                newItemUnit.value = opt.getAttribute('data-unit') || '';
            });

            /* ================================================================
               LPO NUMBER INPUT — debounced search
               ================================================================ */
            lpoNumberInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                const val = this.value.trim();

                // reset badge / hint when field is cleared
                if (!val) {
                    resetFetchUI();
                    clearForm();
                    return;
                }

                setFetchUI('loading', '<span class="spinner-border spinner-border-sm me-1" style="width:.7rem;height:.7rem;"></span>Searching…');

                searchTimeout = setTimeout(() => {
                    fetchLpo(val);
                }, 500); // 500ms debounce
            });

            /* ================================================================
               FETCH LPO FROM BACKEND
               ================================================================ */
            function fetchLpo(lpoNumber) {
                const csrfToken = document.querySelector('input[name="_token"]').value;

                fetch('{{ route("fetch.lpo") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ lpo_number: lpoNumber })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.found) {
                            populateForm(data.lpo);
                        } else {
                            // LPO not found — keep what the user typed, clear everything else
                            fetchedLpoId = null;
                            selectedItems = [];
                            renderItems();
                            clientSelect.value = '';
                            supplierSelect.value = '';
                            supplierNameInput.style.display = 'none';
                            supplierNameInput.required = false;
                            supplierNameInput.value = '';
                            notesInput.value = '';
                            lpoStatusBadge.style.display = 'none';
                            setFetchUI('notfound', '<i class="fas fa-info-circle"></i> No existing LPO found — will create new on receive.');
                        }
                    })
                    .catch(() => {
                        setFetchUI('error', '<i class="fas fa-exclamation-triangle"></i> Network error. Please try again.');
                    });
            }

            /* ================================================================
               POPULATE FORM FROM FETCHED LPO
               ================================================================ */
            function populateForm(lpo) {
                fetchedLpoId = lpo.id;

                // --- header fields ---
                // lpo_number already has what user typed, keep it
                if (lpo.date)  dateInput.value = lpo.date;
                if (lpo.client_id) clientSelect.value = lpo.client_id;

                // supplier
                if (lpo.supplier_id) {
                    supplierSelect.value = lpo.supplier_id;
                    supplierNameInput.style.display = 'none';
                    supplierNameInput.required = false;
                    supplierNameInput.value = '';
                }

                if (lpo.notes) notesInput.value = lpo.notes;

                // --- status badge ---
                if (lpo.status) {
                    const colors = {
                        draft: 'bg-secondary text-white',
                        pending: 'bg-warning text-dark',
                        approved: 'bg-success text-white',
                        rejected: 'bg-danger text-white',
                        completed: 'bg-info text-dark',
                        cancelled: 'bg-dark text-white'
                    };
                    lpoStatusBadge.className = 'lpo-status-badge ms-1 ' + (colors[lpo.status] || 'bg-secondary text-white');
                    lpoStatusBadge.textContent = lpo.status;
                    lpoStatusBadge.style.display = 'inline-block';
                }

                // --- items ---
                selectedItems = [];
                if (lpo.items && lpo.items.length) {
                    lpo.items.forEach(item => {
                        selectedItems.push({
                            itemId:   item.item_id,
                            itemName: item.item_name,
                            unit:     item.unit,
                            quantity: item.quantity
                        });
                    });
                }
                renderItems();

                setFetchUI('found', '<i class="fas fa-check-circle"></i> LPO found and loaded. You can edit items before receiving.');
            }

            /* ================================================================
               CLEAR FORM (when search input is emptied)
               ================================================================ */
            function clearForm() {
                fetchedLpoId = null;
                selectedItems = [];
                renderItems();
                dateInput.valueAsDate = new Date();
                clientSelect.value = '';
                supplierSelect.value = '';
                supplierNameInput.style.display = 'none';
                supplierNameInput.required = false;
                supplierNameInput.value = '';
                notesInput.value = '';
                lpoStatusBadge.style.display = 'none';
            }

            /* ================================================================
               FETCH UI HELPERS
               ================================================================ */
            function setFetchUI(state, html) {
                fetchHint.className = 'fetch-hint ' + state;
                fetchHint.innerHTML = html;

                // icon in the input
                if (state === 'found') {
                    lpoStatusIcon.style.display = 'inline';
                    lpoStatusIcon.className = 'lpo-status-icon fas fa-check-circle text-success';
                } else if (state === 'notfound') {
                    lpoStatusIcon.style.display = 'inline';
                    lpoStatusIcon.className = 'lpo-status-icon fas fa-info-circle text-primary';
                } else if (state === 'error') {
                    lpoStatusIcon.style.display = 'inline';
                    lpoStatusIcon.className = 'lpo-status-icon fas fa-exclamation-triangle text-danger';
                } else {
                    lpoStatusIcon.style.display = 'none';
                }
            }

            function resetFetchUI() {
                fetchHint.className = 'fetch-hint';
                fetchHint.innerHTML = '';
                lpoStatusIcon.style.display = 'none';
                lpoStatusBadge.style.display = 'none';
            }

            /* ================================================================
               ADD ITEM
               ================================================================ */
            addItemBtn.addEventListener('click', function () {
                const itemId  = newItemSelect.value;
                const qty     = parseFloat(newItemQty.value).toFixed(2);
                const unit    = newItemUnit.value;

                if (!itemId || !qty || qty <= 0) {
                    alert('Please select an item and enter a valid quantity.');
                    return;
                }

                const itemName = newItemSelect.options[newItemSelect.selectedIndex].getAttribute('data-name');

                // update existing row if same item already added
                const existing = selectedItems.find(i => i.itemId === itemId);
                if (existing) {
                    existing.quantity = qty;
                } else {
                    selectedItems.push({ itemId, itemName, unit, quantity: qty });
                }

                renderItems();

                // reset new-item row
                newItemSelect.value = '';
                newItemUnit.value   = '';
                newItemQty.value    = '';
            });

            /* ================================================================
               REMOVE ITEM
               ================================================================ */
            window.removeItem = function (index) {
                selectedItems.splice(index, 1);
                renderItems();
            };

            /* ================================================================
               EDIT QUANTITY (inline)
               ================================================================ */
            window.updateQuantity = function (index, value) {
                const qty = parseFloat(value);
                if (!isNaN(qty) && qty > 0) {
                    selectedItems[index].quantity = qty;
                }
            };

            /* ================================================================
               RENDER ITEMS TABLE
               ================================================================ */
            function renderItems() {
                if (selectedItems.length === 0) {
                    itemsTableBody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="bi bi-box-seam" style="font-size:2.5rem;color:#dee2e6;"></i>
                            <p class="text-muted mb-0 mt-2">No items yet. Add items using the row below.</p>
                        </div>
                    </td>
                </tr>`;
                    return;
                }

                itemsTableBody.innerHTML = selectedItems.map((item, i) => `
            <tr class="fade-in">
                <td class="text-muted">${i + 1}</td>
                <td>${item.itemName}</td>
                <td>${item.unit || '—'}</td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm"
                           value="${item.quantity}"
                           min="0.001"
                           step="0.001"
                           oninput="updateQuantity(${i}, this.value)">
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${i})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
            }

            /* ================================================================
               FORM SUBMIT
               ================================================================ */
            document.getElementById('lpoForm').addEventListener('submit', function (e) {
                e.preventDefault();

                if (selectedItems.length === 0) {
                    alert('Please add at least one item before receiving.');
                    return;
                }

                const supplierValue = supplierSelect.value;
                let   supplierName  = supplierSelect.options[supplierSelect.selectedIndex].text;
                if (supplierValue === 'other') {
                    supplierName = supplierNameInput.value;
                }

                const payload = {
                    lpo_id:        fetchedLpoId,                          // null if new
                    lpo_number:    lpoNumberInput.value,
                    date:          dateInput.value,
                    client:        clientSelect.value,
                    supplier:      supplierValue === 'other' ? null : supplierValue,
                    supplier_name: supplierValue === 'other' ? supplierName : null,
                    items:         selectedItems,
                    notes:         notesInput.value
                };

                // loading state
                const submitBtn      = document.getElementById('submitBtn');
                const originalBtnHTML = submitBtn.innerHTML;
                submitBtn.disabled   = true;
                submitBtn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Receiving…';

                const csrfToken = document.querySelector('input[name="_token"]').value;

                fetch('{{ route("store.purchases") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            toastr.options = { closeButton: true, progressBar: true, closeDuration: 10000 };
                            toastr.success(data.message);
                            window.location.href = '{{ route("purchases.view") }}';
                        } else {
                            toastr.options = { closeButton: true, progressBar: true, closeDuration: 10000 };
                            toastr.error(data.message || 'Failed to receive LPO.');
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
