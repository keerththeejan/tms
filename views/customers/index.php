<?php
/** @var array $customers */
$customers = $customers ?? [];
$name = $name ?? '';
$phone = $phone ?? '';
$email = $email ?? '';
$address = $address ?? '';
$delivery_location = $delivery_location ?? '';
$type = $type ?? '';
$q = $q ?? '';

$crmCssPath = dirname(__DIR__, 2) . '/public/assets/css/customers-module.css';
$crmCssVer = is_file($crmCssPath) ? (string) filemtime($crmCssPath) : '1';

$importedCnt = isset($_GET['imported']) ? (int)$_GET['imported'] : null;
$failedCnt = isset($_GET['failed']) ? (int)$_GET['failed'] : null;
$importFailed = (string)($_GET['import_failed'] ?? '') === '1';
$purgeFailed = (string)($_GET['purge_failed'] ?? '') === '1';
$purgeError = (string)($_GET['purge_error'] ?? '') === '1';
$purgedCnt = isset($_GET['purged']) ? (int)$_GET['purged'] : null;
$purgeErrors = [];
if ($purgeFailed && isset($_SESSION['customer_purge_errors']) && is_array($_SESSION['customer_purge_errors'])) {
  $purgeErrors = $_SESSION['customer_purge_errors'];
  unset($_SESSION['customer_purge_errors']);
}
$hasImportErrors = (string)($_GET['import_errors'] ?? '') === '1';
$importErrors = [];
if ($hasImportErrors && isset($_SESSION['import_customer_errors']) && is_array($_SESSION['import_customer_errors'])) {
  $importErrors = $_SESSION['import_customer_errors'];
  unset($_SESSION['import_customer_errors']);
}

$crmInitials = static function (string $n): string {
  $n = trim($n);
  if ($n === '') return '?';
  $p = preg_split('/\s+/', $n) ?: [];
  if (count($p) === 1) return strtoupper(substr($p[0], 0, 2));
  return strtoupper(substr($p[0], 0, 1) . substr($p[count($p) - 1], 0, 1));
};
$crmCode = static fn (int $id): string => 'CUS-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
$crmCity = static function (array $c): string {
  $dl = trim((string)($c['delivery_location'] ?? ''));
  if ($dl !== '') {
    $parts = array_map('trim', explode(',', $dl));
    return $parts[0] !== '' ? $parts[0] : $dl;
  }
  $addr = trim((string)($c['address'] ?? ''));
  if ($addr !== '') {
    $parts = array_map('trim', explode(',', $addr));
    return $parts[0] !== '' ? $parts[0] : $addr;
  }
  return '';
};
$crmStatus = static function (array $c): array {
  $typeV = strtolower(trim((string)($c['customer_type'] ?? '')));
  $created = (string)($c['created_at'] ?? '');
  $isNew = $created !== '' && str_starts_with($created, date('Y-m'));
  if ($typeV === 'corporate') return ['vip', 'VIP', 'crm-badge-vip'];
  if (isset($c['ledger_active']) && (int)$c['ledger_active'] !== 1) return ['inactive', 'Inactive', 'bg-secondary-subtle text-secondary'];
  if ($isNew) return ['new', 'New', 'bg-primary-subtle text-primary'];
  return ['active', 'Active', 'bg-success-subtle text-success'];
};
$crmPhoneDisplay = static function (array $c): string {
  $ph = trim((string)($c['phone'] ?? ''));
  return (preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1) ? '' : $ph;
};

$kpiTotal = count($customers);
$kpiActive = 0;
$kpiInactive = 0;
$kpiNewMonth = 0;
$kpiOutstanding = 0.0;
$kpiSales = 0.0;
$kpiToday = 0;
$kpiVip = 0;
$monthPrefix = date('Y-m');
$today = date('Y-m-d');
foreach ($customers as $c) {
  [$st] = $crmStatus($c);
  if ($st === 'inactive') $kpiInactive++;
  else $kpiActive++;
  if ($st === 'vip') $kpiVip++;
  $created = (string)($c['created_at'] ?? '');
  if ($created !== '' && str_starts_with($created, $monthPrefix)) $kpiNewMonth++;
  if ($created !== '' && substr($created, 0, 10) === $today) $kpiToday++;
  $kpiOutstanding += (float)($c['outstanding_amount'] ?? 0);
  $kpiSales += (float)($c['total_invoices'] ?? 0);
}

