<style>
     .blurred-amount {
        filter: blur(5px); /* Adjust blur intensity */
        pointer-events: none; /* Prevent interaction */
    }
</style>
<div class="card">
    <div class="card-body overflow-hidden p-lg-6">
        <div class="row align-items-center">
{{--            <div class="row ps-lg-4 my-5 text-center text-lg-start">--}}
                <div class="row g-3 mb-3">
                    <div class="col-xxl-6 col-lg-12">
                        <div class="card h-100">
                            <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-3.png') }});">
                            </div>
                            <!--/.bg-holder-->

                            <div class="card-header z-1">
                                <h5 class="text-primary">Welcome {{ auth()->user()->user->surname }}! </h5>
                                <h6 class="text-600">Here are some quick links for you to start </h6>
                            </div>
                            <div class="card-body z-1">
                                <div class="row g-2 h-100 align-items-end">
                                    <div class="col-sm-6 col-md-5">
                                        <div class="d-flex position-relative">
                                            <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fa fa-user text-primary"></span></div>
                                            <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                    <h6 class="mb-0">Role</h6>
                                                </a>
                                                <p class="mb-0 fs-11 text-500 fs-sm">{{ ucwords(strtolower(auth()->user()->role->role_name)) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-5">
                                        <div class="d-flex position-relative">
                                            <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fas fa-calendar text-warning"></span></div>
                                            <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                    <h6 class="mb-0">Active Since</h6>
                                                </a>
                                                <p class="mb-0 fs-11 text-500">{{ Carbon\Carbon::parse(auth()->user()->created_at)->format('D, d/m/y H:i') }} </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-5">
                                        <div class="d-flex position-relative">
                                            <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fa-solid fa-location-pin-lock text-success"></span></div>
                                            <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                    <h6 class="mb-0">Location</h6>
                                                </a>
                                                <p class="mb-0 fs-11 text-500">{{ auth()->user()->station->location->location_name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-5">
                                        <div class="d-flex position-relative">
                                            <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fa-solid fa-warehouse text-danger"></span></div>
                                            <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                    <h6 class="mb-0">Warehouse</h6>
                                                </a>
                                                <p class="mb-0 fs-11 text-500">{{ auth()->user()->station->station_name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex flex-between-center">
                                <h5 class="mb-0">Income</h5><a class="btn btn-link btn-sm px-0" href="#!">Report<span class="fas fa-chevron-right ms-1 fs-11"> </span></a>
                            </div>
                            <div class="card-body">
                                <p class="fs-10 text-600 ">Total Invoices : <span @if(auth()->user()->role_id != 7) class="blurred-amount"> @endif> {{ number_format($totalIncome->total_invoiced, 2) }} </span></p>
                                <p class="fs-10 text-600">Total Collection : <span @if(auth()->user()->role_id != 7) class="blurred-amount"> @endif> {{ number_format($totalPaid->total_paid, 2) }} </span></p>
                                <div class="progress mb-3 rounded-pill" style="height: 6px;" role="progressbar" aria-valuenow="43.72" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-progress-gradient rounded-pill"
                                         style="width: {{ ($totalPaid && $totalIncome && $totalIncome->total_invoiced > 0) ? number_format(($totalPaid->total_paid/$totalIncome->total_invoiced) * 100, 2) . '%' : '0%' }};">
                                    </div>
                                </div>
                                <p class="mb-0 text-primary">Period Between</p>
                                <p class="mb-0 fs-11 text-500">{{ \Carbon\Carbon::parse($fy->year_starting)->format('M d Y') }} to {{ \Carbon\Carbon::parse($fy->year_ending)->format('M d Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-3 col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex flex-between-center">
                                <h5 class="mb-0">Expenses</h5><a class="btn btn-link btn-sm px-0" href="#!">Report<span class="fas fa-chevron-right ms-1 fs-11"> </span></a>
                            </div>
                            <div class="card-body">
                                <p class="fs-10 text-600">Total Purchase : <span @if(auth()->user()->role_id != 7) class="blurred-amount"> @endif> {{ number_format($totalExpense->total_expensed, 2) }} </span></p>
                                <p class="fs-10 text-600">Total Settled : <span @if(auth()->user()->role_id != 7) class="blurred-amount"> @endif> {{ number_format($totalSettled->total_settled, 2) }} </span></p>
                                <div class="progress mb-3 rounded-pill" style="height: 6px;" role="progressbar" aria-valuenow="43.72" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-progress-gradient rounded-pill" style="width: {{ $totalExpense->total_expensed != 0 ? number_format(($totalSettled->total_settled / $totalExpense->total_expensed) * 100, 2) : '0.00' }}%"></div>
                                </div>
                                <p class="mb-0 text-primary">Period Between</p>
                                <p class="mb-0 fs-11 text-500">{{ \Carbon\Carbon::parse($fy->year_starting)->format('M d Y') }} to {{ \Carbon\Carbon::parse($fy->year_ending)->format('M d Y') }}</p>
                            </div>
                        </div>
                    </div>

                </div>
            <div class="row g-3 mb-3">
                <div class="col-xxl-6 col-lg-12">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-1.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Accounts<span class="badge badge-subtle-warning rounded-pill ms-2">-0.23%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-warning" data-countup='{"endValue":{{ $accounts->count() }},"decimalPlaces":2}'>0</div><a class="fw-semi-bold fs-10 text-nowrap" href="#">All Accounts<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Group Ledgers<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" data-countup='{"endValue":{{ $group->count() }},"decimalPlaces":2}'>0</div><a class="fw-semi-bold fs-10 text-nowrap" href="#">All Grouped Ledgers<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-1.png') }});">
                                        </div>
                                        <!--/.bg-holder-->
                                        <div class="card-body position-relative">
                                            <h6>Income Streams<span class="badge badge-subtle-warning rounded-pill ms-2">-0.23%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-warning" data-countup='{"endValue":{{ $incomes->count() }},"decimalPlaces":2}'>0</div><a class="fw-semi-bold fs-10 text-nowrap" href="#">All Income Streams<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Expenses<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" data-countup='{"endValue":{{ $expenses->count() }},"decimalPlaces":2}'>0</div><a class="fw-semi-bold fs-10 text-nowrap" href="#">All Expenses<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header pb-0">
                                    <div class="row flex-between-center">
                                        <div class="col-auto">
                                        </div>
                                        <div class="col-auto d-flex">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body py-0">
                                    <!-- Find the JS file for the following chart at: src/js/charts/echarts/report-for-this-week.js-->
                                {{-- @if (auth()->user()->role_id == 7) --}}
                                    <div class="echart" id="barChart" style="height: 410px;"></div>
                                {{-- @endif --}}

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-lg-6">
                    <div class="card h-100">
{{--                        <div class="card-header d-flex flex-between-center">--}}
{{--                            <h6 class="mb-0">Project Statistics</h6>--}}
{{--                            <div class="dropdown font-sans-serif btn-reveal-trigger">--}}
{{--                                <button class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button" id="dropdown-project-statistics" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>--}}
{{--                                <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-project-statistics"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>--}}
{{--                                    <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
                        <div class="card-body pt-0">
                            <div class="row mb-2">
                                {{--<div class="col-6 border-end border-200">
                                    <h4 class="mb-0">{{ $clients->count() }} </h4>
                                    <p class="fs-10 text-600 mb-0">All Clients</p>
                                </div>
                                <div class="col-3 border-end text-center border-200">
                                    <h4 class="fs-9 mb-0">{{ $stocks->count() }}</h4>
                                    <p class="fs-10 text-600 mb-0">Clients (stocked) </p>
                                </div>
                                <div class="col-3 text-center">
                                    <h4 class="fs-9 mb-0">{{ $clients->count() - $stocks->count() }} </h4>
                                    <p class="fs-10 text-600 mb-0">Clients (out of stock)</p>
                                </div>--}}
                            </div>
                            <p class="fs-10 mb-2 mt-3">Top Income Streams</p>

                            <div class="d-flex justify-content-between">
                                <h6 class="col-7">Income Stream Name</h6>
                                <h6 class="col-2 text-right" style="text-align: center !important;" >Frequency </h6>
                                <h6 class="col-3 text-right" style="text-align: center !important;" >Total Income</h6>
                            </div>

                            <div id="top-income-streams"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{--<div class="echart-bar-report-for-this-week" id="barChart" style="height: 400px;"></div>--}}
{{--<a class="btn btn-sm btn-danger" href="{{ route('accounts.updateTaxRecords') }}">update tax</a>--}}

<script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>

<script>
    // Initialize the chart
    const chartDom = document.getElementById('barChart');
    const myChart = echarts.init(chartDom);

    // Function to fetch data from server and update chart
    function fetchAndUpdateChart() {
        const userRoleId = {{ auth()->user()->role_id }};
        $.ajax({
            url: '{{ route('accounts.fetchMonthlyIncomesExpenses') }}',  // The route you created to fetch data
            method: 'GET',
            success: function(response) {
                // Prepare data for the chart
               /* const labels = response.labels;
                const incomes = response.incomes.toFixed(2);
                const expenses = response.expenses.toFixed(2);*/

                const labels = response.labels;
                const incomes = response.incomes.map(val => parseFloat(val).toFixed(2));
                const expenses = response.expenses.map(val => parseFloat(val).toFixed(2));

                const option = {
                    title: {
                        text: 'Monthly Incomes vs Expenses',
                        left: 'center'
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data: ['Income', 'Expense'],
                        bottom: 0
                    },
                    xAxis: {
                        type: 'category',
                        data: labels
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            name: 'Income',
                            type: 'bar',
                            data: incomes,
                            itemStyle: {
                                color: '#4CAF50' // Green color for income
                            }
                        },
                        {
                            name: 'Expense',
                            type: 'bar',
                            data: expenses,
                            itemStyle: {
                                color: '#F44336' // Red color for expense
                            }
                        }
                    ]
                };

                if(userRoleId == 7){
                    // Update the chart with the new data
                    myChart.setOption(option);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching data: ', error);
            }
        });
    }

    // Fetch and update the chart on page load
    fetchAndUpdateChart();

    // Optional: Set up an interval to auto-refresh data every minute (or adjust as needed)
    setInterval(fetchAndUpdateChart, 60000);  // Refresh every 60 seconds (60000ms)

    function fetchTopIncomeStreams() {
        const userRoleId = {{ auth()->user()->role_id }};
        $.ajax({
            url: '{{ route('accounts.fetchTopIncomeStreams') }}', // Replace with your API endpoint
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const topIncomeContainer = $('#top-income-streams');

                // Clear any existing content
                topIncomeContainer.empty();

                // Loop through the first 7 items in the data and add them to the DOM
                data.slice(0, 10).forEach((stock, index) => {
                    // Generate color class based on index
                    let colorClass = '';
                    switch(index) {
                        case 0: colorClass = 'text-primary'; break;
                        case 1: colorClass = 'text-warning'; break;
                        case 2: colorClass = 'text-secondary'; break;
                        case 3: colorClass = 'text-info'; break;
                        case 4: colorClass = 'text-dark'; break;
                        case 5: colorClass = 'text-danger'; break;
                        case 6: colorClass = 'text-success'; break;
                        default: colorClass = 'text-muted'; break;
                    }

                    // Generate the HTML for each item
                    const itemHTML = `
                          <div class="d-flex flex-between-center rounded-3 bg-body-tertiary p-3 mb-2">
                             <h6 class="mb-0 col-7 text-capitalize">
                                <span class="fas fa-circle fs-10 me-3 ${colorClass}"></span>
                                ${stock.client_account_name}
                            </h6>
                            <a class="fs-11 text-600 mb-0 col-2" style="text-align: right !important;" href="#!">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(stock.frequency)}</a>
                            <a class="fs-11 text-600 mb-0 col-3" href="#!" style="text-align: right !important;" >
                                 <span class="${userRoleId !== 7 ? 'blurred-amount' : ''}">
                                    ${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(stock.total_income)}
                                </span>
                            </a>
                        </div>
                    `;

                    // Append the item to the container
                    topIncomeContainer.append(itemHTML);
                });
            },
            error: function(xhr, status, error) {
                console.error('Error fetching data:', error);
            }
        });
    }
    fetchTopIncomeStreams();

    setInterval(fetchTopIncomeStreams, 60000)

    // Call the function to fetch and display the data
    // $(document).ready(function() {
    //     fetchTopIncomeStreams();
    // });

</script>
