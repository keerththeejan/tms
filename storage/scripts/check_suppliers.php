<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
try {
    $n = (int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn();
    echo "suppliers count: $n\n";
    $cols = $pdo->query("SHOW COLUMNS FROM suppliers")->fetchAll(PDO::FETCH_COLUMN);
    echo "columns: " . implode(', ', $cols) . "\n";
    $pdo->query('SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id ORDER BY s.created_at DESC LIMIT 100');
    echo "list query OK\n";
    $br = BranchRepository::forFilters($pdo);
    echo "forFilters branches: " . count($br) . "\n";
    foreach ($br as $b) echo "  - {$b['id']}: {$b['name']}\n";
} catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
