<?php /** @var string $phone */ /** @var string $name */ /** @var array|null $customer */ /** @var array $matches */ ?>
<?php
$gsmCssPath = dirname(__DIR__, 2) . '/public/assets/css/global-search-module.css';
$gsmCssVer = is_file($gsmCssPath) ? (string) filemtime($gsmCssPath) : '1';
$keyword = trim((string)($phone ?: $name));
$parcelCount = count($parcels ?? []);
$noteCount = count($notes ?? []);
$totalResults = ($customer ? 1 : 0) + count($matches ?? []) + $parcelCount + $noteCount;
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/global-search-module.css?v=' . rawurlencode($gsmCssVer)); ?>">

<div id="globalSearchApp" class="gsm-app container-fluid px-0">
  <section class="gsm-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div class="d-flex gap-3 align-items-center">
        <div class="gsm-icon"><i class="bi bi-search"></i></div>
        <div>
          <h1 class="gsm-title">Global Search</h1>
          <p class="gsm-subtitle">Quickly search parcels, customers, suppliers, invoices, employees, transactions, and all system records from one place.</p>
        </div>
      </div>
      <div class="gsm-actions d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i>Advanced Search</button>
        <button type="button" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i>Recent Searches</button>
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
      </div>
    </div>
  </section>

  <section class="gsm-card gsm-search-wrap">
    <div class="gsm-search">
      <form method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
        <input type="hidden" name="page" value="search">
        <div class="gsm-mainbar mb-2">
          <i class="bi bi-search gsm-left"></i>
          <input id="gsmMainSearch" type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Search by parcel number, customer, invoice, phone, vehicle, supplier, employee...">
          <div class="gsm-right">
            <button type="button" class="btn btn-light border" title="Voice search (UI only)"><i class="bi bi-mic"></i></button>
            <button id="gsmClearBtn" type="button" class="btn btn-light border" title="Clear"><i class="bi bi-x-lg"></i></button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right"></i><span class="ms-1">Search</span></button>
          </div>
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label small text-muted">Phone (exact)</label>
            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="Exact phone search">
          </div>
          <div class="col-md-8">
            <div class="small text-muted mt-4">Shortcut: <span class="badge text-bg-light border">Ctrl + K</span> (UI only) | Phone exact match takes priority when both fields are used.</div>
          </div>
        </div>
      </form>
      <div class="gsm-chip-bar">
        <span class="gsm-chip active">All Records</span><span class="gsm-chip">Customers</span><span class="gsm-chip">Suppliers</span><span class="gsm-chip">Parcels</span><span class="gsm-chip">Invoices</span><span class="gsm-chip">Payments</span><span class="gsm-chip">Employees</span><span class="gsm-chip">Vehicles</span><span class="gsm-chip">Routes</span><span class="gsm-chip">Delivery Notes</span>
      </div>
    </div>
    <div class="gsm-summary">
      <span class="gsm-kv"><strong>Total:</strong><span id="gsmTotal"><?php echo (int)$totalResults; ?></span></span>
      <span class="gsm-kv"><strong>Time:</strong><span id="gsmTime">0.00s</span></span>
      <span class="gsm-kv"><strong>Keyword:</strong><span><?php echo htmlspecialchars($keyword !== '' ? $keyword : '—'); ?></span></span>
      <span class="gsm-kv"><strong>Categories:</strong><span>Customers <?php echo (int)count($matches ?? []); ?>, Parcels <?php echo (int)$parcelCount; ?>, Notes <?php echo (int)$noteCount; ?></span></span>
    </div>
  </section>

  <?php if (!empty($matches) && !$customer): ?>
    <section class="gsm-card p-3 mb-3">
      <h2 class="h6 fw-bold mb-2">Matching Customers</h2>
      <div class="gsm-grid">
        <?php foreach ($matches as $m): ?>
          <a class="gsm-card gsm-result text-decoration-none text-dark" href="<?php echo Helpers::baseUrl('index.php?page=search&name=' . urlencode($m['name'])); ?>">
            <div class="d-flex align-items-start gap-2">
              <div class="gsm-res-i"><i class="bi bi-person-badge"></i></div>
              <div class="flex-grow-1">
                <p class="gsm-res-title m-0" data-gsm-hl="1" data-raw="<?php echo htmlspecialchars((string)$m['name']); ?>"><?php echo htmlspecialchars((string)$m['name']); ?></p>
                <div class="gsm-res-sub"><?php echo htmlspecialchars((string)($m['phone'] ?? '')); ?></div>
                <span class="badge text-bg-light border gsm-badge mt-1">Customer</span>
              </div>
              <i class="bi bi-chevron-right text-muted"></i>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($customer): ?>
    <section class="gsm-card p-3 mb-3">
      <div class="row g-2">
        <div class="col-md-3"><strong>Name:</strong> <?php echo htmlspecialchars((string)($customer['name'] ?? '')); ?></div>
        <div class="col-md-3"><strong>Phone:</strong> <?php echo htmlspecialchars((string)($customer['phone'] ?? '')); ?></div>
        <div class="col-md-3"><strong>Address:</strong> <?php echo htmlspecialchars((string)($customer['address'] ?? '')); ?></div>
        <div class="col-md-3"><strong>Delivery Location:</strong> <?php echo htmlspecialchars((string)($customer['delivery_location'] ?? '')); ?></div>
      </div>
      <?php if ($dueSummary): ?>
        <?php $due = max(0, (float)$dueSummary['total'] - (float)$dueSummary['paid']); ?>
        <div class="row g-2 mt-2">
          <div class="col-md-4"><div class="gsm-card p-2"><small class="text-muted">Total Billed</small><div class="fw-bold"><?php echo number_format((float)$dueSummary['total'], 2); ?></div></div></div>
          <div class="col-md-4"><div class="gsm-card p-2"><small class="text-muted">Total Paid</small><div class="fw-bold text-success"><?php echo number_format((float)$dueSummary['paid'], 2); ?></div></div></div>
          <div class="col-md-4"><div class="gsm-card p-2"><small class="text-muted">Due Balance</small><div class="fw-bold text-danger"><?php echo number_format($due, 2); ?></div></div></div>
        </div>
      <?php endif; ?>
    </section>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h6 fw-bold mb-0">Search Results</h2>
      <div class="btn-group btn-group-sm">
        <button id="gsmCardBtn" type="button" class="btn btn-outline-secondary">Card View</button>
        <button id="gsmTableBtn" type="button" class="btn btn-outline-secondary">Table View</button>
      </div>
    </div>

    <section id="gsmCardView" class="gsm-grid mb-3">
      <?php foreach (($parcels ?? []) as $p): ?>
        <article class="gsm-card gsm-result">
          <div class="d-flex align-items-start gap-2">
            <div class="gsm-res-i"><i class="bi bi-box-seam"></i></div>
            <div class="flex-grow-1">
              <p class="gsm-res-title" data-gsm-hl="1" data-raw="<?php echo htmlspecialchars((string)($p['tracking_number'] ?? ('Parcel #' . $p['id']))); ?>"><?php echo htmlspecialchars((string)($p['tracking_number'] ?? ('Parcel #' . $p['id']))); ?></p>
              <div class="gsm-res-sub"><?php echo htmlspecialchars(($p['from_branch'] ?? '') . ' -> ' . ($p['to_branch'] ?? '')); ?></div>
              <span class="badge text-bg-light border gsm-badge">Parcel</span>
              <span class="badge text-bg-light border gsm-badge"><?php echo htmlspecialchars((string)$p['status']); ?></span>
            </div>
            <div class="gsm-res-actions d-inline-flex gap-1">
              <button class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></button>
              <button class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></button>
              <button class="btn btn-sm btn-outline-secondary" title="Print"><i class="bi bi-printer"></i></button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
      <?php foreach (($notes ?? []) as $n): ?>
        <article class="gsm-card gsm-result">
          <div class="d-flex align-items-start gap-2">
            <div class="gsm-res-i"><i class="bi bi-receipt"></i></div>
            <div class="flex-grow-1">
              <p class="gsm-res-title">Delivery Note #<?php echo (int)$n['id']; ?></p>
              <div class="gsm-res-sub"><?php echo htmlspecialchars((string)($n['branch_name'] ?? '')); ?> | <?php echo htmlspecialchars((string)$n['delivery_date']); ?></div>
              <span class="badge text-bg-light border gsm-badge">Delivery Note</span>
              <span class="badge text-bg-light border gsm-badge"><?php echo number_format((float)$n['total_amount'], 2); ?></span>
            </div>
            <div class="gsm-res-actions d-inline-flex gap-1">
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>" title="View"><i class="bi bi-eye"></i></a>
              <button class="btn btn-sm btn-outline-secondary" title="Print"><i class="bi bi-printer"></i></button>
              <button class="btn btn-sm btn-outline-secondary" title="History"><i class="bi bi-clock-history"></i></button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <section id="gsmTableView" class="gsm-card overflow-hidden d-none mb-3">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 gsm-table">
          <thead><tr><th>Type</th><th>Reference</th><th>Date</th><th>Description</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach (($parcels ?? []) as $p): ?>
              <tr>
                <td>Parcel</td>
                <td><?php echo htmlspecialchars((string)($p['tracking_number'] ?? ('Parcel #' . $p['id']))); ?></td>
                <td><?php echo htmlspecialchars((string)($p['created_at'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars(($p['from_branch'] ?? '') . ' -> ' . ($p['to_branch'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)$p['status']); ?></td>
                <td><button class="btn btn-sm btn-outline-secondary">View</button></td>
              </tr>
            <?php endforeach; ?>
            <?php foreach (($notes ?? []) as $n): ?>
              <tr>
                <td>Delivery Note</td>
                <td>#<?php echo (int)$n['id']; ?></td>
                <td><?php echo htmlspecialchars((string)$n['delivery_date']); ?></td>
                <td><?php echo htmlspecialchars((string)($n['branch_name'] ?? '')); ?></td>
                <td><?php echo number_format((float)$n['total_amount'], 2); ?></td>
                <td><a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php elseif ($phone !== '' || $name !== ''): ?>
    <div class="gsm-card gsm-empty">
      <i class="bi bi-search"></i>
      <h3 class="h5 text-muted">No matching records found.</h3>
      <p class="small mb-2">Suggestions: check spelling, try another keyword, remove filters, or search all categories.</p>
      <a href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>" class="btn btn-outline-secondary btn-sm">Retry Search</a>
    </div>
  <?php endif; ?>
</div>
