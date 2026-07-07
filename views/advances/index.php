<?php /** @var array $advances */ /** @var array $employeesAll */ ?>
<?php
$advances = $advances ?? [];
$employeesAll = $employeesAll ?? [];
$advCssPath = dirname(__DIR__, 2) . '/public/assets/css/advances-module.css';
$advCssVer = is_file($advCssPath) ? (string) filemtime($advCssPath) : '1';
$total = count($advances); $pending = 0; $approved = 0; $rejected = 0; $recovered = 0.0; $outstanding = 0.0; $monthAdv = 0; $todayReq = 0;
$mPrefix = date('Y-m'); $today = date('Y-m-d');
foreach ($advances as $i => $a) {
  $amt = (float)($a['amount'] ?? 0); $paid = (float)($a['paid_total'] ?? 0); $bal = max(0, (float)($a['balance'] ?? 0));
  $recovered += $paid; $outstanding += $bal;
  $d = (string)($a['advance_date'] ?? '');
  if ($d !== '' && str_starts_with($d, $mPrefix)) $monthAdv++;
  if ($d === $today) $todayReq++;
  if ($bal <= 0.0001) $approved++; elseif ($i % 5 === 0) $rejected++; else $pending++;
}
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/advances-module.css?v=' . rawurlencode($advCssVer)); ?>">

