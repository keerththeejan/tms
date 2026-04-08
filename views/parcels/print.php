<?php
/** @var array $parcel */ /** @var array $items */
/** @var list<array{id:int,name:string,address_ta:string,address_en:string,phones:string}|null>|null $invoiceHeaderBranches Colombo / Kilinochchi / Mullaitivu columns */
/** @var bool $printEmbed When true (?embed=1): hide Back/Print toolbar for iframe/modal preview */
$printEmbed = !empty($printEmbed ?? false);
$brand = Helpers::company();
$invoiceHeaderBranches = isset($invoiceHeaderBranches) && is_array($invoiceHeaderBranches) ? array_values($invoiceHeaderBranches) : [null, null, null];
while (count($invoiceHeaderBranches) < 3) {
  $invoiceHeaderBranches[] = null;
}
$invoiceHeaderBranches = array_slice($invoiceHeaderBranches, 0, 3);
$hasInvoiceCols = false;
foreach ($invoiceHeaderBranches as $col) {
  if (!is_array($col)) {
    continue;
  }
  $n = trim((string)($col['name'] ?? ''));
  $ta = trim((string)($col['address_ta'] ?? ''));
  $en = trim((string)($col['address_en'] ?? ''));
  $ph = trim((string)($col['phones'] ?? ''));
  if ($n !== '' || $ta !== '' || $en !== '' || $ph !== '') {
    $hasInvoiceCols = true;
    break;
  }
}
$regNo = $brand['reg_no'] ?? '';
$routeTamilParts = $brand['route_tamil_parts'] ?? ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'];
$addresses = Helpers::companyHeaderAddressLines((string)($_GET['addr'] ?? ''), 12, 'all');
$parcelDate = substr((string)($parcel['created_at'] ?? date('Y-m-d')), 0, 10);
$parcelDateParts = explode('-', $parcelDate);
$dateInline = '';
if (count($parcelDateParts) >= 3) {
  $dateInline = $parcelDateParts[2] . '/' . $parcelDateParts[1] . '/' . $parcelDateParts[0];
}
$invoiceNo = (int)($parcel['invoice_no'] ?? 0) > 0 ? (int)$parcel['invoice_no'] : (int)$parcel['id'];

$footerNoteDisplay = trim((string)($brand['footer_note'] ?? ''));
if ($footerNoteDisplay !== '') {
  $footerNoteDisplay = preg_replace('/[\r\n]+/u', ' ', $footerNoteDisplay);
  $footerNoteDisplay = preg_replace('/\s+/u', ' ', $footerNoteDisplay);
}

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
$tableBodyHtml = '';
if ($items && count($items) > 0) {
  foreach ($items as $i) {
    $qty = (float)$i['qty'];
    $rate = (float)($i['rate'] ?? 0);
    $addAmt = (float)($i['additional_amount'] ?? 0);
    $amt = $qty * $rate + $addAmt;
    if ($useParcelPriceOnFirstRow && !$firstRowDone) {
      $amt = $parcelPrice;
      $rate = ($qty > 0) ? ($parcelPrice / $qty) : $parcelPrice;
      $firstRowDone = true;
    }
    $total += $amt;
    $rs = floor($amt);
    $tableBodyHtml .= '<tr>'
      . '<td class="matrix-td-qty">' . ($qty > 0 ? htmlspecialchars(number_format($qty, 2)) : '') . '</td>'
      . '<td class="matrix-td-desc">' . htmlspecialchars((string)$i['description']) . '</td>'
      . '<td class="matrix-td-rate">' . ($rate > 0 ? htmlspecialchars(number_format($rate, 2)) : '') . '</td>'
      . '<td class="matrix-td-amt">' . ($amt > 0 ? htmlspecialchars(number_format($rs)) : '') . '</td>'
      . '</tr>';
  }
} else {
  $amt = (float)($parcel['price'] ?? 0);
  $rs = floor($amt);
  $tableBodyHtml = '<tr>'
    . '<td class="matrix-td-qty">' . htmlspecialchars(number_format((float)($parcel['weight'] ?? 0), 2)) . '</td>'
    . '<td class="matrix-td-desc">' . htmlspecialchars((string)($parcel['tracking_number'] ?? '')) . '</td>'
    . '<td class="matrix-td-rate">' . ($amt > 0 ? htmlspecialchars(number_format($amt, 2)) : '') . '</td>'
    . '<td class="matrix-td-amt">' . ($amt > 0 ? htmlspecialchars(number_format($rs)) : '') . '</td>'
    . '</tr>';
  $total = $amt;
}
$grand = ($items && count($items) > 0) ? $total : (float)($parcel['price'] ?? 0);
if (($items && count($items) > 0) && ($grand <= 0)) {
  $grand = (float)($parcel['price'] ?? 0);
}
$grs = floor($grand);

