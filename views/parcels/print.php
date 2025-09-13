<?php /** @var array $parcel */ /** @var array $items */ ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Parcel Receipt #<?php echo (int)$parcel['id']; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --brand:#0d6efd; }
    body { padding: 24px; font-size: 13px; }
    .sheet { border: 2px solid var(--brand); border-radius: 6px; }
    .sheet-header { background: #e7f1ff; border-bottom: 2px solid var(--brand); }
    .brand-title { font-weight: 900; font-size: 26px; letter-spacing: .5px; color: var(--brand); text-transform: uppercase; }
    .addr { font-size: 11px; color: #333; }
    .meta small { color: #333; }
    table.receipt { width: 100%; border-collapse: collapse; }
    table.receipt th, table.receipt td { border: 1px solid var(--brand); padding: 6px; vertical-align: middle; }
    table.receipt th { background: #f8fbff; text-transform: uppercase; font-size: 12px; }
    .totals { border-top: 2px solid var(--brand); background: #e7f1ff; font-weight: 700; }
    .serial-big { font-size: 20px; font-weight: 800; letter-spacing: 2px; }
    .sig-line { border-top: 1px dashed #888; padding-top: 4px; text-align: center; }
    .note { font-size: 11px; color: #555; }
    @media print { .no-print { display:none !important; } body{ padding:0; } }
  </style>
  </head>
<body>
<div class="no-print mb-3 d-flex gap-2">
  <a class="btn btn-secondary" href="javascript:history.back()">Back</a>
  <button class="btn btn-primary" onclick="window.print()">Print</button>
  </div>

<?php $cfg = (require __DIR__ . '/../../config/config.php'); $brand = $cfg['company'] ?? []; ?>
<div class="sheet">
  <div class="sheet-header p-3">
    <div class="row align-items-center">
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2">
          <?php if (!empty($brand['logo_url'])): ?>
            <img src="<?php echo htmlspecialchars($brand['logo_url']); ?>" alt="Logo" style="height:40px">
          <?php endif; ?>
          <div>
            <div class="brand-title"><?php echo htmlspecialchars($brand['name'] ?? ''); ?></div>
            <div class="addr">Transport and Parcel Services</div>
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="row g-1 addr">
          <?php foreach (($brand['addresses'] ?? []) as $addr): ?>
            <div class="col-md-4"><?php echo nl2br(htmlspecialchars($addr)); ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="row mt-2 meta g-2">
      <div class="col-md-3"><small><strong>Vehicle No:</strong> <?php echo htmlspecialchars($parcel['vehicle_no'] ?? ''); ?></small></div>
      <div class="col-md-3"><small><strong>Date:</strong> <?php echo htmlspecialchars(substr((string)($parcel['created_at'] ?? date('Y-m-d')),0,10)); ?></small></div>
      <div class="col-md-6"><small><strong>Customer:</strong> <?php echo htmlspecialchars($parcel['customer_name'] ?? ''); ?> (<?php echo htmlspecialchars($parcel['customer_phone'] ?? ''); ?>)</small></div>
    </div>
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
        <?php $total = 0.0; if ($items && count($items)>0) : foreach ($items as $i): $qty=(float)$i['qty']; $rate=(float)($i['rate'] ?? 0); $amt=$qty*$rate; $total += $amt; $rs=floor($amt); $cts=(int)round(($amt-$rs)*100); ?>
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
        <?php $grand = ($items && count($items)>0) ? $total : (float)($parcel['price'] ?? 0); $grs=floor($grand); $gcts=(int)round(($grand-$grs)*100); ?>
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

    <div class="d-flex justify-content-between mt-3">
      <div class="serial-big">#<?php echo (int)$parcel['id']; ?></div>
      <div class="fw-bold text-primary">TOTAL</div>
    </div>
  </div>
</div>

</body>
</html>
