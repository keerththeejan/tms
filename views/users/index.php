<?php /** @var array $users */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Users</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=users&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New User</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle datatable">
    <thead>
      <tr>
        <th>#</th>
        <th>Username</th>
        <th>Full Name</th>
        <th>Role</th>
        <th>Branch</th>
        <th>Active</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($users ?? []) as $u): ?>
        <tr>
          <td><?php echo (int)$u['id']; ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?></td>
          <td><?php echo htmlspecialchars($u['full_name']); ?></td>
          <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($u['role']); ?></span></td>
          <td><?php echo htmlspecialchars($u['branch_name'] ?? '—'); ?></td>
          <td><?php echo ((int)$u['active'] === 1) ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>'; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=users&action=edit&id='.(int)$u['id']); ?>"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=users&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this user?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
