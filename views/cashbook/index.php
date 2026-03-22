<?php
/** @var string $csrf */
$backupUrl = Helpers::baseUrl('index.php?page=backup');
$settingsUrl = Helpers::baseUrl('index.php?page=settings');
$reportsLegacyUrl = Helpers::baseUrl('index.php?page=reports');
$base = Helpers::baseUrl('');
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/cashbook.css'); ?>">

<div class="cashbook-page pb-5">
  <!-- App bar -->
  <div class="cashbook-appbar">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#cashbookMenu" aria-controls="cashbookMenu" title="Menu">
        <i class="bi bi-list" aria-hidden="true"></i>
      </button>
      <div class="flex-grow-1 min-w-0" style="max-width: 280px;">
        <label class="visually-hidden" for="cbAccountSelect">Account</label>
        <select id="cbAccountSelect" class="form-select form-select-sm"></select>
      </div>
      <div class="flex-grow-1 min-w-0" style="min-width: 120px;">
        <label class="visually-hidden" for="cbSearch">Search</label>
        <input type="search" id="cbSearch" class="form-control form-control-sm" placeholder="Search notes…" autocomplete="off">
      </div>
      <div class="d-flex align-items-center gap-1">
        <label class="visually-hidden" for="cbAnchorDate">Reference date</label>
        <input type="date" id="cbAnchorDate" class="form-control form-control-sm" title="Reference date for period">
      </div>
      <div class="d-none d-lg-flex gap-2 flex-shrink-0 ms-auto">
        <button type="button" class="btn btn-success btn-sm" id="cbBtnIncomeDesk"><i class="bi bi-plus-circle me-1"></i>Income</button>
        <button type="button" class="btn btn-danger btn-sm" id="cbBtnExpenseDesk"><i class="bi bi-dash-circle me-1"></i>Expense</button>
      </div>
    </div>
    <ul class="nav nav-pills cashbook-tabs gap-1 flex-nowrap overflow-auto py-2 mb-0" role="tablist">
      <li class="nav-item"><button type="button" class="nav-link cb-period-tab active" data-period="all">All</button></li>
      <li class="nav-item"><button type="button" class="nav-link cb-period-tab" data-period="daily">Daily</button></li>
      <li class="nav-item"><button type="button" class="nav-link cb-period-tab" data-period="weekly">Weekly</button></li>
      <li class="nav-item"><button type="button" class="nav-link cb-period-tab" data-period="monthly">Monthly</button></li>
      <li class="nav-item"><button type="button" class="nav-link cb-period-tab" data-period="yearly">Yearly</button></li>
    </ul>
  </div>

  <!-- Panels -->
  <div data-cb-panel="transactions" class="mb-3">
    <div id="cbEntryList" class="mt-2"></div>
  </div>

  <div data-cb-panel="summary" class="cashbook-panel-hidden mb-3">
    <h2 class="h6 fw-bold mb-2">Account summary</h2>
    <p class="small text-muted mb-2" id="cbSummaryHint">Totals for the selected period (reference date).</p>
    <div id="cbAccountSummary"></div>
  </div>

  <div data-cb-panel="reports" class="cashbook-panel-hidden mb-3">
    <h2 class="h6 fw-bold mb-2">Reports</h2>
    <div class="row g-2 align-items-end mb-2">
      <div class="col-sm-4">
        <label class="form-label small mb-0" for="cbReportFrom">From</label>
        <input type="date" id="cbReportFrom" class="form-control form-control-sm">
      </div>
      <div class="col-sm-4">
        <label class="form-label small mb-0" for="cbReportTo">To</label>
        <input type="date" id="cbReportTo" class="form-control form-control-sm">
      </div>
      <div class="col-sm-4">
        <button type="button" id="cbReportLoad" class="btn btn-primary btn-sm w-100">Load</button>
      </div>
    </div>
    <div id="cbReportTable" class="table-responsive border rounded bg-white"></div>
    <p class="small text-muted mt-2 mb-0"><a href="<?php echo htmlspecialchars($reportsLegacyUrl); ?>">Open full Reports</a></p>
  </div>

  <!-- Sticky summary -->
  <div class="cashbook-summary-strip">
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
        <div class="fw-bold text-dark" id="cbSummaryBalance">0.00</div>
      </div>
    </div>
    <p class="small text-muted mb-0 mt-1">Balance is the current account balance (all activity). Income/Expense reflect the selected period only.</p>
  </div>

  <!-- Bottom actions (mobile-first) -->
  <div class="cashbook-bottom-actions d-lg-none">
    <button type="button" class="btn btn-success" id="cbBtnIncome"><i class="bi bi-plus-circle me-1"></i>Income</button>
    <button type="button" class="btn btn-danger" id="cbBtnExpense"><i class="bi bi-dash-circle me-1"></i>Expense</button>
  </div>

  <!-- Offcanvas menu -->
  <div class="offcanvas offcanvas-start" tabindex="-1" id="cashbookMenu" aria-labelledby="cashbookMenuLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="cashbookMenuLabel">Cash Book</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
      <nav class="nav flex-column cashbook-offcanvas py-2">
        <a class="nav-link px-3 py-2 cb-nav-panel" href="#" data-panel="transactions"><i class="bi bi-list-columns-reverse me-2"></i>Transactions</a>
        <a class="nav-link px-3 py-2 cb-nav-panel" href="#" data-panel="summary"><i class="bi bi-pie-chart me-2"></i>Summary</a>
        <a class="nav-link px-3 py-2 cb-nav-panel" href="#" data-panel="reports"><i class="bi bi-graph-up me-2"></i>Reports</a>
        <hr class="my-2">
        <button type="button" class="nav-link text-start px-3 py-2 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#cashbookAccountsModal"><i class="bi bi-bank me-2"></i>Accounts</button>
        <button type="button" class="nav-link text-start px-3 py-2 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#cashbookTransferModal"><i class="bi bi-arrow-left-right me-2"></i>Transfer</button>
        <button type="button" class="nav-link text-start px-3 py-2 border-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#cashbookCalcModal"><i class="bi bi-calculator me-2"></i>Calculator</button>
        <hr class="my-2">
        <?php if (Auth::isAdmin()): ?>
          <a class="nav-link px-3 py-2" href="<?php echo htmlspecialchars($backupUrl); ?>"><i class="bi bi-hdd me-2"></i>Backup &amp; Restore</a>
        <?php endif; ?>
        <a class="nav-link px-3 py-2" href="<?php echo htmlspecialchars($settingsUrl); ?>"><i class="bi bi-gear me-2"></i>Settings</a>
      </nav>
    </div>
  </div>

  <!-- Toast host -->
  <div id="cashbookToastHost" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080"></div>

  <!-- Add / edit transaction -->
  <div class="modal fade" id="cashbookTxnModal" tabindex="-1" aria-labelledby="cashbookTxnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <div class="modal-header py-2">
          <h5 class="modal-title fs-6" id="cashbookTxnModalLabel">Income / Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="cbTxnId" value="">
          <div class="btn-group w-100 mb-3" role="group">
            <input type="radio" class="btn-check" name="cbTxnType" id="cbTxnIncome" value="income" autocomplete="off" checked>
            <label class="btn btn-outline-success" for="cbTxnIncome">Income</label>
            <input type="radio" class="btn-check" name="cbTxnType" id="cbTxnExpense" value="expense" autocomplete="off">
            <label class="btn btn-outline-danger" for="cbTxnExpense">Expense</label>
          </div>
          <div class="mb-3">
            <label class="form-label small" for="cbTxnAmount">Amount</label>
            <input type="text" inputmode="decimal" class="form-control form-control-lg" id="cbTxnAmount" placeholder="0.00" autocomplete="off">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label small" for="cbTxnDate">Date</label>
              <input type="date" class="form-control" id="cbTxnDate">
            </div>
            <div class="col-5">
              <label class="form-label small" for="cbTxnTime">Time</label>
              <input type="time" class="form-control" id="cbTxnTime" step="60">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small" for="cbTxnNotes">Notes</label>
            <textarea class="form-control" id="cbTxnNotes" rows="2" placeholder="Description"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small" for="cbTxnParcel">Parcel (optional — income reference)</label>
            <input type="text" class="form-control form-control-sm" id="cbTxnParcel" placeholder="Parcel ID" inputmode="numeric">
          </div>
          <div class="mb-3">
            <label class="form-label small" for="cbTxnItems">Line items (optional JSON array)</label>
            <textarea class="form-control form-control-sm font-monospace" id="cbTxnItems" rows="2" placeholder='[{"label":"Item","amount":100}]'></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small" for="cbTxnFile">Bill / receipt (image or PDF, max 4MB)</label>
            <input type="file" class="form-control form-control-sm" id="cbTxnFile" accept="image/*,application/pdf">
          </div>
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="cbSaveTxnCont">Save &amp; continue</button>
          <button type="button" class="btn btn-success" id="cbSaveTxn">Save &amp; exit</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Transfer -->
  <div class="modal fade" id="cashbookTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header py-2">
          <h5 class="modal-title fs-6">Transfer between accounts</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small" for="cbTrFrom">From</label>
            <select class="form-select" id="cbTrFrom"></select>
          </div>
          <div class="mb-2">
            <label class="form-label small" for="cbTrTo">To</label>
            <select class="form-select" id="cbTrTo"></select>
          </div>
          <div class="mb-2">
            <label class="form-label small" for="cbTrAmount">Amount</label>
            <input type="text" inputmode="decimal" class="form-control form-control-lg" id="cbTrAmount" placeholder="0.00">
          </div>
          <div class="row g-2 mb-2">
            <div class="col-7">
              <label class="form-label small" for="cbTrDate">Date</label>
              <input type="date" class="form-control" id="cbTrDate">
            </div>
            <div class="col-5">
              <label class="form-label small" for="cbTrTime">Time</label>
              <input type="time" class="form-control" id="cbTrTime" step="60">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label small" for="cbTrNotes">Notes</label>
            <textarea class="form-control" id="cbTrNotes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary w-100" id="cbTransferSave">Save transfer</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Accounts -->
  <div class="modal fade" id="cashbookAccountsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
      <div class="modal-content border-0 shadow">
        <div class="modal-header py-2">
          <h5 class="modal-title fs-6">Accounts</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-primary" id="cbAccAddNew">New account</button>
          </div>
          <div id="cbAccountList" class="mb-3"></div>
          <hr>
          <h6 class="small text-uppercase text-muted">Add / edit</h6>
          <input type="hidden" id="cbAccId" value="">
          <div class="mb-2">
            <label class="form-label small" for="cbAccName">Name</label>
            <input type="text" class="form-control" id="cbAccName" placeholder="e.g. Cash Book">
          </div>
          <div class="mb-2">
            <label class="form-label small" for="cbAccType">Type</label>
            <select class="form-select" id="cbAccType">
              <option value="cash">Cash</option>
              <option value="bank">Bank</option>
              <option value="branch">Branch</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small" for="cbAccBranch">Branch ID (optional)</label>
            <input type="number" class="form-control form-control-sm" id="cbAccBranch" placeholder="Leave empty for default">
          </div>
          <button type="button" class="btn btn-primary btn-sm" id="cbAccountSave">Save account</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Calculator -->
  <div class="modal fade" id="cashbookCalcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header py-2">
          <h5 class="modal-title fs-6">Calculator</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="cashbook-calc-display mb-2 p-2 bg-light rounded text-end" id="cbCalcDisplay">0</div>
          <div class="d-flex flex-column gap-1">
            <div class="d-flex gap-1"><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="C">C</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="/">/</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="*">×</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="-">−</button></div>
            <div class="d-flex gap-1"><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="7">7</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="8">8</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="9">9</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="+">+</button></div>
            <div class="d-flex gap-1"><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="4">4</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="5">5</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="6">6</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="=">=</button></div>
            <div class="d-flex gap-1"><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="1">1</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="2">2</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="3">3</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="=">=</button></div>
            <div class="d-flex gap-1"><button type="button" class="btn btn-outline-secondary flex-fill" data-calc="0">0</button><button type="button" class="btn btn-outline-secondary flex-fill" data-calc=".">.</button><button type="button" class="btn btn-primary flex-fill" data-calc="=">=</button></div>
          </div>
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
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/cashbook.js'); ?>"></script>
