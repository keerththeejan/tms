<?php /** @var array $employees */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Employees</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Employee</a>
</div>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Position</th>
        <th>Salary</th>
        <th>Branch</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employees as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td><?php echo htmlspecialchars($e['name']); ?></td>
          <td><?php echo htmlspecialchars($e['position']); ?></td>
          <td><?php echo number_format((float)$e['salary_amount'], 2); ?></td>
          <td><?php echo htmlspecialchars($e['branch_name'] ?? ''); ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=edit&id='.(int)$e['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this employee?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
