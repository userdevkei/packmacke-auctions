@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Manage Containers — {{ $blend->blend_number }}</h5>
            @if($blend->blend_shipped == null)
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContainerModal">
                    <span class="fas fa-plus me-1"></span> Add Container
                </button>
            @endif
        </div>

        <div class="card-body">
            <table class="table table-sm table-bordered table-striped mb-0" id="datatable">
                <thead class="bg-200">
                <tr>
                    <th>#</th>
                    <th>Container Number</th>
                    <th>Seal Number</th>
                    <th>Tare (kg)</th>
                    <th>Pallet Wt.</th>
                    <th>Packages</th>
                    <th>Weight</th>
                    <th>Transporter</th>
                    <th>Escorted</th>
                    <th>Agent</th>
                    <th>Driver Details</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($containers as $index => $container)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $container->container_number }}</td>
                        <td>{{ $container->seal_number }}</td>
                        <td>{{ $container->tare_weight }}</td>
                        <td>{{ $container->pallet_weight }}</td>
                        <td>{{ $container->packages }}</td>
                        <td>{{ $container->weight }}</td>
                        <td>{{ $container->transporter_name }}</td>
                        <td>{{ ucwords($container->escort) }}</td>
                        <td>{{ $container->agent_name }}</td>
                        <td>{{ $container->driver_name }} - {{ $container->id_number }}</td>
                        <td>{{ $container->status }}</td>
                        <td nowrap>
                            @if($container->status !== 'approved')
                                <a href="#" class="link text-warning me-2" data-bs-toggle="modal" data-bs-target="#editContainerModal{{ $container->container_id }}" title="Edit"><span class="fas fa-edit"></span></a>


                                <form action="{{ route('clerk.destroyContainer', $container->container_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this container?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link text-danger me-2 border-0 bg-transparent p-0" title="Delete"><span class="fas fa-trash"></span></button>
                                </form>
                            @endif

                            @if($blend->status == 4 && $container->status == 'approved')
                                <a href="{{ route('clerk.downloadContainerClearance', $container->container_id) }}" target="_blank" class="link text-secondary me-2" title="Port Delivery Note"><span class="fas fa-file"></span></a>
                                <a href="{{ route('clerk.downloadContainerPackingListPdf', $container->container_id) }}" target="_blank" class="link text-secondary me-2" title="Packing List (PDF)"><span class="fas fa-file-pdf"></span></a>
                                <a href="{{ route('clerk.downloadContainerPackingListExcel', $container->container_id) }}" target="_blank" class="link text-secondary" title="Packing List (Excel)"><span class="fas fa-file-excel"></span></a>
                            @endif
                        </td>
                    </tr>

                    {{-- EDIT MODAL --}}
                    <div class="modal fade" id="editContainerModal{{ $container->container_id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Container</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('clerk.updateContainer', $container->container_id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="row row-cols-sm-3 g-3">
                                            <div class="mb-2">
                                                <label class="form-label">Container Number</label>
                                                <input type="text" name="containerNumber" class="form-control" value="{{ $container->container_number }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Container Tare (kg)</label>
                                                <input type="number" step="0.01" name="tare" class="form-control" value="{{ $container->tare_weight }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Pallet Weight (kg)</label>
                                                <input type="number" step="0.01" name="palletWeight" class="form-control" value="{{ $container->pallet_weight }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Packages</label>
                                                <input type="number" name="packages" class="form-control" value="{{ $container->packages }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Weight (kg)</label>
                                                <input type="number" step="0.01" name="weight" class="form-control" value="{{ $container->weight }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Seal Number</label>
                                                <input type="text" name="seal" class="form-control" value="{{ $container->seal_number }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Clearing Agent</label>
                                                <select name="agentId" class="form-select" required>
                                                    <option value="">-- select clearing agent --</option>
                                                    @foreach($agents as $agent)
                                                        <option value="{{ $agent->agent_id }}" @selected($agent->agent_id == $container->agent_id)>{{ $agent->agent_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Cargo Escorted?</label>
                                                <select name="escortId" class="form-select" required>
                                                    <option value="">-- select option --</option>
                                                    <option value="yes" @selected($container->escort == 'yes')>YES</option>
                                                    <option value="no" @selected($container->escort == 'no')>NO</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Transporter</label>
                                                <select name="transporter" class="form-select" required>
                                                    <option value="">-- select transporter --</option>
                                                    @foreach($transporters as $transporter)
                                                        <option value="{{ $transporter->transporter_id }}" @selected($transporter->transporter_id == $container->transporter_id)>{{ $transporter->transporter_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Vehicle Registration</label>
                                                <input type="text" name="registration" class="form-control" value="{{ $container->registration }}" list="regList" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Driver's ID Number</label>
                                                <input type="text" name="idNumber" class="form-control" value="{{ $container->id_number }}" list="idList" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Driver's Name</label>
                                                <input type="text" name="driverName" class="form-control" value="{{ $container->driver_name }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Driver's Phone Number</label>
                                                <input type="text" name="driverPhone" class="form-control" value="{{ $container->phone }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Save Container</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ADD MODAL --}}
    <div class="modal fade" id="addContainerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Container</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('clerk.storeContainer', $blend->blend_id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row row-cols-sm-3 g-3">
                            <div class="mb-2">
                                <label class="form-label">Container Number</label>
                                <input type="text" name="containerNumber" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Container Tare (kg)</label>
                                <input type="number" step="0.01" name="tare" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Pallet Weight (kg)</label>
                                <input type="number" step="0.01" name="palletWeight" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Packages</label>
                                <input type="number" name="packages" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Seal Number</label>
                                <input type="text" name="seal" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Clearing Agent</label>
                                <select name="agentId" class="form-select" required>
                                    <option value="">-- select clearing agent --</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->agent_id }}">{{ $agent->agent_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Cargo Escorted?</label>
                                <select name="escortId" class="form-select" required>
                                    <option value="">-- select option --</option>
                                    <option value="yes">YES</option>
                                    <option value="no">NO</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Transporter</label>
                                <select name="transporter" class="form-select" required>
                                    <option value="">-- select transporter --</option>
                                    @foreach($transporters as $transporter)
                                        <option value="{{ $transporter->transporter_id }}">{{ $transporter->transporter_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Vehicle Registration</label>
                                <input type="text" name="registration" class="form-control" list="regList" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Driver's ID Number</label>
                                <input type="text" name="idNumber" class="form-control" list="idList" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Driver's Name</label>
                                <input type="text" name="driverName" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Driver's Phone Number</label>
                                <input type="text" name="driverPhone" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Container</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <datalist id="regList">
        @foreach($registrations as $registration)
            <option value="{{ $registration }}">{{ $registration }}</option>
        @endforeach
    </datalist>
    <datalist id="idList">
        @foreach($users as $user)
            <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
        @endforeach
    </datalist>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });
    });
</script>
