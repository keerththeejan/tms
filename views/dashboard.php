<style>
  .dashboard-page {
    --dash-card-radius: 0.75rem;
    --dash-shadow: 0 1px 3px rgba(0,0,0,.08);
    --dash-shadow-hover: 0 4px 12px rgba(0,0,0,.12);
  }
  .dashboard-page .page-header {
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--bs-border-color-translucent);
  }
  .dashboard-page .page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--bs-body-color);
    margin: 0;
  }
  .dashboard-page .page-header .lead {
    font-size: 0.95rem;
    color: var(--bs-secondary-color);
    margin: 0.25rem 0 0;
  }
  .dashboard-page .quick-actions .action-card {
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: var(--dash-card-radius);
    box-shadow: var(--dash-shadow);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
  }
  .dashboard-page .quick-actions .action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--dash-shadow-hover);
    border-color: var(--bs-primary-border-subtle);
    color: inherit;
  }
  .dashboard-page .quick-actions .action-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
  }
  .dashboard-page .quick-actions .action-card .action-title { font-weight: 600; font-size: 1rem; margin-bottom: 0.15rem; }
  .dashboard-page .quick-actions .action-card .action-desc { font-size: 0.8rem; color: var(--bs-secondary-color); }
  .dashboard-page .card-dash {
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: var(--dash-card-radius);
    box-shadow: var(--dash-shadow);
  }
  .dashboard-page .card-dash .card-header-dash {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--bs-body-color);
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--bs-border-color-translucent);
    background: var(--bs-tertiary-bg);
    border-radius: var(--dash-card-radius) var(--dash-card-radius) 0 0;
  }
  .dashboard-page .filters-card {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: var(--dash-card-radius);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
  }
  .dashboard-page .filters-card .form-label { font-size: 0.8rem; font-weight: 500; color: var(--bs-secondary-color); }
  .dashboard-page .stat-card {
    border-left: 4px solid;
    border-radius: var(--dash-card-radius);
    overflow: hidden;
  }
  .dashboard-page .stat-card.stat-collections { border-left-color: var(--bs-success); }
  .dashboard-page .stat-card.stat-expenses { border-left-color: var(--bs-warning); }
  .dashboard-page .stat-card .stat-value { font-size: clamp(1.25rem, 4vw, 1.75rem); font-weight: 700; }
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
  <header class="page-header">
    <h1>Dashboard</h1>
    <p class="lead">Overview and quick actions</p>
  </header>

  <section class="quick-actions mb-4">
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
                  <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($p['status']); ?></span></td>
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
                      <td class="text-end"><span class="badge text-bg-secondary"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end"><span class="badge text-bg-info"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end"><span class="badge text-bg-success"><?php echo $getCnt($statusStats['today'] ?? [], $bid, 'delivered'); ?></span></td>
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
                      <td class="text-end"><span class="badge text-bg-secondary"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end"><span class="badge text-bg-info"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end"><span class="badge text-bg-success"><?php echo $getCnt($statusStats['yesterday'] ?? [], $bid, 'delivered'); ?></span></td>
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
                      <td class="text-end"><span class="badge text-bg-secondary"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'pending'); ?></span></td>
                      <td class="text-end"><span class="badge text-bg-info"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'in_transit'); ?></span></td>
                      <td class="text-end"><span class="badge text-bg-success"><?php echo $getCnt($statusStats['last30'] ?? [], $bid, 'delivered'); ?></span></td>
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
 
