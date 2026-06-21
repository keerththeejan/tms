<?php
declare(strict_types=1);

/**
 * VoucherValidator - Business Logic Validation for Payment Vouchers
 * 
 * Validates all accounting rules and business requirements
 * Ensures double-entry integrity and balancing
 */
class VoucherValidator
{
    private array $errors = [];
    private VoucherRepository $repository;

    public function __construct(VoucherRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Validate complete voucher before posting
     */
    public function validateVoucher(array $voucher, array $items): bool
    {
        $this->errors = [];

        // Basic validation
        if (!$this->validateBasicFields($voucher)) {
            return false;
        }

        // Items validation
        if (!$this->validateLineItems($items)) {
            return false;
        }

        // Double-entry validation
        if (!$this->validateDoubleEntry($items)) {
            return false;
        }

        // Balance validation
        if (!$this->validateBalance($items)) {
            return false;
        }

        return true;
    }

    /**
     * Validate basic voucher fields
     */
    private function validateBasicFields(array $voucher): bool
    {
        if (empty($voucher['voucher_date'])) {
            $this->errors[] = 'Voucher date is required';
            return false;
        }

        if (!$this->isValidDate($voucher['voucher_date'])) {
            $this->errors[] = 'Invalid voucher date format';
            return false;
        }

        if (empty($voucher['fiscal_year'])) {
            $this->errors[] = 'Fiscal year is required';
            return false;
        }

        if (empty($voucher['payment_mode']) || !in_array($voucher['payment_mode'], 
            ['CASH', 'BANK', 'CHEQUE', 'ONLINE', 'PETTY_CASH', 'OTHER'])) {
            $this->errors[] = 'Invalid payment mode';
            return false;
        }

        // Cheque validation if payment mode is CHEQUE
        if ($voucher['payment_mode'] === 'CHEQUE') {
            if (empty($voucher['cheque_number'])) {
                $this->errors[] = 'Cheque number is required when payment mode is CHEQUE';
                return false;
            }
            if (empty($voucher['cheque_date'])) {
                $this->errors[] = 'Cheque date is required when payment mode is CHEQUE';
                return false;
            }
        }

        return true;
    }

    /**
     * Validate line items exist and have valid data
     */
    private function validateLineItems(array $items): bool
    {
        if (empty($items)) {
            $this->errors[] = 'At least 2 line items are required (Debit and Credit)';
            return false;
        }

        if (count($items) < 2) {
            $this->errors[] = 'Minimum 2 line items required for double-entry accounting';
            return false;
        }

        foreach ($items as $index => $item) {
            if (empty($item['account_code'])) {
                $this->errors[] = "Line " . ($index + 1) . ": Account code is required";
                return false;
            }

            if (empty($item['account_name'])) {
                $this->errors[] = "Line " . ($index + 1) . ": Account name is required";
                return false;
            }

            // Check if account exists
            $account = $this->repository->getLedgerAccountByCode($item['account_code']);
            if (!$account) {
                $this->errors[] = "Line " . ($index + 1) . ": Invalid account code '{$item['account_code']}'";
                return false;
            }

            // At least one amount must be present
            $debit = (float) ($item['debit_amount'] ?? 0);
            $credit = (float) ($item['credit_amount'] ?? 0);

            if ($debit == 0 && $credit == 0) {
                $this->errors[] = "Line " . ($index + 1) . ": Both debit and credit cannot be zero";
                return false;
            }

            // Both cannot be non-zero (single entry per line)
            if ($debit > 0 && $credit > 0) {
                $this->errors[] = "Line " . ($index + 1) . ": Cannot have both debit and credit in same line";
                return false;
            }

            // Amount must be positive
            if ($debit < 0 || $credit < 0) {
                $this->errors[] = "Line " . ($index + 1) . ": Amounts must be positive";
                return false;
            }
        }

        return true;
    }

    /**
     * Validate double-entry accounting: Total Debit = Total Credit
     */
    private function validateDoubleEntry(array $items): bool
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $totalDebit += (float) ($item['debit_amount'] ?? 0);
            $totalCredit += (float) ($item['credit_amount'] ?? 0);
        }

        // Allow small rounding differences
        $difference = abs($totalDebit - $totalCredit);
        if ($difference > 0.01) {
            $this->errors[] = sprintf(
                'Debit (%s) and Credit (%s) must match. Difference: %s',
                $this->formatMoney($totalDebit),
                $this->formatMoney($totalCredit),
                $this->formatMoney($difference)
            );
            return false;
        }

        return true;
    }

    /**
     * Validate balance (already covered by double-entry, but explicit check)
     */
    private function validateBalance(array $items): bool
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $totalDebit += (float) ($item['debit_amount'] ?? 0);
            $totalCredit += (float) ($item['credit_amount'] ?? 0);
        }

        return abs($totalDebit - $totalCredit) < 0.01;
    }

    /**
     * Validate employee payment specific rules
     */
    public function validateEmployeePayment(array $paymentData): bool
    {
        $this->errors = [];

        if (empty($paymentData['employee_id'])) {
            $this->errors[] = 'Employee is required';
            return false;
        }

        $totalAmount = (float) ($paymentData['salary_amount'] ?? 0) +
                      (float) ($paymentData['advance_amount'] ?? 0) +
                      (float) ($paymentData['bonus_amount'] ?? 0) +
                      (float) ($paymentData['ot_payment'] ?? 0) +
                      (float) ($paymentData['allowance_amount'] ?? 0) -
                      (float) ($paymentData['deduction_amount'] ?? 0);

        if ($totalAmount <= 0) {
            $this->errors[] = 'Total payment amount must be greater than zero';
            return false;
        }

        return true;
    }

    /**
     * Check for duplicate voucher number
     */
    public function checkVoucherNumberUnique(string $voucherNumber, ?int $excludeId = null): bool
    {
        $existing = $this->repository->getVoucherByNumber($voucherNumber);
        
        if ($existing) {
            if ($excludeId && $existing['id'] == $excludeId) {
                return true;
            }
            $this->errors[] = "Voucher number '{$voucherNumber}' already exists";
            return false;
        }

        return true;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): string
    {
        return $this->errors[0] ?? 'Validation failed';
    }

    /**
     * Helper: Check if date format is valid
     */
    private function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Helper: Format money value
     */
    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2);
    }
}
