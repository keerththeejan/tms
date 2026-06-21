<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
$rows = $pdo->query('SELECT id, name, is_active, is_main FROM branches WHERE id IN (1,2,3)')->fetchAll(PDO::FETCH_ASSOC);
echo "branches:\n";
foreach ($rows as $r) {
    echo "  id={$r['id']} active={$r['is_active']} name={$r['name']}\n";
}
$dd = BranchRepository::forDropdowns($pdo);
$ff = BranchRepository::forFilters($pdo);
echo "forDropdowns: " . count($dd) . "\n";
echo "forFilters: " . count($ff) . "\n";
