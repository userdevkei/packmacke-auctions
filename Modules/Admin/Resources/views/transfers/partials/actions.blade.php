@php
    $t = (object) (array) $transfer;
@endphp
<div class="d-flex align-items-center">
    @if($transfer->status == 0)
        <a class="link text-warning" data-bs-toggle="tooltip" data-bs-placement="left"
           title="Click to initiate transfer"
           onclick="return confirm('Are you sure you want to initiate this transfer request?')"
           href="{{ route('admin.initiateExternalTransfer', base64_encode($transfer->delivery_number)) }}">
            <span class="fa-solid fa-toggle-off"></span>
        </a>
    @elseif($transfer->status == 1)
        <a class="link text-warning" data-bs-toggle="tooltip" data-bs-placement="left"
           title="Click to give first approval"
           onclick="return confirm('Are you sure you want to approve this transfer request?')"
           href="{{ route('admin.approveExternalTransfer', base64_encode($transfer->delivery_number)) }}">
            <span class="fa-regular fa-thumbs-up"></span>
        </a>
    @elseif($transfer->status == 2)
        @if($transfer->lot != null)
            <a class="link link-danger release-btn"
               title="Approved — release this transfer"
               data-delivery="{{ $transfer->delivery_number }}"
               data-url="{{ route('admin.releaseForm', base64_encode($transfer->delivery_number.':'.($transfer->lot ?? ''))) }}"
               data-client="{{ $transfer->client_name }}"
               href="#">
                <span class="fa-solid fa-truck-arrow-right"></span>
            </a>
        @else
            <a class="link text-secondary" data-bs-toggle="tooltip" data-bs-placement="left"
               title="Approved, awaiting lot assignment">
                <span class="fa-solid fa-truck-arrow-right"></span>
            </a>
        @endif
    @elseif($transfer->status == 3)
        <a class="link text-primary" data-bs-toggle="tooltip" data-bs-placement="left"
           title="Released — give final approval"
           onclick="return confirm('Are you sure you want to give final approval for this transfer?')"
           href="{{ route('admin.approveExternalTransferFinal', base64_encode($transfer->delivery_number.':'.($transfer->lot ?? ''))) }}">
            <span class="fa-solid fa-check"></span>
        </a>
    @else
        <a class="link text-success" data-bs-toggle="tooltip" data-bs-placement="left"
           title="Transfer completed, stock updated">
            <span class="fa-solid fa-check-double"></span>
        </a>
    @endif

    <div class="dropdown font-sans-serif position-static">
        <a class="link text-600 btn-sm dropdown-toggle btn-reveal" type="button"
           data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
            <span class="fas fa-ellipsis-h fs-10"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end border py-0">
            <div class="py-2">
                <a class="dropdown-item text-info"
                   href="{{ route('admin.viewExternalTransferDetails', base64_encode($transfer->delivery_number)) }}">View Transfer</a>
                @if($transfer->buyer_name != null && $transfer->lot != null)
                    <a class="dropdown-item text-danger"
                       href="{{ route('admin.downloadDelNote', base64_encode($transfer->delivery_number.':'.$transfer->lot)) }}" target="_blank">Download Del Note</a>
                        <a class="dropdown-item text-info" href="{{ route('admin.downloadLocalDeliveryNote', base64_encode($transfer->delivery_number . ':' . $transfer->lot)) }}" target="_blank">Local Delivery Note</a>
                @else
                    <a class="dropdown-item text-primary"
                       href="{{ route('admin.downloadExtraDelNote', base64_encode($transfer->delivery_number.':'.$transfer->lot)) }}" target="_blank">Download Transfer</a>
                      
                @endif
            </div>
        </div>
    </div>
</div>
