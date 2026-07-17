<?php
$itemsResult = $itemsResult ?? ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$editingItem = $editingItem ?? ['id' => 0, 'item_name' => '', 'unit_rate' => '', 'status' => 'Active'];
$q = (string) ($q ?? '');
$statusFilter = (string) ($statusFilter ?? '');
$sort = (string) ($sort ?? 'created_at');
$order = (string) ($order ?? 'DESC');
$limit = (int) ($limit ?? 20);
$rows = $itemsResult['rows'] ?? [];
$page = (int) ($itemsResult['page'] ?? 1);
$pages = (int) ($itemsResult['pages'] ?? 1);
$isEdit = (int) ($editingItem['id'] ?? 0) > 0;
$queryBase = [
    'page' => 'items',
    'q' => $q,
    'status' => $statusFilter,
    'limit' => $limit,
];
$sortUrl = static function (string $field) use ($queryBase, $sort, $order): string {
    $nextOrder = ($sort === $field && $order === 'ASC') ? 'DESC' : 'ASC';
    return Helpers::baseUrl('index.php?' . http_build_query($queryBase + [
        'sort' => $field,
        'order' => $nextOrder,
    ]));
};
?>

<div class="container-fluid px-0">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-box2-heart text-primary me-2"></i>Add Items</h1>
      <p class="text-muted mb-0">Manage parcel item names and standard unit rates.</p>
    </div>
    <span class="badge text-bg-light border fs-6"><?php echo (int) ($itemsResult['total'] ?? 0); ?> item(s)</span>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars((string) $success); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars((string) $error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3 align-items-start">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
          <h2 class="h6 fw-bold mb-0"><?php echo $isEdit ? 'Edit Item' : 'Add New Item'; ?></h2>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=items&action=save'); ?>" id="itemForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Helpers::csrfToken()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) ($editingItem['id'] ?? 0); ?>">

            <div class="mb-3">
              <label for="itemName" class="form-label">Item Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="itemName" name="item_name" maxlength="200" required
                     value="<?php echo htmlspecialchars((string) ($editingItem['item_name'] ?? '')); ?>"
                     placeholder="Enter item name" autocomplete="off">
              <div class="invalid-feedback">Item Name is required.</div>
            </div>

            <div class="mb-3">
              <label for="unitRate" class="form-label">Unit Rate <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><?php echo htmlspecialchars(Helpers::currencySymbol()); ?></span>
                <input type="number" class="form-control" id="unitRate" name="unit_rate" min="0" step="0.01" required
                       value="<?php echo htmlspecialchars((string) ($editingItem['unit_rate'] ?? '')); ?>"
                       placeholder="0.00" inputmode="decimal">
                <div class="invalid-feedback">Enter a valid numeric Unit Rate.</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="itemStatus" class="form-label">Status</label>
              <select class="form-select" id="itemStatus" name="status">
                <option value="Active" <?php echo ($editingItem['status'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo ($editingItem['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-grow-1">
                <i class="bi bi-save me-1"></i><?php echo $isEdit ? 'Update' : 'Save'; ?>
              </button>
              <a href="<?php echo Helpers::baseUrl('index.php?page=items'); ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
          <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="items">
            <div class="col-12 col-md-6">
              <label for="itemSearch" class="form-label small">Search Item, Rate or Status</label>
              <input type="search" class="form-control" id="itemSearch" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search...">
            </div>
            <div class="col-7 col-md-3">
              <label for="statusFilter" class="form-label small">Status</label>
              <select class="form-select" id="statusFilter" name="status">
                <option value="">All Statuses</option>
                <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="col-5 col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i><span class="d-none d-xl-inline ms-1">Search</span></button>
              <a href="<?php echo Helpers::baseUrl('index.php?page=items'); ?>" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            </div>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">No</th>
                <th scope="col"><a class="text-decoration-none text-dark" href="<?php echo $sortUrl('item_name'); ?>">Item Name <i class="bi bi-arrow-down-up small"></i></a></th>
                <th scope="col" class="text-end"><a class="text-decoration-none text-dark" href="<?php echo $sortUrl('unit_rate'); ?>">Unit Rate <i class="bi bi-arrow-down-up small"></i></a></th>
                <th scope="col"><a class="text-decoration-none text-dark" href="<?php echo $sortUrl('status'); ?>">Status <i class="bi bi-arrow-down-up small"></i></a></th>
                <th scope="col"><a class="text-decoration-none text-dark" href="<?php echo $sortUrl('created_at'); ?>">Created Date <i class="bi bi-arrow-down-up small"></i></a></th>
                <th scope="col" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No items found.</td></tr>
              <?php endif; ?>
              <?php foreach ($rows as $index => $item): ?>
                <tr>
                  <td><?php echo (($page - 1) * $limit) + $index + 1; ?></td>
                  <td class="fw-semibold"><?php echo htmlspecialchars((string) $item['item_name']); ?></td>
                  <td class="text-end"><?php echo htmlspecialchars(Helpers::formatMoney((float) $item['unit_rate'])); ?></td>
                  <td><span class="badge <?php echo $item['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo htmlspecialchars((string) $item['status']); ?></span></td>
                  <td><?php echo htmlspecialchars(date('Y-m-d', strtotime((string) $item['created_at']))); ?></td>
                  <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=items&action=edit&id=' . (int) $item['id']); ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=items&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this item? This action cannot be undone.');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Helpers::csrfToken()); ?>">
                      <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pages > 1): ?>
          <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">Page <?php echo $page; ?> of <?php echo $pages; ?></span>
            <nav aria-label="Item list pagination">
              <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                  <?php if ($p === 1 || $p === $pages || abs($p - $page) <= 2): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                      <a class="page-link" href="<?php echo Helpers::baseUrl('index.php?' . http_build_query($queryBase + ['sort' => $sort, 'order' => $order, 'p' => $p])); ?>"><?php echo $p; ?></a>
                    </li>
                  <?php elseif ($p === 2 || $p === $pages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                  <?php endif; ?>
                <?php endfor; ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('itemForm')?.addEventListener('submit', function (event) {
  if (!this.checkValidity()) {
    event.preventDefault();
    event.stopPropagation();
  }
  this.classList.add('was-validated');
});
</script>
