@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Inventory Items</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @canuser('inventoryItem.add')
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Item</span></a>
                        @endcanuser
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg mt-6" role="document">
                <div class="modal-content border-0">
                    <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                            <h5 class="mb-1" id="staticBackdropLabel">Create New Category</h5>
                        </div>
                        <div class="p-4">
                            <form id="userForm" method="POST" action="{{ route('inventory.inventoryItemStore') }}">
                                @csrf
                                <div class="row row-cols-sm-1 g-2">
                                    <div class="mb-2">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">Item Name</label>
                                        <input type="text" name="item_name" class="form-control form-control-lg" placeholder="--">
                                    </div>
                                    <div class="mb-2">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">Category</label>
                                        <select name="category_id" class="form-select js-choice" style="height: 61% !important;">
                                            <option selected disabled value="">-- select --</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">Unit of Measure</label>
                                        <select name="unit" class="form-select js-choice" style="height: 61% !important;">
                                            <option selected disabled value="">-- select --</option>
                                            @foreach($unit as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">Status</label>
                                        <select name="status" class="form-select js-choice" style="height: 61% !important;">
                                            <option selected disabled value="">-- select --</option>
                                            @foreach($status as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <div class="d-flex justify-content-center mt-1">
                                    <button type="submit" id="submitButton" class="btn btn-success col-8">CREATE ITEM </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Category </th>
                            <th>Units </th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $item->item_name }} </td>
                                <td> {{ $item->category?->category_name }} </td>
                                <td> {{ $item->unit_label }} </td>
                                <td> {!! $item->status == 'active' ? '<span class="badge bg-success"> Active </span>' : '<span class="badge bg-danger"> Inactive </span>' !!} </td>
                                <td nowrap="">
                                    @canuser('inventory.editItem')
                                    <a class="text-info" data-bs-toggle="modal" data-bs-target="#staticBackdrop_{{ $item->id }}"><span class="fa-regular fa-pen-to-square"></span><span class="d-none d-sm-inline-block ms-1"></span></a>
                                    <div class="modal fade" id="staticBackdrop_{{ $item->id }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg mt-6" role="document">
                                            <div class="modal-content border-0">
                                                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                        <h5 class="mb-1" id="staticBackdropLabel">Update Item - {{ $item->item_name }}</h5>
                                                    </div>
                                                    <div class="p-4">
                                                        <form id="userForm" method="POST" action="{{ route('inventory.inventoryItemUpdate', $item->id) }}">
                                                            @csrf
                                                            <div class="row row-cols-sm-1 g-2">
                                                                <div class="mb-2">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">Item Name</label>
                                                                    <input type="text" name="item_name" class="form-control" value="{{ $item->item_name }}" placeholder="--">
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">Category</label>
                                                                    <select name="category_id" class="form-select js-choice" style="height: 61% !important;">
                                                                        <option selected disabled value="">-- select --</option>
                                                                        @foreach($categories as $category)
                                                                            <option @selected($category->id == $item->category_id) value="{{ $category->id }}">{{ $category->category_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">Unit of Measure</label>
                                                                    <select name="unit" class="form-select js-choice" style="height: 61% !important;">
                                                                        <option selected disabled value="">-- select --</option>
                                                                        @foreach($unit as $value => $label)
                                                                            <option @selected($value == $item->unit) value="{{ $value }}">{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div class="mb-2">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">Status</label>
                                                                    <select name="status" class="form-select js-choice" style="height: 61% !important;">
                                                                        <option selected disabled value="">-- select --</option>
                                                                        @foreach($status as $value => $label)
                                                                            <option @selected($value == $item->status) value="{{ $value }}">{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                            </div>

                                                            <div class="d-flex justify-content-center mt-1">
                                                                <button type="submit" id="submitButton" class="btn btn-success col-8">UPDATE ITEM </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endcanuser
                                    @canuser('inventory.deleteItem')
                                    <a class="text-danger mx-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Delete Category" onclick="return confirm('Are you sure you want to delete this item?')" href="{{ route('inventory.inventoryItemDelete', $item->id) }}"> <span class="fa fa-trash"></span> </a>
                                    @endcanuser
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
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $('#userForm').on('submit', function(event) {
            // event.preventDefault(); // Prevents the default form submission

            var form = $(this);
            var submitButton = $('#submitButton');

            // Simulate form submission process
            setTimeout(function() {
                // Assuming the form submission is successful, disable the button
                submitButton.prop('disabled', true);

                // You can also display a success message or perform other actions here
                // alert('Form submitted successfully!');
            }, 10); // Simulate a delay for the form submission process
        });
    });
</script>
