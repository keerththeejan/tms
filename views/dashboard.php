<?php
  $dashCssPath = dirname(__DIR__) . '/public/assets/css/dashboard-module.css';
  $dashCssVer = is_file($dashCssPath) ? (string) filemtime($dashCssPath) : '1';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/dashboard-module.css?v=' . rawurlencode($dashCssVer)); ?>">
<div id="mainDashboardApp" class="dashboard-page erp-dashboard">
  <?php
    $kpiPending = (int)($pendingParcels ?? 0);
    $kpiTodayParcels = is_array($todayParcels ?? null) ? count($todayParcels) : 0;
    $kpiCollections = (float)($collectionsToday ?? 0);
    $kpiExpenses = (float)($expensesToday ?? 0);
    $df = $df ?? date('Y-m-d');
    $dt = $dt ?? date('Y-m-d');
    $today = $today ?? date('Y-m-d');
    $scopeAllBranches = !empty($scopeAllBranches);
    $isSingleDay = isset($isSingleDay) ? (bool)$isSingleDay : ($df === $dt);
    $isTodayRange = isset($isTodayRange) ? (bool)$isTodayRange : ($isSingleDay && $df === $today);
    $rangeStr = htmlspecialchars($df === $dt ? $df : ($df . ' → ' . $dt));
    $kpiParcelsTitle = ($isSingleDay && $df === $today && $dt === $today) ? "Today's Parcels" : 'Parcels (filtered)';
    $kpiCollTitle = !$isSingleDay
      ? 'Collections (' . htmlspecialchars($df) . '–' . htmlspecialchars($dt) . ')'
      : ($isTodayRange ? "Today's Collections" : 'Collections (' . htmlspecialchars($df) . ')');
    $kpiExpTitle = !$isSingleDay
      ? 'Expenses (' . htmlspecialchars($df) . '–' . htmlspecialchars($dt) . ')'
      : ($isTodayRange ? "Today's Expenses" : 'Expenses (' . htmlspecialchars($df) . ')');
  ?>

  <section class="erp-topbar card-dash mb-3">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="erp-topbar-icon"><i class="bi bi-speedometer2"></i></div>
        <div>
          <h1 class="erp-title mb-0">Transport Management System</h1>
          <p class="erp-subtitle mb-0">Welcome back! Here's your operational overview.</p>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge text-bg-light border p-2"><i class="bi bi-calendar3 me-1"></i><?php echo htmlspecialchars(date('d M Y')); ?></span>
        <span id="dashLiveClock" class="badge text-bg-light border p-2"><i class="bi bi-clock me-1"></i>--:--:--</span>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search me-1"></i>Global Search</button>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-bell me-1"></i>Notifications</button>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots me-1"></i>Messages</button>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-moon-stars me-1"></i>Dark Mode</button>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrows-fullscreen me-1"></i>Fullscreen</button>
      </div>
    </div>
  </section>

  <div class="filters-card mb-3">
    <form method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>" class="row g-3 align-items-end">
      <input type="hidden" name="page" value="dashboard">
      <div class="col-6 col-md-3">
        <label class="form-label">From</label>
        <input type="date" class="form-control form-control-sm" name="df" value="<?php echo htmlspecialchars($df ?? ($today ?? '')); ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control form-control-sm" name="dt" value="<?php echo htmlspecialchars($dt ?? ($today ?? '')); ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">From Branch</label>
        <select class="form-select form-select-sm" name="fb" data-enhance="false">
          <option value="0">All</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($fb ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">To Branch</label>
        <select class="form-select form-select-sm" name="tb" data-enhance="false" aria-describedby="dashTbHint">
          <option value="0">All</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($tb ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($isMain)): ?>
        <div id="dashTbHint" class="form-text small mt-1 mb-0">“All” = every branch (main hub only).</div>
        <?php endif; ?>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Customer</label>
        <select class="form-select form-select-sm" name="cust" data-enhance="false">
          <option value="0">All</option>
          <?php foreach (($customersAll ?? []) as $c): ?>
            <?php
              $cphone = trim((string)($c['phone'] ?? ''));
              $clabel = ($c['name'] ?? '') . ($cphone !== '' ? ' (' . $cphone . ')' : '');
            ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($cust ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($clabel); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-6 d-flex flex-wrap gap-2 justify-content-md-end dash-filter-actions">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Apply</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"><i class="bi bi-x-circle me-1"></i> Reset</a>
      </div>
    </form>
  </div>

  <section class="mb-3">
    <p class="section-title">Executive KPI Dashboard</p>
    <p class="section-subtitle">Key metrics for <?php echo $scopeAllBranches ? '<strong>all branches</strong>' : 'your branch filters'; ?> · <?php echo $isSingleDay ? 'Date: <strong>'.$rangeStr.'</strong>' : 'Range: <strong>'.$rangeStr.'</strong>'; ?>.</p>
    <div class="row g-3">
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Pending Parcels</div>
              <div class="kpi-value"><?php echo $kpiPending; ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&status=pending'); ?>">View pending</a></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label"><?php echo htmlspecialchars($kpiParcelsTitle); ?></div>
              <div class="kpi-value"><?php echo $kpiTodayParcels; ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(32,201,151,.12); color:#198754;"><i class="bi bi-box-seam"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&from=' . urlencode($df) . '&to=' . urlencode($dt)); ?>">View list</a></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label"><?php echo htmlspecialchars($kpiCollTitle); ?></div>
              <div class="kpi-value"><?php echo Helpers::formatMoney($kpiCollections); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(13,110,253,.10); color:#0d6efd;"><i class="bi bi-cash-stack"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>">Delivery notes</a></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label"><?php echo htmlspecialchars($kpiExpTitle); ?></div>
              <div class="kpi-value"><?php echo Helpers::formatMoney($kpiExpenses); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(255,193,7,.16); color:#8a6d00;"><i class="bi bi-wallet2"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">Go to expenses</a></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Outstanding Due</div>
              <div class="kpi-value"><?php echo Helpers::formatMoney((float)($totalDue ?? 0)); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(255,193,7,.12); color:#b45309;"><i class="bi bi-exclamation-circle"></i></div>
          </div>
          <div class="mt-2"><small class="text-muted">Unsettled delivery notes</small></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Completed Deliveries</div>
              <div class="kpi-value"><?php echo (int)array_sum(array_map(static fn($b)=> (int)($b['delivered'] ?? 0), $statusStats['today'] ?? [])); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(25,135,84,.14); color:#198754;"><i class="bi bi-check2-circle"></i></div>
          </div>
          <div class="mt-2"><small class="text-muted">Today's branch totals</small></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Transfer Vouchers</div>
              <div class="kpi-value"><?php echo (int)($transfersToday ?? 0); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(99,102,241,.14); color:#4338ca;"><i class="bi bi-arrow-left-right"></i></div>
          </div>
          <div class="mt-2"><small class="text-muted"><?php echo Helpers::formatMoney((float)($transfersAmount ?? 0)); ?></small></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Pending Transfers</div>
              <div class="kpi-value"><?php echo (int)($transfersPending ?? 0); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(245,158,11,.16); color:#b45309;"><i class="bi bi-hourglass"></i></div>
          </div>
          <div class="mt-2"><small class="text-muted">Draft transfer vouchers</small></div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-3">
    <div class="row g-3">
      <div class="col-12 col-lg-8">
        <div class="chart-card p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="section-title mb-0">Analytics & Trends</h2>
            <span class="badge text-bg-light border">UI container only</span>
          </div>
          <p class="section-subtitle mb-3">Revenue, expense, parcel volume, cash flow, branch comparison, and delivery performance visual containers.</p>
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
            <div class="timeline-item"><span class="dot"></span><div><strong>Parcel Created</strong><div class="text-muted small">Recent booking captured</div></div></div>
            <div class="timeline-item"><span class="dot"></span><div><strong>Payment Received</strong><div class="text-muted small">Collection posted</div></div></div>
            <div class="timeline-item"><span class="dot"></span><div><strong>Delivery Completed</strong><div class="text-muted small">Route closed successfully</div></div></div>
            <div class="timeline-item"><span class="dot"></span><div><strong>Voucher Posted</strong><div class="text-muted small">Accounting transfer entry</div></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="quick-actions mb-3">
    <h2 class="section-title">Quick Action Center</h2>
    <div class="row g-3">
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>"><div class="action-icon bg-primary-subtle text-primary"><i class="bi bi-box-seam"></i></div><div class="action-title mt-2">New Parcel</div><div class="action-desc">Create shipment</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>"><div class="action-icon bg-info-subtle text-info"><i class="bi bi-person-plus"></i></div><div class="action-title mt-2">New Customer</div><div class="action-desc">Add account</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>"><div class="action-icon bg-warning-subtle text-warning"><i class="bi bi-truck"></i></div><div class="action-title mt-2">New Supplier</div><div class="action-desc">Vendor profile</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>"><div class="action-icon bg-success-subtle text-success"><i class="bi bi-receipt"></i></div><div class="action-title mt-2">Delivery Note</div><div class="action-desc">Billing docs</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>"><div class="action-icon bg-secondary-subtle text-secondary"><i class="bi bi-bar-chart"></i></div><div class="action-title mt-2">Reports</div><div class="action-desc">Insights</div></a></div>
      <div class="col-6 col-md-4 col-lg-3 col-xl-2"><a class="action-card" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=dashboard'); ?>"><div class="action-icon bg-danger-subtle text-danger"><i class="bi bi-calculator"></i></div><div class="action-title mt-2">Accounting</div><div class="action-desc">Finance hub</div></a></div>
    </div>
  </section>

<?php if (isset($pendingParcels, $totalDue, $todayParcels)): ?>
  <?php if (!empty($isMain)): ?>
  <div class="row g-3 mb-3">
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
                <?php foreach (($branchesAll ?? []) as $b): $bid=(int)$b['id']; ?>
                <tr>
                  <td class="fw-medium" data-label="Branch"><?php echo htmlspecialchars($b['name']); ?></td>
                  <td class="text-end" data-label="Pending">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&status=pending&to_branch_id=' . $bid); ?>"><?php echo (int)($pendingByBranch[$bid] ?? 0); ?></a>
                  </td>
                  <td class="text-end" data-label="Due">
                    <span><?php echo Helpers::formatMoney((float)($dueByBranch[$bid] ?? 0)); ?></span>
                  </td>
                  <td class="text-end" data-label="Parcels today">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&to_branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>"><?php echo (int)($todayParcelsByBranch[$bid] ?? 0); ?></a>
                  </td>
                  <td class="text-end" data-label="Collections">
                    <span><?php echo Helpers::formatMoney((float)($collectionsTodayByBranch[$bid] ?? 0)); ?></span>
                  </td>
                  <td class="text-end" data-label="Expenses">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=expenses&branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>"><?php echo Helpers::formatMoney((float)($expensesTodayByBranch[$bid] ?? 0)); ?></a>
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

  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="card card-dash w-100">
        <div class="card-header-dash d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span><?php echo $isSingleDay && $df === $today && $dt === $today ? 'Today\'s Parcels' : 'Parcels'; ?></span>
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
                  <td data-label="#"><?php if (Auth::canCreateParcels()): ?><a class="fw-semibold text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id=' . (int)$p['id']); ?>"><?php echo (int)$p['id']; ?></a><?php else: echo (int)$p['id']; endif; ?></td>
                  <td data-label="Customer"><?php echo htmlspecialchars($p['customer_name'] ?? ''); ?></td>
                  <td data-label="To"><?php echo htmlspecialchars($p['to_branch'] ?? '—'); ?></td>
                  <td data-label="Tracking"><?php echo htmlspecialchars($p['tracking_number'] ?? ''); ?></td>
                  <td data-label="Vehicle"><?php echo htmlspecialchars($p['vehicle_no'] ?? ''); ?></td>
                  <td data-label="Status">
                    <?php
                      $st = (string)($p['status'] ?? '');
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
  $getCnt = function(array $arr, int $bid, string $status): int {
    return (int)(($arr[$bid] ?? [])[$status] ?? 0);
  };
?>

  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card card-dash">
        <div class="card-header-dash">Parcel Status by Branch</div>
        <div class="card-body">
          <ul class="nav nav-tabs nav-tabs-dash gap-1 mb-3" id="statusTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="today-tab" data-bs-toggle="tab" data-bs-target="#today-pane" type="button" role="tab">Today</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="yesterday-tab" data-bs-toggle="tab" data-bs-target="#yesterday-pane" type="button" role="tab">Yesterday</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="last30-tab" data-bs-toggle="tab" data-bs-target="#last30-pane" type="button" role="tab">Last 30 Days</button>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="today-pane" role="tabpanel">
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
                    <?php foreach (($branchesAll ?? []) as $b): $bid=(int)$b['id']; ?>
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
            <div class="tab-pane fade" id="yesterday-pane" role="tabpanel">
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
                    <?php foreach (($branchesAll ?? []) as $b): $bid=(int)$b['id']; ?>
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
            <div class="tab-pane fade" id="last30-pane" role="tabpanel">
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
                    <?php foreach (($branchesAll ?? []) as $b): $bid=(int)$b['id']; ?>
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
