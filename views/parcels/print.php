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
$logoArch = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_arch_color'] ?? 'c00') ?: 'c00');
$logoBarBg = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_bar_bg'] ?? '000') ?: '000');
$logoBarColor = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_bar_color'] ?? 'fff') ?: 'fff');
$logoTitleColor = '#' . (preg_replace('/[^0-9a-fA-F]/', '', $brand['logo_title_color'] ?? 'c00') ?: 'c00');
if (strlen($logoArch) === 4) { $c = $logoArch[1].$logoArch[1].$logoArch[2].$logoArch[2].$logoArch[3].$logoArch[3]; $logoArch = '#'.$c; }
if (strlen($logoBarBg) === 4) { $c = $logoBarBg[1].$logoBarBg[1].$logoBarBg[2].$logoBarBg[2].$logoBarBg[3].$logoBarBg[3]; $logoBarBg = '#'.$c; }
if (strlen($logoBarColor) === 4) { $c = $logoBarColor[1].$logoBarColor[1].$logoBarColor[2].$logoBarColor[2].$logoBarColor[3].$logoBarColor[3]; $logoBarColor = '#'.$c; }
if (strlen($logoTitleColor) === 4) { $c = $logoTitleColor[1].$logoTitleColor[1].$logoTitleColor[2].$logoTitleColor[2].$logoTitleColor[3].$logoTitleColor[3]; $logoTitleColor = '#'.$c; }

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
      . '<td class="text-end">' . ($qty > 0 ? htmlspecialchars(number_format($qty, 2)) : '') . '</td>'
      . '<td>' . htmlspecialchars((string)$i['description']) . '</td>'
      . '<td class="text-end">' . ($rate > 0 ? htmlspecialchars(number_format($rate, 2)) : '') . '</td>'
      . '<td class="text-end">' . ($amt > 0 ? htmlspecialchars(number_format($rs)) : '') . '</td>'
      . '</tr>';
  }
} else {
  $amt = (float)($parcel['price'] ?? 0);
  $rs = floor($amt);
  $tableBodyHtml = '<tr>'
    . '<td class="text-end">' . htmlspecialchars(number_format((float)($parcel['weight'] ?? 0), 2)) . '</td>'
    . '<td>' . htmlspecialchars((string)($parcel['tracking_number'] ?? '')) . '</td>'
    . '<td class="text-end">' . ($amt > 0 ? htmlspecialchars(number_format($amt, 2)) : '') . '</td>'
    . '<td class="text-end">' . ($amt > 0 ? htmlspecialchars(number_format($rs)) : '') . '</td>'
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
    /* Minimal Bootstrap fallbacks for iframe previews.
       If external CSS (Bootstrap) is blocked on cPanel, these keep alignment consistent. */
    .text-end { text-align: right !important; }
    .text-muted { color: #6c757d !important; }
    .fw-bold { font-weight: 700 !important; }
    .small { font-size: 0.875em !important; }

    :root {
      --brand: <?php echo $logoTitleColor; ?>;
      --logo-arch: <?php echo $logoArch; ?>;
      --logo-bar-bg: <?php echo $logoBarBg; ?>;
      --logo-bar-color: <?php echo $logoBarColor; ?>;
      --inv-pad: 6px;
      --inv-gap: 4px;
    }
    * { box-sizing: border-box; }
    html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body.parcel-print-embed {
      padding: 4px;
    }
    body {
      margin: 0;
      padding: 8px;
      font-family: system-ui, "Segoe UI", Roboto, "Noto Sans Tamil", "Noto Sans", "Latha", Tahoma, sans-serif;
      font-size: 11px;
      line-height: 1.25;
      color: #111;
      background: #fff;
    }
    .a4-root {
      width: 100%;
      max-width: min(210mm, 100%);
      margin: 0 auto;
      padding: 0 4px;
    }
    .invoice-sheet {
      border: 1px solid #000;
      padding: var(--inv-pad);
      margin-bottom: 12px;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .inv-header {
      display: grid;
      grid-template-columns: 44px 1fr auto;
      align-items: center;
      gap: 6px 8px;
      padding-bottom: var(--inv-gap);
      border-bottom: 1px solid #000;
    }
    .inv-logo-img { max-height: 36px; width: auto; display: block; }
    .logo-unit { display: flex; flex-direction: column; align-items: flex-start; }
    .logo-unit .logo-wrap {
      width: 40px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      background: var(--logo-arch);
      border-radius: 50% 50% 0 0;
      font-weight: 800; font-size: 13px;
      color: var(--logo-bar-color);
      border: 1px solid #000;
    }
    .logo-unit .bar-small {
      background: var(--logo-bar-bg);
      color: var(--logo-bar-color);
      padding: 1px 5px;
      font-size: 7px;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin-top: 1px;
      max-width: 44px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .inv-company {
      text-align: center;
      font-weight: 800;
      font-size: 15px;
      line-height: 1.15;
      color: var(--brand);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .inv-regdate {
      text-align: right;
      font-size: 10px;
      font-weight: 600;
      line-height: 1.2;
      white-space: nowrap;
    }
    .inv-print-header-block {
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .inv-route {
      text-align: center;
      font-size: 11px;
      line-height: 1.2;
      padding: 2px 0;
      border-bottom: 1px solid #ccc;
    }
    .inv-route span.sep { color: #555; margin: 0 3px; }
    .inv-branches-3 {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      column-gap: 8px;
      row-gap: 0;
      padding: 3px 0 4px;
      border-bottom: 1px solid #ccc;
      font-size: 10.5px;
      line-height: 1.2;
      align-items: start;
    }
    .inv-branch-col {
      min-width: 0;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
    .inv-branches-3 .bc-name {
      font-weight: 700;
      font-size: 11px;
      line-height: 1.2;
      margin-bottom: 1px;
    }
    .inv-branches-3 .bc-line { font-size: 10px; line-height: 1.2; margin: 0; padding: 0; }
    .inv-invoice-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      gap: 8px;
      padding: 2px 0 3px;
      line-height: 1.2;
    }
    .inv-customer-row,
    .customer-row {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      align-items: center;
      column-gap: 8px;
      padding: 2px 0 3px;
      border-bottom: 1px solid #ccc;
      font-size: 11px;
      line-height: 1.2;
    }
    .inv-customer-row > div,
    .customer-row > div {
      min-width: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .inv-customer-left { text-align: left; }
    .inv-customer-center { text-align: center; }
    .inv-customer-right { text-align: right; }
    .inv-invoice-title {
      margin: 0;
      text-align: left;
      font-weight: 700;
      font-size: 13px;
      color: #b00;
    }
    .inv-invoice-date {
      text-align: right;
      font-size: 10px;
      font-weight: 600;
      color: #111;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .inv-body { display: block; }
    table.inv-tbl {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
      line-height: 1.2;
      table-layout: fixed;
    }
    table.inv-tbl th, table.inv-tbl td {
      border: 1px solid #000;
      padding: 3px 5px;
      vertical-align: top;
    }
    table.inv-tbl th {
      background: #eee;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 9px;
      letter-spacing: 0.02em;
    }
    table.inv-tbl col.qty { width: 11%; }
    table.inv-tbl col.desc { width: auto; }
    table.inv-tbl col.rate { width: 14%; }
    table.inv-tbl col.amt { width: 14%; }
    .inv-total-row {
      display: flex;
      justify-content: flex-end;
      align-items: baseline;
      gap: 8px;
      padding-top: 6px;
      padding-bottom: 10px;
      font-size: 12px;
      font-weight: 700;
    }
    .inv-footer {
      display: flex;
      flex-direction: column;
      gap: 16px;
      align-items: stretch;
      margin-top: 6px;
      padding-top: 8px;
      padding-bottom: 6px;
      font-size: 9px;
      line-height: 1.2;
    }
    .inv-footer .note {
      color: #444;
      font-size: 10px;
      line-height: 1.45;
      max-width: 100%;
      margin: 0;
      padding-bottom: 4px;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
    .inv-sigs {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: flex-end;
      width: 100%;
      column-gap: 20px;
      padding: 4px 4px 2px;
      margin-top: 0;
    }
    .inv-footer .sig {
      flex: 0 1 38%;
      min-width: 7.5rem;
      max-width: 42%;
      border-top: 1px dashed #333;
      padding-top: 3px;
      padding-bottom: 2px;
      text-align: center;
      font-weight: 600;
      font-size: 9px;
    }
    @media screen and (max-width: 640px) {
      body { padding: 6px; }
      .a4-root { padding: 0 2px; }
      .inv-header {
        grid-template-columns: 36px 1fr;
        grid-template-rows: auto auto;
      }
      .inv-logo-cell { grid-column: 1; grid-row: 1; }
      .inv-company { grid-column: 2; grid-row: 1; font-size: 13px; }
      .inv-regdate { grid-column: 1 / -1; grid-row: 2; text-align: right; }
      .inv-branches-3 {
        grid-template-columns: 1fr;
        row-gap: 10px;
        column-gap: 0;
        padding-bottom: 8px;
      }
      .inv-branch-col {
        padding-bottom: 6px;
        border-bottom: 1px solid #e5e5e5;
      }
      .inv-branch-col:last-child { border-bottom: none; padding-bottom: 0; }
      .inv-invoice-row { flex-wrap: wrap; gap: 4px; }
      .inv-sigs {
        column-gap: 12px;
        padding: 0;
      }
      .inv-footer .sig {
        flex: 1 1 0;
        min-width: 0;
        max-width: none;
        font-size: 8.5px;
        padding-top: 3px;
      }
    }
    @media print {
      .no-print { display: none !important; }
      @page { size: A4; margin: 8mm; }
      /* Dot matrix / impact: monospace, black ink, no faint grays or screen-only red */
      body {
        padding: 0;
        margin: 0;
        font-family: "Courier New", monospace;
        font-size: 12px;
        line-height: 1.4;
        color: #000 !important;
        background: #fff !important;
        -webkit-font-smoothing: auto;
        -moz-osx-font-smoothing: auto;
      }
      * {
        color: #000 !important;
        background: #fff !important;
        box-shadow: none !important;
        text-shadow: none !important;
        background-image: none !important;
      }
      .inv-company {
        color: #000 !important;
      }
      .inv-route {
        border-bottom: 1px solid #000 !important;
      }
      .inv-route .sep {
        color: #000 !important;
      }
      .inv-branches-3 {
        border-bottom: 1px solid #000 !important;
        font-size: 11px;
        column-gap: 6px;
      }
      .inv-customer-row,
      .customer-row {
        border-bottom: 1px solid #000 !important;
        font-size: 11px;
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
      }
      .inv-customer-row > div,
      .customer-row > div {
        display: table-cell !important;
        width: 33.33% !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
      }
      .inv-branches-3 .bc-name { font-size: 12px; }
      .inv-branches-3 .bc-line { font-size: 11px; }
      .inv-invoice-title {
        color: #000 !important;
        font-size: 14px !important;
      }
      .inv-invoice-date {
        color: #000 !important;
        font-size: 11px !important;
      }
      .inv-invoice-row {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
      }
      .inv-invoice-row > div {
        display: table-cell !important;
        width: 50% !important;
        white-space: nowrap !important;
        vertical-align: baseline !important;
      }
      .inv-invoice-row > div:last-child {
        text-align: right !important;
      }
      .inv-regdate {
        color: #000 !important;
        font-size: 11px !important;
      }
      table.inv-tbl {
        border-collapse: collapse !important;
        width: 100% !important;
        font-size: 11px;
      }
      table.inv-tbl,
      table.inv-tbl th,
      table.inv-tbl td {
        border: 1px solid #000 !important;
      }
      table.inv-tbl th,
      table.inv-tbl td {
        padding: 4px 6px !important;
      }
      table.inv-tbl th {
        background: #fff !important;
        color: #000 !important;
        font-size: 10px !important;
        font-weight: 800 !important;
        border-bottom: 1px solid #000 !important;
        text-transform: uppercase;
      }
      .inv-total-row {
        font-size: 13px !important;
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
      }
      .inv-total-row > span {
        display: table-cell !important;
        width: 50% !important;
      }
      .inv-total-row > span:last-child {
        text-align: right !important;
      }
      .inv-footer .note {
        color: #000 !important;
        font-size: 11px !important;
      }
      .invoice-sheet,
      .inv-header,
      .inv-footer,
      .inv-sigs,
      .inv-footer .sig {
        border-color: #000 !important;
      }
      .inv-footer .sig {
        border-top: 1px solid #000 !important;
        border-top-style: solid !important;
        font-size: 10px !important;
        font-weight: 700 !important;
      }
      .logo-unit .logo-wrap {
        border-color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .logo-unit .bar-small {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .a4-root {
        width: 100%;
        max-width: 210mm;
        margin: 0 auto;
        padding: 0;
        display: block;
      }
      .invoice-sheet {
        margin-bottom: 0;
        overflow: visible;
        page-break-inside: avoid;
        break-inside: avoid;
        box-shadow: none !important;
        border-color: #000 !important;
      }
      .inv-header {
        border-bottom: 1px solid #000 !important;
      }
      .inv-print-header-block {
        page-break-inside: avoid;
        break-inside: avoid;
      }
      .inv-footer { gap: 12px; padding-top: 6px; padding-bottom: 6px; }
      .inv-sigs { padding-top: 2px; }
      .inv-footer .sig { padding-top: 4px; }
    }
  </style>
</head>
<body<?php echo $printEmbed ? ' class="parcel-print-embed"' : ''; ?>>
<?php if (!$printEmbed): ?>
<div class="no-print mb-2 d-flex flex-wrap gap-2">
  <a class="btn btn-secondary btn-sm" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=parcels')); ?>">Back</a>
  <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
  <?php if (!$hasInvoiceCols): ?>
  <button type="button" id="toggleAddrEditor" class="btn btn-outline-secondary btn-sm">Edit header addresses</button>
  <?php endif; ?>
</div>
<?php if (!$hasInvoiceCols): ?>
<div id="addrEditor" class="no-print card card-body p-2 mb-2" style="display:none; max-width:210mm; margin:0 auto;">
  <div class="small text-muted mb-1">One address per line. Applies to this invoice only.</div>
  <textarea id="addrTextarea" class="form-control form-control-sm" rows="4"></textarea>
  <div class="mt-2 d-flex gap-2">
    <button type="button" id="applyAddr" class="btn btn-success btn-sm">Apply</button>
    <button type="button" id="applyAndPrint" class="btn btn-primary btn-sm">Apply &amp; Print</button>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="a4-root">
  <article class="invoice-sheet">
    <div class="inv-print-header-block">
    <header class="inv-header">
      <div class="inv-logo-cell">
        <?php if ($useLogoImage): ?>
          <?php $logoUrl = $brand['logo_url']; $logoUrl = (strpos($logoUrl, 'http') === 0 || strpos($logoUrl, '//') === 0) ? $logoUrl : Helpers::baseUrl($logoUrl); ?>
          <img class="inv-logo-img" src="<?php echo htmlspecialchars($logoUrl); ?>" alt="">
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
      <div class="inv-company"><?php echo htmlspecialchars($brand['name'] ?? 'TS Transport'); ?></div>
      <div class="inv-regdate">
        <?php if ($regNo !== ''): ?><div>Reg: <?php echo htmlspecialchars($regNo); ?></div><?php endif; ?>
      </div>
    </header>
    <div class="inv-route">
      <?php foreach ($routeTamilParts as $i => $part): ?>
        <?php if ($i > 0): ?><span class="sep">⟷</span><?php endif; ?>
        <span><?php echo htmlspecialchars($part); ?></span>
      <?php endforeach; ?>
    </div>
    <?php if ($hasInvoiceCols): ?>
      <div class="inv-branches-3" aria-label="Branch addresses">
        <?php foreach ($invoiceHeaderBranches as $b): ?>
          <div class="inv-branch-col">
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
      </div>
    <?php else: ?>
      <div class="inv-branches-3 js-addr-container">
        <?php foreach ($addresses as $addr): ?>
          <div class="addr-line"><?php echo nl2br(htmlspecialchars($addr)); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    </div>
    <div class="inv-customer-row customer-row" aria-label="Customer details">
      <div class="inv-customer-left left">Customer: <?php echo htmlspecialchars(trim((string)($parcel['customer_name'] ?? ''))); ?></div>
      <div class="inv-customer-center center">Phone: <?php echo htmlspecialchars(trim((string)($parcel['customer_phone'] ?? ''))); ?></div>
      <div class="inv-customer-right right">Delivery: <?php echo htmlspecialchars(trim((string)($parcel['delivery_location'] ?? ''))); ?></div>
    </div>
    <div class="inv-invoice-row">
      <div class="inv-invoice-title">Invoice No. #<?php echo (int)$invoiceNo; ?></div>
      <div class="inv-invoice-date">Date: <?php echo htmlspecialchars($dateInline !== '' ? $dateInline : $parcelDate); ?></div>
    </div>

    <div class="inv-body">
      <table class="inv-tbl">
        <colgroup>
          <col class="qty"><col class="desc"><col class="rate"><col class="amt">
        </colgroup>
        <thead>
          <tr>
            <th class="text-end">Qty</th>
            <th>Description</th>
            <th class="text-end">Rate</th>
            <th class="text-end">Amount</th>
          </tr>
        </thead>
        <tbody><?php echo $tableBodyHtml; ?></tbody>
      </table>
      <div class="inv-total-row">
        <span>Total (Rs)</span>
        <span><?php echo htmlspecialchars(number_format($grs)); ?></span>
      </div>
      <div class="inv-footer">
        <?php if ($footerNoteDisplay !== ''): ?>
        <p class="note"><?php echo htmlspecialchars($footerNoteDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="inv-sigs">
          <div class="sig">Handed Over</div>
          <div class="sig">Receiver</div>
        </div>
      </div>
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
      var first = document.querySelector('.js-addr-container');
      if (!first) return [];
      var nodes = first.querySelectorAll('.addr-line');
      var arr = [];
      for (var i = 0; i < nodes.length; i++) {
        var s = nodes[i].textContent.replace(/\s+/g, ' ').trim();
        if (s) arr.push(s);
      }
      return arr;
    }
    ta.value = getLines().join('\n');
    t.addEventListener('click', function () {
      ed.style.display = ed.style.display === 'none' || ed.style.display === '' ? 'block' : 'none';
    });
    function apply() {
      var parts = ta.value.replace(/\r/g, '').split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
      document.querySelectorAll('.js-addr-container').forEach(function (cont) {
        cont.innerHTML = '';
        parts.forEach(function (line) {
          var d = document.createElement('div');
          d.className = 'addr-line';
          d.textContent = line;
          cont.appendChild(d);
        });
      });
    }
    var a = document.getElementById('applyAddr');
    var ap = document.getElementById('applyAndPrint');
    if (a) a.addEventListener('click', apply);
    if (ap) ap.addEventListener('click', function () { apply(); window.print(); });
  }
})();
</script>
</body>
</html>
