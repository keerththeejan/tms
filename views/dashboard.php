<h2 class="mb-3">Dashboard</h2>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 quick-actions mb-3">
  <div class="col">
    <a class="text-decoration-none text-dark" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <i class="bi bi-search fs-1 me-3 text-primary"></i>
          <div>
            <div class="h6 mb-1">Search Customer</div>
            <small class="text-muted">Find by phone number</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php if (Auth::canCreateParcels()): ?>
  <div class="col">
    <a class="text-decoration-none text-dark" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <i class="bi bi-box-seam fs-1 me-3 text-success"></i>
          <div>
            <div class="h6 mb-1">Add Parcel</div>
            <small class="text-muted">Record incoming/outgoing</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endif; ?>
  <div class="col">
    <a class="text-decoration-none text-dark" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <i class="bi bi-receipt fs-1 me-3 text-warning"></i>
          <div>
            <div class="h6 mb-1">Print Delivery Note</div>
            <small class="text-muted">Group daily parcels</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php if (Auth::canCollectPayments()): ?>
  <div class="col">
    <a class="text-decoration-none text-dark" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <i class="bi bi-cash-coin fs-1 me-3 text-danger"></i>
          <div>
            <div class="h6 mb-1">Collect Due</div>
            <small class="text-muted">Record payments</small>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endif; ?>
</div>

<form method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>" class="row g-2 align-items-end mb-3">
  <input type="hidden" name="page" value="dashboard">
  <div class="col-6 col-md-3">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="df" value="<?php echo htmlspecialchars($df ?? ($today ?? '')); ?>">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="dt" value="<?php echo htmlspecialchars($dt ?? ($today ?? '')); ?>">
  </div>
  <div class="col-12 col-md-3">
    <label class="form-label">From Branch</label>
    <select class="form-select" name="fb">
      <option value="0">All</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($fb ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-3">
    <label class="form-label">To Branch</label>
    <select class="form-select" name="tb">
      <option value="0">All</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($tb ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6">
    <label class="form-label">Customer</label>
    <select class="form-select" name="cust">
      <option value="0">All</option>
      <?php foreach (($customersAll ?? []) as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($cust ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name'].' ('.$c['phone'].')'); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-6 text-md-end">
    <button class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Apply</button>
    <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"><i class="bi bi-x-circle"></i> Reset</a>
  </div>
</form>
<?php if (isset($pendingParcels, $totalDue, $todayParcels)): ?>
  <div class="row g-3 mb-3">
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted">Pending Parcels (to your branch)</div>
          <div class="display-6 fw-bold"><?php echo (int)$pendingParcels; ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted">Total Payment Due (Your Branch)</div>
          <div class="display-6 fw-bold">Rs. <?php echo number_format((float)$totalDue, 2); ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-4 d-flex align-items-stretch">
      <div class="card border-0 shadow-sm w-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted">Today's Parcels</div>
            <small class="text-muted"><?php echo htmlspecialchars($today ?? ''); ?></small>
          </div>
          <?php if (empty($todayParcels)): ?>
            <div class="text-muted">No parcels today.</div>
          <?php else: ?>
          <div class="table-responsive" style="max-height:200px; overflow:auto;">
            <table class="table table-sm table-striped align-middle mb-0">
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
  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">All Branches Status (Today)</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
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
                  <td><?php echo htmlspecialchars($b['name']); ?></td>
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
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted">Today's Collections</div>
          <div class="display-6 fw-bold">Rs. <?php echo number_format((float)($collectionsToday ?? 0), 2); ?></div>
          <a class="small" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">Go to Payments</a>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted">Today's Expenses</div>
          <div class="display-6 fw-bold">Rs. <?php echo number_format((float)($expensesToday ?? 0), 2); ?></div>
          <a class="small" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">Go to Expenses</a>
        </div>
      </div>
    </div>
    <div class="col-sm-4 d-flex align-items-stretch">
      <div class="card border-0 shadow-sm w-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted">Recent Payments</div>
            <a class="small" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">View all</a>
          </div>
          <?php if (empty($recentPayments)): ?>
            <div class="text-muted">No recent payments.</div>
          <?php else: ?>
          <div class="table-responsive" style="max-height:200px; overflow:auto;">
            <table class="table table-sm table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th>Paid At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentPayments as $p): ?>
                <tr>
                  <td><?php echo (int)$p['id']; ?></td>
                  <td><?php echo htmlspecialchars(($p['customer_name'] ?? '').' ('.($p['customer_phone'] ?? '').')'); ?></td>
                  <td>Rs. <?php echo number_format((float)$p['amount'], 2); ?></td>
                  <td><small class="text-muted"><?php echo htmlspecialchars($p['paid_at']); ?></small></td>
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

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Branch Status Summary</h5>
        <ul class="nav nav-tabs" id="statusTabs" role="tablist">
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
        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="today-pane" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle mb-0">
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
                    <td><?php echo htmlspecialchars($b['name']); ?></td>
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
              <table class="table table-sm table-striped align-middle mb-0">
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
                    <td><?php echo htmlspecialchars($b['name']); ?></td>
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
              <table class="table table-sm table-striped align-middle mb-0">
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
                    <td><?php echo htmlspecialchars($b['name']); ?></td>
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
 
