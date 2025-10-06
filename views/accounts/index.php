<?php /** @var string $from */ /** @var string $to */ /** @var float $totalPayments */ /** @var float $totalExpenses */ /** @var int $branchId */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Accounts Summary</h3>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="accounts">
  <div class="col-md-3"><label class="form-label small mb-1">From</label><input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>"></div>
  <div class="col-md-3"><label class="form-label small mb-1">To</label><input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>"></div>
  <div class="col-auto align-self-end"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button></div>
</form>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Collections</div>
            <div class="fs-4 fw-semibold"><?php echo number_format((float)($totalPayments ?? 0), 2); ?></div>
          </div>
          <i class="bi bi-cash-coin fs-2 text-success"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Expenses</div>
            <div class="fs-4 fw-semibold"><?php echo number_format((float)($totalExpenses ?? 0), 2); ?></div>
          </div>
          <i class="bi bi-wallet2 fs-2 text-danger"></i>
        </div>
      </div>
    </div>
  </div>
</div>
