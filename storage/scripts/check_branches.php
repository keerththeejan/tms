<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
BranchRepository::ensureSchema($pdo);
$rows = $pdo->query('SELECT id, name, code, is_main FROM branches ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo "branches:\n";
foreach ($rows as $r) {
    echo "  #{$r['id']} {$r['name']} ({$r['code']}) main={$r['is_main']}\n";
}
