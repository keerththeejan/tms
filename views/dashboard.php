<?php
  $dashCssPath = dirname(__DIR__) . '/public/assets/css/dashboard-module.css';
  $dashCssVer = is_file($dashCssPath) ? (string) filemtime($dashCssPath) : '1';
  $user = Auth::user();
  $userDisplay = trim((string) ($user['full_name'] ?? $user['username'] ?? 'User'));
  $branchDisplay = trim((string) ($user['branch_name'] ?? $user['branch_code'] ?? '—'));
  $kpiPending = (int) ($pendingParcels ?? 0);
  $kpiTodayParcels = is_array($todayParcels ?? null) ? count($todayParcels) : 0;
  $kpiCollections = (float) ($collectionsToday ?? 0);
  $kpiExpenses = (float) ($expensesToday ?? 0);
  $kpiDue = (float) ($totalDue ?? 0);
  $kpiCompleted = (int) array_sum(array_map(static fn($b) => (int) ($b['delivered'] ?? 0), $statusStats['today'] ?? []));
  $kpiTransfers = (int) ($transfersToday ?? 0);
  $kpiTransfersPending = (int) ($transfersPending ?? 0);
  $df = $df ?? date('Y-m-d');
  $dt = $dt ?? date('Y-m-d');
  $today = $today ?? date('Y-m-d');
  $scopeAllBranches = !empty($scopeAllBranches);
  $isSingleDay = isset($isSingleDay) ? (bool) $isSingleDay : ($df === $dt);
  $isTodayRange = isset($isTodayRange) ? (bool) $isTodayRange : ($isSingleDay && $df === $today);
  $rangeStr = htmlspecialchars($df === $dt ? $df : ($df . ' → ' . $dt));
  $kpiParcelsTitle = ($isSingleDay && $df === $today && $dt === $today) ? "Today's Parcels" : 'Parcels (filtered)';
  $kpiCollTitle = !$isSingleDay
    ? 'Collections (' . $df . '–' . $dt . ')'
    : ($isTodayRange ? "Today's Collections" : 'Collections (' . $df . ')');
  $kpiExpTitle = !$isSingleDay
    ? 'Expenses (' . $df . '–' . $dt . ')'
    : ($isTodayRange ? "Today's Expenses" : 'Expenses (' . $df . ')');
  $updatedLabel = date('d M Y');
  $base = Helpers::baseUrl('');
  $kpiCards = [
    [
      'id' => 'pending-parcels',
      'theme' => 'orange',
      'icon' => 'bi-box-seam',
      'title' => 'Pending Parcels',
      'value' => $kpiPending,
      'format' => 'count',
      'description' => 'Awaiting dispatch / receipt',
      'badge' => 'Operations',
      'href' => $base . 'index.php?page=parcels&status=pending',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'today-parcels',
      'theme' => 'blue',
      'icon' => 'bi-truck',
      'title' => $kpiParcelsTitle,
      'value' => $kpiTodayParcels,
      'format' => 'count',
      'description' => 'Parcels in selected date range',
      'badge' => 'Volume',
      'href' => $base . 'index.php?page=parcels&from=' . urlencode($df) . '&to=' . urlencode($dt),
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'collections',
      'theme' => 'green',
      'icon' => 'bi-cash-stack',
      'title' => $kpiCollTitle,
      'value' => $kpiCollections,
      'format' => 'money',
      'description' => 'Payments collected',
      'badge' => 'Finance',
      'href' => $base . 'index.php?page=delivery_notes',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'expenses',
      'theme' => 'red',
      'icon' => 'bi-wallet2',
      'title' => $kpiExpTitle,
      'value' => $kpiExpenses,
      'format' => 'money',
      'description' => 'Expense postings in range',
      'badge' => 'Finance',
      'href' => $base . 'index.php?page=expenses',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'outstanding-due',
      'theme' => 'amber',
      'icon' => 'bi-exclamation-circle',
      'title' => 'Outstanding Due',
      'value' => $kpiDue,
      'format' => 'money',
      'description' => 'Unsettled delivery notes',
      'badge' => 'Receivables',
      'href' => $base . 'index.php?page=delivery_notes',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'completed-deliveries',
      'theme' => 'teal',
      'icon' => 'bi-check-circle',
      'title' => 'Completed Deliveries',
      'value' => $kpiCompleted,
      'format' => 'count',
      'description' => "Today's branch delivery totals",
      'badge' => 'Service',
      'href' => $base . 'index.php?page=parcels&status=delivered',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'transfer-vouchers',
      'theme' => 'purple',
      'icon' => 'bi-arrow-left-right',
      'title' => 'Transfer Vouchers',
      'value' => $kpiTransfers,
      'format' => 'count',
      'description' => Helpers::formatMoney((float) ($transfersAmount ?? 0)) . ' movement today',
      'badge' => 'Treasury',
      'href' => $base . 'index.php?page=transfer_voucher',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
    [
      'id' => 'pending-transfers',
      'theme' => 'gray',
      'icon' => 'bi-clock-history',
      'title' => 'Pending Transfers',
      'value' => $kpiTransfersPending,
      'format' => 'count',
      'description' => 'Draft transfer vouchers',
      'badge' => 'Queue',
      'href' => $base . 'index.php?page=transfer_voucher',
      'link_label' => 'View Details',
      'updated' => $updatedLabel,
    ],
  ];
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/dashboard-module.css?v=' . rawurlencode($dashCssVer)); ?>">

<div
  id="mainDashboardApp"
  class="dashboard-page erp-dashboard container-fluid px-3 px-lg-4 py-3"
  data-dash-refresh-url="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?' . http_build_query([
    'page' => 'dashboard',
    'df' => $df,
    'dt' => $dt,
    'fb' => (int) ($fb ?? 0),
    'tb' => (int) ($tb ?? 0),
    'cust' => (int) ($cust ?? 0),
  ]))); ?>"
  data-currency-symbol="<?php echo htmlspecialchars(Helpers::currencySymbol()); ?>"
