<div class="row g-3">
  <?php
  $reports = [
      ['action' => 'trial_balance', 'title' => 'Trial Balance', 'desc' => 'Debit/credit balances for all accounts', 'icon' => 'bi-balance-scale'],
      ['action' => 'profit_loss', 'title' => 'Profit & Loss', 'desc' => 'Income and expense statement', 'icon' => 'bi-graph-up-arrow'],
      ['action' => 'balance_sheet', 'title' => 'Balance Sheet', 'desc' => 'Assets, liabilities, and equity', 'icon' => 'bi-clipboard-data'],
      ['action' => 'ledger', 'title' => 'General Ledger', 'desc' => 'Account-wise transaction history', 'icon' => 'bi-journal-richtext'],
      ['action' => 'daybook', 'title' => 'Day Book', 'desc' => 'Chronological voucher entries', 'icon' => 'bi-calendar-day'],
      ['action' => 'cash_book', 'title' => 'Cash Book', 'desc' => 'Cash account movements', 'icon' => 'bi-wallet2'],
      ['action' => 'bank_book', 'title' => 'Bank Book', 'desc' => 'Bank account movements', 'icon' => 'bi-bank'],
      ['action' => 'customer_ledger', 'title' => 'Customer Statement', 'desc' => 'Receivable account ledger', 'icon' => 'bi-person-lines-fill'],
      ['action' => 'supplier_ledger', 'title' => 'Supplier Statement', 'desc' => 'Payable account ledger', 'icon' => 'bi-truck'],
      ['action' => 'vouchers', 'title' => 'Journal Report', 'desc' => 'Voucher register listing', 'icon' => 'bi-journal-text'],
  ];
  foreach ($reports as $report): ?>
    <div class="col-md-6 col-xl-4">
      <a class="acc-integration-card h-100" href="<?php echo htmlspecialchars(AccountingModule::url($report['action'])); ?>">
        <div class="d-flex align-items-start gap-3">
          <i class="bi <?php echo htmlspecialchars($report['icon']); ?>"></i>
          <div>
            <div class="fw-semibold"><?php echo htmlspecialchars($report['title']); ?></div>
            <div class="small text-muted"><?php echo htmlspecialchars($report['desc']); ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
