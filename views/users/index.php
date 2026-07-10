<?php /** @var array $users */ ?>
<?php
  $rolesCatalog = $rolesCatalog ?? [];
  $rolesDynamic = $rolesDynamic ?? [];
  $currentUserId = (int)($currentUserId ?? 0);
?>
<div class="container-fluid px-0">
  <div class="row g-2">
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-3">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2">
            <h3 class="h5 mb-0 fw-bold">Users</h3>
            <a href="<?php echo Helpers::baseUrl('index.php?page=users&action=new'); ?>" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-1"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>New user</span></a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-3">
          <form class="row g-2 align-items-end" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>" id="usersFilterForm">
            <input type="hidden" name="page" value="users">
            <div class="col-6 col-md-3">
              <label class="form-label small mb-1">Username</label>
              <input type="text" name="username" class="form-control form-control-sm" placeholder="Username" value="<?php echo htmlspecialchars($usernameF ?? ''); ?>">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small mb-1">Full name</label>
              <input type="text" name="full_name" class="form-control form-control-sm" placeholder="Full Name" value="<?php echo htmlspecialchars($fullNameF ?? ''); ?>">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small mb-1">Role</label>
              <select name="role" class="form-select form-select-sm" data-enhance="false">
                <?php $rf = (string)($roleF ?? ''); ?>
                <option value="" <?php echo $rf===''?'selected':''; ?>>Any</option>
                <?php foreach ($rolesCatalog as $rk => $label): ?>
                  <option value="<?php echo htmlspecialchars($rk); ?>" <?php echo ($rf===$rk)?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
                <?php foreach ($rolesDynamic as $r): $val = trim((string)($r['role'] ?? '')); if ($val==='' || isset($rolesCatalog[$val])) continue; ?>
                  <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($rf===$val)?'selected':''; ?>><?php echo htmlspecialchars(ucwords(str_replace('_',' ', $val))); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small mb-1">Branch</label>
              <select name="branch_id" class="form-select form-select-sm" data-enhance="false">
                <?php $bf = (int)($branchF ?? 0); ?>
                <option value="0" <?php echo $bf===0?'selected':''; ?>>Any</option>
                <?php foreach (($branchesAll ?? []) as $b): ?>
                  <option value="<?php echo (int)$b['id']; ?>" <?php echo $bf===(int)$b['id']?'selected':''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label small mb-1">Active</label>
              <select name="active" class="form-select form-select-sm" data-enhance="false">
                <?php $af = (string)($activeF ?? ''); ?>
                <option value="" <?php echo $af===''?'selected':''; ?>>Any</option>
                <option value="1" <?php echo $af==='1'?'selected':''; ?>>Yes</option>
                <option value="0" <?php echo $af==='0'?'selected':''; ?>>No</option>
              </select>
            </div>
            <div class="col-12 col-sm-auto d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1"><i class="bi bi-search" aria-hidden="true"></i><span>Filter</span></button>
              <a class="btn btn-outline-dark btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>">Clear</a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php if (!empty($success)): ?>
    <div class="col-12">
      <div class="alert alert-success py-2 mb-0"><?php echo htmlspecialchars((string)$success); ?></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="col-12">
      <div class="alert alert-danger py-2 mb-0"><?php echo htmlspecialchars($error); ?></div>
    </div>
    <?php endif; ?>
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle datatable mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Full Name</th>
                  <th>Role</th>
                  <th>Branch</th>
                  <th>Active</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $rowNum = 0; foreach (($users ?? []) as $u): $rowNum++; ?>
                  <?php $uid = (int)($u['id'] ?? 0); $isSelf = ($currentUserId > 0 && $uid === $currentUserId); ?>
                  <tr>
                    <td><?php echo (int)$rowNum; ?></td>
                    <td><?php echo $uid; ?></td>
                    <td><?php echo htmlspecialchars((string)($u['username'] ?? '')); ?><?php if ($isSelf): ?> <span class="badge text-bg-info">You</span><?php endif; ?></td>
                    <td><?php echo htmlspecialchars((string)($u['full_name'] ?? '')); ?></td>
                    <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string)($u['role'] ?? '')); ?></span></td>
                    <td><?php echo htmlspecialchars($u['branch_name'] ?? '—'); ?></td>
                    <td><?php echo ((int)($u['active'] ?? 0) === 1) ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>'; ?></td>
                    <td class="text-end text-nowrap">
                      <a class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" href="<?php echo Helpers::baseUrl('index.php?page=users&action=edit&id='.$uid); ?>"><i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></a>
                      <?php if (!$isSelf): ?>
                      <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=users&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo $uid; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"><i class="bi bi-trash" aria-hidden="true"></i><span>Delete</span></button>
                      </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
