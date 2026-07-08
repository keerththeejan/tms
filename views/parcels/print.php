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
$rowNo = 0;
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
    $rowNo++;
    $tableBodyHtml .= '<tr>'
      . '<td class="text-center">' . (int)$rowNo . '</td>'
      . '<td>' . htmlspecialchars((string)$i['description']) . '</td>'
      . '<td class="text-end">' . ($qty > 0 ? htmlspecialchars(number_format($qty, 2)) : '') . '</td>'
      . '<td class="text-end">' . ($rate > 0 ? htmlspecialchars(number_format($rate, 2)) : '') . '</td>'
      . '<td class="text-end">0.00</td>'
      . '<td class="text-end">0.00</td>'
      . '<td class="text-end">' . ($amt > 0 ? htmlspecialchars(number_format($rs)) : '') . '</td>'
      . '</tr>';
  }
} else {
  $amt = (float)($parcel['price'] ?? 0);
  $rs = floor($amt);
  $tableBodyHtml = '<tr>'
    . '<td class="text-center">1</td>'
    . '<td>' . htmlspecialchars((string)($parcel['tracking_number'] ?? '')) . '</td>'
    . '<td class="text-end">' . htmlspecialchars(number_format((float)($parcel['weight'] ?? 0), 2)) . '</td>'
    . '<td class="text-end">' . ($amt > 0 ? htmlspecialchars(number_format($amt, 2)) : '') . '</td>'
    . '<td class="text-end">0.00</td>'
    . '<td class="text-end">0.00</td>'
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&family=Noto+Sans+Tamil:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/report-master.css?v=1'); ?>">
  <style>
    .text-end { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted { color: #6b7280 !important; }
    .fw-bold { font-weight: 700 !important; }
    .small { font-size: 0.875em !important; }

    :root {
      --brand: <?php echo $logoTitleColor; ?>;
      --primary-red: #C62828;
      --corp-blue: #1E4FA8;
      --cn-bg: #F8F9FB;
      --cn-border: #E3E6EB;
      --cn-ink: #333333;
      --cn-muted: #5c6370;
      --cn-surface: #F8F9FB;
      --cn-radius: 8px;
      --cn-gap: 6px;
      --cn-pad: 8px;
      --logo-arch: <?php echo $logoArch; ?>;
      --logo-bar-bg: <?php echo $logoBarBg; ?>;
      --logo-bar-color: <?php echo $logoBarColor; ?>;
    }

    * { box-sizing: border-box; }
    html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    body {
      margin: 0;
      padding: 8px;
      font-family: Poppins, Inter, "Segoe UI", "Noto Sans Tamil", system-ui, sans-serif;
      font-size: 12px;
      line-height: 1.25;
      color: var(--cn-ink);
      background: #e8ecf2;
    }
    body.parcel-print-embed {
      padding: 4px;
      background: #fff;
    }

    .a4-root,
    .invoice-container {
      width: 100%;
      max-width: 176mm;
      margin: 0 auto;
    }

    .invoice-sheet {
      background: #fff;
      border: 1px solid var(--cn-border);
      border-radius: var(--cn-radius);
      padding: var(--cn-pad);
      margin-bottom: 0;
      box-shadow: 0 1px 6px rgba(15, 23, 42, 0.04);
      overflow: hidden;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .inv-print-header-block {
      page-break-inside: avoid;
      break-inside: avoid;
    }

    /* 1. Company Header */
    .inv-header {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 6px 10px;
      height: 60px;
      min-height: 60px;
      max-height: 60px;
      padding: 0 2px;
      margin: 0 0 var(--cn-gap);
      box-sizing: border-box;
      overflow: hidden;
    }
    .inv-logo-cell {
      position: relative;
      z-index: 1;
      flex: 0 0 60px;
      width: 60px;
      display: flex;
      align-items: center;
      justify-content: flex-start;
    }
    .inv-logo-img {
      max-height: 48px;
      max-width: 60px;
      width: auto;
      height: auto;
      object-fit: contain;
      display: block;
    }
    .logo-unit { display: flex; flex-direction: column; align-items: flex-start; }
    .logo-unit .logo-wrap {
      width: 38px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      background: var(--logo-arch);
      border-radius: 50% 50% 0 0;
      font-weight: 800; font-size: 11px;
      color: var(--logo-bar-color);
      border: 1px solid var(--cn-border);
    }
    .logo-unit .bar-small {
      background: var(--logo-bar-bg);
      color: var(--logo-bar-color);
      padding: 1px 4px;
      font-size: 6.5px;
      font-weight: 700;
      letter-spacing: 0.3px;
      margin-top: 1px;
      max-width: 44px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      border-radius: 0 0 3px 3px;
    }

    .inv-company-block {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      z-index: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      height: auto;
      width: max-content;
      max-width: calc(100% - 240px);
      min-width: 0;
      margin: 0;
      padding: 0;
      gap: 2px;
      pointer-events: none;
    }
    .inv-company {
      margin: 0;
      padding: 0;
      font-family: Poppins, Inter, "Segoe UI", sans-serif;
      font-weight: 700;
      font-size: 26px;
      line-height: 1.05;
      color: #C62828;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      text-align: center;
      white-space: nowrap;
    }
    .inv-tagline {
      margin: 0;
      padding: 0;
      font-size: 12px;
      font-weight: 500;
      color: #666;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      text-align: center;
      white-space: nowrap;
      line-height: 1.15;
    }

    .inv-regdate {
      position: relative;
      z-index: 1;
      flex: 0 0 auto;
      align-self: center;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      justify-content: center;
      margin: 0 0 0 32px;
      padding: 0;
      width: max-content;
      min-width: 0;
      max-width: none;
      box-sizing: border-box;
    }
    .inv-regdate .cn-meta-item {
      display: grid;
      grid-template-columns: 95px 8px auto;
      column-gap: 4px;
      align-items: baseline;
      justify-content: start;
      margin: 0;
      padding: 0;
      width: 100%;
      min-width: 100%;
      box-sizing: border-box;
    }
    .inv-regdate .cn-meta-item + .cn-meta-item {
      margin-top: 2px;
    }
    .inv-regdate .cn-meta-label {
      grid-column: 1;
      grid-row: 1;
      display: block;
      box-sizing: border-box;
      width: 95px;
      max-width: 95px;
      margin: 0;
      padding: 0;
      font-size: 11px;
      font-weight: 600;
      text-transform: none;
      letter-spacing: 0;
      color: #666;
      line-height: 1.15;
      white-space: nowrap;
      overflow: hidden;
      text-align: left;
      justify-self: start;
    }
    .inv-regdate .cn-meta-label::after {
      content: none !important;
    }
    .inv-regdate .cn-meta-item::after {
      content: ":";
      grid-column: 2;
      grid-row: 1;
      box-sizing: border-box;
      width: 8px;
      margin: 0;
      padding: 0;
      font-size: 11px;
      font-weight: 600;
      color: #666;
      line-height: 1.15;
      text-align: center;
      justify-self: center;
      align-self: baseline;
    }
    .inv-regdate .cn-meta-value {
      grid-column: 3;
      grid-row: 1;
      display: block;
      margin: 0;
      padding: 0;
      font-size: 13px;
      font-weight: 700;
      color: #222;
      line-height: 1.15;
      white-space: nowrap;
      text-align: left;
      justify-self: start;
    }

    .cn-separator {
      height: 2px;
      background: linear-gradient(90deg, var(--primary-red) 0%, var(--corp-blue) 50%, var(--primary-red) 100%);
      border-radius: 1px;
      margin: 0 0 var(--cn-gap);
    }

    /* 2. Route */
    .inv-route {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      min-height: 42px;
      max-height: 42px;
      padding: 6px;
      margin: 0 0 var(--cn-gap);
      background: var(--cn-surface);
      border: 1px solid var(--cn-border);
      border-radius: var(--cn-radius);
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
      page-break-inside: avoid;
      break-inside: avoid;
      overflow: hidden;
    }
    .inv-route-ta {
      font-family: "Noto Sans Tamil", Poppins, sans-serif;
      font-size: 11px;
      font-weight: 600;
      color: var(--cn-ink);
      line-height: 1.15;
      margin: 0;
    }
    .inv-route-en {
      margin: 1px 0 0;
      font-size: 8.5px;
      font-weight: 500;
      color: var(--cn-muted);
      line-height: 1.15;
    }

    /* 3. Branch cards — equal height */
    .cn-branches {
      display: flex;
      flex-wrap: wrap;
      align-items: stretch;
      gap: 6px;
      margin: 0 0 var(--cn-gap);
    }
    .cn-branch-card {
      flex: 1 1 calc(33.333% - 4px);
      display: flex;
      flex-direction: column;
      min-width: 0;
      background: var(--cn-surface);
      border: 1px solid var(--cn-border);
      border-radius: var(--cn-radius);
      padding: 8px;
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
      page-break-inside: avoid;
      break-inside: avoid;
      font-size: 11px;
      line-height: 1.2;
    }
    .cn-branch-name {
      font-size: 11px;
      font-weight: 700;
      color: var(--primary-red);
      margin: 0 0 2px;
      padding: 0 0 2px;
      border-bottom: 1px solid var(--cn-border);
    }
    .cn-branch-ta {
      font-family: "Noto Sans Tamil", sans-serif;
      font-size: 9px;
      line-height: 1.2;
      color: var(--cn-ink);
    }
    .cn-branch-en {
      font-size: 8.5px;
      line-height: 1.2;
      color: var(--cn-muted);
      margin-top: 1px;
    }
    .cn-branch-phone {
      margin-top: auto;
      padding-top: 2px;
      font-size: 12px;
      font-weight: 700;
      color: var(--cn-ink);
    }

    /* 4. Consignment Details — 3×2 Bootstrap grid (B5 card width) */
    .cn-consignment-title {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.04em;
      color: var(--corp-blue);
      margin: 0 0 4px;
      text-transform: uppercase;
      line-height: 1.2;
    }
    .inv-customer-row.customer-row {
      width: 100%;
      max-width: 100%;
      margin: 0;
      padding: 12px;
      background: #FFFFFF;
      border: 1px solid #E5E7EB;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
      page-break-inside: avoid;
      break-inside: avoid;
      box-sizing: border-box;
    }
    .inv-customer-row .cn-consignment-row {
      --bs-gutter-x: 16px;
      --bs-gutter-y: 0;
      display: flex;
      flex-wrap: wrap;
      width: 100%;
      max-width: 100%;
      margin: 0;
    }
    .inv-customer-row .cn-consignment-row + .cn-consignment-row {
      margin-top: 10px;
    }
    .inv-customer-row .cn-consignment-row > [class*="col-"] {
      flex: 0 0 33.33333333%;
      max-width: 33.33333333%;
      width: 33.33333333%;
      padding-left: calc(var(--bs-gutter-x) * 0.5);
      padding-right: calc(var(--bs-gutter-x) * 0.5);
    }
    .inv-customer-row .cn-consignment-row > .cn-field,
    .customer-row .cn-consignment-row > .cn-field {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: stretch;
      min-width: 0;
      margin: 0;
      box-sizing: border-box;
    }
    .inv-customer-row .cn-field-label,
    .customer-row .cn-field-label {
      display: block;
      flex: 0 0 auto;
      margin: 0 0 2px;
      padding: 0;
      font-size: 10px;
      font-weight: 600;
      color: #6B7280;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .inv-customer-row .cn-field-value,
    .customer-row .cn-field-value {
      display: block;
      flex: 0 0 auto;
      margin: 0;
      padding: 0;
      font-size: 15px;
      font-weight: 700;
      color: #222222;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .inv-invoice-row { display: none; }
    .inv-invoice-title,
    .inv-invoice-date { display: none; }

    /* 5. Items table — keep column order; ~28px rows; blue header */
    .inv-body { display: block; }
    .inv-table-wrap {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      margin-bottom: var(--cn-gap);
      border-radius: var(--cn-radius);
    }
    table.inv-tbl {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 11px;
      line-height: 1.2;
      table-layout: fixed;
      border: 1px solid var(--cn-border);
      border-radius: var(--cn-radius);
      overflow: hidden;
      margin-bottom: 0;
    }
    table.inv-tbl th,
    table.inv-tbl td {
      border-bottom: 1px solid var(--cn-border);
      border-right: 1px solid var(--cn-border);
      padding: 5px;
      vertical-align: middle;
      overflow: hidden;
      text-overflow: ellipsis;
      font-size: 11px;
      line-height: 1.15;
    }
    table.inv-tbl th:last-child,
    table.inv-tbl td:last-child { border-right: none; }
    table.inv-tbl tbody tr:last-child td { border-bottom: none; }
    table.inv-tbl thead th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: var(--corp-blue);
      color: #fff;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 9px;
      letter-spacing: 0.04em;
      padding: 5px;
      height: 30px;
    }
    table.inv-tbl tbody td {
      height: 28px;
    }
    table.inv-tbl tbody tr:nth-child(even) td { background: #f4f6f9; }
    table.inv-tbl tbody tr:nth-child(odd) td { background: #fff; }
    table.inv-tbl .text-end,
    table.inv-tbl td.text-end,
    table.inv-tbl th.text-end { text-align: right !important; }
    table.inv-tbl .text-center,
    table.inv-tbl td.text-center,
    table.inv-tbl th.text-center { text-align: center !important; }
    table.inv-tbl col.col-no { width: 5%; }
    table.inv-tbl col.desc { width: auto; }
    table.inv-tbl col.qty { width: 10%; }
    table.inv-tbl col.rate { width: 12%; }
    table.inv-tbl col.disc { width: 11%; }
    table.inv-tbl col.tax { width: 10%; }
    table.inv-tbl col.amt { width: 12%; }
    table.inv-tbl tfoot { display: none; }

    /* 6. Total summary — right */
    .inv-total-row {
      display: flex;
      justify-content: flex-end;
      margin-bottom: var(--cn-gap);
    }
    .cn-totals-box {
      width: 260px;
      max-width: 260px;
      min-width: 200px;
      border: 1px solid var(--cn-border);
      border-radius: var(--cn-radius);
      overflow: hidden;
      background: #fff;
      box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .cn-totals-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
      padding: 6px;
      font-size: 11px;
      min-height: 0;
      border-bottom: 1px solid var(--cn-border);
    }
    .cn-totals-row:last-child { border-bottom: none; }
    .cn-totals-row.cn-grand {
      background: #fff5f5;
      padding: 6px;
      font-size: 12px;
      font-weight: 700;
      color: var(--primary-red);
    }
    .cn-totals-row.cn-grand .cn-totals-label,
    .cn-totals-row.cn-grand .cn-totals-value {
      color: var(--primary-red);
      font-weight: 700;
      font-size: 12px;
    }
    .cn-totals-label { color: var(--cn-muted); font-weight: 500; font-size: 10px; }
    .cn-totals-value { font-weight: 700; text-align: right; font-size: 11px; color: var(--cn-ink); }

    /* 7–8. Notes & signatures */
    .inv-footer {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin: 0;
      padding: 0;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .inv-footer .note {
      margin: 0;
      padding: 6px;
      font-family: "Noto Sans Tamil", Inter, "Segoe UI", sans-serif;
      font-size: 10px;
      line-height: 1.2;
      color: var(--cn-muted);
      background: #f0f2f5;
      border: 1px solid var(--cn-border);
      border-radius: var(--cn-radius);
      word-break: break-word;
      overflow-wrap: anywhere;
    }
    .inv-sigs {
      --bs-gutter-x: 12px;
      display: flex;
      flex-wrap: nowrap;
      align-items: flex-end;
      width: 100%;
      max-width: 100%;
      margin: 10px 0 8px;
      padding: 0;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .inv-sigs > [class*="col-"] {
      flex: 0 0 33.33333333%;
      max-width: 33.33333333%;
      width: 33.33333333%;
      padding-left: calc(var(--bs-gutter-x) * 0.5);
      padding-right: calc(var(--bs-gutter-x) * 0.5);
    }
    .inv-footer .sig {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
      width: 100%;
      margin: 0;
      padding: 8px 0 0;
      border: none;
      min-height: 0;
      font-size: 11px;
      font-weight: 600;
      color: #555;
      text-align: center;
      line-height: 1.2;
    }
    .inv-footer .sig::before {
      content: '';
      display: block;
      width: 90%;
      border-top: 1px solid #333;
      margin-bottom: 8px;
      flex-shrink: 0;
    }

    /* 9. Footer */
    .cn-page-footer {
      margin: var(--cn-gap) 0 0;
      padding: 4px 0 0;
      border-top: 1px solid var(--cn-border);
      text-align: center;
      font-size: 10px;
      line-height: 1.2;
      color: #8b919a;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .cn-page-footer .cn-thanks {
      font-size: 10px;
      font-weight: 600;
      color: var(--cn-muted);
      margin: 0 0 1px;
    }
    .cn-page-footer .cn-contact {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2px 8px;
      margin-bottom: 1px;
    }

    /* Tablet — consignment 2 columns */
    @media screen and (max-width: 991.98px) and (min-width: 576px) {
      .inv-customer-row .cn-consignment-row > [class*="col-"] {
        flex: 0 0 50%;
        max-width: 50%;
        width: 50%;
      }
      .cn-branch-card { flex: 1 1 calc(50% - 6px); }
    }

    /* Mobile — same section order; keep header right-panel alignment */
    @media screen and (max-width: 576px) {
      body { padding: 4px; }
      .invoice-sheet { padding: 8px; }
      .inv-header {
        position: relative;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: space-between;
        min-height: 70px;
        height: auto;
        max-height: none;
        gap: 6px 8px;
      }
      .inv-company-block {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        width: max-content;
        max-width: calc(100% - 220px);
        margin: 0;
        padding: 0;
        gap: 4px;
      }
      .inv-company {
        font-size: 16px;
        letter-spacing: 1px;
        color: #C62828;
        text-align: center;
      }
      .inv-tagline {
        font-size: 10px;
        letter-spacing: 1.5px;
        color: #666;
        text-align: center;
      }
      .inv-regdate {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: center;
        align-self: center;
        flex: 0 0 auto;
        width: max-content;
        min-width: 0;
        max-width: none;
        margin: 0 0 0 40px;
        padding: 0;
        gap: 0;
      }
      .inv-regdate .cn-meta-item {
        display: grid;
        grid-template-columns: 95px 8px auto;
        column-gap: 4px;
        width: 100%;
        margin: 0;
        padding: 0;
      }
      .inv-regdate .cn-meta-item + .cn-meta-item {
        margin-top: 2px !important;
      }
      .inv-regdate .cn-meta-label {
        width: 95px;
        max-width: 95px;
        font-size: 11px;
        white-space: nowrap;
        text-align: left;
        line-height: 1.15;
        margin: 0;
        padding: 0;
      }
      .inv-regdate .cn-meta-label::after {
        content: none !important;
      }
      .inv-regdate .cn-meta-item::after {
        content: ":";
        grid-column: 2;
        grid-row: 1;
        width: 8px;
        text-align: center;
        font-size: 11px;
        line-height: 1.15;
        margin: 0;
        padding: 0;
      }
      .inv-regdate .cn-meta-value {
        font-size: 13px;
        white-space: nowrap;
        text-align: left;
        line-height: 1.15;
        margin: 0;
        padding: 0;
      }
      .cn-branch-card { flex: 1 1 100%; }
      .inv-customer-row.customer-row {
        padding: 12px;
      }
      .inv-customer-row .cn-consignment-row > [class*="col-"] {
        flex: 0 0 100%;
        max-width: 100%;
        width: 100%;
      }
      .inv-customer-row .cn-field-value,
      .customer-row .cn-field-value {
        font-size: 14px;
      }
      .inv-sigs {
        --bs-gutter-x: 8px;
        margin: 10px 0 8px;
      }
      .cn-totals-box { max-width: 100%; }
    }

    /* Print — B5 portrait single-page */
    @media print {
      .no-print { display: none !important; }
      @page {
        size: B5 portrait;
        margin: 6mm;
      }
      html, body {
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
        font-size: 10px;
        width: 100%;
        height: auto;
        overflow: hidden;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .a4-root,
      .invoice-container {
        width: 100%;
        max-width: 176mm;
        margin: 0 auto;
        padding: 4mm;
      }
      .invoice-sheet {
        border: none;
        border-radius: 0;
        box-shadow: none;
        padding: 0;
        margin: 0;
        width: 100%;
        max-height: 238mm;
        min-height: 0;
        overflow: hidden;
        page-break-inside: avoid;
        break-inside: avoid;
        page-break-after: avoid;
        page-break-before: avoid;
      }
      .inv-print-header-block,
      .inv-route,
      .cn-branches,
      .cn-branch-card,
      .inv-customer-row,
      .customer-row,
      .inv-table-wrap,
      .inv-total-row,
      .inv-footer,
      .inv-sigs,
      .cn-totals-box,
      .cn-page-footer {
        page-break-inside: avoid;
        break-inside: avoid;
      }
      .cn-separator { margin-bottom: 4px; }
      .inv-header {
        height: 60px;
        min-height: 60px;
        max-height: 60px;
        margin-bottom: 4px;
        padding: 0;
      }
      .inv-logo-cell { flex: 0 0 60px; width: 60px; }
      .inv-logo-img { max-height: 44px; max-width: 60px; }
      .inv-company {
        font-size: 26px !important;
        letter-spacing: 0.8px;
        color: #C62828 !important;
        line-height: 1.05;
      }
      .inv-tagline {
        font-size: 12px !important;
        letter-spacing: 1.2px;
        color: #666 !important;
        line-height: 1.15;
      }
      .inv-regdate { margin-left: 28px; }
      .inv-regdate .cn-meta-label { font-size: 9px; }
      .inv-regdate .cn-meta-value { font-size: 11px; }
      .inv-route {
        min-height: 42px;
        max-height: 42px;
        padding: 6px;
        margin-bottom: 4px;
      }
      .inv-route-ta { font-size: 10px; line-height: 1.15; }
      .inv-route-en { font-size: 8px; margin-top: 0; }
      .cn-branches { gap: 4px; margin-bottom: 4px; }
      .cn-branch-card {
        padding: 8px;
        font-size: 10px;
        margin: 0;
        box-shadow: none;
      }
      .cn-branch-name { font-size: 10px; margin-bottom: 1px; padding-bottom: 1px; }
      .cn-branch-ta { font-size: 8px; line-height: 1.15; }
      .cn-branch-en { font-size: 7.5px; line-height: 1.15; }
      .cn-branch-phone { font-size: 12px; font-weight: 700; padding-top: 1px; }
      .cn-consignment-title { font-size: 9px; margin: 0 0 2px; }
      .inv-customer-row.customer-row {
        width: 100%;
        max-width: 100%;
        padding: 6px 8px;
        margin: 0 0 4px;
        box-shadow: none;
        page-break-inside: avoid;
        break-inside: avoid;
      }
      .inv-customer-row .cn-consignment-row {
        --bs-gutter-x: 12px;
      }
      .inv-customer-row .cn-consignment-row + .cn-consignment-row {
        margin-top: 6px;
      }
      .inv-customer-row .cn-consignment-row > [class*="col-"] {
        flex: 0 0 33.33333333% !important;
        max-width: 33.33333333% !important;
        width: 33.33333333% !important;
      }
      .inv-customer-row .cn-field-label,
      .customer-row .cn-field-label {
        font-size: 8px;
        margin: 0 0 1px;
        letter-spacing: 0.3px;
        line-height: 1.2;
        color: #6B7280 !important;
      }
      .inv-customer-row .cn-field-value,
      .customer-row .cn-field-value {
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
        color: #222222 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .inv-table-wrap { margin-bottom: 4px; }
      table.inv-tbl {
        font-size: 11px;
        page-break-inside: avoid;
        break-inside: avoid;
      }
      table.inv-tbl tr {
        page-break-inside: avoid;
        break-inside: avoid;
      }
      table.inv-tbl thead th {
        background: var(--corp-blue) !important;
        color: #fff !important;
        height: 30px;
        padding: 5px;
        font-size: 8px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      table.inv-tbl tbody td {
        height: 28px;
        padding: 5px;
        font-size: 11px;
      }
      .inv-total-row { margin-bottom: 4px; }
      .cn-totals-box {
        width: 260px;
        max-width: 260px;
        box-shadow: none;
      }
      .cn-totals-row { padding: 6px; min-height: 0; }
      .cn-totals-row.cn-grand,
      .cn-totals-row.cn-grand .cn-totals-label,
      .cn-totals-row.cn-grand .cn-totals-value {
        color: var(--primary-red) !important;
        font-size: 11px;
        padding: 6px;
      }
      .cn-branch-name { color: var(--primary-red) !important; }
      .inv-footer { gap: 4px; }
      .inv-footer .note {
        padding: 6px;
        font-size: 10px;
        line-height: 1.2;
        margin: 0;
        box-shadow: none;
      }
      .inv-sigs {
        --bs-gutter-x: 10px;
        flex-wrap: nowrap !important;
        width: 100%;
        margin: 8px 0 6px;
        page-break-inside: avoid;
        break-inside: avoid;
      }
      .inv-sigs > [class*="col-"] {
        flex: 0 0 33.33333333% !important;
        max-width: 33.33333333% !important;
        width: 33.33333333% !important;
      }
      .inv-footer .sig {
        padding: 6px 0 0;
        font-size: 9px;
        line-height: 1.2;
        color: #555 !important;
      }
      .inv-footer .sig::before {
        width: 90%;
        border-top: 1px solid #333 !important;
        margin-bottom: 6px;
      }
      .cn-page-footer {
        margin: 4px 0 0;
        padding: 2px 0 0;
        font-size: 10px;
        line-height: 1.2;
      }
      .cn-page-footer .cn-thanks { font-size: 10px; margin: 0; }
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
  <span class="small text-muted">Print: B5 Portrait · Margins 6mm · Scale Default</span>
</div>
<?php if (!$hasInvoiceCols): ?>
<div id="addrEditor" class="no-print card card-body p-2 mb-2" style="display:none; max-width:176mm; margin:0 auto;">
  <div class="small text-muted mb-1">One address per line. Applies to this invoice only.</div>
  <textarea id="addrTextarea" class="form-control form-control-sm" rows="4"></textarea>
  <div class="mt-2 d-flex gap-2">
    <button type="button" id="applyAddr" class="btn btn-success btn-sm">Apply</button>
    <button type="button" id="applyAndPrint" class="btn btn-primary btn-sm">Apply &amp; Print</button>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php
  $routeTaDisplay = implode(' ⟷ ', array_filter(array_map('trim', $routeTamilParts), static function ($p) {
    return $p !== '';
  }));
  if ($routeTaDisplay === '') {
    $routeTaDisplay = 'கொழும்பு ⟷ கிளிநொச்சி ⟷ முல்லைத்தீவு';
  }
  $routeEnParts = [];
  foreach ($invoiceHeaderBranches as $brColRoute) {
    if (!is_array($brColRoute)) { continue; }
    $bn = trim((string)($brColRoute['name'] ?? ''));
    if ($bn !== '') { $routeEnParts[] = $bn; }
  }
  $routeEnDisplay = count($routeEnParts) >= 2
    ? implode(' ↔ ', $routeEnParts)
    : 'Colombo ↔ Kilinochchi ↔ Mullaitivu';
  $footerPhones = [];
  foreach ($invoiceHeaderBranches as $brColFoot) {
    if (!is_array($brColFoot)) { continue; }
    $ph = trim((string)($brColFoot['phones'] ?? ''));
    if ($ph !== '') { $footerPhones[] = str_replace('|', ' | ', $ph); }
  }
  $footerPhoneDisplay = $footerPhones !== [] ? implode(' · ', array_slice($footerPhones, 0, 2)) : '';
?>

<div class="a4-root invoice-container">
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
        <div class="inv-company-block">
          <h1 class="inv-company"><?php echo htmlspecialchars($brand['name'] ?? 'TS Transport'); ?></h1>
          <p class="inv-tagline">Courier &amp; Parcel Service</p>
        </div>
        <div class="inv-regdate">
          <?php if ($regNo !== ''): ?>
          <div class="cn-meta-item">
            <span class="cn-meta-label">Registration No</span>
            <span class="cn-meta-value"><?php echo htmlspecialchars($regNo); ?></span>
          </div>
          <?php endif; ?>
          <div class="cn-meta-item">
            <span class="cn-meta-label">Invoice Date</span>
            <span class="cn-meta-value"><?php echo htmlspecialchars($dateInline !== '' ? $dateInline : $parcelDate); ?></span>
          </div>
          <div class="cn-meta-item">
            <span class="cn-meta-label">Invoice No</span>
            <span class="cn-meta-value">#<?php echo (int)$invoiceNo; ?></span>
          </div>
        </div>
      </header>

      <div class="cn-separator" aria-hidden="true"></div>

      <div class="inv-route">
        <div class="inv-route-ta"><?php echo htmlspecialchars($routeTaDisplay); ?></div>
        <div class="inv-route-en"><?php echo htmlspecialchars($routeEnDisplay); ?></div>
      </div>

      <div class="cn-branches" aria-label="Branch addresses">
        <?php for ($col = 0; $col < 3; $col++):
            $brCol = $invoiceHeaderBranches[$col] ?? null;
            ?>
        <div class="cn-branch-card">
          <?php if (is_array($brCol)): ?>
            <div class="cn-branch-name"><?php echo htmlspecialchars((string)($brCol['name'] ?? '')); ?></div>
            <?php if (trim((string)($brCol['address_ta'] ?? '')) !== ''): ?>
              <div class="cn-branch-ta"><?php echo nl2br(htmlspecialchars((string)$brCol['address_ta'])); ?></div>
            <?php endif; ?>
            <?php if (trim((string)($brCol['address_en'] ?? '')) !== ''): ?>
              <div class="cn-branch-en"><?php echo nl2br(htmlspecialchars((string)$brCol['address_en'])); ?></div>
            <?php endif; ?>
            <?php if (trim((string)($brCol['phones'] ?? '')) !== ''): ?>
              <div class="cn-branch-phone"><?php echo htmlspecialchars(str_replace('|', ' | ', (string)$brCol['phones'])); ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <p class="cn-consignment-title">Consignment Details</p>
    <div class="inv-customer-row customer-row" aria-label="Customer details">
      <div class="row cn-consignment-row">
        <div class="col-lg-4 col-md-6 col-12 cn-field inv-customer-left left">
          <span class="cn-field-label">Customer</span>
          <span class="cn-field-value"><?php echo htmlspecialchars(trim((string)($parcel['customer_name'] ?? ''))); ?></span>
        </div>
        <div class="col-lg-4 col-md-6 col-12 cn-field inv-customer-center center">
          <span class="cn-field-label">Phone</span>
          <span class="cn-field-value"><?php echo htmlspecialchars(trim((string)($parcel['customer_phone'] ?? ''))); ?></span>
        </div>
        <div class="col-lg-4 col-md-6 col-12 cn-field inv-customer-right right">
          <span class="cn-field-label">Delivery Branch</span>
          <span class="cn-field-value"><?php echo htmlspecialchars(trim((string)($parcel['delivery_location'] ?? ''))); ?></span>
        </div>
      </div>
      <div class="row cn-consignment-row">
        <div class="col-lg-4 col-md-6 col-12 cn-field">
          <span class="cn-field-label">Reference</span>
          <span class="cn-field-value"><?php echo htmlspecialchars(trim((string)($parcel['tracking_number'] ?? ''))); ?></span>
        </div>
        <div class="col-lg-4 col-md-6 col-12 cn-field">
          <span class="cn-field-label">Booking Date</span>
          <span class="cn-field-value"><?php echo htmlspecialchars($dateInline !== '' ? $dateInline : $parcelDate); ?></span>
        </div>
        <div class="col-lg-4 col-md-6 col-12 cn-field">
          <span class="cn-field-label">Booked By</span>
          <span class="cn-field-value"><?php echo htmlspecialchars(trim((string)($parcel['supplier_name'] ?? ''))); ?></span>
        </div>
      </div>
    </div>

    <div class="inv-invoice-row" aria-hidden="true">
      <div class="inv-invoice-title">Invoice No. #<?php echo (int)$invoiceNo; ?></div>
      <div class="inv-invoice-date">Date: <?php echo htmlspecialchars($dateInline !== '' ? $dateInline : $parcelDate); ?></div>
    </div>

    <div class="inv-body">
      <div class="inv-table-wrap">
        <table class="inv-tbl table mb-0">
          <colgroup>
            <col class="col-no"><col class="desc"><col class="qty"><col class="rate"><col class="disc"><col class="tax"><col class="amt">
          </colgroup>
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Description</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Discount</th>
              <th class="text-end">Tax</th>
              <th class="text-end">Amount</th>
            </tr>
          </thead>
          <tbody><?php echo $tableBodyHtml; ?></tbody>
          <tfoot>
            <tr>
              <td colspan="6"></td>
              <td class="text-end fw-bold">Total (Rs): <?php echo htmlspecialchars(number_format($grs)); ?></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="inv-total-row">
        <div class="cn-totals-box">
          <div class="cn-totals-row">
            <span class="cn-totals-label">Subtotal (Rs)</span>
            <span class="cn-totals-value"><?php echo htmlspecialchars(number_format($grs)); ?></span>
          </div>
          <div class="cn-totals-row">
            <span class="cn-totals-label">Discount (Rs)</span>
            <span class="cn-totals-value"><?php echo htmlspecialchars(number_format((float)($parcel['discount'] ?? 0))); ?></span>
          </div>
          <div class="cn-totals-row">
            <span class="cn-totals-label">Tax (Rs)</span>
            <span class="cn-totals-value"><?php echo htmlspecialchars(number_format((float)($parcel['tax_amount'] ?? 0))); ?></span>
          </div>
          <div class="cn-totals-row cn-grand">
            <span class="cn-totals-label">Grand Total (Rs)</span>
            <span class="cn-totals-value"><?php echo htmlspecialchars(number_format($grs)); ?></span>
          </div>
        </div>
      </div>

      <div class="inv-footer">
        <?php if ($footerNoteDisplay !== ''): ?>
        <p class="note"><?php echo htmlspecialchars($footerNoteDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="inv-sigs row">
          <div class="col-4 sig">Prepared By</div>
          <div class="col-4 sig">Handed Over</div>
          <div class="col-4 sig">Receiver Signature</div>
        </div>
      </div>

      <footer class="cn-page-footer">
        <div class="cn-thanks">Thank you for choosing <?php echo htmlspecialchars($brand['name'] ?? 'TS Transport'); ?></div>
        <?php if ($footerPhoneDisplay !== ''): ?>
        <div class="cn-contact">
          <span>Tel: <?php echo htmlspecialchars($footerPhoneDisplay); ?></span>
        </div>
        <?php endif; ?>
        <div>Printed: <?php echo date('d/m/Y H:i'); ?> · Generated by System</div>
      </footer>
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
