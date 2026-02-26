<?php /** @var array $routes */ /** @var string|null $success */ /** @var string|null $error */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Delivery Routes (common)</h3>
  <span class="text-muted small">Saved routes appear in the customer form dropdown.</span>
</div>
<?php if (!empty($success)): ?>
  <div class="alert alert-success py-2"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-md-5 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header py-2"><strong>Add delivery route</strong></div>
      <div class="card-body">
        <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_routes&action=save'); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <div class="mb-2">
            <label class="form-label">Route name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Kilinochchi, Jaffna" required>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Save</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-7 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-header py-2"><strong>Common delivery routes</strong></div>
      <div class="card-body p-0">
        <?php if (empty($routes)): ?>
          <p class="text-muted px-3 py-3 mb-0">No delivery routes yet. Add one with the form on the left.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($routes as $r): ?>
                  <tr>
                    <td><?php echo (int)$r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
                    <td class="text-end">
                      <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_routes&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Remove this delivery route from the common list?');">
                        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Remove</button>
                      </form>
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
