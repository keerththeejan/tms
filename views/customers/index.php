<?php /** @var array $customers */ ?>
<style>
  .customers-page { --ui-border: rgba(17,24,39,.10); --ui-shadow: 0 1px 2px rgba(16,24,40,.06); --ui-radius: 14px; }
  .customers-page .page-head { display:flex; align-items:flex-start; justify-content:space-between; gap: 12px; margin-bottom: 12px; }
  .customers-page .page-title { font-size: 1.25rem; font-weight: 800; margin: 0; letter-spacing: -.01em; }
  .customers-page .page-subtitle { font-size: .86rem; color:#6b7280; margin: 2px 0 0; }
  .customers-page .card-soft { background:#fff; border: 1px solid var(--ui-border); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); }
  .customers-page .filters { padding: 12px; }
  .customers-page .filters .form-label { font-size: .78rem; font-weight: 700; color:#6b7280; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 6px; }
  .customers-page .filters .form-control, .customers-page .filters .form-select { height: 38px; font-size: .92rem; border-radius: 12px; }
  .customers-page .table-wrap { border: 1px solid var(--ui-border); border-radius: var(--ui-radius); background:#fff; }
  .customers-page .customers-table { table-layout: fixed; font-size: 13px; }
  .customers-page .customers-table th, .customers-page .customers-table td { padding: 6px 10px !important; vertical-align: middle; }
  .customers-page .customers-table thead th { font-size: 12px; letter-spacing: .02em; }
  .customers-page .cell-ellipsis { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
  .customers-page .col-num { width: 56px; }
  .customers-page .col-phone { width: 130px; }
  .customers-page .col-type { width: 110px; }
  .customers-page .col-actions { width: 120px; }
  .customers-page .btn-icon { width: 30px; height: 30px; padding: 0; display:inline-flex; align-items:center; justify-content:center; }
  @media (max-width: 992px) {
    .customers-page .customers-table th, .customers-page .customers-table td { padding: 6px 8px !important; }
  }
  @media (max-width: 576px) {
    .customers-page .page-head { flex-direction: column; align-items: stretch; }
    .customers-page .customers-table { font-size: 12.5px; }
  }
</style>

<div class="customers-page">

<?php
  $importedCnt = isset($_GET['imported']) ? (int)$_GET['imported'] : null;
  $failedCnt = isset($_GET['failed']) ? (int)$_GET['failed'] : null;
  $importFailed = (string)($_GET['import_failed'] ?? '') === '1';
  $hasImportErrors = (string)($_GET['import_errors'] ?? '') === '1';
  $importErrors = [];
  if ($hasImportErrors && isset($_SESSION['import_customer_errors']) && is_array($_SESSION['import_customer_errors'])) {
    $importErrors = $_SESSION['import_customer_errors'];
    unset($_SESSION['import_customer_errors']);
  }
?>

<div class="page-head">
  <div>
    <h3 class="page-title">Customers</h3>
    <div class="page-subtitle">Search, filter, and manage customer records. Import Excel-export CSV for bulk add.</div>
  </div>
  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerImportModal"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Import</button>
    <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Customer</a>
  </div>
</div>

<?php if ($importFailed): ?>
  <div class="alert alert-danger">Import failed. Please upload a valid <strong>.csv</strong> file exported from Excel.</div>
<?php elseif ($importedCnt !== null || $failedCnt !== null): ?>
  <div class="alert alert-info">
    Imported: <strong><?php echo (int)($importedCnt ?? 0); ?></strong>
    | Failed: <strong><?php echo (int)($failedCnt ?? 0); ?></strong>
    <?php if (!empty($importErrors)): ?>
      <div class="mt-2 small">
        <?php foreach ($importErrors as $e): ?>
          <div><?php echo htmlspecialchars((string)$e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="modal fade" id="customerImportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Import Customers (CSV)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=import'); ?>" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <div class="mb-2">
            <label class="form-label">CSV file</label>
            <input type="file" class="form-control" name="import_file" accept=".csv,text/csv" required>
            <div class="form-text">
              <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=import_template'); ?>" class="text-decoration-none">Download template</a>
              | <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=import_template&data_only=1'); ?>" class="text-decoration-none">Download sample data only</a>
              <span class="d-block">Upload after editing. Save as <strong>CSV UTF-8</strong>.</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card-soft filters mb-3">
  <form class="row g-2 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
    <input type="hidden" name="page" value="customers">
    <div class="col-6 col-md-3 col-lg-2">
      <label class="form-label">Name</label>
      <input type="text" class="form-control" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <label class="form-label">Phone</label>
      <input type="text" class="form-control" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <label class="form-label">Email</label>
      <input type="text" class="form-control" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-3">
      <label class="form-label">Address</label>
      <input type="text" class="form-control" name="address" placeholder="Address" value="<?php echo htmlspecialchars($address ?? ''); ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-3">
      <label class="form-label">Delivery Location</label>
      <input type="text" class="form-control" name="delivery_location" placeholder="Delivery Location" value="<?php echo htmlspecialchars($delivery_location ?? ''); ?>">
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <label class="form-label">Type</label>
      <select name="type" class="form-select">
        <?php $t = $type ?? ''; ?>
        <option value="" <?php echo ($t==='')?'selected':''; ?>>Any</option>
        <option value="regular" <?php echo ($t==='regular')?'selected':''; ?>>regular</option>
        <option value="corporate" <?php echo ($t==='corporate')?'selected':''; ?>>corporate</option>
      </select>
    </div>
    <div class="col-12 col-lg-auto d-flex gap-2 justify-content-lg-end">
      <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i> Apply</button>
      <a class="btn btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>">Reset</a>
    </div>
  </form>
</div>

<div class="table-responsive table-wrap">
  <table class="table table-sm table-striped align-middle datatable customers-table">
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th>Name</th>
        <th class="col-phone">Phone</th>
        <th class="d-none d-lg-table-cell">Email</th>
        <th class="d-none d-lg-table-cell">Address</th>
        <th>Delivery Location</th>
        <th class="col-type">Type</th>
        <th class="text-end col-actions">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $rowNum = 0; foreach ($customers as $c): $rowNum++; ?>
        <tr>
          <td><?php echo $rowNum; ?></td>
          <td><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)$c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></span></td>
          <td>
            <?php $ph = trim((string)($c['phone'] ?? '')); $showPh = (preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1) ? '' : $ph; echo htmlspecialchars($showPh); ?>
          </td>
          <td class="d-none d-lg-table-cell"><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)($c['email'] ?? '')); ?>"><?php echo htmlspecialchars($c['email'] ?? ''); ?></span></td>
          <td class="d-none d-lg-table-cell"><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)($c['address'] ?? '')); ?>"><?php echo htmlspecialchars($c['address']); ?></span></td>
          <td><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)($c['delivery_location'] ?? '')); ?>"><?php echo htmlspecialchars($c['delivery_location']); ?></span></td>
          <td><?php echo htmlspecialchars($c['customer_type'] ?? ''); ?></td>
          <td class="text-end">
            <div class="dropdown d-inline">
              <button class="btn btn-outline-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=edit&id='.(int)$c['id']); ?>"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=delete'); ?>" class="px-3" onsubmit="return confirm('Delete this customer?');">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-2"></i>Delete</button>
                  </form>
                </li>
              </ul>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</div>
