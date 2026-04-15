<?php
/** @var array $employee */
$cashbookAccountId = (int)($cashbookAccountId ?? 0);
?>
<div class="hr-page emp-form">
<div class="hr-toolbar emp-form-toolbar d-flex flex-column flex-lg-row justify-content-between align-items-stretch align-items-lg-center gap-2 mb-3">
  <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
    <h3 class="mb-0"><?php echo $employee['id'] ? 'Edit Employee' : 'New Employee'; ?></h3>
    <?php if ($cashbookAccountId > 0): ?>
      <span class="badge rounded-pill text-bg-success emp-acct-badge"><i class="bi bi-check2-circle" aria-hidden="true"></i> Account linked</span>
    <?php endif; ?>
  </div>
  <div class="d-flex flex-wrap gap-2 align-items-stretch justify-content-lg-end">
    <?php if ($cashbookAccountId > 0): ?>
      <a class="btn btn-outline-primary w-100 w-md-auto" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=accounts&tab=statement&account_id=' . $cashbookAccountId), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-bank me-1"></i> View account</a>
    <?php endif; ?>
    <a href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>" class="btn btn-outline-secondary w-100 w-sm-auto"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=save'); ?>" class="card emp-form-card">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Employee Code</label>
        <input type="text" name="emp_code" class="form-control" value="<?php echo htmlspecialchars($employee['emp_code'] ?? ''); ?>" placeholder="Optional employee code">
      </div>
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Position</label>
        <input type="text" name="position" class="form-control" required value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Role</span>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddEmpRole" aria-expanded="false"><i class="bi bi-person-gear"></i> Quick Add</button>
        </label>
        <select name="role" class="form-select" id="empRoleSelect">
          <option value="">Select Role</option>
          <?php 
            $builtin = ['admin'=>'Admin','manager'=>'Manager','driver'=>'Driver','clerk'=>'Clerk','mechanic'=>'Mechanic'];
            $curRole = (string)($employee['role'] ?? '');
            // Render built-ins
            foreach ($builtin as $k=>$lbl): ?>
              <option value="<?php echo $k; ?>" <?php echo $curRole===$k?'selected':''; ?>><?php echo $lbl; ?></option>
          <?php endforeach; 
            // Render dynamic roles from DB
            $rendered = array_fill_keys(array_keys($builtin), true);
            if (!empty($rolesDynamic) && is_array($rolesDynamic)) {
              foreach ($rolesDynamic as $r) {
                $rk = trim((string)($r['role'] ?? ''));
                if ($rk === '' || isset($rendered[$rk])) continue;
                $rendered[$rk] = true;
                $label = ucwords(str_replace('_',' ', $rk));
          ?>
                <option value="<?php echo htmlspecialchars($rk); ?>" <?php echo $curRole===$rk?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php 
              }
            }
            // If current is custom and not in the lists yet
            if ($curRole !== '' && !isset($rendered[$curRole])) { 
              $label = ucwords(str_replace('_',' ', $curRole)); ?>
              <option value="<?php echo htmlspecialchars($curRole); ?>" selected><?php echo htmlspecialchars($label); ?></option>
          <?php } ?>
        </select>
        <div class="collapse mt-2" id="quickAddEmpRole">
          <div class="border rounded p-2 bg-light">
            <div class="row g-2">
              <div class="col-8"><input type="text" id="empRoleNew" class="form-control form-control-sm" placeholder="e.g., supervisor or hub_manager"></div>
              <div class="col-4 text-end"><button type="button" id="empRoleAdd" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add & Select</button></div>
            </div>
            <div class="form-text">Tip: Use simple words or snake_case.</div>
          </div>
        </div>
      </div>
      <!-- Salary Amount removed as per request -->
      
      <div class="col-md-6">
        <label class="form-label">License Number</label>
        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($employee['license_number'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">License Expiry</label>
        <input type="date" name="license_expiry" class="form-control" value="<?php echo htmlspecialchars($employee['license_expiry'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Vehicle ID</span>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddEmpVehicle" aria-expanded="false"><i class="bi bi-truck"></i> Quick Add</button>
        </label>
        <?php if (!empty($vehiclesAll)): ?>
          <select name="vehicle_id" id="empVehicleSelect" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($vehiclesAll as $v): $vid = (int)($v['id'] ?? 0); $vno = trim((string)($v['vehicle_no'] ?? '')); ?>
              <option value="<?php echo $vid; ?>" <?php echo ((int)($employee['vehicle_id'] ?? 0) === $vid) ? 'selected' : ''; ?>><?php echo $vno!=='' ? htmlspecialchars($vno) : 'ID '.$vid; ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="text" name="vehicle_id" id="empVehicleInput" class="form-control" value="<?php echo htmlspecialchars($employee['vehicle_id'] ?? ''); ?>">
        <?php endif; ?>
        <div class="collapse mt-2" id="quickAddEmpVehicle">
          <div class="border rounded p-2 bg-light">
            <div class="row g-2">
              <div class="col-8"><input type="text" id="empVehicleNo" class="form-control form-control-sm" placeholder="Vehicle Number (e.g., AB-1234)"></div>
              <div class="col-4 text-end"><button type="button" id="empVehicleAdd" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select" required>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($employee['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Join Date</label>
        <input type="date" name="join_date" class="form-control" value="<?php echo htmlspecialchars($employee['join_date'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?php echo ($employee['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?php echo ($employee['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
          <option value="suspended" <?php echo ($employee['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button type="submit" class="btn btn-primary w-100 w-md-auto"><i class="bi bi-save"></i> Save</button>
  </div>
</form>

<div class="toast-container position-fixed top-0 end-0 p-3 emp-toast-wrap" style="z-index: 1085" aria-live="polite" aria-atomic="true">
  <div id="empSaveToast" class="toast align-items-center border-0 shadow" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="empSaveToastBody"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
</div><!-- /.hr-page -->
<?php if (!empty($error)): ?>
<script>
  // Show a popup alert for errors (e.g., duplicate email or employee code)
  (function(){
    try { alert(<?php echo json_encode((string)$error, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>); } catch (e) {}
  })();
</script>
<?php endif; ?>

<script>
(function(){
  document.addEventListener('DOMContentLoaded', function () {
    try {
      var params = new URLSearchParams(window.location.search);
      if (params.get('emp_ok') !== '1') return;
      var acct = params.get('acct') || '';
      var body = document.getElementById('empSaveToastBody');
      var toastEl = document.getElementById('empSaveToast');
      if (!body || !toastEl || typeof bootstrap === 'undefined' || !bootstrap.Toast) return;
      toastEl.classList.remove('text-bg-success', 'text-bg-info');
      if (acct === 'new') {
        body.innerHTML = '<strong>Employee saved.</strong><br><span class="small">Cash Book account created automatically.</span>';
        toastEl.classList.add('text-bg-success');
      } else {
        body.innerHTML = '<strong>Employee saved.</strong><br><span class="small">Account was already linked - no duplicate created.</span>';
        toastEl.classList.add('text-bg-info');
      }
      var t = new bootstrap.Toast(toastEl, { delay: 7000 });
      t.show();
      params.delete('emp_ok');
      params.delete('acct');
      var q = params.toString();
      var newUrl = window.location.pathname + (q ? '?' + q : '') + window.location.hash;
      window.history.replaceState({}, '', newUrl);
    } catch (e) {}
  });
})();
</script>
<script>
(function(){
  // Quick Add Role
  const roleBtn = document.getElementById('empRoleAdd');
  const roleSelect = document.getElementById('empRoleSelect');
  roleBtn?.addEventListener('click', function(){
    const input = document.getElementById('empRoleNew');
    if (!input || !roleSelect) return;
    const raw = (input.value || '').trim();
    if (!raw) { alert('Enter a role'); return; }
    let key = raw.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!key) key = raw.toLowerCase();
    let exists = false;
    Array.from(roleSelect.options).forEach(opt => { if ((opt.value||'') === key) exists = true; });
    if (!exists) {
      const opt = document.createElement('option');
      opt.value = key;
      opt.textContent = raw;
      roleSelect.appendChild(opt);
    }
    roleSelect.value = key;
    input.value = '';
    const c = document.getElementById('quickAddEmpRole'); if (c && window.bootstrap) new bootstrap.Collapse(c, {toggle:true});
  });

  // Quick Add Vehicle by Number via AJAX -> returns ID
  const vehBtn = document.getElementById('empVehicleAdd');
  vehBtn?.addEventListener('click', async function(){
    const vInput = document.getElementById('empVehicleNo');
    const v = (vInput?.value || '').trim();
    if (!v) { alert('Enter a vehicle number'); return; }
    try {
      const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('vehicle_no', v);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=vehicles&action=save'); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
        body: fd
      });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      const sel = document.getElementById('empVehicleSelect');
      const inp = document.getElementById('empVehicleInput');
      if (sel) {
        const idStr = String(data.id);
        const label = (data.vehicle_no || v) + ' (ID ' + idStr + ')';
        let exists = false;
        Array.from(sel.options).forEach(o=>{ if (String(o.value) === idStr) { o.textContent = label; exists = true; } });
        if (!exists) { const opt = document.createElement('option'); opt.value = idStr; opt.textContent = label; sel.appendChild(opt); }
        const wasDisabled = sel.disabled; if (wasDisabled) sel.disabled = false;
        sel.value = idStr;
        sel.dispatchEvent(new Event('change'));
        sel.dispatchEvent(new Event('input'));
        if (wasDisabled) sel.disabled = true;
      } else if (inp) {
        inp.value = String(data.id);
      }
      if (vInput) vInput.value = '';
      const c = document.getElementById('quickAddEmpVehicle'); if (c && window.bootstrap) new bootstrap.Collapse(c, {toggle:true});
    } catch (e) {
      alert('Failed to add vehicle');
    }
  });

  // No payroll calculations on this form (cash-related fields removed)
})();
</script>
