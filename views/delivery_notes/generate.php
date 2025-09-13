<?php /** @var array $customersAll */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Generate Delivery Note</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Customer</label>
        <select id="dnCustomer" name="customer_id" class="form-select" required>
          <option value="">-- Select Customer --</option>
          <?php foreach (($customersAll ?? []) as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'].' ('.$c['phone'].')'); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Delivery Date</label>
        <input id="dnDate" type="date" name="delivery_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
      <div class="col-md-2 text-end">
        <button class="btn btn-primary"><i class="bi bi-gear"></i> Generate</button>
      </div>
    </div>
    <div class="mt-3 p-3 border rounded bg-light">
      <div class="row">
        <div class="col-md-6"><strong>Customer:</strong> <span id="dnCustomerDisplay" class="text-muted">selected above</span></div>
        <div class="col-md-3"><strong>Date:</strong> <span id="dnDateDisplay"><?php echo date('Y-m-d'); ?></span></div>
        <div class="col-md-3"><strong>Branch:</strong> <span class="text-muted"><?php echo htmlspecialchars(Auth::user()['branch_name'] ?? ''); ?></span></div>
      </div>
    </div>
    <p class="text-muted mt-3 mb-0">All parcels for the selected customer and date (destined to your branch) will be grouped into one delivery note. Prices are summed automatically.</p>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-magic"></i> Generate</button>
  </div>
</form>
<script>
(function(){
  const sel = document.getElementById('dnCustomer');
  const dateInput = document.getElementById('dnDate');
  const custDisp = document.getElementById('dnCustomerDisplay');
  const dateDisp = document.getElementById('dnDateDisplay');
  function upd(){
    if (sel && custDisp) {
      const t = sel.options[sel.selectedIndex]?.text || 'selected above';
      custDisp.textContent = t;
    }
    if (dateInput && dateDisp) {
      dateDisp.textContent = dateInput.value || '';
    }
  }
  sel?.addEventListener('change', upd);
  dateInput?.addEventListener('input', upd);
  upd();
})();
</script>
