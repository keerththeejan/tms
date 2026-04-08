<?php
/** @var array $dn */ /** @var array $items */
$isEmbed = (($_GET['embed'] ?? '') === '1');
$paperParam = isset($_GET['paper']) ? strtolower(trim((string)$_GET['paper'])) : '80';
$receiptWidthPx = (in_array($paperParam, ['58', '58mm'], true)) ? 220 : 280;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delivery Note #<?php echo (int)$dn['id']; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root { --receipt-width: <?php echo (int)$receiptWidthPx; ?>px; }
    .text-end { text-align: right !important; }
    .text-muted { color: #6c757d !important; }
    .fw-bold { font-weight: 700 !important; }
    .small { font-size: 0.875em !important; }

    * { box-sizing: border-box; }
    html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body.dn-print-body {
      margin: 0;
      padding: <?php echo $isEmbed ? '6px' : '16px'; ?>;
      font-family: "Noto Sans Tamil", "Courier New", Courier, "Liberation Mono", monospace, sans-serif;
      font-size: 12px;
      line-height: 1.4;
      color: #000;
      background: #fff;
    }
    .receipt.thermal-receipt.dn-print-root {
      width: 100%;
      max-width: min(var(--receipt-width), 100%);
      margin: 0 auto;
    }
    .print-header-card {
      border: 1px solid #000;
      border-radius: 0;
      padding: 8px;
      margin-bottom: 10px;
      background: #fff;
      box-shadow: none;
    }
    .doc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .75rem; gap: 8px; }
    .doc-title { font-weight: 800; font-size: 1.1rem; color: #000; }
    .muted { color: #000; }
    .dn-cust-block { text-align: right; max-width: 58%; word-break: break-word; overflow-wrap: anywhere; }
    .dn-cust-block strong { font-weight: 800; }
    .dn-deliv {
      margin-top: 8px;
      padding-top: 8px;
      border-top: 1px solid #000;
      text-align: left;
      font-size: 11px;
      line-height: 1.45;
    }
    .dn-deliv strong { display: block; margin-bottom: 4px; }
    .amount { text-align: right; }
    .table-sm th, .table-sm td { padding-top: .35rem; padding-bottom: .35rem; vertical-align: top; }
    .table-bordered th, .table-bordered td { border: 1px solid #000 !important; }
    .table thead th {
      background: #fff !important;
      color: #000 !important;
      font-weight: 800;
      font-size: 10px;
    }
    <?php if ($isEmbed): ?>
    .table { margin-bottom: .75rem; }
    h4, .doc-title { margin-bottom: .25rem; }
    <?php endif; ?>
    @media (max-width: 576px) {
      .doc-header { flex-direction: column; }
      .dn-cust-block { max-width: 100%; text-align: left; }
    }
    @media print {
      .no-print { display: none !important; }
      @page { size: auto; margin: 0; }
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      body.dn-print-body {
        padding: 0 !important;
        font-family: "Noto Sans Tamil", "Courier New", Courier, monospace !important;
        font-size: 11px;
        color: #000 !important;
      }
      .print-header-card {
        border: 0 !important;
        padding: 0 0 6px 0 !important;
        margin-bottom: 6px !important;
      }
      .print-header-card .text-muted { color: #000 !important; }
      .muted { color: #000 !important; }
      .table { font-size: 9px; }
      .table thead th { font-size: 8px; }
      .receipt.thermal-receipt.dn-print-root {
        max-width: var(--receipt-width) !important;
      }
      .doc-header { page-break-inside: avoid; }
      .table-responsive { overflow: visible !important; }
    }
  </style>
</head>
<body class="dn-print-body">
<?php if (!$isEmbed): ?>
  <div class="no-print mb-3">
    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
      <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
      <button id="toggleAddrEditor" class="btn btn-outline-secondary" type="button"><i class="bi bi-pencil-square"></i> Edit Header Addresses</button>
      <span class="small text-muted">Print: scale 100%, margins None. Add <code>&amp;paper=58</code> for 58mm.</span>
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
<?php
  $brand = Helpers::company();
  $addresses = Helpers::companyHeaderAddressLines((string)($_GET['addr'] ?? ''), 50, 'all');
  $delLocDn = trim((string)($dn['customer_delivery_location'] ?? ''));
?>
<div class="receipt thermal-receipt dn-print-root">
<div class="mb-2 print-header-card">
  <div class="d-flex align-items-center gap-2">
    <?php $useLogoImage = (($brand['logo_display'] ?? 'builtin') === 'image') && !empty($brand['logo_url']); ?>
    <?php if ($useLogoImage): ?>
      <?php $logoUrl = $brand['logo_url']; $logoUrl = (strpos($logoUrl, 'http') === 0 || strpos($logoUrl, '//') === 0) ? $logoUrl : Helpers::baseUrl($logoUrl); ?>
      <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" style="height:38px;max-width:100%">
    <?php endif; ?>
    <div>
      <div class="fw-bold"><?php echo htmlspecialchars($brand['name'] ?? ''); ?></div>
      <div class="small text-muted">Transport and Parcel Services</div>
    </div>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-1 mt-1 small text-muted" id="addrContainer">
    <?php foreach ($addresses as $addr): ?>
      <div class="addr-line"><?php echo nl2br(htmlspecialchars($addr)); ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php
  $vehSet = [];
  foreach (($items ?? []) as $it) {
    $v = trim((string)($it['vehicle_no'] ?? ''));
    if ($v !== '') { $vehSet[$v] = true; }
  }
  $vehList = implode(', ', array_keys($vehSet));
?>
<div class="doc-header">
  <div>
    <div class="doc-title">Delivery Note #<?php echo (int)$dn['id']; ?></div>
    <div class="muted">Date: <?php echo htmlspecialchars($dn['delivery_date'] ?? ''); ?></div>
  </div>
  <div class="dn-cust-block">
    <div><strong>Customer:</strong> <?php echo htmlspecialchars($dn['customer_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    <div><strong>Phone:</strong> &lrm;<?php echo htmlspecialchars($dn['customer_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    <div><strong>Branch:</strong> <?php echo htmlspecialchars($dn['branch_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    <?php if ($vehList !== ''): ?><div><strong>Vehicle(s):</strong> <?php echo htmlspecialchars($vehList, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
  </div>
</div>
<?php if ($delLocDn !== ''): ?>
  <div class="dn-deliv">
    <strong>Delivery Location:</strong>
    <?php echo nl2br(htmlspecialchars($delLocDn, ENT_QUOTES, 'UTF-8')); ?>
  </div>
  <div style="letter-spacing:0.12em; margin:6px 0; font-size:10px;">-------------------</div>
<?php endif; ?>
<div class="table-responsive">
<table class="table table-sm table-bordered align-middle mb-0">
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
      <th colspan="9" class="text-end">Subtotal</th>
      <th class="text-end"><?php echo number_format($subtotal, 2); ?></th>
    </tr>
    <?php if ($disc != 0): ?>
    <tr>
      <th colspan="9" class="text-end">Discount</th>
      <th class="text-end"><?php echo ($disc>0?'+':'').number_format($disc, 2); ?></th>
    </tr>
    <?php endif; ?>
    <tr>
      <th colspan="9" class="text-end">Net Total</th>
      <th class="text-end"><?php echo number_format($net, 2); ?></th>
    </tr>
  </tfoot>
</table>
</div>
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
        d.className = 'addr-line';
        d.textContent = line;
        cont.appendChild(d);
      });
    }
    document.getElementById('applyAddr').addEventListener('click', function(){ applyAddrs(); });
    document.getElementById('applyAndPrint').addEventListener('click', function(){ applyAddrs(); window.print(); });
  })();
</script>
<?php endif; ?>
</body>
</html>
