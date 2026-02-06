<?php
/** @var array $parcel */ /** @var array $items */
$cfg = (require __DIR__ . '/../../config/config.php');
$brand = $cfg['company'] ?? [];
$branches = $brand['branches'] ?? [];
$logoArch = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_arch_color'] ?? 'c00') ?: 'c00');
$logoBarBg = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_bar_bg'] ?? '000') ?: '000');
$logoBarColor = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_bar_color'] ?? 'fff') ?: 'fff');
$logoTitleColor = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_title_color'] ?? 'c00') ?: 'c00');
if (strlen($logoArch) === 4) { $c = $logoArch[1].$logoArch[1].$logoArch[2].$logoArch[2].$logoArch[3].$logoArch[3]; $logoArch = '#'.$c; }
if (strlen($logoBarBg) === 4) { $c = $logoBarBg[1].$logoBarBg[1].$logoBarBg[2].$logoBarBg[2].$logoBarBg[3].$logoBarBg[3]; $logoBarBg = '#'.$c; }
if (strlen($logoBarColor) === 4) { $c = $logoBarColor[1].$logoBarColor[1].$logoBarColor[2].$logoBarColor[2].$logoBarColor[3].$logoBarColor[3]; $logoBarColor = '#'.$c; }
if (strlen($logoTitleColor) === 4) { $c = $logoTitleColor[1].$logoTitleColor[1].$logoTitleColor[2].$logoTitleColor[2].$logoTitleColor[3].$logoTitleColor[3]; $logoTitleColor = '#'.$c; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Parcel Receipt #<?php echo (int)$parcel['id']; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand:<?php echo $logoTitleColor; ?>; --logo-arch:<?php echo $logoArch; ?>; --logo-bar-bg:<?php echo $logoBarBg; ?>; --logo-bar-color:<?php echo $logoBarColor; ?>; }
    body { padding: 24px; font-size: 13px; background: #fff; color: #333; }
    .sheet { border: 2px solid #333; border-radius: 6px; overflow: hidden; background: #fff; }
    .sheet-header { background: #fff; position: relative; }
    .header-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 12px 16px 10px; }
    .header-brand { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .logo-unit { display: flex; flex-direction: column; align-items: flex-start; }
    .logo-unit .logo-wrap { width: 64px; height: 48px; display: flex; align-items: center; justify-content: center; background: var(--logo-arch); border-radius: 50% 50% 0 0; font-weight: 800; font-size: 18px; color: var(--logo-bar-color); border: 2px solid #333; position: relative; }
    .logo-unit .logo-wrap::before { content: ''; position: absolute; left: 0; right: 0; top: 0; bottom: 0; border-radius: inherit; background: repeating-linear-gradient(0deg, #000 0, #000 2px, transparent 2px, transparent 6px); opacity: 0.4; pointer-events: none; }
    .logo-unit .bar-small { background: var(--logo-bar-bg); color: var(--logo-bar-color); padding: 4px 10px; font-size: 10px; font-weight: 700; letter-spacing: 1px; margin-top: 2px; }
    .brand-title { font-weight: 900; font-size: 26px; letter-spacing: 2px; color: var(--brand); text-transform: uppercase; line-height: 1.1; }
    .reg-no { font-size: 12px; color: #333; font-weight: 600; position: absolute; top: 12px; right: 16px; }
    .route-bar { background: #000; color: #fff; padding: 10px 16px; font-size: 14px; text-align: center; display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 6px 10px; }
    .route-bar .route-part { white-space: nowrap; }
    .route-bar .arrow-double { color: #ffc107; font-size: 18px; margin: 0 4px; }
    .branch-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px 18px; padding: 14px 16px; border-bottom: 1px solid #ddd; }
    .branch-col { display: flex; flex-direction: column; gap: 4px; font-size: 11px; }
    .branch-col .branch-name { font-weight: 700; font-size: 12px; color: #333; margin-bottom: 2px; }
    .branch-col .addr-ta { color: #333; line-height: 1.35; }
    .branch-col .addr-en { color: #555; line-height: 1.35; }
    .branch-col .addr-phones { color: #333; }
    .branch-col .addr-phones::before { content: "📞 "; }
    .header-date { padding: 10px 16px 12px; font-size: 13px; color: #333; text-align: right; }
    .header-date .date-placeholder { border-bottom: 1px dotted #333; padding: 0 6px 2px; margin: 0 2px; min-width: 28px; display: inline-block; }
    .meta-row { padding: 8px 14px; background: #f8f9fa; border-top: 1px solid #ddd; display: flex; flex-wrap: wrap; gap: 12px 20px; font-size: 12px; }
    .meta-row strong { margin-right: 4px; }
    .invoice-no-block { padding: 10px 16px 8px; text-align: center; }
    .invoice-no-block .invoice-no-title { color: #c00; font-size: 1.25rem; font-weight: 700; }
    .invoice-no-block .invoice-no-line { border-bottom: 1px solid #000; margin-top: 4px; }
    .addr { font-size: 11px; color: #333; }
    table.receipt { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.receipt th, table.receipt td { border: 1px solid #333; padding: 8px; vertical-align: middle; }
    table.receipt th { background: #f0f0f0; text-transform: uppercase; font-size: 12px; font-weight: 700; }
    .totals { border-top: 2px solid #333; background: #e8e8e8; font-weight: 700; }
    .serial-big { font-size: 20px; font-weight: 800; letter-spacing: 2px; color: var(--brand); }
    .sig-line { border-top: 1px dashed #888; padding-top: 4px; text-align: center; }
    .note { font-size: 11px; color: #555; }
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; background: #fff; }
      .sheet { border-color: #000; background: #fff; box-shadow: none; }
      .sheet-header { break-inside: avoid; }
      .route-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .logo-unit .bar-small { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .invoice-no-block .invoice-no-title { -webkit-print-color-adjust: exact; print-color-adjust: exact; color: #c00; }
    }
  </style>
  </head>
<body>
<div class="no-print mb-3 d-flex flex-column gap-2">
  <div class="d-flex gap-2">
    <a class="btn btn-secondary" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=parcels')); ?>">Back</a>
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <?php if (empty($branches)): ?>
    <button id="toggleAddrEditor" class="btn btn-outline-secondary" type="button">Edit Header Addresses</button>
    <?php endif; ?>
  </div>
  <div id="addrEditor" style="display:none">
    <div class="card card-body p-2">
      <div class="mb-2 small text-muted">Enter one address per line. Changes affect only this print.</div>
      <textarea id="addrTextarea" class="form-control" rows="4"></textarea>
      <div class="mt-2 d-flex gap-2">
        <button id="applyAddr" class="btn btn-success" type="button">Apply</button>
        <button id="applyAndPrint" class="btn btn-primary" type="button">Apply & Print</button>
      </div>
    </div>
  </div>
  </div>

<?php
$regNo = $brand['reg_no'] ?? '';
$routeTamilParts = $brand['route_tamil_parts'] ?? ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'];
$addrParam = (string)($_GET['addr'] ?? '');
if ($addrParam !== '') { $addrParam = str_replace(["\r"], '', $addrParam); }
$addresses = [];
if ($addrParam !== '') {
  $tmp = explode("\n", $addrParam);
  foreach ($tmp as $a) { $a = trim($a); if ($a !== '') { $addresses[] = $a; } }
} elseif (!empty($branches)) {
  foreach ($branches as $b) {
    $addresses[] = ($b['address_en'] ?? '') . ' | ' . ($b['phones'] ?? '');
  }
} else {
  foreach (($brand['addresses'] ?? []) as $a) { $a = trim((string)$a); if ($a !== '') { $addresses[] = $a; } }
}
$parcelDate = substr((string)($parcel['created_at'] ?? date('Y-m-d')), 0, 10);
$parcelDateParts = explode('-', $parcelDate);
$invoiceNo = (int)($parcel['invoice_no'] ?? 0) > 0 ? (int)$parcel['invoice_no'] : (int)$parcel['id'];
?>
<div class="sheet">
  <div class="sheet-header">
    <?php if ($regNo !== ''): ?>
      <div class="reg-no">Reg No: <?php echo htmlspecialchars($regNo); ?></div>
    <?php endif; ?>
    <div class="header-top">
      <div class="header-brand">
        <?php
          $useLogoImage = (($brand['logo_display'] ?? 'builtin') === 'image') && !empty($brand['logo_url']);
          $logoInitials = trim($brand['logo_initials'] ?? 'TS') ?: 'TS';
          $logoInitials = mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $logoInitials), 0, 6) ?: 'TS';
        ?>
        <?php if ($useLogoImage): ?>
          <?php $logoUrl = $brand['logo_url']; $logoUrl = (strpos($logoUrl, 'http') === 0 || strpos($logoUrl, '//') === 0) ? $logoUrl : Helpers::baseUrl($logoUrl); ?>
          <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" style="height:56px">
        <?php else: ?>
          <div class="logo-unit">
            <div class="logo-wrap"><?php echo htmlspecialchars(strtoupper($logoInitials)); ?></div>
            <span class="bar-small"><?php echo htmlspecialchars(strtoupper($brand['name'] ?? 'TS Transport')); ?></span>
          </div>
        <?php endif; ?>
        <div class="brand-title"><?php echo htmlspecialchars($brand['name'] ?? 'TS Transport'); ?></div>
      </div>
    </div>
    <div class="route-bar">
      <?php foreach ($routeTamilParts as $i => $part): ?>
        <?php if ($i > 0): ?><span class="arrow-double">⟷</span><?php endif; ?>
        <span class="route-part"><?php echo htmlspecialchars($part); ?></span>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($branches)): ?>
      <div class="branch-cols">
        <?php foreach ($branches as $b): ?>
          <div class="branch-col">
            <div class="branch-name"><?php echo htmlspecialchars($b['name'] ?? ''); ?></div>
            <?php if (!empty($b['address_ta'])): ?>
              <div class="addr-ta"><?php echo htmlspecialchars($b['address_ta']); ?></div>
            <?php endif; ?>
            <?php if (!empty($b['address_en'])): ?>
              <div class="addr-en"><?php echo htmlspecialchars($b['address_en']); ?></div>
            <?php endif; ?>
            <?php if (!empty($b['phones'])): ?>
              <div class="addr-phones"><?php echo htmlspecialchars($b['phones']); ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="branch-cols" id="addrContainer">
        <?php foreach ($addresses as $addr): ?>
          <div class="branch-col addr-line"><?php echo nl2br(htmlspecialchars($addr)); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="header-date">
      <strong>Date:</strong>
      <span class="date-placeholder"><?php echo count($parcelDateParts) >= 1 ? $parcelDateParts[2] : ''; ?></span> /
      <span class="date-placeholder"><?php echo count($parcelDateParts) >= 2 ? $parcelDateParts[1] : ''; ?></span> /
      <span class="date-placeholder"><?php echo count($parcelDateParts) >= 3 ? $parcelDateParts[0] : ''; ?></span>
    </div>
    <div class="meta-row">
      <span><strong>Vehicle No:</strong> <?php echo htmlspecialchars($parcel['vehicle_no'] ?? '—'); ?></span>
      <span><strong>Customer:</strong> <?php echo htmlspecialchars($parcel['customer_name'] ?? ''); ?> (<?php echo htmlspecialchars($parcel['customer_phone'] ?? ''); ?>)</span>
      <span><strong>Supplier:</strong> <?php echo htmlspecialchars($parcel['supplier_name'] ?? '—'); ?><?php echo !empty($parcel['supplier_phone']) ? ' (' . htmlspecialchars($parcel['supplier_phone']) . ')' : ''; ?></span>
    </div>
  </div>

  <div class="invoice-no-block">
    <div class="invoice-no-title">Invoice No. #<?php echo $invoiceNo; ?></div>
    <div class="invoice-no-line"></div>
  </div>

  <div class="p-3">
    <table class="receipt">
      <thead>
        <tr>
          <th style="width:10%">Qty</th>
          <th>Description</th>
          <th style="width:15%">Rate</th>
          <th style="width:10%">Rs</th>
          <th style="width:10%">Cts</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $total = 0.0;
          $parcelPrice = (float)($parcel['price'] ?? 0);
          $hasAnyItemRate = false;
          if ($items && count($items) > 0) {
            foreach ($items as $i) {
              if ((float)($i['rate'] ?? 0) > 0) { $hasAnyItemRate = true; break; }
            }
          }
          $useParcelPriceOnFirstRow = ($items && count($items) > 0 && !$hasAnyItemRate && $parcelPrice > 0);
          $firstRowDone = false;
          if ($items && count($items)>0) : foreach ($items as $i):
            $qty = (float)$i['qty'];
            $rate = (float)($i['rate'] ?? 0);
            $amt = $qty * $rate;
            if ($useParcelPriceOnFirstRow && !$firstRowDone) {
              $amt = $parcelPrice;
              $rate = ($qty > 0) ? ($parcelPrice / $qty) : $parcelPrice;
              $firstRowDone = true;
            }
            $total += $amt;
            $rs = floor($amt);
            $cts = (int)round(($amt - $rs) * 100);
        ?>
          <tr>
            <td><?php echo $qty>0?number_format($qty, 2):''; ?></td>
            <td><?php echo htmlspecialchars($i['description']); ?></td>
            <td class="text-end"><?php echo $rate>0?number_format($rate,2):''; ?></td>
            <td class="text-end"><?php echo $amt>0?number_format($rs):''; ?></td>
            <td class="text-end"><?php echo $amt>0?str_pad((string)$cts,2,'0',STR_PAD_LEFT):''; ?></td>
          </tr>
        <?php endforeach; else: // fallback to single line ?>
          <?php $amt = (float)($parcel['price'] ?? 0); $rs=floor($amt); $cts=(int)round(($amt-$rs)*100); ?>
          <tr>
            <td><?php echo number_format((float)($parcel['weight'] ?? 0), 2); ?></td>
            <td><?php echo htmlspecialchars($parcel['tracking_number'] ?? ''); ?></td>
            <td class="text-end"><?php echo $amt>0?number_format($amt,2):''; ?></td>
            <td class="text-end"><?php echo $amt>0?number_format($rs):''; ?></td>
            <td class="text-end"><?php echo $amt>0?str_pad((string)$cts,2,'0',STR_PAD_LEFT):''; ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <?php 
          // If items exist but their computed total is 0, fall back to parcel price
          $grand = ($items && count($items)>0) ? $total : (float)($parcel['price'] ?? 0);
          if (($items && count($items)>0) && ($grand <= 0)) {
            $grand = (float)($parcel['price'] ?? 0);
          }
          $grs=floor($grand); $gcts=(int)round(($grand-$grs)*100); 
        ?>
        <tr class="totals">
          <td colspan="3" class="text-end">Total</td>
          <td class="text-end"><?php echo number_format($grs); ?></td>
          <td class="text-end"><?php echo str_pad((string)$gcts,2,'0',STR_PAD_LEFT); ?></td>
        </tr>
      </tfoot>
    </table>

    <div class="row mt-4 align-items-end">
      <div class="col-6 note"><?php echo htmlspecialchars($brand['footer_note'] ?? ''); ?></div>
      <div class="col-3"><div class="sig-line">Handed Over</div></div>
      <div class="col-3"><div class="sig-line">Receiver</div></div>
    </div>

    <?php
      $displayGrand = ($items && count($items)>0) ? $total : (float)($parcel['price'] ?? 0);
      if (($items && count($items)>0) && $displayGrand <= 0) { $displayGrand = (float)($parcel['price'] ?? 0); }
      $dRs = floor($displayGrand);
      $dCts = (int)round(($displayGrand - $dRs) * 100);
    ?>
    <div class="d-flex justify-content-between mt-3 align-items-center">
      <div class="serial-big">Invoice No. #<?php echo $invoiceNo; ?></div>
      <div class="fw-bold" style="color: var(--brand); font-size: 1.1rem;">
        TOTAL RS <?php echo number_format($dRs); ?> CTS <?php echo str_pad((string)$dCts, 2, '0', STR_PAD_LEFT); ?>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    var t = document.getElementById('toggleAddrEditor');
    var ed = document.getElementById('addrEditor');
    var ta = document.getElementById('addrTextarea');
    if (!t || !ed || !ta) return;
    function getLines(){
      var nodes = document.querySelectorAll('#addrContainer .addr-line');
      var arr=[]; for (var i=0;i<nodes.length;i++){ var s=nodes[i].textContent.trim(); if(s) arr.push(s); }
      return arr;
    }
    ta.value = getLines().join('\n');
    t.addEventListener('click', function(){ ed.style.display = (ed.style.display==='none'||ed.style.display==='')?'block':'none'; });
    function apply(){
      var cont = document.getElementById('addrContainer'); if(!cont) return;
      var parts = ta.value.replace(/\r/g,'').split('\n').map(function(s){return s.trim();}).filter(Boolean);
      cont.innerHTML = '';
      parts.forEach(function(line){ var d=document.createElement('div'); d.className='col-md-4 addr-line'; d.textContent=line; cont.appendChild(d); });
    }
    document.getElementById('applyAddr').addEventListener('click', apply);
    document.getElementById('applyAndPrint').addEventListener('click', function(){ apply(); window.print(); });
  })();
</script>
</body>
</html>
