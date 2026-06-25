<?php

declare(strict_types=1);

/**
 * Idempotent schema upgrades for the Expenses ERP module.
 */
class ExpenseSchemaRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        self::ensureCategoriesTable($pdo);
        self::ensureExpenseColumns($pdo);
        self::ensureExpensePaymentsTable($pdo);
        self::seedCategories($pdo);
        self::backfillExpenses($pdo);
    }

    private static function ensureCategoriesTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS expense_categories (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              code VARCHAR(40) NOT NULL,
              name VARCHAR(120) NOT NULL,
              account_id BIGINT UNSIGNED NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              is_system TINYINT(1) NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0,
              created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_expense_categories_code (code),
              KEY idx_expense_categories_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureExpensePaymentsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS expense_payments (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              expense_id BIGINT UNSIGNED NOT NULL,
              amount DECIMAL(12,2) NOT NULL,
              paid_at DATETIME NOT NULL,
              paid_by BIGINT UNSIGNED NULL,
              notes VARCHAR(255) NULL,
              created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY idx_exp_pay_expense (expense_id),
              KEY idx_exp_pay_paid_at (paid_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureExpenseColumns(PDO $pdo): void
    {
        $columns = [
            'expense_number' => 'VARCHAR(32) NULL AFTER id',
            'category_id' => 'INT UNSIGNED NULL AFTER expense_type',
            'supplier_id' => 'BIGINT UNSIGNED NULL AFTER category_id',
            'account_id' => 'BIGINT UNSIGNED NULL AFTER supplier_id',
            'reference_number' => 'VARCHAR(64) NULL AFTER account_id',
            'description' => 'TEXT NULL AFTER reference_number',
            'tax_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER amount',
            'discount_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER tax_amount',
            'total_amount' => 'DECIMAL(12,2) NULL AFTER discount_amount',
            'payment_method' => "VARCHAR(20) NOT NULL DEFAULT 'cash' AFTER total_amount",
            'payment_account_id' => 'BIGINT UNSIGNED NULL AFTER payment_method',
            'balance_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER paid_amount',
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER balance_amount",
            'voucher_id' => 'BIGINT UNSIGNED NULL AFTER status',
            'created_by' => 'BIGINT UNSIGNED NULL AFTER voucher_id',
            'approved_at' => 'DATETIME NULL AFTER approved_by',
            'rejected_by' => 'BIGINT UNSIGNED NULL AFTER approved_at',
            'rejected_at' => 'DATETIME NULL AFTER rejected_by',
            'attachment_path' => 'VARCHAR(255) NULL AFTER notes',
        ];

        foreach ($columns as $name => $definition) {
            if (!self::columnExists($pdo, 'expenses', $name)) {
                try {
                    $pdo->exec("ALTER TABLE expenses ADD COLUMN {$name} {$definition}");
                } catch (Throwable $e) {
                    /* ignore if race or unsupported */
                }
            }
        }

        self::ensureIndex($pdo, 'expenses', 'uq_expenses_number', 'expense_number', true);
        self::ensureIndex($pdo, 'expenses', 'idx_expenses_category', 'category_id', false);
        self::ensureIndex($pdo, 'expenses', 'idx_expenses_supplier', 'supplier_id', false);
        self::ensureIndex($pdo, 'expenses', 'idx_expenses_status', 'status', false);
        self::ensureIndex($pdo, 'expenses', 'idx_expenses_payment_method', 'payment_method', false);
        self::ensureIndex($pdo, 'expenses', 'idx_expenses_voucher', 'voucher_id', false);
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$table, $column]);

        return (int) $st->fetchColumn() > 0;
    }

    private static function ensureIndex(PDO $pdo, string $table, string $indexName, string $column, bool $unique): void
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $st->execute([$table, $indexName]);
        if ((int) $st->fetchColumn() > 0) {
            return;
        }
        try {
            $type = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $pdo->exec("CREATE {$type} {$indexName} ON {$table} ({$column})");
        } catch (Throwable $e) {
            /* ignore */
        }
    }

    private static function seedCategories(PDO $pdo): void
    {
        $defaults = [
            ['fuel', 'Fuel', 10],
            ['transport', 'Transport', 20],
            ['office', 'Office Expenses', 30],
            ['electricity', 'Electricity', 40],
            ['water', 'Water', 50],
            ['internet', 'Internet', 60],
            ['telephone', 'Telephone', 70],
            ['vehicle_repairs', 'Vehicle Repairs', 80],
            ['vehicle_insurance', 'Vehicle Insurance', 90],
            ['tyres', 'Tyres', 100],
            ['staff_salary', 'Staff Salary', 110],
            ['meals', 'Meals', 120],
            ['accommodation', 'Accommodation', 130],
            ['maintenance', 'Maintenance', 140],
            ['marketing', 'Marketing', 150],
            ['printing', 'Printing', 160],
            ['stationery', 'Stationery', 170],
            ['cleaning', 'Cleaning', 180],
            ['miscellaneous', 'Miscellaneous', 190],
        ];

        $st = $pdo->prepare(
            'INSERT IGNORE INTO expense_categories (code, name, is_system, sort_order) VALUES (?, ?, 1, ?)'
        );
        foreach ($defaults as [$code, $name, $sort]) {
            $st->execute([$code, $name, $sort]);
        }

        // Map legacy expense_type values
        $legacyMap = [
            'fuel' => 'fuel',
            'vehicle_maintenance' => 'vehicle_repairs',
            'office' => 'office',
            'utilities' => 'electricity',
            'other' => 'miscellaneous',
        ];
        $upd = $pdo->prepare(
            'UPDATE expense_categories SET name = ? WHERE code = ? AND is_system = 1'
        );
        foreach ($legacyMap as $legacy => $code) {
            $upd->execute([ucwords(str_replace('_', ' ', $legacy)), $code]);
        }
    }

    private static function backfillExpenses(PDO $pdo): void
    {
        try {
            $pdo->exec('UPDATE expenses SET total_amount = amount WHERE total_amount IS NULL');
            $pdo->exec(
                "UPDATE expenses SET payment_method = CASE
                   WHEN payment_mode = 'credit' OR is_credit = 1 THEN 'credit'
                   ELSE COALESCE(NULLIF(payment_method, ''), 'cash')
                 END
                 WHERE payment_method = 'cash'"
            );
            $pdo->exec(
                "UPDATE expenses SET status = 'approved'
                 WHERE approved_by IS NOT NULL AND (status IS NULL OR status = '' OR status = 'pending')"
            );
            $pdo->exec(
                'UPDATE expenses e
                 INNER JOIN expense_categories c ON c.code = e.expense_type
                 SET e.category_id = c.id
                 WHERE e.category_id IS NULL AND e.expense_type IS NOT NULL AND e.expense_type <> \'\''
            );
            $pdo->exec(
                "UPDATE expenses SET expense_number = CONCAT('EXP-LEG-', LPAD(id, 6, '0'))
                 WHERE expense_number IS NULL OR expense_number = ''"
            );
        } catch (Throwable $e) {
            /* optional backfill */
        }
    }
}
