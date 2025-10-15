<?php /** @var array $dn */ ?>
<div class="container mt-3">
  <h4 class="mb-3">Email Log</h4>
  <div class="card">
    <div class="card-body">
      <div class="mb-2"><strong>Delivery Note:</strong> #<?php echo (int)($dn['id'] ?? 0); ?></div>
      <div class="mb-2"><strong>Customer:</strong> <?php echo htmlspecialchars((string)($dn['customer_name'] ?? '')); ?></div>
      <div class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars((string)($dn['customer_email'] ?? '')); ?></div>
      <div class="mb-2"><strong>Status:</strong>
        <?php $st = strtolower(trim((string)($dn['last_email_status'] ?? ''))); ?>
        <?php if ($st === 'sent'): ?>
          <span class="badge bg-success">Sent</span>
        <?php elseif ($st === 'failed'): ?>
          <span class="badge bg-danger">Failed</span>
        <?php else: ?>
          <span class="badge bg-secondary">Not sent</span>
        <?php endif; ?>
      </div>
      <div class="mb-2"><strong>Time:</strong> <?php echo htmlspecialchars((string)($dn['last_emailed_at'] ?? '')); ?></div>
      <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary">Back</a>
    </div>
  </div>
</div>
