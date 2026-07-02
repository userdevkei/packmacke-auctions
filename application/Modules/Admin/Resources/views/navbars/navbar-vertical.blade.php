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

            </div><a class="navbar-brand" href="{{ route('dashboard') }}">
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
                    <div class="col-auto navbar-vertical-label">Tea Deliveries
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewDeliveryOrders') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-shipping-fast"></span></span><span class="nav-link-text ps-1">Delivery Orders</span>
                    </div>
                  </a>
                  <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewLLIs') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-dolly"></span></span><span class="nav-link-text ps-1">Tea  Collections </span>
                    </div>
                  </a>

                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewDirectDeliveries') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-truck"></span></span><span class="nav-link-text ps-1">Direct Deliveries </span>
                        </div>
                    </a>
                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.foreignTeas') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-globe"></span></span><span class="nav-link-text ps-1">Foreign Teas </span>
                        </div>
                    </a>
                </li>
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Stock Position
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewDeliveries') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-chart-line"></span></span><span class="nav-link-text ps-1">Teas in Stock</span>
                    </div>
                  </a>
                  <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewBlendBalances') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-balance-scale"></span></span><span class="nav-link-text ps-1">Blend Balances</span>
                    </div>
                  </a>
                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.teaSamplesRequest') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-exclamation-triangle"></span></span><span class="nav-link-text ps-1">Tea Discrepancies</span>
                    </div>
                  </a>
                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.allArchivedTeas') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-regular fa-folder-open"></span></span><span class="nav-link-text ps-1">Archived Teas</span>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Tea Transfers
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewInternalTransfers') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-arrow-down-up-across-line"></span></span><span class="nav-link-text ps-1">Internal </span>
                        </div>
                    </a>
                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewExternalTransfers') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-exchange-alt" aria-hidden="true"></span></span><span class="nav-link-text ps-1">External </span>
                        </div>
                    </a>
                </li>
                  <li class="nav-item">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">Auctions
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>

                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.teaAuction') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-gavel"></span></span><span class="nav-link-text ps-1">Auctioned Teas </span>
                          </div>
                      </a>
                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewSales') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-calendar-week"></span></span><span class="nav-link-text ps-1">Auction Sales </span>
                          </div>
                      </a>
                  </li>
                  <li class="nav-item">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">Private Sale
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>
                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.teaPrivateSale') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-cart-shopping"></span></span><span class="nav-link-text ps-1">Sold Teas </span>
                          </div>
                      </a>
                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewPrivateSales') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-calendar-day"></span></span><span class="nav-link-text ps-1">Private Sales </span>
                          </div>
                      </a>
                  </li>
                <li class="nav-item">
                  <!-- label-->
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                    <div class="col-auto navbar-vertical-label">Tea Shipment
                    </div>
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  </div>
                  <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewShippingInstructions') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-rocket"></span></span><span class="nav-link-text ps-1">Straight Line </span>
                    </div>
                  </a>

                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewBlendProcessing') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-cogs" aria-hidden="true"></span></span><span class="nav-link-text ps-1">Blended Process </span>
                        </div>
                    </a>
                    <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewRebagging') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-box-open" aria-hidden="true"></span></span><span class="nav-link-text ps-1">Rebagging Process </span>
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
                      <!-- parent pages--><a class="nav-link" href="{{ route('tasks.dashboard') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-list"></span></span><span class="nav-link-text ps-1">Tasks </span>
                          </div>
                      </a>
                  </li>

                  <li class="nav-item">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">Verified Reports
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>
                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewReportRequest') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-file"></span></span><span class="nav-link-text ps-1">View Reports</span>
                          </div>
                      </a>

                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.viewPendingTCIs') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-truck-front"></span></span><span class="nav-link-text ps-1">TCI Collection</span>
                          </div>
                      </a>

                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.stockAgingReport') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-hourglass-start"></span></span><span class="nav-link-text ps-1">Aging Analysis</span>
                          </div>
                      </a>

                      <!-- parent pages--><a class="nav-link" href="{{ route('admin.stockPerWarehouse') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-chart-column"></span></span><span class="nav-link-text ps-1">Stock Analysis</span>
                          </div>
                      </a>
                  </li>

                  <li class="nav-item mb-5">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">System & Settings
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#settings" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="settings">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-gears"></span></span><span class="nav-link-text ps-1">Setup & Settings </span>
                          </div>
                      </a>
                              <ul class="nav collapse" id="settings">
                                  <li class="nav-item">
                                  <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.users') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-users-gear"></span> Users </span>
                                          </div>
                                  </a>
                                  </li>
                                  <li class="nav-item">
                                  <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.userPermissions') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-users-gear"></span> User Permissions </span>
                                          </div>
                                  </a>
                                  </li>
                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewClients') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-users-between-lines"></span> Clients </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewClearingAgents') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-user-tie"></span> Clearing Agents </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewBrokers') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-handshake-slash"></span> Brokers </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewGardens') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-wheat-awn"></span> Tea Gardens </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewTeaGrade') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-trademark"></span> Tea Grades </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewWarehouses') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-warehouse"></span> Producer Warehouses </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewTransporters') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-truck-arrow-right"></span> Transporters </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewShippingDestinations') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-anchor"></span> Destinations </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewStations') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-boxes-stacked"></span> PMHL Warehouses </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewOurLocations') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-map-location-dot"></span> PMHL Localities </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewRoles') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-screwdriver-wrench"></span> User Roles </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-1" href="{{ route('admin.viewDepartments') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-diagram-project"></span> Departments </span>
                                          </div>
                                      </a>
                                  </li>

                                  <li class="nav-item">
                                      <!-- parent pages--><a class="nav-link mb-4" href="{{ route('admin.viewSignatories') }}">
                                          <div class="d-flex align-items-center"><span class="nav-link-text ps-1"><span class="fa-solid fa-file-signature"></span> Signatories </span>
                                          </div>
                                      </a>
                                  </li>
                        </ul>
                  </li>
              </ul>
            </div>
          </div>
        </nav>
        <!-- ----- navbar-vertical end -------------- -->
