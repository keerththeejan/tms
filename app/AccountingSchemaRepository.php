<?php

declare(strict_types=1);

/**
 * Idempotent accounting schema installer.
 * Creates missing tables/columns without breaking the existing payment-voucher vouchers table.
 */
class AccountingSchemaRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        self::createAccountGroupsTable($pdo);
        self::ensureAccountGroupColumns($pdo);
        self::seedAccountGroups($pdo);
        self::createAccountsTable($pdo);
        self::seedAccounts($pdo);
        self::ensureVoucherColumns($pdo);
        self::createVoucherSeriesTable($pdo);
        self::seedVoucherSeries($pdo);
        self::createVoucherDetailsTable($pdo);
        self::createLedgerEntriesTable($pdo);
        self::createCostCentersTable($pdo);
        self::createAuditLogTable($pdo);
        self::createTransportMappingTable($pdo);
        self::ensureCustomerLedgerTable($pdo);
        AccountingPaymentModeSettingsRepository::ensureSchema($pdo);
    }

    public static function ensureCustomerLedgerTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'customer_ledger')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE customer_ledger (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id bigint unsigned NOT NULL,
  account_id bigint unsigned NOT NULL,
  ledger_code varchar(30) NOT NULL,
  ledger_type varchar(50) NOT NULL DEFAULT 'Accounts Receivable',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customer_ledger_customer (customer_id),
  UNIQUE KEY uq_customer_ledger_account (account_id),
  UNIQUE KEY uq_customer_ledger_code (ledger_code),
  KEY idx_customer_ledger_code (ledger_code),
  CONSTRAINT fk_customer_ledger_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_customer_ledger_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $st->execute([$table]);

        return (int) $st->fetchColumn() > 0;
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $st->execute([$table, $column]);

        return (int) $st->fetchColumn() > 0;
    }

    private static function exec(PDO $pdo, string $sql): void
    {
        $pdo->exec($sql);
    }

    private static function createAccountGroupsTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'account_groups')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE account_groups (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_code varchar(40) NOT NULL UNIQUE,
  group_name varchar(100) NOT NULL,
  parent_id bigint unsigned NULL,
  group_type enum('ASSETS', 'LIABILITIES', 'CAPITAL', 'INCOME', 'EXPENSES') NOT NULL,
  nature enum('DEBIT', 'CREDIT') NOT NULL,
  is_primary tinyint(1) NOT NULL DEFAULT 0,
  is_system tinyint(1) NOT NULL DEFAULT 0,
  sort_order int NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL,
  KEY idx_group_code (group_code),
  KEY idx_parent_id (parent_id),
  KEY idx_group_type (group_type),
  KEY idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function seedAccountGroups(PDO $pdo): void
    {
        self::seedAccountGroupsIfEmpty($pdo);
    }

    /** @return int Number of groups seeded (0 if groups already exist). */
    public static function seedAccountGroupsIfEmpty(PDO $pdo): int
    {
        if (!self::tableExists($pdo, 'account_groups')) {
            return 0;
        }

        $count = (int) $pdo->query('SELECT COUNT(*) FROM account_groups WHERE deleted_at IS NULL')->fetchColumn();
        if ($count > 0) {
            return self::syncStandardAccountGroups($pdo);
        }

        $deletedCount = (int) $pdo->query('SELECT COUNT(*) FROM account_groups')->fetchColumn();
        if ($deletedCount > 0) {
            return self::syncStandardAccountGroups($pdo);
        }

        return self::insertStandardAccountGroups($pdo);
    }

    /**
     * Standard TMS account group hierarchy.
     * Display names match the business chart; group_code values preserve system integrations.
     *
     * @return list<array{0:string,1:string,2:?string,3:string,4:string,5:int,6:int}>
     */
    public static function standardAccountGroupDefinitions(): array
    {
        return [
            ['ASSETS', 'Assets', null, 'ASSETS', 'DEBIT', 1, 1],
            ['LIABILITIES', 'Liabilities', null, 'LIABILITIES', 'CREDIT', 1, 2],
            ['CAPITAL', 'Equity', null, 'CAPITAL', 'CREDIT', 1, 3],
            ['INCOME', 'Income', null, 'INCOME', 'CREDIT', 1, 4],
            ['EXPENSES', 'Expenses', null, 'EXPENSES', 'DEBIT', 1, 5],
            ['CURRENT_ASSETS', 'Current Assets', 'ASSETS', 'ASSETS', 'DEBIT', 0, 10],
            ['FIXED_ASSETS', 'Fixed Assets', 'ASSETS', 'ASSETS', 'DEBIT', 0, 11],
            ['CURRENT_LIABILITIES', 'Current Liabilities', 'LIABILITIES', 'LIABILITIES', 'CREDIT', 0, 20],
            ['LONG_TERM_LIABILITIES', 'Long-Term Liabilities', 'LIABILITIES', 'LIABILITIES', 'CREDIT', 0, 21],
            ['SALES_INCOME', 'Sales Revenue', 'INCOME', 'INCOME', 'CREDIT', 0, 30],
            ['SERVICE_INCOME', 'Service Revenue', 'INCOME', 'INCOME', 'CREDIT', 0, 31],
            ['ADMIN_EXPENSES', 'Administrative Expenses', 'EXPENSES', 'EXPENSES', 'DEBIT', 0, 40],
            ['TRANSPORT_EXPENSES', 'Transport Expenses', 'EXPENSES', 'EXPENSES', 'DEBIT', 0, 41],
            ['SALARY_EXPENSES', 'Salary Expenses', 'EXPENSES', 'EXPENSES', 'DEBIT', 0, 42],
            ['CASH', 'Cash', 'CURRENT_ASSETS', 'ASSETS', 'DEBIT', 0, 100],
            ['BANK', 'Bank', 'CURRENT_ASSETS', 'ASSETS', 'DEBIT', 0, 101],
            ['SUNDRY_DEBTORS', 'Sundry Debtors', 'CURRENT_ASSETS', 'ASSETS', 'DEBIT', 0, 102],
            ['SUNDRY_CREDITORS', 'Sundry Creditors', 'CURRENT_LIABILITIES', 'LIABILITIES', 'CREDIT', 0, 200],
            ['FUEL_EXPENSES', 'Fuel Expenses', 'TRANSPORT_EXPENSES', 'EXPENSES', 'DEBIT', 0, 300],
            ['VEHICLE_EXPENSES', 'Vehicle Expenses', 'TRANSPORT_EXPENSES', 'EXPENSES', 'DEBIT', 0, 301],
            ['DRIVER_SALARY', 'Driver Salary', 'SALARY_EXPENSES', 'EXPENSES', 'DEBIT', 0, 302],
        ];
    }

    /** @return int Number of groups inserted. */
    public static function insertStandardAccountGroups(PDO $pdo): int
    {
        self::repairTruncatedAccountGroupCodes($pdo);

        $created = 0;
        $ins = $pdo->prepare(
            'INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
        );

        foreach (self::standardAccountGroupDefinitions() as [$code, $name, $parentCode, $type, $nature, $isPrimary, $sort]) {
            if (self::findGroupIdByCode($pdo, $code) !== null) {
                continue;
            }
            $parentId = null;
            if ($parentCode !== null) {
                $parentId = self::findGroupIdByCode($pdo, $parentCode);
                if ($parentId === null) {
                    continue;
                }
            }
            $ins->execute([$code, $name, $parentId, $type, $nature, $isPrimary, $sort]);
            $created++;
        }

        return $created;
    }

    /** Insert missing standard groups and refresh display names. @return int Number of groups created. */
    public static function syncStandardAccountGroups(PDO $pdo): int
    {
        if (!self::tableExists($pdo, 'account_groups')) {
            return 0;
        }

        self::repairTruncatedAccountGroupCodes($pdo);

        $created = 0;
        $insert = $pdo->prepare(
            'INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $update = $pdo->prepare(
            'UPDATE account_groups SET group_code = ?, group_name = ?, parent_id = ?, group_type = ?, nature = ?, is_primary = ?, sort_order = ?, deleted_at = NULL
             WHERE id = ?'
        );

        foreach (self::standardAccountGroupDefinitions() as [$code, $name, $parentCode, $type, $nature, $isPrimary, $sort]) {
            $parentId = null;
            if ($parentCode !== null) {
                $parentId = self::findGroupIdByCode($pdo, $parentCode);
            }

            $existingId = self::findGroupIdByCode($pdo, $code);

            if ($existingId) {
                try {
                    $update->execute([$code, $name, $parentId, $type, $nature, $isPrimary, $sort, $existingId]);
                } catch (Throwable $e) {
                    /* keep existing row if update fails */
                }
                continue;
            }

            try {
                $insert->execute([$code, $name, $parentId, $type, $nature, $isPrimary, $sort]);
                $created++;
            } catch (Throwable $e) {
                $retryId = self::findGroupIdByCode($pdo, $code);
                if ($retryId) {
                    try {
                        $update->execute([$code, $name, $parentId, $type, $nature, $isPrimary, $sort, $retryId]);
                    } catch (Throwable $ignored) {
                    }
                }
            }
        }

        return $created;
    }

    private static function findGroupIdByCode(PDO $pdo, string $code): ?int
    {
        foreach (self::groupCodeLookupVariants($code) as $lookupCode) {
            $st = $pdo->prepare('SELECT id FROM account_groups WHERE group_code = ? LIMIT 1');
            $st->execute([$lookupCode]);
            $id = $st->fetchColumn();
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function groupCodeLookupVariants(string $code): array
    {
        $aliases = [
            'LONG_TERM_LIABILITIES' => ['LONG_TERM_LIABILITIES', 'LONG_TERM_LIABILITIE'],
        ];

        return $aliases[$code] ?? [$code];
    }

    private static function repairTruncatedAccountGroupCodes(PDO $pdo): void
    {
        try {
            self::exec($pdo, 'ALTER TABLE account_groups MODIFY group_code varchar(40) NOT NULL');
        } catch (Throwable $e) {
            /* column may already be wide enough */
        }

        $st = $pdo->prepare(
            'SELECT id FROM account_groups WHERE group_code = ? AND NOT EXISTS (
                SELECT 1 FROM account_groups g2 WHERE g2.group_code = ?
             ) LIMIT 1'
        );
        $st->execute(['LONG_TERM_LIABILITIE', 'LONG_TERM_LIABILITIES']);
        $id = $st->fetchColumn();
        if ($id) {
            $pdo->prepare('UPDATE account_groups SET group_code = ?, group_name = ? WHERE id = ?')
                ->execute(['LONG_TERM_LIABILITIES', 'Long-Term Liabilities', (int) $id]);
        }
    }

    private static function ensureAccountGroupColumns(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'account_groups')) {
            return;
        }
        if (!self::columnExists($pdo, 'account_groups', 'deleted_at')) {
            self::exec($pdo, 'ALTER TABLE account_groups ADD COLUMN deleted_at timestamp NULL AFTER updated_at');
        }
        if (!self::columnExists($pdo, 'account_groups', 'description')) {
            self::exec($pdo, 'ALTER TABLE account_groups ADD COLUMN description varchar(500) NULL AFTER sort_order');
        }
        if (!self::columnExists($pdo, 'account_groups', 'is_active')) {
            self::exec($pdo, 'ALTER TABLE account_groups ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1 AFTER description');
        }
        self::repairTruncatedAccountGroupCodes($pdo);
    }

    private static function createAccountsTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'accounts')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE accounts (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  account_code varchar(30) NOT NULL UNIQUE,
  account_name varchar(150) NOT NULL,
  account_group_id bigint unsigned NOT NULL,
  opening_balance decimal(15,2) NOT NULL DEFAULT 0.00,
  opening_balance_type enum('DEBIT', 'CREDIT') NOT NULL DEFAULT 'DEBIT',
  current_balance decimal(15,2) NOT NULL DEFAULT 0.00,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  is_system tinyint(1) NOT NULL DEFAULT 0,
  branch_id bigint unsigned NULL,
  created_by bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL,
  KEY idx_account_code (account_code),
  KEY idx_account_name (account_name),
  KEY idx_account_group_id (account_group_id),
  KEY idx_branch_id (branch_id),
  KEY idx_is_active (is_active),
  CONSTRAINT fk_accounts_group FOREIGN KEY (account_group_id) REFERENCES account_groups(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function seedAccounts(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'accounts')) {
            return;
        }

        $count = (int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            ['CASH_MAIN', 'Main Cash Account', 'CASH', 0, 'DEBIT'],
            ['CASH_PETTY', 'Petty Cash', 'CASH', 0, 'DEBIT'],
            ['BANK_MAIN', 'Main Bank Account', 'BANK', 0, 'DEBIT'],
            ['BANK_SAVINGS', 'Savings Bank Account', 'BANK', 0, 'DEBIT'],
            ['CAPITAL_OWNER', 'Owner Capital', 'CAPITAL', 0, 'CREDIT'],
            ['FUEL_DIESEL', 'Diesel Fuel Expense', 'FUEL_EXPENSES', 0, 'DEBIT'],
            ['FUEL_PETROL', 'Petrol Fuel Expense', 'FUEL_EXPENSES', 0, 'DEBIT'],
            ['VEH_MAINTENANCE', 'Vehicle Maintenance', 'VEHICLE_EXPENSES', 0, 'DEBIT'],
            ['VEH_REPAIRS', 'Vehicle Repairs', 'VEHICLE_EXPENSES', 0, 'DEBIT'],
            ['DRIVER_SALARY_WAGES', 'Driver Salary & Wages', 'DRIVER_SALARY', 0, 'DEBIT'],
            ['SALES_FREIGHT', 'Freight Sales', 'SALES_INCOME', 0, 'CREDIT'],
            ['SALES_LOADING', 'Loading Charges', 'SALES_INCOME', 0, 'CREDIT'],
        ];

        $ins = $pdo->prepare(
            'INSERT INTO accounts (account_code, account_name, account_group_id, opening_balance, opening_balance_type, is_system)
             SELECT ?, ?, g.id, ?, ?, 1 FROM account_groups g WHERE g.group_code = ? LIMIT 1'
        );
        foreach ($rows as [$code, $name, $groupCode, $ob, $obType]) {
            $ins->execute([$code, $name, $ob, $obType, $groupCode]);
        }
    }

    private static function ensureVoucherColumns(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'vouchers')) {
            self::exec($pdo, <<<'SQL'
CREATE TABLE vouchers (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_number varchar(50) NOT NULL UNIQUE,
  voucher_type enum('PAYMENT', 'RECEIPT', 'JOURNAL', 'CONTRA', 'TRANSFER') NOT NULL,
  series_id bigint unsigned NULL,
  voucher_date date NOT NULL,
  fiscal_year varchar(10) NOT NULL,
  reference_number varchar(50) NULL,
  payment_mode enum('CASH', 'BANK', 'CHEQUE', 'ONLINE', 'OTHER') NOT NULL DEFAULT 'CASH',
  cheque_number varchar(50) NULL,
  cheque_date date NULL,
  bank_account_id bigint unsigned NULL,
  narration text NULL,
  total_debit decimal(15,2) NOT NULL DEFAULT 0.00,
  total_credit decimal(15,2) NOT NULL DEFAULT 0.00,
  status enum('DRAFT', 'POSTED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
  posted_at timestamp NULL,
  posted_by bigint unsigned NULL,
  cancelled_at timestamp NULL,
  cancelled_by bigint unsigned NULL,
  cancellation_reason text NULL,
  branch_id bigint unsigned NULL,
  created_by bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL,
  KEY idx_voucher_number (voucher_number),
  KEY idx_voucher_type (voucher_type),
  KEY idx_voucher_date (voucher_date),
  KEY idx_fiscal_year (fiscal_year),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
            return;
        }

        $columns = [
            'series_id' => 'bigint unsigned NULL',
            'bank_account_id' => 'bigint unsigned NULL',
            'branch_id' => 'bigint unsigned NULL',
            'cancelled_at' => 'timestamp NULL',
            'cancelled_by' => 'bigint unsigned NULL',
            'cancellation_reason' => 'text NULL',
            'is_locked' => 'tinyint(1) NOT NULL DEFAULT 0',
        ];
        foreach ($columns as $name => $definition) {
            if (!self::columnExists($pdo, 'vouchers', $name)) {
                self::exec($pdo, "ALTER TABLE vouchers ADD COLUMN {$name} {$definition}");
            }
        }
    }

    private static function createVoucherSeriesTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'voucher_series')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE voucher_series (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  series_name varchar(50) NOT NULL UNIQUE,
  voucher_type enum('PAYMENT', 'RECEIPT', 'JOURNAL', 'CONTRA', 'TRANSFER') NOT NULL,
  prefix varchar(20) NOT NULL,
  starting_number int NOT NULL DEFAULT 1,
  current_number int NOT NULL DEFAULT 0,
  reset_type enum('NONE', 'YEARLY', 'MONTHLY') NOT NULL DEFAULT 'YEARLY',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  branch_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_series_name (series_name),
  KEY idx_voucher_type (voucher_type),
  KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function seedVoucherSeries(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'voucher_series')) {
            return;
        }

        $count = (int) $pdo->query('SELECT COUNT(*) FROM voucher_series')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            ['PAYMENT_SERIES', 'PAYMENT', 'PY-', 1, 0],
            ['RECEIPT_SERIES', 'RECEIPT', 'RC-', 1, 0],
            ['JOURNAL_SERIES', 'JOURNAL', 'JR-', 1, 0],
            ['CONTRA_SERIES', 'CONTRA', 'CT-', 1, 0],
            ['TRANSFER_SERIES', 'TRANSFER', 'TR-', 1, 0],
        ];
        $ins = $pdo->prepare(
            'INSERT INTO voucher_series (series_name, voucher_type, prefix, starting_number, current_number, reset_type, is_active)
             VALUES (?, ?, ?, ?, ?, "YEARLY", 1)'
        );
        foreach ($rows as $row) {
            $ins->execute($row);
        }
    }

    private static function createVoucherDetailsTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'voucher_details')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE voucher_details (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  line_number int NOT NULL,
  account_id bigint unsigned NOT NULL,
  debit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  credit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  narration varchar(255) NULL,
  cost_center_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_account_id (account_id),
  KEY idx_line_number (line_number),
  CONSTRAINT fk_vd_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
  CONSTRAINT fk_vd_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function createLedgerEntriesTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'ledger_entries')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE ledger_entries (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  voucher_detail_id bigint unsigned NULL,
  account_id bigint unsigned NOT NULL,
  entry_date date NOT NULL,
  voucher_type enum('PAYMENT', 'RECEIPT', 'JOURNAL', 'CONTRA', 'TRANSFER') NOT NULL,
  voucher_number varchar(50) NOT NULL,
  debit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  credit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  balance_type enum('DEBIT', 'CREDIT') NOT NULL,
  running_balance decimal(15,2) NOT NULL DEFAULT 0.00,
  narration text NULL,
  reference_id bigint unsigned NULL,
  reference_type varchar(50) NULL,
  branch_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_account_id (account_id),
  KEY idx_entry_date (entry_date),
  KEY idx_voucher_number (voucher_number),
  KEY idx_voucher_type (voucher_type),
  KEY idx_branch_id (branch_id),
  KEY idx_reference (reference_id, reference_type),
  CONSTRAINT fk_le_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
  CONSTRAINT fk_le_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function createCostCentersTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'cost_centers')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE cost_centers (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cost_center_code varchar(20) NOT NULL UNIQUE,
  cost_center_name varchar(100) NOT NULL,
  parent_id bigint unsigned NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  branch_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cost_center_code (cost_center_code),
  KEY idx_parent_id (parent_id),
  KEY idx_branch_id (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function createAuditLogTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'accounting_audit_log')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE accounting_audit_log (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity_type varchar(50) NOT NULL,
  entity_id bigint unsigned NOT NULL,
  action varchar(50) NOT NULL,
  old_values json NULL,
  new_values json NULL,
  user_id bigint unsigned NULL,
  ip_address varchar(45) NULL,
  user_agent varchar(255) NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id),
  KEY idx_action (action),
  KEY idx_user_id (user_id),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private static function createTransportMappingTable(PDO $pdo): void
    {
        if (self::tableExists($pdo, 'transport_voucher_mapping')) {
            return;
        }

        self::exec($pdo, <<<'SQL'
CREATE TABLE transport_voucher_mapping (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  transport_type enum('FUEL', 'VEHICLE_EXPENSE', 'CUSTOMER_INVOICE', 'SUPPLIER_PAYMENT', 'DRIVER_SALARY') NOT NULL,
  transport_id bigint unsigned NOT NULL,
  mapping_details json NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_transport (transport_type, transport_id),
  CONSTRAINT fk_tvm_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
}