>
  <header class="erp-topbar mb-4" role="banner">
    <div class="erp-topbar-main">
      <div class="erp-topbar-brand">
        <div class="erp-topbar-icon" aria-hidden="true"><i class="bi bi-speedometer2"></i></div>
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb erp-breadcrumb mb-1">
              <li class="breadcrumb-item"><a href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </nav>
          <h1 class="erp-title mb-0">Executive Dashboard</h1>
          <p class="erp-subtitle mb-0">Transaction Management System · operational command center</p>
        </div>
      </div>
      <div class="erp-topbar-meta" role="group" aria-label="Session context">
        <span class="erp-chip" title="Current date"><i class="bi bi-calendar3" aria-hidden="true"></i><span><?php echo htmlspecialchars(date('d M Y')); ?></span></span>
        <span class="erp-chip" id="dashLiveClock" title="Live clock"><i class="bi bi-clock" aria-hidden="true"></i><span>--:--:--</span></span>
        <span class="erp-chip" title="Logged in user"><i class="bi bi-person-circle" aria-hidden="true"></i><span><?php echo htmlspecialchars($userDisplay); ?></span></span>
        <span class="erp-chip" title="Branch"><i class="bi bi-building" aria-hidden="true"></i><span><?php echo htmlspecialchars($branchDisplay); ?></span></span>
        <button type="button" class="erp-icon-btn" aria-label="Notifications" title="Notifications">
          <i class="bi bi-bell" aria-hidden="true"></i>
        </button>
        <button type="button" class="erp-icon-btn" aria-label="Theme toggle placeholder" title="Theme (coming soon)" disabled>
          <i class="bi bi-moon-stars" aria-hidden="true"></i>
        </button>
      </div>
    </div>
  </header>

  <section class="filters-card mb-4" aria-label="Dashboard filters">
    <form method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>" class="row g-3 align-items-end">
      <input type="hidden" name="page" value="dashboard">
      <div class="col-6 col-md-3">
        <label class="form-label" for="dashDf">From</label>
        <input type="date" class="form-control form-control-sm" id="dashDf" name="df" value="<?php echo htmlspecialchars($df ?? ($today ?? '')); ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="dashDt">To</label>
        <input type="date" class="form-control form-control-sm" id="dashDt" name="dt" value="<?php echo htmlspecialchars($dt ?? ($today ?? '')); ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="dashFb">From Branch</label>
        <select class="form-select form-select-sm" id="dashFb" name="fb" data-enhance="false">
          <option value="0">All</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int) $b['id']; ?>" <?php echo ((int) ($fb ?? 0) === (int) $b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="dashTb">To Branch</label>
        <select class="form-select form-select-sm" id="dashTb" name="tb" data-enhance="false" aria-describedby="dashTbHint">
          <option value="0">All</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int) $b['id']; ?>" <?php echo ((int) ($tb ?? 0) === (int) $b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($isMain)): ?>
        <div id="dashTbHint" class="form-text small mt-1 mb-0">“All” = every branch (main hub only).</div>
        <?php endif; ?>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="dashCust">Customer</label>
        <select class="form-select form-select-sm" id="dashCust" name="cust" data-enhance="false">
          <option value="0">All</option>
          <?php foreach (($customersAll ?? []) as $c): ?>
            <?php
              $cphone = trim((string) ($c['phone'] ?? ''));
              $clabel = ($c['name'] ?? '') . ($cphone !== '' ? ' (' . $cphone . ')' : '');
            ?>
            <option value="<?php echo (int) $c['id']; ?>" <?php echo ((int) ($cust ?? 0) === (int) $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($clabel); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-6 d-flex flex-wrap gap-2 justify-content-md-end dash-filter-actions">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Apply</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Reset</a>
      </div>
    </form>
  </section>

  <section class="mb-4" aria-labelledby="kpiSectionTitle">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
      <div>
        <h2 class="section-title mb-1" id="kpiSectionTitle">Executive KPI Dashboard</h2>
        <p class="section-subtitle mb-0">
          Key metrics for <?php echo $scopeAllBranches ? '<strong>all branches</strong>' : 'your branch filters'; ?>
          · <?php echo $isSingleDay ? 'Date: <strong>' . $rangeStr . '</strong>' : 'Range: <strong>' . $rangeStr . '</strong>'; ?>.
          <span class="erp-refresh-hint" id="dashKpiRefreshHint" aria-live="polite"></span>
        </p>
      </div>
    </div>
    <div class="row g-4" id="dashKpiGrid" data-kpi-grid>
      <?php foreach ($kpiCards as $kpi): ?>
        <div class="col-12 col-md-6 col-xl-3 d-flex">
          <?php require __DIR__ . '/dashboard/partials/kpi_card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mb-4" aria-label="Analytics and live operations">
    <div class="row g-4">
      <div class="col-12 col-lg-8">
        <div class="chart-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="section-title mb-0">Analytics &amp; Trends</h2>
            <span class="badge text-bg-light border">UI container only</span>
          </div>
          <p class="section-subtitle mb-3">Revenue, expense, parcel volume, and delivery performance visual containers.</p>
          <div class="row g-3">
            <div class="col-md-6"><div class="mini-metric"><div class="mini-label">Revenue Trend</div><div class="chart-placeholder">Chart UI</div></div></div>
            <div class="col-md-6"><div class="mini-metric"><div class="mini-label">Expense Trend</div><div class="chart-placeholder">Chart UI</div></div></div>
            <div class="col-md-6"><div class="mini-metric"><div class="mini-label">Parcel Volume</div><div class="chart-placeholder">Chart UI</div></div></div>
            <div class="col-md-6"><div class="mini-metric"><div class="mini-label">Vehicle Usage</div><div class="chart-placeholder">Chart UI</div></div></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="finance-card p-3 h-100">
          <h2 class="section-title mb-2">Live Operations</h2>
          <div class="timeline-list">
            <div class="timeline-item"><span class="dot" aria-hidden="true"></span><div><strong>Parcel Created</strong><div class="text-muted small">Recent booking captured</div></div></div>
            <div class="timeline-item"><span class="dot" aria-hidden="true"></span><div><strong>Payment Received</strong><div class="text-muted small">Collection posted</div></div></div>
            <div class="timeline-item"><span class="dot" aria-hidden="true"></span><div><strong>Delivery Completed</strong><div class="text-muted small">Route closed successfully</div></div></div>
            <div class="timeline-item"><span class="dot" aria-hidden="true"></span><div><strong>Voucher Posted</strong><div class="text-muted small">Accounting transfer entry</div></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="quick-actions mb-4" aria-labelledby="quickActionsTitle">
    <h2 class="section-title" id="quickActionsTitle">Quick Action Center</h2>
    <div class="row g-3">
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>"><div class="action-icon bg-primary-subtle text-primary"><i class="bi bi-box-seam" aria-hidden="true"></i></div><div class="action-title mt-2">New Parcel</div><div class="action-desc">Create shipment</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>"><div class="action-icon bg-info-subtle text-info"><i class="bi bi-person-plus" aria-hidden="true"></i></div><div class="action-title mt-2">New Customer</div><div class="action-desc">Add account</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>"><div class="action-icon bg-warning-subtle text-warning"><i class="bi bi-truck" aria-hidden="true"></i></div><div class="action-title mt-2">New Supplier</div><div class="action-desc">Vendor profile</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>"><div class="action-icon bg-success-subtle text-success"><i class="bi bi-receipt" aria-hidden="true"></i></div><div class="action-title mt-2">Delivery Note</div><div class="action-desc">Billing docs</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>"><div class="action-icon bg-secondary-subtle text-secondary"><i class="bi bi-bar-chart" aria-hidden="true"></i></div><div class="action-title mt-2">Reports</div><div class="action-desc">Insights</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=dashboard'); ?>"><div class="action-icon bg-danger-subtle text-danger"><i class="bi bi-calculator" aria-hidden="true"></i></div><div class="action-title mt-2">Accounting</div><div class="action-desc">Finance hub</div></a></div>
    </div>
  </section>

<?php if (isset($pendingParcels, $totalDue, $todayParcels)): ?>
  <?php if (!empty($isMain)): ?>
  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card card-dash">
        <div class="card-header-dash">All Branches (Today)</div>
        <div class="card-body p-0">
          <div class="table-responsive dash-table-x">
            <table class="table table-sm table-dash table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Branch</th>
                  <th class="text-end">Pending Parcels</th>
                  <th class="text-end">Due (Total)</th>
                  <th class="text-end">Parcels Today</th>
                  <th class="text-end">Collections Today</th>
                  <th class="text-end">Expenses Today</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($branchesAll ?? []) as $b): $bid = (int) $b['id']; ?>
                <tr>
                  <td class="fw-medium" data-label="Branch"><?php echo htmlspecialchars($b['name']); ?></td>
                  <td class="text-end" data-label="Pending">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&status=pending&to_branch_id=' . $bid); ?>"><?php echo (int) ($pendingByBranch[$bid] ?? 0); ?></a>
                  </td>
                  <td class="text-end" data-label="Due">
                    <span><?php echo Helpers::formatMoney((float) ($dueByBranch[$bid] ?? 0)); ?></span>
                  </td>
                  <td class="text-end" data-label="Parcels today">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&to_branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>"><?php echo (int) ($todayParcelsByBranch[$bid] ?? 0); ?></a>
                  </td>
                  <td class="text-end" data-label="Collections">
                    <span><?php echo Helpers::formatMoney((float) ($collectionsTodayByBranch[$bid] ?? 0)); ?></span>
                  </td>
                  <td class="text-end" data-label="Expenses">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=expenses&branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>"><?php echo Helpers::formatMoney((float) ($expensesTodayByBranch[$bid] ?? 0)); ?></a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card card-dash w-100">
        <div class="card-header-dash d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span><?php echo $isSingleDay && $df === $today && $dt === $today ? "Today's Parcels" : 'Parcels'; ?></span>
          <small class="text-muted fw-normal"><?php echo $rangeStr; ?><?php echo $scopeAllBranches ? ' · All branches' : ''; ?></small>
        </div>
        <div class="card-body">
          <?php if (empty($todayParcels)): ?>
            <p class="text-muted small mb-0">No parcels in this range.</p>
          <?php else: ?>
          <div class="table-responsive dash-table-scroll-y dash-table-x">
            <table class="table table-sm table-dash table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Customer</th>
                  <th>To</th>
                  <th>Tracking</th>
                  <th>Vehicle</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($todayParcels as $p): ?>
                <tr>
                  <td data-label="#"><?php if (Auth::canCreateParcels()): ?><a class="fw-semibold text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id=' . (int) $p['id']); ?>"><?php echo (int) $p['id']; ?></a><?php else: echo (int) $p['id']; endif; ?></td>
                  <td data-label="Customer"><?php echo htmlspecialchars($p['customer_name'] ?? ''); ?></td>
                  <td data-label="To"><?php echo htmlspecialchars($p['to_branch'] ?? '—'); ?></td>
                  <td data-label="Tracking"><?php echo htmlspecialchars($p['tracking_number'] ?? ''); ?></td>
                  <td data-label="Vehicle"><?php echo htmlspecialchars($p['vehicle_no'] ?? ''); ?></td>
                  <td data-label="Status">
                    <?php
                      $st = (string) ($p['status'] ?? '');
                      $stClass = Helpers::parcelStatusBadgeClass($st);
                    ?>
                    <span class="badge badge-soft <?php echo $stClass; ?>"><?php echo htmlspecialchars(Helpers::parcelStatusLabel($st)); ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
  $getCnt = function (array $arr, int $bid, string $status): int {
    return (int) (($arr[$bid] ?? [])[$status] ?? 0);
  };
