<?php /** @var array $routes */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Routes</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=routes&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Route</a>
  </div>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="routes">
  <div class="col-md-4">
    <input type="text" class="form-control" name="q" placeholder="Search routes by name or notes" value="<?php echo htmlspecialchars($q ?? ''); ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Search</button>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
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
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=routes&action=edit&id='.(int)$r['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
          <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=routes&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this route?');">
            <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
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
