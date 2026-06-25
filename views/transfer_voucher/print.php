<?php
$voucher = $voucher ?? [];
$voucherNumber = (string) ($voucher['voucher_number'] ?? $voucher['voucher_no'] ?? '');
$items = $voucher['items'] ?? [];
$fromName = (string) ($voucher['from_account_name'] ?? '');
$toName = (string) ($voucher['to_account_name'] ?? '');
?>
<style>
@media print { .no-print { display: none !important; } body { background: #fff !important; } }
.tv-print { max-width: 900px; margin: 0 auto; padding: 24px; background: #fff; color: #111827; font-family: Arial, sans-serif; }
.tv-print-head { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #1f3a5f; padding-bottom: 16px; margin-bottom: 20px; }
.tv-print h1 { margin: 0; font-size: 28px; }
.tv-print .meta { color: #4b5563; font-size: 13px; }
.tv-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 18px; }
.tv-box { border: 1px solid #dbe4ef; border-radius: 12px; padding: 14px; }
.tv-box .label { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
.tv-box .value { font-size: 16px; font-weight: 700; }
.tv-note { border: 1px solid #dbe4ef; border-radius: 12px; padding: 14px; min-height: 120px; white-space: pre-wrap; }
.tv-actions { margin-bottom: 16px; }
.tv-items-table { width: 100%; border-collapse: collapse; margin: 18px 0; }
.tv-items-table th { background: #1f3a5f; color: white; padding: 10px; text-align: left; font-size: 12px; text-transform: uppercase; }
.tv-items-table td { border: 1px solid #dbe4ef; padding: 10px; font-size: 13px; }
.tv-items-table .text-right { text-align: right; }
.tv-totals { margin-top: 18px; }
.tv-total-row { display: flex; justify-content: space-between; padding: 10px 14px; border: 1px solid #dbe4ef; border-radius: 8px; margin-bottom: 8px; }
.tv-total-row .label { font-weight: 600; color: #6b7280; }
.tv-total-row .value { font-weight: 700; font-size: 16px; }
</style>

<div class="tv-print">
  <div class="tv-actions no-print">
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print / Save PDF</button>
  </div>
  <div class="tv-print-head">
    <div>
      <h1>Transfer Voucher</h1>
      <div class="meta">Voucher No: <?php echo htmlspecialchars($voucherNumber); ?></div>
    </div>
    <div class="meta text-end">
      <div>Status: <?php echo htmlspecialchars((string) ($voucher['status'] ?? '')); ?></div>
      <div>Date: <?php echo htmlspecialchars((string) ($voucher['voucher_date'] ?? '')); ?></div>
      <div>Payment Mode: <?php echo htmlspecialchars((string) ($voucher['payment_mode'] ?? '')); ?></div>
    </div>
  </div>

  <?php if (!empty($items)): ?>
  <table class="tv-items-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Account</th>
        <th class="text-right">Debit</th>
        <th class="text-right">Credit</th>
        <th>Narration</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $index => $item): ?>
      <tr>
        <td><?php echo $index + 1; ?></td>
        <td><?php echo htmlspecialchars((string) ($item['account_name'] ?? '')); ?></td>
        <td class="text-right"><?php echo (float) ($item['debit_amount'] ?? 0) > 0 ? number_format((float) $item['debit_amount'], 2) : '-'; ?></td>
        <td class="text-right"><?php echo (float) ($item['credit_amount'] ?? 0) > 0 ? number_format((float) $item['credit_amount'], 2) : '-'; ?></td>
        <td><?php echo htmlspecialchars((string) ($item['description'] ?? '')); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

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

  <div class="tv-box mb-3"><div class="label">Narration</div><div class="tv-note"><?php echo htmlspecialchars((string) ($voucher['narration'] ?? '')); ?></div></div>
  <div class="tv-grid">
    <div class="tv-box"><div class="label">Created By</div><div class="value"><?php echo htmlspecialchars((string) ($voucher['created_by_name'] ?? '')); ?></div></div>
    <div class="tv-box"><div class="label">Posted At</div><div class="value"><?php echo htmlspecialchars((string) ($voucher['posted_at'] ?? '')); ?></div></div>
  </div>
</div>