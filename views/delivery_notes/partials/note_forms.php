<?php
/** @var array $n */
/** @var string $from */
/** @var string $to */
/** @var string $q */
$id = (int) ($n['id'] ?? 0);
$vehFirst = explode(',', (string) ($n['vehicles'] ?? ''))[0] ?? '';
$csrf = Helpers::csrfToken();
$base = Helpers::baseUrl('');
?>
<form id="dn-edit-<?php echo $id; ?>" method="post" action="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=dn_update'); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars((string) ($n['delivery_date'] ?? '')); ?>">
  <input type="hidden" name="vehicle_no" value="<?php echo htmlspecialchars(trim($vehFirst)); ?>">
  <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
</form>
<form id="dn-disc-<?php echo $id; ?>" method="post" action="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=dn_update_discount'); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <input type="hidden" name="discount" value="<?php echo htmlspecialchars((string) ($n['discount'] ?? '0')); ?>">
  <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
</form>
<form id="dn-del-<?php echo $id; ?>" method="post" action="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=dn_delete'); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">
  <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
</form>
<?php if (Auth::canCollectPayments()): ?>
<form id="dn-pay-<?php echo $id; ?>" method="post" action="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=collect_payment'); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="delivery_note_id" value="<?php echo $id; ?>">
  <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
  <input type="hidden" name="amount" value="0">
</form>
<?php endif; ?>
