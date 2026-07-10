<?php
/** Day Book print-only header + info panel (presentation only) */
require __DIR__ . '/setup.php';
$reportTitle = 'Day Book';
$reportSubtitle = 'Courier & Parcel Service';
$reportShowAddresses = false;
$reportShowInfoPanel = false;
$reportMetaItems = [
    ['label' => 'Print Date', 'value' => date('d/m/Y H:i'), 'id' => 'dbPrintDate'],
    ['label' => 'Branch', 'value' => $reportBranch, 'id' => 'dbPrintBranch'],
];
$reportInfoItems = [
    ['label' => 'Report Name', 'value' => 'Day Book'],
    ['label' => 'Branch', 'value' => $reportBranch, 'id' => 'dbInfoBranch'],
    ['label' => 'Prepared By', 'value' => $reportPreparedBy, 'id' => 'dbInfoPreparedBy'],
    ['label' => 'Printed Date', 'value' => date('d/m/Y H:i'), 'id' => 'dbInfoPrintDate'],
];
?>
<div class="db-print-only db-print-header-wrap">
  <?php include __DIR__ . '/letterhead.php'; ?>
  <div class="rpt-info-panel" aria-label="Report information">
    <div class="row rpt-info-row">
      <?php foreach ($reportInfoItems as $__info):
        $__il = trim((string)($__info['label'] ?? ''));
        $__iv = trim((string)($__info['value'] ?? ''));
        if ($__il === '' && $__iv === '') { continue; }
      ?>
      <div class="col-6 col-md-3 rpt-info-item">
        <span class="rpt-info-label"><?php echo htmlspecialchars($__il); ?></span>
        <span class="rpt-info-value"<?php if (!empty($__info['id'])): ?> id="<?php echo htmlspecialchars((string)$__info['id']); ?>"<?php endif; ?>><?php echo htmlspecialchars($__iv); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
