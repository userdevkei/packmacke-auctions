@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">

<style>
    .form-label-required::after {
        content: " *";
        color: red;
    }

    .modal-dialog {
        max-width: 700px;
    }

    .supplier-info {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .supplier-info i {
        width: 20px;
        text-align: center;
    }
</style>

@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Suppliers Management</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @canuser('supplier.add')
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">New Supplier</span>
                        </a>
                        @endcanuser
                    </div>
                </div>

                <!-- Create Supplier Modal -->
                <div class="modal fade" id="createSupplierModal" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="createSupplierModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg mt-6" role="document">
                        <div class="modal-content border-0">
                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                    <h5 class="mb-1" id="createSupplierModalLabel">CREATE SUPPLIER</h5>
                                </div>
                                <div class="p-4">
                                    <form class="supplier-form" method="POST" action="{{ route('suppliers.store') }}">
                                        @csrf
                                        <div class="row g-3">
                                            <!-- Supplier Name -->
                                            <div class="col-12">
                                                <label class="form-label form-label-required fw-bold" style="font-size: small !important;">
                                                    SUPPLIER NAME
                                                </label>
                                                <input type="text" name="supplier_name" class="form-control" placeholder="Enter supplier name" required>
                                            </div>

                                            <!-- Contact Information Section -->
                                            <div class="col-12">
                                                <h6 class="border-bottom pb-2 mb-3">Contact Information</h6>
                                            </div>

                                            <!-- Phone Number -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold" style="font-size: small !important;">
                                                    PHONE NUMBER
                                                </label>
                                                <input type="text" name="phone_number" class="form-control" placeholder="e.g., +254 712 345 678">
                                            </div>

                                            <!-- Email -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold" style="font-size: small !important;">
                                                    EMAIL ADDRESS
                                                </label>
                                                <input type="email" name="email" class="form-control" placeholder="supplier@example.com">
                                            </div>

                                            <!-- Address Section -->
                                            <div class="col-12">
                                                <h6 class="border-bottom pb-2 mb-3 mt-2">Address Details</h6>
                                            </div>

                                            <!-- PO Box -->
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold" style="font-size: small !important;">
                                                    P.O. BOX
                                                </label>
                                                <input type="text" name="po_box" class="form-control" placeholder="e.g., 12345">
                                            </div>

                                            <!-- Street -->
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold" style="font-size: small !important;">
                                                    STREET
                                                </label>
                                                <input type="text" name="street" class="form-control" placeholder="e.g., Moi Avenue">
                                            </div>

                                            <!-- Town -->
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold" style="font-size: small !important;">
                                                    TOWN/CITY
                                                </label>
                                                <input type="text" name="town" class="form-control" placeholder="e.g., Nairobi">
                                            </div>

                                            <!-- Notes -->
                                            <div class="col-12">
                                                <label class="form-label fw-bold" style="font-size: small !important;">
                                                    ADDITIONAL NOTES
                                                </label>
                                                <textarea name="notes" class="form-control" rows="3" placeholder="Any additional information about the supplier..."></textarea>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-center mt-4">
                                            <button type="submit" class="btn btn-success col-8 submit-button">
                                                <i class="fas fa-check me-2"></i>CREATE SUPPLIER
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Supplier Name</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($suppliers as $supplier)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $supplier->supplier_name }}</strong>
                                    @if($supplier->notes)
                                        <br><small class="text-muted">{{ Str::limit($supplier->notes, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="supplier-info">
                                        @if($supplier->phone_number)
                                            <div><i class="fas fa-phone"></i> {{ $supplier->phone_number }}</div>
                                        @endif
                                        @if($supplier->email)
                                            <div><i class="fas fa-envelope"></i> {{ $supplier->email }}</div>
                                        @endif
                                        @if(!$supplier->phone_number && !$supplier->email)
                                            <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="supplier-info">
                                        @if($supplier->po_box || $supplier->street || $supplier->town)
                                            @if($supplier->po_box)
                                                <div><i class="fas fa-box"></i> P.O. Box {{ $supplier->po_box }}</div>
                                            @endif
                                            @if($supplier->street)
                                                <div><i class="fas fa-road"></i> {{ $supplier->street }}</div>
                                            @endif
                                            @if($supplier->town)
                                                <div><i class="fas fa-map-marker-alt"></i> {{ $supplier->town }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $supplier->created_at->format('M d, Y') }}</td>
                                <td nowrap="">
                                    @canuser('supplier.edit')
                                        <a class="text-info" data-bs-toggle="modal" data-bs-target="#editSupplierModal_{{ $supplier->id }}" title="Edit Supplier">
                                            <span class="fa-regular fa-pen-to-square"></span>
                                        </a>
                                    @endcanuser

                                    @canuser('supplier.delete')
                                    <a class="text-danger mx-2" data-bs-toggle="tooltip" data-bs-placement="left" title="Delete Supplier"
                                       onclick="return confirm('Are you sure you want to delete this supplier?')"
                                       href="{{ route('suppliers.destroy', $supplier->id) }}">
                                        <span class="fa fa-trash-alt"></span>
                                    </a>
                                    @endcanuser

                                    <!-- Edit Supplier Modal -->
                                    <div class="modal fade" id="editSupplierModal_{{ $supplier->id }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg mt-6" role="document">
                                            <div class="modal-content border-0">
                                                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                        <h5 class="mb-1">UPDATE SUPPLIER</h5>
                                                    </div>
                                                    <div class="p-4">
                                                        <form class="supplier-form" method="POST" action="{{ route('suppliers.update', $supplier->id) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="row g-3">
                                                                <!-- Supplier Name -->
                                                                <div class="col-12">
                                                                    <label class="form-label form-label-required fw-bold" style="font-size: small !important;">
                                                                        SUPPLIER NAME
                                                                    </label>
                                                                    <input type="text" name="supplier_name" class="form-control" value="{{ $supplier->supplier_name }}" required>
                                                                </div>

                                                                <!-- Contact Information Section -->
                                                                <div class="col-12">
                                                                    <h6 class="border-bottom pb-2 mb-3">Contact Information</h6>
                                                                </div>

                                                                <!-- Phone Number -->
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-bold" style="font-size: small !important;">
                                                                        PHONE NUMBER
                                                                    </label>
                                                                    <input type="text" name="phone_number" class="form-control" value="{{ $supplier->phone_number }}">
                                                                </div>

                                                                <!-- Email -->
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-bold" style="font-size: small !important;">
                                                                        EMAIL ADDRESS
                                                                    </label>
                                                                    <input type="email" name="email" class="form-control" value="{{ $supplier->email }}">
                                                                </div>

                                                                <!-- Address Section -->
                                                                <div class="col-12">
                                                                    <h6 class="border-bottom pb-2 mb-3 mt-2">Address Details</h6>
                                                                </div>

                                                                <!-- PO Box -->
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold" style="font-size: small !important;">
                                                                        P.O. BOX
                                                                    </label>
                                                                    <input type="text" name="po_box" class="form-control" value="{{ $supplier->po_box }}">
                                                                </div>

                                                                <!-- Street -->
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold" style="font-size: small !important;">
                                                                        STREET
                                                                    </label>
                                                                    <input type="text" name="street" class="form-control" value="{{ $supplier->street }}">
                                                                </div>

                                                                <!-- Town -->
                                                                <div class="col-md-4">
                                                                    <label class="form-label fw-bold" style="font-size: small !important;">
                                                                        TOWN/CITY
                                                                    </label>
                                                                    <input type="text" name="town" class="form-control" value="{{ $supplier->town }}">
                                                                </div>

                                                                <!-- Notes -->
                                                                <div class="col-12">
                                                                    <label class="form-label fw-bold" style="font-size: small !important;">
                                                                        ADDITIONAL NOTES
                                                                    </label>
                                                                    <textarea name="notes" class="form-control" rows="3">{{ $supplier->notes }}</textarea>
                                                                </div>
                                                            </div>

                                                            <div class="d-flex justify-content-center mt-4">
                                                                <button type="submit" class="btn btn-success col-8 submit-button">
                                                                    <i class="fas fa-check me-2"></i>UPDATE SUPPLIER
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#datatable').DataTable({
            order: [[0, 'asc']],
            pageLength: 50,
            columnDefs: [
                { orderable: false, targets: 5 }
            ]
        });

        // Form submission handling
        $('.supplier-form').on('submit', function(event) {
            var submitButton = $(this).find('.submit-button');

            setTimeout(function() {
                submitButton.prop('disabled', true);
                submitButton.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
            }, 10);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