$flashMessage = '';
$flashType = 'success';
if ($purgeError) {
  $flashMessage = 'Type DELETE CUSTOMERS exactly to confirm clearing all customer data.';
  $flashType = 'warning';
} elseif ($purgeFailed) {
  $flashMessage = 'Failed to clear all customer data.';
  $flashType = 'danger';
} elseif ($purgedCnt !== null) {
  $flashMessage = 'Cleared ' . (int)$purgedCnt . ' customer record(s) and related data.';
} elseif ($importFailed) {
  $flashMessage = 'Import failed. Please upload a valid .csv file exported from Excel.';
  $flashType = 'danger';
} elseif ($importedCnt !== null || $failedCnt !== null) {
  $flashMessage = 'Imported: ' . (int)($importedCnt ?? 0) . ' | Failed: ' . (int)($failedCnt ?? 0);
  $flashType = 'info';
}
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/customers-module.css?v=' . rawurlencode($crmCssVer)); ?>">

<div id="customersApp" class="crm-page container-fluid px-0" data-api-base="<?php echo htmlspecialchars(Helpers::baseUrl('')); ?>">

<div class="position-fixed bottom-0 end-0 p-3 crm-toast-stack" id="crmToastContainer" aria-live="polite" aria-atomic="true"></div>

<section class="crm-hero" aria-labelledby="crmPageTitle">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="crm-hero-icon" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
      <div>
        <h1 id="crmPageTitle" class="crm-hero-title">Customer Management</h1>
        <p class="crm-hero-subtitle">Manage customer information, contacts, balances and transactions.</p>
      </div>
    </div>
    <div class="crm-hero-actions d-flex flex-wrap gap-2 justify-content-lg-end">
      <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Customer
      </a>
      <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerImportModal" aria-label="Import customers">
        <i class="bi bi-file-earmark-arrow-up me-1" aria-hidden="true"></i>Import Customers
      </button>
      <div class="btn-group">
        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-download me-1" aria-hidden="true"></i>Export
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><button class="dropdown-item" type="button" data-crm-action="export-all"><i class="bi bi-filetype-csv me-2"></i>All Records (CSV)</button></li>
          <li><button class="dropdown-item" type="button" data-crm-action="export-filtered"><i class="bi bi-funnel me-2"></i>Filtered / Visible (CSV)</button></li>
        </ul>
      </div>
      <button type="button" class="btn btn-outline-secondary" data-crm-action="print-table" aria-label="Print customer list">
        <i class="bi bi-printer me-1" aria-hidden="true"></i>Print
      </button>
      <button type="button" class="btn btn-outline-secondary" id="crmBtnRefresh" aria-label="Refresh customer list">
        <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
      </button>
      <?php if (Auth::isAdmin()): ?>
      <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#customerPurgeModal" aria-label="Clear all customers">
        <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Clear All
      </button>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if (!empty($purgeErrors) || !empty($importErrors)): ?>
