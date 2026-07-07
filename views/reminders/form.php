<?php /** @var array $reminder */ ?>
<?php
$reminder = is_array($reminder ?? null) ? $reminder : [];
$reminder += ['id'=>0,'title'=>'','category'=>'','due_date'=>date('Y-m-d'),'repeat_interval'=>'none','notify_before_days'=>7,'repeat_every_days'=>'','notes'=>''];
$rmdCssPath = dirname(__DIR__, 2) . '/public/assets/css/reminders-module.css';
$rmdCssVer = is_file($rmdCssPath) ? (string) filemtime($rmdCssPath) : '1';
$ridLabel = 'REM-' . str_pad((string)((int)$reminder['id']), 5, '0', STR_PAD_LEFT);
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/reminders-module.css?v=' . rawurlencode($rmdCssVer)); ?>">

<div id="remindersApp" class="rmd-app container-fluid px-0">
  <section class="rmd-hero mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div><h1 class="rmd-title mb-0"><?php echo ($reminder['id'] ?? 0) ? 'Edit Reminder' : 'New Reminder'; ?></h1><p class="rmd-subtitle">Manage reminder lifecycle, scheduling and recurrence from one place.</p></div>
      <a href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
  </section>

  <?php if (!empty($error)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <form id="rmdForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=save'); ?>" class="rmd-card">
        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
        <input type="hidden" name="id" value="<?php echo (int)($reminder['id'] ?? 0); ?>">

        <div class="rmd-form-sec">
          <div class="rmd-form-title">Section 1 - Reminder Information</div>
          <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Reminder Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required placeholder="e.g., Truck Insurance Renewal" value="<?php echo htmlspecialchars($reminder['title'] ?? ''); ?>"></div>
            <div class="col-md-4"><label class="form-label">Reminder ID</label><input class="form-control" value="<?php echo htmlspecialchars($ridLabel); ?>" readonly></div>
            <div class="col-md-4"><label class="form-label">Category</label><?php $cat = trim((string)($reminder['category'] ?? '')); ?><input type="text" name="category" class="form-control" list="remCatList" value="<?php echo htmlspecialchars($cat); ?>"><datalist id="remCatList"><option value="insurance">Insurance</option><option value="tax">Tax</option><option value="electricity">Electricity</option><option value="license">License</option><option value="rent">Rent</option><option value="other">Other</option></datalist></div>
            <div class="col-md-4"><label class="form-label">Priority</label><select class="form-select"><option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option></select></div>
            <div class="col-md-4"><label class="form-label">Status</label><?php $st = strtolower(trim((string)($reminder['status'] ?? 'open'))); ?><select class="form-select" disabled><option value="open" <?php echo ($st==='open')?'selected':''; ?>>Pending</option><option value="done" <?php echo ($st==='done')?'selected':''; ?>>Completed</option></select></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="2" placeholder="Reminder purpose"></textarea></div>
          </div>
        </div>

        <div class="rmd-form-sec">
          <div class="rmd-form-title">Section 2 - Scheduling</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Reminder Date</label><input type="date" name="due_date" class="form-control" required value="<?php echo htmlspecialchars($reminder['due_date'] ?? date('Y-m-d')); ?>"></div>
            <div class="col-md-4"><label class="form-label">Reminder Time</label><input type="time" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Due Time</label><input type="time" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Recurring</label><?php $rep = $reminder['repeat'] ?? $reminder['repeat_interval'] ?? 'none'; ?><select name="repeat_interval" class="form-select"><?php $rOpts = ['none'=>'None','monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly']; foreach($rOpts as $k=>$v): ?><option value="<?php echo $k; ?>" <?php echo ($rep===$k)?'selected':''; ?>><?php echo $v; ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Repeat Every (days)</label><input type="number" name="repeat_every_days" min="0" step="1" class="form-control" value="<?php echo htmlspecialchars((string)($reminder['repeat_every_days'] ?? '')); ?>"></div>
            <div class="col-md-4"><label class="form-label">Notify Before (days)</label><input type="number" name="notify_before_days" min="0" step="1" class="form-control" value="<?php echo htmlspecialchars((string)($reminder['notify_days'] ?? $reminder['notify_before_days'] ?? 7)); ?>"></div>
          </div>
        </div>

        <div class="rmd-form-sec">
          <div class="rmd-form-title">Section 3 - Assignment</div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Assigned User</label><input class="form-control" placeholder="UI only"></div>
            <div class="col-md-4"><label class="form-label">Department</label><input class="form-control" placeholder="UI only"></div>
            <div class="col-md-4"><label class="form-label">Branch</label><input class="form-control" placeholder="UI only"></div>
            <div class="col-md-6"><label class="form-label">Notification Method</label><select class="form-select"><option>System</option><option>Email</option><option>SMS</option></select></div>
          </div>
        </div>

        <div class="rmd-form-sec">
          <div class="rmd-form-title">Section 4 - Additional Information</div>
          <div class="row g-3">
            <div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($reminder['notes'] ?? ''); ?>"></div>
            <div class="col-12"><label class="form-label">Attachments</label><input type="file" class="form-control" disabled></div>
          </div>
        </div>

        <div class="rmd-form-sec d-flex justify-content-end gap-2">
          <a href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>" class="btn btn-outline-secondary">Cancel</a>
          <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>

    <div class="col-lg-4">
      <aside class="rmd-card rmd-summary p-3">
        <h2 class="h6 fw-bold mb-2"><i class="bi bi-layout-text-sidebar-reverse me-1 text-primary"></i>Reminder Summary</h2>
        <div class="rmd-summary-row"><span>Title</span><strong id="rmdSumTitle">—</strong></div>
        <div class="rmd-summary-row"><span>Assigned User</span><strong>User 1</strong></div>
        <div class="rmd-summary-row"><span>Priority</span><strong>Medium</strong></div>
        <div class="rmd-summary-row"><span>Status</span><strong id="rmdSumStatus">Pending</strong></div>
        <div class="rmd-summary-row"><span>Category</span><strong id="rmdSumCategory">—</strong></div>
        <div class="rmd-summary-row"><span>Due Date</span><strong id="rmdSumDue">—</strong></div>
        <div class="rmd-summary-row"><span>Recurring</span><strong id="rmdSumRepeat">none</strong></div>
        <div class="rmd-summary-row"><span>Time Remaining</span><strong id="rmdSumCountdown">—</strong></div>
        <div class="rmd-summary-row"><span>Notes</span><strong id="rmdSumNotes">—</strong></div>
        <div class="mt-3">
          <div class="small text-muted mb-1">Timeline</div>
          <div class="rmd-timeline">
            <div class="rmd-timeline-item">Created</div>
            <div class="rmd-timeline-item">Assigned</div>
            <div class="rmd-timeline-item">Reminder Triggered</div>
            <div class="rmd-timeline-item">Completed</div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>
