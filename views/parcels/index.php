<?php /** @var array $parcels */ 
  $filter_type = $filter_type ?? '';
  $today = date('Y-m-d');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h3 class="mb-0">Parcels</h3>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm"><i class="bi bi-geo-alt me-1"></i> Route Planning</a>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm"><i class="bi bi-truck-front me-1"></i> Vehicle Routes</a>
  </div>
</div>

<div class="card border shadow-sm mb-3">
  <div class="card-header py-2 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
    <span class="fw-semibold"><i class="bi bi-funnel me-1"></i> Filters</span>
    <div class="d-flex flex-wrap gap-1 align-items-center">
      <span class="small text-muted me-1">Show only:</span>
      <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=route_planning&from='.$today.'&to='.$today); ?>" class="btn btn-sm <?php echo $filter_type==='route_planning'?'btn-primary':'btn-outline-primary'; ?>"><i class="bi bi-geo-alt me-1"></i> Delivery Route Planning</a>
      <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=vehicle_routes'); ?>" class="btn btn-sm <?php echo $filter_type==='vehicle_routes'?'btn-primary':'btn-outline-primary'; ?>"><i class="bi bi-truck-front me-1"></i> Vehicle Routes</a>
      <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=customers'); ?>" class="btn btn-sm <?php echo $filter_type==='customers'?'btn-primary':'btn-outline-primary'; ?>"><i class="bi bi-people me-1"></i> Customers</a>
      <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-sm btn-outline-secondary" title="Reset all filters"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset all</a>
    </div>
  </div>
  <div class="card-body py-3">
    <?php if ($filter_type === 'route_planning'): ?>
    <div class="alert alert-info py-2 mb-3"><i class="bi bi-info-circle me-1"></i> Showing only parcels for <strong>Delivery Route Planning</strong>: pending or in transit, today's date.</div>
    <?php elseif ($filter_type === 'vehicle_routes'): ?>
    <div class="alert alert-info py-2 mb-3"><i class="bi bi-info-circle me-1"></i> Showing only parcels with a <strong>Vehicle</strong> assigned.</div>
    <?php elseif ($filter_type === 'customers'): ?>
    <div class="alert alert-info py-2 mb-3"><i class="bi bi-info-circle me-1"></i> Select a <strong>Customer</strong> below and click Apply to see only that customer's parcels.</div>
    <?php endif; ?>
    <p class="small text-muted mb-2">Filter by the same fields used when creating a parcel (customer, branches, date) plus search, tracking, invoice, vehicle, delivery location, and status.</p>
    <form method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
      <input type="hidden" name="page" value="parcels">
      <?php if ($filter_type !== ''): ?><input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($filter_type); ?>"><?php endif; ?>
      <div class="row g-2 g-md-3">
        <div class="col-12"><label class="form-label small fw-semibold text-primary mb-1">Required / key fields (from parcel form)</label></div>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label small text-muted mb-0 d-block">Customer</label>
          <select class="form-select form-select-sm" name="customer_id">
            <option value="0">All Customers</option>
            <?php foreach (($customersList ?? []) as $c): ?>
              <?php $nm = (string)($c['name'] ?? ''); $ph = trim((string)($c['phone'] ?? '')); $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1; $label = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : ''); ?>
              <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($customer_filter_id ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label small text-muted mb-0 d-block">From Branch</label>
          <select class="form-select form-select-sm" name="from_branch_id">
            <option value="0">All From Branches</option>
            <?php foreach (($branchesFilterList ?? []) as $b): ?>
              <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($from_branch_filter_id ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label small text-muted mb-0 d-block">To Branch</label>
          <select class="form-select form-select-sm" name="to_branch_id">
            <option value="0">All To Branches</option>
            <?php foreach (($branchesFilterList ?? []) as $b): ?>
              <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($to_branch_filter_id ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Date From</label>
          <input type="date" class="form-control form-control-sm" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>" title="From date">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Date To</label>
          <input type="date" class="form-control form-control-sm" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>" title="To date">
        </div>

        <div class="col-12 mt-3"><label class="form-label small fw-semibold text-secondary mb-1">Other filters (search, tracking, invoice, vehicle, delivery location, status)</label></div>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label small text-muted mb-0 d-block">Search (name / phone / tracking)</label>
          <input type="text" class="form-control form-control-sm" name="q" placeholder="Customer name, phone, or tracking" value="<?php echo htmlspecialchars($q ?? ''); ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Tracking / Serial</label>
          <input type="text" class="form-control form-control-sm" name="tracking_number" placeholder="Tracking No" value="<?php echo htmlspecialchars($tracking_filter ?? ''); ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Invoice No</label>
          <input type="text" class="form-control form-control-sm" name="invoice_no" placeholder="Invoice No" value="<?php echo htmlspecialchars($invoice_no_filter ?? ''); ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label small text-muted mb-0 d-block">Supplier</label>
          <select class="form-select form-select-sm" name="supplier_id">
            <option value="0">All Suppliers</option>
            <?php foreach (($suppliersFilterList ?? []) as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>" <?php echo ((int)($supplier_filter_id ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name'] ?? ''); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Vehicle No</label>
          <input type="text" class="form-control form-control-sm" name="vehicle_no" placeholder="Vehicle No" value="<?php echo htmlspecialchars($vehicle_no ?? ''); ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-3">
          <label class="form-label small text-muted mb-0 d-block">Delivery Location</label>
          <input type="text" class="form-control form-control-sm" name="delivery_location" placeholder="Customer delivery location" value="<?php echo htmlspecialchars($delivery_location_filter ?? ''); ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Status</label>
          <select class="form-select form-select-sm" name="status">
            <option value="">All Status</option>
            <option value="pending" <?php echo ($status ?? '')==='pending'?'selected':''; ?>>Pending</option>
            <option value="in_transit" <?php echo ($status ?? '')==='in_transit'?'selected':''; ?>>In Transit</option>
            <option value="delivered" <?php echo ($status ?? '')==='delivered'?'selected':''; ?>>Delivered</option>
          </select>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <label class="form-label small text-muted mb-0 d-block">Route date</label>
          <input type="date" class="form-control form-control-sm" name="route_date" value="<?php echo htmlspecialchars($route_date ?? ''); ?>" title="Parcels with delivery route on this date">
        </div>
        <div class="col-12 col-lg-12 d-flex align-items-end gap-2 flex-wrap mt-2">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Apply filters</button>
          <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary btn-sm">Reset all</a>
          <?php if (isset($_SESSION['parcels_filter_from']) || isset($_SESSION['parcels_filter_to'])): ?>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&clear_dates=1'); ?>" class="btn btn-outline-danger btn-sm" title="Clear saved date filter"><i class="bi bi-x-circle me-1"></i> Clear saved dates</a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
</div>
<?php if (isset($_SESSION['parcels_filter_from']) || isset($_SESSION['parcels_filter_to'])): ?>
  <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
    <i class="bi bi-info-circle"></i> Date filter is saved: 
    <strong><?php echo htmlspecialchars($from ?? ''); ?></strong> to <strong><?php echo htmlspecialchars($to ?? ''); ?></strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
  <a href="<?php echo Helpers::baseUrl('index.php?' . http_build_query(array_merge($_GET, ['page'=>'parcels','action'=>'print_list']))); ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i> Print current list</a>
  <?php if (!empty($vehicle_no)): ?>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_detail&vehicle_no=' . urlencode($vehicle_no) . '&from=' . urlencode($from ?? date('Y-m-d')) . '&to=' . urlencode($to ?? date('Y-m-d')) . '&direction=from'); ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-signpost me-1"></i> Print by route (vehicle <?php echo htmlspecialchars($vehicle_no); ?>)</a>
  <?php endif; ?>
</div>
<div class="table-responsive" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
  <table class="table table-sm table-striped align-middle datatable">
    <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Supplier</th>
        <th>From Branch</th>
        <th>To Branch</th>
        <th>Vehicle</th>
        <th>Delivery Route</th>
        <th>Items</th>
        <th>Weight</th>
        <th>Price</th>
        <th>Status</th>
        <th>Email</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $rowNum = (int)($parcelRowStart ?? 0); foreach ($parcels as $p): $rowNum++; ?>
        <tr>
          <td><?php echo $rowNum; ?></td>
          <td>
            <?php $cid = (int)$p['customer_id']; ?>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&customer_id=' . $cid); ?>" class="text-decoration-none">
              <?php $nm = (string)($p['customer_name'] ?? ''); $ph = trim((string)($p['customer_phone'] ?? '')); $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1; $label = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : ''); echo htmlspecialchars($label); ?>
            </a>
          </td>
          <td><?php echo htmlspecialchars($p['supplier_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['from_branch'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($p['to_branch'] ?? ''); ?></td>
          <td>
            <?php if (!empty($p['vehicle_no'])): ?>
              <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&vehicle_no=' . urlencode($p['vehicle_no'])); ?>" class="text-decoration-none">
                <?php echo htmlspecialchars($p['vehicle_no']); ?>
              </a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td class="small">
            <?php
              $rdTo = trim((string)($p['route_date_to'] ?? ''));
              $rdFrom = trim((string)($p['route_date_from'] ?? ''));
              if ($rdTo !== '' || $rdFrom !== ''):
                $parts = [];
                if ($rdTo !== '') $parts[] = 'To: ' . $rdTo;
                if ($rdFrom !== '') $parts[] = 'From: ' . $rdFrom;
                echo htmlspecialchars(implode(' / ', $parts));
              else:
                echo '—';
              endif;
            ?>
          </td>
          <td>
            <?php 
              $desc = trim((string)($p['item_descriptions'] ?? ''));
              echo $desc === '' ? '—' : htmlspecialchars($desc);
            ?>
          </td>
          <td><?php echo number_format((float)$p['weight'], 2); ?></td>
          <td><?php echo is_null($p['price']) ? '-' : number_format((float)$p['price'], 2); ?></td>
          <td><span class="badge bg-<?php echo $p['status']==='delivered'?'success':($p['status']==='in_transit'?'info':'secondary'); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
          <td>
            <?php if (!empty($p['email_status'])): ?>
              <?php if ($p['email_status'] === 'sent'): ?>
                <span class="badge bg-success">Sent</span>
              <?php else: ?>
                <span class="badge bg-danger">Failed</span>
              <?php endif; ?>
              <small class="text-muted d-block"><?php echo htmlspecialchars($p['emailed_at'] ?? ''); ?></small>
            <?php else: ?>
              <span class="badge bg-secondary">Not sent</span>
            <?php endif; ?>
            <div>
              <a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=email_log&id='.(int)$p['id']); ?>">View log</a>
            </div>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=parcel_print&id='.(int)$p['id']); ?>"><i class="bi bi-printer"></i> Print</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id='.(int)$p['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <a class="btn btn-sm btn-outline-success" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route&customer_id='.(int)$p['customer_id']); ?>"><i class="bi bi-signpost"></i> Delivery Route</a>
            
            <a class="btn btn-sm btn-outline-info" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=email_form&id='.(int)$p['id']); ?>"><i class="bi bi-envelope"></i> Email</a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this parcel?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-3">
  <div class="text-muted">
    Showing <?php echo count($parcels); ?> of <?php echo (int)($totalCount ?? 0); ?> parcels
  </div>
  <nav>
    <ul class="pagination pagination-sm mb-0">
      <?php if (($page ?? 1) > 1): ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = ($page ?? 1) - 1;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>">Previous</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled">
          <span class="page-link">Previous</span>
        </li>
      <?php endif; ?>
      
      <?php
      $currentPage = $page ?? 1;
      $totalPages = $totalPages ?? 1;
      $startPage = max(1, $currentPage - 2);
      $endPage = min($totalPages, $currentPage + 2);
      
      if ($startPage > 1): ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = 1;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>">1</a>
        </li>
        <?php if ($startPage > 2): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = $i;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      
      <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = $totalPages;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>"><?php echo $totalPages; ?></a>
        </li>
      <?php endif; ?>
      
      <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = ($page ?? 1) + 1;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>">Next</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled">
          <span class="page-link">Next</span>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
</div>
<?php endif; ?>
