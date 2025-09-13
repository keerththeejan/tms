<?php /** @var array $payment */ /** @var array $dn */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Collect Payment</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3"><strong>DN #</strong> <?php echo (int)$dn['id']; ?></div>
      <div class="col-md-3"><strong>Date</strong> <?php echo htmlspecialchars($dn['delivery_date']); ?></div>
      <div class="col-md-3"><strong>Customer</strong> <?php echo htmlspecialchars($dn['customer_name']); ?></div>
      <div class="col-md-3"><strong>Phone</strong> <?php echo htmlspecialchars($dn['customer_phone']); ?></div>
      <div class="col-md-3"><strong>Total</strong> <?php echo number_format((float)$dn['total_amount'],2); ?></div>
      <div class="col-md-3"><strong>Paid</strong> <?php echo number_format((float)$dn['paid'],2); ?></div>
      <div class="col-md-3"><strong>Due</strong> <span class="text-danger"><?php echo number_format(max(0,(float)$dn['total_amount']-(float)$dn['paid']),2); ?></span></div>
    </div>
  </div>
</div>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=payments&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="delivery_note_id" value="<?php echo (int)$payment['delivery_note_id']; ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo htmlspecialchars((string)$payment['amount']); ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Paid At</label>
        <input type="datetime-local" class="form-control" name="paid_at" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($payment['paid_at']))); ?>">
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-cash-coin"></i> Save Payment</button>
  </div>
</form>
