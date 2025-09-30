<?php /** @var array $employees */ ?>
<style>
  /* Neater row spacing and truncation helpers scoped to employees page */
  #employeesTable td, #employeesTable th { vertical-align: middle; }
  .nowrap { white-space: nowrap; }
  .truncate { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  @media (max-width: 1200px) { .truncate { max-width: 160px; } }
  @media (max-width: 992px) { .truncate { max-width: 120px; } }
  @media (max-width: 768px) { .truncate { max-width: 90px; } }
  /* Make action buttons tighter */
  #employeesTable .btn { padding: .15rem .4rem; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Employees</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Employee</a>
</div>
<!-- Clean details-only view: no toolbar toggles -->
<div class="table-responsive" style="width: 100%; overflow-x: auto;">
  <table class="table table-sm table-striped align-middle" id="employeesTable" style="width: 100%; min-width: 1100px;">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee Code</th>
        <th>Name</th>
        <th class="d-none d-xl-table-cell">First Name</th>
        <th class="d-none d-xl-table-cell">Last Name</th>
        <th class="d-none d-lg-table-cell">Email</th>
        <th>Phone</th>
        <th class="d-none d-lg-table-cell">Address</th>
        <th>Position</th>
        <th class="d-none d-xl-table-cell">Role</th>
        <th class="d-none d-lg-table-cell">License Number</th>
        <th class="d-none d-lg-table-cell">License Expiry</th>
        <th class="d-none d-md-table-cell">Vehicle</th>
        <th class="d-none d-md-table-cell">Branch</th>
        <th class="d-none d-lg-table-cell">Join Date</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employees as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td class="nowrap"><?php echo htmlspecialchars($e['emp_code'] ?? ''); ?></td>
          <td class="truncate" title="<?php echo htmlspecialchars($e['name'] ?? ''); ?>"><?php echo htmlspecialchars($e['name'] ?? ''); ?></td>
          <td class="d-none d-xl-table-cell truncate" title="<?php echo htmlspecialchars($e['first_name'] ?? ''); ?>"><?php echo htmlspecialchars($e['first_name'] ?? ''); ?></td>
          <td class="d-none d-xl-table-cell truncate" title="<?php echo htmlspecialchars($e['last_name'] ?? ''); ?>"><?php echo htmlspecialchars($e['last_name'] ?? ''); ?></td>
          <td class="d-none d-lg-table-cell truncate" title="<?php echo htmlspecialchars($e['email'] ?? ''); ?>"><?php echo htmlspecialchars($e['email'] ?? ''); ?></td>
          <td class="nowrap"><?php echo htmlspecialchars($e['phone'] ?? ''); ?></td>
          <td class="d-none d-lg-table-cell truncate" title="<?php echo htmlspecialchars($e['address'] ?? ''); ?>"><?php echo htmlspecialchars($e['address'] ?? ''); ?></td>
          <td class="truncate" title="<?php echo htmlspecialchars($e['position'] ?? ''); ?>"><?php echo htmlspecialchars($e['position'] ?? ''); ?></td>
          <td class="d-none d-xl-table-cell truncate" title="<?php echo htmlspecialchars($e['role'] ?? ''); ?>"><?php echo htmlspecialchars($e['role'] ?? ''); ?></td>
          <td class="d-none d-lg-table-cell nowrap"><?php echo htmlspecialchars($e['license_number'] ?? ''); ?></td>
          <td class="d-none d-lg-table-cell nowrap"><?php echo htmlspecialchars($e['license_expiry'] ?? ''); ?></td>
          <?php $vlabel = trim((string)($e['vehicle_no_join'] ?? '')); if ($vlabel==='') { $vlabel = (string)($e['vehicle_id'] ?? ($e['vehicle_id_join'] ?? '')); } ?>
          <td class="d-none d-md-table-cell truncate" title="<?php echo htmlspecialchars($vlabel); ?>"><?php echo htmlspecialchars($vlabel); ?></td>
          <td class="d-none d-md-table-cell truncate" title="<?php echo htmlspecialchars($e['branch_name'] ?? ''); ?>"><?php echo htmlspecialchars($e['branch_name'] ?? ''); ?></td>
          <td class="d-none d-lg-table-cell nowrap"><?php echo htmlspecialchars($e['join_date'] ?? ''); ?></td>
          <td><?php echo $e['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=edit&id='.(int)$e['id']); ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this employee?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize DataTable for employees (details-only)
  if (typeof $ !== 'undefined' && $.fn.DataTable) {
    $('#employeesTable').DataTable({
      responsive: false,
      pageLength: 25,
      order: [[0, 'desc']],
      columnDefs: [
        { targets: [11], type: 'date' }, // License Expiry
        { targets: [14], type: 'date' }, // Join Date
        { targets: [16], orderable: false } // Actions
      ],
      scrollX: true,
      autoWidth: false,
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           '<"row"<"col-sm-12"tr>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      language: { search: 'Search employees:', lengthMenu: 'Show _MENU_ employees per page' }
    });
  }
});
</script>
