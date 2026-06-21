<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
echo 'parcels total: ' . (int)$pdo->query('SELECT COUNT(*) FROM parcels')->fetchColumn() . "\n";
$rows = $pdo->query('SELECT from_branch_id, to_branch_id, vehicle_no, COUNT(*) c FROM parcels GROUP BY from_branch_id, to_branch_id, vehicle_no LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo " from={$r['from_branch_id']} to={$r['to_branch_id']} veh={$r['vehicle_no']} count={$r['c']}\n";
}
