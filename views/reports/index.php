<?php
/** @var array $branchesAll */
$repCssPath = dirname(__DIR__, 2) . '/public/assets/css/reports-module.css';
$repJsPath = dirname(__DIR__, 2) . '/public/assets/js/reports-module.js';
$repCssVer = is_file($repCssPath) ? (string) filemtime($repCssPath) : '1';
$repJsVer = is_file($repJsPath) ? (string) filemtime($repJsPath) : '1';
$base = Helpers::baseUrl('');
$apiBase = $base . 'index.php?page=reports';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/reports-module.css?v=' . rawurlencode($repCssVer)); ?>">

<div class="rep-page container-fluid px-0 px-sm-1" id="reportsApp"
     data-api-base="<?php echo htmlspecialchars($apiBase); ?>">

  <header class="rep-page-head d-flex flex-column flex-lg-row align-items-stretch align-items-lg-start justify-content-between gap-3 mb-3">
    <div class="min-w-0">
      <h1 class="rep-title"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> Reports</h1>
      <p class="rep-subtitle text-muted mb-0">Revenue, parcels, expenses, and supplier analytics from live TMS &amp; accounting data.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="small text-muted" id="repUpdated">Loading…</span>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="repBtnPrint"><i class="bi bi-printer me-1"></i> Print</button>
      <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">Export</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item rep-export" href="#" data-type="summary">Summary (CSV)</a></li>
          <li><a class="dropdown-item rep-export" href="#" data-type="revenue">Revenue (CSV)</a></li>
          <li><a class="dropdown-item rep-export" href="#" data-type="expenses">Expenses (CSV)</a></li>
          <li><a class="dropdown-item rep-export" href="#" data-type="suppliers">Suppliers (CSV)</a></li>
          <li><a class="dropdown-item rep-export" href="#" data-type="parcels">Parcels (CSV)</a></li>
        </ul>
      </div>
      <button type="button" class="btn btn-sm btn-primary" id="repBtnRefresh"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
    </div>
  </header>

  <section class="rep-card rep-filters-card mb-3" aria-label="Report filters">
    <form id="repFilterForm" class="row g-3 align-items-end">
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="repFrom">Start Date</label>
        <input type="date" class="form-control" id="repFrom" name="from" value="<?php echo date('Y-m-01'); ?>">
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="repTo">End Date</label>
        <input type="date" class="form-control" id="repTo" name="to" value="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="repBranch">Branch</label>
        <select class="form-select" id="repBranch" name="branch_id" data-enhance="false">
          <option value="0">All Branches</option>
          <?php foreach ($branchesAll as $b): ?>
          <option value="<?php echo (int) $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="repSupplier">Supplier</label>
        <select class="form-select" id="repSupplier" name="supplier_id" data-enhance="false">
          <option value="0">All Suppliers</option>
        </select>
      </div>
      <div class="col-12 col-lg-auto">
        <button type="submit" class="btn btn-outline-primary d-none">Apply</button>
      </div>
    </form>
  </section>

  <div id="repAlert" class="alert alert-danger d-none" role="alert"></div>

  <section class="rep-kpi-grid mb-3" id="repKpis" aria-label="Summary cards">
    <div class="rep-kpi"><div class="rep-kpi-label">Total Revenue</div><div class="rep-kpi-value" data-kpi="total_revenue">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Total Expenses</div><div class="rep-kpi-value negative" data-kpi="total_expenses">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Net Profit</div><div class="rep-kpi-value" data-kpi="net_profit">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Total Parcels</div><div class="rep-kpi-value" data-kpi="total_parcels">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Delivered</div><div class="rep-kpi-value positive" data-kpi="delivered_parcels">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Pending</div><div class="rep-kpi-value" data-kpi="pending_parcels">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Cancelled</div><div class="rep-kpi-value negative" data-kpi="cancelled_parcels">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Active Suppliers</div><div class="rep-kpi-value" data-kpi="active_suppliers">—</div></div>
    <div class="rep-kpi"><div class="rep-kpi-label">Active Customers</div><div class="rep-kpi-value" data-kpi="active_customers">—</div></div>
  </section>

  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="rep-card h-100">
        <div class="rep-card-header">Revenue by Branch</div>
        <div class="rep-card-body"><canvas id="repBranchChart" height="220"></canvas></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="rep-card h-100">
        <div class="rep-card-header">Expenses by Category</div>
        <div class="rep-card-body"><canvas id="repExpenseChart" height="220"></canvas></div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="rep-card h-100">
        <div class="rep-card-header">Monthly Revenue (12 months)</div>
        <div class="rep-card-body"><canvas id="repMonthlyChart" height="160"></canvas></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="rep-card h-100">
        <div class="rep-card-header">Top Suppliers</div>
        <div class="rep-card-body"><canvas id="repSupplierChart" height="220"></canvas></div>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs rep-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#repTabRevenue" type="button">Revenue</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#repTabExpenses" type="button">Expenses</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#repTabSuppliers" type="button">Suppliers</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#repTabParcels" type="button">Parcels</button></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="repTabRevenue">
      <div class="rep-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 datatable" id="repRevenueTable" data-dt-scroll-x="true">
              <thead><tr><th>Branch</th><th>Accounting</th><th>Freight</th><th class="text-end">Total</th><th class="text-end">%</th><th class="text-end">Txns</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="repTabExpenses">
      <div class="rep-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 datatable" id="repExpenseTable">
              <thead><tr><th>Date</th><th>Number</th><th>Category</th><th>Branch</th><th>Supplier</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="repTabSuppliers">
      <div class="rep-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 datatable" id="repSupplierTable">
              <thead><tr><th>Supplier</th><th class="text-end">Parcels</th><th class="text-end">Revenue</th><th class="text-end">Pending</th><th class="text-end">Delivered</th><th class="text-end">Cancelled</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="repTabParcels">
      <div class="rep-card">
        <div class="rep-card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 datatable" id="repParcelTable" data-dt-scroll-x="true">
              <thead><tr><th>Date</th><th>Tracking</th><th>Customer</th><th>Supplier</th><th>From</th><th>To</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
