@extends('admin::layouts.default')
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Rebagging Jobs </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <a class="btn btn-falcon-default btn-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="d-none d-sm-inline-block ms-1">New Job</span></a>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl mt-6" role="document">
                    <div class="modal-content border-0">
                        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 bg-body-tertiary py-3 ps-4 pe-6">
                                <h5 class="mb-1" id="staticBackdropLabel">CREATE A REBAG JOB</h5>
                            </div>
                            <div class="p-4">
                                <form method="POST" action="{{ route('admin.prepareRebagJob') }}">
                                    @csrf
                                    <div class="row row-cols-sm-2 g-2">
                                        <div class="mb-4">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">REBAG ON</label>
                                            <select name="request_type" class="form-select  js-choice" id="reportType" required>
                                                <option disabled selected>-- select --</option>
                                                <option value="1">STRAIGHT LINE</option>
                                                <option value="2">BLEND JOBS</option>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="fw-bold fs-6" style="font-size: small !important;">SI/BLEND NUMBER</label>
                                            <select name="siNumber" id="siNumber" class="form-select js-choice" required style="height: 61% !important;">
                                                <option selected disabled value="">-- select --</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-center mt-1">
                                        <button type="submit" id="submitBtn" class="btn btn-success col-8">CREATE REBAG JOB </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    <table class="table mb-0 table-bordered fs-sm table-sm table-striped" id="datatable">
                        <thead class="bg-200">
                        <tr>
                            <th>#</th>
                            <th>SI/Blend Number </th>
                            <th>Rebagging On</th>
                            <th>Client Name</th>
                            <th>Total Packages</th>
                            <th>Total Weight</th>
                            <th>Initiator</th>
                            <th nowrap="">Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bags as $transfer)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $transfer->siNumber }}</td>
                                <td>{{ $transfer->type }}</td>
                                <td>{{ $transfer->clientName }}</td>
                                <td>{{ number_format($transfer->packages, 0) }}</td>
                                <td>{{ number_format($transfer->weight, 2) }}</td>
                                <td>{{ ucwords(strtolower($transfer->username)) }}</td>
                                <td>
                                    {!! $transfer->status == 0 ? '<span class="badge bg-warning"> Blend Created </span>' : ($transfer->status == 1 ? '<span class="badge bg-info"> Teas Updated </span>' : ($transfer->status == 2 ? '<span class="badge bg-secondary"> Blend Updated </span>' : ($transfer->status == 3 ? '<span class="badge bg-dark"> Pend. Approval </span>' : '<span class="badge bg-success"> Shipped </span>'))) !!}
                                </td>
                                <td>
                                    <a class="link-primary" href="{{ route('clerk.viewRebaggedTeas', $transfer->shippingId) }}">view</a> |
                                    @if($transfer->status <= 4)
                                        <a class="link-danger" onclick="return confirm('Are you sure you want to delete this rebagging record')" href="{{ route('clerk.removeRebaggedTeas', $transfer->shippingId) }}">delete</a>
                                    @else
                                        <span class="text-danger fst-italic">action disabled</span>
                                    @endif
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
<script>
    let choicesInstance = null;
    function initChoices() {
        const selectElement = document.getElementById('siNumber');

        // If Choices was already initialized, destroy it first
        if (choicesInstance) {
            choicesInstance.destroy();
        }

        // Initialize Choices
        choicesInstance = new Choices(selectElement, {
            removeItemButton: true,
            shouldSort: false,
            placeholderValue: 'Select container...',
        });
    }

    // Initialize once the DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        initChoices();
    });

    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        // AJAX call to populate select
        $('#reportType').on('change', function () {
            const reportId = $(this).val();
            console.log(reportId)
            $.ajax({
                url: '{{ route('admin.fetchBySiNumber') }}',
                method: 'GET',
                data: { reportId },
                success: function (response) {
                    console.log(response);

                    // Re-initialize in case DOM changed
                    initChoices();

                    const newChoices = response.map(item => ({
                        value: item.shipping_id,
                        label: item.siNumber+' - '+item.client_name,
                    }));

                    choicesInstance.setChoices(newChoices, 'value', 'label', true);
                },
                error: function () {
                    alert('Failed to fetch container numbers.');
                }
            });
        });
    });
</script>