<div class="alert alert-danger" role="alert">
  <?php foreach ($purgeErrors as $pe): ?><div><?php echo htmlspecialchars((string)$pe); ?></div><?php endforeach; ?>
  <?php foreach ($importErrors as $e): ?><div><?php echo htmlspecialchars((string)$e); ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<section class="crm-kpi-grid" aria-label="Customer summary metrics">
  <article class="crm-kpi-card crm-kpi-card--blue">
    <div class="crm-kpi-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">Total Customers</div>
      <div class="crm-kpi-value" data-count="<?php echo (int)$kpiTotal; ?>">0</div>
      <div class="crm-kpi-trend"><i class="bi bi-graph-up me-1"></i>Current list</div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--green">
    <div class="crm-kpi-icon"><i class="bi bi-person-check" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">Active Customers</div>
      <div class="crm-kpi-value" data-count="<?php echo (int)$kpiActive; ?>">0</div>
      <div class="crm-kpi-trend">Engaged accounts</div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--gray">
    <div class="crm-kpi-icon"><i class="bi bi-person-dash" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">Inactive Customers</div>
      <div class="crm-kpi-value" data-count="<?php echo (int)$kpiInactive; ?>">0</div>
      <div class="crm-kpi-trend">Ledger inactive</div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--cyan">
    <div class="crm-kpi-icon"><i class="bi bi-calendar-plus" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">New This Month</div>
      <div class="crm-kpi-value" data-count="<?php echo (int)$kpiNewMonth; ?>">0</div>
      <div class="crm-kpi-trend"><?php echo htmlspecialchars(date('F Y')); ?></div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--amber">
    <div class="crm-kpi-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">Outstanding Balance</div>
      <div class="crm-kpi-value" data-count="<?php echo $kpiOutstanding; ?>" data-count-money="1">0</div>
      <div class="crm-kpi-trend">Receivables due</div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--violet">
    <div class="crm-kpi-icon"><i class="bi bi-receipt" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">Total Sales</div>
      <div class="crm-kpi-value" data-count="<?php echo $kpiSales; ?>" data-count-money="1">0</div>
      <div class="crm-kpi-trend">Invoice value</div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--rose">
    <div class="crm-kpi-icon"><i class="bi bi-sunrise" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">Today's Customers</div>
      <div class="crm-kpi-value" data-count="<?php echo (int)$kpiToday; ?>">0</div>
      <div class="crm-kpi-trend">Registered today</div>
    </div>
  </article>
  <article class="crm-kpi-card crm-kpi-card--gold">
    <div class="crm-kpi-icon"><i class="bi bi-star-fill" aria-hidden="true"></i></div>
    <div>
      <div class="crm-kpi-label">VIP Customers</div>
      <div class="crm-kpi-value" data-count="<?php echo (int)$kpiVip; ?>">0</div>
      <div class="crm-kpi-trend">Corporate accounts</div>
    </div>
  </article>
</section>

<section class="crm-filters-card" aria-label="Search and filters">
  <div class="crm-filters-head d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h2 class="h6 mb-0 fw-bold"><i class="bi bi-search me-2 text-primary"></i>Search &amp; Filter</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="crmAdvToggle" aria-expanded="false" aria-controls="crmAdvPanel">
      <i class="bi bi-sliders me-1"></i>Advanced Filter
    </button>
  </div>
  <div class="crm-filters-body">
    <form id="crmFilterForm" class="row g-2 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
      <input type="hidden" name="page" value="customers">
      <div class="col-12 col-lg-4">
        <label class="form-label" for="crmGlobalSearch">Global Search</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
          <input type="search" class="form-control" id="crmGlobalSearch" name="q" placeholder="Name, phone, email, address…" value="<?php echo htmlspecialchars($q); ?>" aria-label="Global search">
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label" for="crmFilterName">Customer Name</label>
        <input type="text" class="form-control" id="crmFilterName" name="name" value="<?php echo htmlspecialchars($name); ?>">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label" for="crmFilterPhone">Phone Number</label>
        <input type="text" class="form-control" id="crmFilterPhone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label" for="crmFilterEmail">Email</label>
        <input type="text" class="form-control" id="crmFilterEmail" name="email" value="<?php echo htmlspecialchars($email); ?>">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label" for="crmFilterType">Customer Group</label>
        <select name="type" id="crmFilterType" class="form-select">
          <option value="" <?php echo ($type==='')?'selected':''; ?>>Any</option>
          <option value="regular" <?php echo ($type==='regular')?'selected':''; ?>>Regular</option>
          <option value="corporate" <?php echo ($type==='corporate')?'selected':''; ?>>Corporate (VIP)</option>
        </select>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label" for="crmFilterAddress">Address</label>
        <input type="text" class="form-control" id="crmFilterAddress" name="address" value="<?php echo htmlspecialchars($address); ?>">
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label" for="crmFilterDelivery">Delivery Location</label>
        <input type="text" class="form-control" id="crmFilterDelivery" name="delivery_location" value="<?php echo htmlspecialchars($delivery_location); ?>">
      </div>
      <div id="crmAdvPanel" class="col-12 d-none">
        <div class="row g-2 pt-2 border-top mt-1">
          <div class="col-6 col-md-3">
            <label class="form-label">NIC / Passport</label>
            <input type="text" class="form-control" disabled placeholder="Not in current schema" title="UI preview only">
            <div class="crm-ui-hint">Preview field — not submitted</div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">City</label>
            <input type="text" class="form-control" disabled placeholder="Use delivery location filter">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" disabled><option>All statuses</option></select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Branch</label>
            <select class="form-select" disabled><option>All branches</option></select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Registration Date</label>
            <input type="date" class="form-control" disabled>
          </div>
        </div>
      </div>
      <div class="col-12 d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
        <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>">Reset</a>
      </div>
    </form>
  </div>
