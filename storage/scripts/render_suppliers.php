<?php
require __DIR__ . '/../../app/bootstrap.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['page' => 'suppliers'];
$pdo = Database::pdo();

$name = trim($_GET['name'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$code = trim($_GET['code'] ?? '');
$branch_id = (int)($_GET['branch_id'] ?? 0);
$q = trim($_GET['q'] ?? '');

$hasFilters = ($name !== '' || $phone !== '' || $code !== '' || $branch_id > 0);
if ($hasFilters) {
    $sql = 'SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id WHERE 1=1';
    $params = [];
    if ($name !== '') { $sql .= ' AND s.name LIKE ?'; $params[] = "%$name%"; }
    if ($phone !== '') { $sql .= ' AND s.phone LIKE ?'; $params[] = "%$phone%"; }
    if ($code !== '') { $sql .= ' AND s.supplier_code LIKE ?'; $params[] = "%$code%"; }
    if ($branch_id > 0) { $sql .= ' AND s.branch_id = ?'; $params[] = $branch_id; }
    $sql .= ' ORDER BY s.created_at DESC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
} else if ($q !== '') {
    $stmt = $pdo->prepare("SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id WHERE s.name LIKE ? OR s.phone LIKE ? OR s.supplier_code LIKE ? ORDER BY s.created_at DESC LIMIT 100");
    $like = "%$q%";
    $stmt->execute([$like,$like,$like]);
    $suppliers = $stmt->fetchAll();
} else {
    $suppliers = $pdo->query('SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id ORDER BY s.created_at DESC LIMIT 100')->fetchAll();
}
$branchesAll = BranchRepository::forFilters($pdo);
$branchesForm = BranchRepository::forDropdowns($pdo);

ob_start();
include __DIR__ . '/../../views/suppliers/index.php';
$html = ob_get_clean();
echo "suppliers: " . count($suppliers) . "\n";
echo "branchesAll: " . count($branchesAll) . "\n";
echo "branchesForm: " . count($branchesForm) . "\n";
echo "render OK, html bytes: " . strlen($html) . "\n";
if (strpos($html, 'Colombo') === false) echo "WARN: Colombo not in HTML\n";
if (strpos($html, 'Fatal error') !== false) echo "ERR: Fatal in output\n";
