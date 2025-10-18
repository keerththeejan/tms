<?php /** @var string $from */ /** @var string $to */ /** @var string $account */ /** @var array $pSeries */ /** @var array $eSeries */ /** @var float $openingBalance */ /** @var float $netMovement */ /** @var float $closingBalance */ /** @var float $totalPayments */ /** @var float $totalExpenses */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Account Ledger</h3>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="ledger">
  <div class="col-md-3"><label class="form-label small mb-1">From</label><input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>"></div>
  <div class="col-md-3"><label class="form-label small mb-1">To</label><input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>"></div>
  <div class="col-md-3">
    <label class="form-label small mb-1">Account</label>
    <select class="form-select" name="account">
      <option value="customers" <?php echo ($account==='customers')?'selected':''; ?>>Customers</option>
      <option value="suppliers" <?php echo ($account==='suppliers')?'selected':''; ?>>Suppliers</option>
    </select>
  </div>
  <div class="col-auto align-self-end"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button></div>
</form>
<div class="row g-3 mb-2">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row text-center">
          <div class="col-6 col-md-3 mb-2"> 
            <div class="text-muted small">Opening Balance</div>
            <?php $ob = (float)($openingBalance ?? 0); $obSign = $ob>=0 ? 'CR' : 'DR'; ?>
            <div class="h5 mb-0">Rs. <?php echo number_format(abs($ob), 2); ?> <span class="badge <?php echo $ob>=0?'text-bg-success':'text-bg-danger'; ?>"><?php echo $obSign; ?></span></div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="text-muted small">Collections (Period)</div>
            <div class="h5 mb-0">Rs. <?php echo number_format((float)($totalPayments ?? 0), 2); ?></div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="text-muted small">Expenses (Period)</div>
            <div class="h5 mb-0">Rs. <?php echo number_format((float)($totalExpenses ?? 0), 2); ?></div>
          </div>
          <div class="col-6 col-md-3 mb-2">
            <div class="text-muted small">Net Movement</div>
            <div class="h5 mb-0">Rs. <?php echo number_format((float)($netMovement ?? 0), 2); ?></div>
          </div>
        </div>
        <hr class="my-3">
        <?php $cb = (float)($closingBalance ?? 0); $cbSign = $cb>=0 ? 'CR' : 'DR'; ?>
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Closing Balance</div>
          <div class="h5 mb-0">Rs. <?php echo number_format(abs($cb), 2); ?> <span class="badge <?php echo $cb>=0?'text-bg-success':'text-bg-danger'; ?>"><?php echo $cbSign; ?></span></div>
        </div>
      </div>
    </div>
  </div>
  
</div>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header py-2 fw-semibold">Collections</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Date</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            <?php $tp=0; foreach ($pSeries as $r): $tp += (float)$r['s']; ?>
              <tr>
                <td><?php echo htmlspecialchars($r['d']); ?></td>
                <td class="text-end"><?php echo number_format((float)$r['s'], 2); ?></td>
              </tr>
            <?php endforeach; if (empty($pSeries)): ?>
              <tr><td colspan="2" class="text-center text-muted">No collections</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot><tr><th>Total</th><th class="text-end"><?php echo number_format($tp, 2); ?></th></tr></tfoot>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header py-2 fw-semibold">Expenses</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th>Date</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            <?php $te=0; foreach ($eSeries as $r): $te += (float)$r['s']; ?>
              <tr>
                <td><?php echo htmlspecialchars($r['d']); ?></td>
                <td class="text-end"><?php echo number_format((float)$r['s'], 2); ?></td>
              </tr>
            <?php endforeach; if (empty($eSeries)): ?>
              <tr><td colspan="2" class="text-center text-muted">No expenses</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot><tr><th>Total</th><th class="text-end"><?php echo number_format($te, 2); ?></th></tr></tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