</section>

<div class="crm-table-card">
  <div class="crm-table-toolbar">
    <span class="small text-muted me-auto"><strong><?php echo (int)$kpiTotal; ?></strong> customer(s) loaded</span>
    <label class="small text-muted mb-0 d-flex align-items-center gap-1">
      Page size
      <select id="crmPageSize" class="form-select form-select-sm crm-page-size" aria-label="Rows per page">
        <option value="10">10</option>
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </label>
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-layout-three-columns me-1"></i>Columns
      </button>
      <ul class="dropdown-menu dropdown-menu-end p-2 crm-col-menu" id="crmColToggleMenu">
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="1" id="crmCol1" checked><label class="form-check-label" for="crmCol1">Code</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="2" id="crmCol2" checked><label class="form-check-label" for="crmCol2">Name</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="3" id="crmCol3" checked><label class="form-check-label" for="crmCol3">Phone</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="4" id="crmCol4" checked><label class="form-check-label" for="crmCol4">Email</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="5" id="crmCol5" checked><label class="form-check-label" for="crmCol5">City</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="6" id="crmCol6" checked><label class="form-check-label" for="crmCol6">Group</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="7" id="crmCol7" checked><label class="form-check-label" for="crmCol7">Delivery</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="8" id="crmCol8" checked><label class="form-check-label" for="crmCol8">Outstanding</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="9" id="crmCol9" checked><label class="form-check-label" for="crmCol9">Status</label></li>
        <li class="form-check"><input class="form-check-input" type="checkbox" data-col="10" id="crmCol10" checked><label class="form-check-label" for="crmCol10">Created</label></li>
      </ul>
    </div>
  </div>

