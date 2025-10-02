<?php /** @var array $suppliers */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Suppliers</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Supplier</a>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="suppliers">
  <div class="col-6 col-md-3 col-lg-3">
    <input type="text" class="form-control" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="code" placeholder="Code" value="<?php echo htmlspecialchars($code ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-3">
    <select name="branch_id" class="form-select">
      <?php $bid = (int)($branch_id ?? 0); ?>
      <option value="0" <?php echo ($bid===0)?'selected':''; ?>>Branch (any)</option>
      <?php foreach (($branchesAll ?? []) as $b): ?>
        <option value="<?php echo (int)$b['id']; ?>" <?php echo ($bid===(int)$b['id'])?'selected':''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>">Clear</a>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle datatable">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Code</th>
        <th>Branch</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($suppliers as $s): ?>
        <tr>
          <td><?php echo (int)$s['id']; ?></td>
          <td><?php echo htmlspecialchars($s['name']); ?></td>
          <td><?php echo htmlspecialchars($s['phone']); ?></td>
          <td><?php echo htmlspecialchars($s['supplier_code']); ?></td>
          <td><?php echo htmlspecialchars($s['branch_name'] ?? ''); ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=edit&id='.(int)$s['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
