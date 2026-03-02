@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Update User Permissions </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active mt-5" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                    @php
                        $u = $user;
                        $assigned = $user->userpermissions->pluck('permission_id')->filter()->toArray();
                    @endphp

                    <div class="container">

                        {{-- USER CARD --}}
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body d-flex justify-content-between">
                                <div>
                                    <h5 class="mb-1">{{ $u->first_name.' '.$u->surname }}</h5>
                                    <div class="text-muted">{{ $u->email_address }}</div>
                                    <div class="text-muted">{{ $u->username }}</div>
                                    <span class="badge bg-info mt-2">{{ $u->role_name }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- PERMISSIONS --}}
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <strong>Permissions</strong>
                                <span class="text-muted small">(click to enable / disable)</span>
                            </div>

                            <div class="card-body">
                                <div class="row">
                                    @foreach ($categories as $category => $permissions)
                                        <h5 class="mb-3">{{ ucwords($category) }}</h5>
                                        <hr>
                                        @foreach($permissions as $permission)
                                            <div class="col-md-3 mb-2">
                                                <div class="custom-control custom-switch">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input permission-toggle"
                                                        id="perm_{{ $permission->id }}"
                                                        data-user="{{ $u->user_id }}"
                                                        data-permission="{{ $permission->id }}"
                                                        {{ in_array($permission->id, $assigned) ? 'checked' : '' }}
                                                    >
                                                    <label class="custom-control-label" for="perm_{{ $permission->id }}">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('.permission-toggle').on('change', function () {
            const checkbox = $(this);

            $.ajax({
                url: "{{ route('admin.usersPermissionsToggle') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    user_id: checkbox.data('user'),
                    permission_id: checkbox.data('permission'),
                    checked: checkbox.is(':checked') ? 1 : 0
                },
                success: function () {
                    checkbox.closest('.custom-control')
                        .addClass('bg-light')
                        .delay(200)
                        .queue(function(next){
                            $(this).removeClass('bg-light');
                            next();
                        });
                    window.location.reload();
                },
                error: function () {
                    alert('Permission update failed');
                    checkbox.prop('checked', !checkbox.is(':checked'));
                }
            });
        });
    });
</script>
