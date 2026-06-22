<?php

declare(strict_types=1);

/**
 * Transport Accounting Service
 * Integrates transport operations with accounting vouchers
 * Auto-generates accounting vouchers for fuel, expenses, invoices, payments, salaries
 */
class TransportAccountingService
{
    /**
     * Auto-create journal voucher for fuel entry
     */
    public static function createFuelJournalVoucher(PDO $pdo, array $fuelData, int $userId): ?array
    {
        $pdo->beginTransaction();
        try {
            // Get fuel expense account
            $fuelAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $fuelAccount->execute(['FUEL_DIESEL']);
            $fuelAccountId = $fuelAccount->fetchColumn();
            
            if (!$fuelAccountId) {
                throw new RuntimeException('Fuel expense account not found. Please configure Chart of Accounts.');
            }

            // Get cash account
            $cashAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $cashAccount->execute(['CASH_MAIN']);
            $cashAccountId = $cashAccount->fetchColumn();
            
            if (!$cashAccountId) {
                throw new RuntimeException('Cash account not found. Please configure Chart of Accounts.');
            }

            $amount = (float) ($fuelData['amount'] ?? 0);
            $voucherDate = $fuelData['date'] ?? date('Y-m-d');
            $vehicleNo = $fuelData['vehicle_no'] ?? '';
            $fuelType = $fuelData['fuel_type'] ?? 'Diesel';

            // Create voucher
            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'JOURNAL',
                'voucher_date' => $voucherDate,
                'fiscal_year' => date('Y'),
                'reference_number' => $fuelData['reference_number'] ?? 'FUEL-' . $vehicleNo,
                'payment_mode' => 'CASH',
                'narration' => "Fuel purchase for vehicle {$vehicleNo} - {$fuelType}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'branch_id' => $fuelData['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Create voucher details
            $details = [
                [
                    'account_id' => $fuelAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => "Fuel expense - {$fuelType} for vehicle {$vehicleNo}",
                ],
                [
                    'account_id' => $cashAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'narration' => "Payment for fuel - {$vehicleNo}",
                ],
            ];

            VoucherDetailRepository::createBatch($pdo, (int) $voucher['id'], $details);

            // Post voucher
            AccountingVoucherRepository::post($pdo, (int) $voucher['id'], $userId);

            // Create transport mapping
            $st = $pdo->prepare(
                'INSERT INTO transport_voucher_mapping (voucher_id, transport_type, transport_id, mapping_details)
                 VALUES (?, ?, ?, ?)'
            );
            $st->execute([
                (int) $voucher['id'],
                'FUEL',
                (int) ($fuelData['fuel_id'] ?? 0),
                json_encode($fuelData),
            ]);

            $pdo->commit();
            return $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Auto-create expense voucher for vehicle expenses
     */
    public static function createVehicleExpenseVoucher(PDO $pdo, array $expenseData, int $userId): ?array
    {
        $pdo->beginTransaction();
        try {
            // Get vehicle expense account based on expense type
            $expenseType = $expenseData['expense_type'] ?? 'MAINTENANCE';
            $accountCodes = [
                'MAINTENANCE' => 'VEH_MAINTENANCE',
                'REPAIRS' => 'VEH_REPAIRS',
                'OTHER' => 'VEH_MAINTENANCE',
            ];
            $accountCode = $accountCodes[$expenseType] ?? 'VEH_MAINTENANCE';

            $expenseAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $expenseAccount->execute([$accountCode]);
            $expenseAccountId = $expenseAccount->fetchColumn();
            
            if (!$expenseAccountId) {
                throw new RuntimeException('Vehicle expense account not found. Please configure Chart of Accounts.');
            }

            // Get cash account
            $cashAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $cashAccount->execute(['CASH_MAIN']);
            $cashAccountId = $cashAccount->fetchColumn();
            
            if (!$cashAccountId) {
                throw new RuntimeException('Cash account not found. Please configure Chart of Accounts.');
            }

            $amount = (float) ($expenseData['amount'] ?? 0);
            $voucherDate = $expenseData['date'] ?? date('Y-m-d');
            $vehicleNo = $expenseData['vehicle_no'] ?? '';

            // Create voucher
            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => $voucherDate,
                'fiscal_year' => date('Y'),
                'reference_number' => $expenseData['reference_number'] ?? 'EXP-' . $vehicleNo,
                'payment_mode' => 'CASH',
                'narration' => "Vehicle expense for {$vehicleNo} - {$expenseType}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'branch_id' => $expenseData['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Create voucher details
            $details = [
                [
                    'account_id' => $expenseAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => "Vehicle expense - {$expenseType} for {$vehicleNo}",
                ],
                [
                    'account_id' => $cashAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'narration' => "Payment for vehicle expense - {$vehicleNo}",
                ],
            ];

            VoucherDetailRepository::createBatch($pdo, (int) $voucher['id'], $details);

            // Post voucher
            AccountingVoucherRepository::post($pdo, (int) $voucher['id'], $userId);

            // Create transport mapping
            $st = $pdo->prepare(
                'INSERT INTO transport_voucher_mapping (voucher_id, transport_type, transport_id, mapping_details)
                 VALUES (?, ?, ?, ?)'
            );
            $st->execute([
                (int) $voucher['id'],
                'VEHICLE_EXPENSE',
                (int) ($expenseData['expense_id'] ?? 0),
                json_encode($expenseData),
            ]);

            $pdo->commit();
            return $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Auto-create sales voucher for customer invoice
     */
    public static function createSalesVoucher(PDO $pdo, array $invoiceData, int $userId): ?array
    {
        $pdo->beginTransaction();
        try {
            // Get sales income account
            $salesAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $salesAccount->execute(['SALES_FREIGHT']);
            $salesAccountId = $salesAccount->fetchColumn();
            
            if (!$salesAccountId) {
                throw new RuntimeException('Sales income account not found. Please configure Chart of Accounts.');
            }

            // Get customer account (sundry debtor) via customer ledger link when available
            $customerId = (int) ($invoiceData['customer_id'] ?? 0);
            if ($customerId > 0) {
                $customerAccountId = CustomerLedgerRepository::resolveAccountIdForCustomer(
                    $pdo,
                    $customerId,
                    (string) ($invoiceData['customer_name'] ?? 'Customer'),
                    $userId
                );
            } else {
                $customerAccount = $pdo->prepare(
                    'SELECT id FROM accounts WHERE account_name LIKE ? AND account_group_id IN (SELECT id FROM account_groups WHERE group_code = ?) LIMIT 1'
                );
                $customerAccount->execute(['%' . ($invoiceData['customer_name'] ?? '') . '%', 'SUNDRY_DEBTORS']);
                $customerAccountId = $customerAccount->fetchColumn();

                if (!$customerAccountId) {
                    $customerAccountId = self::createCustomerAccount($pdo, $invoiceData['customer_name'] ?? 'Customer');
                }
            }

            $amount = (float) ($invoiceData['amount'] ?? 0);
            $voucherDate = $invoiceData['date'] ?? date('Y-m-d');
            $invoiceNo = $invoiceData['invoice_no'] ?? '';

            // Create voucher
            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'RECEIPT',
                'voucher_date' => $voucherDate,
                'fiscal_year' => date('Y'),
                'reference_number' => $invoiceNo,
                'payment_mode' => 'CASH',
                'narration' => "Freight income - Invoice {$invoiceNo}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'branch_id' => $invoiceData['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Create voucher details
            $details = [
                [
                    'account_id' => $customerAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => "Customer payment - Invoice {$invoiceNo}",
                ],
                [
                    'account_id' => $salesAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'narration' => "Freight income - Invoice {$invoiceNo}",
                ],
            ];

            VoucherDetailRepository::createBatch($pdo, (int) $voucher['id'], $details);

            // Post voucher
            AccountingVoucherRepository::post($pdo, (int) $voucher['id'], $userId);

            // Create transport mapping
            $st = $pdo->prepare(
                'INSERT INTO transport_voucher_mapping (voucher_id, transport_type, transport_id, mapping_details)
                 VALUES (?, ?, ?, ?)'
            );
            $st->execute([
                (int) $voucher['id'],
                'CUSTOMER_INVOICE',
                (int) ($invoiceData['invoice_id'] ?? 0),
                json_encode($invoiceData),
            ]);

            $pdo->commit();
            return $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Auto-create payment voucher for supplier payment
     */
    public static function createSupplierPaymentVoucher(PDO $pdo, array $paymentData, int $userId): ?array
    {
        $pdo->beginTransaction();
        try {
            // Get supplier account (sundry creditor)
            $supplierAccount = $pdo->prepare(
                'SELECT id FROM accounts WHERE account_name LIKE ? AND account_group_id IN (SELECT id FROM account_groups WHERE group_code = ?) LIMIT 1'
            );
            $supplierAccount->execute(['%' . ($paymentData['supplier_name'] ?? '') . '%', 'SUNDRY_CREDITORS']);
            $supplierAccountId = $supplierAccount->fetchColumn();
            
            if (!$supplierAccountId) {
                // Create supplier account if not exists
                $supplierAccountId = self::createSupplierAccount($pdo, $paymentData['supplier_name'] ?? 'Supplier');
            }

            // Get cash account
            $cashAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $cashAccount->execute(['CASH_MAIN']);
            $cashAccountId = $cashAccount->fetchColumn();
            
            if (!$cashAccountId) {
                throw new RuntimeException('Cash account not found. Please configure Chart of Accounts.');
            }

            $amount = (float) ($paymentData['amount'] ?? 0);
            $voucherDate = $paymentData['date'] ?? date('Y-m-d');
            $paymentNo = $paymentData['payment_no'] ?? '';

            // Create voucher
            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => $voucherDate,
                'fiscal_year' => date('Y'),
                'reference_number' => $paymentNo,
                'payment_mode' => 'CASH',
                'narration' => "Supplier payment - Payment {$paymentNo}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'branch_id' => $paymentData['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Create voucher details
            $details = [
                [
                    'account_id' => $supplierAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => "Supplier payment - Payment {$paymentNo}",
                ],
                [
                    'account_id' => $cashAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'narration' => "Payment to supplier - Payment {$paymentNo}",
                ],
            ];

            VoucherDetailRepository::createBatch($pdo, (int) $voucher['id'], $details);

            // Post voucher
            AccountingVoucherRepository::post($pdo, (int) $voucher['id'], $userId);

            // Create transport mapping
            $st = $pdo->prepare(
                'INSERT INTO transport_voucher_mapping (voucher_id, transport_type, transport_id, mapping_details)
                 VALUES (?, ?, ?, ?)'
            );
            $st->execute([
                (int) $voucher['id'],
                'SUPPLIER_PAYMENT',
                (int) ($paymentData['payment_id'] ?? 0),
                json_encode($paymentData),
            ]);

            $pdo->commit();
            return $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Auto-create salary voucher for driver salary
     */
    public static function createDriverSalaryVoucher(PDO $pdo, array $salaryData, int $userId): ?array
    {
        $pdo->beginTransaction();
        try {
            // Get driver salary account
            $salaryAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $salaryAccount->execute(['DRIVER_SALARY_WAGES']);
            $salaryAccountId = $salaryAccount->fetchColumn();
            
            if (!$salaryAccountId) {
                throw new RuntimeException('Driver salary account not found. Please configure Chart of Accounts.');
            }

            // Get cash account
            $cashAccount = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
            $cashAccount->execute(['CASH_MAIN']);
            $cashAccountId = $cashAccount->fetchColumn();
            
            if (!$cashAccountId) {
                throw new RuntimeException('Cash account not found. Please configure Chart of Accounts.');
            }

            $amount = (float) ($salaryData['amount'] ?? 0);
            $voucherDate = $salaryData['date'] ?? date('Y-m-d');
            $driverName = $salaryData['driver_name'] ?? '';

            // Create voucher
            $voucher = AccountingVoucherRepository::create($pdo, [
                'voucher_type' => 'PAYMENT',
                'voucher_date' => $voucherDate,
                'fiscal_year' => date('Y'),
                'reference_number' => $salaryData['reference_number'] ?? 'SAL-' . $driverName,
                'payment_mode' => 'CASH',
                'narration' => "Driver salary payment - {$driverName}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'branch_id' => $salaryData['branch_id'] ?? null,
                'created_by' => $userId,
            ]);

            // Create voucher details
            $details = [
                [
                    'account_id' => $salaryAccountId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => "Driver salary - {$driverName}",
                ],
                [
                    'account_id' => $cashAccountId,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'narration' => "Salary payment - {$driverName}",
                ],
            ];

            VoucherDetailRepository::createBatch($pdo, (int) $voucher['id'], $details);

            // Post voucher
            AccountingVoucherRepository::post($pdo, (int) $voucher['id'], $userId);

            // Create transport mapping
            $st = $pdo->prepare(
                'INSERT INTO transport_voucher_mapping (voucher_id, transport_type, transport_id, mapping_details)
                 VALUES (?, ?, ?, ?)'
            );
            $st->execute([
                (int) $voucher['id'],
                'DRIVER_SALARY',
                (int) ($salaryData['salary_id'] ?? 0),
                json_encode($salaryData),
            ]);

            $pdo->commit();
            return $voucher;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Create customer account automatically
     */
    private static function createCustomerAccount(PDO $pdo, string $customerName): int
    {
        $st = $pdo->prepare('SELECT id FROM account_groups WHERE group_code = ? LIMIT 1');
        $st->execute(['SUNDRY_DEBTORS']);
        $groupId = $st->fetchColumn();
        
        if (!$groupId) {
            throw new RuntimeException('Sundry Debtors group not found in Chart of Accounts.');
        }

        $accountCode = 'CUST-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $customerName), 0, 8)) . '-' . time();

        $account = AccountRepository::create($pdo, [
            'account_code' => $accountCode,
            'account_name' => $customerName,
            'account_group_id' => (int) $groupId,
            'opening_balance' => 0,
            'opening_balance_type' => 'DEBIT',
            'is_active' => 1,
            'is_system' => 0,
        ]);

        return (int) $account['id'];
    }

    /**
     * Create supplier account automatically
     */
    private static function createSupplierAccount(PDO $pdo, string $supplierName): int
    {
        $st = $pdo->prepare('SELECT id FROM account_groups WHERE group_code = ? LIMIT 1');
        $st->execute(['SUNDRY_CREDITORS']);
        $groupId = $st->fetchColumn();
        
        if (!$groupId) {
            throw new RuntimeException('Sundry Creditors group not found in Chart of Accounts.');
        }

        $accountCode = 'SUPP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $supplierName), 0, 8)) . '-' . time();

        $account = AccountRepository::create($pdo, [
            'account_code' => $accountCode,
            'account_name' => $supplierName,
            'account_group_id' => (int) $groupId,
            'opening_balance' => 0,
            'opening_balance_type' => 'CREDIT',
            'is_active' => 1,
            'is_system' => 0,
        ]);

        return (int) $account['id'];
    }
}
