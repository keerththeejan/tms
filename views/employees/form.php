<?php
/** @var array $employee */
$cashbookAccountId = (int)($cashbookAccountId ?? 0);
$pageTitle = $employee['id'] ? 'Edit Employee' : 'New Employee';
$pageSubtitle = $employee['id']
  ? 'Refine profile details, permissions, and assignments without leaving the workspace.'
  : 'Create a polished employee profile with role, branch, and operational details in one pass.';
?>
<div class="hr-page emp-form emp-form--dashboard">
<section class="emp-form-hero emp-form-hero--sticky mb-0">
  <div class="hr-toolbar emp-form-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-2">
    <div class="emp-form-hero-copy d-flex flex-wrap align-items-center gap-2 gap-md-3 min-w-0">
      <span class="emp-form-kicker mb-0">HR</span>
      <h3 class="mb-0 emp-form-hero-title"><?php echo $pageTitle; ?></h3>
      <?php if ($cashbookAccountId > 0): ?>
        <span class="badge rounded-pill text-bg-success emp-acct-badge"><i class="bi bi-check2-circle" aria-hidden="true"></i> Linked</span>
      <?php endif; ?>
      <p class="emp-form-hero-text mb-0 d-none d-xl-block text-muted small"><?php echo htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="d-flex flex-wrap gap-1 gap-sm-2 align-items-center justify-content-end emp-form-hero-actions emp-form-hero-actions--toolbar flex-shrink-0">
      <?php if ($cashbookAccountId > 0): ?>
        <a class="btn btn-outline-primary btn-sm d-none d-md-inline-flex" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=accounts&tab=statement&account_id=' . $cashbookAccountId), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-bank me-1"></i> Account</a>
      <?php endif; ?>
      <a href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>" class="btn btn-outline-secondary btn-sm emp-form-back"><i class="bi bi-arrow-left"></i> Back</a>
      <button type="submit" form="empFormMain" class="btn btn-primary btn-sm emp-form-toolbar-save"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save</button>
    </div>
  </div>
