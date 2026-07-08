<?php
/** Master corporate letterhead — logo | company | meta panel */
require __DIR__ . '/setup.php';
?>
<div class="rpt-print-header-block inv-print-header-block">
  <header class="rpt-header inv-header">
    <div class="rpt-logo-cell inv-logo-cell">
      <?php if ($useLogoImage): ?>
        <?php
          $logoUrl = $brand['logo_url'];
          $logoUrl = (strpos($logoUrl, 'http') === 0 || strpos($logoUrl, '//') === 0) ? $logoUrl : Helpers::baseUrl($logoUrl);
        ?>
        <img class="rpt-logo-img inv-logo-img" src="<?php echo htmlspecialchars($logoUrl); ?>" alt="">
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
    <div class="rpt-company-block inv-company-block">
      <h1 class="rpt-company inv-company"><?php echo htmlspecialchars($brand['name'] ?? 'TS Transport'); ?></h1>
      <p class="rpt-tagline inv-tagline"><?php echo htmlspecialchars($reportSubtitle); ?></p>
    </div>
    <div class="rpt-meta-panel inv-regdate">
      <?php foreach ($reportMetaItems as $__meta):
        $__ml = trim((string)($__meta['label'] ?? ''));
        $__mv = trim((string)($__meta['value'] ?? ''));
        if ($__ml === '' && $__mv === '') { continue; }
      ?>
      <div class="cn-meta-item">
        <span class="cn-meta-label"><?php echo htmlspecialchars($__ml); ?></span>
        <span class="cn-meta-value"><?php echo htmlspecialchars($__mv); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </header>
  <div class="rpt-separator cn-separator" aria-hidden="true"></div>
  <?php
    $__addrLines = $reportAddressLines ?? ($addresses ?? null);
    if (!empty($reportShowAddresses) && is_array($__addrLines) && $__addrLines !== []):
  ?>
  <div class="rpt-addresses row row-cols-1 row-cols-sm-2 row-cols-md-3 g-1 small text-muted" id="addrContainer">
    <?php foreach ($__addrLines as $__addr): ?>
      <div class="addr-line col"><?php echo nl2br(htmlspecialchars((string)$__addr)); ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if (!empty($reportShowInfoPanel)): ?>
    <?php include __DIR__ . '/info_panel.php'; ?>
  <?php endif; ?>
</div>
