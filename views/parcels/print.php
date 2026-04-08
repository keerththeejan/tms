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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .text-end { text-align: right !important; }
    .text-muted { color: #6c757d !important; }
    .small { font-size: 0.875em !important; }

    * { box-sizing: border-box; }
    html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body.parcel-print-embed { padding: 5px; }
    body {
      margin: 0;
      padding: 5px;
      font-family: "Courier New", "Noto Sans Tamil", Courier, monospace;
      font-size: 12px;
      line-height: 1.2;
      color: #000;
      background: #fff;
    }
    body.matrix-print-page p,
    body.matrix-print-page div { margin: 2px 0; }
    .matrix-section { margin-bottom: 5px; }
    .matrix-root {
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
    }
    .matrix-sheet {
      border: 1px solid #000;
      padding: 5px;
      margin: 0 auto 8px;
      page-break-inside: avoid;
      box-shadow: none;
      border-radius: 0;
    }
    .matrix-logo-strip {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-bottom: 4px;
    }
    .matrix-logo-strip .logo-unit .logo-wrap {
      width: 40px;
      display: flex; align-items: center; justify-content: center;
      padding: 4px 2px;
      background: #ccc;
      font-weight: 800; font-size: 13px;
      color: #000;
      border: 1px solid #000;
      border-radius: 0;
    }
    .matrix-logo-strip .bar-small {
      background: #000;
      color: #fff;
      padding: 1px 4px;
      font-size: 7px;
      font-weight: 700;
      max-width: 48px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .matrix-reg {
      text-align: center;
      margin: 0 0 2px;
      font-size: 11px;
      line-height: 1.2;
    }
    .matrix-co-name {
      margin: 0 0 2px;
      text-align: center;
      font-size: 16px;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      color: #000;
    }
    .matrix-route {
      text-align: center;
      font-size: 11px;
      margin: 0 0 4px;
      padding-bottom: 3px;
      border-bottom: 1px solid #000;
      line-height: 1.2;
    }
    .matrix-route .sep { margin: 0 3px; }
    /* 3 equal columns — same alignment as branch header (left, tight) */
    .matrix-branches {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 4px 10px;
      font-size: 12px;
      line-height: 1.2;
      margin-bottom: 5px;
      padding-bottom: 4px;
      border-bottom: 1px solid #000;
      page-break-inside: avoid;
    }
    .matrix-bc { min-width: 0; word-wrap: break-word; overflow-wrap: anywhere; white-space: normal; text-align: left; }
    .matrix-bc-left, .matrix-bc-center, .matrix-bc-right { text-align: left; }
    .matrix-bc .bc-name { font-weight: 700; margin: 0 0 1px; }
    .matrix-bc .bc-line { margin: 0; padding: 0; font-size: 11px; line-height: 1.2; }
    .matrix-inv-meta {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 10px;
      margin: 4px 0 5px;
      font-weight: 700;
      line-height: 1.2;
    }
    .matrix-inv-meta .matrix-date { font-weight: 700; white-space: nowrap; }
    .matrix-customer-row {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 4px 10px;
      margin: 5px 0;
      padding: 4px 0;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      font-size: 12px;
      line-height: 1.2;
      page-break-inside: avoid;
    }
    .matrix-customer-row > div {
      min-width: 0;
      text-align: left;
      word-wrap: break-word;
      overflow-wrap: anywhere;
    }
    .matrix-customer-row strong {
      display: block;
      font-weight: 700;
      margin: 0 0 1px;
    }
    .matrix-customer-val { white-space: pre-wrap; margin: 0; padding: 0; }
    table.matrix-tbl {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      font-size: 12px;
    }
    table.matrix-tbl th,
    table.matrix-tbl td {
      border: 1px solid #000;
      padding: 2px 4px;
      vertical-align: top;
      word-wrap: break-word;
      white-space: normal;
    }
    table.matrix-tbl thead th {
      font-weight: 700;
      text-transform: uppercase;
      background: #fff;
      color: #000;
    }
    .matrix-th-qty, .matrix-td-qty { text-align: center; width: 12%; }
    .matrix-th-desc, .matrix-td-desc { text-align: left; width: 48%; }
    .matrix-th-rate, .matrix-td-rate { text-align: right; width: 18%; }
    .matrix-th-amt, .matrix-td-amt { text-align: right; width: 22%; }
    .matrix-total {
      margin-top: 5px;
      text-align: right;
      font-weight: 800;
      font-size: 12px;
      line-height: 1.2;
    }
    .matrix-footer-note {
      margin: 6px 0 4px;
      text-align: center;
      font-size: 11px;
      line-height: 1.25;
      word-wrap: break-word;
    }
    .matrix-dash {
      border: 0;
      border-top: 1px solid #000;
      margin: 4px 0;
    }
    .matrix-sigs {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      margin-top: 2px;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.2;
    }
    .matrix-sigs span {
      flex: 1;
      text-align: center;
      padding-top: 4px;
      border-top: 1px solid #000;
    }
    @media screen and (max-width: 720px) {
      .matrix-branches,
      .matrix-customer-row { grid-template-columns: 1fr; }
    }
    @media print {
      .no-print { display: none !important; }
      @page { size: A5 landscape; margin: 5mm; }
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      body {
        width: 100%;
        margin: 0;
        padding: 0;
        line-height: 1.2 !important;
        font-family: "Courier New", "Noto Sans Tamil", Courier, monospace !important;
        font-size: 12px !important;
        color: #000 !important;
      }
      .matrix-root { width: 100% !important; max-width: 100% !important; }
      .matrix-sheet {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
      }
      .matrix-branches {
        grid-template-columns: 1fr 1fr 1fr !important;
      }
      .matrix-bc-left, .matrix-bc-center, .matrix-bc-right { text-align: left !important; }
      table.matrix-tbl { font-size: 12px !important; }
      table.matrix-tbl th, table.matrix-tbl td {
        border: 1px solid #000 !important;
        padding: 2px 4px !important;
      }
    }
  </style>
</head>
<body class="matrix-print-page<?php echo $printEmbed ? ' parcel-print-embed' : ''; ?>">
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
      <p class="matrix-reg matrix-section">Reg: <?php echo htmlspecialchars($regNo); ?></p>
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
          $aligns = ['matrix-bc-left', 'matrix-bc-center', 'matrix-bc-right'];
          foreach ($invoiceHeaderBranches as $bi => $b):
        ?>
          <div class="matrix-bc <?php echo $aligns[$bi] ?? 'matrix-bc-left'; ?>">
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
        <div class="matrix-bc matrix-bc-left">
          <div class="addr-line"><?php echo $addrSlots[0] !== '' ? nl2br(htmlspecialchars($addrSlots[0])) : '&nbsp;'; ?></div>
        </div>
        <div class="matrix-bc matrix-bc-center">
          <div class="addr-line"><?php echo $addrSlots[1] !== '' ? nl2br(htmlspecialchars($addrSlots[1])) : '&nbsp;'; ?></div>
        </div>
        <div class="matrix-bc matrix-bc-right">
          <div class="addr-line"><?php echo $addrSlots[2] !== '' ? nl2br(htmlspecialchars($addrSlots[2])) : '&nbsp;'; ?></div>
        </div>
      <?php endif; ?>
    </div>

    <div class="matrix-inv-meta">
      <span>Invoice No: #<?php echo (int)$invoiceNo; ?></span>
      <span class="matrix-date">Date: <?php echo htmlspecialchars($dateInline !== '' ? $dateInline : $parcelDate); ?></span>
    </div>

    <?php if ($custNameDisp !== '' || $custPhoneDisp !== '' || $delLocDisp !== ''): ?>
    <section class="matrix-customer-row matrix-section" aria-label="Customer details">
      <div>
        <strong>Customer</strong>
        <div class="matrix-customer-val"><?php echo $custNameDisp !== '' ? htmlspecialchars($custNameDisp, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
      </div>
      <div>
        <strong>Phone</strong>
        <div class="matrix-customer-val"><?php echo $custPhoneDisp !== '' ? htmlspecialchars($custPhoneDisp, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
      </div>
      <div>
        <strong>Delivery</strong>
        <div class="matrix-customer-val"><?php echo $delLocDisp !== '' ? htmlspecialchars($delLocDisp, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
      </div>
    </section>
    <?php endif; ?>

    <table class="matrix-tbl">
      <colgroup>
        <col class="matrix-col-qty"><col class="matrix-col-desc"><col class="matrix-col-rate"><col class="matrix-col-amt">
      </colgroup>
      <thead>
        <tr>
          <th class="matrix-th-qty">Qty</th>
          <th class="matrix-th-desc">Description</th>
          <th class="matrix-th-rate">Rate</th>
          <th class="matrix-th-amt">Amount</th>
        </tr>
      </thead>
      <tbody><?php echo $tableBodyHtml; ?></tbody>
    </table>

    <div class="matrix-total">Total (Rs): <?php echo htmlspecialchars(number_format($grs)); ?></div>

    <?php if ($footerNoteDisplay !== ''): ?>
      <p class="matrix-footer-note"><?php echo htmlspecialchars($footerNoteDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <hr class="matrix-dash">
    <div class="matrix-sigs">
      <span>Handed Over</span>
      <span>Receiver</span>
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
