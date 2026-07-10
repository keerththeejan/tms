<?php
/** @var array $notes */
$dnCssPath = dirname(__DIR__, 2) . '/public/assets/css/delivery-notes.css';
$dnJsPath = dirname(__DIR__, 2) . '/public/assets/js/delivery-notes.js';
$dnCssVer = is_file($dnCssPath) ? (string) filemtime($dnCssPath) : '1';
$dnJsVer = is_file($dnJsPath) ? (string) filemtime($dnJsPath) : '1';
$base = Helpers::baseUrl('');
$filterBranchId = (int) ($filterBranchId ?? 0);
$isMain = !empty($isMain);
$hasFilters = !empty($hasFilters);
$canCreateParcels = Auth::canCreateParcels();
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/delivery-notes.css?v=' . rawurlencode($dnCssVer)); ?>">

<div class="dn-page container-fluid px-0 px-sm-1">
  <header class="dn-page-head d-flex flex-column flex-lg-row align-items-stretch align-items-lg-start justify-content-between gap-3">
    <div class="min-w-0">
      <h1 class="dn-title"><i class="bi bi-receipt" aria-hidden="true"></i> Delivery Notes</h1>
      <p class="dn-subtitle">Search, filter, and manage delivery notes. Use route planning to assign vehicles before generating bills.</p>
    </div>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center flex-shrink-0">
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=generate'); ?>" class="btn btn-primary">
        <i class="bi bi-magic me-1" aria-hidden="true"></i> Generate
      </a>
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=customer_summary'); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-people me-1" aria-hidden="true"></i> Customer Summary
      </a>
    </div>
  </header>

  <?php if (($_GET['saved'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    Saved successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (($_GET['collected'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    Payment recorded successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (($_GET['err'] ?? '') === 'payment_not_delivered'): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    Payments are allowed only after all parcels in the delivery note are delivered.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (($_GET['err'] ?? '') === 'payment_overdue'): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    Payment amount exceeds the current due balance.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (in_array(($_GET['err'] ?? ''), ['payment_invalid', 'payment_not_found'], true)): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    Could not record payment. Check the delivery note and amount.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (($_GET['deleted'] ?? '') === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    Deleted successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (($_GET['err'] ?? '') === 'invalid_input'): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    Invalid input.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php elseif (($_GET['err'] ?? '') === 'delete_failed'): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    Delete failed.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php endif; ?>

  <section class="dn-card dn-filters-card" aria-label="Filter delivery notes">
    <form method="get" action="<?php echo htmlspecialchars($base . 'index.php'); ?>" class="row g-3 align-items-end">
      <input type="hidden" name="page" value="delivery_notes">
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="dnFilterFrom">From</label>
        <input type="date" class="form-control" id="dnFilterFrom" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="dnFilterTo">To</label>
        <input type="date" class="form-control" id="dnFilterTo" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
      </div>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="dnFilterDirection">Direction</label>
        <select class="form-select" id="dnFilterDirection" name="direction" data-enhance="false">
          <option value="" <?php echo ($direction ?? '') === '' ? 'selected' : ''; ?>>All directions</option>
          <option value="from" <?php echo ($direction ?? '') === 'from' ? 'selected' : ''; ?>>Dispatch (from branch)</option>
          <option value="to" <?php echo ($direction ?? '') === 'to' ? 'selected' : ''; ?>>Arrival (to branch)</option>
        </select>
      </div>
      <?php if ($isMain): ?>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-2">
        <label class="form-label" for="dnFilterBranch">Branch</label>
        <select class="form-select" id="dnFilterBranch" name="branch_id" data-enhance="false">
          <?php foreach (($branchesAll ?? []) as $b): ?>
          <option value="<?php echo (int) $b['id']; ?>" <?php echo $filterBranchId === (int) $b['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name'] ?? ''); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-12 col-sm-6 col-lg-3 col-xl-<?php echo $isMain ? '2' : '3'; ?>">
        <label class="form-label" for="dnFilterVehicle">Vehicle</label>
        <input type="text" class="form-control" id="dnFilterVehicle" name="vehicle" placeholder="Vehicle number" value="<?php echo htmlspecialchars($vehicle ?? ''); ?>" autocomplete="off">
      </div>
      <div class="col-12 col-lg-6 col-xl-<?php echo $isMain ? '2' : '3'; ?>">
        <label class="form-label" for="dnFilterQ">Customer</label>
        <input type="search" class="form-control" id="dnFilterQ" name="q" placeholder="Name or phone" value="<?php echo htmlspecialchars($q ?? ''); ?>" autocomplete="off">
      </div>
      <div class="col-12 col-lg-6 col-xl-auto dn-filter-actions d-flex flex-column flex-sm-row flex-wrap gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1 flex-sm-grow-0"><i class="bi bi-funnel me-1" aria-hidden="true"></i> Filter</button>
        <a class="btn btn-outline-secondary flex-grow-1 flex-sm-grow-0" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes'); ?>">Clear</a>
      </div>
    </form>

    <div class="dn-toolbar-actions d-flex flex-column flex-sm-row flex-wrap gap-2 mt-3 pt-3 border-top">
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=route'); ?>" class="btn btn-outline-primary flex-grow-1 flex-sm-grow-0">
        <i class="bi bi-map me-1" aria-hidden="true"></i> Route Planning
      </a>
      <?php if ($canCreateParcels): ?>
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=parcels&action=new'); ?>" class="btn btn-outline-secondary flex-grow-1 flex-sm-grow-0 d-none d-md-inline-flex">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New Parcel
      </a>
      <?php endif; ?>
    </div>
  </section>

  <?php if (empty($notes)): ?>
  <section class="dn-card dn-empty" aria-label="No delivery notes">
    <div class="dn-empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></div>
    <h2><?php echo $hasFilters ? 'No delivery notes match your filters' : 'No delivery notes yet'; ?></h2>
    <p><?php echo $hasFilters
      ? 'Try widening the date range, clearing the vehicle filter, or switching direction.'
      : 'Generate a delivery note from pending parcels or create a new parcel to get started.'; ?></p>
    <div class="dn-empty-actions">
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=generate'); ?>" class="btn btn-primary">Generate</a>
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=route'); ?>" class="btn btn-outline-secondary">Route Planning</a>
      <?php if ($canCreateParcels): ?>
      <a href="<?php echo htmlspecialchars($base . 'index.php?page=parcels&action=new'); ?>" class="btn btn-outline-secondary">New Parcel</a>
      <?php endif; ?>
    </div>
  </section>
  <?php else: ?>

  <div class="dn-mobile-cards d-lg-none d-flex flex-column gap-2 mb-2">
    <?php foreach ($notes as $n): ?>
      <?php
        $disc = (float) ($n['discount'] ?? 0);
        $net = isset($n['net_total']) ? (float) $n['net_total'] : ((float) ($n['total_amount'] ?? 0) + $disc);
        $st = strtolower(trim((string) ($n['email_status'] ?? '')));
      ?>
      <article class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div class="min-w-0">
              <div class="fw-bold">#<?php echo (int) $n['id']; ?> · <?php echo htmlspecialchars((string) ($n['delivery_date'] ?? '')); ?></div>
              <div class="text-truncate"><?php echo htmlspecialchars((string) ($n['customer_name'] ?? '')); ?></div>
            </div>
            <div class="text-end flex-shrink-0">
              <div class="fw-bold"><?php echo Helpers::formatMoney($net); ?></div>
              <?php if ($disc != 0): ?><div class="small text-muted">Disc <?php echo ($disc > 0 ? '+' : '') . number_format($disc, 2); ?></div><?php endif; ?>
            </div>
          </div>
          <div class="dn-kv"><span class="k">Phone</span><span class="v">&lrm;<?php echo htmlspecialchars((string) ($n['customer_phone'] ?? '—')); ?></span></div>
          <div class="dn-kv"><span class="k">Vehicles</span><span class="v"><?php echo htmlspecialchars(($n['vehicles'] ?? '') !== '' ? (string) $n['vehicles'] : '—'); ?></span></div>
          <div class="dn-kv"><span class="k">Email</span><span class="v">
            <?php if ($st === 'sent'): ?><span class="badge text-bg-success">Sent</span>
            <?php elseif ($st === 'failed'): ?><span class="badge text-bg-danger">Failed</span>
            <?php else: ?><span class="badge text-bg-secondary">Not sent</span><?php endif; ?>
          </span></div>
          <div class="mt-3">
            <?php $variant = 'dropdown'; include __DIR__ . '/partials/note_actions.php'; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <section class="dn-card dn-table-card d-none d-lg-block" aria-label="Delivery notes table">
    <div class="table-responsive">
      <table id="dnTable" class="table table-striped table-hover align-middle mb-0 dn-table">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Delivery Date</th>
            <th scope="col">Customer</th>
            <th scope="col">Phone</th>
            <th scope="col">Supplier</th>
            <th scope="col">Supplier Phone</th>
            <th scope="col">Vehicles</th>
            <th scope="col">Items</th>
            <th scope="col">Email</th>
            <th scope="col" class="text-end">Total</th>
            <th scope="col" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notes as $n): ?>
          <tr>
            <td><?php echo (int) $n['id']; ?></td>
            <td class="text-nowrap"><?php echo htmlspecialchars((string) ($n['delivery_date'] ?? '')); ?></td>
            <td class="text-truncate" style="max-width:10rem" title="<?php echo htmlspecialchars((string) ($n['customer_name'] ?? '')); ?>"><?php echo htmlspecialchars((string) ($n['customer_name'] ?? '')); ?></td>
            <td class="text-nowrap">&lrm;<?php echo htmlspecialchars((string) ($n['customer_phone'] ?? '')); ?></td>
            <td class="text-truncate" style="max-width:8rem" title="<?php echo htmlspecialchars((string) ($n['suppliers'] ?? '—')); ?>"><?php echo htmlspecialchars((string) ($n['suppliers'] ?? '—')); ?></td>
            <td class="text-truncate" style="max-width:7rem" title="<?php echo htmlspecialchars((string) ($n['supplier_phones'] ?? '—')); ?>"><?php echo htmlspecialchars((string) ($n['supplier_phones'] ?? '—')); ?></td>
            <td class="text-truncate" style="max-width:7rem" title="<?php echo htmlspecialchars((string) ($n['vehicles'] ?? '—')); ?>"><?php echo ($n['vehicles'] ?? '') !== '' ? htmlspecialchars((string) $n['vehicles']) : '—'; ?></td>
            <td class="text-truncate" style="max-width:9rem" title="<?php echo htmlspecialchars((string) ($n['item_descriptions'] ?? '—')); ?>">
              <?php $it = trim((string) ($n['item_descriptions'] ?? '')); echo $it !== '' ? htmlspecialchars($it) : '—'; ?>
            </td>
            <td class="text-nowrap">
              <?php $st = strtolower(trim((string) ($n['email_status'] ?? ''))); ?>
              <?php if ($st === 'sent'): ?>
                <span class="badge text-bg-success">Sent</span>
              <?php elseif ($st === 'failed'): ?>
                <span class="badge text-bg-danger">Failed</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">Not sent</span>
              <?php endif; ?>
              <div><a class="small text-decoration-none" href="<?php echo htmlspecialchars($base . 'index.php?page=delivery_notes&action=email_log&id=' . (int) $n['id']); ?>">View log</a></div>
            </td>
            <td class="dn-amount">
              <?php $disc = (float) ($n['discount'] ?? 0); $net = isset($n['net_total']) ? (float) $n['net_total'] : ((float) ($n['total_amount'] ?? 0) + $disc); ?>
              <div><?php echo Helpers::formatMoney($net); ?></div>
              <?php if ($disc != 0): ?>
              <div class="dn-disc">Disc: <?php echo ($disc > 0 ? '+' : '') . number_format($disc, 2); ?></div>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php $variant = 'table'; include __DIR__ . '/partials/note_actions.php'; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($notes)): ?>
  <div class="visually-hidden" aria-hidden="true">
    <?php foreach ($notes as $n): ?>
      <?php include __DIR__ . '/partials/note_forms.php'; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($canCreateParcels): ?>
  <a href="<?php echo htmlspecialchars($base . 'index.php?page=parcels&action=new'); ?>" class="dn-fab btn btn-primary d-md-none" aria-label="New parcel" title="New parcel">
    <i class="bi bi-plus-lg fs-5" aria-hidden="true"></i>
  </a>
  <?php endif; ?>
</div>

<script src="<?php echo Helpers::baseUrl('assets/js/delivery-notes.js?v=' . rawurlencode($dnJsVer)); ?>"></script>
<script>
(function () {
  'use strict';

  function formById(prefix, id) {
    return document.getElementById(prefix + id);
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-dn-action]');
    if (!btn) return;
    var action = btn.getAttribute('data-dn-action');
    var id = btn.getAttribute('data-dn-id');
    if (!id) return;

    if (action === 'edit') {
      var f = formById('dn-edit-', id);
      if (!f) return;
      var d = prompt('Edit delivery date (YYYY-MM-DD)', f.delivery_date.value);
      if (d === null) return;
      d = d.trim();
      if (!d) { alert('Enter date'); return; }
      var cur = (f.vehicle_no.value || '').toString().split(',')[0].trim();
      var v = prompt('Edit vehicle number (applies to all items in this note)', cur);
      if (v === null) return;
      v = v.trim();
      if (!v) { alert('Enter vehicle number'); return; }
      f.delivery_date.value = d;
      f.vehicle_no.value = v;
      f.submit();
      return;
    }

    if (action === 'discount') {
      var df = formById('dn-disc-', id);
      if (!df) return;
      var val = prompt('Set discount (negative only, e.g., -500)', df.discount.value || '0');
      if (val === null) return;
      val = val.trim();
      if (val !== '' && !isNaN(val)) {
        df.discount.value = val;
        df.submit();
      } else {
        alert('Enter a number');
      }
      return;
    }

    if (action === 'delete') {
      var del = formById('dn-del-', id);
      if (del && confirm('Delete this delivery note? This cannot be undone.')) {
        del.submit();
      }
      return;
    }

    if (action === 'pay') {
      var pf = formById('dn-pay-', id);
      if (!pf) return;
      var maxDue = parseFloat(btn.getAttribute('data-dn-due') || '0') || 0;
      var amt = prompt('Enter card amount to collect (max ' + maxDue.toFixed(2) + ')', maxDue.toFixed(2));
      if (amt === null) return;
      amt = amt.trim();
      if (amt === '' || isNaN(amt)) { alert('Enter a valid amount'); return; }
      var num = parseFloat(amt);
      if (num <= 0) { alert('Amount must be greater than 0'); return; }
      if (num > maxDue) { alert('Amount exceeds current due'); return; }
      pf.amount.value = num.toFixed(2);
      pf.submit();
    }
  });
})();
</script>