?>

  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card card-dash">
        <div class="card-header-dash">Parcel Status by Branch</div>
        <div class="card-body">
          <ul class="nav nav-tabs nav-tabs-dash gap-1 mb-3" id="statusTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="today-tab" data-bs-toggle="tab" data-bs-target="#today-pane" type="button" role="tab" aria-controls="today-pane" aria-selected="true">Today</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="yesterday-tab" data-bs-toggle="tab" data-bs-target="#yesterday-pane" type="button" role="tab" aria-controls="yesterday-pane" aria-selected="false">Yesterday</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="last30-tab" data-bs-toggle="tab" data-bs-target="#last30-pane" type="button" role="tab" aria-controls="last30-pane" aria-selected="false">Last 30 Days</button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="today-pane" role="tabpanel" aria-labelledby="today-tab">
              <div class="table-responsive dash-table-x">
                <table class="table table-sm table-dash table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Branch</th>
                      <th class="text-end">Pending</th>
                      <th class="text-end">In Transit</th>
                      <th class="text-end">Delivered</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (($branchesAll ?? []) as $b): $bid = (int) $b['id']; ?>
                    <tr>
                      <td class="fw-medium" data-label="Branch"><?php echo htmlspecialchars($b['name']); ?></td>
                      <td class="text-end" data-label="Pending"><span class="badge badge-soft badge-soft-warning"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end" data-label="In transit"><span class="badge badge-soft badge-soft-info"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end" data-label="Delivered"><span class="badge badge-soft badge-soft-success"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'delivered'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="yesterday-pane" role="tabpanel" aria-labelledby="yesterday-tab">
              <div class="table-responsive dash-table-x">
                <table class="table table-sm table-dash table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Branch</th>
                      <th class="text-end">Pending</th>
                      <th class="text-end">In Transit</th>
                      <th class="text-end">Delivered</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (($branchesAll ?? []) as $b): $bid = (int) $b['id']; ?>
                    <tr>
                      <td class="fw-medium" data-label="Branch"><?php echo htmlspecialchars($b['name']); ?></td>
                      <td class="text-end" data-label="Pending"><span class="badge badge-soft badge-soft-warning"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end" data-label="In transit"><span class="badge badge-soft badge-soft-info"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end" data-label="Delivered"><span class="badge badge-soft badge-soft-success"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'delivered'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="last30-pane" role="tabpanel" aria-labelledby="last30-tab">
              <div class="table-responsive dash-table-x">
                <table class="table table-sm table-dash table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Branch</th>
                      <th class="text-end">Pending</th>
                      <th class="text-end">In Transit</th>
                      <th class="text-end">Delivered</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (($branchesAll ?? []) as $b): $bid = (int) $b['id']; ?>
                    <tr>
                      <td class="fw-medium" data-label="Branch"><?php echo htmlspecialchars($b['name']); ?></td>
                      <td class="text-end" data-label="Pending"><span class="badge badge-soft badge-soft-warning"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end" data-label="In transit"><span class="badge badge-soft badge-soft-info"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end" data-label="Delivered"><span class="badge badge-soft badge-soft-success"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'delivered'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
