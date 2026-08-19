@php
    $t = (object) (array) $transfer;
@endphp
<div class="d-flex align-items-center">
    @if($t->status === 0 || $t->status === '0')
        <a class="link text-info" data-bs-toggle="tooltip" data-bs-placement="left" title="Initiate external transfer" onclick="return confirm('Are you sure you want to initiate this transfer request?')" href="{{ route('admin.initiateExternalTransfer', base64_encode($t->delivery_number)) }}"><span class="fa-solid fa-check-circle"></span></a>
    @elseif($t->status == 1)
        <a class="link text-dark" data-bs-toggle="tooltip" data-bs-placement="left" title="Approve transfer, operations dept" onclick="return confirm('Are you sure you want to approve this transfer request?')" href="{{ route('admin.approveExternalTransfer', base64_encode($t->delivery_number)) }}"><span class="fa-solid fa-check"></span></a>
    @elseif($t->status == 2)
        <a class="link text-secondary" data-bs-toggle="tooltip" data-bs-placement="left" title="Approve transfer, finance dept" onclick="return confirm('Are you sure you want to approve this transfer request?')" href="{{ route('admin.approveExternalTransfer', base64_encode($t->delivery_number)) }}"><span class="fa-solid fa-check-double"></span></a>
    @elseif($t->status == 3)
        @if(!empty($t->release_date))
            <a class="link link-danger release-btn"
               title="Transfer approved, pending release"
               data-delivery="{{ $t->delivery_number }}"
               data-url="{{ route('admin.releaseForm', base64_encode($t->delivery_number . ':' . ($t->lot ?? ''))) }}"
               data-client="{{ $t->client_name }}"
               href="#">
                <span class="fa-solid fa-truck-arrow-right"></span>
            </a>
        @else
            <a class="link text-secondary" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer approved, pending release"><span class="fa-solid fa-truck-field"></span></a>
        @endif
    @else
        <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left" title="Transfer released, and stock updated"><span class="fa-solid fa-truck-fast"></span></a>
    @endif

    <div class="dropdown font-sans-serif position-static">
        <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
            <span class="fas fa-ellipsis-h fs-10"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end border py-0">
            <div class="py-2">
                <a class="dropdown-item text-info" href="{{ route('admin.viewExternalTransferDetails', base64_encode($t->delivery_number)) }}">View Transfer</a>
                @if(empty($t->buyer_name))
                    <a class="dropdown-item text-primary" href="{{ route('admin.downloadExtraDelNote', base64_encode($t->delivery_number . ':' . $t->lot)) }}" target="_blank">Download Transfer</a>
                @else
                    <a class="dropdown-item text-danger" href="{{ route('admin.downloadDelNote', base64_encode($t->delivery_number . ':' . $t->lot)) }}" target="_blank">Download Del Note</a>
                    <a class="dropdown-item text-danger" href="{{ route('admin.downloadLocalDeliveryNote', base64_encode($t->delivery_number . ':' . $t->lot)) }}" target="_blank">Local Delivery Note</a>
                @endif
            </div>
        </div>
    </div>
</div>
