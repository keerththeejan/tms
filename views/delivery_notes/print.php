<?php /** @var array $dn */ /** @var array $items */ $isEmbed = (($_GET['embed'] ?? '') === '1'); ?>
<?php
$reportEmbed = $isEmbed;
$reportDocTitle = 'Delivery Note #' . (int)$dn['id'];
$reportTitle = 'Delivery Note';
$reportSubtitle = 'Transport and Parcel Services';
$addresses = Helpers::companyHeaderAddressLines((string)($_GET['addr'] ?? ''), 50, 'all');
$reportShowAddresses = true;
$reportAddressLines = $addresses;
$reportMetaItems = [
    ['label' => 'Document No', 'value' => '#' . (int)$dn['id']],
    ['label' => 'Print Date', 'value' => date('d/m/Y H:i')],
    ['label' => 'Date', 'value' => (string)($dn['delivery_date'] ?? '')],
    ['label' => 'Branch', 'value' => (string)($dn['branch_name'] ?? '')],
];
$vehSet = [];
foreach (($items ?? []) as $it) {
    $v = trim((string)($it['vehicle_no'] ?? ''));
    if ($v !== '') { $vehSet[$v] = true; }
}
$vehList = implode(', ', array_keys($vehSet));
$reportInfoItems = [
    ['label' => 'Report Name', 'value' => 'Delivery Note'],
    ['label' => 'Customer', 'value' => (string)($dn['customer_name'] ?? '')],
    ['label' => 'Phone', 'value' => (string)($dn['customer_phone'] ?? '')],
    ['label' => 'Branch', 'value' => (string)($dn['branch_name'] ?? '')],
    ['label' => 'Date', 'value' => (string)($dn['delivery_date'] ?? '')],
    ['label' => 'Printed Date', 'value' => date('d/m/Y H:i')],
];
if ($vehList !== '') {
    $reportInfoItems[] = ['label' => 'Vehicle(s)', 'value' => $vehList];
}
$reportShowInfoPanel = true;
include __DIR__ . '/../partials/report/print_document_open.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  .text-end { text-align: right !important; }
  .text-muted { color: #6c757d !important; }
  .fw-bold { font-weight: 700 !important; }
  .small { font-size: 0.875em !important; }
  .doc-header { display: none; }
  .amount { text-align: right; }
  .table-sm th, .table-sm td { padding-top: .35rem; padding-bottom: .35rem; }
  @media (max-width: 576px) { .table { font-size: .9rem; } }
</style>
<?php if (!$isEmbed): ?>
  <div class="no-print mb-3">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      <button id="toggleAddrEditor" class="btn btn-outline-secondary" type="button"><i class="bi bi-pencil-square"></i> Edit Header Addresses</button>
    </div>
    <div id="addrEditor" style="display:none">
      <div class="card card-body p-2">
        <div class="mb-2 small text-muted">Enter one address per line. Changes affect only this print.</div>
        <textarea id="addrTextarea" class="form-control" rows="4"></textarea>
        <div class="mt-2 d-flex gap-2">
          <button id="applyAddr" class="btn btn-success" type="button"><i class="bi bi-check"></i> Apply</button>
          <button id="applyAndPrint" class="btn btn-primary" type="button"><i class="bi bi-printer"></i> Apply & Print</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="rpt-root rpt-root--b5">
  <article class="rpt-sheet">
    <?php include __DIR__ . '/../partials/report/letterhead.php'; ?>

    <div class="rpt-table-wrap table-responsive">
<table class="table table-sm table-bordered align-middle mb-0 rpt-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Delivery Date</th>
      <th>Customer</th>
      <th>Phone</th>
      <th>Supplier</th>
      <th>Supplier Phone</th>
      <th>Tracking</th>
      <th>Items</th>
      <th>Weight</th>
      <th class="text-end">Amount</th>
    </tr>
  </thead>
  <tbody>
  <?php $total = 0; foreach ($items as $i): $total += (float)$i['amount']; ?>
    <tr>
      <td><?php echo (int)$dn['id']; ?></td>
      <td><?php echo htmlspecialchars($dn['delivery_date']); ?></td>
      <td><?php echo htmlspecialchars($dn['customer_name']); ?></td>
      <td><?php echo htmlspecialchars($dn['customer_phone']); ?></td>
      <td><?php echo htmlspecialchars($i['supplier_name'] ?? ''); ?></td>
      <td><?php echo htmlspecialchars($i['supplier_phone'] ?? ''); ?></td>
      <td><?php echo htmlspecialchars($i['tracking_number'] ?? ''); ?></td>
      <td><?php $desc = trim((string)($i['item_descriptions'] ?? '')); echo $desc !== '' ? htmlspecialchars($desc) : '—'; ?></td>
      <td><?php echo number_format((float)($i['weight'] ?? 0), 2); ?></td>
      <td class="text-end"><?php echo number_format((float)$i['amount'], 2); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <?php 
      $disc = (float)($dn['discount'] ?? 0);
      $subtotal = (float)$total;
      if ($subtotal <= 0 && isset($dn['total_amount'])) { $subtotal = (float)$dn['total_amount']; }
      $net = $subtotal + $disc;
    ?>
    <tr>
      <th colspan="8" class="text-end">Subtotal</th>
      <th class="text-end"><?php echo number_format($subtotal, 2); ?></th>
    </tr>
    <?php if ($disc != 0): ?>
    <tr>
      <th colspan="8" class="text-end">Discount</th>
      <th class="text-end"><?php echo ($disc>0?'+':'').number_format($disc, 2); ?></th>
    </tr>
    <?php endif; ?>
    <tr>
      <th colspan="8" class="text-end">Net Total</th>
      <th class="text-end"><?php echo number_format($net, 2); ?></th>
    </tr>
  </tfoot>
</table>
    </div>

    <?php include __DIR__ . '/../partials/report/footer.php'; ?>
  </article>
</div>
<?php if (!$isEmbed): ?>
<script>
  window.addEventListener('load', function(){
    setTimeout(function(){
      if (window.matchMedia) { window.print(); }
    }, 50);
  });
  window.addEventListener('afterprint', function(){ /* window.close(); */ });
  (function(){
    var toggleBtn = document.getElementById('toggleAddrEditor');
    var ed = document.getElementById('addrEditor');
    var ta = document.getElementById('addrTextarea');
    if (!toggleBtn || !ed || !ta) return;
    function getCurrentLines(){
      var nodes = document.querySelectorAll('#addrContainer .addr-line');
      var arr = [];
      for (var i=0;i<nodes.length;i++){ var t = nodes[i].textContent.trim(); if(t) arr.push(t); }
      return arr;
    }
    ta.value = getCurrentLines().join('\n');
    toggleBtn.addEventListener('click', function(){ ed.style.display = (ed.style.display==='none' || ed.style.display==='') ? 'block' : 'none'; });
    function applyAddrs(){
      var cont = document.getElementById('addrContainer');
      if (!cont) return;
      var val = ta.value.replace(/\r/g,'');
      var parts = val.split('\n').map(function(s){ return s.trim(); }).filter(function(s){ return s.length>0; });
      cont.innerHTML = '';
      parts.forEach(function(line){
        var d = document.createElement('div');
        d.className = 'addr-line col';
        d.textContent = line;
        cont.appendChild(d);
      });
    }
    document.getElementById('applyAddr').addEventListener('click', function(){ applyAddrs(); });
    document.getElementById('applyAndPrint').addEventListener('click', function(){ applyAddrs(); window.print(); });
  })();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../partials/report/print_document_close.php'; ?>
