<?php /** @var array $suppliers */ ?>
<style>
  .sup-page {
    --sup-border: rgba(17, 24, 39, 0.08);
    --sup-shadow: 0 1px 3px rgba(16, 24, 40, 0.06), 0 8px 24px rgba(15, 23, 42, 0.06);
    --sup-radius: 14px;
    --sup-accent: #0d9488;
  }
  .sup-page .sup-head { margin-bottom: 1rem; }
  .sup-page .sup-title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .sup-page .sup-title i { color: var(--sup-accent); }
  .sup-page .sup-subtitle { font-size: 0.9rem; color: #64748b; margin: 0.35rem 0 0; max-width: 48rem; line-height: 1.45; }
  .sup-page .sup-badge {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: rgba(13, 148, 136, 0.12);
    color: #0f766e;
    border: 1px solid rgba(13, 148, 136, 0.22);
  }
  .sup-page .sup-filters {
    background: #fff;
    border: 1px solid var(--sup-border);
    border-radius: var(--sup-radius);
    box-shadow: var(--sup-shadow);
    padding: 0.85rem 1rem;
    margin-bottom: 1rem;
  }
  .sup-page .sup-filters .form-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 0.35rem;
  }
  .sup-page .sup-filters .form-control,
  .sup-page .sup-filters .form-select {
    border-radius: 10px;
    font-size: 0.9rem;
    border-color: rgba(15, 23, 42, 0.12);
  }
  .sup-page .sup-table-card {
    background: #fff;
    border: 1px solid var(--sup-border);
    border-radius: var(--sup-radius);
    box-shadow: var(--sup-shadow);
    overflow: hidden;
  }
  .sup-page .sup-table-card .table-responsive { border-radius: 0; }
  .sup-page .sup-table thead th {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid var(--sup-border);
    padding: 0.65rem 0.75rem;
    white-space: nowrap;
  }
  .sup-page .sup-table tbody td {
    padding: 0.55rem 0.75rem;
    vertical-align: middle;
    border-color: rgba(15, 23, 42, 0.06);
    font-size: 0.9rem;
  }
  .sup-page .sup-table tbody tr:hover { background: rgba(13, 148, 136, 0.04); }
  .sup-page .sup-actions .btn { border-radius: 10px; }
  .sup-page .sup-empty {
    text-align: center;
    padding: 2.5rem 1.25rem;
    color: #64748b;
  }
  .sup-page .sup-empty .bi { font-size: 2.5rem; opacity: 0.35; color: var(--sup-accent); display: block; margin-bottom: 0.75rem; }
  .sup-page .sup-dt-note {
    font-size: 0.75rem;
    color: #94a3b8;
    padding: 0.5rem 1rem;
    border-top: 1px solid var(--sup-border);
    background: #fafbfc;
  }
</style>

<div class="sup-page">
  <div class="sup-head d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div>
      <h1 class="sup-title">
        <i class="bi bi-truck" aria-hidden="true"></i>
        Suppliers
        <span class="sup-badge"><?php echo count($suppliers ?? []); ?> in list</span>
      </h1>
      <p class="sup-subtitle">
        Vendors linked to a branch for parcel entry. Filter by name, phone, code, or branch — then edit or remove records.
      </p>
    </div>
    <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>" class="btn btn-primary rounded-pill px-4 flex-shrink-0">
      <i class="bi bi-plus-lg me-1"></i> New supplier
    </a>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert">
      <i class="bi bi-check-circle-fill flex-shrink-0"></i>
      <span><?php echo htmlspecialchars($success); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="sup-filters">
    <form class="row g-2 g-md-3 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
      <input type="hidden" name="page" value="suppliers">
      <div class="col-6 col-md-6 col-lg-2">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="name" placeholder="Search name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
      </div>
      <div class="col-6 col-md-6 col-lg-2">
        <label class="form-label">Phone</label>
        <input type="text" class="form-control" name="phone" placeholder="Search phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
      </div>
      <div class="col-6 col-md-6 col-lg-2">
        <label class="form-label">Code</label>
        <input type="text" class="form-control" name="code" placeholder="Supplier code" value="<?php echo htmlspecialchars($code ?? ''); ?>">
      </div>
      <div class="col-6 col-md-6 col-lg-3">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select">
          <?php $bid = (int)($branch_id ?? 0); ?>
          <option value="0" <?php echo ($bid === 0) ? 'selected' : ''; ?>>All branches</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ($bid === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1 flex-lg-grow-0"><i class="bi bi-funnel me-1"></i> Apply filters</button>
        <a class="btn btn-outline-secondary flex-grow-1 flex-lg-grow-0" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>"><i class="bi bi-x-lg me-1"></i> Clear</a>
      </div>
    </form>
  </div>

  <div class="sup-table-card">
    <?php if (empty($suppliers)): ?>
      <div class="sup-empty">
        <i class="bi bi-inbox" aria-hidden="true"></i>
        <div class="fw-semibold text-dark mb-1">No suppliers match</div>
        <div class="small mb-3">Adjust filters or add a new supplier.</div>
        <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-plus-lg me-1"></i> Add supplier</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 sup-table datatable w-100">
          <thead>
            <tr>
              <th style="width:72px;">ID</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Code</th>
              <th>Branch</th>
              <th class="text-end" style="min-width:180px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($suppliers as $s): ?>
              <tr>
                <td class="text-muted small font-monospace">#<?php echo (int)$s['id']; ?></td>
                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['phone'] ?? ''); ?></td>
                <td><span class="badge rounded-pill text-bg-light border"><?php echo htmlspecialchars($s['supplier_code'] ?? ''); ?></span></td>
                <td><?php echo htmlspecialchars($s['branch_name'] ?? '—'); ?></td>
                <td class="text-end sup-actions">
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=edit&id=' . (int)$s['id']); ?>" title="Edit">
                    <i class="bi bi-pencil-square"></i><span class="d-none d-md-inline ms-1">Edit</span>
                  </a>
                  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this supplier? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Delete</span></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="sup-dt-note mb-0">
        <i class="bi bi-search me-1"></i> Use the DataTables search on this grid for instant filtering — list shows up to 100 suppliers (newest first).
      </div>
    <?php endif; ?>
  </div>
</div>