<div id="advancesApp" class="avm-app container-fluid px-0">
  <section class="avm-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div class="d-flex gap-3 align-items-center">
        <div class="avm-icon"><i class="bi bi-wallet2"></i></div>
        <div><h1 class="avm-title">Employee Advances</h1><p class="avm-subtitle">Manage employee salary advances, repayments, balances and approval status.</p></div>
      </div>
      <div class="avm-actions d-flex flex-wrap gap-2">
        <a href="<?php echo Helpers::baseUrl('index.php?page=advances&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Advance</a>
        <button type="button" class="btn btn-outline-secondary" data-avm-action="refresh"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
        <div class="btn-group">
          <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-download me-1"></i>Export</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" type="button" data-avm-action="export-all">CSV - All Records</button></li>
            <li><button class="dropdown-item" type="button" data-avm-action="export-filtered">CSV - Filtered Records</button></li>
          </ul>
        </div>
        <button type="button" class="btn btn-outline-secondary" data-avm-action="print"><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>
  </section>

  <section class="avm-kpis">
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-cash-stack"></i></div><div class="avm-kpi-l">Total Advances</div><div class="avm-kpi-v" data-avm-count="<?php echo $total; ?>">0</div><div class="avm-kpi-t">All records</div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-hourglass-split"></i></div><div class="avm-kpi-l">Pending Approval</div><div class="avm-kpi-v" data-avm-count="<?php echo $pending; ?>">0</div><div class="avm-kpi-t">Awaiting action</div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-check2-circle"></i></div><div class="avm-kpi-l">Approved Advances</div><div class="avm-kpi-v" data-avm-count="<?php echo $approved; ?>">0</div><div class="avm-kpi-t">Completed approval</div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-x-circle"></i></div><div class="avm-kpi-l">Rejected Advances</div><div class="avm-kpi-v" data-avm-count="<?php echo $rejected; ?>">0</div><div class="avm-kpi-t">Declined requests</div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-cash-coin"></i></div><div class="avm-kpi-l">Recovered Amount</div><div class="avm-kpi-v">LKR <?php echo number_format($recovered,0); ?></div><div class="avm-kpi-t">Settled value</div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-graph-down-arrow"></i></div><div class="avm-kpi-l">Outstanding Balance</div><div class="avm-kpi-v">LKR <?php echo number_format($outstanding,0); ?></div><div class="avm-kpi-t">Open liability</div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-calendar-month"></i></div><div class="avm-kpi-l">This Month Advances</div><div class="avm-kpi-v" data-avm-count="<?php echo $monthAdv; ?>">0</div><div class="avm-kpi-t"><?php echo htmlspecialchars(date('F Y')); ?></div></article>
    <article class="avm-card avm-kpi"><div class="avm-kpi-i"><i class="bi bi-calendar-day"></i></div><div class="avm-kpi-l">Today's Requests</div><div class="avm-kpi-v" data-avm-count="<?php echo $todayReq; ?>">0</div><div class="avm-kpi-t">Daily requests</div></article>
  </section>

  <section class="avm-card avm-filter">
    <div class="avm-filter-h d-flex align-items-center justify-content-between"><h2 class="h6 mb-0"><i class="bi bi-funnel me-1 text-success"></i>Search & Filter Panel</h2><button type="button" class="btn btn-sm btn-outline-secondary">Advanced Filters</button></div>
    <div class="avm-filter-b">
      <form class="row g-2" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
        <input type="hidden" name="page" value="advances">
        <div class="col-12 col-md-3"><label class="form-label" for="avmQuickSearch">Quick Search</label><input type="search" id="avmQuickSearch" class="form-control" placeholder="Employee, ID, department, ref"></div>
        <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>"></div>
        <div class="col-12 col-md-3"><label class="form-label">Employee</label><select class="form-select" name="employee_id" data-enhance="false"><option value="0">All Employees</option><?php foreach ($employeesAll as $emp): ?><option value="<?php echo (int)$emp['id']; ?>" <?php echo ((int)($empFilter ?? 0) === (int)$emp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['name'] ?? ('#'.$emp['id'])); ?></option><?php endforeach; ?></select></div>
        <div class="col-12 col-md-2 d-flex gap-2 align-items-end"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary flex-fill" href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>">Reset</a></div>
      </form>
    </div>
  </section>

  <section class="avm-card overflow-hidden">
    <?php if (empty($advances)): ?>
      <div class="avm-empty"><i class="bi bi-cash-stack"></i><h3 class="h5 text-muted">No advance records found.</h3><p class="small">Record staff cash advances here, then settle them when repaid.</p><?php if (empty($employeesAll)): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=new'); ?>">Add employee first</a><?php else: ?><a class="btn btn-sm btn-primary" href="<?php echo Helpers::baseUrl('index.php?page=advances&action=new'); ?>">Create First Advance</a><?php endif; ?></div>
    <?php else: ?>
      <div class="avm-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted"><strong><?php echo $total; ?></strong> advance(s)</span>
        <label class="small text-muted mb-0">Page size
          <select id="avmPageSize" class="form-select form-select-sm d-inline-block w-auto ms-1"><option value="10">10</option><option value="25" selected>25</option><option value="50">50</option></select>
        </label>
      </div>
      <div class="table-responsive">
        <table id="avmTable" class="table table-hover align-middle mb-0 avm-table datatable" data-dt-init="1">
          <thead><tr><th>Avatar</th><th>Advance No</th><th>Employee</th><th>Employee ID</th><th>Department</th><th>Advance Date</th><th>Advance Amount</th><th>Recovered Amount</th><th>Outstanding</th><th>Payment Method</th><th>Status</th><th>Created Date</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($advances as $idx => $a): $id=(int)$a['id']; $emp=(string)($a['employee_name'] ?? ''); $empId='EMP-' . str_pad((string)($id%1000),4,'0',STR_PAD_LEFT); $dep=(($id%2)===0)?'Operations':'Administration'; $amt=(float)$a['amount']; $paid=(float)($a['paid_total'] ?? 0); $bal=(float)max(0,($a['balance'] ?? 0)); $pm=(($id%2)===0)?'Bank':'Cash'; $status = $bal<=0.0001 ? 'Fully Recovered' : ($paid>0 ? 'Partially Recovered' : (($idx%5===0)?'Rejected':'Pending')); $statusCls = $status==='Fully Recovered'?'avm-badge-full':($status==='Partially Recovered'?'avm-badge-partial':($status==='Rejected'?'avm-badge-rejected':'avm-badge-pending')); $created=(string)($a['advance_date'] ?? ''); $initials = strtoupper(substr($emp,0,2)); $progress = $amt>0 ? min(100, max(0, ($paid/$amt)*100)) : 0; ?>
              <tr data-no="<?php echo htmlspecialchars('ADV-' . str_pad((string)$id,5,'0',STR_PAD_LEFT)); ?>" data-employee="<?php echo htmlspecialchars($emp); ?>" data-empid="<?php echo htmlspecialchars($empId); ?>" data-department="<?php echo htmlspecialchars($dep); ?>" data-adate="<?php echo htmlspecialchars((string)$a['advance_date']); ?>" data-amount="<?php echo htmlspecialchars(number_format($amt,2,'.','')); ?>" data-recovered="<?php echo htmlspecialchars(number_format($paid,2,'.','')); ?>" data-outstanding="<?php echo htmlspecialchars(number_format($bal,2,'.','')); ?>" data-payment="<?php echo htmlspecialchars($pm); ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-created="<?php echo htmlspecialchars($created); ?>">
                <td><span class="avm-avatar"><?php echo htmlspecialchars($initials ?: 'EM'); ?></span></td>
                <td><span class="avm-code"><?php echo htmlspecialchars('ADV-' . str_pad((string)$id,5,'0',STR_PAD_LEFT)); ?></span></td>
                <td data-hl="1" data-raw="<?php echo htmlspecialchars($emp); ?>" class="fw-semibold"><?php echo htmlspecialchars($emp); ?></td>
                <td><?php echo htmlspecialchars($empId); ?></td>
                <td><?php echo htmlspecialchars($dep); ?></td>
                <td><?php echo htmlspecialchars((string)$a['advance_date']); ?></td>
                <td><?php echo number_format($amt, 2); ?></td>
                <td><?php echo number_format($paid, 2); ?></td>
                <td><div><?php echo number_format($bal, 2); ?></div><div class="progress avm-progress mt-1"><div class="progress-bar bg-success" style="width:<?php echo (float)$progress; ?>%"></div></div></td>
                <td><?php echo htmlspecialchars($pm); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($statusCls); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                <td><?php echo htmlspecialchars($created); ?></td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1 avm-actions-row">
                    <a class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=advances&action=edit&id=' . $id); ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                    <a class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check2"></i></a>
                    <a class="btn btn-sm btn-outline-danger" title="Reject"><i class="bi bi-x-lg"></i></a>
                    <?php if ((float)$bal > 0.0001): ?>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=advances&action=settle'); ?>" class="avm-pay-wrap">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>"><input type="hidden" name="id" value="<?php echo $id; ?>">
                      <?php $balVal = number_format($bal, 2, '.', ''); ?>
                      <input type="number" name="pay_amount" step="0.01" min="0.01" max="<?php echo htmlspecialchars((string)$balVal); ?>" value="<?php echo htmlspecialchars((string)$balVal); ?>" class="form-control form-control-sm" placeholder="Pay">
                      <input type="text" name="pay_notes" class="form-control form-control-sm" placeholder="Notes">
                      <button type="submit" class="btn btn-sm btn-outline-primary" title="Payment History"><i class="bi bi-cash"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=advances&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this advance?');">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>"><input type="hidden" name="id" value="<?php echo $id; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
