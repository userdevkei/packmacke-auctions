@extends('admin::layouts.default')
<style>
    .form-control {
        height: 2.8rem !important;
    }
</style>
@section('admin::dashboard')

    @php
        $incompleteCount = collect($records)->where('_incomplete', true)->count();
        $completeCount   = count($records) - $incompleteCount;
        $displayCols     = array_filter(
                               array_keys($records[0] ?? []),
                               fn($k) => !str_starts_with($k, '_')
                           );
    @endphp

    <div class="card mb-3">

        {{-- ═══ CARD HEADER ═══ --}}
        <div class="card-header py-3">
            <div class="row flex-between-center">
                <div class="col-auto d-flex align-items-center gap-2">
                    <a href="{{ route('admin.viewDirectDeliveries') }}" class="btn btn-sm btn-falcon-default">
                        <span class="fas fa-arrow-left me-1"></span> Back
                    </a>
                    <h5 class="mb-0 fs-9 text-nowrap">Direct Delivery Import — Preview</h5>
                </div>
                <div class="col-auto d-flex gap-2 align-items-center">
                <span class="badge bg-success fs-xs">
                    <span class="fas fa-check me-1"></span>{{ $completeCount }} Ready
                </span>
                    @if($incompleteCount > 0)
                        <span class="badge bg-warning text-dark fs-xs">
                        <span class="fas fa-exclamation-triangle me-1"></span>{{ $incompleteCount }} Will be skipped
                    </span>
                    @endif
                    <span class="badge bg-secondary fs-xs">
                    {{ count($records) }} Total rows
                </span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">

            {{-- ═══ ERROR ALERT ═══ --}}
            @if(session('error') || $errors->any())
                <div class="alert alert-danger rounded-0 mb-0 border-0 border-bottom">
                    <span class="fas fa-times-circle me-1"></span>
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.saveImport') }}" id="saveForm" enctype="multipart/form-data">
                @csrf

                {{-- ═══ RECORDS PREVIEW ═══ --}}
                <div class="p-4">

                    {{-- Incomplete rows warning --}}
                    @if($incompleteCount > 0)
                        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 mb-3">
                            <span class="fas fa-exclamation-triangle mt-1 flex-shrink-0"></span>
                            <span>
                            <strong>{{ $incompleteCount }} row{{ $incompleteCount !== 1 ? 's' : '' }}</strong>
                            highlighted in yellow have missing required fields and will be
                            <strong>skipped</strong> on save. Missing cells are marked in
                            <span class="text-danger fw-bold">red</span>.
                        </span>
                        </div>
                    @endif

                    {{-- No complete records warning --}}
                    @if($completeCount === 0)
                        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
                            <span class="fas fa-ban"></span>
                            <span>No complete records found. Please fix your Excel file and re-upload.</span>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0" id="previewTable">
                            <thead class="bg-200 text-uppercase" style="font-size: 0.68rem;">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                @foreach($displayCols as $col)
                                    <th class="text-nowrap px-2">{{ $col }}</th>
                                @endforeach
                                <th class="text-center" style="width: 120px;">Status</th>
                            </tr>
                            </thead>
                            <tbody style="font-size: 0.75rem;">
                            @forelse($records as $i => $record)
                                <tr class="{{ $record['_incomplete'] ? 'table-warning' : '' }}">
                                    <td class="text-center text-muted">{{ $i + 1 }}</td>

                                    @foreach($displayCols as $col)
                                        @php
                                            $val       = $record[$col] ?? '';
                                            $isMissing = $record['_incomplete'] && in_array($col, $record['_missing'] ?? []);
                                        @endphp
                                        <td class="{{ $isMissing ? 'text-danger fw-bold bg-danger bg-opacity-10' : '' }} px-2"
                                            @if($isMissing) title="REQUIRED — value missing" @endif>
                                            @if($isMissing)
                                                <span class="fas fa-exclamation-circle me-1" style="font-size:0.65rem;"></span>missing
                                            @elseif($val !== '' && $val !== null)
                                                {{ $val }}
                                            @else
                                                <span class="text-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="text-center">
                                        @if($record['_incomplete'])
                                            <span class="badge bg-warning text-dark"
                                                  style="font-size:0.62rem;"
                                                  title="Missing: {{ implode(', ', $record['_missing'] ?? []) }}">
                                                <span class="fas fa-times me-1"></span>Skip
                                            </span>
                                        @else
                                            <span class="badge bg-success" style="font-size:0.62rem;">
                                                <span class="fas fa-check me-1"></span>OK
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($displayCols) + 2 }}" class="text-center text-muted py-4">
                                        No records found in the uploaded file.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                            @if(count($records) > 0)
                                <tfoot class="bg-100" style="font-size: 0.7rem;">
                                <tr>
                                    <td colspan="{{ count($displayCols) + 2 }}" class="px-3 py-2">
                                        <span class="badge bg-success me-2">{{ $completeCount }} will be saved</span>
                                        @if($incompleteCount > 0)
                                            <span class="badge bg-warning text-dark">{{ $incompleteCount }} will be skipped</span>
                                        @endif
                                    </td>
                                </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    {{-- ═══ DELIVERY DETAILS PANEL ═══ --}}
                    <div class="">
                        <h6 class="text-uppercase text-600 mb-3" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                            <span class="fas fa-truck me-1"></span> Delivery Details
                        </h6>

                        <div class="row g-3">
                            {{-- Dispatch Date --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    DISPATCH DATE <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       name="dispatch_date"
                                       class="form-control @error('dispatch_date') is-invalid @enderror"
                                       required
                                       value="{{ old('dispatch_date') }}">
                                @error('dispatch_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Dispatch Date --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    ARRIVAL DATE <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       name="arrival_date"
                                       class="form-control @error('arrival_date') is-invalid @enderror"
                                       required
                                       value="{{ old('arrival_date') }}">
                                @error('arrival_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Transporter --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    TRANSPORTER <span class="text-danger">*</span>
                                </label>
                                <select name="transporter_id"
                                        id="transporterSelect"
                                        class="form-select form-select-sm js-choice @error('transporter_id') is-invalid @enderror"
                                        required
                                        onchange="toggleOtherInput(this, 'otherTransporterInput')">
                                    <option disabled selected value="">-- select transporter --</option>
                                    @foreach($transporters as $t)
                                        <option value="{{ $t->transporter_id }}" @selected(old('transporter_id') == $t->transporter_id)>
                                            {{ $t->transporter_name }}
                                        </option>
                                    @endforeach
                                    <option value="other">Other (specify below)</option>
                                </select>
                                <input type="text"
                                       name="transporter_other"
                                       id="otherTransporterInput"
                                       class="form-control mt-1 d-none"
                                       placeholder="Enter transporter name">
                                @error('transporter_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Vehicle Registration --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    VEHICLE REGISTRATION <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="registration"
                                       class="form-control @error('registration') is-invalid @enderror"
                                       required
                                       placeholder="Plate number"
                                       value="{{ old('registration') }}">
                                @error('registration')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Driver ID (lookup) --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">DRIVER'S ID NUMBER</label>
                                <input type="text"
                                       id="idSelectPreview"
                                       name="id_number"
                                       list="idListPreview"
                                       class="form-control"
                                       placeholder="Type ID — auto-fills name & phone">
                                <datalist id="idListPreview">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                                    @endforeach
                                </datalist>
                                <small class="text-muted fs-xs">Optional — auto-fills driver details</small>
                            </div>

                            {{-- Driver Name --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    DRIVER NAME <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="driver_name"
                                       id="driverNamePreview"
                                       class="form-control @error('driver_name') is-invalid @enderror"
                                       required
                                       placeholder="Full name"
                                       value="{{ old('driver_name') }}">
                                @error('driver_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Driver Phone --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    DRIVER PHONE <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="driver_phone"
                                       id="driverPhonePreview"
                                       class="form-control @error('driver_phone') is-invalid @enderror"
                                       required
                                       placeholder="Phone number"
                                       value="{{ old('driver_phone') }}">
                                @error('driver_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Driver Phone --}}
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold fs-xs mb-1">
                                    DELIVERY NOTE <span class="text-danger">*</span>
                                </label>
                                <input type="file"
                                       name="delivery_note"
                                       id="deliveryNote"
                                       class="form-control @error('delivery_note') is-invalid @enderror"
                                       required
                                       placeholder="Delivery Note"
                                       value="{{ old('delivery_note') }}"
                                       accept="image/png,image/jpeg,image/jpg,application/pdf">
                                @error('delivery_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>{{-- /row --}}
                    </div>{{-- /delivery details panel --}}

                    {{-- ═══ ACTION BUTTONS ═══ --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="{{ route('admin.viewDirectDeliveries') }}" class="btn btn-falcon-default">
                            <span class="fas fa-times me-1"></span> Cancel Import
                        </a>
                        <button type="submit"
                                class="btn btn-success px-5"
                                id="saveBtn"
                                @if($completeCount === 0) disabled @endif>
                            <span class="fas fa-save me-1"></span>
                            Save {{ $completeCount }} Record{{ $completeCount !== 1 ? 's' : '' }}
                        </button>
                    </div>

                </div>{{-- /records preview --}}

            </form>
        </div>{{-- /card-body --}}
    </div>{{-- /card --}}

    <script>
        $(document).ready(function () {

            // DataTable
            $('#previewTable').DataTable({
                order:       [[0, 'asc']],
                pageLength:  50,
                scrollX:     true,
                columnDefs:  [{ targets: -1, orderable: false, searchable: false }],
                language: {
                    search:     'Filter rows:',
                    lengthMenu: 'Show _MENU_ rows',
                }
            });

            // Driver ID auto-fill
            $('#idSelectPreview').on('change', function () {
                var idNumber = $(this).val();
                if (!idNumber) return;

                $.ajax({
                    url:      '{{ route('admin.fetchIdNumber') }}',
                    method:   'GET',
                    data:     { idNumber },
                    dataType: 'json',
                    success: function (res) {
                        $('#driverNamePreview').val(res.driver_name ?? '');
                        $('#driverPhonePreview').val(res.driver_phone ?? '');
                    },
                    error: function () {
                        $('#driverNamePreview').val('');
                        $('#driverPhonePreview').val('');
                    }
                });
            });

            // Prevent double-submit
            $('#saveForm').on('submit', function () {
                $('#saveBtn').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            });

        });

        function toggleOtherInput(selectEl, inputId) {
            const inputEl = document.getElementById(inputId);
            const isOther = selectEl.value === 'other';
            inputEl.classList.toggle('d-none', !isOther);
            inputEl.required = isOther;
            if (!isOther) inputEl.value = '';
        }
    </script>

@endsection
