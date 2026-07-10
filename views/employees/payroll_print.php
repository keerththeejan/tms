<?php /** @var array $employee */ ?>
<?php
$reportDocTitle = 'Payroll - ' . ($employee['emp_code'] ?? 'Employee');
$reportTitle = 'Employee Payroll';
$reportSubtitle = 'Transport and Parcel Services';
$reportMetaItems = [
    ['label' => 'Report', 'value' => 'Employee Payroll'],
    ['label' => 'Print Date', 'value' => date('d/m/Y H:i')],
    ['label' => 'Employee', 'value' => (string)($employee['emp_code'] ?? '')],
    ['label' => 'Month', 'value' => (string)($employee['month_year'] ?? '')],
];
$reportInfoItems = [
    ['label' => 'Report Name', 'value' => 'Employee Payroll'],
    ['label' => 'Employee Code', 'value' => (string)($employee['emp_code'] ?? '')],
    ['label' => 'Name', 'value' => (string)($employee['name'] ?? '')],
    ['label' => 'Branch', 'value' => (string)($employee['branch_name'] ?? '')],
    ['label' => 'Month-Year', 'value' => (string)($employee['month_year'] ?? '')],
    ['label' => 'Printed Date', 'value' => date('d/m/Y H:i')],
];
$reportShowInfoPanel = true;
$addresses = Helpers::companyHeaderAddressLines((string)($_GET['addr'] ?? ''), 3);
$reportShowAddresses = true;
$reportAddressLines = $addresses;
include __DIR__ . '/../partials/report/print_document_open.php';
?>
<div class="no-print mb-3">
  <div class="d-flex flex-wrap gap-2 mb-2">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    <button id="toggleAddrEditor" class="btn btn-outline-secondary" type="button"><i class="bi bi-pencil-square"></i> Edit Header Addresses</button>
  </div>
  <div id="addrEditor" style="display:none">
    <div class="card card-body p-2">
      <div class="mb-2 small text-muted">Enter one address per line. Changes affect only this print.</div>
      <textarea id="addrTextarea" class="form-control" rows="4"></textarea>
      <div class="mt-2 d-flex gap-2">
        <button id="applyAddr" class="btn btn-success" type="button"><i class="bi bi-check"></i> Apply</button>
        <button id="applyAndPrint" class="btn btn-primary" type="button"><i class="bi bi-printer"></i> Apply & Print</button>
      </div>
    </div>
  </div>
</div>

<div class="rpt-root rpt-root--b5">
  <article class="rpt-sheet container">
    <?php include __DIR__ . '/../partials/report/letterhead.php'; ?>

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

  <table class="table table-bordered rpt-table">
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

    <?php include __DIR__ . '/../partials/report/footer.php'; ?>
  </article>
</div>
<script>
  (function(){
    var toggleBtn = document.getElementById('toggleAddrEditor');
    var ed = document.getElementById('addrEditor');
    var ta = document.getElementById('addrTextarea');
    if (!toggleBtn || !ed || !ta) return;
    function getCurrentLines(){
      var nodes = document.querySelectorAll('#addrContainer .addr-line');
      var arr = [];
      for (var i=0;i<nodes.length;i++){ var t = nodes[i].textContent.trim(); if(t) arr.push(t); }
      return arr;
    }
    ta.value = getCurrentLines().join('\n');
    toggleBtn.addEventListener('click', function(){ ed.style.display = (ed.style.display==='none' || ed.style.display==='') ? 'block' : 'none'; });
    function applyAddrs(){
      var cont = document.getElementById('addrContainer');
      if (!cont) return;
      var val = ta.value.replace(/\r/g,'');
      var parts = val.split('\n').map(function(s){ return s.trim(); }).filter(function(s){ return s.length>0; });
      cont.innerHTML = '';
      parts.forEach(function(line){
        var d = document.createElement('div');
        d.className = 'addr-line col';
        d.textContent = line;
        cont.appendChild(d);
      });
    }
    document.getElementById('applyAddr').addEventListener('click', function(){ applyAddrs(); });
    document.getElementById('applyAndPrint').addEventListener('click', function(){ applyAddrs(); window.print(); });
  })();
  window.addEventListener('load', function(){ setTimeout(function(){ if (window.matchMedia) { window.print(); } }, 300); });
</script>
<?php include __DIR__ . '/../partials/report/print_document_close.php'; ?>
