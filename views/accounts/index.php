<?php
/** @var string $from */ /** @var string $to */ /** @var float $totalPayments */ /** @var float $totalExpenses */ /** @var int $branchId */
$tab = (string) ($_GET['tab'] ?? 'all');
if (!in_array($tab, ['all', 'add', 'statement'], true)) {
  $tab = 'all';
}
$today = date('Y-m-d');
$df = Helpers::parseDateOr((string)($_GET['df'] ?? ''), date('Y-m-01'));
$dt = Helpers::parseDateOr((string)($_GET['dt'] ?? ''), $today);
[$df, $dt] = Helpers::orderDateRange($df, $dt);
$csrf = Helpers::csrfToken();
$reportTitle = 'Customer Statement';
$reportPeriod = $df . ' — ' . $dt;
include __DIR__ . '/../partials/report/embed_block.php';
?>

<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/accounts-module.css?v=1'); ?>">

<div class="accounts-page">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <h3 class="mb-0">Accounts</h3>
      <div class="text-muted small">Manage ledgers and view account statements.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-primary rounded-pill" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=add'); ?>">
        <i class="bi bi-plus-lg me-1"></i>Add Account
      </a>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-pills gap-2 mb-3 flex-nowrap overflow-auto">
    <li class="nav-item"><a class="nav-link rounded-pill <?php echo $tab==='all'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=all'); ?>"><i class="bi bi-list-ul me-1"></i>All Accounts</a></li>
    <li class="nav-item"><a class="nav-link rounded-pill <?php echo $tab==='add'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=add'); ?>"><i class="bi bi-plus-circle me-1"></i>Add Account</a></li>
    <li class="nav-item"><a class="nav-link rounded-pill <?php echo $tab==='statement'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=statement'); ?>"><i class="bi bi-receipt-cutoff me-1"></i>Account Statement</a></li>
  </ul>

  <!-- All Accounts -->
  <section class="<?php echo $tab==='all'?'':'d-none'; ?>" id="accAllPanel">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
          <div class="col-12 col-md-4">
            <label class="form-label small mb-1" for="accSearch">Search</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="search" class="form-control" id="accSearch" placeholder="Search account name…" autocomplete="off">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="accFilterType">Type</label>
            <select id="accFilterType" class="form-select" data-enhance="false">
              <option value="">All</option>
              <option value="cash">Cash</option>
              <option value="bank">Bank</option>
              <option value="customer">Customer</option>
              <option value="supplier">Supplier</option>
              <option value="employee">Employee</option>
              <option value="main">Main (Cash + Digital)</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="accFilterStatus">Status</label>
            <select id="accFilterStatus" class="form-select" data-enhance="false">
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12 col-md-2 d-grid">
            <button class="btn btn-outline-secondary" id="accRefresh"><i class="bi bi-arrow-repeat me-1"></i>Refresh</button>
          </div>
        </div>

        <div id="accAllLoading" class="text-center text-muted py-4 d-none">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <div class="table-responsive d-none d-md-block">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Account Name</th>
                <th>Type</th>
                <th class="text-end">Opening</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="accAllTbody"></tbody>
          </table>
        </div>
        <div class="d-md-none" id="accAllCards"></div>

        <div id="accAllEmpty" class="text-center text-muted py-5 d-none">
          <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
          <div class="fw-semibold">No accounts found</div>
          <div class="small mb-3">Try a different search/filter, or add a new account.</div>
          <a class="btn btn-primary rounded-pill" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=add'); ?>"><i class="bi bi-plus-lg me-1"></i>Add Account</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Add/Edit -->
  <section class="<?php echo $tab==='add'?'':'d-none'; ?>" id="accFormPanel">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div>
            <h5 class="mb-0">Add / Edit Account</h5>
            <div class="small text-muted">Create new ledgers for cash, bank, customer, or supplier.</div>
          </div>
          <button class="btn btn-outline-secondary rounded-pill d-none" id="accFormReset"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
        </div>

        <div class="alert alert-danger d-none" id="accFormErr"></div>
        <input type="hidden" id="accFormId" value="0">

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold" for="accName">Account Name</label>
            <input class="form-control" id="accName" placeholder="e.g. Main Cash" autocomplete="off">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold" for="accType">Account Type</label>
            <select class="form-select" id="accType" data-enhance="false">
              <option value="cash">Cash</option>
              <option value="bank">Bank</option>
              <option value="branch">Digital</option>
              <option value="customer">Customer</option>
              <option value="supplier">Supplier</option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold" for="accOpening">Opening Balance</label>
            <input class="form-control" id="accOpening" placeholder="0.00" inputmode="decimal" autocomplete="off">
            <div class="form-text">Numeric only.</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label small fw-semibold" for="accStatus">Status</label>
            <select class="form-select" id="accStatus" data-enhance="false">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold" for="accDesc">Description</label>
            <textarea class="form-control" id="accDesc" rows="3" placeholder="Optional"></textarea>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 acc-sticky-actions">
          <button class="btn btn-primary rounded-pill" id="accSave"><i class="bi bi-check2 me-1"></i>Save</button>
          <a class="btn btn-outline-secondary rounded-pill" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=all'); ?>"><i class="bi bi-x-lg me-1"></i>Cancel</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Statement -->
  <section class="<?php echo $tab==='statement'?'':'d-none'; ?>" id="accStmtPanel">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
          <div class="min-w-0">
            <h5 class="mb-1 text-truncate" id="accStmtTitle">Account Statement</h5>
            <div class="small text-muted" id="accStmtSub">Select an account to view transactions.</div>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-success rounded-pill" id="accStmtAddTxn" href="#"><i class="bi bi-plus-circle me-1"></i>Add Transaction</a>
            <a class="btn btn-warning text-dark rounded-pill" id="accStmtTransfer" href="#"><i class="bi bi-shuffle me-1"></i>Transfer</a>
          </div>
        </div>

        <div class="row g-2 align-items-end mb-3">
          <div class="col-12 col-md-4">
            <label class="form-label small mb-1" for="accStmtAccount">Account</label>
            <select id="accStmtAccount" class="form-select" data-enhance="false"></select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="accStmtFrom">From</label>
            <input type="date" id="accStmtFrom" class="form-control" value="<?php echo htmlspecialchars($df); ?>">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="accStmtTo">To</label>
            <input type="date" id="accStmtTo" class="form-control" value="<?php echo htmlspecialchars($dt); ?>">
          </div>
          <div class="col-12 col-md-2 d-grid">
            <button class="btn btn-outline-primary" id="accStmtLoad"><i class="bi bi-lightning-charge me-1"></i>Load</button>
          </div>
          <div class="col-12">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="search" class="form-control" id="accStmtSearch" placeholder="Search notes / peer…" autocomplete="off">
              <button class="btn btn-outline-secondary" id="accStmtExport"><i class="bi bi-download"></i></button>
              <button class="btn btn-outline-secondary" id="accStmtPrint"><i class="bi bi-printer"></i></button>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 acc-kpi acc-kpi-open">
              <div class="card-body py-3">
                <div class="small text-muted text-uppercase fw-semibold">Opening</div>
                <div class="fs-5 fw-bold font-monospace" id="accKpiOpen">0.00</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 acc-kpi acc-kpi-credit">
              <div class="card-body py-3">
                <div class="small text-success text-uppercase fw-semibold">Credit</div>
                <div class="fs-5 fw-bold font-monospace text-success" id="accKpiCredit">0.00</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 acc-kpi acc-kpi-debit">
              <div class="card-body py-3">
                <div class="small text-danger text-uppercase fw-semibold">Debit</div>
                <div class="fs-5 fw-bold font-monospace text-danger" id="accKpiDebit">0.00</div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 acc-kpi acc-kpi-balance">
              <div class="card-body py-3">
                <div class="small text-primary text-uppercase fw-semibold">Balance</div>
                <div class="fs-5 fw-bold font-monospace text-primary" id="accKpiBalance">0.00</div>
              </div>
            </div>
          </div>
        </div>

        <div id="accStmtLoading" class="text-center text-muted py-4 d-none"><div class="spinner-border text-primary"></div></div>

        <div class="table-responsive d-none d-md-block">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>ID</th>
                <th>Type</th>
                <th>Description</th>
                <th class="text-end text-danger">Debit</th>
                <th class="text-end text-success">Credit</th>
                <th class="text-end">Running</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="accStmtTbody"></tbody>
          </table>
        </div>
        <div class="d-md-none" id="accStmtCards"></div>

        <div id="accStmtEmpty" class="text-center text-muted py-5 d-none">
          <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
          <div class="fw-semibold">No transactions available</div>
          <div class="small">Try a different date range.</div>
        </div>

        <nav class="mt-3 d-flex justify-content-center" aria-label="Statement pagination">
          <ul class="pagination pagination-sm mb-0" id="accStmtPager"></ul>
        </nav>
      </div>
    </div>
  </section>
</div>

<script>
window.TMS_ACCOUNTS = <?php echo json_encode([
  'csrf' => $csrf,
  'cashbookApiUrl' => Helpers::baseUrl('index.php?page=api_cashbook'),
  'accountsUrl' => Helpers::baseUrl('index.php?page=accounts'),
  'accountingEntryUrl' => Helpers::baseUrl('index.php?page=accounting&action=entry&voucher_type=PAYMENT'),
  'transferVoucherUrl' => Helpers::baseUrl('index.php?page=transfer_voucher&action=entry'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/accounts.js?v=1'); ?>"></script>
