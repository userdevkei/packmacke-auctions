@php
    use App\Helpers\TaskPermissionHelper;
    use App\Helpers\TaskRoleHelper;
    $role = TaskRoleHelper::role(auth()->user()->user_id);
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}">

@extends('tasks::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">

<style>
    /* Chat Modal Styles - Scoped to avoid conflicts */
    .chat-trigger-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #2563eb;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.2s;
        width: 100%;
        justify-content: center;
    }

    .chat-trigger-btn:hover {
        background-color: #1d4ed8;
    }

    .chat-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .chat-modal.active {
        display: flex !important;
    }

    .chat-modal-content {
        background: white;
        border-radius: 0.5rem;
        width: 90%;
        max-width: 28rem;
        height: 600px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .chat-modal .chat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .chat-modal .chat-header h2 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }

    .chat-modal .close-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        display: flex;
        align-items: center;
        transition: color 0.2s;
    }

    .chat-modal .close-btn:hover {
        color: #374151;
    }

    .chat-modal .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .chat-modal .no-messages {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #9ca3af;
    }

    .chat-modal .no-messages svg {
        margin-bottom: 0.5rem;
    }

    .chat-modal .no-messages p {
        margin: 0.25rem 0;
    }

    .chat-modal .sub-text {
        font-size: 0.875rem;
    }

    .chat-modal .no-messages.hidden {
        display: none;
    }

    .chat-modal .messages-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .chat-modal .message-item {
        display: flex;
        flex-direction: column;
        margin-bottom: 1rem;
    }

    .chat-modal .message-item.own-message {
        align-items: flex-end;
    }

    .chat-modal .message-item.other-message {
        align-items: flex-start;
    }

    .chat-modal .message-user {
        font-size: 0.75rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 0.25rem;
        padding: 0 0.25rem;
    }

    .chat-modal .message-bubble {
        border-radius: 0.5rem;
        padding: 0.75rem;
        max-width: 80%;
        word-wrap: break-word;
    }

    .chat-modal .own-message .message-bubble {
        background-color: #2563eb;
        color: white;
    }

    .chat-modal .other-message .message-bubble {
        background-color: #f3f4f6;
        color: #1f2937;
    }

    .chat-modal .message-text {
        font-size: 0.875rem;
        margin: 0;
        word-wrap: break-word;
    }

    .chat-modal .message-time {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
        padding: 0 0.25rem;
    }

    .chat-modal .chat-input-area {
        display: flex;
        gap: 0.5rem;
        padding: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .chat-modal .message-input {
        flex: 1;
        padding: 0.5rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 14px;
        outline: none;
    }

    .chat-modal .message-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .chat-modal .send-btn {
        background-color: #2563eb;
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: background-color 0.2s;
    }

    .chat-modal .send-btn:hover {
        background-color: #1d4ed8;
    }

    .chat-modal .message-item.own-message {
        align-items: flex-end;
    }

    .chat-modal .message-item.other-message {
        align-items: flex-start;
    }

    .chat-modal .own-message .message-bubble {
        background-color: #2563eb;
        color: white;
    }

    .chat-modal .other-message .message-bubble {
        background-color: #f3f4f6;
        color: #1f2937;
    }
    .chat-modal .own-message .message-bubble {
        border-bottom-right-radius: 0;
    }
    .chat-modal .other-message .message-bubble {
        border-bottom-left-radius: 0;
    }

     .hover-lift:hover {
         transform: translateY(-4px);
         box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
     }
</style>


@section('tasks::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Tasks #{{ $task->task_number }} - {{ $task->task_name }} </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <div class="row mb-3 g-3">
                        <div class="col-lg-12 col-xxl-7">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Task Progress -->
                                        <div class="col-lg-6 border-end-lg border-bottom border-bottom-lg-0 pb-3 pb-lg-0 mb-3">
                                            <div class="d-flex flex-between-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-item icon-item-sm bg-primary-subtle shadow-none me-2">
                                                        <i class="fas fa-tasks text-primary fs-11"></i>
                                                    </div>
                                                    <h6 class="mb-0">Task Progress</h6>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-3">
                                                <h6 class="mb-0 me-2 text-primary">{{ $completed }}/{{ $total_subtasks }}</h6>
                                                <div class="progress w-100" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                         style="width: {{ $percentage }}%"
                                                         aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <span class="ms-2 text-700">{{ $percentage }}%</span>
                                            </div>
                                        </div>

                                        <!-- Task Priority -->
                                        <div class="col-lg-6 border-end-lg border-bottom border-bottom-lg-0 pb-3 pb-lg-0 mb-3">
                                            <div class="d-flex flex-between-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-item icon-item-sm bg-warning-subtle shadow-none me-2">
                                                        <i class="fas fa-exclamation-triangle text-warning fs-11"></i>
                                                    </div>
                                                    <h6 class="mb-0">Priority</h6>
                                                </div>
                                            </div>
                                            <h6 class="mb-0 text-{{ $priorityColor }}">
                                                {{ ucfirst($priority) }}
                                            </h6>
                                        </div>

                                        <!-- Task Due & Status -->
                                        <div class="col-lg-6 border-end-lg border-bottom border-bottom-lg-0 pb-3 pb-lg-0 mb-3">
                                            <div class="d-flex flex-between-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-item icon-item-sm bg-info-subtle shadow-none me-2">
                                                        <i class="fas fa-calendar-day text-info fs-11"></i>
                                                    </div>
                                                    <h6 class="mb-0">Due & Status</h6>
                                                </div>
                                            </div>
                                            <p class="mb-0">
                                                <span class="fw-bold">{{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y H:i') }}</span><br>
                                                <span class="badge bg-{{ $statusColor }}">{{ ucfirst($status) }}</span>
                                            </p>
                                        </div>

                                        <!-- Risk Level -->
                                        <div class="col-lg-6 border-end-lg border-bottom border-bottom-lg-0 pb-3 mb-3">
                                            <div class="d-flex flex-between-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-item icon-item-sm bg-danger-subtle shadow-none me-2">
                                                        <i class="fas fa-bolt text-danger fs-11"></i>
                                                    </div>
                                                    <h6 class="mb-0">Risk Level</h6>
                                                </div>
                                            </div>
                                            <h6 class="mb-0 text-{{ $riskColor }}">{{ ucfirst($riskLevel) }}</h6>
                                            <p class="fs-11 mb-0 text-500">
                                                {{ $riskDescription }}
                                            </p>
                                        </div>

                                        <!-- Location -->
                                        <div class="col-lg-6 border-end-lg border-bottom border-bottom-lg-0 pb-3 pb-lg-0 mb-3">
                                            <div class="d-flex flex-between-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-item icon-item-sm bg-info-subtle shadow-none me-2">
                                                        <i class="fa-solid fa-map-location-dot text-info fs-11"></i>
                                                    </div>
                                                    <h6 class="mb-0">Location</h6>
                                                </div>
                                            </div>
                                            <p class="mb-0">
                                                <span class="fw-bold">{{ $task->location?->station_name }}</span><br>
{{--                                                <span class="badge bg-{{ $statusColor }}">{{ ucfirst($status) }}</span>--}}
                                            </p>
                                        </div>

                                        <!-- Risk Level -->
                                        <div class="col-lg-6 border-end-lg border-bottom border-bottom-lg-0 pb-3 mb-3">
                                            <div class="d-flex flex-between-center mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-item icon-item-sm bg-danger-subtle shadow-none me-2">
                                                        <i class="fas fa-building text-danger fs-11"></i>
                                                    </div>
                                                    <h6 class="mb-0">Department</h6>
                                                </div>
                                            </div>
{{--                                            <h6 class="mb-0 text-{{ $riskColor }}">{{ ucfirst($riskLevel) }}</h6>--}}
                                            <p class="fs-11 mb-0 text-500">
                                                {{ $task->department?->department_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <div class="row flex-between-center">
                                        <div class="col-6 col-sm-auto d-flex align-items-center pe-1">
                                            <h6 class="fs-11 mb-0 text-nowrap py-0 py-xl-0">View/ Update Task Details and Status</h6>
                                        </div>
                                        <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                                            <div id="table-simple-pagination-replace-element">
                                                @if (TaskPermissionHelper::can($role, 'edit_task') && TaskPermissionHelper::canEditTask($task))
                                                    @if(TaskPermissionHelper::can($role, 'modify-task') && $role == 'Admin' || in_array($task->status, [0, 1]))
                                                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop_{{ $task->task_id }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Update</span></a>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="staticBackdrop_{{ $task->task_id }}"
                                         data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1"
                                         aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                        <div class="modal-dialog  @if (TaskPermissionHelper::can($role, 'modify-task')) modal-xl @else modal-lg @endif mt-6" role="document">
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
                                                                    <input type="datetime-local" name="date_due" class="form-control" value="{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d\TH:i') : '' }}" required style="height: 62% !important;">
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
                                </div>
                                <div class="card-body p-4">
                                    <div class="position-relative bg-white rounded-3 shadow-sm border border-light p-4 hover-lift" style="transition: all 0.3s ease;">

                                        <!-- Header: Position Badge -->
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <!-- Avatar -->
                                                <div class="position-relative">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                                         style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 1.25rem;">
                                                        {{ strtoupper(substr($task->assignedTo?->first_name,0,1) . substr($task->assignedTo?->surname,0,1)) }}
                                                    </div>
                                                    <!-- Online Status -->
                                                    <span class="position-absolute bottom-0 end-0 rounded-circle border border-3 border-white bg-success"
                                                          style="width: 16px; height: 16px;"></span>
                                                </div>

                                                <!-- Name & Points -->
                                                <div>
                                                    <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                                                        {{ $task->assignedTo?->first_name.' '.$task->assignedTo?->surname }}
                                                        @if($userStats['position'] <= 3)
                                                            <i class="fas fa-badge-check text-primary" style="font-size: 0.875rem;"></i>
                                                        @endif
                                                    </h5>
                                                    <div class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill"
                                                         style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                                                        <i class="fas fa-trophy text-warning" style="font-size: 0.875rem;"></i>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;">{{ $userStats['points'] }} Points</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Position Badge -->
                                            <div class="text-center">
                                                @if($userStats['position'] === 1)
                                                    <i class="fas fa-crown text-warning mb-1 d-block" style="font-size: 1.5rem;"></i>
                                                @elseif($userStats['position'] === 2)
                                                    <i class="fas fa-medal mb-1 d-block" style="font-size: 1.25rem; color: #C0C0C0;"></i>
                                                @elseif($userStats['position'] === 3)
                                                    <i class="fas fa-medal mb-1 d-block" style="font-size: 1.25rem; color: #CD7F32;"></i>
                                                @else
                                                    <i class="fas fa-star text-primary mb-1 d-block" style="font-size: 1rem;"></i>
                                                @endif
                                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm mx-auto"
                                                     style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                    <div class="text-center" style="line-height: 1;">
                                                        <small class="d-block" style="font-size: 0.625rem;">RANK</small>
                                                        <strong style="font-size: 1.125rem;">#{{ $userStats['position'] }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Tags -->
                                        <div class="d-flex gap-2 mb-3">
                                            <span class="badge bg-light text-dark border d-flex align-items-center gap-1" style="font-weight: 500;">
                                                <i class="fas fa-fire text-danger"></i>
                                                On Fire!
                                            </span>
                                            <span class="badge bg-light text-dark border d-flex align-items-center gap-1" style="font-weight: 500;">
                                                <i class="fas fa-chart-line text-success"></i>
                                                Trending
                                            </span>
                                        </div>

                                        <!-- Bottom Stats -->
                                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                            <div class="d-flex align-items-center gap-2 text-muted">
                                                <i class="fas fa-tasks text-primary"></i>
                                                <small>{{ $userStats['completed_tasks'] }} Tasks Completed</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 text-muted">
                                                <i class="fas fa-clock text-success"></i>
                                                <small>Active</small>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Make sure Font Awesome is included -->
                                <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
                            </div>
                        </div>
                        <div class="col-xxl-5">
                            <div class="card mb-3">
                                <div class="card-header d-flex flex-between-center py-2 border-bottom">
                                    <h6 class="mb-0">Task File/Details</h6>
                                    <div class="dropdown font-sans-serif btn-reveal-trigger">
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="row align-items-center">
                                        <div class="col-md-5 col-xxl-12 mb-xxl-1">
                                            <div class="position-relative">
                                                <p class="fw-bold">Description</p>
                                               <p>{!!  $task->description ?? '<span class="fst-italic fs-sm fw-light">No special details provided</span>' !!}</p>
                                            </div>
                                        </div>
                                        <div class="col-xxl-12 col-md-7">
                                            <p class="fw-bold">Files</p>
                                            <hr class="mx-nx1 mb-0 d-md-none d-xxl-block">
                                            @foreach($files as $file)
                                                <div class="d-flex flex-between-center border-bottom py-3 pt-md-0 pt-xxl-3 align-items-center text-sm-end">
                                                    <a class="link-200 d-flex align-items-center flex-grow-1 text-decoration-none"
                                                       href="{{ route('tasks.viewFile', $file->file_id) }}"
                                                       target="_blank"
                                                       data-bs-toggle="tooltip"
                                                       data-bs-placement="top"
                                                       title="View or download file">
                                                        <h6 class="text-700 mb-0 me-3">{{ Str::limit($file->file_name, 20, '...') }}</h6>
                                                        <h6 class="text-700 mb-0 me-4">{{ $file->type }}</h6>
                                                        <p class="fs-10 text-500 mb-0 fw-semi-bold me-1">{{ $file->surname }}</p>
                                                    </a>
                                                    @if (TaskPermissionHelper::can($role, 'delete_file'))
                                                    <a class="link link-danger ms-3"
                                                       href="{{ route('tasks.deleteFile', $file->file_id) }}"
                                                       onclick="return confirm('Are you sure you want to drop this file?')">
                                                        <span class="fa fa-trash-alt"></span>
                                                    </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Replace your chat section with this -->
                            <div class="card">
                                <div class="card-body">
                                    <button class="chat-trigger-btn" id="openChatBtn">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        Open Chat
                                    </button>
                                </div>
                            </div>

                            <!-- Chat Modal (Place before closing body tag) -->
                            <div class="chat-modal" id="chatModal">
                                <div class="chat-modal-content">
                                    <div class="chat-header">
                                        <h2>Task Discussion</h2>
                                        <button class="close-btn" id="closeBtn">
                                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="messages-container" id="messagesContainer">
                                        <div class="no-messages" id="noMessages">
                                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                            <p>No messages yet</p>
                                            <p class="sub-text">Start the conversation!</p>
                                        </div>
                                        <div class="messages-list" id="messagesList"></div>
                                    </div>

                                    <div class="chat-input-area">
                                        <input
                                            type="text"
                                            class="message-input"
                                            id="messageInput"
                                            placeholder="Type your message..."
                                        >
                                        <button class="send-btn" id="sendBtn">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <script>
                                var chatMessages = [];
                                var chatModal = document.getElementById('chatModal');
                                var openChatBtn = document.getElementById('openChatBtn');
                                var closeBtn = document.getElementById('closeBtn');
                                var sendBtn = document.getElementById('sendBtn');
                                var messageInput = document.getElementById('messageInput');
                                var pollingInterval = null;


                                function openChatModal() {
                                    chatModal.classList.add('active');
                                    messageInput.focus();

                                    // Start polling for new messages every 3 seconds
                                    startPolling();

                                    // ✅ Scroll to bottom after messages render
                                    setTimeout(function() {
                                        const chatContainer = document.getElementById('messagesContainer');
                                        if (chatContainer) {
                                            chatContainer.scrollTo({
                                                top: chatContainer.scrollHeight,
                                                behavior: 'smooth'
                                            });
                                        }
                                    }, 200); // slight delay to allow rendering
                                }

                                function closeChatModal() {
                                    chatModal.classList.remove('active');
                                    // Stop polling when modal is closed
                                    stopPolling()
                                }

                                // Polling functions - Check for new messages every 3 seconds
                                function startPolling() {
                                    // Clear any existing interval
                                    if (pollingInterval) {
                                        clearInterval(pollingInterval);
                                    }

                                    // Fetch immediately when opening
                                    fetchNewMessages();

                                    // Then check every 3 seconds
                                    pollingInterval = setInterval(function() {
                                        fetchNewMessages();
                                    }, 3000);
                                }

                                function stopPolling() {
                                    if (pollingInterval) {
                                        clearInterval(pollingInterval);
                                        pollingInterval = null;
                                    }
                                }

                                function fetchNewMessages() {
                                    fetch('{{ route('tasks.getComments', $task->task_id) }}', {
                                        method: 'GET',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                        }
                                    })
                                        .then(response => {
                                            if (!response.ok) throw new Error('Failed to fetch');
                                            return response.json();
                                        })
                                        .then(data => {
                                            if (data.success && data.comments) {
                                                // ✅ Now we can directly use the API format
                                                let newMessages = data.comments.map(c => ({
                                                    id: c.id,
                                                    text: c.text,
                                                    timestamp: c.timestamp,
                                                    user: c.is_own ? 'You' : c.user, // ✅ Use is_own from backend
                                                    userId: c.user_id,
                                                    isOwn: c.is_own // ✅ Keep this for rendering
                                                }));

                                                // Update chat only if there's a change
                                                if (newMessages.length !== chatMessages.length) {
                                                    chatMessages = newMessages;
                                                    renderChatMessages();
                                                }
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error fetching messages:', error);
                                        });
                                }

                                function sendMessage() {
                                    var messageText = messageInput.value.trim();
                                    if (messageText === '') return;

                                    var now = new Date();
                                    var hours = now.getHours();
                                    var minutes = now.getMinutes();
                                    var ampm = hours >= 12 ? 'PM' : 'AM';
                                    hours = hours % 12;
                                    hours = hours ? hours : 12;
                                    minutes = minutes < 10 ? '0' + minutes : minutes;
                                    var timeString = hours + ':' + minutes + ' ' + ampm;

                                    // ✅ Include current user details
                                    var message = {
                                        id: Date.now(),
                                        text: messageText,
                                        timestamp: timeString,
                                        user: "{{ auth()->user()->surname ?? auth()->user()->name }}",
                                        userId: "{{ auth()->user()->user_id }}",
                                        is_own: true
                                    };

                                    chatMessages.push(message);
                                    renderChatMessages();
                                    messageInput.value = '';
                                    messageInput.focus();

                                    // ✅ Save to DB
                                    saveChatMessage(message);
                                }

                                function handleChatKeyPress(event) {
                                    if (event.key === 'Enter') {
                                        sendMessage();
                                    }
                                }

                                function renderChatMessages() {
                                    var noMessages = document.getElementById('noMessages');
                                    var messagesList = document.getElementById('messagesList');

                                    if (chatMessages.length === 0) {
                                        noMessages.classList.remove('hidden');
                                        messagesList.innerHTML = '';
                                    } else {
                                        noMessages.classList.add('hidden');

                                        var html = '';
                                        for (var i = 0; i < chatMessages.length; i++) {
                                            var msg = chatMessages[i];
                                            var isOwn = msg.is_own || msg.userId === "{{ auth()->user()->user_id }}"; // ✅ support both

                                            html += '<div class="message-item ' + (isOwn ? 'own-message' : 'other-message') + '">';
                                            html += '<div class="message-user">' + (isOwn ? 'You' : escapeHtmlText(msg.user)) + '</div>';
                                            html += '<div class="message-bubble">';
                                            html += '<p class="message-text">' + escapeHtmlText(msg.text) + '</p>';
                                            html += '</div>';
                                            html += '<span class="message-time">' + msg.timestamp + '</span>';
                                            html += '</div>';
                                        }

                                        messagesList.innerHTML = html;

                                        var container = document.getElementById('messagesContainer');
                                        container.scrollTop = container.scrollHeight;
                                    }
                                }

                                function escapeHtmlText(text) {
                                    var div = document.createElement('div');
                                    div.textContent = text;
                                    return div.innerHTML;
                                }

                                function loadExistingChatMessages(existingMessages) {
                                    chatMessages = existingMessages;
                                    renderChatMessages();
                                }

                                function saveChatMessage(message) {
                                    var csrfToken = document.querySelector('meta[name="csrf-token"]');
                                    if (!csrfToken) {
                                        console.warn('CSRF token not found');
                                        return;
                                    }

                                    fetch('{{ route('tasks.storeComment') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken.content
                                        },
                                        body: JSON.stringify({
                                            task_id: "{{ $task->task_id ?? 'null' }}",
                                            message: message.text
                                        })
                                    })
                                        .then(function(response) {
                                            // Handle redirect-to-login if not authenticated
                                            if (response.redirected) {
                                                window.location.href = response.url;
                                                return;
                                            }
                                            return response.json();
                                        })
                                        .then(function(data) {
                                            if (data && data.success) {
                                                console.log('Message saved:', data);
                                                // Optional: replace temporary ID with DB ID
                                                var index = chatMessages.findIndex(m => m.id === message.id);
                                                if (index !== -1) {
                                                    chatMessages[index].id = data.data.task_comment_id;
                                                }
                                            } else {
                                                console.warn('Message not saved:', data);
                                            }
                                        })
                                        .catch(function(error) {
                                            console.error('Error saving message:', error);
                                        });
                                }

                                // Event listeners
                                if (openChatBtn) {
                                    openChatBtn.addEventListener('click', openChatModal);
                                }

                                if (closeBtn) {
                                    closeBtn.addEventListener('click', closeChatModal);
                                }

                                if (sendBtn) {
                                    sendBtn.addEventListener('click', sendMessage);
                                }

                                if (messageInput) {
                                    messageInput.addEventListener('keypress', handleChatKeyPress);
                                }

                                if (chatModal) {
                                    chatModal.addEventListener('click', function(event) {
                                        if (event.target === chatModal) {
                                            closeChatModal();
                                        }
                                    });
                                }

                                var existingComments = @json($comments ?? []);
                                if (existingComments.length > 0) {
                                    loadExistingChatMessages(existingComments.map(function(c) {
                                        return {
                                            id: c.id,
                                            text: c.text,
                                            timestamp: c.timestamp,
                                            user: c.user,
                                            userId: c.user_id,
                                        };
                                    }));
                                }

                                $('#chatModal').on('shown.bs.modal', function () {
                                    const chatContainer = document.getElementById('messagesContainer');
                                    if (chatContainer) {
                                        chatContainer.scrollTo({
                                            top: chatContainer.scrollHeight,
                                            behavior: 'smooth'
                                        });
                                    }
                                });

                            </script>
                        </div>
                    </div>
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
    });
</script>
