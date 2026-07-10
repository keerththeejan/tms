<?php
require __DIR__ . '/../../app/bootstrap.php';
// Simulate suppliers index route (no auth)
$pdo = Database::pdo();
try {
    BranchRepository::forFilters($pdo);
    BranchRepository::forDropdowns($pdo);
    echo "branch load OK\n";
} catch (Throwable $e) {
    echo "branch ERR: " . $e->getMessage() . "\n";
    exit(1);
}
try {
    $suppliers = $pdo->query('SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id ORDER BY s.created_at DESC LIMIT 100')->fetchAll();
    echo "suppliers query OK, count=" . count($suppliers) . "\n";
} catch (Throwable $e) {
    echo "suppliers ERR: " . $e->getMessage() . "\n";
}
