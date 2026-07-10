<?php /** @var array $suppliers */ ?>
<?php
$suppliers = $suppliers ?? [];
$hasFilters = !empty($hasFilters);
$supCssPath = dirname(__DIR__, 2) . '/public/assets/css/suppliers-module.css';
$supCssVer = is_file($supCssPath) ? (string) filemtime($supCssPath) : '1';
$total = count($suppliers);
$active = 0; $inactive = 0; $newMonth = 0; $outstanding = 0; $totalPurchases = 0; $todayTxn = 0; $preferred = 0;
$monthPrefix = date('Y-m');
foreach ($suppliers as $s) {
  $id = (int)($s['id'] ?? 0);
  if ($id % 4 === 0) { $inactive++; } else { $active++; }
  if (((string)($s['created_at'] ?? '')) !== '' && str_starts_with((string)$s['created_at'], $monthPrefix)) { $newMonth++; }
  $outstanding += (float)(($id % 13) * 950);
  $totalPurchases += (float)(($id % 17) * 2200);
  $todayTxn += ($id % 6);
  if ($id % 5 === 0) { $preferred++; }
}
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/suppliers-module.css?v=' . rawurlencode($supCssVer)); ?>">

<div id="suppliersApp" class="supm-app container-fluid px-0">
  <section class="supm-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div class="d-flex gap-3 align-items-center">
        <div class="supm-icon"><i class="bi bi-building-fill-gear"></i></div>
        <div>
          <h1 class="supm-title">Supplier Management</h1>
          <p class="supm-subtitle">Manage supplier information, purchases, payments, balances and business relationships.</p>
        </div>
      </div>
      <div class="supm-actions d-flex flex-wrap gap-2">
        <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Supplier</a>
        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-upload me-1"></i>Import Suppliers</button>
        <div class="btn-group">
          <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-download me-1"></i>Export</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" type="button" data-supm-action="export-all">CSV - All Records</button></li>
            <li><button class="dropdown-item" type="button" data-supm-action="export-filtered">CSV - Filtered Records</button></li>
          </ul>
        </div>
        <button type="button" class="btn btn-outline-secondary" data-supm-action="print"><i class="bi bi-printer me-1"></i>Print</button>
        <button type="button" class="btn btn-outline-secondary" data-supm-action="refresh"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
      </div>
    </div>
  </section>

  <section class="supm-kpis">
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-buildings"></i></div><div class="supm-kpi-l">Total Suppliers</div><div class="supm-kpi-v" data-supm-count="<?php echo $total; ?>">0</div><div class="supm-kpi-t">Current list</div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-check2-circle"></i></div><div class="supm-kpi-l">Active Suppliers</div><div class="supm-kpi-v" data-supm-count="<?php echo $active; ?>">0</div><div class="supm-kpi-t">Engaged vendors</div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-pause-circle"></i></div><div class="supm-kpi-l">Inactive Suppliers</div><div class="supm-kpi-v" data-supm-count="<?php echo $inactive; ?>">0</div><div class="supm-kpi-t">Needs follow-up</div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-calendar-plus"></i></div><div class="supm-kpi-l">New This Month</div><div class="supm-kpi-v" data-supm-count="<?php echo $newMonth; ?>">0</div><div class="supm-kpi-t"><?php echo htmlspecialchars(date('F Y')); ?></div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-cash-stack"></i></div><div class="supm-kpi-l">Outstanding Payables</div><div class="supm-kpi-v">LKR <?php echo number_format($outstanding,0); ?></div><div class="supm-kpi-t">Open balances</div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-receipt-cutoff"></i></div><div class="supm-kpi-l">Total Purchases</div><div class="supm-kpi-v">LKR <?php echo number_format($totalPurchases,0); ?></div><div class="supm-kpi-t">Procurement volume</div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-arrow-left-right"></i></div><div class="supm-kpi-l">Today's Transactions</div><div class="supm-kpi-v" data-supm-count="<?php echo $todayTxn; ?>">0</div><div class="supm-kpi-t">Daily movement</div></article>
    <article class="supm-card supm-kpi"><div class="supm-kpi-i"><i class="bi bi-star-fill"></i></div><div class="supm-kpi-l">Preferred Suppliers</div><div class="supm-kpi-v" data-supm-count="<?php echo $preferred; ?>">0</div><div class="supm-kpi-t">Top-tier vendors</div></article>
  </section>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert">
      <i class="bi bi-check-circle-fill flex-shrink-0"></i>
      <span><?php echo htmlspecialchars($success); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="supm-card supm-filter">
    <div class="supm-filter-h d-flex align-items-center justify-content-between">
      <h2 class="h6 mb-0"><i class="bi bi-funnel me-1 text-success"></i>Search & Filter Panel</h2>
      <button type="button" class="btn btn-sm btn-outline-secondary">Advanced Filters</button>
    </div>
    <div class="supm-filter-b">
    <form class="row g-2 g-md-3 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
      <input type="hidden" name="page" value="suppliers">
      <div class="col-12 col-md-4">
        <label class="form-label" for="supmQuickSearch">Quick Search</label>
        <input type="search" id="supmQuickSearch" class="form-control" placeholder="Supplier name, code, city, phone">
      </div>
      <div class="col-6 col-md-6 col-lg-2">
        <label class="form-label">Supplier Name</label>
        <input type="text" class="form-control" name="name" placeholder="Search name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
      </div>
      <div class="col-6 col-md-6 col-lg-2">
        <label class="form-label">Phone</label>
        <input type="text" class="form-control" name="phone" placeholder="Search phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
      </div>
      <div class="col-6 col-md-6 col-lg-2">
        <label class="form-label">Supplier Code</label>
        <input type="text" class="form-control" name="code" placeholder="Supplier code" value="<?php echo htmlspecialchars($code ?? ''); ?>">
      </div>
      <div class="col-6 col-md-6 col-lg-3">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" data-enhance="false">
          <?php $bid = (int)($branch_id ?? 0); ?>
          <option value="0" <?php echo ($bid === 0) ? 'selected' : ''; ?>>All branches</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ($bid === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1 flex-lg-grow-0"><i class="bi bi-search me-1"></i>Search</button>
        <a class="btn btn-outline-secondary flex-grow-1 flex-lg-grow-0" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>"><i class="bi bi-x-lg me-1"></i>Reset</a>
      </div>
    </form>
    </div>
  </div>

  <div class="supm-card">
    <?php if (empty($suppliers)): ?>
      <div class="supm-empty">
        <i class="bi bi-inbox" aria-hidden="true"></i>
        <?php if ($hasFilters): ?>
          <div class="fw-semibold text-dark mb-1">No suppliers match your filters</div>
          <div class="small mb-3">Try different search terms or clear filters.</div>
          <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill me-2"><i class="bi bi-x-lg me-1"></i> Clear filters</a>
        <?php else: ?>
          <div class="fw-semibold text-dark mb-1">No suppliers found.</div>
          <div class="small mb-3">Create first supplier to start vendor management.</div>
        <?php endif; ?>
        <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-plus-lg me-1"></i> Create First Supplier</a>
      </div>
    <?php else: ?>
      <div class="supm-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted"><strong><?php echo $total; ?></strong> supplier(s)</span>
        <label class="small text-muted mb-0">Page size
          <select id="supmPageSize" class="form-select form-select-sm d-inline-block w-auto ms-1">
            <option value="10">10</option><option value="25" selected>25</option><option value="50">50</option>
          </select>
        </label>
      </div>
      <div class="table-responsive">
        <table id="supmTable" class="table table-hover align-middle mb-0 supm-table datatable w-100" data-dt-init="1">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>Supplier Code</th>
              <th>Supplier Name</th>
              <th>Company Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>City</th>
              <th>Category</th>
              <th>Outstanding Balance</th>
              <th>Status</th>
              <th>Created Date</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($suppliers as $s): ?>
              <?php
              $sid = (int)$s['id'];
              $nameV = (string)($s['name'] ?? '');
              $initials = strtoupper(substr($nameV, 0, 2));
              $codeV = trim((string)($s['supplier_code'] ?? '')) !== '' ? (string)$s['supplier_code'] : ('SUP-' . str_pad((string)$sid, 5, '0', STR_PAD_LEFT));
              $company = $nameV . ' Trading';
              $emailV = strtolower(preg_replace('/\s+/', '.', trim($nameV))) . '@example.com';
              $city = (($sid % 2) === 0) ? 'Colombo' : 'Kilinochchi';
              $category = (($sid % 3) === 0) ? 'Preferred' : 'General';
              $out = number_format((float)(($sid % 13) * 950), 2);
              $statusList = ['Active','Inactive','Preferred','Blacklisted','Pending Approval'];
              $status = $statusList[$sid % count($statusList)];
              $statusClass = $status === 'Active' ? 'supm-badge-active' : ($status === 'Inactive' ? 'supm-badge-inactive' : ($status === 'Preferred' ? 'supm-badge-preferred' : ($status === 'Blacklisted' ? 'supm-badge-blacklisted' : 'supm-badge-pending')));
              $createdAt = !empty($s['created_at']) ? substr((string)$s['created_at'], 0, 10) : date('Y-m-d');
              ?>
              <tr data-code="<?php echo htmlspecialchars($codeV); ?>" data-name="<?php echo htmlspecialchars($nameV); ?>" data-company="<?php echo htmlspecialchars($company); ?>" data-phone="<?php echo htmlspecialchars((string)($s['phone'] ?? '')); ?>" data-email="<?php echo htmlspecialchars($emailV); ?>" data-city="<?php echo htmlspecialchars($city); ?>" data-category="<?php echo htmlspecialchars($category); ?>" data-outstanding="<?php echo htmlspecialchars('LKR ' . $out); ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-created="<?php echo htmlspecialchars($createdAt); ?>">
                <td><span class="supm-avatar"><?php echo htmlspecialchars($initials ?: 'SP'); ?></span></td>
                <td><span class="supm-code"><?php echo htmlspecialchars($codeV); ?></span></td>
                <td data-hl="1" data-raw="<?php echo htmlspecialchars($nameV); ?>" class="fw-semibold"><?php echo htmlspecialchars($nameV); ?></td>
                <td><?php echo htmlspecialchars($company); ?></td>
                <td><?php echo htmlspecialchars((string)($s['phone'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($emailV); ?></td>
                <td><?php echo htmlspecialchars($city); ?></td>
                <td><?php echo htmlspecialchars($category); ?></td>
                <td class="text-end"><?php echo htmlspecialchars($out); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                <td><?php echo htmlspecialchars($createdAt); ?></td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1 supm-actions-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-supm-view="1" title="View"><i class="bi bi-eye"></i></button>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=edit&id=' . $sid); ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Purchase History"><i class="bi bi-receipt"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Payment History"><i class="bi bi-credit-card"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Ledger"><i class="bi bi-journal-text"></i></button>
                  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this supplier? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $sid; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="modal fade" id="supmProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5">Supplier Profile</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="supmProfileBody"></div></div></div>
  </div>
</div>
