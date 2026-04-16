<?php /** @var array $reminders */ ?>
<div class="container-fluid px-0">
  <div class="row g-2">
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-3">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
            <h3 class="h5 mb-0 fw-bold">Reminders</h3>
            <a href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=new'); ?>" class="btn btn-primary d-inline-flex align-items-center gap-1"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>New reminder</span></a>
          </div>
<form class="row g-2" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="reminders">
  <div class="col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="category">
      <?php $catv = $cat ?? ''; ?>
      <option value="" <?php echo ($catv==='')?'selected':''; ?>>Category (any)</option>
      <?php $opts = ['insurance'=>'Insurance','tax'=>'Tax','electricity'=>'Electricity','license'=>'License','rent'=>'Rent','other'=>'Other']; foreach ($opts as $k=>$v): ?>
        <option value="<?php echo $k; ?>" <?php echo ($catv===$k)?'selected':''; ?>><?php echo $v; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <select class="form-select" name="status">
      <?php $st = $status ?? ''; ?>
      <option value="" <?php echo ($st==='')?'selected':''; ?>>Status (any)</option>
      <option value="open" <?php echo ($st==='open')?'selected':''; ?>>Open</option>
      <option value="done" <?php echo ($st==='done')?'selected':''; ?>>Done</option>
    </select>
  </div>
  <div class="col-auto d-flex gap-2">
    <button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><i class="bi bi-search" aria-hidden="true"></i><span>Filter</span></button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>">Clear</a>
  </div>
</form>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
        <div class="card-body p-0">
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle mb-0">
    <thead>
      <tr>
        <th>#</th>
        <th>Due Date</th>
        <th>Title</th>
        <th>Category</th>
        <th>Notify</th>
        <th>Status</th>
        <th>Notes</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reminders as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td>
            <?php 
              $due = $r['due_date']; $dd = strtotime((string)$due);
              $daysLeft = is_numeric($dd) ? floor((strtotime(date('Y-m-d')) - $dd)/86400) : 0;
              $overdue = ($r['status'] ?? 'open')==='open' && $dd && $dd < strtotime(date('Y-m-d'));
              $cls = $overdue ? 'badge bg-danger' : 'badge bg-secondary';
            ?>
            <?php echo htmlspecialchars($r['due_date']); ?>
            <?php if (($r['status'] ?? 'open')==='open'): ?><span class="<?php echo $cls; ?> ms-1"><?php echo $overdue ? 'Overdue' : 'Due'; ?></span><?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($r['title'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars(ucwords((string)($r['category'] ?? ''))); ?></td>
          <td><?php echo (int)($r['notify_before_days'] ?? 7); ?> days</td>
          <td><?php echo ($r['status'] ?? 'open')==='done' ? '<span class="badge bg-success">Done</span>' : '<span class="badge bg-warning text-dark">Open</span>'; ?></td>
          <td><?php echo htmlspecialchars($r['notes'] ?? ''); ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=edit&id='.(int)$r['id']); ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span></a>
            <?php if (($r['status'] ?? 'open')!=='done'): ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=mark_done'); ?>" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Mark done</span></button>
            </form>
            <?php endif; ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this reminder?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"><i class="bi bi-trash" aria-hidden="true"></i><span>Delete</span></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
        </div>
      </div>
    </div>
  </div>
</div>
