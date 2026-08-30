<div class="card">
    <div class="card-body overflow-hidden p-lg-6">
        <div class="row align-items-center">
            <div class="row g-3 mb-3">
                <div class="col-xxl-6 col-lg-12">
                    <div class="card h-100">
                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-3.png') }});">
                        </div>
                        <!--/.bg-holder-->

                        <div class="card-header z-1">
                            <h5 class="text-primary">Welcome {{ auth()->user()->user->surname }}! </h5>
                            <h6 class="text-600">Here are some quick links for you to start </h6>
                        </div>
                        <div class="card-body z-1">
                            <div class="row g-2 h-100 align-items-end">
                                <div class="col-sm-6 col-md-5">
                                    <div class="d-flex position-relative">
                                        <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fa fa-user text-primary"></span></div>
                                        <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                <h6 class="mb-0">Role</h6>
                                            </a>
                                            <p class="mb-0 fs-11 text-500 fs-sm text-lowercase">{{ auth()->user()->role->role_name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-5">
                                    <div class="d-flex position-relative">
                                        <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fas fa-calendar text-warning"></span></div>
                                        <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                <h6 class="mb-0">Active Since</h6>
                                            </a>
                                            <p class="mb-0 fs-11 text-500">{{ Carbon\Carbon::parse(auth()->user()->created_at)->format('D, d/m/y H:i') }} </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-5">
                                    <div class="d-flex position-relative">
                                        <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fa-solid fa-location-pin-lock text-success"></span></div>
                                        <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                <h6 class="mb-0">Location</h6>
                                            </a>
                                            <p class="mb-0 fs-11 text-500">{{ auth()->user()->station->location->location_name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-5">
                                    <div class="d-flex position-relative">
                                        <div class="icon-item icon-item-sm border rounded-3 shadow-none me-2"><span class="fa-solid fa-warehouse text-danger"></span></div>
                                        <div class="flex-1"><a class="stretched-link text-800" href="#!">
                                                <h6 class="mb-0">Warehouse</h6>
                                            </a>
                                            <p class="mb-0 fs-11 text-500">{{ auth()->user()->station->station_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex flex-between-center">
                            <h5 class="mb-0">Blends</h5><a class="btn btn-link btn-sm px-0" href="#!">Report<span class="fas fa-chevron-right ms-1 fs-11"> </span></a>
                        </div>
                        <div class="card-body">
                            <p class="fs-10 text-600">Processed Today</p>
                            <div class="progress mb-3 rounded-pill" style="height: 6px;" role="progressbar" aria-valuenow="43.72" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-progress-gradient rounded-pill" style="width: 75%"></div>
                            </div>
                            <p class="mb-0 text-primary">75% completed</p>
                            <p class="mb-0 fs-11 text-500">Jan 1st to 30th</p>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex flex-between-center">
                            <h5 class="mb-0">Straight Lines</h5><a class="btn btn-link btn-sm px-0" href="#!">Report<span class="fas fa-chevron-right ms-1 fs-11"> </span></a>
                        </div>
                        <div class="card-body">
                            <p class="fs-10 text-600">Completed Today</p>
                            <div class="progress mb-3 rounded-pill" style="height: 6px;" role="progressbar" aria-valuenow="43.72" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-progress-gradient rounded-pill" style="width: 75%"></div>
                            </div>
                            <p class="mb-0 text-primary">75% completed</p>
                            <p class="mb-0 fs-11 text-500">Jan 1st to 30th</p>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row g-3 mb-3">
                <div class="col-xxl-6 col-lg-12">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-1.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Teas Under Collection<span class="badge badge-subtle-warning rounded-pill ms-2">-0.23%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-warning" id="stat-uncollected" data-stat="uncollected">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(1)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Late Collection<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" id="stat-late" data-stat="late">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(2)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Teas Without TCI<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" id="stat-noTCI" data-stat="noTCI">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(3)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Teas Overstayed <span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" id="stat-overstayed" data-stat="overstayed">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(4)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-1.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Internal Transfers Pending<span class="badge badge-subtle-warning rounded-pill ms-2">-0.23%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-warning" id="stat-internal" data-stat="internal">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(5)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>External Transfers Pending<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" id="stat-external" data-stat="external">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(6)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Straight Lines Pending<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" id="stat-si" data-stat="si">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(7)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="card overflow-hidden" style="min-width: 12rem">
                                        <div class="bg-holder bg-card" style="background-image:url({{ url('assets/img/icons/spot-illustrations/corner-2.png') }});">
                                        </div>
                                        <!--/.bg-holder-->

                                        <div class="card-body position-relative">
                                            <h6>Blends Pending<span class="badge badge-subtle-info rounded-pill ms-2">0.0%</span></h6>
                                            <div class="display-4 fs-5 mb-2 fw-normal font-sans-serif text-info" id="stat-blend" data-stat="blend">
                                                <span class="placeholder-glow"><span class="placeholder col-6"></span></span>
                                            </div>
                                            <a class="fw-semi-bold fs-10 text-nowrap" href="{{ route('admin.dashboardReport', base64_encode(8)) }}">view all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex flex-between-center">
                            <h6 class="mb-0">Client Statistics</h6>
                            <div class="dropdown font-sans-serif btn-reveal-trigger">
                                <button class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button" id="dropdown-project-statistics" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                                <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-project-statistics"><a class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                                    <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row mb-2">
                                <div class="col-6 border-end border-200">
                                    <h4 class="mb-0">{{ $clients->count() }} </h4>
                                    <p class="fs-10 text-600 mb-0">All Clients</p>
                                </div>
                                <div class="col-3 border-end text-center border-200">
                                    <h4 class="fs-9 mb-0" id="stat-stocked">
                                        <span class="placeholder-glow"><span class="placeholder col-8"></span></span>
                                    </h4>
                                    <p class="fs-10 text-600 mb-0">Clients (stocked) </p>
                                </div>
                                <div class="col-3 text-center">
                                    <h4 class="fs-9 mb-0" id="stat-outofstock">
                                        <span class="placeholder-glow"><span class="placeholder col-8"></span></span>
                                    </h4>
                                    <p class="fs-10 text-600 mb-0">Clients (out of stock)</p>
                                </div>
                            </div>
                            <p class="fs-10 mb-2 mt-3">Top Clients By Stock</p>

                            <div class="d-flex justify-content-between">
                                <h6 class="col-6">Client Name</h6>
                                <h6 class="col-3">Packages </h6>
                                <h6 class="col-3">Net Weight</h6>
                            </div>

                            <div id="top-clients-list">
                                <div class="d-flex flex-between-center rounded-3 bg-body-tertiary p-3 mb-2">
                                    <div class="placeholder-glow w-100"><span class="placeholder col-4"></span></div>
                                </div>
                                <div class="d-flex flex-between-center rounded-3 bg-body-tertiary p-3 mb-2">
                                    <div class="placeholder-glow w-100"><span class="placeholder col-4"></span></div>
                                </div>
                                <div class="d-flex flex-between-center rounded-3 bg-body-tertiary p-3 mb-2">
                                    <div class="placeholder-glow w-100"><span class="placeholder col-4"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dotColors = ['primary', 'warning', 'secondary', 'info', 'dark', 'danger', 'success'];

        fetch('{{ route("admin.dashboard.stats") }}')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                animateStat('uncollected', data.uncollected);
                animateStat('late', data.late);
                animateStat('noTCI', data.noTCI);
                animateStat('overstayed', data.overstayed);
                animateStat('internal', data.internal);
                animateStat('external', data.external);
                animateStat('si', data.si);
                animateStat('blend', data.blend);

                document.getElementById('stat-stocked').textContent = data.stockedCount;
                document.getElementById('stat-outofstock').textContent = data.clientsCount - data.stockedCount;

                const list = document.getElementById('top-clients-list');
                list.innerHTML = '';

                if (!data.topClients || data.topClients.length === 0) {
                    list.innerHTML = '<p class="fs-10 text-500 mb-0">No stocked clients yet.</p>';
                    return;
                }

                data.topClients.forEach(function (c, i) {
                    list.insertAdjacentHTML('beforeend', `
                    <div class="d-flex flex-between-center rounded-3 bg-body-tertiary p-3 mb-2">
                        <h6 class="mb-0 col-6">
                            <span class="fas fa-circle fs-10 me-3 text-${dotColors[i] || 'primary'}"></span>
                            ${c.client_name}
                        </h6>
                        <a class="fs-11 text-600 mb-0 col-3" href="#!">${Number(c.packages).toLocaleString()}</a>
                        <a class="fs-11 text-600 mb-0 col-3" href="#!">${Number(c.net_weight).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</a>
                    </div>
                `);
                });
            })
            .catch(function (err) {
                console.error('Dashboard stats failed to load', err);
                document.querySelectorAll('[data-stat]').forEach(function (el) { el.textContent = '—'; });
                document.getElementById('stat-stocked').textContent = '—';
                document.getElementById('stat-outofstock').textContent = '—';
                document.getElementById('top-clients-list').innerHTML = '<p class="fs-10 text-danger mb-0">Failed to load.</p>';
            });

        function animateStat(key, value) {
            const el = document.getElementById('stat-' + key);
            if (!el) return;
            el.innerHTML = '<span data-countup>0</span>';
            const span = el.querySelector('[data-countup]');
            if (window.countUp && window.countUp.CountUp) {
                new window.countUp.CountUp(span, value, { decimalPlaces: 0 }).start();
            } else {
                span.textContent = value;
            }
        }
    });
</script>
