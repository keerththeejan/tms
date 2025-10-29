<?php /** @var array $customers */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Customers</h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=customers&action=new'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Customer</a>
</div>
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="customers">
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <input type="text" class="form-control" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-3">
    <input type="text" class="form-control" name="address" placeholder="Address" value="<?php echo htmlspecialchars($address ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-3">
    <input type="text" class="form-control" name="delivery_location" placeholder="Delivery Location" value="<?php echo htmlspecialchars($delivery_location ?? ''); ?>">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <select name="type" class="form-select">
      <?php $t = $type ?? ''; ?>
      <option value="" <?php echo ($t==='')?'selected':''; ?>>Type (any)</option>
      <option value="regular" <?php echo ($t==='regular')?'selected':''; ?>>regular</option>
      <option value="corporate" <?php echo ($t==='corporate')?'selected':''; ?>>corporate</option>
    </select>
  </div>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
    <a class="btn btn-outline-dark" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>">Clear</a>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle datatable">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Address</th>
        <th>Delivery Location</th>
        <th>Type</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><?php echo (int)$c['id']; ?></td>
          <td><?php echo htmlspecialchars($c['name']); ?></td>
          <td>
            <?php $ph = trim((string)($c['phone'] ?? '')); $showPh = (preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1) ? '' : $ph; echo htmlspecialchars($showPh); ?>
          </td>
          <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($c['address']); ?></td>
          <td><?php echo htmlspecialchars($c['delivery_location']); ?></td>
          <td><?php echo htmlspecialchars($c['customer_type'] ?? ''); ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=customers&action=edit&id='.(int)$c['id']); ?>"><i class="bi bi-pencil-square"></i> Edit</a>
            <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this customer?');">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
