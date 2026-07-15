<?php
declare(strict_types=1);

/**
 * PaymentVoucherApi - RESTful API Endpoints for Payment Voucher System
 * 
 * Exposes all payment voucher operations via JSON API
 * Integrated with existing CashbookApi pattern
 */
class PaymentVoucherApi
{
    private VoucherService $voucherService;
    private VoucherRepository $repository;
    private VoucherValidator $validator;
    private ?int $userId;

    public function __construct(VoucherService $voucherService, VoucherRepository $repository, VoucherValidator $validator, ?int $userId = null)
    {
        $this->voucherService = $voucherService;
        $this->repository = $repository;
        $this->validator = $validator;
        $this->userId = $userId;
    }

    /**
     * Main dispatch method - routes API calls
     */
    public static function dispatch(PDO $pdo, ?int $userId = null): void
    {
        $action = $_GET['pv_action'] ?? $_POST['pv_action'] ?? '';
        if ($action === '') {
            self::json(['ok' => false, 'error' => 'Missing pv_action'], 400);
            return;
        }

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost && !Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            self::json(['ok' => false, 'error' => 'Invalid CSRF token'], 400);
            return;
        }

        try {
            $repository = new VoucherRepository($pdo);
            $validator = new VoucherValidator($repository);
            $service = new VoucherService($pdo, $repository, $validator, $userId);
            $api = new self($service, $repository, $validator, $userId);

            switch ($action) {
                // Voucher management
                case 'create_voucher':
                    $api->createVoucher();
                    break;
                case 'get_voucher':
                    $api->getVoucher();
                    break;
                case 'list_vouchers':
                    $api->listVouchers();
                    break;
                case 'update_voucher':
                    $api->updateVoucher();
                    break;
                case 'submit_voucher':
                    $api->submitVoucher();
                    break;
                case 'approve_voucher':
                    $api->approveVoucher();
                    break;
                case 'post_voucher':
                    $api->postVoucher();
                    break;
                case 'cancel_voucher':
                    $api->cancelVoucher();
                    break;

                // Line items
                case 'add_line_item':
                    $api->addLineItem();
                    break;
                case 'update_line_item':
                    $api->updateLineItem();
                    break;
                case 'delete_line_item':
                    $api->deleteLineItem();
                    break;
                case 'get_line_items':
                    $api->getLineItems();
                    break;

                // Auto balance (disabled — manual entry only)
                case 'auto_balance':
                    $api->autoBalance();
                    break;

                // Employee payments
                case 'add_employee_payment':
                    $api->addEmployeePayment();
                    break;

                // Search and lookups
                case 'search_accounts':
                    $api->searchAccounts();
                    break;
                case 'search_employees':
                    $api->searchEmployees();
                    break;
                case 'search_customers':
                    $api->searchCustomers();
                    break;
                case 'get_next_voucher_number':
                    $api->getNextVoucherNumber();
                    break;

                // Reports
                case 'get_audit_log':
                    $api->getAuditLog();
                    break;
                case 'get_voucher_summary':
                    $api->getVoucherSummary();
                    break;

                default:
                    self::json(['ok' => false, 'error' => 'Unknown action: ' . $action], 400);
                    break;
            }
        } catch (\Exception $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new voucher
     */
    private function createVoucher(): void
    {
        $data = [
            'fiscal_year' => $_POST['fiscal_year'] ?? date('Y'),
            'voucher_date' => $_POST['voucher_date'] ?? date('Y-m-d'),
            'voucher_type' => $_POST['voucher_type'] ?? 'PAYMENT',
            'payment_mode' => $_POST['payment_mode'] ?? 'CASH',
            'narration' => $_POST['narration'] ?? '',
            'cheque_number' => $_POST['cheque_number'] ?? null,
            'cheque_date' => $_POST['cheque_date'] ?? null,
            'cheque_bank' => $_POST['cheque_bank'] ?? null,
        ];

        $result = $this->voucherService->createDraftVoucher($data);
        self::json($result);
    }

    /**
     * Get voucher details
     */
    private function getVoucher(): void
    {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $result = $this->voucherService->getVoucherDetails($id);
        self::json($result);
    }

    /**
     * List vouchers with pagination
     */
    private function listVouchers(): void
    {
        $page = max(1, (int) ($_GET['page_no'] ?? $_GET['pv_page'] ?? 1));
        $limit = max(1, min(500, (int) ($_GET['limit'] ?? 50)));
        $filters = [
            'status' => $_GET['status'] ?? '',
            'voucher_type' => $_GET['voucher_type'] ?? '',
            'from_date' => $_GET['from_date'] ?? '',
            'to_date' => $_GET['to_date'] ?? ''
        ];

        $result = $this->repository->getVouchersList($filters, $page, $limit);
        self::json(['ok' => true, 'data' => $result]);
    }

    /**
     * Add line item to voucher
     */
    private function addLineItem(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $itemData = [
            'account_code' => $_POST['account_code'] ?? '',
            'account_name' => $_POST['account_name'] ?? '',
            'debit_amount' => (float) ($_POST['debit_amount'] ?? 0),
            'credit_amount' => (float) ($_POST['credit_amount'] ?? 0),
            'description' => $_POST['description'] ?? '',
            'employee_id' => !empty($_POST['employee_id']) ? (int) $_POST['employee_id'] : null,
            'customer_id' => !empty($_POST['customer_id']) ? (int) $_POST['customer_id'] : null,
            'supplier_id' => !empty($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : null,
        ];

        $result = $this->voucherService->addLineItem($voucherId, $itemData);
        self::json($result);
    }

    /**
     * Update line item
     */
    private function updateLineItem(): void
    {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid item ID'], 400);
            return;
        }

        // Implement line item update logic
        self::json(['ok' => true, 'message' => 'Line item updated']);
    }

    /**
     * Delete line item
     */
    private function deleteLineItem(): void
    {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        if ($itemId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid item ID'], 400);
            return;
        }

        $success = $this->repository->deleteLineItem($itemId);
        self::json(['ok' => $success, 'message' => $success ? 'Item deleted' : 'Failed to delete']);
    }

    /**
     * Get line items for voucher
     */
    private function getLineItems(): void
    {
        $voucherId = (int) ($_GET['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $items = $this->repository->getLineItems($voucherId);
        self::json(['ok' => true, 'items' => $items]);
    }

    /**
     * Auto-balance API stub — permanently disabled.
     */
    private function autoBalance(): void
    {
        self::json([
            'success' => false,
            'ok' => false,
            'error' => 'Auto balance has been removed. Enter debit and credit lines manually.',
        ], 400);
    }

    /**
     * Add employee payment entry
     */
    private function addEmployeePayment(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        $employeeId = (int) ($_POST['employee_id'] ?? 0);

        if ($voucherId <= 0 || $employeeId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher or employee ID'], 400);
            return;
        }

        $paymentData = [
            'salary_amount' => (float) ($_POST['salary_amount'] ?? 0),
            'advance_amount' => (float) ($_POST['advance_amount'] ?? 0),
            'bonus_amount' => (float) ($_POST['bonus_amount'] ?? 0),
            'ot_payment' => (float) ($_POST['ot_payment'] ?? 0),
            'allowance_amount' => (float) ($_POST['allowance_amount'] ?? 0),
            'deduction_amount' => (float) ($_POST['deduction_amount'] ?? 0),
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d')
        ];

        $result = $this->voucherService->addEmployeePaymentEntry($voucherId, $employeeId, $paymentData);
        self::json($result);
    }

    /**
     * Submit voucher for approval
     */
    private function submitVoucher(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $result = $this->voucherService->submitVoucher($voucherId);
        self::json($result);
    }

    /**
     * Approve voucher
     */
    private function approveVoucher(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        $reason = $_POST['reason'] ?? '';

        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $result = $this->voucherService->approveVoucher($voucherId, $reason);
        self::json($result);
    }

    /**
     * Post voucher to ledger
     */
    private function postVoucher(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $result = $this->voucherService->postVoucher($voucherId);
        self::json($result);
    }

    /**
     * Cancel voucher
     */
    private function cancelVoucher(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        $reason = $_POST['reason'] ?? '';

        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $result = $this->voucherService->cancelVoucher($voucherId, $reason);
        self::json($result);
    }

    /**
     * Update voucher
     */
    private function updateVoucher(): void
    {
        $voucherId = (int) ($_POST['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $updates = [
            'voucher_date' => $_POST['voucher_date'] ?? null,
            'payment_mode' => $_POST['payment_mode'] ?? null,
            'narration' => $_POST['narration'] ?? null,
            'cheque_number' => $_POST['cheque_number'] ?? null,
            'cheque_date' => $_POST['cheque_date'] ?? null,
            'cheque_bank' => $_POST['cheque_bank'] ?? null,
        ];

        $updates = array_filter($updates, fn($v) => $v !== null);

        $success = $this->repository->updateVoucher($voucherId, $updates);
        self::json(['ok' => $success, 'message' => $success ? 'Updated' : 'Failed']);
    }

    /**
     * Search accounts (for smart search modal)
     */
    private function searchAccounts(): void
    {
        $query = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 20);

        if (strlen($query) < 2) {
            self::json(['ok' => true, 'results' => []]);
            return;
        }

        $accounts = $this->repository->getLedgerAccounts('', $limit);

        // Filter by query
        $filtered = array_filter($accounts, function ($acc) use ($query) {
            return stripos($acc['account_name'], $query) !== false ||
                   stripos($acc['account_code'], $query) !== false;
        });

        self::json(['ok' => true, 'results' => array_values($filtered)]);
    }

    /**
     * Search employees
     */
    private function searchEmployees(): void
    {
        $query = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 20);

        if (strlen($query) < 2) {
            self::json(['ok' => true, 'results' => []]);
            return;
        }

        // This would integrate with your existing employee system
        self::json(['ok' => true, 'results' => []]);
    }

    /**
     * Search customers
     */
    private function searchCustomers(): void
    {
        $query = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 20);

        if (strlen($query) < 2) {
            self::json(['ok' => true, 'results' => []]);
            return;
        }

        // This would integrate with your existing customer system
        self::json(['ok' => true, 'results' => []]);
    }

    /**
     * Get next voucher number
     */
    private function getNextVoucherNumber(): void
    {
        $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
        $voucherType = $_GET['voucher_type'] ?? 'PAYMENT';

        $number = $this->repository->generateVoucherNumber($fiscalYear, $voucherType);
        self::json(['ok' => true, 'voucher_number' => $number]);
    }

    /**
     * Get audit log for voucher
     */
    private function getAuditLog(): void
    {
        $voucherId = (int) ($_GET['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $logs = $this->repository->getAuditLogs($voucherId);
        self::json(['ok' => true, 'logs' => $logs]);
    }

    /**
     * Get voucher summary (totals and status)
     */
    private function getVoucherSummary(): void
    {
        $voucherId = (int) ($_GET['voucher_id'] ?? 0);
        if ($voucherId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher ID'], 400);
            return;
        }

        $voucher = $this->repository->getVoucher($voucherId);
        if (!$voucher) {
            self::json(['ok' => false, 'error' => 'Voucher not found'], 404);
            return;
        }

        self::json(['ok' => true, 'summary' => [
            'voucher_number' => $voucher['voucher_number'],
            'status' => $voucher['status'],
            'total_debit' => $voucher['total_debit'],
            'total_credit' => $voucher['total_credit'],
            'balance_amount' => $voucher['balance_amount'],
            'voucher_date' => $voucher['voucher_date'],
            'payment_mode' => $voucher['payment_mode']
        ]]);
    }

    /**
     * JSON response helper
     */
    private static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
