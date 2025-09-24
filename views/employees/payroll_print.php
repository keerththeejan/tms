<?php /** @var array $employee */ ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payroll - <?php echo htmlspecialchars($employee['emp_code'] ?? 'Employee'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print { display:none !important; }
      body { margin: 0.5in; }
      .table td, .table th { padding: .35rem .5rem; }
    }
  </style>
</head>
<body>
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Employee Payroll</h4>
    <button class="btn btn-primary no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
  </div>

  <div class="row mb-3">
    <div class="col-sm-6">
      <div><strong>Employee Code:</strong> <?php echo htmlspecialchars($employee['emp_code'] ?? ''); ?></div>
      <div><strong>Name:</strong> <?php echo htmlspecialchars($employee['name'] ?? ''); ?></div>
      <div><strong>Position:</strong> <?php echo htmlspecialchars($employee['position'] ?? ''); ?></div>
      <div><strong>Branch:</strong> <?php echo htmlspecialchars($employee['branch_name'] ?? ''); ?></div>
    </div>
    <div class="col-sm-6">
      <div><strong>Month-Year:</strong> <?php echo htmlspecialchars($employee['month_year'] ?? ''); ?></div>
      <div><strong>Status:</strong> <?php echo htmlspecialchars($employee['status'] ?? ''); ?></div>
    </div>
  </div>

  <table class="table table-bordered">
    <tbody>
      <tr>
        <th style="width:30%">Basic Salary</th>
        <td class="text-end"><?php echo number_format((float)($employee['basic_salary'] ?? 0),2); ?></td>
      </tr>
      <tr>
        <th>EPF (Employee)</th>
        <td class="text-end"><?php echo number_format((float)($employee['epf_employee'] ?? 0),2); ?></td>
      </tr>
      <tr>
        <th>EPF (Employer)</th>
        <td class="text-end"><?php echo number_format((float)($employee['epf_employer'] ?? 0),2); ?></td>
      </tr>
      <tr>
        <th>ETF</th>
        <td class="text-end"><?php echo number_format((float)($employee['etf'] ?? 0),2); ?></td>
      </tr>
      <tr>
        <th>Allowance</th>
        <td class="text-end"><?php echo number_format((float)($employee['allowance'] ?? 0),2); ?></td>
      </tr>
      <tr>
        <th>Deductions</th>
        <td class="text-end"><?php echo number_format((float)($employee['deductions'] ?? 0),2); ?></td>
      </tr>
      <tr>
        <th>Net Salary</th>
        <td class="text-end"><?php echo number_format((float)($employee['net_salary'] ?? (($employee['basic_salary'] ?? 0)+($employee['allowance'] ?? 0)-($employee['deductions'] ?? 0)-($employee['epf_employee'] ?? 0))),2); ?></td>
      </tr>
    </tbody>
  </table>

  <div class="mt-4 small text-muted">Generated on <?php echo date('Y-m-d H:i'); ?></div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script>window.addEventListener('load', function(){ setTimeout(function(){ if (window.matchMedia) { window.print(); } }, 300); });</script>
</body>
</html>
