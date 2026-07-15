<?php

declare(strict_types=1);

/**
 * CLI diagnostic for Google Drive backup integration.
 *
 *   php bin/verify-google-drive.php
 */

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';
require_once $root . '/app/bootstrap.php';

$drive = new GoogleDriveService();
$diag = $drive->diagnose();

echo "TMS Google Drive Diagnostics\n";
echo str_repeat('=', 40) . "\n";
foreach ($diag as $key => $value) {
    if ($key === 'errors' || $key === 'diagnostics') {
        continue;
    }
    if (is_bool($value)) {
        $value = $value ? 'YES' : 'NO';
    } elseif (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    echo str_pad((string) $key, 28) . ': ' . $value . "\n";
}

if (!empty($diag['errors'])) {
    echo "\nErrors:\n";
    foreach ($diag['errors'] as $err) {
        echo ' - ' . $err . "\n";
    }
}

echo "\nConnected: " . (!empty($diag['connected']) ? 'YES' : 'NO') . "\n";
exit(!empty($diag['connected']) ? 0 : 1);
