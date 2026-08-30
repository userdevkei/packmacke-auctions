<!-- ---- navbar-vertical starts------------ -->
        <nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
          <script>
            var navbarStyle = localStorage.getItem("navbarStyle");
            if (navbarStyle && navbarStyle !== 'transparent') {
              document.querySelector('.navbar-vertical').classList.add(`navbar-${navbarStyle}`);
            }
          </script>
          <div class="d-flex align-items-center">
            <div class="toggle-icon-wrapper">
              <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>

            </div><a class="navbar-brand" href="{{ route('tasks.dashboard') }}">
              <div class="d-flex align-items-center py-3"><img class="me-2" src="{{ url('assets/img/favicons/icon.png') }}" alt="" width="40" /><span class="font-sans-serif fs-sm"></span>
              </div>
            </a>
          </div>
          <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content scrollbar">
              <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Home
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <!-- parent pages--><a class="nav-link" href="{{ route('tasks.dashboard') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-gauge"></span></span><span class="nav-link-text ps-1">Dashboard</span>
                    </div>
                  </a>
                </li>
                  <li class="nav-item">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">Task Manager
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>
                      <!-- parent pages--><a class="nav-link" href="{{ route('tasks.all') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-list"></span></span><span class="nav-link-text ps-1">Tasks </span>
                          </div>
                      </a>
                      @if(auth()->user()->role_id == 1)
                          <!-- parent pages--><a class="nav-link" href="{{ route('tasks.manageUsers') }}" role="button" data-bs-toggle="" aria-expanded="false">
                              <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-users"></span></span><span class="nav-link-text ps-1">Manage Users </span>
                              </div>
                          </a>
                      @endif
                  </li>
                  @if(auth()->user()->role_id != 11)
                    <li class="nav-item">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">Main Account
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>
                      @php
                          $roleId = auth()->user()->role_id;

                          $dashboardMap = [
                              1 => 'Admin Dashboard',
                              2 => 'Stocks Dashboard',
                              3 => 'Stocks Dashboard',
                              4 => 'Stocks Dashboard',
                              5 => 'Stocks Dashboard',
                              6 => 'Stocks Dashboard',
                              7 => 'Accounts Dashboard',
                              8 => 'Accounts Dashboard',
                              9 => 'Accounts Dashboard',
                          ];

                          $account = $dashboardMap[$roleId] ?? 'Client Dashboard';
                      @endphp

                          <!-- parent pages--><a class="nav-link" href="{{ route('dashboard') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-user-shield"></span></span><span class="nav-link-text ps-1">{{ $account }} </span>
                          </div>
                      </a>
                  </li>
                  @endif

              </ul>
            </div>
          </div>
        </nav>
        <!-- ----- navbar-vertical end -------------- -->
