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
<?php $cfg = (require __DIR__ . '/../../config/config.php'); $brand = $cfg['company'] ?? []; $addrParam = (string)($_GET['addr'] ?? ''); if ($addrParam !== '') { $addrParam = str_replace(["\r"], '', $addrParam); } $addresses = []; if ($addrParam !== '') { $tmp = explode("\n", $addrParam); foreach ($tmp as $a) { $a = trim($a); if ($a !== '') { $addresses[] = $a; } } } else { foreach (($brand['addresses'] ?? []) as $a) { $a = trim((string)$a); if ($a !== '') { $addresses[] = $a; } } } ?>
<div class="container">
  <div class="mb-3 p-2 border rounded">
    <div class="d-flex align-items-center gap-2">
      <?php if (!empty($brand['logo_url'])): ?>
        <img src="<?php echo htmlspecialchars($brand['logo_url']); ?>" alt="Logo" style="height:38px">
      <?php endif; ?>
      <div>
        <div class="fw-bold"><?php echo htmlspecialchars($brand['name'] ?? ''); ?></div>
        <div class="small text-muted">Transport and Parcel Services</div>
      </div>
    </div>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-1 mt-1 small text-muted" id="addrContainer">
      <?php foreach ($addresses as $addr): ?>
        <div class="addr-line"><?php echo nl2br(htmlspecialchars($addr)); ?></div>
      <?php endforeach; ?>
    </div>
  </div>
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
      var cont = document.getElementById('addrContainer'); if (!cont) return;
      var parts = ta.value.replace(/\r/g,'').split('\n').map(function(s){ return s.trim(); }).filter(Boolean);
      cont.innerHTML = '';
      parts.forEach(function(line){ var d=document.createElement('div'); d.className='addr-line'; d.textContent=line; cont.appendChild(d); });
    }
    document.getElementById('applyAddr').addEventListener('click', function(){ applyAddrs(); });
    document.getElementById('applyAndPrint').addEventListener('click', function(){ applyAddrs(); window.print(); });
  })();
  window.addEventListener('load', function(){ setTimeout(function(){ if (window.matchMedia) { window.print(); } }, 300); });
</script>
</body>
</html>
