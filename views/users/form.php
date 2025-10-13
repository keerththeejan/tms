<?php /** @var array $userRow */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $userRow['id'] ? 'Edit User' : 'New User'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=users&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$userRow['id']; ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($userRow['username']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($userRow['full_name']); ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Role</span>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddRole" aria-expanded="false"><i class="bi bi-person-gear"></i> Quick Add</button>
        </label>
        <select name="role" class="form-select">
          <?php 
            // Built-in roles
            $roles = ['admin'=>'Admin','accountant'=>'Accountant','cashier'=>'Cashier','collector'=>'Due Collector','parcel_user'=>'Parcel User','staff'=>'Staff'];
            $currentRole = (string)($userRow['role'] ?? '');
            // None option first
            echo '<option value="" ' . ($currentRole === '' ? 'selected' : '') . '>-- None --</option>';
            // Render built-ins
            foreach ($roles as $k=>$label): ?>
              <option value="<?php echo $k; ?>" <?php echo $currentRole===$k?'selected':''; ?>><?php echo $label; ?></option>
            <?php endforeach; 
            // Render dynamic roles coming from DB (users table)
            $renderedKeys = array_fill_keys(array_keys($roles), true);
            if (!empty($rolesDynamic) && is_array($rolesDynamic)) {
              foreach ($rolesDynamic as $r) {
                $rk = trim((string)($r['role'] ?? ''));
                if ($rk === '' || isset($renderedKeys[$rk])) { continue; }
                $renderedKeys[$rk] = true;
                $label = ucwords(str_replace('_',' ', $rk));
            ?>
                <option value="<?php echo htmlspecialchars($rk); ?>" <?php echo $currentRole===$rk?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php 
              }
            }
            // In case current role is custom and not in dynamic list yet
            if ($currentRole !== '' && !isset($renderedKeys[$currentRole])) { 
              $label = ucwords(str_replace('_',' ', $currentRole)); ?>
              <option value="<?php echo htmlspecialchars($currentRole); ?>" selected><?php echo htmlspecialchars($label); ?></option>
            <?php } ?>
        </select>
        <div class="collapse mt-2" id="quickAddRole">
          <div class="border rounded p-2 bg-light">
            <div class="row g-2">
              <div class="col-8"><input type="text" id="ur_name" class="form-control form-control-sm" placeholder="e.g., Supervisor or hub_manager"></div>
              <div class="col-4 text-end"><button type="button" id="ur_submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add & Select</button></div>
            </div>
            <div class="form-text">Tip: Use simple words or snake_case for the role key.</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Branch (optional)</span>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddUserBranch" aria-expanded="false"><i class="bi bi-building-add"></i> Quick Add</button>
        </label>
        <select name="branch_id" class="form-select">
          <option value="0">-- None --</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($userRow['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
        <div class="collapse mt-2" id="quickAddUserBranch">
          <div class="border rounded p-2 bg-light">
            <div class="row g-2">
              <div class="col-6"><input type="text" id="ub_name" class="form-control form-control-sm" placeholder="Branch name"></div>
              <div class="col-4"><input type="text" id="ub_code" class="form-control form-control-sm" placeholder="Code"></div>
              <div class="col-2 d-flex align-items-center"><div class="form-check"><input id="ub_main" class="form-check-input" type="checkbox"> <label class="form-check-label" for="ub_main">Main</label></div></div>
              <div class="col-12 text-end"><button type="button" id="ub_submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Password <?php echo $userRow['id'] ? '(leave blank to keep)' : ''; ?></label>
        <input type="password" name="password" class="form-control" <?php echo $userRow['id']? '' : 'required'; ?> autocomplete="new-password">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="active" id="activeChk" value="1" <?php echo ((int)($userRow['active'] ?? 1) === 1) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="activeChk">Active</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
<script>
(function(){
  const btn = document.getElementById('ub_submit');
  const select = document.querySelector('select[name="branch_id"]');
  const roleBtn = document.getElementById('ur_submit');
  const roleSelect = document.querySelector('select[name="role"]');
  async function quickAdd(name, code, isMain){
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    const form = new FormData();
    form.append('csrf_token', csrf);
    form.append('ajax', '1');
    form.append('id', '0');
    form.append('name', name);
    form.append('code', code);
    if (isMain) form.append('is_main', '1');
    const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=branches&action=save'); ?>', {
      method: 'POST',
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
      body: form
    });
    if (!res.ok) throw new Error('Failed');
    const data = await res.json();
    if (!data || !data.id) throw new Error('Invalid response');
    return { id: data.id, name: data.name || name };
  }
  btn?.addEventListener('click', async function(){
    const nameEl = document.getElementById('ub_name');
    const codeEl = document.getElementById('ub_code');
    const mainEl = document.getElementById('ub_main');
    const name = nameEl?.value.trim();
    const code = codeEl?.value.trim();
    const isMain = !!mainEl?.checked;
    if (!name || !code) { alert('Name and Code are required.'); return; }
    try {
      const b = await quickAdd(name, code, isMain);
      if (select) {
        const idStr = String(b.id);
        const label = String(b.name || name);
        let exists = false;
        Array.from(select.options).forEach(o=>{ if (String(o.value) === idStr) { o.textContent = label; exists = true; } });
        if (!exists) { const opt = document.createElement('option'); opt.value = idStr; opt.textContent = label; select.appendChild(opt); }
        const wasDisabled = select.disabled; if (wasDisabled) select.disabled = false;
        select.value = idStr;
        select.dispatchEvent(new Event('change'));
        select.dispatchEvent(new Event('input'));
        if (wasDisabled) select.disabled = true;
      }
      if (nameEl) nameEl.value=''; if (codeEl) codeEl.value=''; if (mainEl) mainEl.checked=false;
      const collapseEl = document.getElementById('quickAddUserBranch'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
    } catch(e){ alert('Failed to add branch.'); }
  });

  // Quick Add Role: just add option to dropdown and select it
  roleBtn?.addEventListener('click', function(){
    const input = document.getElementById('ur_name');
    if (!input || !roleSelect) return;
    const raw = (input.value || '').trim();
    if (!raw) { alert('Enter a role name'); return; }
    let key = raw.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!key) key = raw.toLowerCase();
    let exists = false;
    Array.from(roleSelect.options).forEach(opt => { if (opt.value === key) exists = true; });
    if (!exists) {
      const opt = document.createElement('option');
      opt.value = key;
      opt.textContent = raw;
      roleSelect.appendChild(opt);
    }
    const wasDisabled = roleSelect.disabled; if (wasDisabled) roleSelect.disabled = false;
    roleSelect.value = key;
    roleSelect.dispatchEvent(new Event('change'));
    roleSelect.dispatchEvent(new Event('input'));
    if (wasDisabled) roleSelect.disabled = true;
    input.value = '';
    const collapseEl = document.getElementById('quickAddRole'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
  });
})();
</script>
