<?php /** @var array $userRow */ ?>
<?php
  $rolesCatalog = $rolesCatalog ?? [];
  $rolesDynamic = $rolesDynamic ?? [];
  $currentUserId = (int)($currentUserId ?? 0);
  $isSelf = ((int)($userRow['id'] ?? 0) > 0) && ((int)($userRow['id'] ?? 0) === $currentUserId);
?>
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
        <select name="role" class="form-select" id="userRoleSelect">
          <?php 
            $currentRole = (string)($userRow['role'] ?? 'staff');
            $renderedKeys = [];
            foreach ($rolesCatalog as $k => $label): $renderedKeys[$k] = true; ?>
              <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $currentRole===$k?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach;
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
        <label class="form-label">Branch (optional)</label>
        <select name="branch_id" class="form-select">
          <option value="0">-- None --</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <?php
              $bid = (int)$b['id'];
              $inactive = isset($b['is_active']) && (int)$b['is_active'] === 0;
              $selected = ((int)($userRow['branch_id'] ?? 0) === $bid);
              $label = htmlspecialchars((string)($b['name'] ?? '')) . ($inactive ? ' (inactive)' : '');
            ?>
            <option value="<?php echo $bid; ?>" <?php echo $selected ? 'selected' : ''; ?><?php echo ($inactive && !$selected) ? ' disabled' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Colombo, Kilinochchi, or Mullaitivu only.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Password <?php echo $userRow['id'] ? '(leave blank to keep)' : ''; ?></label>
        <input type="password" name="password" class="form-control" <?php echo $userRow['id']? '' : 'required'; ?> autocomplete="new-password">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="active" id="activeChk" value="1" <?php echo ((int)($userRow['active'] ?? 1) === 1) ? 'checked' : ''; ?> <?php echo $isSelf ? 'disabled' : ''; ?>>
          <label class="form-check-label" for="activeChk">Active</label>
          <?php if ($isSelf): ?><input type="hidden" name="active" value="1"><div class="form-text">Your account stays active while you are logged in.</div><?php endif; ?>
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
  function readSelectValue(sel) {
    if (!sel) return '';
    try {
      if (sel._choices) {
        const v = sel._choices.getValue(true);
        if (Array.isArray(v)) {
          const first = v[0];
          return String((first && first.value !== undefined) ? first.value : (first || ''));
        }
        return String(v || '');
      }
    } catch (_) { /* ignore */ }
    return String(sel.value || '');
  }
  function syncNativeSelect(sel) {
    if (!sel || !sel._choices) return;
    const val = readSelectValue(sel);
    if (val !== '') sel.value = val;
  }
  function refreshChoices(sel) {
    if (!sel || !sel._choices) return;
    try { sel.dispatchEvent(new Event('refresh-choices')); } catch (_) { /* ignore */ }
  }
  const userForm = document.querySelector('form[action*="page=users&action=save"]');
  userForm?.addEventListener('submit', function() {
    syncNativeSelect(document.querySelector('select[name="role"]'));
    syncNativeSelect(document.querySelector('select[name="branch_id"]'));
  });

  const roleBtn = document.getElementById('ur_submit');
  const roleSelect = document.querySelector('select[name="role"]');

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
    refreshChoices(roleSelect);
    if (wasDisabled) roleSelect.disabled = true;
    input.value = '';
    const collapseEl = document.getElementById('quickAddRole'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
  });
})();
</script>
