<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
BranchRepository::ensureSchema($pdo);

$seed = [
    ['user', '+94778870135', 2, ''],
    ['test', '+94778870135', 2, ''],
];

$ins = $pdo->prepare('INSERT INTO suppliers (name, phone, branch_id, supplier_code) VALUES (?,?,?,?)');
$upd = $pdo->prepare('UPDATE suppliers SET phone=?, branch_id=?, supplier_code=? WHERE name=?');
$count = 0;
foreach ($seed as [$name, $phone, $branch, $code]) {
    $st = $pdo->prepare('SELECT id FROM suppliers WHERE name = ? LIMIT 1');
    $st->execute([$name]);
    $ex = $st->fetch(PDO::FETCH_ASSOC);
    if ($ex) {
        $upd->execute([$phone, $branch, $code, $name]);
    } else {
        $ins->execute([$name, $phone, $branch, $code]);
    }
    $count++;
}
echo "restored/updated $count suppliers\n";
echo "total: " . (int)$pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn() . "\n";
