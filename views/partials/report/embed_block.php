<?php
/**
 * Print-only embed block for app-layout reports.
 * Visible in print; hidden on screen. Set $reportTitle before include when possible.
 */
if (!empty($reportSkipEmbed)) {
    return;
}
require __DIR__ . '/setup.php';
?>
<div class="rpt-embed-block no-screen" aria-hidden="true">
  <?php include __DIR__ . '/letterhead.php'; ?>
</div>
<div class="rpt-embed-footer no-screen" aria-hidden="true">
  <?php include __DIR__ . '/footer.php'; ?>
</div>
