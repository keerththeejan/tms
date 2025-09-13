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
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
          <?php $roles = ['admin'=>'Admin','accountant'=>'Accountant','cashier'=>'Cashier','collector'=>'Due Collector','parcel_user'=>'Parcel User','staff'=>'Staff'];
          foreach ($roles as $k=>$label): ?>
            <option value="<?php echo $k; ?>" <?php echo ($userRow['role']??'')===$k?'selected':''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Branch (optional)</label>
        <select name="branch_id" class="form-select">
          <option value="0">-- None --</option>
          <?php foreach (($branchesAll ?? []) as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($userRow['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
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
