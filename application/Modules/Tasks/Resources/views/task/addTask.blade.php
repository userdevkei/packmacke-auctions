@php
    use App\Helpers\TaskPermissionHelper;
    use App\Helpers\TaskRoleHelper;
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
                            <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span
                                    class="d-none d-sm-inline-block ms-1">New</span></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
{{--                <div class="tab-pane preview-tab-pane active" role="tabpanel"--}}
{{--                     aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494"--}}
{{--                     id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">--}}
                    <form novalidate class="needs-validation" id="userForm" method="POST"
                          action="{{ route('tasks.registerTask') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Global Fields -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="fw-bold fs-6" style="font-size: small !important;">LOCATION</label>
                                <select name="location" class="form-select js-choice" required data-search-enabled="true">
                                    <option selected disabled value="">-- select location --</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->station_id }}">{{ $location->station_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold fs-6" style="font-size: small !important;">DATE</label>
                                <input type="date" name="general_date" class="form-control" required style="height: 62% !important;" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <!-- Tasks Container -->
                        <div id="tasksContainer">
                            <!-- Initial Task -->
                            <div class="task-item border rounded p-3 mb-3" data-task-index="0">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Task #1</h6>
                                    <button type="button" class="btn btn-sm btn-danger remove-task" style="display: none;">
                                        Remove
                                    </button>
                                </div>

                                <div class="row row-cols-4 g-2">
                                    <div class="">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">TASK NAME</label>
                                        <input type="text" name="tasks[0][name]" class="form-control" placeholder="Task name" required style="height: 62% !important;">
                                    </div>

                                    <div class="">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">DEPARTMENT</label>
                                        <select name="tasks[0][department]" class="form-select js-choice" required data-search-enabled="true">
                                            <option selected disabled value="">-- select department --</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">ASSIGNED TO</label>
                                        <select name="tasks[0][assigned_to]" class="form-select js-choice" data-search-enabled="true">
                                            <option selected value="">-- select user --</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->user_id }}">{{ $user->username. ' - '.$user->first_name.' '.$user->surname }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">DEADLINE</label>
                                        <input type="datetime-local" name="tasks[0][deadline]" class="form-control" required style="height: 62% !important;">
                                    </div>

                                    <div class="">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">PRIORITY</label>
                                        <select name="tasks[0][priority]" class="form-select js-choice" required data-search-enabled="true">
                                            <option selected disabled value="">-- select priority --</option>
                                            <option value="1">Critical</option>
                                            <option value="2">Very Urgent</option>
                                            <option value="3">Medium Urgency</option>
                                            <option value="4">Low Urgency</option>
                                        </select>
                                    </div>

                                    <div class="">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">ATTACHMENTS</label>
                                        <input type="file" name="tasks[0][attachments][]" multiple class="form-control" style="height: 50% !important;">
                                    </div>

                                    <div class="col-6">
                                        <label class="fw-bold fs-6" style="font-size: small !important;">DESCRIPTION (OPTIONAL)</label>
                                        <textarea class="form-control" name="tasks[0][description]" placeholder="Task description" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Task Button -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="button" id="addTaskBtn" class="btn btn-primary col-md-2">
                                <i class="bi bi-plus-circle"></i> Add Another Task
                            </button>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" id="submitButton" class="btn btn-success col-md-8">
                                CREATE TASKS
                            </button>
                        </div>
                    </form>

                    <script>
                        let taskCount = 1;

                        document.getElementById('addTaskBtn').addEventListener('click', function() {
                            const container = document.getElementById('tasksContainer');
                            const taskIndex = taskCount;

                            const taskHTML = `
                                <div class="task-item border rounded p-3 mb-3" data-task-index="${taskIndex}">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Task #${taskIndex + 1}</h6>
                                        <button type="button" class="btn btn-sm btn-danger remove-task">
                                            Remove
                                        </button>
                                    </div>

                                    <div class="row row-cols-4 g-3">
                                        <div class="">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">TASK NAME</label>
                                            <input type="text" name="tasks[${taskIndex}][name]" class="form-control" placeholder="Task name" required style="height: 62% !important;">
                                        </div>

                                        <div class="">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">DEPARTMENT</label>
                                            <select name="tasks[${taskIndex}][department]" class="form-select js-choice" required data-search-enabled="true">
                                                <option selected disabled value="">-- select department --</option>
                                                @foreach($departments as $department)
                                                <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                                @endforeach
                                                </select>
                                            </div>

                                            <div class="">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">ASSIGNED TO</label>
                                                <select name="tasks[${taskIndex}][assigned_to]" class="form-select js-choice" data-search-enabled="true">
                                                <option selected value="">-- select user --</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->user_id }}">{{ $user->username. ' - '.$user->first_name.' '.$user->surname }}</option>
                                                @endforeach
                                                </select>
                                            </div>

                                            <div class="">
                                                <label class="fw-bold fs-6" style="font-size: small !important;">DEADLINE</label>
                                                <input type="datetime-local" name="tasks[${taskIndex}][deadline]" class="form-control" required style="height: 62% !important;">
                                        </div>

                                        <div class="">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">PRIORITY</label>
                                            <select name="tasks[${taskIndex}][priority]" class="form-select js-choice" required data-search-enabled="true">
                                                <option selected disabled value="">-- select priority --</option>
                                                <option value="1">Critical</option>
                                                <option value="2">Very Urgent</option>
                                                <option value="3">Medium Urgency</option>
                                                <option value="4">Low Urgency</option>
                                            </select>
                                        </div>

                                        <div class="">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">ATTACHMENTS</label>
                                            <input type="file" name="tasks[${taskIndex}][attachments][]" multiple class="form-control" style="height: 50% !important;">
                                        </div>

                                        <div class="col-6">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">DESCRIPTION (OPTIONAL)</label>
                                            <textarea class="form-control" name="tasks[${taskIndex}][description]" placeholder="Task description" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            `;

                            container.insertAdjacentHTML('beforeend', taskHTML);
                            taskCount++;

                            // Initialize js-choice for newly added selects
                            const newTask = container.querySelector(`.task-item[data-task-index="${taskIndex}"]`);
                            newTask.querySelectorAll('.js-choice').forEach(select => {
                                new Choices(select, {
                                    searchEnabled: true,
                                    itemSelectText: '',
                                    shouldSort: false
                                });
                            });

                            // Show remove button on first task if more than one task
                            if (taskCount > 1) {
                                document.querySelector('.task-item[data-task-index="0"] .remove-task').style.display = 'inline-block';
                            }
                        });

                        // Event delegation for remove buttons
                        document.getElementById('tasksContainer').addEventListener('click', function(e) {
                            if (e.target.classList.contains('remove-task') || e.target.closest('.remove-task')) {
                                const taskItem = e.target.closest('.task-item');
                                taskItem.remove();

                                // Update task numbers
                                document.querySelectorAll('.task-item').forEach((item, index) => {
                                    item.querySelector('h6').textContent = `Task #${index + 1}`;
                                });

                                taskCount--;

                                // Hide remove button on first task if only one left
                                if (taskCount === 1) {
                                    document.querySelector('.task-item[data-task-index="0"] .remove-task').style.display = 'none';
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
{{--    </div>--}}
@endsection
