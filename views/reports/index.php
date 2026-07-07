<?php
/** @var array $branchesAll */
$repCssPath = dirname(__DIR__, 2) . '/public/assets/css/reports-module.css';
$repJsPath = dirname(__DIR__, 2) . '/public/assets/js/reports-module.js';
$repCssVer = is_file($repCssPath) ? (string) filemtime($repCssPath) : '1';
$repJsVer = is_file($repJsPath) ? (string) filemtime($repJsPath) : '1';
$base = Helpers::baseUrl('');
$apiBase = $base . 'index.php?page=reports';

$accLink = static function (string $action, array $params = []): string {
    $q = array_merge(['page' => 'accounting', 'action' => $action], $params);
    return Helpers::baseUrl('index.php?' . http_build_query($q));
};

$quickReports = [
    ['cash_book', 'Cash Book', 'Cash receipts & payments', 'bi-wallet2', $accLink('cash_book')],
    ['bank_book', 'Bank Book', 'Bank transactions & balances', 'bi-bank', $accLink('bank_book')],
    ['ledger', 'General Ledger', 'Account-wise ledger entries', 'bi-journal-richtext', $accLink('ledger')],
    ['trial_balance', 'Trial Balance', 'Debit/credit trial balance', 'bi-balance-scale', $accLink('trial_balance')],
    ['profit_loss', 'Profit & Loss', 'Income statement for period', 'bi-graph-up-arrow', $accLink('profit_loss')],
    ['balance_sheet', 'Balance Sheet', 'Assets, liabilities & equity', 'bi-clipboard-data', $accLink('balance_sheet')],
    ['daybook', 'Day Book', 'Daily voucher register', 'bi-calendar-day', $accLink('daybook')],
    ['payment', 'Payment Report', 'Payment vouchers', 'bi-cash-coin', $accLink('entry', ['voucher_type' => 'PAYMENT', 'payment_mode' => 'CASH'])],
    ['receipt', 'Receipt Report', 'Receipt vouchers', 'bi-cash-stack', $accLink('entry', ['voucher_type' => 'RECEIPT', 'payment_mode' => 'CASH'])],
    ['transfer', 'Transfer Report', 'Inter-account transfers', 'bi-shuffle', $accLink('entry', ['voucher_type' => 'TRANSFER'])],
    ['customer', 'Customer Report', 'Customer ledger & activity', 'bi-people', $accLink('customer_ledger')],
    ['supplier', 'Supplier Report', 'Supplier ledger & parcels', 'bi-truck', $accLink('supplier_ledger')],
    ['parcels', 'Parcel Report', 'Operational parcel analytics', 'bi-box-seam', '#repTabParcels'],
    ['expenses', 'Expense Report', 'Approved expenses', 'bi-receipt', '#repTabExpenses'],
    ['revenue', 'Revenue Report', 'Branch revenue breakdown', 'bi-currency-dollar', '#repTabRevenue'],
    ['vouchers', 'Voucher Register', 'All accounting vouchers', 'bi-table', $accLink('vouchers')],
    ['audit', 'Audit Trail', 'Accounting audit log', 'bi-shield-check', $accLink('integrations')],
];
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/reports-module.css?v=' . rawurlencode($repCssVer)); ?>">

