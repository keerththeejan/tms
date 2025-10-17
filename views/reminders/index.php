<?php /** @var array $reminders */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Reminders</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Reminder</a>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
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
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>">Clear</a>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
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
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=reminders&action=edit&id='.(int)$r['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <?php if (($r['status'] ?? 'open')!=='done'): ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=mark_done'); ?>" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check2-circle"></i> Mark Done</button>
            </form>
            <?php endif; ?>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this reminder?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
