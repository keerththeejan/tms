<?php /** @var array $employees */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Employees - Payroll Report</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new_payroll'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Employees Payroll</a>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="employees">
  <input type="hidden" name="action" value="payroll">
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="emp_code" placeholder="Employee Code" value="<?php echo htmlspecialchars($emp_code ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="position" placeholder="Position" value="<?php echo htmlspecialchars($position ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <select name="branch_id" class="form-select">
      <?php $bid = (int)($branch_id ?? 0); ?>
      <option value="0" <?php echo ($bid===0)?'selected':''; ?>>Branch (any)</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ($bid===(int)$b['id'])?'selected':''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="month" class="form-control" name="month_year" value="<?php echo htmlspecialchars($month_year ?? ''); ?>">
  </div>
  <div class="col-auto d-flex gap-2 align-items-end">
    <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>">Clear</a>
  </div>
</form>

<div class="table-responsive" style="width: 100%; overflow-x: auto;">
  <table class="table table-sm table-striped align-middle" id="employeesPayrollTable" style="width: 100%; min-width: 1400px;">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee Code</th>
        <th>Name</th>
        <th>Position</th>
        <th>Branch</th>
        <th>Basic</th>
        <th>EPF (Emp.)</th>
        <th>EPF (Empr.)</th>
        <th>ETF</th>
        <th>Allowance</th>
        <th>Deductions</th>
        <th>Net</th>
        <th>Month-Year</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employees as $e): ?>
        <tr>
          <td><?php echo (int)$e['id']; ?></td>
          <td><?php echo htmlspecialchars($e['emp_code'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['name'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['position'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($e['branch_name'] ?? ''); ?></td>
          <?php $hasP = !empty($e['payroll_id']); ?>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['basic_salary'] ?? 0), 2) : ''; ?></td>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['epf_employee'] ?? 0), 2) : ''; ?></td>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['epf_employer'] ?? 0), 2) : ''; ?></td>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['etf'] ?? 0), 2) : ''; ?></td>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['allowance'] ?? 0), 2) : ''; ?></td>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['deductions'] ?? 0), 2) : ''; ?></td>
          <td class="text-end">&nbsp;<?php echo $hasP ? number_format((float)($e['net_salary'] ?? 0), 2) : ''; ?></td>
          <td><?php echo $hasP ? htmlspecialchars($e['month_year'] ?? '') : ''; ?></td>
          <td class="text-end">
            <?php $pid = (int)($e['payroll_id'] ?? 0); $eid=(int)($e['id'] ?? 0); ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $pid ? Helpers::baseUrl('index.php?page=employees&action=edit_payroll&id='.$pid) : Helpers::baseUrl('index.php?page=employees&action=new_payroll&employee_id='.$eid); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <?php if ($pid): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll_print&id='.$pid); ?>"><i class="bi bi-printer"></i> Print</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll_print&employee_id='.$eid); ?>"><i class="bi bi-printer"></i> Print</a>
            <?php endif; ?>
            <?php if ($pid): ?>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll_delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this payroll entry?');">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $pid; ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
              </form>
            <?php else: ?>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=delete'); ?>" class="d-inline" onsubmit="return confirm('No payroll for this employee. Delete employee record instead?');">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $eid; ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof $ !== 'undefined' && $.fn.DataTable) {
    const table = $('#employeesPayrollTable').DataTable({
      responsive: false,
      pageLength: 25,
      order: [[0, 'desc']],
      columnDefs: [
        { targets: [5,6,7,8,9,10,11], className: 'text-end' }
      ],
      scrollX: true,
      autoWidth: false,
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           '<"row"<"col-sm-12"tr>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      language: { search: 'Search employees:', lengthMenu: 'Show _MENU_ employees per page' }
    });

    if ($.fn.dataTable.Buttons) {
      new $.fn.dataTable.Buttons(table, {
        buttons: [
          { extend: 'copy', text: 'Copy' },
          { extend: 'csv', text: 'CSV' },
          { extend: 'excel', text: 'Excel' },
          { extend: 'print', text: 'Print' }
        ]
      });
      table.buttons().container().appendTo($('.dataTables_filter'));
    }
  }
});
</script>
