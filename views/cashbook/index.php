<?php
/** @var string $csrf */
/** @var list<array{id:int|string,name:string}> $cashbookCustomers */
$cashbookCustomers = $cashbookCustomers ?? [];
$reportsLegacyUrl = Helpers::baseUrl('index.php?page=reports');
$dashboardUrl = Helpers::baseUrl('index.php?page=dashboard');
$base = Helpers::baseUrl('');
$cbCssVer = '1';
$cbJsVer = '1';
try {
  $cbCssPath = __DIR__ . '/../../public/assets/css/cashbook.css';
  $cbJsPath = __DIR__ . '/../../public/assets/js/cashbook.js';
  if (is_file($cbCssPath)) { $cbCssVer = (string) @filemtime($cbCssPath); }
  if (is_file($cbJsPath)) { $cbJsVer = (string) @filemtime($cbJsPath); }
} catch (Throwable $e) {
  // ignore
}
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/cashbook.css?v=' . rawurlencode($cbCssVer)); ?>">

<div class="cashbook-app container-fluid px-0 pb-5">
  <div class="row g-0">
    <!-- Desktop sidebar -->
    <aside class="col-lg-3 col-xl-2 d-none d-lg-flex flex-column cashbook-sidebar border-end bg-body-tertiary" aria-label="Cash book navigation">
      <div class="p-3 border-bottom border-secondary-subtle">
        <div class="fw-semibold text-body-secondary small text-uppercase letter-spacing-sm">Accounting</div>
        <div class="fs-5 fw-bold text-body">Cash Book</div>
      </div>
      <nav class="nav flex-column gap-1 p-2 flex-grow-1">
        <a class="nav-link rounded-pill px-3 py-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2 active" data-panel="transactions"><i class="bi bi-arrow-left-right me-2"></i>Transactions</button>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2" data-panel="accounts"><i class="bi bi-bank me-2"></i>Accounts</button>
        <button type="button" class="nav-link text-start rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#cashbookTransferModal"><i class="bi bi-shuffle me-2"></i>Transfer</button>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2" data-panel="reports"><i class="bi bi-graph-up-arrow me-2"></i>Reports</button>
      </nav>
    </aside>

    <!-- Main column -->
    <div class="col-12 col-lg-9 col-xl-10 cashbook-main">
      <!-- Top bar -->
      <div class="cashbook-topbar border-bottom bg-body shadow-sm">
        <div class="d-flex align-items-center gap-2 flex-wrap px-2 px-md-3 py-2">
          <button class="btn btn-outline-secondary btn-sm rounded-pill d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#cashbookMenu" aria-controls="cashbookMenu"><i class="bi bi-list" aria-hidden="true"></i></button>
          <div class="flex-grow-1 min-w-0 d-none align-items-center flex-wrap gap-1 cashbook-show-for-accounts-mgmt">
            <span class="fw-semibold text-body"><i class="bi bi-bank me-1 text-primary"></i>Accounts</span>
            <span class="small text-muted d-none d-md-inline ms-2">Ledgers and customer links</span>
          </div>
          <div class="flex-grow-1 min-w-0 cashbook-hide-for-accounts-mgmt" style="max-width: 280px;">
            <label class="visually-hidden" for="cbAccountSelect">Account</label>
            <select id="cbAccountSelect" class="form-select form-select-sm rounded-pill border-secondary-subtle" data-enhance="false" aria-label="Cash book account"></select>
          </div>
          <div class="flex-grow-1 min-w-0 cashbook-hide-for-accounts-mgmt" style="min-width: 160px;">
            <label class="visually-hidden" for="cbSearch">Search</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text rounded-start-pill bg-white border-end-0"><i class="bi bi-search text-muted" aria-hidden="true"></i></span>
              <input type="search" id="cbSearch" class="form-control form-control-sm rounded-end-pill border-start-0" placeholder="Search notes…" autocomplete="off">
            </div>
          </div>
          <div class="d-flex align-items-center gap-1 cashbook-hide-for-accounts-mgmt">
            <label class="visually-hidden" for="cbAnchorDate">Reference date</label>
            <input type="date" id="cbAnchorDate" class="form-control form-control-sm rounded-pill" title="Reference date for period">
          </div>
          <div class="d-flex flex-wrap gap-2 ms-lg-auto cb-top-quick-actions">
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3" id="cbBtnIncomeDesk"><i class="bi bi-plus-circle me-1"></i><span class="d-none d-sm-inline">Income</span></button>
            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" id="cbBtnExpenseDesk"><i class="bi bi-dash-circle me-1"></i><span class="d-none d-sm-inline">Expense</span></button>
            <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 text-dark" data-bs-toggle="modal" data-bs-target="#cashbookTransferModal"><i class="bi bi-shuffle me-1"></i><span class="d-none d-sm-inline">Transfer</span></button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="cbBtnExportExcel"><i class="bi bi-file-earmark-excel me-1"></i><span class="d-none d-sm-inline">Excel</span></button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="cbBtnExportPdf"><i class="bi bi-file-earmark-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span></button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="cbBtnPrint"><i class="bi bi-printer me-1"></i><span class="d-none d-sm-inline">Print</span></button>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cashbookCustomerModal"><i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Customer</span></button>
          </div>
        </div>
        <div class="px-2 px-md-3 pb-2 cashbook-hide-for-accounts-mgmt">
          <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4 col-xl-3">
              <label class="visually-hidden" for="cbTxnCategoryFilter">Category</label>
              <select id="cbTxnCategoryFilter" class="form-select form-select-sm rounded-pill border-secondary-subtle" data-enhance="false" aria-label="Filter by transaction category">
                <option value="all">All categories</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
                <option value="transfer">Transfer</option>
                <option value="collection">Collections</option>
                <option value="payroll">Payroll</option>
                <option value="fuel">Fuel</option>
                <option value="general">General</option>
              </select>
            </div>
            <div class="col-12 col-md-4 col-xl-3">
              <label class="visually-hidden" for="cbTxnSort">Sort entries</label>
              <select id="cbTxnSort" class="form-select form-select-sm rounded-pill border-secondary-subtle" data-enhance="false" aria-label="Sort cashbook entries">
                <option value="date_desc">Newest first</option>
                <option value="date_asc">Oldest first</option>
                <option value="amount_desc">Amount high → low</option>
                <option value="amount_asc">Amount low → high</option>
              </select>
            </div>
            <div class="col-12 col-md-4 col-xl-6 text-md-end">
              <span class="small text-muted">Search, category, and sort stay client-side for faster ledger review.</span>
            </div>
          </div>
        </div>
        <ul class="nav nav-pills cashbook-tabs gap-1 flex-nowrap overflow-auto px-2 px-md-3 pb-2 mb-0 cashbook-hide-for-accounts-mgmt" role="tablist">
          <li class="nav-item"><button type="button" class="nav-link cb-period-tab active rounded-pill" data-period="all">All</button></li>
          <li class="nav-item"><button type="button" class="nav-link cb-period-tab rounded-pill" data-period="daily">Daily</button></li>
          <li class="nav-item"><button type="button" class="nav-link cb-period-tab rounded-pill" data-period="weekly">Weekly</button></li>
          <li class="nav-item"><button type="button" class="nav-link cb-period-tab rounded-pill" data-period="monthly">Monthly</button></li>
          <li class="nav-item"><button type="button" class="nav-link cb-period-tab rounded-pill" data-period="yearly">Yearly</button></li>
          <li class="nav-item"><button type="button" class="nav-link cb-period-tab rounded-pill" data-period="audit">Audit Trail</button></li>
        </ul>
      </div>

      <div class="px-2 px-md-3 py-3">
        <!-- Summary cards (transaction period — hidden on Accounts panel) -->
        <div class="row g-3 mb-3 cashbook-hide-for-accounts-mgmt">
          <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 cashbook-stat-card cashbook-stat-income">
              <div class="card-body py-3">
                <div class="small text-white-50 text-uppercase fw-semibold mb-1">Total income</div>
                <div class="fs-4 fw-bold text-white" id="cbCardIncome">0.00</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 cashbook-stat-card cashbook-stat-expense">
              <div class="card-body py-3">
                <div class="small text-white-50 text-uppercase fw-semibold mb-1">Total expense</div>
                <div class="fs-4 fw-bold text-white" id="cbCardExpense">0.00</div>
              </div>
            </div>
          </div>
          <div class="col-sm-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100 cashbook-stat-card cashbook-stat-balance">
              <div class="card-body py-3">
                <div class="small text-white-50 text-uppercase fw-semibold mb-1">Balance</div>
                <div class="fs-4 fw-bold text-white" id="cbCardBalance">0.00</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3 cashbook-hide-for-accounts-mgmt">
          <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 cashbook-summary-card">
              <div class="card-body py-3">
                <div class="small text-uppercase text-muted fw-semibold">Opening balance</div>
                <div class="fs-5 fw-bold" id="cbOpeningBalance">0.00</div>
                <div class="small text-muted">Before the selected period</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 cashbook-summary-card">
              <div class="card-body py-3">
                <div class="small text-uppercase text-muted fw-semibold">Monthly income</div>
                <div class="fs-5 fw-bold text-success" id="cbMonthlyIncome">0.00</div>
                <div class="small text-muted">Current selected month</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 cashbook-summary-card">
              <div class="card-body py-3">
                <div class="small text-uppercase text-muted fw-semibold">Monthly expense</div>
                <div class="fs-5 fw-bold text-danger" id="cbMonthlyExpense">0.00</div>
                <div class="small text-muted">Current selected month</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 cashbook-summary-card">
              <div class="card-body py-3">
                <div class="small text-uppercase text-muted fw-semibold">Closing balance</div>
                <div class="fs-5 fw-bold text-primary" id="cbClosingBalance">0.00</div>
                <div class="small text-muted">Current ledger balance</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3 cashbook-hide-for-accounts-mgmt">
          <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm h-100 cashbook-chart-card">
              <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold"><i class="bi bi-graph-up-arrow me-1 text-primary"></i>Cash Flow Trend</span>
                <small class="text-muted">Selected period totals and movement</small>
              </div>
              <div class="card-body">
                <div class="cashbook-chart-wrap">
                  <canvas id="cbCashbookChart" aria-label="Cashbook income expense chart" role="img"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100 cashbook-summary-panel">
              <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold"><i class="bi bi-tags me-1 text-primary"></i>Transaction Categories</span>
                <small class="text-muted">Read-only classification</small>
              </div>
              <div class="card-body">
                <div class="d-grid gap-2">
                  <div class="d-flex justify-content-between align-items-center"><span class="small text-muted">Collections</span><span class="badge text-bg-success">Income</span></div>
                  <div class="d-flex justify-content-between align-items-center"><span class="small text-muted">Payroll</span><span class="badge text-bg-danger">Expense</span></div>
                  <div class="d-flex justify-content-between align-items-center"><span class="small text-muted">Transfers</span><span class="badge text-bg-secondary">Ledger move</span></div>
                  <div class="d-flex justify-content-between align-items-center"><span class="small text-muted">Fuel / General</span><span class="badge text-bg-warning text-dark">Expense</span></div>
                </div>
                <div class="small text-muted mt-3">The filter above matches these classifications without altering saved records.</div>
              </div>
            </div>
          </div>
        </div>

        <div id="cbLoading" class="cashbook-loading d-none" aria-live="polite">
          <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading</span></div>
        </div>

        <!-- Panels -->
        <div data-cb-panel="transactions" class="mb-3">
          <div class="d-none d-md-block">
            <div class="table-responsive rounded-3 shadow-sm border bg-white">
              <table class="table table-hover align-middle mb-0 cashbook-table" id="cbEntryTable">
                <thead class="table-light">
                  <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Account</th>
                    <th scope="col">Type</th>
                    <th class="text-end" scope="col">Amount</th>
                    <th class="text-end" scope="col">Balance</th>
                    <th class="text-end" scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody id="cbEntryTableBody"></tbody>
              </table>
            </div>
          </div>
          <div id="cbEntryList" class="d-md-none mt-2"></div>
        </div>

        <div data-cb-panel="summary" class="cashbook-panel-hidden mb-3">
          <h2 class="h6 fw-bold mb-2">Account summary</h2>
          <p class="small text-muted mb-2" id="cbSummaryHint">Totals for the selected period (reference date).</p>
          <div id="cbAccountSummary"></div>
        </div>

        <div data-cb-panel="accounts" class="cashbook-panel-hidden mb-3 cb-mgmt-page">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
              <h2 class="h5 fw-bold mb-1">Manage accounts</h2>
              <p class="small text-muted mb-0">All ledgers in one place. New customers get a <strong>Customer</strong> account automatically; use <strong>Link customers</strong> for older records.</p>
            </div>
          </div>

          <div class="row g-3 mb-3 cb-mgmt-dashboard-strip">
            <div class="col-sm-6 col-xl-4">
              <div class="card border-0 shadow-sm rounded-4 h-100 cb-mgmt-dash-card cb-mgmt-dash-balance">
                <div class="card-body py-3">
                  <div class="small text-uppercase text-muted fw-semibold letter-spacing-sm">Total balance</div>
                  <div class="fs-4 fw-bold font-monospace text-body cb-mgmt-dash-value" id="cbMgmtTotalBalance">0.00</div>
                  <div class="small text-muted mt-1 mb-0" id="cbMgmtTotalHint">All ledgers combined</div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-xl-4">
              <div class="card border-0 shadow-sm rounded-4 h-100 cb-mgmt-dash-card cb-mgmt-dash-income">
                <div class="card-body py-3">
                  <div class="small text-uppercase fw-semibold letter-spacing-sm text-success-emphasis">Income (month)</div>
                  <div class="fs-4 fw-bold font-monospace text-success cb-mgmt-dash-value" id="cbMgmtDashIncome">0.00</div>
                  <div class="small text-muted mt-1 mb-0" id="cbMgmtDashIncomeHint">From reference month</div>
                </div>
              </div>
            </div>
            <div class="col-sm-12 col-xl-4">
              <div class="card border-0 shadow-sm rounded-4 h-100 cb-mgmt-dash-card cb-mgmt-dash-expense">
                <div class="card-body py-3">
                  <div class="small text-uppercase fw-semibold letter-spacing-sm text-danger-emphasis">Expense (month)</div>
                  <div class="fs-4 fw-bold font-monospace text-danger cb-mgmt-dash-value" id="cbMgmtDashExpense">0.00</div>
                  <div class="small text-muted mt-1 mb-0" id="cbMgmtDashExpenseHint">From reference month</div>
                </div>
              </div>
            </div>
          </div>

          <div class="d-lg-none overflow-auto pb-2 mb-3 cb-mgmt-mobile-actions cashbook-mgmt-mobile-bar" id="cbMgmtMobileQuickBar">
            <div class="d-flex flex-nowrap gap-2" style="min-width: min-content;">
              <button type="button" class="btn btn-success btn-sm rounded-pill text-nowrap cb-mgmt-mob-income"><i class="bi bi-plus-circle me-1"></i>Income</button>
              <button type="button" class="btn btn-danger btn-sm rounded-pill text-nowrap cb-mgmt-mob-expense"><i class="bi bi-dash-circle me-1"></i>Expense</button>
              <button type="button" class="btn btn-warning btn-sm rounded-pill text-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#cashbookTransferModal"><i class="bi bi-shuffle me-1"></i>Transfer</button>
              <button type="button" class="btn btn-primary btn-sm rounded-pill text-nowrap" data-bs-toggle="modal" data-bs-target="#cashbookCustomerModal"><i class="bi bi-person-plus me-1"></i>Customer</button>
            </div>
          </div>

          <div class="d-lg-none btn-group w-100 shadow-sm mb-3" role="group" aria-label="Accounts or form">
            <button type="button" class="btn btn-outline-primary rounded-start-pill active" id="cbMgmtMobileListBtn"><i class="bi bi-list-ul me-1"></i>List</button>
            <button type="button" class="btn btn-outline-primary rounded-end-pill" id="cbMgmtMobileFormBtn"><i class="bi bi-pencil-square me-1"></i>Form</button>
          </div>

          <div id="cbMgmtSplit" class="row g-3 align-items-stretch cb-mgmt-split cb-mgmt-mobile-list">
            <div class="col-12 col-lg-7 cb-mgmt-col-list">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 border-bottom-0 pt-3 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <span class="fw-semibold"><i class="bi bi-wallet2 me-1 text-primary"></i>Accounts</span>
                  <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill" id="cbMgmtNewBtn">
                      <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New account</span><span class="d-sm-none">New</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="cbLinkCustomersBtn" title="Create Cash Book accounts for customers that do not have one yet">
                      <i class="bi bi-people me-1"></i><span class="d-none d-sm-inline">Link customers</span>
                    </button>
                  </div>
                </div>
                <div class="card-body pt-3 position-relative">
                  <div id="cbMgmtListLoading" class="cb-mgmt-list-loading d-none" aria-hidden="true">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading</span></div>
                  </div>
                  <div class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-4 col-lg-3">
                      <label class="form-label small mb-1" for="cbMgmtFilterQ">Search</label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text rounded-start-pill bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="search" id="cbMgmtFilterQ" class="form-control rounded-end-pill border-start-0" placeholder="Filter by name…" autocomplete="off">
                      </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                      <label class="form-label small mb-1" for="cbMgmtFilterType">Type</label>
                      <select id="cbMgmtFilterType" class="form-select form-select-sm rounded-3" data-enhance="false">
                        <option value="">All types</option>
                        <option value="main">Main (Cash + Digital)</option>
                        <option value="customer">Customer</option>
                        <option value="bank">Bank</option>
                        <option value="supplier">Supplier</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                      <label class="form-label small mb-1" for="cbMgmtFilterStatus">Status</label>
                      <select id="cbMgmtFilterStatus" class="form-select form-select-sm rounded-3" data-enhance="false">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                      </select>
                    </div>
                    <div class="col-12 col-md-12 col-lg-3">
                      <label class="form-label small mb-1" for="cbMgmtSort">Sort</label>
                      <select id="cbMgmtSort" class="form-select form-select-sm rounded-3" data-enhance="false">
                        <option value="default">Default order</option>
                        <option value="name_asc">Name A–Z</option>
                        <option value="name_desc">Name Z–A</option>
                        <option value="balance_desc">Balance high → low</option>
                        <option value="balance_asc">Balance low → high</option>
                      </select>
                    </div>
                  </div>
                  <div class="cb-mgmt-table-wrap d-none d-md-block table-responsive rounded-4 border bg-white">
                    <table class="table table-sm table-striped table-hover align-middle mb-0 cashbook-mgmt-table">
                      <thead class="table-light">
                        <tr>
                          <th scope="col" class="col-num">#</th>
                          <th scope="col">Account Name</th>
                          <th scope="col">Customer</th>
                          <th scope="col">Type</th>
                          <th scope="col">Kind</th>
                          <th class="text-end" scope="col">Balance</th>
                          <th scope="col">Status</th>
                          <th class="text-end" scope="col">Actions</th>
                        </tr>
                      </thead>
                      <tbody id="cbMgmtTableBody"></tbody>
                    </table>
                  </div>
                  <div id="cbMgmtCards" class="d-md-none cb-mgmt-cards gap-2"></div>
                  <div id="cbMgmtEmpty" class="text-center text-muted py-5 d-none rounded-4 border bg-body-tertiary">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                    <div class="fw-semibold">No accounts found</div>
                    <div class="small">Try another search or add a new account.</div>
                  </div>
                  <nav id="cbMgmtPager" class="mt-3 d-none" aria-label="Accounts pagination">
                    <ul class="pagination pagination-sm justify-content-center mb-0 flex-wrap" id="cbMgmtPagerUl"></ul>
                  </nav>
                </div>
              </div>
            </div>
            <div class="col-12 col-lg-5 cb-mgmt-col-form">
              <div id="cbMgmtFormPlaceholder">
                <div id="cbMgmtFormCard" class="card border-0 shadow-sm rounded-4 h-100 cb-mgmt-form-card">
                  <div class="card-header bg-white border-0 border-bottom-0 pt-3 pb-0">
                    <span class="fw-semibold"><i class="bi bi-sliders me-1 text-primary"></i>Account details</span>
                  </div>
                  <div class="card-body pt-3">
                    <div id="cbMgmtFormError" class="alert alert-danger py-2 px-3 small mb-3 d-none" role="alert"></div>
                    <p class="small text-muted mb-3 d-none" id="cbMgmtCustomerNote"><i class="bi bi-link-45deg me-1"></i>This account is linked to a customer — type and customer cannot be changed.</p>
                    <p class="small text-warning-emphasis mb-3 d-none" id="cbMgmtSystemNote"><i class="bi bi-shield-lock me-1"></i>System account — only name, branch, and status can be changed.</p>
                    <input type="hidden" id="cbMgmtId" value="">
                    <div class="mb-3">
                      <label class="form-label small fw-semibold" for="cbMgmtName">Account name</label>
                      <input type="text" class="form-control rounded-3" id="cbMgmtName" placeholder="e.g. Main cash" autocomplete="off">
                      <div class="invalid-feedback" id="cbMgmtNameErr">Name is required.</div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold" for="cbMgmtCategory">Account type</label>
                      <select class="form-select rounded-3" id="cbMgmtCategory" data-enhance="false">
                        <option value="main">Main</option>
                        <option value="customer">Customer</option>
                        <option value="supplier">Supplier</option>
                      </select>
                    </div>
                    <div class="mb-3" id="cbMgmtMainSubtypeWrap">
                      <label class="form-label small fw-semibold" for="cbMgmtMainSubtype">Kind</label>
                      <select class="form-select rounded-3" id="cbMgmtMainSubtype" data-enhance="false">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="branch">Digital</option>
                      </select>
                    </div>
                    <div class="mb-3 d-none" id="cbMgmtCustomerWrap">
                      <label class="form-label small fw-semibold" for="cbMgmtCustomerId">Customer</label>
                      <select class="form-select rounded-3" id="cbMgmtCustomerId" data-enhance="false">
                        <option value="">— Select customer —</option>
                        <?php foreach ($cashbookCustomers as $c): ?>
                          <option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars((string) $c['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="invalid-feedback" id="cbMgmtCustomerErr">Select a customer.</div>
                    </div>
                    <div class="mb-3" id="cbMgmtOpeningWrap">
                      <label class="form-label small fw-semibold" for="cbMgmtOpening">Opening balance</label>
                      <input type="text" class="form-control rounded-3" id="cbMgmtOpening" placeholder="0.00" inputmode="decimal" autocomplete="off">
                      <div class="form-text">Starting balance for this account. Customer-linked and system accounts cannot change opening balance here.</div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold" for="cbMgmtBalance">Balance</label>
                      <input type="text" class="form-control rounded-3 bg-body-secondary" id="cbMgmtBalance" value="0.00" readonly>
                      <div class="form-text">Ledger balance (read-only). Use income, expense, or transfers to change it.</div>
                    </div>
                    <div class="mb-3">
                      <label class="form-label small fw-semibold" for="cbMgmtBranch">Branch ID <span class="text-muted fw-normal">(optional)</span></label>
                      <input type="number" class="form-control rounded-3" id="cbMgmtBranch" placeholder="Optional" min="0" step="1">
                    </div>
                    <div class="form-check form-switch mb-4">
                      <input class="form-check-input" type="checkbox" role="switch" id="cbMgmtStatus" checked>
                      <label class="form-check-label" for="cbMgmtStatus">Active</label>
                    </div>
                    <div class="d-grid d-sm-flex flex-wrap gap-2">
                      <button type="button" class="btn btn-primary rounded-pill flex-sm-grow-0" id="cbMgmtSaveBtn">
                        <span class="cb-mgmt-save-label"><i class="bi bi-check2 me-1"></i>Save</span>
                        <span class="spinner-border spinner-border-sm d-none cb-mgmt-save-spin" role="status" aria-hidden="true"></span>
                      </button>
                      <button type="button" class="btn btn-outline-secondary rounded-pill flex-sm-grow-0" id="cbMgmtResetBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                      </button>
                      <button type="button" class="btn btn-outline-danger rounded-pill flex-sm-grow-0 ms-sm-auto" id="cbMgmtDeleteBtn" disabled>
                        <i class="bi bi-trash me-1"></i>Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button type="button" class="btn btn-primary rounded-circle shadow cb-mgmt-fab" id="cbMgmtFab" title="New account" aria-label="New account">
            <i class="bi bi-plus-lg fs-5"></i>
          </button>
        </div>

        <div data-cb-panel="reports" class="cashbook-panel-hidden mb-3">
          <h2 class="h6 fw-bold mb-2">Reports</h2>
          <div class="row g-2 align-items-end mb-2">
            <div class="col-sm-4">
              <div class="form-floating">
                <input type="date" id="cbReportFrom" class="form-control">
                <label for="cbReportFrom">From</label>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-floating">
                <input type="date" id="cbReportTo" class="form-control">
                <label for="cbReportTo">To</label>
              </div>
            </div>
            <div class="col-sm-4">
              <button type="button" id="cbReportLoad" class="btn btn-primary rounded-pill w-100">Load</button>
            </div>
          </div>
          <div id="cbReportTable" class="table-responsive rounded-3 border bg-white shadow-sm"></div>
          <p class="small text-muted mt-2 mb-0"><a href="<?php echo htmlspecialchars($reportsLegacyUrl); ?>">Open full Reports</a></p>
        </div>

        <div data-cb-panel="audit" class="cashbook-panel-hidden mb-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
              <h2 class="h6 fw-bold mb-1">Audit trail</h2>
              <p class="small text-muted mb-0">Latest account and transaction activity.</p>
            </div>
            <div class="d-flex gap-2">
              <input type="search" id="cbAuditSearch" class="form-control form-control-sm rounded-pill" placeholder="Search audit…" autocomplete="off">
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="cbAuditLoad"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
            </div>
          </div>
          <div id="cbAuditTable" class="table-responsive rounded-3 border bg-white shadow-sm cashbook-audit-table"></div>
        </div>

        <p class="small text-muted d-none d-md-block mb-0 cashbook-hide-for-accounts-mgmt" id="cbRangeHint">Income and expense reflect the selected period. Balance is the current account balance.</p>
      </div>
    </div>
  </div>

  <!-- Sticky summary (mobile) -->
  <div class="cashbook-summary-strip d-md-none cashbook-hide-for-accounts-mgmt">
    <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
      <span id="cbRangeLabel"></span>
    </div>
    <div class="row g-2 text-center">
      <div class="col-4">
        <div class="small text-muted">Income</div>
        <div class="fw-bold text-success" id="cbSummaryIncome">0.00</div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Expense</div>
        <div class="fw-bold text-danger" id="cbSummaryExpense">0.00</div>
      </div>
      <div class="col-4">
        <div class="small text-muted">Balance</div>
        <div class="fw-bold text-primary" id="cbSummaryBalance">0.00</div>
      </div>
    </div>
  </div>

  <div class="cashbook-bottom-actions d-lg-none">
    <button type="button" class="btn btn-success rounded-pill" id="cbBtnIncome"><i class="bi bi-plus-circle me-1"></i><span class="cb-mobile-action-label">Income</span></button>
    <button type="button" class="btn btn-danger rounded-pill" id="cbBtnExpense"><i class="bi bi-dash-circle me-1"></i><span class="cb-mobile-action-label">Expense</span></button>
    <button type="button" class="btn btn-warning rounded-pill text-dark" data-bs-toggle="modal" data-bs-target="#cashbookTransferModal"><i class="bi bi-shuffle me-1"></i><span class="cb-mobile-action-label">Transfer</span></button>
    <button type="button" class="btn btn-outline-secondary rounded-pill" id="cbBtnExportExcelMobile"><i class="bi bi-file-earmark-excel me-1"></i><span class="cb-mobile-action-label">Excel</span></button>
    <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#cashbookCustomerModal"><i class="bi bi-person-plus me-1"></i><span class="cb-mobile-action-label">Customer</span></button>
  </div>

  <!-- Mobile offcanvas -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="cashbookMenu" aria-labelledby="cashbookMenuLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="cashbookMenuLabel">Cash Book</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
      <nav class="nav flex-column cashbook-offcanvas py-2 px-2 gap-1">
        <a class="nav-link rounded-pill px-3 py-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2 active" data-panel="transactions"><i class="bi bi-arrow-left-right me-2"></i>Transactions</button>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2" data-panel="accounts"><i class="bi bi-bank me-2"></i>Accounts</button>
        <button type="button" class="nav-link text-start rounded-pill px-3 py-2" data-bs-toggle="modal" data-bs-target="#cashbookTransferModal"><i class="bi bi-shuffle me-2"></i>Transfer</button>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2" data-panel="reports"><i class="bi bi-graph-up-arrow me-2"></i>Reports</button>
        <button type="button" class="nav-link text-start cb-nav-panel rounded-pill px-3 py-2" data-panel="audit"><i class="bi bi-shield-check me-2"></i>Audit Trail</button>
      </nav>
    </div>
  </div>

  <div id="cashbookToastHost" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080"></div>

  <!-- Add customer -->
  <div class="modal fade" id="cashbookCustomerModal" tabindex="-1" aria-labelledby="cashbookCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title" id="cashbookCustomerModalLabel">Add customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="cbCustomerForm" method="post" action="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=customers&action=save')); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="id" value="0">
            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="name" id="cbCustName" placeholder="Name" required autocomplete="organization">
              <label for="cbCustName">Customer name</label>
            </div>
            <div class="form-floating mb-3">
              <input type="text" class="form-control" name="phone" id="cbCustPhone" placeholder="Phone" autocomplete="tel">
              <label for="cbCustPhone">Phone</label>
            </div>
            <div class="form-floating mb-3">
              <input type="email" class="form-control" name="email" id="cbCustEmail" placeholder="Email" autocomplete="email">
              <label for="cbCustEmail">Email</label>
            </div>
            <p class="small text-muted mb-0">A matching Cash Book account (type Customer, balance 0) is created automatically.</p>
          </form>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary rounded-pill" id="cbCustomerSave"><i class="bi bi-check2 me-1"></i>Save customer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Transaction modal -->
  <div class="modal fade" id="cashbookTxnModal" tabindex="-1" aria-labelledby="cashbookTxnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title" id="cashbookTxnModalLabel">Income / Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-2">
          <input type="hidden" id="cbTxnId" value="">
          <input type="hidden" id="cbTxnAccountId" value="">
          <p class="small text-muted mb-2 d-none" id="cbTxnAccountHint"><i class="bi bi-wallet2 me-1"></i><span id="cbTxnAccountHintText"></span></p>
          <div class="btn-group w-100 mb-3" role="group">
            <input type="radio" class="btn-check" name="cbTxnType" id="cbTxnIncome" value="income" autocomplete="off" checked>
            <label class="btn btn-outline-success rounded-start-pill" for="cbTxnIncome">Income</label>
            <input type="radio" class="btn-check" name="cbTxnType" id="cbTxnExpense" value="expense" autocomplete="off">
            <label class="btn btn-outline-danger rounded-end-pill" for="cbTxnExpense">Expense</label>
          </div>
          <div class="form-floating mb-3">
            <input type="text" inputmode="decimal" class="form-control form-control-lg" id="cbTxnAmount" placeholder="0.00" autocomplete="off">
            <label for="cbTxnAmount">Amount</label>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-7">
              <div class="form-floating">
                <input type="date" class="form-control" id="cbTxnDate">
                <label for="cbTxnDate">Date</label>
              </div>
            </div>
            <div class="col-5">
              <div class="form-floating">
                <input type="time" class="form-control" id="cbTxnTime" step="60">
                <label for="cbTxnTime">Time</label>
              </div>
            </div>
          </div>
          <div class="form-floating mb-3">
            <textarea class="form-control" id="cbTxnNotes" style="height: 5rem" placeholder="Notes"></textarea>
            <label for="cbTxnNotes">Notes</label>
          </div>
          <div class="form-floating mb-3">
            <input type="text" class="form-control" id="cbTxnRefNo" placeholder="Reference">
            <label for="cbTxnRefNo">Reference No (optional)</label>
          </div>
          <div class="form-floating mb-3">
            <input type="text" class="form-control" id="cbTxnParcel" placeholder="Parcel" inputmode="numeric">
            <label for="cbTxnParcel">Parcel ID (optional)</label>
          </div>
          <div class="form-floating mb-3">
            <textarea class="form-control font-monospace" id="cbTxnItems" style="height: 4rem" placeholder="[]"></textarea>
            <label for="cbTxnItems">Line items JSON (optional)</label>
          </div>
          <div class="mb-2">
            <label class="form-label small" for="cbTxnFile">Attachment (max 4MB)</label>
            <input type="file" class="form-control form-control-sm rounded-pill" id="cbTxnFile" accept="image/*,application/pdf">
          </div>
        </div>
        <div class="modal-footer flex-wrap gap-2 border-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary rounded-pill" id="cbSaveTxnCont">Save &amp; continue</button>
          <button type="button" class="btn btn-success rounded-pill" id="cbSaveTxn">Save &amp; exit</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Transfer -->
  <div class="modal fade" id="cashbookTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title">Transfer between accounts</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="form-floating mb-3">
            <select class="form-select" id="cbTrFrom" data-enhance="false" aria-label="From account"></select>
            <label for="cbTrFrom">From account</label>
          </div>
          <div class="form-floating mb-3">
            <select class="form-select" id="cbTrTo" data-enhance="false" aria-label="To account"></select>
            <label for="cbTrTo">To account</label>
          </div>
          <div class="form-floating mb-3">
            <input type="text" inputmode="decimal" class="form-control form-control-lg" id="cbTrAmount" placeholder="0.00">
            <label for="cbTrAmount">Amount</label>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-7">
              <div class="form-floating">
                <input type="date" class="form-control" id="cbTrDate">
                <label for="cbTrDate">Date</label>
              </div>
            </div>
            <div class="col-5">
              <div class="form-floating">
                <input type="time" class="form-control" id="cbTrTime" step="60">
                <label for="cbTrTime">Time</label>
              </div>
            </div>
          </div>
          <div class="form-floating mb-3">
            <textarea class="form-control" id="cbTrNotes" style="height: 4.5rem" placeholder="Note"></textarea>
            <label for="cbTrNotes">Description</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="cbTrPreventNeg" checked>
            <label class="form-check-label" for="cbTrPreventNeg">Require sufficient balance in source account</label>
          </div>
          <p class="small text-muted mb-0">Uncheck only if you allow the source account to go negative. Transfers work between any accounts (including customer ↔ customer ↔ main cash/bank).</p>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-primary rounded-pill w-100" id="cbTransferSave"><i class="bi bi-check2 me-1"></i>Save transfer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Account form (mobile modal — card moved here via JS) -->
  <div class="modal fade" id="cbMgmtFormModal" tabindex="-1" data-bs-backdrop="true" aria-hidden="true" aria-labelledby="cbMgmtFormModalLabel">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-semibold" id="cbMgmtFormModalLabel">Account details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0 px-3 pb-3" id="cbMgmtFormModalBody"></div>
      </div>
    </div>
  </div>

  <!-- Entry details -->
  <div class="modal fade" id="cbEntryDetailsModal" tabindex="-1" aria-hidden="true" aria-labelledby="cbEntryDetailsModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title" id="cbEntryDetailsModalLabel">Transaction details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-2">
          <div id="cbEntryDetailsBody" class="small text-muted">Loading…</div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.TMS_CASHBOOK = <?php echo json_encode([
    'csrf' => $csrf,
    'url' => Helpers::baseUrl('index.php?page=cashbook'),
    'baseUrl' => $base,
    'customersSaveUrl' => Helpers::baseUrl('index.php?page=customers&action=save'),
    'customers' => array_map(static function ($c) {
        return ['id' => (int) $c['id'], 'name' => (string) $c['name']];
    }, $cashbookCustomers),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/cashbook.js?v=' . rawurlencode($cbJsVer)); ?>"></script>
