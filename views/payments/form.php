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
      <div class="col-md-2"><strong>Total</strong> <?php 
          $amountAfterDiscount = (float)$dn['total_amount'] - (float)($dn['discount'] ?? 0);
          echo number_format($amountAfterDiscount, 2); 
      ?></div>
      <div class="col-md-2"><strong>Discount</strong> <span class="text-danger"><?php echo number_format((float)($dn['discount'] ?? 0), 2); ?></span></div>
      <div class="col-md-2"><strong>After Discount</strong> <?php 
          $amountAfterDiscount = (float)$dn['total_amount'] - (float)($dn['discount'] ?? 0);
          echo number_format($amountAfterDiscount, 2); 
      ?></div>
      <div class="col-md-2"><strong>Paid</strong> <?php echo number_format((float)$dn['paid'],2); ?></div>
      <div class="col-md-2"><strong>Due</strong> <span class="text-danger"><?php 
          $dueAmount = max(0, $amountAfterDiscount - (float)$dn['paid']);
          echo number_format($dueAmount, 2); 
      ?></span></div>
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
        <?php
        $discount = (float)($dn['discount'] ?? 0);
        $total = (float)$dn['total_amount'];
        $paid = (float)$dn['paid'];
        $amountAfterDiscount = $total - $discount;
        $dueAmount = max(0, $amountAfterDiscount - $paid);
        // Ensure we don't allow paying more than the due amount
        $defaultAmount = $dueAmount > 0 ? $dueAmount : 0;
        // Ensure the amount doesn't exceed the discounted total minus any payments
        $maxAmount = max(0, $amountAfterDiscount - $paid);
        $defaultAmount = min($defaultAmount, $maxAmount);
        ?>
        <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo number_format($defaultAmount, 2, '.', ''); ?>" required>
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
