<?php /** @var array $users */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Users</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=users&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New User</a>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="users">
  <div class="col-6 col-md-3">
    <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo htmlspecialchars($usernameF ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3">
    <input type="text" name="full_name" class="form-control" placeholder="Full Name" value="<?php echo htmlspecialchars($fullNameF ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3">
    <select name="role" class="form-select">
      <?php $rf = (string)($roleF ?? ''); ?>
      <option value="" <?php echo $rf===''?'selected':''; ?>>Role (any)</option>
      <?php foreach (($rolesDynamic ?? []) as $r): $val = trim((string)($r['role'] ?? '')); if ($val==='') continue; ?>
        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($rf===$val)?'selected':''; ?>><?php echo htmlspecialchars($val); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3">
    <select name="branch_id" class="form-select">
      <?php $bf = (int)($branchF ?? 0); ?>
      <option value="0" <?php echo $bf===0?'selected':''; ?>>Branch (any)</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo $bf===(int)$b['id']?'selected':''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3">
    <select name="active" class="form-select">
      <?php $af = (string)($activeF ?? ''); ?>
      <option value="" <?php echo $af===''?'selected':''; ?>>Active (any)</option>
      <option value="1" <?php echo $af==='1'?'selected':''; ?>>Yes</option>
      <option value="0" <?php echo $af==='0'?'selected':''; ?>>No</option>
    </select>
  </div>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>">Clear</a>
  </div>
</form>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle datatable">
    <thead>
      <tr>
        <th>#</th>
        <th>Username</th>
        <th>Full Name</th>
        <th>Role</th>
        <th>Branch</th>
        <th>Active</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($users ?? []) as $u): ?>
        <tr>
          <td><?php echo (int)$u['id']; ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?></td>
          <td><?php echo htmlspecialchars($u['full_name']); ?></td>
          <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($u['role']); ?></span></td>
          <td><?php echo htmlspecialchars($u['branch_name'] ?? '—'); ?></td>
          <td><?php echo ((int)$u['active'] === 1) ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>'; ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=users&action=edit&id='.(int)$u['id']); ?>"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=users&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this user?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
