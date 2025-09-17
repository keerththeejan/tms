<?php /** @var array $employees */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Employees</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Employee</a>
</div>
<div class="table-responsive" style="width: 100%; overflow-x: auto;">
  <table class="table table-sm table-striped align-middle" id="employeesTable" style="width: 100%; min-width: 1500px;">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee Code</th>
        <th>Name</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Position</th>
        <th>Role</th>
        <th>Salary</th>
        <th>License Number</th>
        <th>License Expiry</th>
        <th>Vehicle ID</th>
        <th>Branch</th>
        <th>Join Date</th>
        <th>Status</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employees as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td><?php echo htmlspecialchars($e['emp_code'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['first_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['last_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['email'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['phone'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['address'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['position'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['role'] ?? ''); ?></td>
          <td><?php echo number_format((float)($e['salary_amount'] ?? 0), 2); ?></td>
          <td><?php echo htmlspecialchars($e['license_number'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['license_expiry'] ?? ''); ?></td>
          <?php $vid = $e['vehicle_id'] ?? ($e['vehicle_id_join'] ?? ''); ?>
          <td><?php echo htmlspecialchars((string)$vid); ?></td>
          <td><?php echo htmlspecialchars($e['branch_name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['join_date'] ?? ''); ?></td>
          <td><?php echo $e['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize DataTable for employees
  if (typeof $ !== 'undefined' && $.fn.DataTable) {
    $('#employeesTable').DataTable({
      responsive: false,
      pageLength: 25,
      order: [[0, 'desc']], // Sort by ID column descending
      columnDefs: [
        { targets: [10], className: 'text-end' }, // Salary column right-aligned
        { targets: [12], type: 'date' }, // License Expiry as date type
        { targets: [15], type: 'date' }, // Join Date as date type
        { targets: [17], orderable: false } // Actions column not sortable
      ],
      scrollX: true, // Enable horizontal scrolling for many columns
      autoWidth: false,
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           '<"row"<"col-sm-12"tr>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      language: {
        search: "Search employees:",
        lengthMenu: "Show _MENU_ employees per page"
      }
    });
  }
});
</script>
