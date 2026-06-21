<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
try {
    $n = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo "users table exists, count: $n\n";
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} {$c['Type']}\n";
    }
    $rows = $pdo->query('SELECT id, username, role, active, branch_id FROM users LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "  user #{$r['id']}: {$r['username']} ({$r['role']}) active={$r['active']} branch={$r['branch_id']}\n";
    }
} catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
