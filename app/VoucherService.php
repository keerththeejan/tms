<?php
declare(strict_types=1);

/**
 * VoucherService - Core Business Logic for Payment Vouchers
 * 
 * Orchestrates all payment voucher operations including:
 * - Voucher creation and management
 * - Line item management
 * - Automatic balance calculation
 * - Double-entry posting
 * - Employee payment allocation
 * - Audit logging
 */
class VoucherService
{
    private VoucherRepository $repository;
    private VoucherValidator $validator;
    private PDO $pdo;
    private ?int $currentUserId;

    public function __construct(PDO $pdo, VoucherRepository $repository, VoucherValidator $validator, ?int $currentUserId = null)
    {
        $this->pdo = $pdo;
        $this->repository = $repository;
        $this->validator = $validator;
        $this->currentUserId = $currentUserId;
    }

    /**
     * Create a new draft payment voucher
     */
    public function createDraftVoucher(array $data): array
    {
        try {
            $this->repository->beginTransaction();

            // Generate voucher number
            $fiscalYear = $data['fiscal_year'] ?? date('Y');
            $voucherType = $data['voucher_type'] ?? 'PAYMENT';
            $voucherNumber = $this->repository->generateVoucherNumber($fiscalYear, $voucherType);

            // Create voucher
            $voucherId = $this->repository->createVoucher([
                'voucher_number' => $voucherNumber,
                'voucher_type' => $voucherType,
                'fiscal_year' => $fiscalYear,
                'voucher_date' => $data['voucher_date'] ?? date('Y-m-d'),
                'payment_mode' => $data['payment_mode'] ?? 'CASH',
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'cheque_bank' => $data['cheque_bank'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'narration' => $data['narration'] ?? null,
                'total_debit' => $data['total_debit'] ?? 0,
                'total_credit' => $data['total_credit'] ?? 0,
                'balance_amount' => $data['balance_amount'] ?? 0,
                'created_by' => $this->currentUserId
            ]);

            // Log creation
            $this->logAudit($voucherId, 'CREATE', [
                'action_description' => 'Draft voucher created',
                'voucher_number' => $voucherNumber
            ]);

            $this->repository->commit();

            return [
                'success' => true,
                'voucher_id' => $voucherId,
                'voucher_number' => $voucherNumber,
                'message' => 'Voucher created successfully'
            ];
        } catch (\Exception $e) {
            $this->repository->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add line item to voucher with automatic validation
     */
    public function addLineItem(int $voucherId, array $itemData): array
    {
        try {
            $voucher = $this->repository->getVoucher($voucherId);
            if (!$voucher) {
                return ['success' => false, 'error' => 'Voucher not found'];
            }

            // Get next line number
            $items = $this->repository->getLineItems($voucherId);
            $lineNumber = count($items) + 1;

            $itemData['line_number'] = $lineNumber;

            // Validate account exists
            $account = $this->repository->getLedgerAccountByCode($itemData['account_code']);
            if (!$account) {
                return ['success' => false, 'error' => 'Account code not found'];
            }

            // Create line item
            $itemId = $this->repository->addLineItem($voucherId, $itemData);

            // Update voucher totals
            $this->updateVoucherTotals($voucherId);

            return [
                'success' => true,
                'item_id' => $itemId,
                'line_number' => $lineNumber,
                'message' => 'Line item added successfully'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Auto balance permanently disabled — manual voucher entry only.
     */
    public function autoBalanceVoucher(int $voucherId): array
    {
        unset($voucherId);

        return [
            'success' => false,
            'error' => 'Auto balance has been removed. Enter debit and credit lines manually.',
        ];
    }

    /**
     * Add automatic employee payment entry
     */
    public function addEmployeePaymentEntry(int $voucherId, int $employeeId, array $paymentData): array
    {
        try {
            // Validate employee payment data
            if (!$this->validator->validateEmployeePayment(['employee_id' => $employeeId] + $paymentData)) {
                return [
                    'success' => false,
                    'error' => $this->validator->getFirstError()
                ];
            }

            // Calculate total payment
            $totalPayment = (float) ($paymentData['salary_amount'] ?? 0) +
                           (float) ($paymentData['advance_amount'] ?? 0) +
                           (float) ($paymentData['bonus_amount'] ?? 0) +
                           (float) ($paymentData['ot_payment'] ?? 0) +
                           (float) ($paymentData['allowance_amount'] ?? 0) -
                           (float) ($paymentData['deduction_amount'] ?? 0);

            // Create salary expense line
            $salaryAccount = $this->repository->getLedgerAccountByCode('5001'); // Salary Expense
            if ($salaryAccount) {
                $this->addLineItem($voucherId, [
                    'account_code' => '5001',
                    'account_name' => 'Salary Expense',
                    'debit_amount' => $totalPayment,
                    'credit_amount' => 0,
                    'description' => 'Salary payment to employee'
                ]);
            }

            // Create employee payable line
            $this->addLineItem($voucherId, [
                'account_code' => 'EMP-' . str_pad((string) $employeeId, 5, '0', STR_PAD_LEFT),
                'account_name' => 'Employee Payable',
                'employee_id' => $employeeId,
                'debit_amount' => 0,
                'credit_amount' => $totalPayment,
                'description' => 'Payment to employee'
            ]);

            // Record employee payment tracking
            $this->repository->recordEmployeePayment($voucherId, $employeeId, [
                'salary_amount' => $paymentData['salary_amount'] ?? 0,
                'advance_amount' => $paymentData['advance_amount'] ?? 0,
                'bonus_amount' => $paymentData['bonus_amount'] ?? 0,
                'ot_payment' => $paymentData['ot_payment'] ?? 0,
                'allowance_amount' => $paymentData['allowance_amount'] ?? 0,
                'deduction_amount' => $paymentData['deduction_amount'] ?? 0,
                'total_payment' => $totalPayment,
                'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d')
            ]);

            return [
                'success' => true,
                'total_payment' => $totalPayment,
                'message' => 'Employee payment entry added'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate and submit voucher for approval
     */
    public function submitVoucher(int $voucherId): array
    {
        try {
            $this->repository->beginTransaction();

            $voucher = $this->repository->getVoucher($voucherId);
            if (!$voucher) {
                return ['success' => false, 'error' => 'Voucher not found'];
            }

            $items = $this->repository->getLineItems($voucherId);

            // Full validation
            if (!$this->validator->validateVoucher($voucher, $items)) {
                return [
                    'success' => false,
                    'errors' => $this->validator->getErrors()
                ];
            }

            // Update status
            $this->repository->updateVoucher($voucherId, [
                'status' => 'SUBMITTED',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Log submission
            $this->logAudit($voucherId, 'UPDATE', [
                'status_before' => 'DRAFT',
                'status_after' => 'SUBMITTED',
                'action_description' => 'Voucher submitted for approval'
            ]);

            $this->repository->commit();

            return [
                'success' => true,
                'message' => 'Voucher submitted for approval'
            ];
        } catch (\Exception $e) {
            $this->repository->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Approve voucher and prepare for posting
     */
    public function approveVoucher(int $voucherId, string $reason = ''): array
    {
        try {
            $this->repository->beginTransaction();

            $voucher = $this->repository->getVoucher($voucherId);
            if (!$voucher) {
                return ['success' => false, 'error' => 'Voucher not found'];
            }

            if ($voucher['status'] !== 'SUBMITTED') {
                return ['success' => false, 'error' => 'Voucher must be in SUBMITTED status'];
            }

            $this->repository->updateVoucher($voucherId, [
                'status' => 'APPROVED',
                'approval_status' => 'APPROVED',
                'approved_by' => $this->currentUserId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit($voucherId, 'APPROVE', [
                'status_before' => 'SUBMITTED',
                'status_after' => 'APPROVED',
                'reason' => $reason
            ]);

            $this->repository->commit();

            return [
                'success' => true,
                'message' => 'Voucher approved successfully'
            ];
        } catch (\Exception $e) {
            $this->repository->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post voucher to ledger (create journal entries)
     */
    public function postVoucher(int $voucherId): array
    {
        try {
            $this->repository->beginTransaction();

            $voucher = $this->repository->getVoucher($voucherId);
            if (!$voucher) {
                return ['success' => false, 'error' => 'Voucher not found'];
            }

            if (!in_array($voucher['status'], ['APPROVED', 'DRAFT'])) {
                return ['success' => false, 'error' => 'Voucher cannot be posted in current status'];
            }

            $items = $this->repository->getLineItems($voucherId);

            // Final validation
            if (!$this->validator->validateVoucher($voucher, $items)) {
                return [
                    'success' => false,
                    'errors' => $this->validator->getErrors()
                ];
            }

            // Create journal entries for each line item
            foreach ($items as $item) {
                $this->createJournalEntry($voucherId, $item);
            }

            // Update voucher status
            $this->repository->updateVoucher($voucherId, [
                'status' => 'POSTED',
                'posted_by' => $this->currentUserId,
                'posted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit($voucherId, 'POST', [
                'status_before' => 'APPROVED',
                'status_after' => 'POSTED',
                'action_description' => 'Voucher posted to ledger'
            ]);

            $this->repository->commit();

            return [
                'success' => true,
                'message' => 'Voucher posted to ledger successfully'
            ];
        } catch (\Exception $e) {
            $this->repository->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancel or reject voucher
     */
    public function cancelVoucher(int $voucherId, string $reason = ''): array
    {
        try {
            $this->repository->beginTransaction();

            $voucher = $this->repository->getVoucher($voucherId);
            if (!$voucher) {
                return ['success' => false, 'error' => 'Voucher not found'];
            }

            $this->repository->updateVoucher($voucherId, [
                'status' => 'CANCELLED',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->logAudit($voucherId, 'DELETE', [
                'status_before' => $voucher['status'],
                'status_after' => 'CANCELLED',
                'reason' => $reason
            ]);

            $this->repository->commit();

            return [
                'success' => true,
                'message' => 'Voucher cancelled'
            ];
        } catch (\Exception $e) {
            $this->repository->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get voucher with all details
     */
    public function getVoucherDetails(int $voucherId): array
    {
        $voucher = $this->repository->getVoucher($voucherId);
        if (!$voucher) {
            return ['success' => false, 'error' => 'Voucher not found'];
        }

        $items = $this->repository->getLineItems($voucherId);
        $auditLogs = $this->repository->getAuditLogs($voucherId);

        return [
            'success' => true,
            'voucher' => $voucher,
            'items' => $items,
            'audit_logs' => $auditLogs
        ];
    }

    /**
     * Update voucher totals based on line items
     */
    private function updateVoucherTotals(int $voucherId): void
    {
        $items = $this->repository->getLineItems($voucherId);

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $totalDebit += (float) ($item['debit_amount'] ?? 0);
            $totalCredit += (float) ($item['credit_amount'] ?? 0);
        }

        $balance = $totalDebit - $totalCredit;

        $this->repository->updateVoucher($voucherId, [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balance_amount' => $balance
        ]);
    }

    /**
     * Create journal entry in ledger (placeholder for actual ledger posting)
     */
    private function createJournalEntry(int $voucherId, array $item): void
    {
        // This would integrate with your existing ledger system
        // For now, it's a placeholder for extending into your cashbook system
        // Implement based on your existing CashbookRepository structure
    }

    /**
     * Log action in audit trail
     */
    private function logAudit(int $voucherId, string $actionType, array $data): void
    {
        try {
            $auditData = [
                'action_type' => $actionType,
                'user_id' => $this->currentUserId,
                'ip_address' => $this->getUserIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'reason' => $data['action_description'] ?? $data['reason'] ?? null
            ] + $data;

            $this->repository->logAudit($voucherId, $auditData);
        } catch (\Exception $e) {
            // Silently fail - don't break main operation if audit logging fails
        }
    }

    /**
     * Get user IP address
     */
    private function getUserIP(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 
              $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
              $_SERVER['REMOTE_ADDR'] ?? 
              'UNKNOWN';

        // Handle multiple IPs
        if (strpos($ip, ',') !== false) {
            $ips = array_map('trim', explode(',', $ip));
            $ip = $ips[0];
        }

        return $ip;
    }
}
