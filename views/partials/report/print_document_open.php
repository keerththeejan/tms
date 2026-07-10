<?php
/** Open standalone print document shell */
/** @var string $reportDocTitle */
require __DIR__ . '/setup.php';
$reportDocTitle = trim((string)($reportDocTitle ?? ($reportTitle !== '' ? $reportTitle : ($brand['name'] ?? 'Report'))));
$rptCssPath = dirname(__DIR__, 3) . '/public/assets/css/report-master.css';
$rptCssVer = is_file($rptCssPath) ? (string)filemtime($rptCssPath) : '1';
$rptEmbedBodyClass = !empty($reportEmbed) ? ' rpt-embed-body' : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($reportDocTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&family=Noto+Sans+Tamil:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/report-master.css?v=' . rawurlencode($rptCssVer)); ?>">
  <style>
    :root {
      --brand: <?php echo htmlspecialchars($logoTitleColor); ?>;
      --logo-arch: <?php echo htmlspecialchars($logoArch); ?>;
      --logo-bar-bg: <?php echo htmlspecialchars($logoBarBg); ?>;
      --logo-bar-color: <?php echo htmlspecialchars($logoBarColor); ?>;
    }
  </style>
</head>
<body class="rpt-body<?php echo $rptEmbedBodyClass; ?>">