</section>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="emp-form-layout">
<form id="empFormMain" method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=save'); ?>" class="card emp-form-card">
  <div class="card-body emp-form-card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">

    <section class="emp-form-section emp-form-panel" aria-labelledby="emp-sec-personal">
      <button type="button" class="emp-form-panel-toggle" data-bs-toggle="collapse" data-bs-target="#emp-panel-personal" aria-expanded="true" aria-controls="emp-panel-personal" id="emp-sec-personal">
        <span class="emp-form-section-title">Personal info</span>
        <i class="bi bi-chevron-down emp-form-panel-chevron" aria-hidden="true"></i>
      </button>
      <div id="emp-panel-personal" class="collapse show emp-form-panel-body">
      <div class="emp-form-grid emp-form-grid--dash">
        <div class="emp-form-field emp-form-field--code emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_code">Employee code</label>
          <input id="emp_inp_code" type="text" name="emp_code" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['emp_code'] ?? ''); ?>" placeholder="Optional" autocomplete="off" data-emp-track="code" data-emp-track-empty="Auto-generated">
        </div>
        <div class="emp-form-field emp-form-field--name-rest emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_name">Name <span class="text-danger" aria-hidden="true">*</span></label>
          <input id="emp_inp_name" type="text" name="name" class="form-control form-control-sm emp-form-control" required value="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>" autocomplete="name" data-emp-track="name" data-emp-required>
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_fn">First name</label>
          <input id="emp_inp_fn" type="text" name="first_name" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" autocomplete="given-name">
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_ln">Last name</label>
          <input id="emp_inp_ln" type="text" name="last_name" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" autocomplete="family-name">
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_email">Email</label>
          <input id="emp_inp_email" type="email" name="email" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" autocomplete="email" data-emp-track="email" data-emp-track-empty="Add email">
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_phone">Phone</label>
          <input id="emp_inp_phone" type="text" name="phone" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" inputmode="tel" autocomplete="tel">
        </div>
        <div class="emp-form-field emp-form-field--full emp-form-field--stack">
          <label class="form-label emp-form-label" for="emp_inp_addr">Address</label>
          <textarea id="emp_inp_addr" name="address" class="form-control form-control-sm emp-form-control emp-form-textarea" rows="2"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
        </div>
      </div>
      </div>
    </section>

    <div class="emp-form-divider" role="presentation"></div>

    <section class="emp-form-section emp-form-panel" aria-labelledby="emp-sec-work">
      <button type="button" class="emp-form-panel-toggle" data-bs-toggle="collapse" data-bs-target="#emp-panel-work" aria-expanded="true" aria-controls="emp-panel-work" id="emp-sec-work">
        <span class="emp-form-section-title">Work info</span>
        <i class="bi bi-chevron-down emp-form-panel-chevron" aria-hidden="true"></i>
      </button>
      <div id="emp-panel-work" class="collapse show emp-form-panel-body">
      <div class="emp-form-grid emp-form-grid--dash">
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_join">Join date</label>
          <input id="emp_inp_join" type="date" name="join_date" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['join_date'] ?? ''); ?>" data-emp-track="join" data-emp-track-empty="Not set">
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_position">Position <span class="text-danger" aria-hidden="true">*</span></label>
          <input id="emp_inp_position" type="text" name="position" class="form-control form-control-sm emp-form-control" required value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>" data-emp-track="position" data-emp-required>
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="empRoleSelect">Role</label>
          <div class="min-w-0">
            <select name="role" class="form-select form-select-sm emp-form-control" id="empRoleSelect" data-emp-track="role" data-emp-track-empty="Select role">
            <option value="">Select role</option>
            <?php 
              $builtin = ['admin'=>'Admin','manager'=>'Manager','driver'=>'Driver','clerk'=>'Clerk','mechanic'=>'Mechanic'];
              $curRole = (string)($employee['role'] ?? '');
              foreach ($builtin as $k=>$lbl): ?>
                <option value="<?php echo $k; ?>" <?php echo $curRole===$k?'selected':''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; 
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
              if ($curRole !== '' && !isset($rendered[$curRole])) { 
                $label = ucwords(str_replace('_',' ', $curRole)); ?>
                <option value="<?php echo htmlspecialchars($curRole); ?>" selected><?php echo htmlspecialchars($label); ?></option>
            <?php } ?>
          </select>
            <button type="button" class="btn btn-sm btn-outline-primary emp-form-inline-btn mt-1 py-0" data-bs-toggle="collapse" data-bs-target="#quickAddEmpRole" aria-expanded="false" aria-controls="quickAddEmpRole"><i class="bi bi-person-gear"></i> Quick add role</button>
          </div>
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_sel_branch">Branch <span class="text-danger" aria-hidden="true">*</span></label>
          <select id="emp_sel_branch" name="branch_id" class="form-select form-select-sm emp-form-control" required data-emp-track="branch" data-emp-required>
            <?php foreach (($branchesAll ?? []) as $b): ?>
              <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($employee['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="emp-form-field emp-form-field--full emp-form-field--stack emp-form-quickadd-slot">
          <div class="collapse mt-0" id="quickAddEmpRole">
            <div class="emp-form-quickadd">
              <div class="emp-form-quickadd-row">
                <input type="text" id="empRoleNew" class="form-control form-control-sm emp-form-control-sm" placeholder="e.g. supervisor or hub_manager">
                <button type="button" id="empRoleAdd" class="btn btn-sm btn-primary emp-form-quickadd-save"><i class="bi bi-plus-lg"></i> Add &amp; select</button>
              </div>
              <p class="emp-form-hint mb-0">Tip: use simple words or snake_case.</p>
            </div>
          </div>
        </div>
      </div>
      </div>
    </section>

    <div class="emp-form-divider" role="presentation"></div>

    <section class="emp-form-section emp-form-panel" aria-labelledby="emp-sec-more">
      <button type="button" class="emp-form-panel-toggle" data-bs-toggle="collapse" data-bs-target="#emp-panel-license" aria-expanded="true" aria-controls="emp-panel-license" id="emp-sec-more">
        <span class="emp-form-section-title">License &amp; vehicle</span>
        <i class="bi bi-chevron-down emp-form-panel-chevron" aria-hidden="true"></i>
      </button>
      <div id="emp-panel-license" class="collapse show emp-form-panel-body">
      <div class="emp-form-grid emp-form-grid--dash">
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_lic">License number</label>
          <input id="emp_inp_lic" type="text" name="license_number" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['license_number'] ?? ''); ?>">
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_inp_licexp">License expiry</label>
          <input id="emp_inp_licexp" type="date" name="license_expiry" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['license_expiry'] ?? ''); ?>">
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="<?php echo !empty($vehiclesAll) ? 'empVehicleSelect' : 'empVehicleInput'; ?>">Vehicle</label>
          <div class="min-w-0">
          <?php if (!empty($vehiclesAll)): ?>
            <select name="vehicle_id" id="empVehicleSelect" class="form-select form-select-sm emp-form-control" data-emp-track="vehicle" data-emp-track-empty="No vehicle">
              <option value="">None</option>
              <?php foreach ($vehiclesAll as $v): $vid = (int)($v['id'] ?? 0); $vno = trim((string)($v['vehicle_no'] ?? '')); ?>
                <option value="<?php echo $vid; ?>" <?php echo ((int)($employee['vehicle_id'] ?? 0) === $vid) ? 'selected' : ''; ?>><?php echo $vno!=='' ? htmlspecialchars($vno) : 'ID '.$vid; ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="text" name="vehicle_id" id="empVehicleInput" class="form-control form-control-sm emp-form-control" value="<?php echo htmlspecialchars($employee['vehicle_id'] ?? ''); ?>" data-emp-track="vehicle" data-emp-track-empty="No vehicle">
          <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-primary emp-form-inline-btn mt-1 py-0" data-bs-toggle="collapse" data-bs-target="#quickAddEmpVehicle" aria-expanded="false" aria-controls="quickAddEmpVehicle"><i class="bi bi-truck"></i> Quick add vehicle</button>
          </div>
        </div>
        <div class="emp-form-field emp-form-field--pair emp-form-field--inline">
          <label class="form-label emp-form-label" for="emp_sel_status">Status</label>
          <select id="emp_sel_status" name="status" class="form-select form-select-sm emp-form-control" data-emp-track="status" data-emp-track-empty="Active">
            <option value="active" <?php echo ($employee['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo ($employee['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="suspended" <?php echo ($employee['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
          </select>
        </div>
        <div class="emp-form-field emp-form-field--full emp-form-field--stack emp-form-quickadd-slot">
          <div class="collapse mt-0" id="quickAddEmpVehicle">
            <div class="emp-form-quickadd">
              <div class="emp-form-quickadd-row">
                <input type="text" id="empVehicleNo" class="form-control form-control-sm emp-form-control-sm" placeholder="Vehicle number (e.g. AB-1234)">
                <button type="button" id="empVehicleAdd" class="btn btn-sm btn-primary emp-form-quickadd-save"><i class="bi bi-save"></i> Save &amp; use</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </section>
  </div>
  <div class="card-footer emp-form-footer">
    <div class="emp-form-footer-copy">
      <span class="emp-form-footer-title">Draft</span>
      <p class="mb-0 small text-muted emp-form-footer-hint">Required fields marked *</p>
    </div>
    <div class="emp-form-footer-actions">
      <a href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>" class="btn btn-outline-secondary btn-sm emp-form-cancel">Cancel</a>
      <button type="submit" class="btn btn-primary btn-sm emp-form-submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save</button>
    </div>
  </div>
</form>

<aside class="emp-form-side" aria-label="Employee profile summary">
  <div class="emp-form-side-card emp-form-side-card--primary">
    <div class="emp-form-side-head">
      <span class="emp-form-side-kicker">Live preview</span>
      <span class="emp-form-side-status" id="empProfileCompletion">0% complete</span>
    </div>
    <div class="emp-form-identity">
      <div class="emp-form-avatar" aria-hidden="true">
        <i class="bi bi-person-badge"></i>
      </div>
      <div>
        <h5 class="mb-1" id="empPreviewName">Unnamed employee</h5>
        <p class="mb-0" id="empPreviewPosition">Add a position to complete the setup.</p>
      </div>
    </div>
    <div class="emp-form-progress" aria-hidden="true">
      <span class="emp-form-progress-bar" id="empProfileProgressBar"></span>
    </div>
    <dl class="emp-form-meta">
      <div>
        <dt>Role</dt>
        <dd id="empPreviewRole">Select role</dd>
      </div>
      <div>
        <dt>Branch</dt>
        <dd id="empPreviewBranch">Choose branch</dd>
      </div>
      <div>
        <dt>Status</dt>
        <dd id="empPreviewStatus">Active</dd>
      </div>
      <div>
        <dt>Employee code</dt>
        <dd id="empPreviewCode">Auto-generated</dd>
      </div>
      <div>
        <dt>Email</dt>
        <dd id="empPreviewEmail">Add email</dd>
      </div>
      <div>
        <dt>Join date</dt>
        <dd id="empPreviewJoin">Not set</dd>
      </div>
      <div>
        <dt>Vehicle</dt>
        <dd id="empPreviewVehicle">No vehicle</dd>
      </div>
    </dl>
  </div>

  <div class="emp-form-side-card">
    <span class="emp-form-side-kicker">Design notes</span>
    <ul class="emp-form-checklist mb-0">
      <li>Keep the name and position crisp so employee lists scan better later.</li>
      <li>Use quick add for new roles or vehicles without leaving the page.</li>
      <li>Branch and status drive downstream reporting, so they stay visible here.</li>
    </ul>
  </div>
</aside>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3 emp-toast-wrap" aria-live="polite" aria-atomic="true">
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
    roleSelect.dispatchEvent(new Event('change'));
    input.value = '';
    const c = document.getElementById('quickAddEmpRole'); if (c && window.bootstrap) new bootstrap.Collapse(c, {toggle:true});
  });

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
        inp.dispatchEvent(new Event('input'));
      }
      if (vInput) vInput.value = '';
      const c = document.getElementById('quickAddEmpVehicle'); if (c && window.bootstrap) new bootstrap.Collapse(c, {toggle:true});
    } catch (e) {
      alert('Failed to add vehicle');
    }
  });

})();
</script>
<script>
(function(){
  function getTrackedValue(field) {
    if (!field) return '';
    if (field.tagName === 'SELECT') {
      var selected = field.options[field.selectedIndex];
      if (!selected) return '';
      return (selected.textContent || '').trim();
    }
    return (field.value || '').trim();
  }

  function updatePreview() {
    var tracked = document.querySelectorAll('[data-emp-track]');
    var required = document.querySelectorAll('[data-emp-required]');
    var filledRequired = 0;

    required.forEach(function(field){
      if (getTrackedValue(field)) filledRequired += 1;
    });

    tracked.forEach(function(field){
      var key = field.getAttribute('data-emp-track');
      var target = document.getElementById('empPreview' + key.charAt(0).toUpperCase() + key.slice(1));
      if (!target) return;
      var value = getTrackedValue(field);
      target.textContent = value || field.getAttribute('data-emp-track-empty') || 'Not set';
    });

    var rawName = getTrackedValue(document.getElementById('emp_inp_name'));
    var rawPosition = getTrackedValue(document.getElementById('emp_inp_position'));
    var name = document.getElementById('empPreviewName');
    var position = document.getElementById('empPreviewPosition');
    if (name) name.textContent = rawName || 'Unnamed employee';
    if (position) position.textContent = rawPosition || 'Add a position to complete the setup.';

    var ratio = required.length ? Math.round((filledRequired / required.length) * 100) : 0;
    var completion = document.getElementById('empProfileCompletion');
    var progressBar = document.getElementById('empProfileProgressBar');
    if (completion) completion.textContent = ratio + '% complete';
    if (progressBar) progressBar.style.width = ratio + '%';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var tracked = document.querySelectorAll('[data-emp-track], [data-emp-required]');
    tracked.forEach(function(field){
      field.addEventListener('input', updatePreview);
      field.addEventListener('change', updatePreview);
    });
    updatePreview();
  });
})();
</script>
