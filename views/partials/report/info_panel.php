<?php
/** Compact report information panel below letterhead */
if (!isset($reportInfoItems) || !is_array($reportInfoItems) || $reportInfoItems === []) {
    return;
}
?>
<div class="rpt-info-panel" aria-label="Report information">
  <div class="row rpt-info-row">
    <?php foreach ($reportInfoItems as $__info):
      $__il = trim((string)($__info['label'] ?? ''));
      $__iv = trim((string)($__info['value'] ?? ''));
      if ($__il === '' && $__iv === '') { continue; }
    ?>
    <div class="col-6 col-md-4 col-lg-3 rpt-info-item">
      <span class="rpt-info-label"><?php echo htmlspecialchars($__il); ?></span>
      <span class="rpt-info-value"><?php echo htmlspecialchars($__iv); ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
