<?php
require_once __DIR__ . '/../app/bootstrap.php';

$config = Helpers::config();
if (($config['env'] ?? 'local') !== 'local') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain');

try {
    $pdo = Database::pdo();
    echo "DB connection: OK\n\n";

    // Show users
    $stmt = $pdo->query('SELECT id, username, role, is_main_branch, active FROM users');
    $users = $stmt->fetchAll();
    if (!$users) {
        echo "users table is empty.\n";
    } else {
        echo "users:\n";
        foreach ($users as $u) {
            echo sprintf("- id=%d username=%s role=%s is_main=%d active=%d\n", $u['id'], $u['username'], $u['role'], $u['is_main_branch'], $u['active']);
        }
    }

    // Check admin user password match for 'admin123'
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "\nadmin user not found.\n";
    } else {
        $ok = password_verify('admin123', $admin['password_hash']);
        echo "\nadmin found: id={$admin['id']}\npassword_verify('admin123'): " . ($ok ? 'MATCH' : 'NO MATCH') . "\n";
    }

    echo "\nHint: Run /seed_admin.php to upsert the admin with a fresh hash.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
