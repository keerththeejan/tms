<?php /** @var string $date */ /** @var array $payments */ /** @var array $expenses */ ?>
<div class="container-fluid px-0">
  <div class="row g-2 mb-2">
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-3">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 mb-3">
            <h3 class="h5 mb-0 fw-bold">Daybook</h3>
          </div>
<form class="row g-2 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="daybook">
  <div class="col-md-3"><label class="form-label small mb-1">Date</label><input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date ?? ''); ?>"></div>
  <div class="col-auto"><button type="submit" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><i class="bi bi-search" aria-hidden="true"></i><span>Filter</span></button></div>
</form>
        </div>
      </div>
    </div>
  </div>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
      <div class="card-header py-2 fw-semibold bg-white">Payments</div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>#</th><th>Time</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            <?php $i=0; foreach ($payments as $p): $i++; ?>
              <tr>
                <td><?php echo (int)$p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['paid_at']); ?></td>
                <td class="text-end"><?php echo number_format((float)$p['amount'], 2); ?></td>
              </tr>
            <?php endforeach; if ($i===0): ?>
              <tr><td colspan="3" class="text-center text-muted">No payments</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
      <div class="card-header py-2 fw-semibold bg-white">Expenses</div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>#</th><th>Date</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            <?php $i=0; foreach ($expenses as $e): $i++; ?>
              <tr>
                <td><?php echo (int)$e['id']; ?></td>
                <td><?php echo htmlspecialchars($e['paid_at']); ?></td>
                <td class="text-end"><?php echo number_format((float)$e['amount'], 2); ?></td>
              </tr>
            <?php endforeach; if ($i===0): ?>
              <tr><td colspan="3" class="text-center text-muted">No expenses</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
