@extends('account::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
        width: 100%;
        max-width: 1400px;
    }

    h1 {
        color: #333;
        margin-bottom: 30px;
        font-size: 28px;
        text-align: center;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 150px;
    }

    label {
        font-size: 14px;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
    }

    input[type="date"],
    input[type="text"],
    select {
        padding: 7px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
        background: white;
        color: #333;
        width: 100%;
    }

    input[type="date"]:focus,
    input[type="text"]:focus,
    select:focus {
        outline: none;
        border-color: #0632f5;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* Optional: Placeholder styling for text inputs */
    input[type="text"]::placeholder {
        color: #999;
        opacity: 1;
    }

    button {
        padding: 7px 24px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .btn-reset {
        padding: 7px 24px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
        white-space: nowrap;
        text-decoration: none !important;
    }

    .btn-filter {
        color: white;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
    }

    .btn-export {
        color: white;
    }

    .btn-export:hover {
        transform: translateY(-2px);
    }

    button:active {
        transform: translateY(0);
    }

    @media (max-width: 1024px) {
        .filter-form {
            gap: 12px;
        }

        .form-group {
            min-width: 140px;
        }

        button {
            padding: 12px 20px;
            font-size: 14px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 30px 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .filter-form {
            gap: 10px;
        }

        .form-group {
            min-width: 120px;
        }

        input[type="date"],
        select {
            padding: 10px 12px;
            font-size: 14px;
        }

        button {
            padding: 10px 18px;
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .form-group {
            min-width: 100%;
        }

        button {
            width: 100%;
        }
    }
</style>
@section('account::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Receive LPOs</h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        @canuser('inventory.addLpo')
                        <a class="btn btn-falcon-default btn-sm" href="{{ route('create.purchases') }}"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">Receive LPO</span></a>
                        @endcanuser
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="mb-3">
                <form method="POST" class="filter-form" id="filterForm">
                @csrf
                    <div class="form-group">
                        <label for="dateFrom">Date From</label>
                        <input type="date" id="dateFrom" name="dateFrom" value="{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') : '' }}">
                    </div>
                    <div class="form-group">
                        <label for="dateTo">Date To</label>
                        <input type="date" id="dateTo" name="dateTo" value="{{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('Y-m-d') : '' }}">
                    </div>

                    <div class="form-group">
                    <label for="client">Client</label>
                    <select id="client" name="client">
                        <option value="">Select Client</option>
                        @foreach($clients as $clientOption)
                            <option value="{{ $clientOption->client_id }}" @selected($client == $clientOption->client_id)>
                                {{ $clientOption->client_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="item">LPO Number</label>
                    <input type="text" name="lpo" id="lpo" value="{{ $lpo ?? old('lpo') }}" placeholder="lpo number">
                </div>

                <button type="submit" class="btn-filter btn-info">Filter</button>
                <a href="{{ route('lpos.view') }}" class="btn-reset btn-danger">Reset</a>
                <button type="submit" name="export" value="1" class="btn-export btn-secondary">Export</button>
                </form>
            </div>
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>Purchase Number</th>
                            <th>LPO Number</th>
                            <th>Client Name </th>
                            <th>Supplier </th>
                            <th>Date Received </th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($lpos as $lpo)
                            <tr>
                                <td> {{ $loop->iteration }} </td>
                                <td> {{ $lpo->purchase_order_number }} </td>
                                <td> {{ $lpo->lpo_number }} </td>
                                <td> {{ $lpo->client_name }} </td>
                                <td> {{ $lpo->supplier_name }} </td>
                                <td> {{ $lpo->date }} </td>
                                <td class="text-center align-middle">
                                    @php
                                        $statuses = [
                                            'pending'   => ['label' => 'Pending',   'class' => 'bg-warning'],
                                            'received'  => ['label' => 'Received',  'class' => 'bg-success'],
                                            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-danger'],
                                        ];

                                        $current = $statuses[$lpo->status] ?? ['label' => 'Unknown', 'class' => 'bg-secondary'];
                                    @endphp

                                    <div class="dropdown position-absolute mx-2">
                                            <span class="badge {{ $current['class'] }} dropdown-toggle"
                                                  role="button"
                                                  data-bs-toggle="dropdown"
                                                  data-bs-display="static"
                                                  aria-expanded="false"
                                                  style="cursor:pointer">
                                                {{ $current['label'] }}
                                            </span>
                                        <ul class="dropdown-menu dropdown-menu-start shadow mt-1">
                                            @foreach($statuses as $key => $status)
                                                @if($key !== $lpo->status)
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="#"
                                                           onclick="updateLpoStatus('{{ $lpo->id }}','{{ $key }}'); return false;">
                                                            {{ $status['label'] }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                                <td nowrap="">
                                    <a class="link link-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Edit LPO" href="{{ route('purchases.edit', $lpo->id) }}"><span class="fa-regular fa-pen-to-square"></span><span class="d-none d-sm-inline-block ms-1"></span></a>
                                    @canuser('inventory.deleteLpo')
                                    <a class="text-danger mx-1" data-bs-toggle="tooltip" data-bs-placement="left" title="Delete Category" onclick="return confirm('Are you sure you want to delete this LPO?')" href="{{ route('purchases.delete', $lpo->id) }}"> <span class="fa fa-trash"></span> </a>
                                    @endcanuser

                                    <a class="link link-dark mx-2" href="{{ route('purchases.download', $lpo->id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="Download LPO" target="_blank"><span class="fas fa-file-pdf text-danger"></span> </a>
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
    const lpoStatusUrl = "{{ route('purchases.lpoUpdateStatus', ['id' => '__ID__']) }}";

    // Define function in global scope
    function updateLpoStatus(lpoId, status) {
        const url = lpoStatusUrl.replace('__ID__', lpoId);

        // Get CSRF token from meta tag or Laravel's global variable
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="_token"]')?.value ||
            '{{ csrf_token() }}';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ status })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Show success toast
                    toastr.options = {
                        "closeButton": true,
                        "progressBar": true,
                        "closeDuration": 10000
                    };
                    toastr.success(data.message);

                    // Optional: reload after short delay
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.options = {
                        "closeButton": true,
                        "progressBar": true,
                        "closeDuration": 10000
                    };
                    toastr.error(data.message || 'Failed to update status');
                }
            })
            .catch(err => {
                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                    "closeDuration": 10000
                };
                toastr.error('Network error');
            });
    }

    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
