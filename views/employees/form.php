<?php /** @var array $employee */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $employee['id'] ? 'Edit Employee' : 'New Employee'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=employees&action=save'); ?>" class="card shadow-sm">
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
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option value="">Select Role</option>
          <option value="admin" <?php echo ($employee['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
          <option value="manager" <?php echo ($employee['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
          <option value="driver" <?php echo ($employee['role'] ?? '') === 'driver' ? 'selected' : ''; ?>>Driver</option>
          <option value="clerk" <?php echo ($employee['role'] ?? '') === 'clerk' ? 'selected' : ''; ?>>Clerk</option>
          <option value="mechanic" <?php echo ($employee['role'] ?? '') === 'mechanic' ? 'selected' : ''; ?>>Mechanic</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Salary Amount</label>
        <input type="number" step="0.01" name="salary_amount" class="form-control" required value="<?php echo htmlspecialchars((string)($employee['salary_amount'] ?? '')); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">License Number</label>
        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($employee['license_number'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">License Expiry</label>
        <input type="date" name="license_expiry" class="form-control" value="<?php echo htmlspecialchars($employee['license_expiry'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Vehicle ID</label>
        <?php if (!empty($vehiclesAll)): ?>
          <select name="vehicle_id" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($vehiclesAll as $v): $vid = (int)($v['id'] ?? 0); ?>
              <option value="<?php echo $vid; ?>" <?php echo ((int)($employee['vehicle_id'] ?? 0) === $vid) ? 'selected' : ''; ?>><?php echo $vid; ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="text" name="vehicle_id" class="form-control" value="<?php echo htmlspecialchars($employee['vehicle_id'] ?? ''); ?>">
        <?php endif; ?>
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
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
<?php if (!empty($error)): ?>
<script>
  // Show a popup alert for errors (e.g., duplicate email or employee code)
  (function(){
    try { alert(<?php echo json_encode((string)$error, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>); } catch (e) {}
  })();
</script>
<?php endif; ?>
