@extends('clerk::layouts.default')
@section('clerk::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Internal Tea Transfers From <span class="text-danger">{!! $transfers[0]->station_name !!}</span> To <span class="text-success">{!! $transfers[0]->warehouse_name !!} </span></h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                        <span class="text-info">{!! $transfers[0]->client_name !!}</span>
                    </div>
                </div>

            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                        <table class="table mb-0 table-bordered table-striped" id="datatable">
                            <thead class="bg-200">
                            <tr>
                                <th>#</th>
                                <th>Garden Name</th>
                                <th>Grade Name</th>
                                <th>Invoice Number </th>
                                <th>Lot Number</th>
                                <th>Requested Pcks</th>
                                <th>Requested Weight</th>
                                <th>Release Date</th>
                                <th>Release Lot</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transfers as $transfer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $transfer->garden_name }}</td>
                                    <td>{{ $transfer->grade_name }}</td>
                                    <td>{{ $transfer->invoice_number }}</td>
                                    <td>{{ $transfer->lot_number }}</td>
                                    <td>{{ $transfer->transferred_palettes }}</td>
                                    <td>{{ $transfer->transferred_weight }}</td>
                                    <td>{{ $transfer->release_date ? $transfer->release_date->format('d-m-Y') : null }}{{ $transfer->lot ? '('.$transfer->lot.')' : null }}</td>
                                    <td>
                                        @if($transfer->status > 3)
                                            <input class="release-checkbox" data-id="{{ $transfer->ex_transfer_id }}" @if($transfer->release_date) checked @else @endif type="checkbox" value="{{ $transfer->ex_transfer_id }}">
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
    $(document).ready(function() {
        $('#datatable').DataTable({
            order: [0, 'asc'],
            pageLength: 50
        });

        $('#datatable').on('click', '.release-checkbox', function(e) {
            var releaseId = $(this).attr('data-id');
            var status = $(this).is(':checked');

            $.ajax({
                url: '{{ route("clerk.releaseTransfer") }}',
                method: "POST",
                data: {
                    releaseId: releaseId,
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        // Use your existing toast function
                        // Adjust the function name to match your setup
                        toastr.success(response.message);

                        // Optional: Update the release date in the table without reload
                        if(response.lot_number) {
                            $('input[data-id="' + releaseId + '"]')
                                .closest('tr')
                                .find('td:eq(7)') // Release Date column (adjust index if needed)
                                .text(response.release_date + ' (Lot ' + response.lot_number + ')');
                        } else {
                            // If unchecked, clear the release date
                            $('input[data-id="' + releaseId + '"]')
                                .closest('tr')
                                .find('td:eq(7)')
                                .text('');
                        }
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });

    });
</script>

