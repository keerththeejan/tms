<?php
require_once __DIR__ . '/../app/bootstrap.php';

$config = Helpers::config();
if (($config['env'] ?? 'local') !== 'local') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$pdo = Database::pdo();

// Ensure branches exist (idempotent)
$branches = [
    ['Main Branch', $config['main_branch_code'] ?? 'MAIN', 1],
    ['Branch A', 'BR-A', 0],
    ['Branch B', 'BR-B', 0],
];

$pdo->beginTransaction();
try {
    foreach ($branches as $b) {
        [$name, $code, $is_main] = $b;
        $stmt = $pdo->prepare('INSERT INTO branches (name, code, is_main) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), is_main = VALUES(is_main)');
        $stmt->execute([$name, $code, $is_main]);
    }

    // Fetch main branch id
    $stmt = $pdo->prepare('SELECT id FROM branches WHERE code = ? LIMIT 1');
    $stmt->execute([$config['main_branch_code'] ?? 'MAIN']);
    $mainBranch = $stmt->fetch();
    if (!$mainBranch) {
        throw new RuntimeException('Main branch not found.');
    }

    $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);

    // Upsert admin user
    // Try update existing admin by username
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute(['admin']);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role = ?, branch_id = ?, is_main_branch = 1, active = 1 WHERE id = ?');
        $stmt->execute([$passwordHash, 'Administrator', 'admin', $mainBranch['id'], $existing['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, branch_id, is_main_branch, active) VALUES (?,?,?,?,?,1,1)');
        $stmt->execute(['admin', $passwordHash, 'Administrator', 'admin', $mainBranch['id']]);
    }

    $pdo->commit();

    echo '<pre>Seed complete.\n\nLogin with:\n  Username: admin\n  Password: admin123\n</pre>';
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}
