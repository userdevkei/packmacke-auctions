@php
    use App\Helpers\TaskPermissionHelper;
    use App\Helpers\TaskRoleHelper;use Carbon\Carbon;
    $role = TaskRoleHelper::role(auth()->user()->user_id);
@endphp
@extends('tasks::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('tasks::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Tasks </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @if (TaskPermissionHelper::can($role, 'create_task'))
                            <a class="btn btn-falcon-default btn-sm" href="{{ route('tasks.addTasks') }}"><span
                                    class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">New</span></a>
                        @endif
                    </div>
                </div>

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel"
                     aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494"
                     id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Task Number</th>
                            <th>Task Name</th>
                            <th>Department</th>
                            <th>Assigned To</th>
                            <th>Assigned By</th>
                            <th>Due Date/Time</th>
                            <th>Created By</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tasks as $task)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ Carbon::createFromTimestamp($task->task_date)->format('d-m-Y') }} </td>
                                <td> {{ $task->task_number }} </td>
                                <td> {{ $task->task_name }} </td>
                                <td> {{ $task->department->department_name }} </td>
                                <td> {{ $task->assignedTo == null ? 'Not Assigned' : ucwords(strtolower($task->assignedTo->first_name.' '.$task->assignedTo->surname)) }} </td>
                                <td> {{ $task->assignedBy == null ? 'Not Assigned' : ucwords(strtolower($task->assignedBy->first_name.' '.$task->assignedBy->surname)) }} </td>
                                <td> {{ Carbon::parse($task->due_date)->format('d-m-Y H:i') }} </td>
                                <td> {{ ucwords(strtolower($task->creator->first_name.' '.$task->creator->surname)) }} </td>
                                <td>
                                    {!! $task->priority == 1 ? '<span class="badge bg-danger">Critical</span>' : ($task->priority == 2 ? '<span class="badge bg-warning">Very Urgent</span>' : ($task->priority == 3 ? '<span class="badge bg-info">Medium Urgency</span>' : '<span class="badge bg-dark">Low Urgency</span>')) !!}
                                </td>
                                <td> {!! $task->status == 0 ? '<span class="badge bg-info"> Pending </span>' : ($task->status == 1 ? '<span class="badge bg-warning"> In progress </span>' : ( $task->status == 2 ?  '<span class="badge bg-success"> Completed </span>' : '<span class="badge bg-danger"> Canceled </span>')) !!} </td>
                                <td nowrap="">
                                    <a class="text-secondary mx-1" data-bs-toggle="tooltip" data-bs-placement="left"
                                       title="View task details" href="{{ route('tasks.viewTask', $task->task_id) }}">
                                        <span class="fa fa-eye"></span> </a>
                                    @if (TaskPermissionHelper::can($role, 'edit_task') && TaskPermissionHelper::canEditTask($task))
                                        @if(in_array($task->status, [2,3], true) && $role !== 'Admin')
                                            {!! $task->status == 2 ? '<span class="fa fa-check-double text-success"></span>' : '<span class="fa fa-ban text-danger"></span>' !!}
                                        @else
                                            <a class="text-info" data-bs-toggle="modal"
                                               data-bs-target="#staticBackdrop_{{ $task->task_id }}">
                                                <span class="fa-regular fa-pen-to-square"></span>
                                                <span class="d-none d-sm-inline-block ms-1"></span>
                                            </a>
                                        @endif
                                    @else
                                        <i class="fa fa-ban text-warning"></i>
                                    @endif


                                    @if (TaskPermissionHelper::can($role, 'delete_task'))
                                        <a class="text-danger mx-1" data-bs-toggle="tooltip" data-bs-placement="left"
                                           title="Disable User Account"
                                           onclick="return confirm('Are you sure you want to delete this task?')"
                                           href="{{ route('tasks.deleteTask', $task->task_id) }}"> <span
                                                class="fa fa-trash-alt"></span> </a>
                                    @endif

                                    <div class="modal fade" id="staticBackdrop_{{ $task->task_id }}"
                                         data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1"
                                         aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog  @if(TaskPermissionHelper::can($role, 'modify-task')) modal-xl @else modal-lg @endif mt-6" role="document">
                                            <div class="modal-content border-0">
                                                <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                                                    <button
                                                        class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base"
                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                                        <h5 class="mb-1" id="staticBackdropLabel">UPDATE
                                                            #{{ $task->task_number }} - {{ $task->task_name }}</h5>
                                                    </div>
                                                    <div class="p-4">
                                                        <form id="userForm" method="POST" novalidate class="needs-validation"
                                                              action="{{ route('tasks.updateTask', $task->task_id) }}"
                                                              enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="row @if (TaskPermissionHelper::can($role, 'modify-task')) row-cols-sm-2 @else row-cols-sm-1 @endif g-2">
                                                                @if (TaskPermissionHelper::can($role, 'modify-task'))
                                                                    <div class="mb-4">
                                                                        <label class="fw-bold fs-6" style="font-size: small !important;">LOCATION</label>
                                                                        <select name="location" class="form-select js-choice" style="height: 61% !important;" required>
                                                                            <option selected disabled value="">-- select location -- </option>
                                                                            @foreach($locations as $location)
                                                                                <option
                                                                                    @selected($task->station_id == $location->station_id) value="{{ $location->station_id }}">{{ $location->station_name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label class="fw-bold fs-6" style="font-size: small !important;">DEPARTMENT</label>
                                                                        <select name="department" class="form-select js-choice" style="height: 61% !important;" required>
                                                                            <option selected disabled value="">-- select department -- </option>
                                                                            @foreach($departments as $department)
                                                                                <option
                                                                                    @selected($task->department_id == $department->department_id) value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label class="fw-bold fs-6" style="font-size: small !important;">TASK NAME</label>
                                                                        <input type="text" name="task" class="form-control" placeholder="Task name" value="{{ $task->task_name }}" required style="height: 67% !important;">
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label class="fw-bold fs-6" style="font-size: small !important;">ASSIGN TO</label>
                                                                        <select name="assign_to" class="form-select js-choice" style="height: 61% !important;">
                                                                            <option selected value="">-- select user -- </option>
                                                                            @foreach($users as $user)
                                                                                <option
                                                                                    @selected($task->assigned_to == $user->user_id) value="{{ $user->user_id }}">{{ $user->username.' - '.$user->first_name.' '.$user->surname }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label class="fw-bold fs-6" style="font-size: small !important;">PRIORITY</label>
                                                                        <select name="priority" class="form-select js-choice" style="height: 61% !important;" required>
                                                                            <option selected disabled value="">-- select priority -- </option>
                                                                            <option @selected($task->priority == 1) value="1"> Critical </option>
                                                                            <option @selected($task->priority == 2) value="2"> Very Urgent </option>
                                                                            <option @selected($task->priority == 3) value="3"> Medium Urgency </option>
                                                                            <option @selected($task->priority == 4) value="4"> Low Urgency </option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label class="fw-bold fs-6" style="font-size: small !important;">DUE DATE AND TIME</label>
                                                                        <input type="datetime-local" name="date_due" class="form-control" value="{{ $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d\TH:i') : '' }}" required style="height: 62% !important;">
                                                                    </div>
                                                                @endif

                                                                <div class="mb-4">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">Status</label>
                                                                    <select name="status" class="form-select js-choice" style="height: 61% !important;" required>
                                                                        <option selected disabled value="">-- select priority -- </option>
                                                                        <option @selected($task->status == 0) value="0"> Pending </option>
                                                                        <option @selected($task->status == 1) value="1"> In Progress </option>
                                                                        <option @selected($task->status == 2) value="2"> Completed </option>
                                                                        <option @selected($task->status == 3) value="3"> Canceled </option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-4">
                                                                    <label class="fw-bold fs-6" style="font-size: small !important;">TASK ATTACHMENTS</label> <input type="file" name="attachments[]" multiple class="form-control" style="height: 67% !important;">
                                                                </div>
                                                                    @if (TaskPermissionHelper::can($role, 'modify-task'))
                                                                        <div class="mb-4 col-md-12">
                                                                            <label class="fw-bold fs-6" style="font-size: small !important;">TASK DESCRIPTION (OPTIONAL) </label>
                                                                            <textarea class="form-control" name="description" placeholder="description">{{ $task->description }}</textarea>
                                                                        </div>
                                                                    @endif
                                                            </div>
                                                            <div class="d-flex justify-content-center mt-1">
                                                                <button type="submit" id="submitButton"
                                                                        class="btn btn-success col-8">UPDATE TASK
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
    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $('#userForm').on('submit', function (event) {
            // event.preventDefault(); // Prevents the default form submission

            var form = $(this);
            var submitButton = $('#submitButton');

            // Simulate form submission process
            setTimeout(function () {
                // Assuming the form submission is successful, disable the button
                submitButton.prop('disabled', true);

                // You can also display a success message or perform other actions here
                // alert('Form submitted successfully!');
            }, 10); // Simulate a delay for the form submission process
        });
    });
</script>
