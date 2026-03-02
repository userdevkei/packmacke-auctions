@php use Illuminate\Support\Facades\DB; @endphp
@extends('clerk::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 0.5rem;
        border: none;
    }

    .card-header {
        background-color: #2c3e50;
        color: white;
        padding: 0.5rem 0.75rem;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header i {
        margin-right: 0.5rem;
    }

    .info-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1rem;
    }

    .info-row {
        display: flex;
        margin-bottom: 0.75rem;
    }

    .info-label {
        font-weight: 600;
        color: #088a19;
        min-width: 160px;
    }

    .info-value {
        color: #1e69b4;
        flex: 1;
    }

    .status-badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .status-collected {
        background-color: #0ae33b;
        color: white;
    }

    .status-under-collection {
        background-color: #ffc107;
        color: #212529;
    }

    .status-processing {
        background-color: #17a2b8;
        color: white;
    }

    .action-btn {
        margin-left: 0.5rem;
        float: right !important;
    }

    table {
        font-size: 13px !important;
        font-family: 'Inter', 'Roboto', 'Segoe UI', 'Helvetica Neue', sans-serif !important;
    }

    .card {
        font-size: 13px !important;
        font-family: 'Inter', 'Roboto', 'Segoe UI', 'Helvetica Neue', sans-serif !important;
    }

    @media (max-width: 768px) {
        .info-row {
            flex-direction: column;
        }

        .info-label {
            margin-bottom: 0.25rem;
            min-width: auto;
        }
    }
</style>

