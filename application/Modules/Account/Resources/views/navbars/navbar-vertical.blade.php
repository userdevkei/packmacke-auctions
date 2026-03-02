<!-- ---- navbar-vertical starts------------ -->
<style>
    .nav-link-icon {
        display: inline !important; /* Force display across breakpoints */
    }
</style>
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
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                    <!-- parent pages--><a class="nav-link dropdown-indicator" href="#revenue" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="revenue">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-wallet"></span></span><span class="nav-link-text ps-1">Sales</span>
                        </div>
                    </a>
                    <ul class="nav collapse" id="revenue">
                        <li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewInvoices') }}">
                                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-bag-shopping"></span></span> <span class="nav-link-text ps-1"> Sales Invoices</span>
                                </div>
                            </a>
                            <!-- more inner pages-->
                        </li>

                        <li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewAllTransactions') }}">
                                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-receipt"></span></span><span class="nav-link-text ps-1">Receipts</span>
                                </div>
                            </a>
                            <!-- more inner pages-->
                        </li>

                        <li class="nav collapse" id="revenue">
                            <li class="nav-item"><a class="nav-link dropdown-indicator" href="#revenue-level-two" data-bs-toggle="collapse" aria-expanded="false" aria-controls="revenue">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-list"></span></span><span class="nav-link-text ps-1"> Historical Sales</span>
                                    </div>
                                </a>
                                <!-- more inner pages-->
                                <ul class="nav collapse" id="revenue-level-two">
                                    <li class="nav-item"><a class="nav-link" href="{{ route('accounts.salesFYTaxes') }}">
                                            <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Sales Invoices</span>
                                            </div>
                                        </a>
                                        <!-- more inner pages-->
                                    </li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('accounts.receiptsFY') }}">
                                            <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Receipts</span>
                                            </div>
                                        </a>
                                        <!-- more inner pages-->
                                    </li>
                                </ul>
                            </li>
                    </ul>
                <li class="nav-item">
                    <div class="col ps-0">
                        <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                    <!-- parent pages--><a class="nav-link dropdown-indicator" href="#purchase" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="purchase">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-coins"></span></span><span class="nav-link-text ps-1">Purchases</span>
                        </div>
                    </a>
                  <!-- label-->
                    <ul class="nav collapse" id="purchase">
                      <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewPurchases') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-cart-shopping"></span></span><span class="nav-link-text ps-1">Purchase Vouchers</span>
                        </div>
                            </a></li>
                      <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewPurchasePayments') }}" role="button" data-bs-toggle="" aria-expanded="false">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-cash-register"></span></span><span class="nav-link-text ps-1">Payments</span>
                        </div>
                            </a></li>
                        <li class="nav collapse" id="purchase">
                        <li class="nav-item"><a class="nav-link dropdown-indicator" href="#purchase-level-two" data-bs-toggle="collapse" aria-expanded="false" aria-controls="purchase">
                                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-list"></span></span><span class="nav-link-text ps-1"> Historical Purchases</span>
                                </div>
                            </a>
                            <!-- more inner pages-->
                            <ul class="nav collapse" id="purchase-level-two">
                                <li class="nav-item"><a class="nav-link" href="{{ route('accounts.purchaseFYTaxes') }}">
                                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Purchase Vouchers</span>
                                        </div>
                                    </a>
                                    <!-- more inner pages-->
                                </li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('accounts.paymentsFY') }}">
                                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Payments</span>
                                        </div>
                                    </a>
                                    <!-- more inner pages-->
                                </li>
                            </ul>
                        </li>
                    </ul>

                  <li class="nav-item">
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#petty" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="petty">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-money-bill"></span></span><span class="nav-link-text ps-1">Petty Cash </span>
                          </div>
                      </a>
                      <!-- label-->
                      <ul class="nav collapse" id="petty">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewPettyCash') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><i class="fa fa-dollar-sign"></i></span><span class="nav-link-text ps-1">Petty Cash Vouchers</span>
                                  </div>
                              </a></li>
                      </ul>
                  </li>

                  <li class="nav-item">
                      <!-- label-->
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                     <!-- parent pages--><a class="nav-link dropdown-indicator" href="#banks" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="banks">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-building-columns"></span></span><span class="nav-link-text ps-1">Bank Reconciliation</span>
                          </div>
                      </a>

                      <ul class="nav collapse" id="banks">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewBanks') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-regular fa-folder-open"></span></span><span class="nav-link-text ps-1">View Banks</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewReconciledBanks') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-clock-rotate-left"></span></span><span class="nav-link-text ps-1">Reconciled Statements</span>
                                  </div>
                              </a></li>
                      </ul>

                  </li>
                  <li class="nav-item">
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#journals" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="journals">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-book"></span></span><span class="nav-link-text ps-1">Journals</span>
                          </div>
                      </a>
                      <!-- label-->
                      <ul class="nav collapse" id="journals">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewSystemJournals') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-regular fa-folder-open"></span></span><span class="nav-link-text ps-1">Journal Voucher</span>
                                  </div>
                              </a></li>
                      </ul>
                  </li>
                  <li class="nav-item">
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#balances" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="balances">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-balance-scale"></span></span><span class="nav-link-text ps-1">Balances</span>
                          </div>
                      </a>
                      <!-- label-->
                      <ul class="nav collapse" id="balances">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewOpeningBalances') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-calculator"></span></span><span class="nav-link-text ps-1">Opening Balances</span>
                                  </div>
                              </a></li>
                      </ul>
                  </li>
                <li class="nav-item">
                  <!-- label-->
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                    <!-- parent pages--><a class="nav-link dropdown-indicator" href="#accounts" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="accounts">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-chart-pie"></span></span><span class="nav-link-text ps-1">Chart of Accounts</span>
                        </div>
                    </a>
                    <ul class="nav collapse" id="accounts">
                        <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewAccounts') }}" role="button" data-bs-toggle="" aria-expanded="false">
                            <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-users-line"></span></span><span class="nav-link-text ps-1">Master Ledger </span>
                            </div>
                            </a></li>
                        <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.accountSubCategories') }}" role="button" data-bs-toggle="" aria-expanded="false">
                            <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-users-between-lines"></span></span><span class="nav-link-text ps-1">Group Ledger</span>
                            </div>
                            </a></li>
                        <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewChartAccounts') }}" role="button" data-bs-toggle="" aria-expanded="false">
                            <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-user"></span></span><span class="nav-link-text ps-1">Sub Group </span>
                            </div>
                            </a></li>
                        <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewClientAccounts') }}" role="button" data-bs-toggle="" aria-expanded="false">
                            <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-money-bills"></span></span><span class="nav-link-text ps-1">Ledger </span>
                            </div>
                            </a></li>
                    </ul>
                </li>

                  <li class="nav-item">
                      <!-- label-->
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#currencies" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="currencies">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-coins"></span></span><span class="nav-link-text ps-1">Currencies</span>
                          </div>
                      </a>
                      <ul class="nav collapse" id="currencies">
                      <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.exchangeRates') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-money-bill-trend-up"></span></span><span class="nav-link-text ps-1">Exchange Rates </span>
                          </div>
                              </a></li>
                      <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewCurrencies') }}" role="button" data-bs-toggle="" aria-expanded="false">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-money-bill"></span></span><span class="nav-link-text ps-1">Currencies </span>
                          </div>
                              </a></li>
                      </ul>
                  </li>

                <li class="nav-item">
                  <!-- label-->
                    <div class="col ps-0">
                      <hr class="mb-0 navbar-vertical-divider" />
                    </div>
                  <!-- parent pages--><a class="nav-link" href="{{ route('accounts.viewFinancialYears') }}" role="button" data-bs-toggle="" aria-expanded="false">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-calendar-days"></span></span><span class="nav-link-text ps-1">View Financial Years </span>
                    </div>
                  </a>
                </li>

                  <li class="nav-item">
                      <!-- label-->
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#taxes" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="taxes">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span><span class="nav-link-text ps-1">Taxes</span>
                          </div>
                      </a>
                      <ul class="nav collapse" id="taxes">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewTaxBrackets') }}" role="button" data-bs-toggle="" aria-expanded="false">
                              <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-hand-holding-dollar"></span></span><span class="nav-link-text ps-1">Tax Brackets</span>
                              </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewTaxes') }}" role="button" data-bs-toggle="" aria-expanded="false">
                              <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-sack-xmark"></span></span><span class="nav-link-text ps-1">Taxes</span>
                              </div>
                              </a></li>
                      </ul>
                  </li>

                  <li class="nav-item mb-0">
                      <!-- label-->
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#report" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="report">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-list-ul"></span></span><span class="nav-link-text ps-1">Accounting Reports</span>
                          </div>
                      </a>
                      <ul class="nav collapse" id="report">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.getPlFinancialYears') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-money-check-dollar"></span></span><span class="nav-link-text ps-1">Ledger Report</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.getLedgerFinancialYears') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-bar-chart"></span></span><span class="nav-link-text ps-1">P&L Statement</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.balanceSheetFy') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-scale-balanced"></span></span><span class="nav-link-text ps-1">Balance Sheet</span>
                                  </div>
                              </a>
                          </li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.getAccountStatementFinancialYears') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-filter-circle-dollar"></span></span><span class="nav-link-text ps-1">Trial Balance</span>
                                  </div>
                              </a>
                          </li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.dayBook') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa fa-book"></span></span><span class="nav-link-text ps-1">DayBook</span>
                                  </div>
                              </a>
                          </li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewAgingAnalysis') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-regular fa-hourglass-half"></span></span><span class="nav-link-text ps-1">Aging Analysis</span>
                                  </div>
                              </a></li>
                      </ul>
                  </li>
                  <li class="nav-item mb-0">
                      <!-- label-->
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#other" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="other">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-regular fa-rectangle-list"></span></span><span class="nav-link-text ps-1">Other Reports</span>
                          </div>
                      </a>
                      <ul class="nav collapse" id="other">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewDeliveries') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-boxes"></span></span><span class="nav-link-text ps-1">Stocks Report</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.transportDetails') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-truck-loading"></span></span><span class="nav-link-text ps-1">Transport Report</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewShipments') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-shipping-fast"></span></span><span class="nav-link-text ps-1">Shipping Report</span>
                                  </div>
                              </a></li>
                      </ul>
                  </li>
                  <li class="nav-item mb-0">
                      <!-- label-->
                      <div class="col ps-0">
                          <hr class="mb-0 navbar-vertical-divider" />
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#stocks" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="stocks">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-chart-column"></span></span><span class="nav-link-text ps-1">Stocks</span>
                          </div>
                      </a>
                      <ul class="nav collapse" id="stocks">
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewInternalTransfers') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-arrows-rotate"></span></span><span class="nav-link-text ps-1">Internal Transfers</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.viewExternalTransfers') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-right-from-bracket"></span></span><span class="nav-link-text ps-1"> External Transfers</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.unbilledClients') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-file-circle-question"></span></span><span class="nav-link-text ps-1">Unbilled Clients</span>
                                  </div>
                              </a></li>
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.closingStockReport') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-box"></span></span><span class="nav-link-text ps-1">Closing Stock</span>
                                  </div>
                              </a></li>

                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('accounts.stockCollectionReport') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-truck-fast"></span></span><span class="nav-link-text ps-1">Stock Collection</span>
                                  </div>
                              </a></li>
                      </ul>
                  </li>
                  @canuser('inventory.access')
                    <li class="nav-item">
                      <!-- label-->
                      <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                          <div class="col-auto navbar-vertical-label">Inventory
                          </div>
                          <div class="col ps-0">
                              <hr class="mb-0 navbar-vertical-divider" />
                          </div>
                      </div>
                      <!-- parent pages--><a class="nav-link dropdown-indicator" href="#inventory" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="inventory">
                          <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-boxes"></span></span><span class="nav-link-text ps-1">Inventory</span>
                          </div>
                      </a>
                      <ul class="nav collapse" id="inventory">
                          @canuser('inventory.view')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('instock.view') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-warehouse"></span></span><span class="nav-link-text ps-1">In Stock</span>
                                  </div>
                              </a></li>
                          @endcanuser

                          @canuser('inventory.viewItem')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('lpos.view') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-cart-shopping"></span></span><span class="nav-link-text ps-1">LPOs</span>
                                  </div>
                              </a></li>
                          @endcanuser

                          @canuser('inventory.viewItemsTransfer')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('purchases.view') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-truck"></span></span><span class="nav-link-text ps-1">Incoming</span>
                                  </div>
                              </a></li>
                          @endcanuser

                          @canuser('inventory.viewItemsTransfer')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('inventory.utilization') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-hand-holding"></span></span><span class="nav-link-text ps-1">Utilization</span>
                                  </div>
                              </a></li>
                          @endcanuser

                          @canuser('inventory.viewItem')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('inventory.view') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-circle-plus"></span></span><span class="nav-link-text ps-1">Inventory Items</span>
                                  </div>
                              </a></li>
                          @endcanuser

                          @canuser('inventory.viewInventoryCategory')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('itemCategory.view') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-tags"></span></span><span class="nav-link-text ps-1">Item Category</span>
                                  </div>
                              </a></li>
                          @endcanuser

                          @canuser('supplier.view')
                          <!-- parent pages--><li class="nav-item"><a class="nav-link" href="{{ route('suppliers.view') }}" role="button" data-bs-toggle="" aria-expanded="false">
                                  <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fa-solid fa-user-tie"></span></span><span class="nav-link-text ps-1">Item Suppliers</span>
                                  </div>
                              </a></li>
                          @endcanuser
                      </ul>
                  </li>
                  @endcanuser
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
              </ul>
            </div>
          </div>
        </nav>
        <!-- ----- navbar-vertical end -------------- -->
