<?php
require __DIR__ . '/../../app/bootstrap.php';
$pdo = Database::pdo();
$result = DataReset::deleteAllCustomerData($pdo);
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
