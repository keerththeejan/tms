<?php
/**
 * Payment Voucher Module Integration
 * 
 * Add this to your main application bootstrap to enable the Payment Voucher module
 * This integrates with the existing Cashbook system
 */

// Register the Payment Voucher module
if (isset($_GET['page']) && $_GET['page'] === 'payment_voucher') {
    // Route the payment voucher module
    $action = $_GET['action'] ?? 'entry';

    // Load module view
    if ($action === 'entry') {
        // Display payment voucher entry form
        include __DIR__ . '/../views/payment_voucher/entry.php';
    } elseif ($action === 'list') {
        // Display list of vouchers
        include __DIR__ . '/../views/payment_voucher/list.php';
    }
}

// API dispatch for payment vouchers
if (isset($_GET['page']) && $_GET['page'] === 'api_payment_voucher') {
    $pdo = Database::pdo();
    $userId = Auth::user()['id'] ?? null;

    PaymentVoucherApi::dispatch($pdo, $userId);
}
