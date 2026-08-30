@extends('admin::layouts.default')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.css">
<style>
    legend {
        font-size: 120% !important;
    }
</style>
@section('admin::dashboard')
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-center">
                <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                    <h5 class="fs-9 mb-0 text-nowrap py-0 py-xl-0">Manage {{ ucwords(strtolower($client->client_name)) }} </h5>
                </div>
                <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                    <div id="table-simple-pagination-replace-element">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body overflow-hidden p-lg-3">
            <div class="row align-items-center">
                <div class="tab-pane preview-tab-pane active" role="tabpanel" aria-labelledby="tab-dom-c3976e0e-38db-410e-861a-36d04a3a7494" id="dom-c3976e0e-38db-410e-861a-36d04a3a7494">
                        <form class="needs-validation" novalidate method="POST" action="{{ route('admin.createLogins', $client->client_id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <fieldset class="border p-3 rounded">
                                        <legend class="w-auto px-1 text-primary fw-bold">
                                            <i class="fas fa-book-open me-1"></i> Client Details
                                        </legend>
                                        <div class="mt-5 mb-4">
                                            <label>Client Name</label>
                                            <input type="text" name="clientName" value="{{ $client->client_name }}" class="form-control" required>
                                        </div>

                                        <div class="mb-4">
                                            <label>Email Address</label>
                                            <input type="email" name="clientEmail" value="{{ $client->email }}" class="form-control" required>
                                        </div>
                                        <div class="mb-4">
                                            <label>Phone Number</label>
                                            <input type="number" name="clientPhone" value="{{ $client->phone }}" class="form-control" required>
                                        </div>

                                    </fieldset>
                                </div>
                                <div class="col-md-6">
                                    <fieldset class="border p-3 rounded">
                                        <legend class="w-auto px-1 text-primary fw-bold">
                                            <i class="fas fa-book-open me-1"></i> Client Logins
                                        </legend>
                                        <div class="mt-5 mb-4">
                                            <label>Username</label>
                                            <input type="text" name="username" value="{{ $user == null ? null : $user->username }}" class="form-control" required>
                                        </div>

                                        <div class="mb-4">
                                            <label>Password</label>
                                            <input type="password" name="password" class="form-control" required>
                                        </div>
                                        <div class="mb-4">
                                            <label>Password Confirmation</label>
                                            <input type="password" name="password_confirmation" class="form-control" required>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="d-flex justify-content-center mb-3 mt-5">
                                    <button type="submit" class="btn btn-primary col-md-7">Create/Update Logins</button>
                                </div>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
@endsection