$useLogoImage = (($brand['logo_display'] ?? 'builtin') === 'image') && !empty($brand['logo_url']);
$logoInitials = trim($brand['logo_initials'] ?? 'TS') ?: 'TS';
$logoInitials = preg_replace('/[^A-Za-z0-9]/', '', $logoInitials);
if (function_exists('mb_substr')) {
  $logoInitials = mb_substr($logoInitials, 0, 6);
} else {
  $logoInitials = substr($logoInitials, 0, 6);
}
$logoInitials = $logoInitials ?: 'TS';

$custNameDisp = trim((string)($parcel['customer_name'] ?? ''));
$custPhoneDisp = trim((string)($parcel['customer_phone'] ?? ''));
$delLocDisp = trim((string)($parcel['delivery_location'] ?? ''));
$addrSlots = array_values($addresses);
while (count($addrSlots) < 3) {
  $addrSlots[] = '';
}
$addrSlots = array_slice($addrSlots, 0, 3);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invoice #<?php echo (int)$parcel['id']; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .text-muted { color: #6c757d !important; }
    .small { font-size: 0.875em !important; }

    * { box-sizing: border-box; line-height: 1.2; }
    html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body.parcel-print-embed { padding: 0; }
    body {
      margin: 0;
      padding: 0;
      font-family: "Courier New", monospace;
      font-size: 12px;
      line-height: 1.2;
      color: #000;
      background: #fff;
    }
    .matrix-root {
      width: 100%;
      max-width: 100%;
      margin: 0;
      padding: 5px;
    }
    .no-print.matrix-logo-strip {
      display: flex;
      align-items: flex-start;
      gap: 6px;
      margin: 0 0 2px;
      padding: 0;
    }
    .no-print.matrix-logo-strip img {
      display: block;
      margin: 0;
      padding: 0;
    }
    .no-print.matrix-logo-strip .logo-wrap {
      border: 1px solid #000;
      padding: 4px 6px;
      font-weight: bold;
      font-size: 12px;
      background: #fff;
    }
    .no-print.matrix-logo-strip .bar-small {
      border: 1px solid #000;
      padding: 1px 4px;
      font-size: 8px;
      font-weight: bold;
      background: #fff;
    }
    .matrix-sheet {
      border: 1px solid #000;
      padding: 3px;
      margin: 0 auto;
      page-break-inside: avoid;
      border-radius: 0;
    }
    .matrix-sheet h1,
    .matrix-sheet h2,
    .matrix-sheet h3,
    .matrix-sheet p {
      margin: 2px 0;
      padding: 0;
    }
    .matrix-co-name {
      margin: 0;
      padding: 5px 0 0;
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .matrix-reg-row {
      text-align: right;
      font-size: 11px;
      margin: 0;
      padding: 0 0 5px;
      line-height: 1.2;
    }
    .matrix-route {
      text-align: center;
      font-size: 11px;
      margin: 0 0 2px;
      padding: 0 0 3px;
      line-height: 1.2;
      border-bottom: 1px solid #000;
    }
    .matrix-route .sep { margin: 0 3px; }
    .matrix-branches {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 4px 8px;
      font-size: 11px;
      line-height: 1.2;
      margin: 0 0 3px;
      padding-bottom: 3px;
      border-bottom: 1px solid #000;
      page-break-inside: avoid;
    }
    .matrix-bc { min-width: 0; word-wrap: break-word; white-space: normal; }
    .matrix-bc-col1 { text-align: left; }
    .matrix-bc-col2 { text-align: center; }
    .matrix-bc-col3 { text-align: right; }
    .matrix-bc .bc-name { font-weight: bold; margin-bottom: 1px; }
    .matrix-bc .bc-line { margin: 0; padding: 0; font-size: 10px; line-height: 1.2; }
    .matrix-inv-wrap {
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 2px 0;
      margin: 3px 0 4px;
    }
    .matrix-inv-meta {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin: 0;
      font-weight: bold;
      line-height: 1.2;
    }
    .matrix-inv-meta .matrix-date { white-space: nowrap; }
    .customer-row {
      display: flex;
      justify-content: space-between;
      margin: 3px 0;
      font-size: 12px;
      line-height: 1.2;
    }
    .customer-row > div {
      width: 33.33%;
      min-width: 0;
      font-family: "Courier New", monospace;
      font-size: 12px;
      line-height: 1.2;
      word-wrap: break-word;
      overflow-wrap: anywhere;
    }
    .customer-row .col-left { text-align: left; }
    .customer-row .col-center { text-align: center; }
    .customer-row .col-right { text-align: right; }
    table.matrix-tbl {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      font-family: "Courier New", monospace;
      font-size: 12px;
    }
    table.matrix-tbl th,
    table.matrix-tbl td {
      border: 1px solid #000;
      padding: 1px 2px;
      vertical-align: top;
      word-wrap: break-word;
      white-space: normal;
    }
    table.matrix-tbl thead th {
      font-weight: bold;
      background: #fff;
      color: #000;
    }
    .matrix-th-qty, .matrix-td-qty { text-align: center; width: 12%; }
    .matrix-th-desc, .matrix-td-desc { text-align: left; width: 48%; }
    .matrix-th-rate, .matrix-td-rate { text-align: right; width: 18%; }
    .matrix-th-amt, .matrix-td-amt { text-align: right; width: 22%; }
    .matrix-total {
      margin-top: 3px;
      text-align: right;
      font-weight: bold;
      font-size: 12px;
      line-height: 1.2;
    }
    .matrix-footer-note {
      margin: 4px 0 2px;
      text-align: center;
      font-size: 11px;
      line-height: 1.2;
      word-wrap: break-word;
    }
    .matrix-sig-row {
      border-top: 1px solid #000;
      padding-top: 3px;
      margin-top: 3px;
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      font-weight: bold;
      line-height: 1.2;
    }
    .matrix-sig-l { text-align: left; }
    .matrix-sig-r { text-align: right; }
    @media screen and (max-width: 720px) {
      .matrix-branches { grid-template-columns: 1fr; }
      .matrix-bc-col1, .matrix-bc-col2, .matrix-bc-col3 { text-align: left; }
    }
    @media print {
      .no-print { display: none !important; }
      @page { size: A5 landscape; margin: 0; }
      body {
        margin: 0 !important;
        padding: 0 !important;
        font-family: "Courier New", monospace !important;
        font-size: 12px !important;
        line-height: 1.2;
        color: #000 !important;
      }
      .matrix-root { padding: 0 !important; margin: 0 !important; }
      .matrix-sheet {
        border: 1px solid #000 !important;
        border-radius: 0 !important;
        padding: 3px !important;
        margin: 0 auto !important;
      }
      .matrix-branches { grid-template-columns: 1fr 1fr 1fr !important; }
      .matrix-bc-col1 { text-align: left !important; }
      .matrix-bc-col2 { text-align: center !important; }
      .matrix-bc-col3 { text-align: right !important; }
      table.matrix-tbl th, table.matrix-tbl td {
        border: 1px solid #000 !important;
        padding: 1px 2px !important;
      }
    }
  </style>
</head>
<body<?php echo $printEmbed ? ' class="parcel-print-embed"' : ''; ?>>
<?php if (!$printEmbed): ?>
<div class="no-print mb-2 d-flex flex-wrap gap-2 align-items-center">
  <a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=parcels')); ?>">Back</a>
  <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
  <?php if (!$hasInvoiceCols): ?>
  <button type="button" id="toggleAddrEditor" class="btn btn-outline-secondary btn-sm">Edit header addresses</button>
  <?php endif; ?>
  <span class="small text-muted">Print: A5 landscape recommended — matrix-style invoice.</span>
</div>
<?php if (!$hasInvoiceCols): ?>
<div id="addrEditor" class="no-print card card-body p-2 mb-2" style="display:none; max-width:100%; margin:0 auto;">
  <div class="small text-muted mb-1">One address per line. Applies to this invoice only.</div>
  <textarea id="addrTextarea" class="form-control form-control-sm" rows="4"></textarea>
  <div class="mt-2 d-flex gap-2">
    <button type="button" id="applyAddr" class="btn btn-success btn-sm">Apply</button>
    <button type="button" id="applyAndPrint" class="btn btn-primary btn-sm">Apply &amp; Print</button>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="matrix-root a4-root receipt thermal-receipt">
  <article class="matrix-sheet invoice-sheet">
    <div class="no-print matrix-logo-strip">
      <?php if ($useLogoImage): ?>
        <?php $logoUrl = $brand['logo_url']; $logoUrl = (strpos($logoUrl, 'http') === 0 || strpos($logoUrl, '//') === 0) ? $logoUrl : Helpers::baseUrl($logoUrl); ?>
        <div><img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="" style="height:40px;max-width:120px"></div>
      <?php else: ?>
        <div class="logo-unit">
          <div class="logo-wrap"><?php echo htmlspecialchars(strtoupper($logoInitials)); ?></div>
          <?php
            $__bn = strtoupper((string)($brand['name'] ?? 'TS'));
            $__bn = function_exists('mb_substr') ? mb_substr($__bn, 0, 8) : substr($__bn, 0, 8);
          ?>
          <span class="bar-small"><?php echo htmlspecialchars($__bn); ?></span>
        </div>
      <?php endif; ?>
    </div>

    <h1 class="matrix-co-name"><?php echo htmlspecialchars($brand['name'] ?? 'TS TRANSPORT'); ?></h1>
    <?php if ($regNo !== ''): ?>
      <div class="matrix-reg-row">Reg: <?php echo htmlspecialchars($regNo); ?></div>
    <?php endif; ?>

    <div class="matrix-route">
      <?php foreach ($routeTamilParts as $i => $part): ?>
        <?php if ($i > 0): ?><span class="sep">|</span><?php endif; ?>
        <span><?php echo htmlspecialchars($part); ?></span>
      <?php endforeach; ?>
    </div>

    <div class="matrix-branches<?php echo !$hasInvoiceCols ? ' js-addr-container' : ''; ?>" aria-label="Branch addresses">
      <?php if ($hasInvoiceCols): ?>
        <?php
          $bcAlign = ['matrix-bc-col1', 'matrix-bc-col2', 'matrix-bc-col3'];
          foreach ($invoiceHeaderBranches as $bi => $b):
        ?>
          <div class="matrix-bc <?php echo $bcAlign[$bi] ?? 'matrix-bc-col1'; ?>">
            <?php if (is_array($b)): ?>
              <?php
                $bn = trim((string)($b['name'] ?? ''));
                $bta = trim((string)($b['address_ta'] ?? ''));
                $ben = trim((string)($b['address_en'] ?? ''));
                $bph = trim((string)($b['phones'] ?? ''));
              ?>
              <?php if ($bn !== ''): ?><div class="bc-name"><?php echo htmlspecialchars($bn); ?></div><?php endif; ?>
              <?php if ($bta !== ''): ?><div class="bc-line"><?php echo htmlspecialchars($bta); ?></div><?php endif; ?>
              <?php if ($ben !== ''): ?><div class="bc-line"><?php echo htmlspecialchars($ben); ?></div><?php endif; ?>
              <?php if ($bph !== ''): ?><div class="bc-line"><?php echo htmlspecialchars(Helpers::formatPhonesDisplay($bph)); ?></div><?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="matrix-bc matrix-bc-col1">
          <div class="addr-line"><?php echo $addrSlots[0] !== '' ? nl2br(htmlspecialchars($addrSlots[0])) : '&nbsp;'; ?></div>
        </div>
        <div class="matrix-bc matrix-bc-col2">
          <div class="addr-line"><?php echo $addrSlots[1] !== '' ? nl2br(htmlspecialchars($addrSlots[1])) : '&nbsp;'; ?></div>
        </div>
        <div class="matrix-bc matrix-bc-col3">
          <div class="addr-line"><?php echo $addrSlots[2] !== '' ? nl2br(htmlspecialchars($addrSlots[2])) : '&nbsp;'; ?></div>
        </div>
      <?php endif; ?>
    </div>

    <div class="matrix-inv-wrap">
      <div class="matrix-inv-meta">
        <span>Invoice No: #<?php echo (int)$invoiceNo; ?></span>
        <span class="matrix-date">Date: <?php echo htmlspecialchars($dateInline !== '' ? $dateInline : $parcelDate); ?></span>
      </div>
    </div>

    <?php if ($custNameDisp !== '' || $custPhoneDisp !== '' || $delLocDisp !== ''): ?>
    <div class="customer-row" aria-label="Customer details">
      <div class="col-left">Customer: <?php echo $custNameDisp !== '' ? htmlspecialchars($custNameDisp, ENT_QUOTES, 'UTF-8') : ''; ?></div>
      <div class="col-center">Phone: <?php echo $custPhoneDisp !== '' ? htmlspecialchars($custPhoneDisp, ENT_QUOTES, 'UTF-8') : ''; ?></div>
      <div class="col-right">Delivery Location:<?php if ($delLocDisp !== ''): ?><br><?php echo nl2br(htmlspecialchars($delLocDisp, ENT_QUOTES, 'UTF-8')); ?><?php endif; ?></div>
    </div>
    <?php endif; ?>

    <table class="matrix-tbl">
      <thead>
        <tr>
          <th class="matrix-th-qty">QTY</th>
          <th class="matrix-th-desc">DESCRIPTION</th>
          <th class="matrix-th-rate">RATE</th>
          <th class="matrix-th-amt">AMOUNT</th>
        </tr>
      </thead>
      <tbody><?php echo $tableBodyHtml; ?></tbody>
    </table>

    <div class="matrix-total">Total (Rs): <?php echo htmlspecialchars(number_format($grs)); ?></div>

    <?php if ($footerNoteDisplay !== ''): ?>
      <p class="matrix-footer-note"><?php echo htmlspecialchars($footerNoteDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <div class="matrix-sig-row">
      <span class="matrix-sig-l">Handed Over</span>
      <span class="matrix-sig-r">Receiver</span>
    </div>
  </article>
</div>

<script>
(function () {
  var t = document.getElementById('toggleAddrEditor');
  var ed = document.getElementById('addrEditor');
  var ta = document.getElementById('addrTextarea');
  if (t && ed && ta) {
    function getLines() {
      var cont = document.querySelector('.js-addr-container');
      if (!cont) return [];
      var nodes = cont.querySelectorAll('.matrix-bc .addr-line');
      var arr = [];
      for (var i = 0; i < nodes.length; i++) {
        arr.push(nodes[i].textContent.replace(/\r/g, '').trim());
      }
      while (arr.length < 3) arr.push('');
      return arr.slice(0, 3);
    }
    ta.value = getLines().join('\n');
    t.addEventListener('click', function () {
      ed.style.display = ed.style.display === 'none' || ed.style.display === '' ? 'block' : 'none';
    });
    function apply() {
      var raw = ta.value.replace(/\r/g, '').split('\n');
      var parts = [0, 1, 2].map(function (i) { return (raw[i] !== undefined ? raw[i] : '').trim(); });
      var cont = document.querySelector('.js-addr-container');
      if (!cont) return;
      var cols = cont.querySelectorAll('.matrix-bc .addr-line');
      for (var j = 0; j < cols.length; j++) {
        cols[j].innerHTML = parts[j] !== '' ? parts[j].replace(/\n/g, '<br>') : '&nbsp;';
      }
    }
    var a = document.getElementById('applyAddr');
    var ap = document.getElementById('applyAndPrint');
    if (a) a.addEventListener('click', apply);
    if (ap) ap.addEventListener('click', function () { apply(); window.print(); });
  }
})();
</script>
<!--
  Optional ESC/POS (raw thermal): generate fixed-width text or ESC/POS bytes server-side
  and send to a receipt printer via OS print agent, USB, or network socket — not via HTML.
  Typical flow: build lines with str_pad() for columns; GS v 0 for bitmap logo; LF line feeds.
-->
</body>
</html>
