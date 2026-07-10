<?php
/** @var array $n */
/** @var string $variant table|dropdown */
$variant = $variant ?? 'table';
$id = (int) ($n['id'] ?? 0);
$disc = (float) ($n['discount'] ?? 0);
$net = isset($n['net_total']) ? (float) $n['net_total'] : ((float) ($n['total_amount'] ?? 0) + $disc);
$paid = (float) ($n['paid'] ?? 0);
$due = max(0, $net - $paid);
$base = Helpers::baseUrl('');
?>

<?php if ($variant === 'dropdown'): ?>
<div class="dropdown w-100">
  <button class="btn btn-outline-secondary btn-sm w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for delivery note <?php echo $id; ?>">
    <i class="bi bi-three-dots-vertical me-1" aria-hidden="true"></i> Actions
  </button>
  <ul class="dropdown-menu dropdown-menu-end shadow-sm w-100">
    <li><button type="button" class="dropdown-item" data-dn-action="edit" data-dn-id="<?php echo $id; ?>"><i class="bi bi-pencil-square me-2"></i>Edit</button></li>
    <li><button type="button" class="dropdown-item" data-dn-action="discount" data-dn-id="<?php echo $id; ?>"><i class="bi bi-percent me-2"></i>Discount</button></li>
    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=view&id=' . $id); ?>"><i class="bi bi-eye me-2"></i>View</a></li>
    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=print&id=' . $id); ?>" target="_blank" rel="noopener"><i class="bi bi-printer me-2"></i>Print</a></li>
    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=email_form&id=' . $id); ?>"><i class="bi bi-envelope me-2"></i>Email</a></li>
    <?php if (Auth::canCollectPayments()): ?>
    <li><button type="button" class="dropdown-item" data-dn-action="pay" data-dn-id="<?php echo $id; ?>" data-dn-due="<?php echo htmlspecialchars(number_format($due, 2, '.', '')); ?>"><i class="bi bi-credit-card me-2"></i>Card Pay</button></li>
    <?php endif; ?>
    <li><hr class="dropdown-divider"></li>
    <li><button type="button" class="dropdown-item text-danger" data-dn-action="delete" data-dn-id="<?php echo $id; ?>"><i class="bi bi-trash me-2"></i>Delete</button></li>
  </ul>
</div>
<?php else: ?>
<div class="dn-actions-wrap" role="group" aria-label="Actions for delivery note <?php echo $id; ?>">
  <button type="button" class="btn btn-sm btn-outline-success" data-dn-action="edit" data-dn-id="<?php echo $id; ?>" title="Edit"><i class="bi bi-pencil-square"></i><span class="d-none d-xl-inline ms-1">Edit</span></button>
  <button type="button" class="btn btn-sm btn-outline-dark" data-dn-action="discount" data-dn-id="<?php echo $id; ?>" title="Discount"><i class="bi bi-percent"></i><span class="d-none d-xl-inline ms-1">Discount</span></button>
  <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=view&id=' . $id); ?>" title="View"><i class="bi bi-eye"></i><span class="d-none d-xl-inline ms-1">View</span></a>
  <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=print&id=' . $id); ?>" target="_blank" rel="noopener" title="Print"><i class="bi bi-printer"></i><span class="d-none d-xl-inline ms-1">Print</span></a>
  <a class="btn btn-sm btn-outline-info" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=email_form&id=' . $id); ?>" title="Email"><i class="bi bi-envelope"></i><span class="d-none d-xl-inline ms-1">Email</span></a>
  <?php if (Auth::canCollectPayments()): ?>
  <button type="button" class="btn btn-sm btn-warning" data-dn-action="pay" data-dn-id="<?php echo $id; ?>" data-dn-due="<?php echo htmlspecialchars(number_format($due, 2, '.', '')); ?>" title="Card Pay"><i class="bi bi-credit-card"></i><span class="d-none d-xl-inline ms-1">Pay</span></button>
  <?php endif; ?>
  <button type="button" class="btn btn-sm btn-outline-danger" data-dn-action="delete" data-dn-id="<?php echo $id; ?>" title="Delete"><i class="bi bi-trash"></i><span class="d-none d-xl-inline ms-1">Delete</span></button>
</div>
<?php endif; ?>
