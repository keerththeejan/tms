<?php
require_once __DIR__ . '/../app/bootstrap.php';

$config = Helpers::config();
if (($config['env'] ?? 'local') !== 'local') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$pdo = Database::pdo();
BranchRepository::ensureSchema($pdo);

$mainBranchId = 2;
$st = $pdo->query('SELECT id FROM branches WHERE is_main = 1 ORDER BY id LIMIT 1');
$row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;
if ($row) {
    $mainBranchId = (int)$row['id'];
}

$passwordHash = password_hash('admin123', PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute(['admin']);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, full_name = ?, role = ?, branch_id = ?, is_main_branch = 1, active = 1 WHERE id = ?');
        $stmt->execute([$passwordHash, 'Administrator', 'admin', $mainBranchId, (int)$existing['id']]);
        $action = 'updated';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, branch_id, is_main_branch, active) VALUES (?,?,?,?,?,1,1)');
        $stmt->execute(['admin', $passwordHash, 'Administrator', 'admin', $mainBranchId]);
        $action = 'created';
    }

    $pdo->commit();

    $isCli = PHP_SAPI === 'cli';
    $msg = "Admin user {$action}.\n\nLogin with:\n  Username: admin\n  Password: admin123\n  Branch id: {$mainBranchId}\n";
    if ($isCli) {
        echo $msg;
    } else {
        echo '<pre>' . htmlspecialchars($msg) . '</pre>';
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}
