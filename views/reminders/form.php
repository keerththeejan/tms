<?php /** @var array $reminder */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo ($reminder['id'] ?? 0) ? 'Edit Reminder' : 'New Reminder'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=reminders&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)($reminder['id'] ?? 0); ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" required placeholder="e.g., Truck Insurance Renewal" value="<?php echo htmlspecialchars($reminder['title'] ?? ''); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Category</label>
        <?php $cat = trim((string)($reminder['category'] ?? '')); ?>
        <input type="text" name="category" class="form-control" list="remCatList" placeholder="e.g., Insurance" value="<?php echo htmlspecialchars($cat); ?>">
        <datalist id="remCatList">
          <option value="insurance">Insurance</option>
          <option value="tax">Tax</option>
          <option value="electricity">Electricity</option>
          <option value="license">License</option>
          <option value="rent">Rent</option>
          <option value="other">Other</option>
        </datalist>
      </div>
      <div class="col-md-3">
        <label class="form-label">Due Date</label>
        <input type="date" name="due_date" class="form-control" required value="<?php echo htmlspecialchars($reminder['due_date'] ?? date('Y-m-d')); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Repeat</label>
        <?php $rep = $reminder['repeat'] ?? $reminder['repeat_interval'] ?? 'none'; ?>
        <select name="repeat_interval" class="form-select">
          <?php $rOpts = ['none'=>'None','monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly']; foreach($rOpts as $k=>$v): ?>
            <option value="<?php echo $k; ?>" <?php echo ($rep===$k)?'selected':''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Repeat Every (days)</label>
        <input type="number" name="repeat_every_days" min="0" step="1" class="form-control" placeholder="e.g., 30" value="<?php echo htmlspecialchars((string)($reminder['repeat_every_days'] ?? '')); ?>">
        <div class="form-text">Optional. If set, this overrides the Repeat select.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Notify Before (days)</label>
        <input type="number" name="notify_before_days" min="0" step="1" class="form-control" value="<?php echo htmlspecialchars((string)($reminder['notify_days'] ?? $reminder['notify_before_days'] ?? 7)); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <?php $st = strtolower(trim((string)($reminder['status'] ?? 'open'))); ?>
        <select name="status" class="form-select" disabled>
          <option value="open" <?php echo ($st==='open')?'selected':''; ?>>Open</option>
          <option value="done" <?php echo ($st==='done')?'selected':''; ?>>Done</option>
        </select>
        <div class="form-text">Status can be changed from the list using Mark Done.</div>
      </div>
      <div class="col-12">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional note" value="<?php echo htmlspecialchars($reminder['notes'] ?? ''); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
