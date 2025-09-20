<?php /** @var array $employees */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Employees</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Employee</a>
</div>
<div class="d-flex gap-2 align-items-center mb-2">
  <div class="form-check">
    <input class="form-check-input" type="checkbox" value="1" id="togglePayroll">
    <label class="form-check-label" for="togglePayroll">Show Payroll Columns</label>
  </div>
  <div class="form-check">
    <input class="form-check-input" type="checkbox" value="1" id="toggleContact">
    <label class="form-check-label" for="toggleContact">Show Contact/Address</label>
  </div>
  <div class="ms-auto" id="exportButtons"></div>
</div>
<div class="table-responsive" style="width: 100%; overflow-x: auto;">
  <table class="table table-sm table-striped align-middle" id="employeesTable" style="width: 100%; min-width: 2000px;">
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
        <th>Basic</th>
        <th>EPF (Emp.)</th>
        <th>EPF (Empr.)</th>
        <th>ETF</th>
        <th>Allowance</th>
        <th>Deductions</th>
        <th>Net</th>
        <th>Month-Year</th>
        <th>License Number</th>
        <th>License Expiry</th>
        <th>Vehicle</th>
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
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['basic_salary'] ?? 0), 2); ?></td>
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['epf_employee'] ?? 0), 2); ?></td>
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['epf_employer'] ?? 0), 2); ?></td>
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['etf'] ?? 0), 2); ?></td>
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['allowance'] ?? 0), 2); ?></td>
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['deductions'] ?? 0), 2); ?></td>
          <td class="text-end">&nbsp;<?php echo number_format((float)($e['net_salary'] ?? 0), 2); ?></td>
          <td><?php echo htmlspecialchars($e['month_year'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['license_number'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['license_expiry'] ?? ''); ?></td>
          <?php $vlabel = trim((string)($e['vehicle_no_join'] ?? '')); if ($vlabel==='') { $vlabel = (string)($e['vehicle_id'] ?? ($e['vehicle_id_join'] ?? '')); } ?>
          <td><?php echo htmlspecialchars($vlabel); ?></td>
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
    const table = $('#employeesTable').DataTable({
      responsive: false,
      pageLength: 25,
      order: [[0, 'desc']], // Sort by ID column descending
      columnDefs: [
        { targets: [10,11,12,13,14,15,16], className: 'text-end' }, // numeric payroll columns
        { targets: [19], type: 'date' }, // License Expiry (col 19)
        { targets: [22], type: 'date' }, // Join Date (col 22)
        { targets: [24], orderable: false } // Actions (col 24)
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

    // Default: compact view hides payroll and contact/address
    const payrollCols = [10,11,12,13,14,15,16,17];
    const contactCols = [5,6,7,18,19];
    payrollCols.forEach(i=> table.column(i).visible(false));
    contactCols.forEach(i=> table.column(i).visible(false));

    // Toggle handlers
    const tp = document.getElementById('togglePayroll');
    const tc = document.getElementById('toggleContact');
    if (tp) tp.addEventListener('change', function(){ payrollCols.forEach(i=> table.column(i).visible(tp.checked)); });
    if (tc) tc.addEventListener('change', function(){ contactCols.forEach(i=> table.column(i).visible(tc.checked)); });

    // Export buttons if Buttons extension exists
    if ($.fn.dataTable.Buttons) {
      new $.fn.dataTable.Buttons(table, {
        buttons: [
          { extend: 'copy', text: 'Copy' },
          { extend: 'csv', text: 'CSV' },
          { extend: 'excel', text: 'Excel' },
          { extend: 'print', text: 'Print' }
        ]
      });
      table.buttons().container().appendTo($('#exportButtons'));
    }

  } else {
    // Fallback: No DataTables loaded. Implement simple show/hide by toggling CSS on columns.
    const tbl = document.getElementById('employeesTable');
    if (!tbl) return;
    // Column indices (0-based)
    const payrollCols = [10,11,12,13,14,15,16,17];
    const contactCols = [5,6,7,18,19];
    // Hide selected columns
    function setColsVisible(indices, visible){
      const display = visible ? '' : 'none';
      const rows = tbl.querySelectorAll('tr');
      rows.forEach(tr => {
        const cells = Array.from(tr.children);
        indices.forEach(i => { if (cells[i]) cells[i].style.display = display; });
      });
    }
    // Default compact
    setColsVisible(payrollCols, false);
    setColsVisible(contactCols, false);
    // Wire toggles
    const tp = document.getElementById('togglePayroll');
    const tc = document.getElementById('toggleContact');
    if (tp) tp.addEventListener('change', ()=> setColsVisible(payrollCols, tp.checked));
    if (tc) tc.addEventListener('change', ()=> setColsVisible(contactCols, tc.checked));
  }
});
</script>