<?php if (empty($customers)): ?>
  <div class="crm-empty-state">
    <div class="crm-empty-icon" aria-hidden="true"><i class="bi bi-people"></i></div>
    <h3 class="h5 text-muted">No customers found.</h3>
    <p class="text-muted small mb-3">Add your first customer or import from a CSV spreadsheet.</p>
    <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i>Add First Customer
    </a>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table id="crmCustomersTable" class="table table-sm align-middle mb-0 crm-data-table datatable" data-dt-init="1" data-dt-scroll-x="true">
      <thead>
        <tr>
          <th scope="col" class="crm-col-avatar" aria-label="Avatar"></th>
          <th scope="col">Code</th>
          <th scope="col">Customer Name</th>
          <th scope="col">Phone</th>
          <th scope="col" class="d-none d-lg-table-cell">Email</th>
          <th scope="col" class="d-none d-md-table-cell">City</th>
          <th scope="col">Group</th>
          <th scope="col" class="d-none d-xl-table-cell">Delivery Location</th>
          <th scope="col" class="text-end">Outstanding</th>
          <th scope="col">Status</th>
          <th scope="col" class="d-none d-lg-table-cell">Created</th>
          <th scope="col" class="text-end crm-col-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c):
          $cid = (int)($c['id'] ?? 0);
          $code = !empty($c['ledger_code']) ? (string)$c['ledger_code'] : $crmCode($cid);
          $init = $crmInitials((string)($c['name'] ?? ''));
          $city = $crmCity($c);
          [$stKey, $stLabel, $stClass] = $crmStatus($c);
          $phShow = $crmPhoneDisplay($c);
          $out = (float)($c['outstanding_amount'] ?? 0);
          $created = (string)($c['created_at'] ?? '');
          $createdShort = $created !== '' ? substr($created, 0, 10) : '';
          $ledgerUrl = !empty($c['ledger_account_id'])
            ? Helpers::baseUrl('index.php?page=accounting&action=customer_ledger&customer_id=' . $cid)
            : '';
          $groupLabel = ($c['customer_type'] ?? '') !== '' ? ucfirst((string)$c['customer_type']) : '—';
        ?>
        <tr data-customer-id="<?php echo $cid; ?>"
            data-code="<?php echo htmlspecialchars($code); ?>"
            data-name="<?php echo htmlspecialchars((string)($c['name'] ?? '')); ?>"
            data-phone="<?php echo htmlspecialchars($phShow); ?>"
            data-email="<?php echo htmlspecialchars((string)($c['email'] ?? '')); ?>"
            data-city="<?php echo htmlspecialchars($city); ?>"
            data-group="<?php echo htmlspecialchars($groupLabel); ?>"
            data-outstanding="<?php echo number_format($out, 2, '.', ''); ?>"
            data-status="<?php echo htmlspecialchars($stLabel); ?>"
            data-created="<?php echo htmlspecialchars($createdShort); ?>"
            data-initials="<?php echo htmlspecialchars($init); ?>"
            <?php if ($ledgerUrl): ?>data-ledger-url="<?php echo htmlspecialchars($ledgerUrl); ?>"<?php endif; ?>>
          <td><span class="crm-avatar" aria-hidden="true"><?php echo htmlspecialchars($init); ?></span></td>
          <td data-highlight="1" data-text="<?php echo htmlspecialchars($code); ?>"><code class="small"><?php echo htmlspecialchars($code); ?></code></td>
          <td data-highlight="1" data-text="<?php echo htmlspecialchars((string)($c['name'] ?? '')); ?>">
            <span class="crm-cell-ellipsis fw-semibold" title="<?php echo htmlspecialchars((string)($c['name'] ?? '')); ?>"><?php echo htmlspecialchars((string)($c['name'] ?? '')); ?></span>
          </td>
          <td data-highlight="1" data-text="<?php echo htmlspecialchars($phShow); ?>"><?php echo htmlspecialchars($phShow); ?></td>
          <td class="d-none d-lg-table-cell" data-highlight="1" data-text="<?php echo htmlspecialchars((string)($c['email'] ?? '')); ?>">
            <span class="crm-cell-ellipsis"><?php echo htmlspecialchars((string)($c['email'] ?? '')); ?></span>
          </td>
          <td class="d-none d-md-table-cell" data-highlight="1" data-text="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city ?: '—'); ?></td>
          <td><?php echo htmlspecialchars($groupLabel); ?></td>
          <td class="d-none d-xl-table-cell"><span class="crm-cell-ellipsis" title="<?php echo htmlspecialchars((string)($c['delivery_location'] ?? '')); ?>"><?php echo htmlspecialchars((string)($c['delivery_location'] ?? '')); ?></span></td>
          <td class="text-end">
            <?php if ($out > 0): ?>
              <span class="badge bg-warning-subtle text-warning-emphasis"><?php echo number_format($out, 2); ?></span>
            <?php else: ?>
              <span class="text-muted small">0.00</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?php echo htmlspecialchars($stClass); ?>"><?php echo htmlspecialchars($stLabel); ?></span></td>
          <td class="d-none d-lg-table-cell text-muted small"><?php echo htmlspecialchars($createdShort ?: '—'); ?></td>
          <td class="text-end">
            <div class="crm-row-actions" role="group" aria-label="Customer actions">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-crm-action="view" data-id="<?php echo $cid; ?>" title="View profile" aria-label="View profile"><i class="bi bi-eye"></i></button>
              <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=edit&id=' . $cid); ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></a>
              <?php if ($ledgerUrl): ?>
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($ledgerUrl); ?>" title="Ledger" aria-label="Ledger"><i class="bi bi-journal-text"></i></a>
              <?php endif; ?>
              <div class="dropdown d-inline">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More actions" aria-label="More actions"><i class="bi bi-three-dots"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <?php if ($ledgerUrl): ?>
                  <li><a class="dropdown-item" href="<?php echo htmlspecialchars($ledgerUrl); ?>"><i class="bi bi-clock-history me-2"></i>Transaction History</a></li>
                  <li><a class="dropdown-item" href="<?php echo htmlspecialchars($ledgerUrl); ?>"><i class="bi bi-receipt me-2"></i>Invoices &amp; Ledger</a></li>
                  <li><a class="dropdown-item" href="<?php echo htmlspecialchars($ledgerUrl); ?>"><i class="bi bi-credit-card me-2"></i>Payments</a></li>
                  <?php endif; ?>
                  <li><button type="button" class="dropdown-item" data-crm-action="print-row"><i class="bi bi-printer me-2"></i>Print</button></li>
                  <li><button type="button" class="dropdown-item" data-crm-action="export-row"><i class="bi bi-download me-2"></i>Download CSV</button></li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=delete'); ?>" onsubmit="return confirm('Delete this customer?');">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                      <input type="hidden" name="id" value="<?php echo $cid; ?>">
                      <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                  </li>
                </ul>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</div>

