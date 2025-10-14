<?php /** @var array $prefill */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Email Delivery Note</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=email_send'); ?>" class="row g-3">
  <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
  <input type="hidden" name="id" value="<?php echo (int)($prefill['id'] ?? 0); ?>">
  <div class="col-md-6">
    <label class="form-label">To Email</label>
    <input type="email" name="to_email" class="form-control" value="<?php echo htmlspecialchars($prefill['to_email'] ?? ''); ?>" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">To Name</label>
    <input type="text" name="to_name" class="form-control" value="<?php echo htmlspecialchars($prefill['to_name'] ?? ''); ?>">
  </div>
  <div class="col-12">
    <label class="form-label">Subject</label>
    <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($prefill['subject'] ?? ''); ?>" required>
  </div>
  <div class="col-12">
    <label class="form-label">Message (HTML)</label>
    <textarea name="html" class="form-control" rows="12" required><?php echo htmlspecialchars($prefill['html'] ?? ''); ?></textarea>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Send Email</button>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
