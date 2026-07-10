<?php
/** @var array $branchesAll */
/** @var bool $isAdmin */
/** @var int $branchId */
/** @var int $editId */
$expCssPath = dirname(__DIR__, 2) . '/public/assets/css/expenses-module.css';
$expJsPath = dirname(__DIR__, 2) . '/public/assets/js/expenses-module.js';
$expCssVer = is_file($expCssPath) ? (string) filemtime($expCssPath) : '1';
$expJsVer = is_file($expJsPath) ? (string) filemtime($expJsPath) : '1';
$base = Helpers::baseUrl('');
$csrf = Helpers::csrfToken();
$apiBase = $base . 'index.php?page=expenses';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/expenses-module.css?v=' . rawurlencode($expCssVer)); ?>">

<div class="exp-page container-fluid px-0 px-sm-1"
     id="expensesApp"
     data-api-base="<?php echo htmlspecialchars($apiBase); ?>"
     data-csrf="<?php echo htmlspecialchars($csrf); ?>"
     data-is-admin="<?php echo $isAdmin ? '1' : '0'; ?>"
     data-default-branch="<?php echo (int) $branchId; ?>"
     data-edit-id="<?php echo (int) ($editId ?? 0); ?>">

  <header class="exp-page-head d-flex flex-column flex-lg-row align-items-stretch align-items-lg-start justify-content-between gap-3 mb-3">
    <div class="min-w-0">
      <h1 class="exp-title"><i class="bi bi-wallet2" aria-hidden="true"></i> Expenses</h1>
      <p class="exp-subtitle text-muted mb-0">Record, approve, and analyse operating expenses with full accounting integration.</p>
    </div>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
      <button type="button" class="btn btn-outline-secondary" id="expBtnCategories" data-bs-toggle="modal" data-bs-target="#expCategoryModal">
        <i class="bi bi-tags me-1"></i> Categories
      </button>
      <button type="button" class="btn btn-primary" id="expBtnNew" data-bs-toggle="modal" data-bs-target="#expenseModal">
        <i class="bi bi-plus-lg me-1"></i> New Expense
      </button>
    </div>
  </header>

  <div id="expAlert" class="alert d-none" role="alert"></div>

  <?php if (($_GET['saved'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show"><span>Expense saved.</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php elseif (($_GET['approved'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show"><span>Expense approved.</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php elseif (($_GET['deleted'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show"><span>Expense deleted.</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php elseif (($_GET['settled'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show"><span>Payment recorded.</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <section class="exp-card exp-filters-card mb-3" aria-label="Filter expenses">
    <form id="expFilterForm" class="row g-3 align-items-end">
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expFrom">From Date</label>
        <input type="date" class="form-control" id="expFrom" name="from" value="<?php echo date('Y-m-01'); ?>">
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expTo">To Date</label>
        <input type="date" class="form-control" id="expTo" name="to" value="<?php echo date('Y-m-d'); ?>">
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expBranch">Branch</label>
        <select class="form-select" id="expBranch" name="branch_id">
          <option value="">All Branches</option>
          <?php foreach ($branchesAll as $b): ?>
          <option value="<?php echo (int) $b['id']; ?>" <?php echo ((int) $branchId === (int) $b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expCategory">Category</label>
        <select class="form-select" id="expCategory" name="category_id"><option value="">All Categories</option></select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expPayment">Payment Method</label>
        <select class="form-select" id="expPayment" name="payment_method">
          <option value="">All Methods</option>
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="cheque">Cheque</option>
          <option value="credit">Credit</option>
          <option value="transfer">Transfer</option>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expSupplier">Supplier</label>
        <select class="form-select" id="expSupplier" name="supplier_id"><option value="">All Suppliers</option></select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expApproval">Approval Status</label>
        <select class="form-select" id="expApproval" name="approval">
          <option value="">Any</option>
          <option value="yes">Approved</option>
          <option value="no">Pending</option>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="expCredit">Credit Status</label>
        <select class="form-select" id="expCredit" name="credit_status">
          <option value="">Any</option>
          <option value="open">Open</option>
          <option value="settled">Settled</option>
          <option value="overdue">Overdue</option>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
        <label class="form-label" for="expSearch">Search Notes / Ref</label>
        <input type="search" class="form-control" id="expSearch" name="q" placeholder="Reference, notes, party…">
      </div>
      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        <button type="button" class="btn btn-outline-secondary" id="expBtnClear">Clear</button>
        <button type="button" class="btn btn-outline-success" id="expBtnExportCsv"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel</button>
        <button type="button" class="btn btn-outline-danger" id="expBtnPrint"><i class="bi bi-printer me-1"></i> Print</button>
      </div>
    </form>
  </section>

  <section class="row g-3 mb-3" id="expStatsCards" aria-label="Expense statistics">
    <?php
    $cards = [
      ['total_expenses', 'Total Expenses', 'bi-cash-stack'],
      ['cash_expenses', 'Cash Expenses', 'bi-cash'],
      ['credit_expenses', 'Credit Expenses', 'bi-credit-card'],
      ['pending_payments', 'Pending Payments', 'bi-hourglass-split'],
      ['approved_expenses', 'Approved', 'bi-check-circle'],
      ['this_month', 'This Month', 'bi-calendar-month'],
      ['today', 'Today', 'bi-calendar-day'],
      ['outstanding_balance', 'Outstanding', 'bi-exclamation-circle'],
    ];
    foreach ($cards as [$key, $label, $icon]):
    ?>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="exp-stat-card">
        <div class="exp-stat-icon"><i class="bi <?php echo $icon; ?>"></i></div>
        <div class="exp-stat-body">
          <div class="exp-stat-label"><?php echo htmlspecialchars($label); ?></div>
          <div class="exp-stat-value" data-stat="<?php echo $key; ?>">—</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </section>

  <section class="row g-3 mb-3">
    <div class="col-lg-5">
      <div class="exp-card h-100">
        <div class="exp-card-head">Top Categories</div>
        <div class="exp-card-body"><canvas id="expChartPie" height="220" aria-label="Category pie chart"></canvas></div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="exp-card h-100">
        <div class="exp-card-head">Monthly Trend</div>
        <div class="exp-card-body"><canvas id="expChartTrend" height="220" aria-label="Monthly trend chart"></canvas></div>
      </div>
    </div>
  </section>

  <section class="exp-card mb-3" id="expTableSection">
    <div class="exp-card-head d-flex flex-wrap justify-content-between align-items-center gap-2">
      <span>Expense Register</span>
      <div class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0" for="expPageSize">Rows</label>
        <select class="form-select form-select-sm w-auto" id="expPageSize">
          <option value="10">10</option>
          <option value="25" selected>25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
    <div class="exp-card-body p-0">
      <div id="expLoading" class="text-center py-5 text-muted d-none"><div class="spinner-border spinner-border-sm me-2"></div>Loading…</div>
      <div id="expEmpty" class="exp-empty d-none">
        <i class="bi bi-inbox"></i>
        <p>No expenses match your filters.</p>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#expenseModal">Add first expense</button>
      </div>

      <div class="d-none d-lg-block table-responsive exp-table-wrap">
        <table class="table table-hover table-sm align-middle mb-0 exp-table" id="expTableDesktop">
          <thead class="table-light sticky-top">
            <tr>
              <th>Expense No</th>
              <th>Date</th>
              <th>Category</th>
              <th>Supplier</th>
              <th>Branch</th>
              <th class="text-end">Amount</th>
              <th class="text-end">Paid</th>
              <th class="text-end">Balance</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Approved By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="expTableBody"></tbody>
        </table>
      </div>

      <div class="d-lg-none" id="expCardsMobile"></div>

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 p-3 border-top" id="expPaginationWrap">
        <div class="small text-muted" id="expPaginationInfo"></div>
        <nav aria-label="Expense pagination"><ul class="pagination pagination-sm mb-0" id="expPagination"></ul></nav>
      </div>
    </div>
  </section>
</div>

<?php require __DIR__ . '/partials/expense_modal.php'; ?>
<?php require __DIR__ . '/partials/category_modal.php'; ?>

<div class="d-none" id="expPrintArea"></div>

<script src="<?php echo Helpers::baseUrl('assets/js/expenses-module.js?v=' . rawurlencode($expJsVer)); ?>"></script>
