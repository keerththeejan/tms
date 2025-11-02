<?php /** @var array $notes */ ?>
<style>
  /* Delivery Notes list polish */
  #dnTable td, #dnTable th { vertical-align: middle; }
  #dnTable .text-truncate { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  #dnTable .nowrap { white-space: nowrap; }
  #dnTable .amount { text-align: right; font-weight: 600; }
  #dnTable .disc { color: #6c757d; }
  @media (max-width: 992px) { #dnTable .text-truncate { max-width: 160px; } }
  @media (max-width: 768px) { #dnTable .text-truncate { max-width: 120px; } }
  .dn-actions .btn { padding: .15rem .45rem; }
  .dn-actions { gap: .25rem; }
  .dn-veh { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Delivery Notes</h3>
  <div class="d-flex gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=generate'); ?>" class="btn btn-primary"><i class="bi bi-magic"></i> Generate</a>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=customer_summary'); ?>" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Customer Summary</a>
  </div>
</div>
<?php if (($_GET['saved'] ?? '') === '1'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  Saved successfully.
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
<form class="row g-2 mb-3" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>">
  <input type="hidden" name="page" value="delivery_notes">
  <div class="col-md-3">
    <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
  </div>
  <div class="col-md-4">
    <input type="text" class="form-control" name="q" placeholder="Search customer phone or name" value="<?php echo htmlspecialchars($q ?? ''); ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button>
  </div>
</form>
<div class="table-responsive">
  <table id="dnTable" class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>Delivery Date</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Supplier</th>
        <th>Supplier Phone</th>
        <th>Vehicles</th>
        <th>Email</th>
        <th class="text-end">Total</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($notes as $n): ?>
        <tr>
          <td><?php echo (int)$n['id']; ?></td>
          <td><?php echo htmlspecialchars($n['delivery_date']); ?></td>
          <td class="text-truncate" title="<?php echo htmlspecialchars($n['customer_name'] ?? ''); ?>"><?php echo htmlspecialchars($n['customer_name'] ?? ''); ?></td>
          <td class="nowrap">&lrm;<?php echo htmlspecialchars($n['customer_phone'] ?? ''); ?></td>
          <td class="text-truncate" title="<?php echo htmlspecialchars($n['suppliers'] ?? '—'); ?>"><?php echo htmlspecialchars($n['suppliers'] ?? '—'); ?></td>
          <td class="text-truncate" title="<?php echo htmlspecialchars($n['supplier_phones'] ?? '—'); ?>"><?php echo htmlspecialchars($n['supplier_phones'] ?? '—'); ?></td>
          <td><span class="dn-veh" title="<?php echo htmlspecialchars($n['vehicles'] ?? '—'); ?>"><?php echo ($n['vehicles'] ?? '') !== '' ? htmlspecialchars($n['vehicles']) : '—'; ?></span></td>
          <td class="nowrap">
            <?php $st = strtolower(trim((string)($n['email_status'] ?? ''))); ?>
            <?php if ($st === 'sent'): ?>
              <span class="badge bg-success">Sent</span>
              <?php if (!empty($n['emailed_at'])): ?><small class="text-muted ms-1"><?php echo htmlspecialchars($n['emailed_at']); ?></small><?php endif; ?>
            <?php elseif ($st === 'failed'): ?>
              <span class="badge bg-danger">Failed</span>
              <?php if (!empty($n['emailed_at'])): ?><small class="text-muted ms-1"><?php echo htmlspecialchars($n['emailed_at']); ?></small><?php endif; ?>
            <?php else: ?>
              <span class="badge bg-secondary">Not sent</span>
            <?php endif; ?>
            <div><a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=email_log&id='.(int)$n['id']); ?>">View log</a></div>
          </td>
          <td class="amount">
            <?php $disc = (float)($n['discount'] ?? 0); $net = isset($n['net_total']) ? (float)$n['net_total'] : ((float)$n['total_amount'] + $disc); ?>
            <div><?php echo number_format($net, 2); ?></div>
            <?php if ($disc != 0): ?>
              <div class="disc small">Disc: <?php echo ($disc>0?'+':'').number_format($disc,2); ?></div>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <form id="dn-edit-<?php echo (int)$n['id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=dn_update'); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
              <input type="hidden" name="delivery_date" value="<?php echo htmlspecialchars($n['delivery_date']); ?>">
              <input type="hidden" name="vehicle_no" value="<?php echo htmlspecialchars(explode(',', (string)($n['vehicles'] ?? ''))[0] ?? ''); ?>">
              <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
              <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
              <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
            </form>
            <div class="dn-actions d-inline-flex">
            <button type="button" class="btn btn-sm btn-outline-success" onclick="(function(f){
              var d=prompt('Edit delivery date (YYYY-MM-DD)', f.delivery_date.value);
              if(d===null) return; d=d.trim(); if(!d){alert('Enter date'); return;}
              var cur=(f.vehicle_no.value||'').toString().split(',')[0].trim();
              var v=prompt('Edit vehicle number (applies to all items in this note)', cur);
              if(v===null) return; v=v.trim(); if(!v){alert('Enter vehicle number'); return;}
              f.delivery_date.value=d; f.vehicle_no.value=v; f.submit();
            })(document.getElementById('dn-edit-<?php echo (int)$n['id']; ?>'));"><i class="bi bi-pencil-square"></i> Edit</button>

            <form id="dn-disc-<?php echo (int)$n['id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=dn_update_discount'); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
              <input type="hidden" name="discount" value="<?php echo htmlspecialchars((string)($n['discount'] ?? '0')); ?>">
              <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
              <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
              <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
            </form>
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="(function(f){var v=prompt('Set discount (negative only, e.g., -500)', f.discount.value || '0'); if(v!==null){v=v.trim(); if(v!=='' && !isNaN(v)){f.discount.value=v; f.submit();} else {alert('Enter a number');}}})(document.getElementById('dn-disc-<?php echo (int)$n['id']; ?>'));"><i class="bi bi-percent"></i> Discount</button>

            <form id="dn-del-<?php echo (int)$n['id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=dn_delete'); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
              <input type="hidden" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>">
              <input type="hidden" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>">
              <input type="hidden" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>">
            </form>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="(function(f){ if(confirm('Delete this delivery note? This cannot be undone.')) f.submit();})(document.getElementById('dn-del-<?php echo (int)$n['id']; ?>'));"><i class="bi bi-trash"></i> Delete</button>

            <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='.(int)$n['id']); ?>"><i class="bi bi-eye"></i> View</a>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=print&id='.(int)$n['id']); ?>" target="_blank"><i class="bi bi-printer"></i> Print</a>
            <a class="btn btn-sm btn-outline-info" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=email_form&id='.(int)$n['id']); ?>"><i class="bi bi-envelope"></i> Email</a>
            
            <?php if (Auth::canCollectPayments()): ?>
            <form id="dn-pay-<?php echo (int)$n['id']; ?>" class="d-inline" method="post" action="<?php echo Helpers::baseUrl('index.php?page=payments&action=save'); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
              <input type="hidden" name="delivery_note_id" value="<?php echo (int)$n['id']; ?>">
              <input type="hidden" name="amount" value="0">
            </form>
            <button type="button" class="btn btn-sm btn-warning" onclick="(function(f){
              var maxDue = <?php $disc=(float)($n['discount']??0); $net=(float)$n['total_amount'] - $disc; $paid=(float)($n['paid']??0); $due=max(0,$net-$paid); echo json_encode((float)$due); ?>;
              var v = prompt('Enter card amount to collect (max '+ maxDue.toFixed(2) +')', maxDue.toFixed(2));
              if(v===null) return; v=v.trim(); if(v===''||isNaN(v)){ alert('Enter a valid amount'); return; }
              var num = parseFloat(v); if(num<=0){ alert('Amount must be greater than 0'); return; }
              if(num>maxDue){ alert('Amount exceeds current due'); return; }
              f.amount.value = num.toFixed(2);
              f.submit();
            })(document.getElementById('dn-pay-<?php echo (int)$n['id']; ?>'));"><i class="bi bi-credit-card"></i> Card Pay</button>
            <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
