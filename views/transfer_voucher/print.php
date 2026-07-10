<?php
$voucher = $voucher ?? [];
$voucherNumber = (string) ($voucher['voucher_number'] ?? $voucher['voucher_no'] ?? '');
$items = $voucher['items'] ?? [];
$fromName = (string) ($voucher['from_account_name'] ?? '');
$toName = (string) ($voucher['to_account_name'] ?? '');
$reportDocTitle = 'Transfer Voucher ' . $voucherNumber;
$reportTitle = 'Transfer Voucher';
$reportMetaItems = [
    ['label' => 'Voucher No', 'value' => $voucherNumber],
    ['label' => 'Print Date', 'value' => date('d/m/Y H:i')],
    ['label' => 'Status', 'value' => (string)($voucher['status'] ?? '')],
    ['label' => 'Date', 'value' => (string)($voucher['voucher_date'] ?? '')],
    ['label' => 'Payment Mode', 'value' => (string)($voucher['payment_mode'] ?? '')],
];
$reportShowInfoPanel = false;
include __DIR__ . '/../partials/report/print_document_open.php';
?>
<style>
.tv-print { max-width: 900px; margin: 0 auto; }
.tv-box { border: 1px solid #E5E7EB; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
.tv-box .label { color: #6B7280; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.tv-box .value { font-size: 15px; font-weight: 700; color: #222; }
.tv-note { border: 1px solid #E5E7EB; border-radius: 8px; padding: 14px; min-height: 80px; white-space: pre-wrap; }
.tv-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 12px; }
.tv-totals { margin-top: 12px; }
.tv-total-row { display: flex; justify-content: space-between; padding: 8px 12px; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 6px; }
.tv-total-row .label { font-weight: 600; color: #6B7280; font-size: 11px; text-transform: uppercase; }
.tv-total-row .value { font-weight: 700; font-size: 15px; }
</style>

<div class="rpt-root">
  <article class="rpt-sheet tv-print">
    <div class="tv-actions no-print mb-2">
      <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print / Save PDF</button>
    </div>
    <?php include __DIR__ . '/../partials/report/letterhead.php'; ?>

  <?php if (!empty($items)): ?>
  <div class="rpt-table-wrap">
  <table class="table table-bordered rpt-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Account</th>
        <th class="text-end">Debit</th>
        <th class="text-end">Credit</th>
        <th>Narration</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $index => $item): ?>
      <tr>
        <td><?php echo $index + 1; ?></td>
        <td><?php echo htmlspecialchars((string) ($item['account_name'] ?? '')); ?></td>
        <td class="text-end"><?php echo (float) ($item['debit_amount'] ?? 0) > 0 ? number_format((float) $item['debit_amount'], 2) : '-'; ?></td>
        <td class="text-end"><?php echo (float) ($item['credit_amount'] ?? 0) > 0 ? number_format((float) $item['credit_amount'], 2) : '-'; ?></td>
        <td><?php echo htmlspecialchars((string) ($item['description'] ?? '')); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <div class="tv-totals">
    <div class="tv-total-row">
      <span class="label">Total Debit</span>
      <span class="value"><?php echo Helpers::formatMoney((float) ($voucher['total_debit'] ?? 0)); ?></span>
    </div>
    <div class="tv-total-row">
      <span class="label">Total Credit</span>
      <span class="value"><?php echo Helpers::formatMoney((float) ($voucher['total_credit'] ?? 0)); ?></span>
    </div>
    <div class="tv-total-row">
      <span class="label">Balance</span>
      <span class="value"><?php echo Helpers::formatMoney((float) ($voucher['balance_amount'] ?? 0)); ?></span>
    </div>
  </div>
  <?php else: ?>
  <div class="tv-grid">
    <div class="tv-box"><div class="label">From Account</div><div class="value"><?php echo htmlspecialchars($fromName); ?></div></div>
    <div class="tv-box"><div class="label">To Account</div><div class="value"><?php echo htmlspecialchars($toName); ?></div></div>
    <div class="tv-box"><div class="label">Amount</div><div class="value"><?php echo Helpers::formatMoney((float) ($voucher['amount'] ?? 0)); ?></div></div>
    <div class="tv-box"><div class="label">Reference</div><div class="value"><?php echo htmlspecialchars((string) ($voucher['reference_number'] ?? '')); ?></div></div>
  </div>
  <?php endif; ?>

  <div class="tv-box"><div class="label">Narration</div><div class="tv-note"><?php echo htmlspecialchars((string) ($voucher['narration'] ?? '')); ?></div></div>
  <div class="tv-grid">
    <div class="tv-box"><div class="label">Created By</div><div class="value"><?php echo htmlspecialchars((string) ($voucher['created_by_name'] ?? '')); ?></div></div>
    <div class="tv-box"><div class="label">Posted At</div><div class="value"><?php echo htmlspecialchars((string) ($voucher['posted_at'] ?? '')); ?></div></div>
  </div>

    <?php include __DIR__ . '/../partials/report/footer.php'; ?>
  </article>
</div>
<?php include __DIR__ . '/../partials/report/print_document_close.php'; ?>