@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Tea Tracking </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel"
                     aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494"
                     id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <div class="card">
                        <div class="card-header">
                            @if(auth()->user()->role_id == 2)
                                @if($teaDetails['deliveryOrder'])
                                    <div>
                                        <a class="btn btn-sm btn-primary action-btn"
                                           href="{{ route('clerk.getDoToEdit', $teaDetails['deliveryOrder']->delivery_id) }}">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        @if($teaDetails['deliveryOrder']->do_status < 2 || $teaDetails['deliveryOrder']->do_status == null)
                                            <a class="btn btn-sm btn-danger action-btn"
                                               onclick="return confirm('Are you sure you want to delete this delivery order?')"
                                               href="{{ route('clerk.getDoToDelete', $teaDetails['deliveryOrder']->delivery_id) }}">
                                                <i class="fas fa-trash-alt me-1"></i>Delete
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                        <div class="card-body">
                            @if($teaDetails['deliveryOrder'])
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="info-card">
                                            <h6><i class="fas fa-truck"></i> Delivery Order Overview</h6>
                                            <div class="info-row">
                                                <span class="info-label">Invoice Number:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->invoice_number ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Sale Number:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->sale_number ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Lot Number:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->lot_number ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Sale Date:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->sale_date ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Prompt Date:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->prompt_date ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="info-card">
                                            <h6><i class="fas fa-info"></i> Delivery Order Info</h6>
                                            <div class="info-row">
                                                <span class="info-label">Date Added:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->created_at }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Order Number:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->order_number }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Status: </span>
                                                <span class="info-value">
                                    @php
                                        $order = $teaDetails['deliveryOrder'];

                                        // Default logic
                                        if ($order->delivery_type != 'DO Entry') {
                                            $status = 'collected';
                                        } else {
                                            $status = $order->collection ?? 'in_hand';
                                        }

                                        $map = [
                                            'in_hand'          => ['label' => 'DO In Hand',           'class' => 'bg-warning'],
                                            'collected'        => ['label' => 'DO Collected',         'class' => 'bg-success'],
                                            'under_collection' => ['label' => 'DO Under Collection',  'class' => 'bg-info'],
                                        ];

                                        $key = strtolower($status);

                                        $badge = $map[$key] ?? [
                                            'label' => Str::title(str_replace(['-', '_'], ' ', $status)),
                                            'class' => 'bg-secondary'
                                        ];
                                    @endphp

                                <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Created By:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->created_by }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="info-card">
                                            <h6><i class="fas fa-user-tie me-2"></i>Client Information</h6>
                                            <div class="info-row">
                                                <span class="info-label">Client Name:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->client_name }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Broker:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->broker_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Tea Origin:</span>
                                                <span class="info-value">{{ $teaDetails['deliveryOrder']->tea_type ?? 'Local Tea' }}
                                                    @php
                                                        $received  = $teaDetails['deliveryOrder']->received;
                                                        $validated = $teaDetails['deliveryOrder']->validated;
                                                    @endphp

                                                    @if(in_array($teaDetails['deliveryOrder']->tea_type, ['EPZ', 'Foreign']))
                                                        @if ($received == 'received' && $validated == 'validated')
                                                            <span class="badge bg-success">Entry Received & Validated</span>

                                                        @elseif ($received == 'received' && $validated == 'not_validated')
                                                            <span class="badge bg-info">Entry Received, Not Validated</span>

                                                        @else
                                                            <span class="badge bg-danger">Entry Not Received & Not Validated</span>
                                                        @endif

                                                    @endif

                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="info-card">
                                            <h6><i class="fas fa-leaf me-2"></i>Tea Information</h6>
                                            <div class="info-row">
                                                <span class="info-label">Garden:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->garden_name }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Grade:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->grade_name }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Tea Type:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->tea_type }}</span>
                                            </div>

                                            <div class="info-row">
                                                <span class="info-label">Production Date:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->production_date }}</span>
                                            </div>

                                            <div class="info-row">
                                                <span class="info-label">Expiry Date:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->expiry_date }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="info-card">
                                            <h6><i class="fas fa-boxes me-2"></i>Package Details</h6>
                                            <div class="info-row">
                                                <span class="info-label">Packages:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->packet }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Weight:</span>
                                                <span
                                                    class="info-value">{{ number_format($teaDetails['deliveryOrder']->weight, 2) }} kg</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Package Type:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->package }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Pallete Height:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->height ?? 'Not recorded' }} FT </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="info-card">
                                            <h6><i class="fas fa-warehouse me-2"></i>Warehouse Details</h6>
                                            <div class="info-row">
                                                <span class="info-label">Warehouse:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->warehouse_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Sub-Warehouse:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->sub_warehouse_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Locality:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['deliveryOrder']->locality ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>No delivery order data found
                                </div>
                            @endif
                        </div>
                    </div>
                    <!-- Tea Collection Information (TCI) Card -->
                    @if($teaDetails['tciDetails'])
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-truck-loading"></i>Tea Collection Information (TCI)
                            </div>
                            <div class="card-body">
                                <div class="info-card">
                                    <div class="info-row">
                                        <span class="info-label">Loading Number:</span>
                                        <span class="info-value">{{ $teaDetails['tciDetails']->loading_number }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value">

                                    @php
                                        $status = $teaDetails['tciDetails']->collection;

                                        $map = [
                                            'in_hand'          => ['label' => 'DO In Hand',        'class' => 'bg-warning'],
                                            'collected'        => ['label' => 'Do Collected',         'class' => 'bg-success'],
                                            'under_collection' => ['label' => 'Do Under Collection',  'class' => 'bg-info'],
                                        ];

                                        if (is_null($status)) {
                                            $badge = $map['in_hand'];
                                        } else {
                                            $key = strtolower($status);
                                            $badge = $map[$key] ?? [
                                                'label' => Str::title(str_replace(['-', '_'], ' ', $status)),
                                                'class' => 'bg-secondary'
                                            ];
                                        }
                                    @endphp

                                <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Delivery Number:</span>
                                        <span class="info-value">
                                            {{ $teaDetails['tciDetails']->delivery_number }} |
                                            @if($teaDetails['tciDetails'] && $teaDetails['tciDetails']->path)
                                                <a class="link link-danger" href="{{ route('clerk.downloadDeliveryNote', base64_encode($teaDetails['tciDetails']->delivery_number)) }}" target="_blank">
                                                    <i class="fa fa-file"></i> download
                                                </a>
                                            @endif
                                        </span>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h6><i class="fas fa-truck me-2"></i>Transporter Details</h6>
                                            <div class="info-row">
                                                <span class="info-label">Transporter:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['tciDetails']->transporter_name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h6><i class="fas fa-user-tie me-2"></i>Driver Details</h6>
                                            <div class="info-row">
                                                <span class="info-label">Driver Name:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['tciDetails']->driver_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">ID Number:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['tciDetails']->id_number ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Phone:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['tciDetails']->phone ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-card">
                                    <div class="info-row">
                                        <span class="info-label">Created By:</span>
                                        <span class="info-value">{{ $teaDetails['tciDetails']->created_by }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Created At:</span>
                                        <span class="info-value">{{ $teaDetails['tciDetails']->created_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Tea Delivery Information Card -->
                    @if($teaDetails['directDelivery'])
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-truck-loading"></i>Tea Delivery Information
                            </div>
                            <div class="card-body">
                                <div class="info-card">
                                    <div class="info-row">
                                        <span class="info-label">Delivery Number:</span>
                                        <span class="info-value">
                                            {{ $teaDetails['directDelivery']->delivery_number }} |
                                            @if($teaDetails['directDelivery'] && $teaDetails['directDelivery']->path)
                                                <a class="link link-danger" href="{{ route('clerk.downloadDeliveryNote', base64_encode($teaDetails['directDelivery']->delivery_number)) }}" target="_blank">
                                                    <i class="fa fa-file"></i> download
                                                </a>
                                            @endif
                                        </span>

                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value">

                                    @php
                                        $status = $teaDetails['directDelivery']->status;

                                        $map = [
                                            'in_hand'          => ['label' => 'DO In Hand',        'class' => 'bg-warning'],
                                            'collected'        => ['label' => 'Do Collected',         'class' => 'bg-success'],
                                            'under_collection' => ['label' => 'Do Under Collection',  'class' => 'bg-info'],
                                        ];

                                        if (is_null($status)) {
                                            $badge = $map['in_hand'];
                                        } else {
                                            $key = strtolower($status);
                                            $badge = $map[$key] ?? [
                                                'label' => Str::title(str_replace(['-', '_'], ' ', $status)),
                                                'class' => 'bg-secondary'
                                            ];
                                        }
                                    @endphp

                                <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </span>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h6><i class="fas fa-truck me-2"></i>Transporter Details</h6>
                                            <div class="info-row">
                                                <span class="info-label">Transporter:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['directDelivery']->transporter_name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card">
                                            <h6><i class="fas fa-user-tie me-2"></i>Driver Details</h6>
                                            <div class="info-row">
                                                <span class="info-label">Driver Name:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['directDelivery']->driver_name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">ID Number:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['directDelivery']->id_number ?? 'N/A' }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Phone:</span>
                                                <span
                                                    class="info-value">{{ $teaDetails['directDelivery']->phone ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-card">
                                    <div class="info-row">
                                        <span class="info-label">Created By:</span>
                                        <span class="info-value">{{ $teaDetails['directDelivery']->created_by }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Created At:</span>
                                        <span class="info-value">{{ $teaDetails['directDelivery']->created_at }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Blend Usage (Table for multiple records) -->
                    @if($teaDetails['blendUsage'] && count($teaDetails['blendUsage']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-blender"></i>Blend Usage
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Blend Number</th>
                                            <th>Packages Used</th>
                                            <th>Weight Used</th>
                                            <th>Blend Date</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Date Created</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['blendUsage'] as $blend)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $blend->blend_number }}</td>
                                                <td>{{ $blend->blended_packages }}</td>
                                                <td>{{ number_format($blend->blended_weight, 2) }}</td>
                                                <td>{{ $blend->blend_date }}</td>
                                                <td>
                                            <span
                                                class="status-badge {{ $blend->status == 'Blend Shipped' ? 'status-collected' : 'status-processing' }}">
                                                {{ $blend->status }}  {{ $blend->blend_shipped == null ? '' : \Carbon\Carbon::createFromTimestamp($blend->blend_shipped)->format('Y-m-d') }}
                                            </span>
                                                </td>
                                                <td>{{ $blend->created_by }}</td>
                                                <td>{{ $blend->created_at }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Straight Line Shipping (Table for multiple records) -->
                    @if($teaDetails['straightLine'] && count($teaDetails['straightLine']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-ship"></i>Straight Line Shipping
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Shipping Number</th>
                                            <th>Packages Shipped</th>
                                            <th>Weight Shipped</th>
                                            <th>Ship Date</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Date Created</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['straightLine'] as $shipment)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $shipment->shipping_number }}</td>
                                                <td>{{ $shipment->shipped_packages }}</td>
                                                <td>{{ number_format((float) str_replace(',', '', $shipment->shipped_weight), 2) }}</td>
                                                <td>{{ $shipment->ship_date }}</td>
                                                <td>
                                            <span
                                                class="status-badge {{ $shipment->status == 'SI Shipped' ? 'status-collected' : 'status-processing' }}">
                                                {{ $shipment->status }} {{ $shipment->ship_date == null ? '' : \Carbon\Carbon::createFromTimestamp($shipment->ship_date)->format('Y-m-d') }}
                                            </span>
                                                </td>
                                                <td>{{ $shipment->created_by }}</td>
                                                <td>{{ $shipment->created_at }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Internal Transfers (Table for multiple records) -->
                    @if($teaDetails['internalTransfer'] && count($teaDetails['internalTransfer']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-exchange-alt"></i>Internal Transfers
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Delivery Number</th>
                                            <th>From Station</th>
                                            <th>To Station</th>
                                            <th>Packages</th>
                                            <th>Weight</th>
                                            <th>Status</th>
                                            <th>Transporter</th>
                                            <th>Diver</th>
                                            <th>Created By</th>
                                            <th>Date Created</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['internalTransfer'] as $transfer)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $transfer->delivery_number }}</td>
                                                <td>{{ $transfer->station_name }}</td>
                                                <td>{{ $transfer->destination_name }}</td>
                                                <td>{{ $transfer->requested_palettes }}</td>
                                                <td>{{ number_format($transfer->requested_weight, 2) }}</td>
                                                <td>
                                            <span
                                                class="status-badge {{ $transfer->status == 'Transfer Received' ? 'status-collected' : 'status-processing' }}">
                                                {{ $transfer->status }}
                                            </span>
                                                </td>
                                                <td>{{ $transfer->transporter_name ?? 'N/A' }}</td>
                                                <td>{{ $transfer->driver_name ?? 'N/A' }}</td>
                                                <td>{{ $transfer->created_by ?? 'N/A' }}</td>
                                                <td>{{ $transfer->created_at }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- External Transfers (Table for multiple records) -->
                    @if($teaDetails['externalTransfer'] && count($teaDetails['externalTransfer']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-truck-moving"></i>External Transfers
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Delivery Number</th>
                                            <th>From Station</th>
                                            <th>To Warehouse</th>
                                            <th>Packages</th>
                                            <th>Weight</th>
                                            <th>Status</th>
                                            <th>Transporter</th>
                                            <th>Driver Name</th>
                                            <th>Created By</th>
                                            <th>Date Created</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['externalTransfer'] as $transfer)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $transfer->delivery_number }}</td>
                                                <td>{{ $transfer->station_name }}</td>
                                                <td>{{ $transfer->warehouse_name }}</td>
                                                <td>{{ $transfer->transferred_palettes }}</td>
                                                <td>{{ number_format($transfer->transferred_weight, 2) }}</td>
                                                <td>
                                            <span
                                                class="status-badge {{ $transfer->status == 'Transfer Received' ? 'status-collected' : 'status-processing' }}">
                                                {{ $transfer->status }}
                                            </span>
                                                </td>
                                                <td>{{ $transfer->transporter_name ?? 'N/A' }}</td>
                                                <td>{{ $transfer->driver_name ?? 'N/A' }}</td>
                                                <td>{{ $transfer->created_by }}</td>
                                                <td>{{ $transfer->created_at }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($teaDetails['buyers'] && count($teaDetails['buyers']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fa-solid fa-cart-shopping"></i>Sale Details
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                     <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <td>#</td>
                                            <th>Delivery Number</th>
                                            <th>Buyer Name</th>
                                            <th>Sale Number</th>
                                            <th>Sale Date</th>
                                            <th>Prompt Date</th>
                                            <th>Packages</th>
                                            <th>Weight</th>
                                            <th>Date Released</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['buyers'] as $sample)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $sample->delivery_number ?? 'Release Not Prepared' }}</td>
                                                <td>{{ $sample->client_name != null ? $sample->client_name : 'On Auction' }}</td>
                                                <td>{{ $sample->sale }}</td>
                                                <td>{{ $sample->sale_date ? \Carbon\Carbon::parse($sample->sale_date)->format('d-m-Y') : 'On Sale' }}</td>
                                                <td>{{ $sample->prompt_date ? \Carbon\Carbon::parse($sample->prompt_date)->format('d-m-Y') : 'On Sale' }}</td>
                                                <td>{{ number_format($sample->packet, 0) }}</td>
                                                <td>{{ number_format($sample->unit_weight ?? $sample->weight, 2) }}</td>
                                                <td>{{ $sample->release_date ? Carbon\Carbon::parse($sample->release_date)->format('d-m-Y') : 'Not Released' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- External Transfers (Table for multiple records) -->
                    @if($teaDetails['samples'] && count($teaDetails['samples']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-not-equal"></i>Weight Difference
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <td>#</td>
                                            <th>Package Difference</th>
                                            <th>Weight Difference</th>
                                            <th>DifferenceType</th>
                                            <th>Updated By</th>
                                            <th>Date Updated</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['samples'] as $sample)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $sample->sample_palletes }}</td>
                                                <td>{{ number_format($sample->sample_weight, 2) }}</td>
                                                <td>{{ $sample->type }}</td>
                                                <td>{{ ucwords(strtolower($sample->user_name)) }}</td>
                                                <td>{{ Carbon\Carbon::parse($sample->created_at)->format('d-m-Y') }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($teaDetails['rebaggings'] && count($teaDetails['rebaggings']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-box-open"></i>Rebagging History
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <td>#</td>
                                            <th>SI/Blend Number</th>
                                            <th>Job Type</th>
                                            <th>Packages</th>
                                            <th>Weight</th>
                                            <th>Updated By</th>
                                            <th>Date Updated</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['rebaggings'] as $sample)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $sample->siNumber }}</td>
                                                <td>{{ $sample->type }}</td>
                                                <td>{{ number_format($sample->packages, 0) }}</td>
                                                <td>{{ number_format($sample->weight, 2) }}</td>
                                                <td>{{ ucwords(strtolower($sample->username)) }}</td>
                                                <td>{{ Carbon\Carbon::parse($sample->created_at)->format('d-m-Y') }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Stock Details (Table for multiple records) -->
                    @if($teaDetails['stockDetails'] && count($teaDetails['stockDetails']) > 0)
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-warehouse"></i>Stock Details
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th></th>
                                            <th>Delivery Number</th>
                                            <th>Location</th>
                                            <th>Bay</th>
                                            <th>Current Stock</th>
                                            <th>Current Weight</th>
                                            <th>Received On</th>
                                            <th>Received By</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($teaDetails['stockDetails'] as $stock)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $stock->delivery_number }}</td>
                                                <td>{{ $stock->stocked_at }}</td>
                                                <td>{{ $stock->bay_name }}</td>
                                                <td>{{ $stock->current_stock }}</td>
                                                <td>{{ number_format($stock->current_weight, 2) }}</td>
                                                <td>{{ \Carbon\Carbon::createFromTimestamp($stock->date_received)->format('Y-m-d') }}</td>
                                                <td>{{ $stock->created_by }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Summary -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calculator"></i>Stock Summary
                            </div>
                            <div class="card-body">
                                @php
                                    $currentStock = DB::table('currentstock')
                                        ->where('delivery_id', $teaDetails['deliveryOrder']->delivery_id)
                                        ->where('current_stock', '>', 0)
                                        ->where('current_weight', '>', 0)
                                        ->select('allocated_job')
                                        ->selectRaw('SUM(current_stock) as stockAtHand')
                                        ->selectRaw('SUM(current_weight) as weightAtHand')
                                        ->get();
                                @endphp

                                <h6>Usable Stock</h6>

                                @if(count($currentStock) > 0 && $currentStock[0]->stockAtHand > 0)
                                    <div class="info-card bg-light-success">
                                        <div class="info-row">
                                            <span class="info-label">Current Balance:</span>
                                            <span class="info-value">
                                        <span
                                            class="badge bg-success me-2">Packages: {{ number_format($currentStock[0]->stockAtHand, 2) }}</span>
                                        <span class="badge bg-primary">Weight: {{ number_format($currentStock[0]->weightAtHand, 2) }} kg</span>
                                    </span>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-4">
                                        <i class="fas fa-exclamation-circle me-2"></i>Tea out of stock
                                    </div>
                                @endif

                                <h6>Allocated Stock</h6>

                                @php
                                    $allocations = DB::table('currentstock')
                                        ->where('delivery_id', $teaDetails['deliveryOrder']->delivery_id)
                                        ->select('allocated_job')
                                        ->get();

                                @endphp

{{--                                {!! $allocations !!}--}}

                                @foreach($allocations as $allocation)
                                    @if(!empty($allocation->allocated_job))
                                        @php
                                            // Fix the malformed JSON by adding quotes around transaction_id values
                                            $jsonString = $allocation->allocated_job;

                                            // Add quotes around unquoted values after transaction_id:
                                            $jsonString = preg_replace('/"transaction_id":([^,}\]]+)/', '"transaction_id":"$1"', $jsonString);

                                            // Decode the JSON
                                            $jobs = json_decode($jsonString, true);

                                            // Debug: uncomment to see what's happening
                                            // dd($allocation->allocated_job, $jsonString, $jobs);
                                        @endphp

                                        @if(is_array($jobs) && count($jobs) > 0)
                                            <div class="row g-2">
                                                @foreach($jobs as $job)
                                                    <div class="col-md-3">
                                                        <div class="border rounded p-3 h-100">
                                                            <strong>Type:</strong> {{ ucfirst($job['type'] ?? 'N/A') }} <br>
                                                            <strong>Transaction ID:</strong> {{ $job['transaction_number'] ?? 'N/A' }} <br>
{{--                                                            <strong>Transaction ID:</strong> {{ $job['transaction_id'] ?? 'N/A' }} <br>--}}
                                                            <strong>Packages:</strong> {{ $job['packages'] ?? 0 }} <br>
                                                            <strong>Weight:</strong> {{ number_format($job['weight'] ?? 0, 2) }} kg
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-danger">
                                                Invalid job data format
                                            </div>
                                        @endif
                                    @endif
                                @endforeach

                                @if($allocations->every(fn($row) => empty($row->allocated_job)))
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        All Transactions Completed
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
    <script>
        $(document).ready(function () {
            $('.table').DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });
        });
    </script>
@endsection
