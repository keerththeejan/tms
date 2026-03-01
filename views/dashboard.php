<style>
  .dashboard-page {
    --dash-radius: 14px;
    --dash-border: rgba(17,24,39,.10);
    --dash-shadow: 0 1px 2px rgba(16,24,40,.06);
    --dash-shadow-hover: 0 6px 18px rgba(16,24,40,.10);
  }
  .dashboard-page .section-title {
    font-size: .92rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 .6rem;
  }
  .dashboard-page .section-subtitle {
    font-size: .82rem;
    color: #6b7280;
    margin: 0 0 .75rem;
  }
  .dashboard-page .kpi-card {
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    box-shadow: var(--dash-shadow);
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    background: #fff;
    padding: 12px 14px;
    height: 100%;
  }
  .dashboard-page .kpi-card:hover { transform: translateY(-1px); box-shadow: var(--dash-shadow-hover); }
  .dashboard-page .kpi-label { font-size: .78rem; color: #6b7280; font-weight: 600; }
  .dashboard-page .kpi-value { font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em; color: #111827; }
  .dashboard-page .kpi-icon {
    width: 34px; height: 34px;
    border-radius: 12px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(13,110,253,.10);
    color: #0d6efd;
    flex: 0 0 auto;
  }

  .dashboard-page .quick-actions .action-card {
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    box-shadow: var(--dash-shadow);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
  }
  .dashboard-page .quick-actions .action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--dash-shadow-hover);
    border-color: rgba(13,110,253,.25);
    color: inherit;
  }
  .dashboard-page .quick-actions .action-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
  }
  .dashboard-page .quick-actions .action-card .action-title { font-weight: 700; font-size: .92rem; margin-bottom: 0.1rem; }
  .dashboard-page .quick-actions .action-card .action-desc { font-size: 0.78rem; color: #6b7280; }
  .dashboard-page .card-dash { border: 1px solid var(--dash-border); border-radius: var(--dash-radius); box-shadow: var(--dash-shadow); background:#fff; }
  .dashboard-page .card-dash .card-header-dash {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    padding: 0.7rem .9rem;
    border-bottom: 1px solid var(--dash-border);
    background: #fbfcfe;
    border-radius: var(--dash-radius) var(--dash-radius) 0 0;
  }
  .dashboard-page .filters-card {
    background: #fff;
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    padding: .85rem .95rem;
    margin-bottom: 1rem;
    box-shadow: var(--dash-shadow);
  }
  .dashboard-page .filters-card .form-label { font-size: 0.8rem; font-weight: 500; color: var(--bs-secondary-color); }
  .dashboard-page .stat-card { border-radius: var(--dash-radius); overflow: hidden; }
  .dashboard-page .stat-card .stat-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -.01em; }
  .dashboard-page .table-dash thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    color: var(--bs-secondary-color);
    border-bottom-width: 1px;
    padding: 0.6rem 0.75rem;
  }
  .dashboard-page .table-dash tbody td { padding: 0.6rem 0.75rem; vertical-align: middle; }
  .dashboard-page .table-dash.table-hover tbody tr:hover { background-color: var(--bs-tertiary-bg); }

  .dashboard-page .badge-soft { font-weight: 700; border: 1px solid rgba(17,24,39,.10); }
  .dashboard-page .badge-soft-success { background: rgba(25,135,84,.12); color: #146c43; }
  .dashboard-page .badge-soft-warning { background: rgba(255,193,7,.16); color: #8a6d00; }
  .dashboard-page .badge-soft-info { background: rgba(13,202,240,.16); color: #055160; }
  .dashboard-page .badge-soft-secondary { background: rgba(108,117,125,.14); color: #495057; }
  .dashboard-page .nav-tabs-dash .nav-link {
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    color: var(--bs-secondary-color);
  }
  .dashboard-page .nav-tabs-dash .nav-link:hover { color: var(--bs-primary); background: var(--bs-tertiary-bg); }
  .dashboard-page .nav-tabs-dash .nav-link.active { color: var(--bs-primary); background: var(--bs-primary-bg-subtle); }
  @media (max-width: 576px) {
    .dashboard-page .page-header h1 { font-size: 1.4rem; }
    .dashboard-page .quick-actions .action-icon { width: 2.5rem; height: 2.5rem; font-size: 1.25rem; }
    .dashboard-page .quick-actions .action-card .action-title { font-size: 0.95rem; }
    .dashboard-page .quick-actions .action-card .action-desc { font-size: 0.75rem; }
    .dashboard-page .nav-tabs-dash { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
    .dashboard-page .nav-tabs-dash .nav-link { white-space: nowrap; }
  }
</style>
<div class="dashboard-page">
  <?php
    $kpiPending = (int)($pendingParcels ?? 0);
    $kpiTodayParcels = is_array($todayParcels ?? null) ? count($todayParcels) : 0;
    $kpiCollections = (float)($collectionsToday ?? 0);
    $kpiExpenses = (float)($expensesToday ?? 0);
  ?>

  <section class="mb-3">
    <p class="section-title">Overview</p>
    <p class="section-subtitle">Key operational metrics for the selected branch and date range.</p>
    <div class="row g-3">
      <div class="col-12 col-md-6 col-xl-3">
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
      <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Today's Parcels</div>
              <div class="kpi-value"><?php echo $kpiTodayParcels; ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(32,201,151,.12); color:#198754;"><i class="bi bi-box-seam"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&from=' . urlencode($today ?? date('Y-m-d')) . '&to=' . urlencode($today ?? date('Y-m-d'))); ?>">View list</a></div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Today’s Collections</div>
              <div class="kpi-value">Rs. <?php echo number_format($kpiCollections, 2); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(13,110,253,.10); color:#0d6efd;"><i class="bi bi-cash-stack"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">Go to payments</a></div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
              <div class="kpi-label">Today’s Expenses</div>
              <div class="kpi-value">Rs. <?php echo number_format($kpiExpenses, 2); ?></div>
            </div>
            <div class="kpi-icon" aria-hidden="true" style="background: rgba(255,193,7,.16); color:#8a6d00;"><i class="bi bi-wallet2"></i></div>
          </div>
          <div class="mt-2"><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">Go to expenses</a></div>
        </div>
      </div>
    </div>
  </section>

  <section class="quick-actions mb-3">
    <p class="section-title">Quick Actions</p>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>">
          <div class="action-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-search"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Search Customer</div>
            <div class="action-desc">Find by phone</div>
          </div>
        </a>
      </div>
      <?php if (Auth::canCreateParcels()): ?>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>">
          <div class="action-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-box-seam"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Add Parcel</div>
            <div class="action-desc">Record parcel</div>
          </div>
        </a>
      </div>
      <?php endif; ?>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=parcels&status=pending'); ?>">
          <div class="action-icon bg-secondary bg-opacity-10 text-secondary">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Pending Parcels</div>
            <div class="action-desc">Count: <strong><?php echo (int)($pendingParcels ?? 0); ?></strong></div>
          </div>
        </a>
      </div>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>">
          <div class="action-icon bg-warning bg-opacity-10 text-warning">
            <i class="bi bi-receipt"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Delivery Note</div>
            <div class="action-desc">Print / group</div>
          </div>
        </a>
      </div>
      <?php if (Auth::canCollectPayments()): ?>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">
          <div class="action-icon bg-danger bg-opacity-10 text-danger">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Payments</div>
            <div class="action-desc">Due: <strong>Rs. <?php echo number_format((float)($totalDue ?? 0), 2); ?></strong></div>
          </div>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="mb-4">
    <p class="section-title">Delivery</p>
    <p class="section-subtitle">Register a customer first, then plan routes and assign vehicles. When adding a delivery route you can pick the customer and use their address.</p>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>">
          <div class="action-icon bg-info bg-opacity-10 text-info">
            <i class="bi bi-person-plus"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Register Customer</div>
            <div class="action-desc">Add customer &amp; address</div>
          </div>
        </a>
      </div>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>">
          <div class="action-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-signpost-split"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Plan Delivery Route</div>
            <div class="action-desc">Pick customer, use address</div>
          </div>
        </a>
      </div>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>">
          <div class="action-icon bg-secondary bg-opacity-10 text-secondary">
            <i class="bi bi-truck-front"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Vehicle Routes</div>
            <div class="action-desc">By vehicle &amp; date</div>
          </div>
        </a>
      </div>
      <div class="col">
        <a class="action-card card card-body d-flex flex-row align-items-center gap-3" href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=route_planning'); ?>">
          <div class="action-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-geo-alt"></i>
          </div>
          <div class="min-w-0">
            <div class="action-title">Parcels by Route</div>
            <div class="action-desc">Today’s route planning</div>
          </div>
        </a>
      </div>
    </div>
  </section>

  <div class="filters-card">
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
        <select class="form-select form-select-sm" name="fb">
          <option value="0">All</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($fb ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">To Branch</label>
        <select class="form-select form-select-sm" name="tb">
          <option value="0">All</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($tb ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Customer</label>
        <select class="form-select form-select-sm" name="cust">
          <option value="0">All</option>
          <?php foreach (($customersAll ?? []) as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($cust ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name'].' ('.$c['phone'].')'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-6 d-flex flex-wrap gap-2 justify-content-md-end">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Apply</button>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"><i class="bi bi-x-circle me-1"></i> Reset</a>
      </div>
    </form>
  </div>
<?php if (isset($pendingParcels, $totalDue, $todayParcels)): ?>
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card card-dash w-100">
        <div class="card-header-dash d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span>Today's Parcels</span>
          <small class="text-muted fw-normal"><?php echo htmlspecialchars($today ?? ''); ?></small>
        </div>
        <div class="card-body">
          <?php if (empty($todayParcels)): ?>
            <p class="text-muted small mb-0">No parcels today.</p>
          <?php else: ?>
          <div class="table-responsive" style="max-height:220px; overflow:auto;-webkit-overflow-scrolling:touch;">
            <table class="table table-sm table-dash table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Customer</th>
                  <th>Tracking</th>
                  <th>Vehicle</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($todayParcels as $p): ?>
                <tr>
                  <td><?php echo (int)$p['id']; ?></td>
                  <td><?php echo htmlspecialchars($p['customer_name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($p['tracking_number'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($p['vehicle_no'] ?? ''); ?></td>
                  <td>
                    <?php
                      $st = (string)($p['status'] ?? '');
                      $stClass = ($st === 'delivered') ? 'badge-soft-success' : (($st === 'in_transit') ? 'badge-soft-info' : 'badge-soft-warning');
                    ?>
                    <span class="badge badge-soft <?php echo $stClass; ?>"><?php echo htmlspecialchars($st); ?></span>
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
  <?php if (!empty($isMain)): ?>
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card card-dash h-100">
        <div class="card-header-dash">All Branches (Today)</div>
        <div class="card-body p-0">
          <div class="table-responsive overflow-auto">
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
                  <td class="fw-medium"><?php echo htmlspecialchars($b['name']); ?></td>
                  <td class="text-end">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&status=pending&to_branch_id=' . $bid); ?>"><?php echo (int)($pendingByBranch[$bid] ?? 0); ?></a>
                  </td>
                  <td class="text-end">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=payments&branch_id=' . $bid); ?>">Rs. <?php echo number_format((float)($dueByBranch[$bid] ?? 0), 2); ?></a>
                  </td>
                  <td class="text-end">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&to_branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>"><?php echo (int)($todayParcelsByBranch[$bid] ?? 0); ?></a>
                  </td>
                  <td class="text-end">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=payments&branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>">Rs. <?php echo number_format((float)($collectionsTodayByBranch[$bid] ?? 0), 2); ?></a>
                  </td>
                  <td class="text-end">
                    <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=expenses&branch_id=' . $bid . '&from=' . urlencode($today) . '&to=' . urlencode($today)); ?>">Rs. <?php echo number_format((float)($expensesTodayByBranch[$bid] ?? 0), 2); ?></a>
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
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card card-dash stat-card stat-collections h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-muted small">Today's Collections</span>
            <i class="bi bi-cash-stack text-success opacity-75"></i>
          </div>
          <div class="stat-value text-success">Rs. <?php echo number_format((float)($collectionsToday ?? 0), 2); ?></div>
          <a class="small text-decoration-none mt-2 d-inline-block" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">Go to Payments <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card card-dash stat-card stat-expenses h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-muted small">Today's Expenses</span>
            <i class="bi bi-wallet2 text-warning opacity-75"></i>
          </div>
          <div class="stat-value text-warning">Rs. <?php echo number_format((float)($expensesToday ?? 0), 2); ?></div>
          <a class="small text-decoration-none mt-2 d-inline-block" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">Go to Expenses <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4 d-flex align-items-stretch">
      <div class="card card-dash w-100">
        <div class="card-header-dash d-flex justify-content-between align-items-center">
          <span>Recent Payments</span>
          <a class="small text-decoration-none fw-normal" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">View all</a>
        </div>
        <div class="card-body">
          <?php if (empty($recentPayments)): ?>
            <p class="text-muted small mb-0">No recent payments.</p>
          <?php else: ?>
          <div class="table-responsive overflow-auto" style="max-height:220px;-webkit-overflow-scrolling:touch;">
            <table class="table table-sm table-dash table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th class="d-none d-sm-table-cell">Paid At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentPayments as $p): ?>
                <tr>
                  <td><?php echo (int)$p['id']; ?></td>
                  <td><?php echo htmlspecialchars(($p['customer_name'] ?? '').' ('.($p['customer_phone'] ?? '').')'); ?></td>
                  <td>Rs. <?php echo number_format((float)$p['amount'], 2); ?></td>
                  <td class="d-none d-sm-table-cell"><small class="text-muted"><?php echo htmlspecialchars($p['paid_at']); ?></small></td>
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
  // Helper to safely fetch status counts
  $getCnt = function(array $arr, int $bid, string $status): int {
    return isset($arr[$bid][$status]) ? (int)$arr[$bid][$status] : 0;
  };
?>

  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card card-dash">
        <div class="card-header-dash">Branch Status Summary</div>
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
              <div class="table-responsive">
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
                      <td class="fw-medium"><?php echo htmlspecialchars($b['name']); ?></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-warning"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-info"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-success"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'delivered'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="yesterday-pane" role="tabpanel">
              <div class="table-responsive">
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
                      <td class="fw-medium"><?php echo htmlspecialchars($b['name']); ?></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-warning"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-info"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-success"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'delivered'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="last30-pane" role="tabpanel">
              <div class="table-responsive">
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
                      <td class="fw-medium"><?php echo htmlspecialchars($b['name']); ?></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-warning"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-info"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end"><span class="badge badge-soft badge-soft-success"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'delivered'); ?></span></td>
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
 
