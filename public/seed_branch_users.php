<?php
require_once __DIR__ . '/../app/bootstrap.php';

// This seeder (re)creates/upserts branch admin users for Colombo and Mullaitivu with known passwords.
// Usage: open in browser: /public/seed_branch_users.php
// SECURITY: Remove or protect this file after running in production.

if (!Auth::check()) {
  // allow running without login but restrict to localhost by default
  $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
  if (!$isLocal) {
    http_response_code(403);
    echo 'Forbidden (run from server or login first)';
    exit;
  }
}

$pdo = Database::pdo();
$pdo->beginTransaction();

function getBranchId(PDO $pdo, string $name, string $code, int $isMain = 0): int {
  $q = $pdo->prepare('SELECT id FROM branches WHERE name=? OR code=? LIMIT 1');
  $q->execute([$name, $code]);
  $row = $q->fetch();
  if ($row) return (int)$row['id'];
  $ins = $pdo->prepare('INSERT INTO branches (name, code, is_main) VALUES (?,?,?)');
  $ins->execute([$name, $code, $isMain]);
  return (int)$pdo->lastInsertId();
}

$colId = getBranchId($pdo, 'Colombo', 'COL', 0);
$mulId = getBranchId($pdo, 'Mullaitivu', 'MUL', 0);

$users = [
  [
    'username' => 'col_admin',
    'password' => 'col12345',
    'full_name' => 'Colombo Admin',
    'role' => 'admin',
    'branch_id' => $colId,
    'is_main_branch' => 0,
  ],
  [
    'username' => 'mul_admin',
    'password' => 'mul12345',
    'full_name' => 'Mullaitivu Admin',
    'role' => 'admin',
    'branch_id' => $mulId,
    'is_main_branch' => 0,
  ],
];

foreach ($users as $u) {
  $hash = password_hash($u['password'], PASSWORD_BCRYPT);
  $exists = $pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
  $exists->execute([$u['username']]);
  $row = $exists->fetch();
  if ($row) {
    $upd = $pdo->prepare('UPDATE users SET password_hash=?, full_name=?, role=?, branch_id=?, is_main_branch=?, active=1 WHERE id=?');
    $upd->execute([$hash, $u['full_name'], $u['role'], $u['branch_id'], $u['is_main_branch'], (int)$row['id']]);
  } else {
    $ins = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, branch_id, is_main_branch, active) VALUES (?,?,?,?,?,?,1)');
    $ins->execute([$u['username'], $hash, $u['full_name'], $u['role'], $u['branch_id'], $u['is_main_branch']]);
  }
}

$pdo->commit();

echo '<pre>Seeded users successfully.\n\nLogin credentials:\n- Colombo: username=col_admin, password=col12345\n- Mullaitivu: username=mul_admin, password=mul12345\n</pre>';
