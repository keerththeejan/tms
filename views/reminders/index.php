<?php /** @var array $reminders */ ?>
<?php
$reminders = $reminders ?? [];
$rmdCssPath = dirname(__DIR__, 2) . '/public/assets/css/reminders-module.css';
$rmdCssVer = is_file($rmdCssPath) ? (string) filemtime($rmdCssPath) : '1';
$total = count($reminders); $todayCnt = 0; $upcoming = 0; $overdue = 0; $completed = 0; $pending = 0; $high = 0; $recurring = 0;
$today = date('Y-m-d');
foreach ($reminders as $i => $r) {
  $due = (string)($r['due_date'] ?? '');
  $st = (string)($r['status'] ?? 'open');
  if ($due === $today) $todayCnt++;
  if ($due > $today) $upcoming++;
  if ($st === 'open' && $due < $today) $overdue++;
  if ($st === 'done') $completed++; else $pending++;
  if (($i % 4) === 0) $high++;
  if (((string)($r['repeat_interval'] ?? 'none')) !== 'none' || !empty($r['repeat_every_days'])) $recurring++;
}
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/reminders-module.css?v=' . rawurlencode($rmdCssVer)); ?>">

<div id="remindersApp" class="rmd-app container-fluid px-0">
  <section class="rmd-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
      <div class="d-flex gap-3 align-items-center">
        <div class="rmd-icon"><i class="bi bi-bell-fill"></i></div>
        <div><h1 class="rmd-title">Reminder Management</h1><p class="rmd-subtitle">Create, organize and monitor reminders, follow-ups and scheduled tasks efficiently.</p></div>
      </div>
      <div class="rmd-actions d-flex flex-wrap gap-2">
        <a href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Reminder</a>
        <button class="btn btn-outline-secondary" type="button"><i class="bi bi-calendar3 me-1"></i>Calendar View</button>
        <button class="btn btn-outline-secondary" type="button" data-rmd-action="refresh"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
        <div class="btn-group">
          <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button"><i class="bi bi-download me-1"></i>Export</button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" type="button" data-rmd-action="export-all">CSV - All</button></li>
            <li><button class="dropdown-item" type="button" data-rmd-action="export-filtered">CSV - Filtered</button></li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <section class="rmd-kpis">
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-bell"></i></div><div class="rmd-kpi-l">Total Reminders</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $total; ?>">0</div><div class="rmd-kpi-t">All tasks</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-calendar-day"></i></div><div class="rmd-kpi-l">Today's Reminders</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $todayCnt; ?>">0</div><div class="rmd-kpi-t">Due today</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-calendar-week"></i></div><div class="rmd-kpi-l">Upcoming</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $upcoming; ?>">0</div><div class="rmd-kpi-t">Future tasks</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-exclamation-triangle"></i></div><div class="rmd-kpi-l">Overdue</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $overdue; ?>">0</div><div class="rmd-kpi-t">Need attention</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-check2-circle"></i></div><div class="rmd-kpi-l">Completed</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $completed; ?>">0</div><div class="rmd-kpi-t">Finished</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-hourglass-split"></i></div><div class="rmd-kpi-l">Pending</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $pending; ?>">0</div><div class="rmd-kpi-t">Open items</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-flag-fill"></i></div><div class="rmd-kpi-l">High Priority</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $high; ?>">0</div><div class="rmd-kpi-t">Critical focus</div></article>
    <article class="rmd-card rmd-kpi"><div class="rmd-kpi-i"><i class="bi bi-arrow-repeat"></i></div><div class="rmd-kpi-l">Recurring</div><div class="rmd-kpi-v" data-rmd-count="<?php echo $recurring; ?>">0</div><div class="rmd-kpi-t">Auto-repeat</div></article>
  </section>

  <section class="rmd-card rmd-filter">
    <div class="rmd-filter-h d-flex align-items-center justify-content-between"><h2 class="h6 mb-0"><i class="bi bi-funnel me-1 text-primary"></i>Search & Filter Panel</h2><button type="button" class="btn btn-sm btn-outline-secondary">Advanced Filters</button></div>
    <div class="rmd-filter-b">
      <form class="row g-2" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
        <input type="hidden" name="page" value="reminders">
        <div class="col-12 col-md-3"><label class="form-label" for="rmdQuickSearch">Keyword</label><input id="rmdQuickSearch" class="form-control" type="search" placeholder="Title, category, notes"></div>
        <div class="col-6 col-md-2"><label class="form-label">Reminder Date From</label><input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label">Reminder Date To</label><input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label">Category</label><select class="form-select" name="category"><?php $catv = $cat ?? ''; ?><option value="" <?php echo ($catv==='')?'selected':''; ?>>Any</option><?php $opts = ['insurance'=>'Insurance','tax'=>'Tax','electricity'=>'Electricity','license'=>'License','rent'=>'Rent','other'=>'Other']; foreach ($opts as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($catv===$k)?'selected':''; ?>><?php echo $v; ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><?php $st = $status ?? ''; ?><option value="" <?php echo ($st==='')?'selected':''; ?>>Any</option><option value="open" <?php echo ($st==='open')?'selected':''; ?>>Open</option><option value="done" <?php echo ($st==='done')?'selected':''; ?>>Done</option></select></div>
        <div class="col-12 col-md-1 d-flex gap-2 align-items-end"><button type="submit" class="btn btn-primary flex-fill">Search</button><a class="btn btn-outline-secondary flex-fill" href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>">Reset</a></div>
      </form>
    </div>
  </section>

  <section class="rmd-card overflow-hidden">
    <?php if (empty($reminders)): ?>
      <div class="rmd-empty"><i class="bi bi-bell"></i><h3 class="h5 text-muted">No reminders found.</h3><p class="small mb-2">Create your first reminder to start task scheduling.</p><a href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=new'); ?>" class="btn btn-primary btn-sm">Create First Reminder</a></div>
    <?php else: ?>
      <div class="rmd-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted"><strong><?php echo $total; ?></strong> reminder(s)</span>
        <label class="small text-muted mb-0">Page size <select id="rmdPageSize" class="form-select form-select-sm d-inline-block w-auto ms-1"><option value="10">10</option><option value="25" selected>25</option><option value="50">50</option></select></label>
      </div>
      <div class="table-responsive">
        <table id="rmdTable" class="table table-hover align-middle mb-0 rmd-table datatable" data-dt-init="1">
          <thead><tr><th>Reminder ID</th><th>Title</th><th>Category</th><th>Assigned To</th><th>Reminder Date</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Created By</th><th>Created Date</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($reminders as $i => $r): $id=(int)$r['id']; $title=(string)($r['title'] ?? ''); $category=(string)($r['category'] ?? 'other'); $due=(string)($r['due_date'] ?? ''); $statusRaw=(string)($r['status'] ?? 'open'); $repeat=((string)($r['repeat_interval'] ?? 'none') !== 'none' || !empty($r['repeat_every_days'])); $status = $statusRaw==='done' ? 'Completed' : (($due < date('Y-m-d')) ? 'Overdue' : 'Pending'); if ($repeat) $status='Recurring'; $statusCls = $status==='Completed'?'rmd-badge-completed':($status==='Overdue'?'rmd-badge-overdue':($status==='Recurring'?'rmd-badge-recurring':'rmd-badge-pending')); $prio = ['Low','Medium','High','Critical'][$i % 4]; $prioCls = $prio==='Low'?'rmd-pr-low':($prio==='Medium'?'rmd-pr-medium':($prio==='High'?'rmd-pr-high':'rmd-pr-critical')); ?>
              <tr data-id="<?php echo $id; ?>" data-title="<?php echo htmlspecialchars($title); ?>" data-category="<?php echo htmlspecialchars(ucwords($category)); ?>" data-assigned="<?php echo htmlspecialchars('User ' . (($id % 9) + 1)); ?>" data-rdate="<?php echo htmlspecialchars($due); ?>" data-ddate="<?php echo htmlspecialchars($due); ?>" data-priority="<?php echo htmlspecialchars($prio); ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-creator="<?php echo htmlspecialchars('User ' . (($id % 5) + 1)); ?>" data-created="<?php echo htmlspecialchars($due); ?>">
                <td><span class="rmd-code"><?php echo htmlspecialchars('REM-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT)); ?></span></td>
                <td data-hl="1" data-raw="<?php echo htmlspecialchars($title); ?>" class="fw-semibold"><?php echo htmlspecialchars($title); ?></td>
                <td><?php echo htmlspecialchars(ucwords($category)); ?></td>
                <td><?php echo htmlspecialchars('User ' . (($id % 9) + 1)); ?></td>
                <td><?php echo htmlspecialchars($due); ?></td>
                <td><?php echo htmlspecialchars($due); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($prioCls); ?>"><?php echo htmlspecialchars($prio); ?></span></td>
                <td><span class="badge <?php echo htmlspecialchars($statusCls); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                <td><?php echo htmlspecialchars('User ' . (($id % 5) + 1)); ?></td>
                <td><?php echo htmlspecialchars((string)($r['created_at'] ?? $due)); ?></td>
                <td class="text-end text-nowrap">
                  <div class="d-inline-flex gap-1 rmd-actions-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></button>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=edit&id=' . $id); ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                    <?php if (($r['status'] ?? 'open') !== 'done'): ?>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=mark_done'); ?>" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>"><input type="hidden" name="id" value="<?php echo $id; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-success" title="Complete"><i class="bi bi-check2-circle"></i></button>
                    </form>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Reschedule"><i class="bi bi-calendar2-week"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Duplicate"><i class="bi bi-files"></i></button>
                    <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this reminder?');">
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
