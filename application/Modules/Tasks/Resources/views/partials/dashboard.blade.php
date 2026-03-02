{{--<div class="container-fluid">--}}
{{--    --}}{{-- Header Section --}}
{{--    <div class="row mb-3">--}}
{{--        <div class="col-12">--}}
{{--            <h5 class="mb-1">Dashboard Overview</h5>--}}
{{--            <p class="text-muted">--}}
{{--                @if($role === 'Admin')--}}
{{--                    System-wide statistics and performance metrics--}}
{{--                @elseif($role === 'Supervisor')--}}
{{--                    {{ $department ?? 'Department' }} Performance Overview--}}
{{--                @else--}}
{{--                    Your Personal Task Statistics--}}
{{--                @endif--}}
{{--            </p>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    --}}{{-- Today's Key Metrics Row --}}
{{--    <div class="row g-3 mb-3">--}}
{{--        --}}{{-- Today's Tasks --}}
{{--        <div class="col-md-6 col-xxl-3">--}}
{{--            <div class="card h-md-100">--}}
{{--                <div class="card-header pb-0">--}}
{{--                    <h6 class="mb-0 mt-2 d-flex align-items-center">--}}
{{--                        Today's Tasks--}}
{{--                        <span class="ms-1 text-400" data-bs-toggle="tooltip" title="Tasks with due date today">--}}
{{--                            <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>--}}
{{--                        </span>--}}
{{--                    </h6>--}}
{{--                </div>--}}
{{--                <div class="card-body d-flex flex-column justify-content-end">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col">--}}
{{--                            <p class="font-sans-serif lh-1 mb-1 fs-4">{{ $stats['today_tasks'] ?? 0 }}</p>--}}
{{--                            <span class="badge badge-soft-{{ ($stats['today_tasks_change'] ?? 0) >= 0 ? 'success' : 'danger' }} rounded-pill fs--2">--}}
{{--                                {{ ($stats['today_tasks_change'] ?? 0) >= 0 ? '+' : '' }}{{ $stats['today_tasks_change'] ?? 0 }}%--}}
{{--                            </span>--}}
{{--                        </div>--}}
{{--                        <div class="col-auto ps-0">--}}
{{--                            <div class="fs--2 text-500">--}}
{{--                                <div>Due Today</div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        --}}{{-- Completed Today --}}
{{--        <div class="col-md-6 col-xxl-3">--}}
{{--            <div class="card h-md-100">--}}
{{--                <div class="card-header pb-0">--}}
{{--                    <h6 class="mb-0 mt-2">Completed Today</h6>--}}
{{--                </div>--}}
{{--                <div class="card-body d-flex flex-column justify-content-end">--}}
{{--                    <div class="row justify-content-between">--}}
{{--                        <div class="col-auto align-self-end">--}}
{{--                            <div class="fs-4 fw-normal font-sans-serif text-700 lh-1 mb-1">{{ $stats['completed_today'] ?? 0 }}</div>--}}
{{--                            <span class="badge rounded-pill fs--2 bg-200 text-success">--}}
{{--                                <span class="fas fa-check me-1"></span>{{ $stats['completion_rate'] ?? 0 }}% rate--}}
{{--                            </span>--}}
{{--                        </div>--}}
{{--                        <div class="col-auto ps-0 mt-n4">--}}
{{--                            <canvas id="completedChart" height="50"></canvas>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        --}}{{-- Pending Today --}}
{{--        <div class="col-md-6 col-xxl-3">--}}
{{--            <div class="card h-md-100">--}}
{{--                <div class="card-header pb-0">--}}
{{--                    <h6 class="mb-0 mt-2">Pending Today</h6>--}}
{{--                </div>--}}
{{--                <div class="card-body d-flex flex-column justify-content-end">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col">--}}
{{--                            <p class="font-sans-serif lh-1 mb-1 fs-4">{{ $stats['pending_today'] ?? 0 }}</p>--}}
{{--                            <span class="badge badge-soft-warning rounded-pill fs--2">--}}
{{--                                {{ $stats['pending_percentage'] ?? 0 }}% of total--}}
{{--                            </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        --}}{{-- At Risk Tasks --}}
{{--        <div class="col-md-6 col-xxl-3">--}}
{{--            <div class="card h-md-100 {{ ($stats['at_risk'] ?? 0) > 0 ? 'border-danger' : '' }}">--}}
{{--                <div class="card-header pb-0">--}}
{{--                    <h6 class="mb-0 mt-2 text-danger">At Risk</h6>--}}
{{--                </div>--}}
{{--                <div class="card-body d-flex flex-column justify-content-end">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col">--}}
{{--                            <p class="font-sans-serif lh-1 mb-1 fs-4 text-danger">{{ $stats['at_risk'] ?? 0 }}</p>--}}
{{--                            <span class="badge badge-soft-danger rounded-pill fs--2">--}}
{{--                                <span class="fas fa-exclamation-triangle me-1"></span>Urgent attention--}}
{{--                            </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    --}}{{-- Quick Insights --}}
{{--    <div class="row g-0 mt-3">--}}
{{--        <div class="col-lg-6 col-xl-5 col-xxl-4 mb-3 ps-lg-2">--}}
{{--            <div class="card h-lg-100">--}}
{{--                <div class="card-header bg-light">--}}
{{--                    <h6 class="mb-0">Quick Insights</h6>--}}
{{--                </div>--}}
{{--                <div class="card-body">--}}
{{--                    <div class="d-flex align-items-start mb-3">--}}
{{--                        <div class="flex-shrink-0">--}}
{{--                            <span class="fas fa-chart-line text-success fs-2"></span>--}}
{{--                        </div>--}}
{{--                        <div class="flex-grow-1 ms-3">--}}
{{--                            <h6 class="mb-1">Productivity Trend</h6>--}}
{{--                            <p class="fs--1 mb-0 text-500">--}}
{{--                                {{ $insights['productivity_trend'] ?? 0 }}%--}}
{{--                                {{ $insights['productivity_direction'] ?? 'neutral' }} from last period--}}
{{--                            </p>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="d-flex align-items-start mb-3">--}}
{{--                        <div class="flex-shrink-0">--}}
{{--                            <span class="fas fa-clock text-info fs-2"></span>--}}
{{--                        </div>--}}
{{--                        <div class="flex-grow-1 ms-3">--}}
{{--                            <h6 class="mb-1">Average Completion Time</h6>--}}
{{--                            <p class="fs--1 mb-0 text-500">--}}
{{--                                {{ $insights['avg_completion_time'] ?? 0 }} hours per task--}}
{{--                            </p>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="d-flex align-items-start">--}}
{{--                        <div class="flex-shrink-0">--}}
{{--                            <span class="fas fa-trophy text-warning fs-2"></span>--}}
{{--                        </div>--}}
{{--                        <div class="flex-grow-1 ms-3">--}}
{{--                            <h6 class="mb-1">Your Ranking</h6>--}}
{{--                            <p class="fs--1 mb-0 text-500">--}}
{{--                                @if($role === null)--}}
{{--                                    You're ranked #{{ $insights['user_rank'] ?? 'N/A' }} out of {{ $insights['total_users'] ?? 0 }}--}}
{{--                                @else--}}
{{--                                    Team average performance: {{ $insights['team_performance'] ?? 0 }}%--}}
{{--                                @endif--}}
{{--                            </p>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    --}}{{-- Task Distribution Chart --}}
{{--    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>--}}
{{--    <script>--}}
{{--        document.addEventListener('DOMContentLoaded', function() {--}}
{{--            const ctx = document.getElementById('taskDistributionChart');--}}
{{--            if (ctx) {--}}
{{--                new Chart(ctx, {--}}
{{--                    type: 'line',--}}
{{--                    data: {--}}
{{--                        labels: {!! json_encode($chartData['labels'] ?? []) !!},--}}
{{--                        datasets: [--}}
{{--                            {--}}
{{--                                label: 'Completed',--}}
{{--                                data: {!! json_encode($chartData['completed'] ?? []) !!},--}}
{{--                                borderColor: 'rgb(75, 192, 192)',--}}
{{--                                backgroundColor: 'rgba(75, 192, 192, 0.1)',--}}
{{--                                tension: 0.4--}}
{{--                            },--}}
{{--                            {--}}
{{--                                label: 'Pending',--}}
{{--                                data: {!! json_encode($chartData['pending'] ?? []) !!},--}}
{{--                                borderColor: 'rgb(255, 205, 86)',--}}
{{--                                backgroundColor: 'rgba(255, 205, 86, 0.1)',--}}
{{--                                tension: 0.4--}}
{{--                            },--}}
{{--                            {--}}
{{--                                label: 'At Risk',--}}
{{--                                data: {!! json_encode($chartData['at_risk'] ?? []) !!},--}}
{{--                                borderColor: 'rgb(255, 99, 132)',--}}
{{--                                backgroundColor: 'rgba(255, 99, 132, 0.1)',--}}
{{--                                tension: 0.4--}}
{{--                            }--}}
{{--                        ]--}}
{{--                    },--}}
{{--                    options: {--}}
{{--                        responsive: true,--}}
{{--                        maintainAspectRatio: false,--}}
{{--                        plugins: {--}}
{{--                            legend: { position: 'top' }--}}
{{--                        },--}}
{{--                        scales: {--}}
{{--                            y: { beginAtZero: true }--}}
{{--                        }--}}
{{--                    }--}}
{{--                });--}}
{{--            }--}}

{{--            // Initialize tooltips--}}
{{--            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));--}}
{{--            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));--}}
{{--        });--}}
{{--    </script>--}}
{{--</div>--}}

<div class="container-fluid">

    {{-- Header Section --}}
    <div class="row mb-3">
        <div class="col-12">
            <h5 class="mb-1">Dashboard Overview</h5>
            <p class="text-muted">
                @if($role === 'Admin')
                    System-wide statistics and performance metrics
                @elseif($role === 'Supervisor')
                    {{ $department ?? 'Department' }} Performance Overview
                @else
                    Your Personal Task Statistics
                @endif
            </p>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="row g-3 mb-3">
        {{-- Total Tasks --}}
        <div class="col-md-6 col-xxl-3">
            <div class="card h-md-100 border-start border-info border-3">
                <div class="card-body">
                    <h6 class="text-muted">Total Tasks</h6>
                    <h4 class="fw-semibold">{{ $stats['total_tasks'] ?? 0 }}</h4>
                    <small class="text-500">Across all categories</small>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="col-md-6 col-xxl-3">
            <div class="card h-md-100 border-start border-success border-3">
                <div class="card-body">
                    <h6 class="text-muted">Completed Tasks</h6>
                    <h4 class="fw-semibold text-success">{{ $stats['completed_count'] ?? 0 }}</h4>
                    <small class="text-500">{{ $stats['completion_rate'] ?? 0 }}% completion rate</small>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="col-md-6 col-xxl-3">
            <div class="card h-md-100 border-start border-warning border-3">
                <div class="card-body">
                    <h6 class="text-muted">Pending Tasks</h6>
                    <h4 class="fw-semibold text-warning">{{ $stats['pending_today'] ?? 0 }}</h4>
                    <small class="text-500">{{ $stats['pending_percentage'] ?? 0 }}% of total</small>
                </div>
            </div>
        </div>

        {{-- At Risk --}}
        <div class="col-md-6 col-xxl-3">
            <div class="card h-md-100 border-start border-danger border-3">
                <div class="card-body">
                    <h6 class="text-muted text-danger">At Risk</h6>
                    <h4 class="fw-semibold text-danger">{{ $stats['at_risk'] ?? 0 }}</h4>
                    <small class="text-500">Requires urgent attention</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Insights Row --}}
    <div class="row g-3 mb-3">
        {{-- Task Trend Chart --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Task Performance Trend</h6>
                </div>
                <div class="card-body" style="height: 350px;">
                    <canvas id="taskDistributionChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Quick Insights --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Quick Insights</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <span class="fas fa-chart-line text-success fs-2 me-3"></span>
                        <div>
                            <h6>Productivity Trend</h6>
                            <p class="mb-0 small text-500">
                                {{ $insights['productivity_trend'] ?? 0 }}% {{ $insights['productivity_direction'] ?? 'neutral' }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <span class="fas fa-clock text-info fs-2 me-3"></span>
                        <div>
                            <h6>Average Completion Time</h6>
                            <p class="mb-0 small text-500">{{ $insights['avg_completion_time'] ?? 0 }} hrs</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <span class="fas fa-trophy text-warning fs-2 me-3"></span>
                        <div>
                            <h6>
                                {{ $role === 'user' ? 'Your Ranking' : 'Team Performance' }}
                            </h6>
                            <p class="mb-0 small text-500">
                                @if($role === 'user')
                                    #{{ $insights['user_rank'] ?? 'N/A' }} / {{ $insights['total_users'] ?? 0 }}
                                @else
                                    {{ $insights['team_performance'] ?? 0 }}% average
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(in_array($role, ['Admin', 'Supervisor']))
    {{-- Recent Tasks --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between">
                    <h6 class="mb-0">Today's Tasks</h6>
                    <a href="{{ route('tasks.all') }}" class="small text-primary">View all</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="text-muted">
                            <tr>
                                <th>Task Date</th>
                                <th>Task Number</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Status</th>

                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tasks ?? [] as $task)
                                <tr>
                                    <td>{{ $task['task_date'] ?? 'N/A' }}</td>
                                    <td>{{ $task['id'] ?? 'N/A' }}</td>
                                    <td>{{ $task['title'] ?? 'N/A' }}</td>
                                    <td>{{ $task['department'] ?? 'N/A' }}</td>
                                    <td>{{ $task['assignedTo'] ?? 'Not Assigned' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($task['due_date'])->format('M d, Y H:i') }}</td>
                                    <td>
                                            <span class="badge bg-{{ $task['priority_color'] ?? 'secondary' }}">
                                                {{ $task['priority'] ?? '-' }}
                                            </span>
                                    </td>
                                    <td>
                                            <span class="badge bg-{{ $task['status_color'] ?? 'secondary' }}">
                                                {{ $task['status'] ?? 'Unknown' }}
                                            </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No tasks available</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-3">

    @if(in_array($role, ['Admin', 'Supervisor']))
    {{-- Top Performers & Department Stats --}}
        {{-- Top Performers --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Top Performers</h6>
                    </div>
                    <div class="card-body">
                        @forelse($topPerformers ?? [] as $performer)
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ $performer['avatar_url'] ?? asset('assets/img/team/avatar.png') }}"
                                     alt="avatar" class="rounded-circle me-3" width="40" height="40">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $performer['name'] ?? 'N/A' }}</h6>
                                    <small class="text-muted">
                                        {{ json_decode($performer['role'] ?? '{}')->role_name ?? 'N/A' }} |
                                        {{ $performer['department'] ?? 'N/A' }}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">{{ $performer['score'] ?? 0 }} pts</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small">No performer data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Department Performance --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Department Performance</h6>
                    </div>
                    <div class="card-body">
                        @forelse($departmentStats ?? [] as $dept)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-0">{{ $dept['name'] ?? 'N/A' }}</h6>
                                    <small class="text-muted">
                                        {{ $dept['completed_tasks'] ?? 0 }} completed |
                                        Avg {{ $dept['avg_completion_hours'] ?? 0 }} hrs
                                    </small>
                                </div>
                                <span class="badge bg-{{ $dept['performance_color'] ?? 'secondary' }}">
                                    {{ $dept['performance_percentage'] ?? 0 }}%
                                </span>
                            </div>
                        @empty
                            <p class="text-muted small">No department data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">My Performance</h6>
{{--                        <span class="text-muted small">Last 30 Days</span>--}}
                    </div>
                    <div class="card-body">
                        @if(!empty($myOverallPosition))
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <h4 class="mb-1 text-primary">#{{ $myOverallPosition['position'] ?? 'N/A' }}</h4>
                                    <small class="text-muted">Overall Position</small>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-1">{{ $myOverallPosition['points'] ?? 0 }} pts</h4>
                                    <small class="text-muted">Total Points</small>
                                </div>
                            </div>

                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ min(($myOverallPosition['points'] / 100) * 100, 100) }}%"
                                     aria-valuenow="{{ $myOverallPosition['points'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <p class="text-muted small mt-2">
                                Points are earned by completing tasks before deadline (5 points each).
                            </p>
                        @else
                            <p class="text-muted small">No performance data available yet.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">My Pending Tasks</h6>
                        <span class="text-muted small">{{ count($myPendingTasks ?? []) }} tasks</span>
                    </div>
                    <div class="card-body">
                        @php
                            $priorities = [
                                '1' => 'Critical',
                                '2' => 'Very Urgent',
                                '3' => 'Medium Urgency',
                                '4' => 'Low Urgency',
                            ];
                        @endphp
                        @forelse($myPendingTasks as $task)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong>{{ $task->task_name }}</strong><br>
                                    <small class="text-muted">
                                        Due: {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('M d, Y') : 'No deadline' }}
                                    </small>
                                </div>
                                <span class="badge bg-warning text-dark"> {{ $priorities[$task->priority] ?? 'Normal' }}</span>
                            </div>
                        @empty
                            <p class="text-muted small">You have no pending tasks 🎉</p>
                        @endforelse
                    </div>
                </div>
            </div>


        @endif
    </div>

</div>

{{-- ChartJS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('taskDistributionChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels'] ?? []) !!},
                    datasets: [
                        {
                            label: 'Completed',
                            data: {!! json_encode($chartData['completed'] ?? []) !!},
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Pending',
                            data: {!! json_encode($chartData['pending'] ?? []) !!},
                            borderColor: 'rgb(255, 205, 86)',
                            backgroundColor: 'rgba(255, 205, 86, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'At Risk',
                            data: {!! json_encode($chartData['at_risk'] ?? []) !!},
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    });
</script>