<div class="rep-page container-fluid px-0" id="reportsApp" data-api-base="<?php echo htmlspecialchars($apiBase); ?>">

  <!-- Loading overlay -->
  <div id="repLoadingOverlay" class="rep-loading-overlay d-none" aria-live="polite" aria-busy="true">
    <div class="rep-loading-panel">
      <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading</span></div>
      <div class="fw-semibold">Loading reports…</div>
      <div class="rep-skeleton mt-3">
        <div class="rep-skeleton-line"></div>
        <div class="rep-skeleton-line short"></div>
        <div class="rep-skeleton-line"></div>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast-container position-fixed top-0 end-0 p-3 rep-toast-wrap" style="z-index: 1080;">
    <div id="repToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i><span id="repToastMsg">Report generated successfully.</span></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="rep-hero">
    <div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center justify-content-between gap-3">
      <div class="d-flex align-items-start gap-3 min-w-0">
        <div class="rep-hero-icon" aria-hidden="true"><i class="bi bi-pie-chart-fill"></i></div>
        <div class="min-w-0">
          <h1 class="rep-hero-title">Reports &amp; Analytics</h1>
          <p class="rep-hero-subtitle mb-0">View, filter, export and analyze business reports.</p>
          <span class="rep-updated small text-muted" id="repUpdated">Loading…</span>
        </div>
      </div>
      <div class="rep-hero-actions d-flex flex-wrap gap-2 justify-content-xl-end">
        <div class="btn-group">
          <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-download me-1" aria-hidden="true"></i> Export All
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item rep-export" href="#" data-type="summary"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Summary (CSV)</a></li>
            <li><a class="dropdown-item rep-export" href="#" data-type="revenue"><i class="bi bi-graph-up me-2"></i>Revenue (CSV)</a></li>
            <li><a class="dropdown-item rep-export" href="#" data-type="expenses"><i class="bi bi-receipt me-2"></i>Expenses (CSV)</a></li>
            <li><a class="dropdown-item rep-export" href="#" data-type="suppliers"><i class="bi bi-truck me-2"></i>Suppliers (CSV)</a></li>
            <li><a class="dropdown-item rep-export" href="#" data-type="parcels"><i class="bi bi-box-seam me-2"></i>Parcels (CSV)</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="repOpenExportModal"><i class="bi bi-box-arrow-up-right me-2"></i>More formats…</a></li>
          </ul>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="repBtnRefresh" aria-label="Refresh reports">
          <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Refresh
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="repBtnPrint" aria-label="Print reports">
          <i class="bi bi-printer me-1" aria-hidden="true"></i> Print
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="repBtnFullscreen" aria-label="Toggle fullscreen">
          <i class="bi bi-arrows-fullscreen me-1" aria-hidden="true"></i> Fullscreen
        </button>
      </div>
    </div>
  </header>

  <!-- Filters -->
  <section class="rep-card rep-filters-card rep-filters-sticky mb-3" aria-label="Report filters">
    <div class="rep-filters-head d-flex align-items-center justify-content-between gap-2">
      <button class="btn btn-link rep-filters-toggle text-decoration-none p-0 fw-semibold" type="button"
        data-bs-toggle="collapse" data-bs-target="#repFiltersBody" aria-expanded="true" aria-controls="repFiltersBody">
        <i class="bi bi-funnel me-2" aria-hidden="true"></i>Report Filters
        <i class="bi bi-chevron-down ms-1 rep-filters-chevron" aria-hidden="true"></i>
      </button>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="repBtnResetFilters">
          <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset
        </button>
        <button type="submit" form="repFilterForm" class="btn btn-sm btn-primary" id="repBtnApplyFilters">
          <i class="bi bi-check2 me-1" aria-hidden="true"></i> Apply
        </button>
      </div>
    </div>
    <div class="collapse show" id="repFiltersBody">
      <form id="repFilterForm" class="rep-filters-form">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="repFrom"><i class="bi bi-calendar-event me-1 text-muted"></i>Start Date</label>
            <input type="date" class="form-control" id="repFrom" name="from" value="<?php echo date('Y-m-01'); ?>">
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="repTo"><i class="bi bi-calendar-check me-1 text-muted"></i>End Date</label>
            <input type="date" class="form-control" id="repTo" name="to" value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="repBranch"><i class="bi bi-building me-1 text-muted"></i>Branch</label>
            <select class="form-select" id="repBranch" name="branch_id" data-enhance="false">
              <option value="0">All Branches</option>
              <?php foreach ($branchesAll as $b): ?>
              <option value="<?php echo (int) $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="repSupplier"><i class="bi bi-truck me-1 text-muted"></i>Supplier</label>
            <select class="form-select" id="repSupplier" name="supplier_id" data-enhance="false">
              <option value="0">All Suppliers</option>
            </select>
          </div>
          <div class="col-12 col-lg-6">
            <label class="form-label" for="repGlobalSearch"><i class="bi bi-search me-1 text-muted"></i>Search reports</label>
            <input type="search" class="form-control" id="repGlobalSearch" placeholder="Search active report table…" autocomplete="off">
          </div>
        </div>
        <div class="rep-advanced-filters mt-3 pt-3 border-top">
          <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Advanced filters (visual reference — use module reports for detailed filtering)</p>
          <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
              <label class="form-label small">Voucher Type</label>
              <select class="form-select form-select-sm" disabled aria-disabled="true" title="Open accounting module for voucher filters">
                <option>All types</option>
              </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
              <label class="form-label small">Customer</label>
              <select class="form-select form-select-sm" disabled aria-disabled="true"><option>All customers</option></select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
              <label class="form-label small">Employee</label>
              <select class="form-select form-select-sm" disabled aria-disabled="true"><option>All employees</option></select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
              <label class="form-label small">Payment Method</label>
              <select class="form-select form-select-sm" disabled aria-disabled="true"><option>All methods</option></select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
              <label class="form-label small">Status</label>
              <select class="form-select form-select-sm" disabled aria-disabled="true"><option>All statuses</option></select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
              <label class="form-label small">Reference Number</label>
              <input type="text" class="form-control form-control-sm" disabled placeholder="Ref #" aria-disabled="true">
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>

  <div id="repAlert" class="alert alert-danger rep-alert d-none" role="alert">
    <div class="d-flex align-items-start gap-2">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
      <div class="flex-grow-1">
        <div id="repAlertMsg"></div>
        <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="repBtnRetry"><i class="bi bi-arrow-clockwise me-1"></i> Retry</button>
      </div>
    </div>
  </div>

  <!-- KPI Summary -->
  <section class="rep-kpi-grid mb-4" id="repKpis" aria-label="Summary KPIs">
    <article class="rep-kpi-card rep-kpi-card--blue" data-accent="blue">
      <div class="rep-kpi-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Total Reports</div>
        <div class="rep-kpi-value" data-kpi="total_parcels">—</div>
        <div class="rep-kpi-trend" data-trend="total_parcels">Parcel records in period</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--cyan" data-accent="cyan">
      <div class="rep-kpi-icon"><i class="bi bi-calendar-day" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Today's Reports</div>
        <div class="rep-kpi-value" data-kpi="today_reports">—</div>
        <div class="rep-kpi-trend">When range is today</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--indigo" data-accent="indigo">
      <div class="rep-kpi-icon"><i class="bi bi-calendar-month" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Monthly Reports</div>
        <div class="rep-kpi-value" data-kpi="monthly_reports">—</div>
        <div class="rep-kpi-trend">Current month range</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--amber" data-accent="amber">
      <div class="rep-kpi-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Pending Reports</div>
        <div class="rep-kpi-value" data-kpi="pending_parcels">—</div>
        <div class="rep-kpi-trend negative">Awaiting completion</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--green" data-accent="green">
      <div class="rep-kpi-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Completed Reports</div>
        <div class="rep-kpi-value" data-kpi="delivered_parcels">—</div>
        <div class="rep-kpi-trend positive">Delivered</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--red" data-accent="red">
      <div class="rep-kpi-icon"><i class="bi bi-x-circle" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Cancelled Reports</div>
        <div class="rep-kpi-value" data-kpi="cancelled_parcels">—</div>
        <div class="rep-kpi-trend negative">Cancelled / failed</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--emerald" data-accent="emerald">
      <div class="rep-kpi-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Total Income</div>
        <div class="rep-kpi-value" data-kpi="total_revenue">—</div>
        <div class="rep-kpi-trend positive">Revenue</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--rose" data-accent="rose">
      <div class="rep-kpi-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></div>
      <div class="rep-kpi-body">
        <div class="rep-kpi-label">Total Expenses</div>
        <div class="rep-kpi-value negative" data-kpi="total_expenses">—</div>
        <div class="rep-kpi-trend">Approved + GL</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--violet d-none" data-accent="violet" aria-hidden="true">
      <div class="rep-kpi-body">
        <div class="rep-kpi-value" data-kpi="net_profit">—</div>
      </div>
    </article>
    <article class="rep-kpi-card rep-kpi-card--slate d-none" aria-hidden="true">
      <div class="rep-kpi-value" data-kpi="active_suppliers">—</div>
      <div class="rep-kpi-value" data-kpi="active_customers">—</div>
    </article>
  </section>

  <!-- Quick Reports -->
  <section class="mb-4" aria-labelledby="repQuickTitle">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="rep-section-title mb-0" id="repQuickTitle"><i class="bi bi-lightning-charge me-2" aria-hidden="true"></i>Quick Reports</h2>
    </div>
    <div class="rep-quick-grid">
      <?php foreach ($quickReports as [$key, $title, $desc, $icon, $href]): ?>
      <a class="rep-quick-card" href="<?php echo htmlspecialchars(str_starts_with($href, '#') ? $href : $href); ?>"
        <?php echo str_starts_with($href, '#') ? ' data-rep-tab-target="' . htmlspecialchars(substr($href, 1)) . '"' : ' target="_self"'; ?>>
        <div class="rep-quick-icon" aria-hidden="true"><i class="bi <?php echo $icon; ?>"></i></div>
        <div class="rep-quick-body">
          <h3 class="rep-quick-title"><?php echo htmlspecialchars($title); ?></h3>
          <p class="rep-quick-desc"><?php echo htmlspecialchars($desc); ?></p>
          <span class="rep-quick-open">Open <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Charts -->
  <div class="row g-3 mb-4 rep-charts-row">
    <div class="col-lg-6">
      <div class="rep-card h-100">
        <div class="rep-card-header"><i class="bi bi-bar-chart me-2" aria-hidden="true"></i>Revenue by Branch</div>
        <div class="rep-card-body"><canvas id="repBranchChart" height="220" aria-label="Revenue by branch chart"></canvas></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="rep-card h-100">
        <div class="rep-card-header"><i class="bi bi-pie-chart me-2" aria-hidden="true"></i>Expenses by Category</div>
        <div class="rep-card-body"><canvas id="repExpenseChart" height="220" aria-label="Expenses chart"></canvas></div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="rep-card h-100">
        <div class="rep-card-header"><i class="bi bi-graph-up me-2" aria-hidden="true"></i>Monthly Revenue (12 months)</div>
        <div class="rep-card-body"><canvas id="repMonthlyChart" height="160" aria-label="Monthly revenue chart"></canvas></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="rep-card h-100">
        <div class="rep-card-header"><i class="bi bi-trophy me-2" aria-hidden="true"></i>Top Suppliers</div>
        <div class="rep-card-body"><canvas id="repSupplierChart" height="220" aria-label="Top suppliers chart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Table toolbar -->
  <div class="rep-table-toolbar rep-card mb-2" id="repTableToolbar">
    <div class="d-flex flex-wrap align-items-center gap-2 p-2">
      <div class="input-group input-group-sm rep-toolbar-search flex-grow-1" style="max-width: 280px;">
        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
        <input type="search" class="form-control" id="repTableSearch" placeholder="Search table…" aria-label="Search report table">
      </div>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-secondary" id="repTbRefresh" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
        <button type="button" class="btn btn-outline-secondary" id="repTbPrint" title="Print"><i class="bi bi-printer"></i></button>
        <button type="button" class="btn btn-outline-secondary rep-export" data-type="summary" title="Excel/CSV"><i class="bi bi-file-earmark-excel"></i></button>
        <button type="button" class="btn btn-outline-secondary" id="repTbCopy" title="Copy"><i class="bi bi-clipboard"></i></button>
      </div>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-secondary rep-export" data-type="summary" title="CSV export"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button type="button" class="btn btn-outline-secondary" id="repTbPdf" title="Print as PDF"><i class="bi bi-filetype-pdf"></i> PDF</button>
      </div>
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-layout-three-columns me-1"></i> Density
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><button type="button" class="dropdown-item rep-density" data-density="comfortable">Comfortable</button></li>
          <li><button type="button" class="dropdown-item rep-density" data-density="compact">Compact</button></li>
        </ul>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="repTbFullscreen" title="Fullscreen table area">
        <i class="bi bi-arrows-fullscreen"></i>
      </button>
      <span class="small text-muted ms-auto" id="repRowCount">0 rows</span>
    </div>
  </div>

  <ul class="nav nav-tabs rep-tabs mb-0" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#repTabRevenue" type="button" role="tab" aria-controls="repTabRevenue" aria-selected="true">Revenue</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#repTabExpenses" type="button" role="tab">Expenses</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#repTabSuppliers" type="button" role="tab">Suppliers</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#repTabParcels" type="button" role="tab">Parcels</button>
    </li>
  </ul>

  <div class="tab-content rep-tab-content">
    <div class="tab-pane fade show active" id="repTabRevenue" role="tabpanel">
      <div class="rep-card rep-table-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive rep-table-wrap">
            <table class="table table-hover align-middle mb-0 rep-data-table datatable" id="repRevenueTable" data-dt-scroll-x="true">
              <thead><tr><th>Branch</th><th>Accounting</th><th>Freight</th><th class="text-end">Total</th><th class="text-end">%</th><th class="text-end">Txns</th><th class="text-end rep-col-actions">Actions</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="repTabExpenses" role="tabpanel">
      <div class="rep-card rep-table-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive rep-table-wrap">
            <table class="table table-hover align-middle mb-0 rep-data-table datatable" id="repExpenseTable">
              <thead><tr><th>Date</th><th>Number</th><th>Category</th><th>Branch</th><th>Supplier</th><th class="text-end">Amount</th><th>Status</th><th class="text-end rep-col-actions">Actions</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="repTabSuppliers" role="tabpanel">
      <div class="rep-card rep-table-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive rep-table-wrap">
            <table class="table table-hover align-middle mb-0 rep-data-table datatable" id="repSupplierTable">
              <thead><tr><th>Supplier</th><th class="text-end">Parcels</th><th class="text-end">Revenue</th><th class="text-end">Pending</th><th class="text-end">Delivered</th><th class="text-end">Cancelled</th><th class="text-end rep-col-actions">Actions</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="repTabParcels" role="tabpanel">
      <div class="rep-card rep-table-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive rep-table-wrap">
            <table class="table table-hover align-middle mb-0 rep-data-table datatable" id="repParcelTable" data-dt-scroll-x="true">
              <thead><tr><th>Date</th><th>Tracking</th><th>Customer</th><th>Supplier</th><th>From</th><th>To</th><th>Status</th><th class="text-end">Amount</th><th class="text-end rep-col-actions">Actions</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Empty state -->
  <div id="repEmptyState" class="rep-empty-state d-none" role="status">
    <div class="rep-empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></div>
    <h3 class="h5 fw-semibold">No reports found</h3>
    <p class="text-muted mb-3">Try adjusting your date range or filters to see report data.</p>
    <button type="button" class="btn btn-primary" id="repEmptyReset"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters</button>
  </div>

  <!-- Export modal -->
  <div class="modal fade" id="repExportModal" tabindex="-1" aria-labelledby="repExportModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rep-modal">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="repExportModalTitle"><i class="bi bi-download me-2"></i>Export Report</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Choose a format. CSV exports use your current filter settings.</p>
          <div class="list-group list-group-flush rep-export-list">
            <button type="button" class="list-group-item list-group-item-action rep-export" data-type="summary"><i class="bi bi-filetype-csv me-2 text-success"></i>Summary — CSV</button>
            <button type="button" class="list-group-item list-group-item-action rep-export" data-type="revenue"><i class="bi bi-graph-up me-2 text-primary"></i>Revenue — CSV</button>
            <button type="button" class="list-group-item list-group-item-action rep-export" data-type="expenses"><i class="bi bi-receipt me-2 text-danger"></i>Expenses — CSV</button>
            <button type="button" class="list-group-item list-group-item-action rep-export" data-type="suppliers"><i class="bi bi-truck me-2 text-secondary"></i>Suppliers — CSV</button>
            <button type="button" class="list-group-item list-group-item-action rep-export" data-type="parcels"><i class="bi bi-box-seam me-2 text-warning"></i>Parcels — CSV</button>
            <button type="button" class="list-group-item list-group-item-action" id="repExportPrint"><i class="bi bi-printer me-2"></i>Print</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="rep-print-footer d-none" aria-hidden="true">
    <div class="rep-print-meta">
      <strong><?php echo htmlspecialchars(Helpers::config()['company']['name'] ?? 'Transport Management System'); ?></strong>
      <span>Reports &amp; Analytics</span>
      <span id="repPrintDate"></span>
    </div>
  </footer>
</div>
