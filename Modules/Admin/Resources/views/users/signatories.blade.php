@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Departmental Signatories</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New </span></a>
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
                                    <h5 class="mb-1" id="staticBackdropLabel">CREATE SIGNATORY</h5>
                                </div>
                                <div class="p-4">
                                    <form id="userForm" method="POST" action="{{ route('admin.storeSignatory') }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row row-cols-sm-1 g-2">
                                            <div class="mb-1">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">SIGNATORY NAME</label>
                                                <select class="form-control js-choice" name="signatory" required>
                                                    <option value="" selected disabled>-- select user --</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->user_id }}">{{ $user->surname.' '.$user->first_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-1">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">DEPARTMENT NAME</label>
                                                <select class="form-control js-choice" name="department" required>
                                                    <option value="" selected disabled>-- select department --</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-1">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">UPLOAD SIGNATURE</label>
                                                <input type="file" name="signature" class="form-control" placeholder="--" required accept=".png,.jpg,.jpeg">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center mt-2">
                                            <button type="submit" id="submitButton" class="btn btn-success col-8">SAVE SIGNATORY </button>
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
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Signatory Name</th>
                            <th>Department Name</th>
                            <th>Signature </th>
                            <th>Created By </th>
                            <th>Status </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($signatories as $signatory)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $signatory->full_name }} </td>
                                <td> {{ $signatory->department_name }} </td>
                                <td> {{ $signatory->signature }} </td>
                                <td> {{ $signatory->username }} </td>
                                <td> {!! $signatory->status == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Active</span>' !!} </td>
                                <td nowrap="">
                                    <a class="text-info" data-bs-toggle="modal" data-bs-target="#staticBackdrop_{{ $signatory->signatory_id }}"><span class="fa-regular fa-pen-to-square"></span><span class="d-none d-sm-inline-block ms-1"></span></a>

                                    <a class="text-danger mx-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Disable User Account" onclick="return confirm('Are you sure you want to delete this department?')" href="{{ route('admin.deleteSignatory', $signatory->signatory_id) }}"> <span class="fa fa-trash-alt"></span> </a>

                                    <div class="modal fade" id="staticBackdrop_{{ $signatory->signatory_id }}" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg mt-6" role="document">
                                            <div class="modal-content border-0">
                                                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                        <h5 class="mb-1" id="staticBackdropLabel">UPDATE SIGNATORY</h5>
                                                    </div>
                                                    <div class="p-4">
                                                        <form id="userForm" method="POST" action="{{ route('admin.updateSignatory', $signatory->signatory_id) }}" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="row row-cols-sm-1 g-2">
                                                                <div class="mb-1">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">SIGNATORY NAME</label>
                                                                    <select class="form-control js-choice" name="signatory" required>
                                                                        <option value="" selected disabled>-- select user --</option>
                                                                        @foreach($users as $user)
                                                                            <option @selected($signatory->user_id == $user->user_id) value="{{ $user->user_id }}">{{ $user->surname.' '.$user->first_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-1">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">DEPARTMENT NAME</label>
                                                                    <select class="form-control js-choice" name="department" required>
                                                                        <option value="" selected disabled>-- select department --</option>
                                                                        @foreach($departments as $department)
                                                                            <option @selected($signatory->department_id == $department->department_id) value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-1">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">UPLOAD SIGNATURE</label>
                                                                    <input type="file" name="signature" class="form-control" placeholder="--" accept=".png,.jpg,.jpeg">
                                                                </div>

                                                                <div class="mb-1">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">STATUS</label>
                                                                    <select name="status" class="form-select js-choice" required>
                                                                        <option @if($signatory->status == 1) selected @endif value="1">ACTIVE </option>
                                                                        <option @if($signatory->status == 2) selected @endif value="2">INACTIVE</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-center mt-1">
                                                                <button type="submit" id="submitButton" class="btn btn-success col-8">UPDATE DEPARTMENT</button>
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
