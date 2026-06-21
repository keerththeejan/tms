<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
try {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    echo "customers count: $count\n";
    $rows = $pdo->query('SELECT id, name, phone FROM customers ORDER BY id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
    $pdo->query('SELECT * FROM customers ORDER BY created_at DESC LIMIT 1')->fetch();
    echo "index query OK\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
