<?php /** @var array $revenueByBranch */ /** @var array $parcelsBySupplier */ /** @var array $expenseSummary */ ?>
<h3 class="mb-3">Reports</h3>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="reports">
  <div class="col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <select name="branch_id" class="form-select">
      <option value="0">All Branches</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($branchId ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <select name="supplier_id" class="form-select">
      <option value="0">All Suppliers</option>
      <?php foreach (($suppliers ?? []) as $s): ?>
        <option value="<?php echo (int)$s['id']; ?>" <?php echo ((int)($supplierId ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
  </div>
</form>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6>Revenue by Branch</h6>
        <?php if (!$revenueByBranch): ?>
          <div class="text-muted">No data.</div>
        <?php else: ?>
          <ul class="mb-0">
            <?php foreach ($revenueByBranch as $r): ?>
              <li><?php echo htmlspecialchars($r['branch_name'] ?? ''); ?>: <strong><?php echo number_format((float)$r['revenue'], 2); ?></strong></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6>Parcels by Supplier</h6>
        <?php if (!$parcelsBySupplier): ?>
          <div class="text-muted">No data.</div>
        <?php else: ?>
          <ul class="mb-0">
            <?php foreach ($parcelsBySupplier as $r): ?>
              <li><?php echo htmlspecialchars($r['supplier_name'] ?? ''); ?>: <strong><?php echo (int)$r['parcels_count']; ?></strong> parcels, total price <?php echo number_format((float)$r['total_price'], 2); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6>Expenses Summary</h6>
        <?php if (!$expenseSummary): ?>
          <div class="text-muted">No data.</div>
        <?php else: ?>
          <ul class="mb-0">
            <?php foreach ($expenseSummary as $e): ?>
              <li><?php echo htmlspecialchars($e['expense_type']); ?>: <strong><?php echo number_format((float)$e['total'], 2); ?></strong></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
