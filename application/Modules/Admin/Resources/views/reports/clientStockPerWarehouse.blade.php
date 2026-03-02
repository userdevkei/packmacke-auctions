@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Stocked at {{ $stocks[0]->stocked_at }} </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Report</span></a>
                    </div>
                </div>

{{--                <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">--}}
{{--                    <div class="modal-dialog modal-lg mt-6" role="document">--}}
{{--                        <div class="modal-content border-0">--}}
{{--                            <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">--}}
{{--                                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>--}}
{{--                            </div>--}}
{{--                            <div class="modal-body p-0">--}}
{{--                                <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">--}}
{{--                                    <h5 class="mb-1" id="staticBackdropLabel">CREATE A CUSTOM REPORT</h5>--}}
{{--                                </div>--}}
{{--                                <div class="p-4">--}}

{{--                                    <form method="POST" action="{{ route('admin.downloadStockAgingReport') }}">--}}
{{--                                        @csrf--}}
{{--                                        <div class="row row-cols-sm-1 g-2">--}}
{{--                                            <div class="mb-4">--}}
{{--                                                <label class="fw-bold fs-6" style="font-size: small !important;">CLIENT NAME</label>--}}
{{--                                                <select name="clientId" id="clientId" class="form-select js-choice" style="height: 61% !important;">--}}
{{--                                                    <option selected disabled value="">-- select client account --</option>--}}
{{--                                                    @foreach($clients as $client)--}}
{{--                                                        <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}

{{--                                            <div class="mb-4">--}}
{{--                                                <label class="fw-bold fs-6" style="font-size: small !important;">REPORT FORMAT</label>--}}
{{--                                                <select name="reportType" class="form-select js-choice">--}}
{{--                                                    <option value="" selected>-- select report format --</option>--}}
{{--                                                    <option value="1">PDF DOCUMENT</option>--}}
{{--                                                    <option value="2">EXCEL DOCUMENT</option>--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="d-flex justify-content-center mt-1">--}}
{{--                                            <button type="submit" class="btn btn-success col-8">DOWNLOAD REPORT </button>--}}
{{--                                        </div>--}}
{{--                                    </form>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th> Invoice Number </th>
                            <th> Lot Number </th>
                            <th> TCI Number </th>
                            <th> Packages </th>
                            <th> Net Weight </th>
                            <th> Bay Name </th>
                            <th> Date Received </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($stocks as $request)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $request->client_name }}</td>
                                <td> {{ $request->invoice_number }}</td>
                                <td> {{ $request->lot_number }}</td>
                                <td> {{ $request->loading_number }}</td>
                                <td> {{ number_format($request->current_stock, 0) }}</td>
                                <td> {{ number_format($request->current_weight, 2) }}</td>
                                <td> {{ $request->bay_name }} </td>
                                <td> {{ \Carbon\Carbon::createFromTimestamp($request->date_received)->format('d/m/y') }} </td>
                                <td>
                                    <a class="link text-secondary m-2" data-bs-toggle="tooltip" data-bs-placement="left" title="View Clients Stock Analysis" href="{{ route('admin.clientStock',$request->client_id) }}"> <span class="fas fa-folder-open"> </span> </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                    <div class="d-flex justify-content-around mt-5">
                        <div id="warehouseStats" style="width: 50vw !important; height: 500px;"></div>
                        <div id="bayStats" style="width: 50vw !important; height: 500px;"></div>
                    </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>


<script>
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 25
        });
    });

    // Correct usage of stationId (camelCase)
    const stationId = @json($stocks[0]->station_id);
    const byWarehouse = @json(route('admin.getClientStockData', ['id' => '__ID__'])).replace('__ID__', stationId);
    const byBay = @json(route('admin.getStockDataByBay', ['id' => '__ID__'])).replace('__ID__', stationId);

    // Fetch data from API
    fetch(byWarehouse) // Use stationId here
        .then(response => response.json())
        .then(data => {
            const chartDom = document.getElementById('warehouseStats');
            const myChart = echarts.init(chartDom);

            // Prepare data for the pie chart
            const chartData = data.map(item => ({
                name: item.client_name,
                value: item.total_weight, // or item.total_weight
            }));

            const option = {
                title: {
                    text: 'Stock Distribution By Client',
                    left: 'center',
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)',
                },
                legend: {
                    orient: 'vertical',
                    left: 'right', // Move legend to the right of the chart
                    top: 'middle', // Align legend vertically in the middle
                },
                series: [
                    {
                        name: 'Client (Weight)',
                        type: 'pie',
                        radius: '70%', // Keep the pie chart style with increased radius
                        avoidLabelOverlap: true, // Automatically adjust labels to prevent overlap
                        label: {
                            show: true,
                            position: 'outside', // Move labels outside the pie segments
                            formatter: '{b}: {d}%', // Show label name and percentage
                            fontSize: 12, // Reduce font size to minimize clutter
                        },
                        labelLine: {
                            show: true, // Display lines connecting labels to pie segments
                            length: 15, // Extend lines to reduce overlap
                            length2: 15, // Second segment of the label line
                            smooth: true, // Smooth the line for better aesthetics
                        },
                        data: chartData,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)',
                            },
                        },
                    },
                ],
            };

// Set options and render the chart
            myChart.setOption(option);
        })
        .catch(error => console.error('Error fetching data:', error));


    fetch(byBay) // Use stationId here
        .then(response => response.json())
        .then(data => {
            const chartDom = document.getElementById('bayStats');
            const myChart = echarts.init(chartDom);

            // Prepare data for the pie chart
            const chartData = data.map(item => ({
                name: item.bay_name,
                value: item.total_weight, // or item.total_weight
            }));

            const option = {
                title: {
                    text: 'Stock Distribution By Bay',
                    left: 'center',
                },
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)',
                },
                legend: {
                    orient: 'vertical',
                    left: 'right', // Move legend to the right of the chart
                    top: 'middle', // Align legend vertically in the middle
                },
                series: [
                    {
                        name: 'Bay Name (Weight)',
                        type: 'pie',
                        radius: '70%', // Keep the pie chart style with increased radius
                        avoidLabelOverlap: true, // Automatically adjust labels to prevent overlap
                        label: {
                            show: true,
                            position: 'outside', // Move labels outside the pie segments
                            formatter: '{b}: {d}%', // Show label name and percentage
                            fontSize: 12, // Reduce font size to minimize clutter
                        },
                        labelLine: {
                            show: true, // Display lines connecting labels to pie segments
                            length: 15, // Extend lines to reduce overlap
                            length2: 15, // Second segment of the label line
                            smooth: true, // Smooth the line for better aesthetics
                        },
                        data: chartData,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)',
                            },
                        },
                    },
                ],
            };

            myChart.setOption(option);
        })
        .catch(error => console.error('Error fetching data:', error));

</script>

