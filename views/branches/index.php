<?php /** @var array $branches */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Branches</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=branches&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Branch</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle datatable">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Code</th>
        <th>Main</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($branches as $b): ?>
        <tr>
          <td><?php echo (int)$b['id']; ?></td>
          <td><?php echo htmlspecialchars($b['name']); ?></td>
          <td><?php echo htmlspecialchars($b['code']); ?></td>
          <td><?php echo (int)$b['is_main'] === 1 ? '<span class="badge bg-success">Main</span>' : ''; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=branches&action=edit&id='.(int)$b['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=branches&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this branch?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
