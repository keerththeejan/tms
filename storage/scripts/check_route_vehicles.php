<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
$from = date('Y-m-01');
$to = date('Y-m-d');
echo "parcels with vehicle_no ($from to $to): ";
try {
    $n = (int)$pdo->query("SELECT COUNT(*) FROM parcels WHERE vehicle_no IS NOT NULL AND vehicle_no <> '' AND DATE(created_at) BETWEEN '$from' AND '$to'")->fetchColumn();
    echo "$n\n";
    $rows = $pdo->query("SELECT vehicle_no, COUNT(*) c FROM parcels WHERE vehicle_no IS NOT NULL AND vehicle_no <> '' GROUP BY vehicle_no LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) echo "  {$r['vehicle_no']}: {$r['c']}\n";
} catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
$user = $pdo->query("SELECT branch_id, is_main_branch FROM users WHERE username='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "admin branch_id: " . ($user['branch_id'] ?? 'null') . " main=" . ($user['is_main_branch'] ?? 0) . "\n";
