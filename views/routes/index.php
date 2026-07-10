<?php /** @var array $routes */ ?>
<div class="container-fluid px-0">
  <div class="row g-2">
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-3">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
            <h3 class="h5 mb-0 fw-bold">Routes</h3>
            <div class="d-flex gap-2">
              <a href="<?php echo Helpers::baseUrl('index.php?page=routes&action=new'); ?>" class="btn btn-primary d-inline-flex align-items-center gap-1"><i class="bi bi-plus-circle" aria-hidden="true"></i><span>Add route</span></a>
            </div>
          </div>
          <form class="row g-2 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
            <input type="hidden" name="page" value="routes">
            <div class="col-md-4">
              <label class="form-label small mb-1">Search</label>
              <input type="text" class="form-control" name="q" placeholder="Search routes by name or notes" value="<?php echo htmlspecialchars($q ?? ''); ?>">
            </div>
            <div class="col-auto">
              <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><i class="bi bi-search" aria-hidden="true"></i><span>Search</span></button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Notes</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($routes ?? []) as $r): ?>
                <tr>
                  <td><?php echo (int)$r['id']; ?></td>
                  <td><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r['notes'] ?? ''); ?></td>
                  <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?php echo Helpers::baseUrl('index.php?page=routes&action=edit&id='.(int)$r['id']); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span></a>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=routes&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this route?');">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"><i class="bi bi-trash" aria-hidden="true"></i><span>Delete</span></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($routes)): ?>
                <tr><td colspan="4" class="text-center text-muted">No routes found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
