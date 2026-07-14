<?php
/**
 * Reusable executive KPI card.
 *
 * Expected $kpi keys:
 * - id, theme, icon, title, value (numeric), format (count|money)
 * - href, link_label, description, badge (optional), updated (optional)
 */
$kpiId = preg_replace('/[^a-z0-9_-]/i', '', (string) ($kpi['id'] ?? 'kpi'));
$theme = preg_replace('/[^a-z]/i', '', (string) ($kpi['theme'] ?? 'blue'));
$icon = (string) ($kpi['icon'] ?? 'bi-speedometer2');
$title = (string) ($kpi['title'] ?? 'Metric');
$value = (float) ($kpi['value'] ?? 0);
$format = ((string) ($kpi['format'] ?? 'count')) === 'money' ? 'money' : 'count';
$href = (string) ($kpi['href'] ?? '#');
$linkLabel = (string) ($kpi['link_label'] ?? 'View Details');
$description = (string) ($kpi['description'] ?? '');
$badge = trim((string) ($kpi['badge'] ?? ''));
$updated = (string) ($kpi['updated'] ?? date('d M Y'));
$ariaLabel = $title . ': ' . ($format === 'money' ? Helpers::formatMoney($value) : number_format($value));
?>
<article
  class="erp-kpi-card erp-kpi-<?php echo htmlspecialchars($theme); ?> h-100"
  data-kpi-id="<?php echo htmlspecialchars($kpiId); ?>"
  data-format="<?php echo htmlspecialchars($format); ?>"
  data-value="<?php echo htmlspecialchars((string) $value); ?>"
  aria-label="<?php echo htmlspecialchars($ariaLabel); ?>"
>
  <div class="erp-kpi-accent" aria-hidden="true"></div>
  <div class="erp-kpi-top">
    <div class="erp-kpi-icon" aria-hidden="true">
      <i class="bi <?php echo htmlspecialchars($icon); ?>"></i>
    </div>
    <?php if ($badge !== ''): ?>
      <span class="erp-kpi-badge"><?php echo htmlspecialchars($badge); ?></span>
    <?php endif; ?>
  </div>
  <div class="erp-kpi-body">
    <p class="erp-kpi-title"><?php echo htmlspecialchars($title); ?></p>
    <p class="erp-kpi-value" data-kpi-value>
      <span class="erp-kpi-skeleton" aria-hidden="true"></span>
    </p>
    <?php if ($description !== ''): ?>
      <p class="erp-kpi-desc"><?php echo htmlspecialchars($description); ?></p>
    <?php endif; ?>
  </div>
  <div class="erp-kpi-footer">
    <div class="erp-kpi-meta">
      <span class="erp-kpi-updated"><i class="bi bi-clock me-1" aria-hidden="true"></i>Updated <?php echo htmlspecialchars($updated); ?></span>
    </div>
    <a
      class="btn btn-sm erp-kpi-btn"
      href="<?php echo htmlspecialchars($href); ?>"
      aria-label="<?php echo htmlspecialchars($linkLabel . ' — ' . $title); ?>"
    >
      <?php echo htmlspecialchars($linkLabel); ?>
      <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
    </a>
  </div>
</article>
