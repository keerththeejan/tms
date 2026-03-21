#!/usr/bin/env php
<?php
/**
 * Repair phpMyAdmin / MySQL dumps that were pasted or exported as a single line.
 *
 * Usage:
 *   php scripts/normalize_sql_dump.php path/to/broken.sql > fixed.sql
 *   php scripts/normalize_sql_dump.php path/to/broken.sql --in-place
 *
 * Does not guarantee valid SQL for all edge cases; review output before import.
 */
declare(strict_types=1);

$argv = $_SERVER['argv'] ?? [];
$inPlace = in_array('--in-place', $argv, true);
$args = array_values(array_filter($argv, static function ($a) {
    return $a !== '--in-place';
}));
array_shift($args);
$paths = $args;

if (count($paths) !== 1) {
    fwrite(STDERR, "Usage: php scripts/normalize_sql_dump.php <input.sql> [--in-place]\n");
    exit(1);
}

$path = $paths[0];
if (!is_readable($path)) {
    fwrite(STDERR, "Cannot read: {$path}\n");
    exit(1);
}

$s = file_get_contents($path);
if ($s === false) {
    fwrite(STDERR, "Failed to read file.\n");
    exit(1);
}

$s = str_replace(["\r\n", "\r"], "\n", $s);

// Split glued section headers: ---- ... ----
$s = preg_replace('/-{4,}/', "\n-- --------------------------------------------------------\n", $s);

// Newlines before common statement starters when stuck to previous `;`
$patterns = [
    '/;(\s*)(\/\*!)/' => ';\n$1$2',
    '/;(\s*)(--\s)/' => ';\n$1$2',
    '/;(\s*)(SET\s+)/i' => ';\n$1$2',
    '/;(\s*)(START\s+TRANSACTION)/i' => ';\n$1$2',
    '/;(\s*)(COMMIT)/i' => ';\n$1$2',
    '/;(\s*)(DROP\s+TABLE)/i' => ';\n$1$2',
    '/;(\s*)(CREATE\s+TABLE)/i' => ';\n$1$2',
    '/;(\s*)(INSERT\s+INTO)/i' => ';\n$1$2',
    '/;(\s*)(ALTER\s+TABLE)/i' => ';\n$1$2',
    '/;(\s*)(LOCK\s+TABLES)/i' => ';\n$1$2',
    '/;(\s*)(UNLOCK\s+TABLES)/i' => ';\n$1$2',
];
foreach ($patterns as $re => $rep) {
    $s = preg_replace($re, $rep, $s);
}

// Space out glued comments: `--foo--bar` -> `-- foo\n-- bar` (conservative)
$s = preg_replace('/--\s*(--\s+)/', "--\n-- ", $s);

// Normalize multiple blank lines
$s = preg_replace("/\n{4,}/", "\n\n\n", $s);

$s = trim($s) . "\n";

if ($inPlace) {
    if (file_put_contents($path, $s) === false) {
        fwrite(STDERR, "Failed to write: {$path}\n");
        exit(1);
    }
    fwrite(STDERR, "Wrote: {$path}\n");
} else {
    echo $s;
}

exit(0);