</div><!-- /#customersApp -->

<div class="offcanvas offcanvas-end crm-drawer" tabindex="-1" id="crmProfileDrawer" aria-labelledby="crmProfileDrawerLabel">
  <div class="offcanvas-header">
    <h2 class="offcanvas-title h5" id="crmProfileDrawerLabel">Customer Profile</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" id="crmProfileBody"></div>
</div>

<?php if (Auth::isAdmin()): ?>
<div class="modal fade" id="customerPurgeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Clear all customer data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=purge_all'); ?>">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <p class="mb-2">This permanently deletes <strong>all customers</strong> and their related:</p>
          <ul class="small mb-3">
            <li>Parcels and parcel items</li>
            <li>Delivery notes and payments</li>
            <li>Daily invoices</li>
            <li>Route assignments</li>
          </ul>
          <p class="mb-2">Users, branches, vehicles, and suppliers are <strong>not</strong> removed.</p>
          <label class="form-label" for="confirm_purge">Type <strong>DELETE CUSTOMERS</strong> to confirm</label>
          <input type="text" class="form-control" id="confirm_purge" name="confirm_purge" autocomplete="off" required>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash3 me-1"></i>Clear all customer data</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="customerImportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="bi bi-cloud-upload me-2 text-primary"></i>Import Customers</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="crmImportForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=import'); ?>" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=import_template'); ?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-file-earmark-excel me-1"></i>Download Sample Excel (CSV)
            </a>
            <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=import_template&data_only=1'); ?>" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-filetype-csv me-1"></i>Sample data only
            </a>
          </div>
          <div id="crmImportZone" class="crm-import-zone" role="button" tabindex="0" aria-label="Drag and drop CSV file or click to browse">
            <i class="bi bi-cloud-arrow-up display-6 text-primary mb-2 d-block" aria-hidden="true"></i>
            <div class="fw-semibold">Drag &amp; drop your CSV here</div>
            <div class="small text-muted">or click to browse files</div>
            <div id="crmImportFileName" class="small text-primary mt-2"></div>
          </div>
          <input type="file" class="visually-hidden" id="crmImportFile" name="import_file" accept=".csv,text/csv" required>
          <div class="progress mt-3 d-none" id="crmImportProgress" role="progressbar" aria-label="Import progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
          </div>
          <p class="form-text mt-2 mb-0">Save your spreadsheet as <strong>CSV UTF-8</strong> before uploading. Duplicate rows are validated on import.</p>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import Customers</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
window.TMS_CUSTOMERS = <?php echo json_encode([
  'baseUrl' => Helpers::baseUrl(''),
  'csrf' => Helpers::csrfToken(),
  'globalQ' => $q,
  'flashMessage' => $flashMessage,
  'flashType' => $flashType,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
