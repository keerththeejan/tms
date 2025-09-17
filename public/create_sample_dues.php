<?php
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = Database::pdo();

try {
    // Update existing delivery note to have an amount
    $pdo->prepare("UPDATE delivery_notes SET total_amount = 15000.00 WHERE id = 1")->execute();
    
    // Create additional delivery notes with dues
    $pdo->prepare("INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount) VALUES (1, 1, '2025-09-14', 8500.50)")->execute();
    $pdo->prepare("INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount) VALUES (1, 1, '2025-09-15', 12000.00)")->execute();
    
    echo "Sample delivery notes with dues created successfully!\n";
    echo "- DN #1: Rs. 15,000.00 (2025-09-13)\n";
    echo "- DN #2: Rs. 8,500.50 (2025-09-14)\n";
    echo "- DN #3: Rs. 12,000.00 (2025-09-15)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
