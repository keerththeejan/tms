<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
BranchRepository::ensureSchema($pdo);

$name = 'Test Supplier ' . date('His');
$phone = '0771234567';
$branch_id = BranchRepository::resolveToFixedBranchId($pdo, 2);
$code = 'TST001';

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, branch_id, supplier_code) VALUES (?,?,?,?)');
    $stmt->execute([$name, $phone, $branch_id, $code]);
    $id = (int)$pdo->lastInsertId();
    $row = $pdo->query("SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id WHERE s.id = $id")->fetch(PDO::FETCH_ASSOC);
    echo "insert OK id=$id branch={$row['branch_name']}\n";
    $pdo->rollBack();
    echo "rolled back (test only)\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERR: " . $e->getMessage() . "\n";
}
