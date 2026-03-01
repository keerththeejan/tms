<?php /** @var array $branches */ ?>
<style>
  .branches-page { --ui-border: rgba(17,24,39,.10); --ui-shadow: 0 1px 2px rgba(16,24,40,.06); --ui-radius: 14px; }
  .branches-page .page-header { flex-wrap: wrap; gap: 0.5rem; }
  .branches-page .page-header h3 { font-size: 1.25rem; font-weight: 800; letter-spacing: -.01em; }
  .branches-page .card-soft { background:#fff; border: 1px solid var(--ui-border); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); }
  .branches-page .table-wrap { border: 1px solid var(--ui-border); border-radius: var(--ui-radius); background:#fff; }
  .branches-page .branches-table { table-layout: fixed; font-size: 13px; }
  .branches-page .branches-table th, .branches-page .branches-table td { padding: 6px 10px !important; vertical-align: middle; }
  .branches-page .branches-table thead th { font-size: 12px; letter-spacing: .02em; }
  .branches-page .cell-ellipsis { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
  .branches-page .col-id { width: 72px; }
  .branches-page .col-code { width: 120px; }
  .branches-page .col-main { width: 92px; }
  .branches-page .col-actions { width: 140px; }
  .branches-page .badge-soft-success { background: rgba(25,135,84,.12); color: #146c43; border: 1px solid rgba(25,135,84,.20); font-weight: 800; }
  .branches-page .btn-icon { width: 30px; height: 30px; padding: 0; display:inline-flex; align-items:center; justify-content:center; }
  .branches-page .actions-cell { white-space: nowrap; }
  @media (max-width: 576px) {
    .branches-page .page-header { flex-direction: column; align-items: stretch; }
    .branches-page .page-header .btn { width: 100%; }
  }
</style>
<div class="branches-page">
<div class="d-flex justify-content-between align-items-center mb-3 page-header">
  <h3 class="mb-0">Branches</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=branches&action=new'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Branch</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="table-responsive table-wrap">
  <table class="table table-sm table-striped align-middle datatable branches-table">
    <thead>
      <tr>
        <th class="col-id">#</th>
        <th>Name</th>
        <th class="col-code">Code</th>
        <th class="col-main">Main</th>
        <th class="text-end col-actions">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($branches as $b): ?>
        <tr>
          <td><?php echo (int)$b['id']; ?></td>
          <td><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)$b['name']); ?>"><?php echo htmlspecialchars($b['name']); ?></span></td>
          <td><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)$b['code']); ?>"><?php echo htmlspecialchars($b['code']); ?></span></td>
          <td><?php echo (int)$b['is_main'] === 1 ? '<span class="badge badge-soft-success">Main</span>' : ''; ?></td>
          <td class="text-end actions-cell">
            <div class="d-none d-md-inline">
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=branches&action=edit&id='.(int)$b['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=branches&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this branch?');">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
              </form>
            </div>
            <div class="dropdown d-inline d-md-none">
              <button class="btn btn-outline-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=branches&action=edit&id='.(int)$b['id']); ?>"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=branches&action=delete'); ?>" class="px-3" onsubmit="return confirm('Delete this branch?');">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-2"></i>Delete</button>
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
