<style>
  /* Dashboard — responsive shell + SaaS polish (frontend only) */
  main.content-wrapper > .container-fluid:has(.dashboard-page) {
    max-width: none;
    width: 100%;
    margin-left: 0;
    margin-right: 0;
    padding-left: 12px;
    padding-right: 12px;
  }
  .dashboard-page {
    --dash-space-1: 8px;
    --dash-space-2: 12px;
    --dash-space-3: 16px;
    --dash-space-4: 24px;
    --dash-radius: 12px;
    --dash-border: rgba(17, 24, 39, 0.1);
    --dash-shadow: 0 1px 3px rgba(16, 24, 40, 0.07), 0 1px 2px rgba(16, 24, 40, 0.04);
    --dash-shadow-hover: 0 8px 24px rgba(16, 24, 40, 0.1);
    --dash-table-min: 720px;
    min-width: 0;
    max-width: 100%;
  }
  .dashboard-page .section-title {
    font-size: clamp(1.05rem, 0.95rem + 0.35vw, 1.2rem);
    font-weight: 700;
    color: #111827;
    margin: 0 0 var(--dash-space-2);
    letter-spacing: -0.02em;
  }
  .dashboard-page .section-subtitle {
    font-size: clamp(0.8125rem, 0.78rem + 0.2vw, 0.875rem);
    color: #6b7280;
    margin: 0 0 var(--dash-space-3);
    line-height: 1.45;
  }
  .dashboard-page .kpi-card {
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    box-shadow: var(--dash-shadow);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    background: #fff;
    padding: var(--dash-space-3);
    height: 100%;
  }
  .dashboard-page .kpi-card:hover {
    transform: translateY(-1px);
    box-shadow: var(--dash-shadow-hover);
  }
  .dashboard-page .kpi-label {
    font-size: clamp(0.75rem, 0.72rem + 0.12vw, 0.8125rem);
    color: #6b7280;
    font-weight: 600;
  }
  .dashboard-page .kpi-value {
    font-size: clamp(1.25rem, 1.1rem + 0.5vw, 1.45rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #111827;
  }
  .dashboard-page .kpi-icon {
    width: clamp(32px, 28px + 1.2vw, 36px);
    height: clamp(32px, 28px + 1.2vw, 36px);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    flex: 0 0 auto;
  }

  .dashboard-page .quick-actions .action-card,
  .dashboard-page section:not(.quick-actions) .action-card {
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    box-shadow: var(--dash-shadow);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
    padding: var(--dash-space-3) !important;
  }
  .dashboard-page .action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--dash-shadow-hover);
    border-color: rgba(13, 110, 253, 0.22);
    color: inherit;
  }
  .dashboard-page .action-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
  }
  .dashboard-page .action-card .action-title {
    font-weight: 700;
    font-size: clamp(0.875rem, 0.82rem + 0.15vw, 0.9375rem);
    margin-bottom: 0.15rem;
  }
  .dashboard-page .action-card .action-desc {
    font-size: clamp(0.75rem, 0.72rem + 0.1vw, 0.8125rem);
    color: #6b7280;
  }
  .dashboard-page .card-dash {
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    box-shadow: var(--dash-shadow);
    background: #fff;
    min-width: 0;
  }
  .dashboard-page .card-dash .card-header-dash {
    font-weight: 600;
    font-size: clamp(0.875rem, 0.82rem + 0.12vw, 0.9375rem);
    color: var(--bs-body-color);
    padding: var(--dash-space-2) var(--dash-space-3);
    border-bottom: 1px solid var(--dash-border);
    background: linear-gradient(180deg, #fbfcfe 0%, #f8fafc 100%);
    border-radius: var(--dash-radius) var(--dash-radius) 0 0;
  }
  .dashboard-page .card-dash .card-body {
    padding: var(--dash-space-3);
  }
  .dashboard-page .filters-card {
    background: #fff;
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    padding: var(--dash-space-3);
    margin-bottom: var(--dash-space-3);
    box-shadow: var(--dash-shadow);
  }
  .dashboard-page .filters-card .form-label {
    font-size: clamp(0.75rem, 0.72rem + 0.08vw, 0.8125rem);
    font-weight: 600;
    color: var(--bs-secondary-color);
    margin-bottom: var(--dash-space-1);
  }
  .dashboard-page .filters-card .form-control,
  .dashboard-page .filters-card .form-select {
    width: 100%;
    max-width: 100%;
    border-radius: 10px;
    font-size: clamp(0.875rem, 0.82rem + 0.1vw, 0.9375rem);
  }
  .dashboard-page .filters-card .btn {
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.18);
    transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s ease;
  }
  .dashboard-page .filters-card .btn-outline-secondary {
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
  }
  .dashboard-page .filters-card .btn:active:not(:disabled) {
    transform: translateY(1px);
  }
  .dashboard-page .stat-card {
    border-radius: var(--dash-radius);
    overflow: hidden;
  }
  .dashboard-page .stat-card .stat-value {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.01em;
  }
  .dashboard-page .table-dash thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    color: var(--bs-secondary-color);
    border-bottom-width: 1px;
    padding: 0.55rem 0.65rem;
  }
  .dashboard-page .table-dash tbody td {
    padding: 0.55rem 0.65rem;
    vertical-align: middle;
    font-size: clamp(0.8125rem, 0.78rem + 0.1vw, 0.875rem);
  }
  .dashboard-page .table-dash.table-hover tbody tr:hover {
    background-color: var(--bs-tertiary-bg);
  }

  /* Horizontal scroll regions — desktop unchanged; mobile scrolls inside wrapper only */
  .dashboard-page .dash-table-x {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    max-width: 100%;
  }
  .dashboard-page .dash-table-scroll-y {
    max-height: 220px;
    overflow-y: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .dashboard-page .dash-table-scroll-y.table-responsive {
    display: block;
  }

  .dashboard-page .badge-soft {
    font-weight: 700;
    border: 1px solid rgba(17, 24, 39, 0.1);
  }
  .dashboard-page .badge-soft-success {
    background: rgba(25, 135, 84, 0.12);
    color: #146c43;
  }
  .dashboard-page .badge-soft-warning {
    background: rgba(255, 193, 7, 0.16);
    color: #8a6d00;
  }
  .dashboard-page .badge-soft-info {
    background: rgba(13, 202, 240, 0.16);
    color: #055160;
  }
  .dashboard-page .badge-soft-secondary {
    background: rgba(108, 117, 125, 0.14);
    color: #495057;
  }
  .dashboard-page .badge-soft-danger {
    background: rgba(220, 53, 69, 0.14);
    color: #b02a37;
  }
  .dashboard-page .nav-tabs-dash {
    flex-wrap: nowrap;
    gap: var(--dash-space-1);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
    scrollbar-width: thin;
  }
  .dashboard-page .nav-tabs-dash .nav-link {
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    font-size: clamp(0.8125rem, 0.78rem + 0.1vw, 0.9rem);
    padding: 0.45rem 0.85rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    flex-shrink: 0;
  }
  .dashboard-page .nav-tabs-dash .nav-link:hover {
    color: var(--bs-primary);
    background: var(--bs-tertiary-bg);
  }
  .dashboard-page .nav-tabs-dash .nav-link.active {
    color: var(--bs-primary);
    background: var(--bs-primary-bg-subtle);
  }

  .dashboard-page .finance-card,
  .dashboard-page .chart-card {
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    box-shadow: var(--dash-shadow);
    background: #fff;
    min-width: 0;
    height: 100%;
  }

  .dashboard-page .chart-wrap {
    position: relative;
    min-height: 320px;
  }

  .dashboard-page .mini-metric {
    border-radius: 12px;
    padding: 0.9rem 1rem;
    background: linear-gradient(180deg, #fbfcfe 0%, #f8fafc 100%);
    border: 1px solid rgba(15, 23, 42, 0.08);
  }

  .dashboard-page .mini-metric .mini-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--bs-secondary-color);
    font-weight: 700;
  }

  .dashboard-page .mini-metric .mini-value {
    font-size: clamp(1.05rem, 0.95rem + 0.28vw, 1.25rem);
    font-weight: 800;
    letter-spacing: -0.02em;
  }

  .dashboard-page .mini-metric .mini-sub {
    font-size: 0.75rem;
    color: var(--bs-secondary-color);
  }

  /* Breakpoints: 480 / 768 / 1024 / 1280 */
  @media (max-width: 1279.98px) {
    .dashboard-page {
      --dash-table-min: 680px;
    }
  }
  @media (max-width: 1023.98px) {
    main.content-wrapper > .container-fluid:has(.dashboard-page) {
      padding-left: max(12px, env(safe-area-inset-left, 0px));
      padding-right: max(12px, env(safe-area-inset-right, 0px));
    }
  }
  @media (min-width: 768px) {
    main.content-wrapper > .container-fluid:has(.dashboard-page) {
      padding-left: 16px;
      padding-right: 16px;
    }
  }
  @media (max-width: 767.98px) {
    main.content-wrapper > .container-fluid:has(.dashboard-page) {
      max-width: none;
      margin-top: 0.5rem !important;
      padding-left: max(10px, env(safe-area-inset-left, 0px));
      padding-right: max(10px, env(safe-area-inset-right, 0px));
    }
    .dashboard-page section {
      margin-bottom: var(--dash-space-3) !important;
    }
    .dashboard-page .mb-4 {
      margin-bottom: var(--dash-space-4) !important;
    }
    .dashboard-page .filters-card .form-control-sm,
    .dashboard-page .filters-card .form-select-sm {
      min-height: 44px;
      padding-top: 0.5rem;
      padding-bottom: 0.5rem;
    }
    .dashboard-page .filters-card .btn-sm {
      min-height: 44px;
      padding-left: 1rem;
      padding-right: 1rem;
    }
    .dashboard-page .filters-card .dash-filter-actions {
      flex-direction: column;
      align-items: stretch !important;
      justify-content: flex-start !important;
    }
    .dashboard-page .filters-card .dash-filter-actions .btn {
      width: 100%;
    }
    /* Tables: horizontal scroll, preserve desktop table layout (no card stacking) */
    .dashboard-page .dash-table-x .table-dash {
      width: max-content;
      min-width: var(--dash-table-min);
      margin-bottom: 0;
    }
    .dashboard-page .dash-table-x .table-dash th,
    .dashboard-page .dash-table-x .table-dash td {
      white-space: nowrap;
    }
  }
  @media (max-width: 479.98px) {
    .dashboard-page {
      --dash-space-3: 12px;
      --dash-radius: 10px;
    }
    .dashboard-page .row.g-3 {
      --bs-gutter-y: 0.65rem;
      --bs-gutter-x: 0.65rem;
    }
  }
  @media (min-width: 768px) {
    .dashboard-page .dash-table-x .table-dash {
      width: 100%;
      min-width: 0;
      max-width: 100%;
    }
    .dashboard-page .dash-table-x .table-dash th,
    .dashboard-page .dash-table-x .table-dash td {
      white-space: normal;
    }
  }
</style>
<div class="dashboard-page">
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
    <p class="section-title">Overview</p>
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
