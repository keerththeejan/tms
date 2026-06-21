<?php

declare(strict_types=1);

/**
 * DataReset – delete all records from TMS data tables.
 * Tables are cleared in dependency order (child tables first) to satisfy foreign keys.
 * Use only for development/reset; requires admin role when called from the UI.
 */
class DataReset
{
    /**
     * Tables to clear, in order (children first). Tables that may not exist in older DBs
     * are included; failures for missing tables are ignored.
     */
    private static function getTablesInOrder(): array
    {
        return [
            'parcel_emails',
            'delivery_note_emails',
            'delivery_note_parcels',
            'delivery_route_assignments',
            'expense_payments',
            'employee_advance_payments',
            'payments',
            'parcel_items',
            'delivery_notes',
            'parcels',
            'expenses',
            'employee_advances',
            'employee_payroll',
            'salaries',
            'reminders',
            'routes',
            'employees',
            'suppliers',
            'customers',
            'users',
            'vehicles',
            'branches',
        ];
    }

    /**
     * Delete all rows from all known data tables in correct order.
     * Uses a transaction; rolls back on failure.
     *
     * @return array{success: bool, cleared: array<string>, errors: array<string>}
     */
    public static function deleteAllRecords(PDO $pdo): array
    {
        $cleared = [];
        $errors = [];
        $tables = self::getTablesInOrder();

        try {
            $pdo->beginTransaction();
            foreach ($tables as $table) {
                try {
                    $pdo->exec("DELETE FROM `" . preg_replace('/[^a-z0-9_]/', '', $table) . "`");
                    $cleared[] = $table;
                } catch (Throwable $e) {
                    // Table might not exist in this database version
                    $errors[] = $table . ': ' . $e->getMessage();
                }
            }
            $pdo->commit();
            return ['success' => true, 'cleared' => $cleared, 'errors' => $errors];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Transaction failed: ' . $e->getMessage();
            return ['success' => false, 'cleared' => $cleared, 'errors' => $errors];
        }
    }

    /**
     * Truncate all data tables (resets auto_increment). Disables foreign key checks
     * so order does not matter. Use for a full reset including IDs.
     *
     * @return array{success: bool, cleared: array<string>, errors: array<string>}
     */
    public static function truncateAllTables(PDO $pdo): array
    {
        $cleared = [];
        $errors = [];
        $tables = self::getTablesInOrder();

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                $safe = preg_replace('/[^a-z0-9_]/', '', $table);
                try {
                    $pdo->exec("TRUNCATE TABLE `{$safe}`");
                    $cleared[] = $table;
                } catch (Throwable $e) {
                    $errors[] = $table . ': ' . $e->getMessage();
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            return ['success' => true, 'cleared' => $cleared, 'errors' => $errors];
        } catch (Throwable $e) {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable $_) {
                // ignore
            }
            $errors[] = 'Truncate failed: ' . $e->getMessage();
            return ['success' => false, 'cleared' => $cleared, 'errors' => $errors];
        }
    }

    /**
     * Remove all customers and dependent parcel/billing records (keeps users, branches, vehicles, etc.).
     *
     * @return array{success: bool, cleared: array<string>, errors: array<string>, customers_deleted: int}
     */
    public static function deleteAllCustomerData(PDO $pdo): array
    {
        $cleared = [];
        $errors = [];
        $customersDeleted = 0;

        try {
            $pdo->beginTransaction();

            $steps = [
                'parcel_items' => 'DELETE pi FROM parcel_items pi INNER JOIN parcels p ON p.id = pi.parcel_id',
                'delivery_note_parcels' => 'DELETE dnp FROM delivery_note_parcels dnp INNER JOIN parcels p ON p.id = dnp.parcel_id',
                'payments' => 'DELETE pay FROM payments pay INNER JOIN delivery_notes dn ON dn.id = pay.delivery_note_id',
                'parcels' => 'DELETE FROM parcels',
                'delivery_notes' => 'DELETE FROM delivery_notes',
                'delivery_route_assignments' => 'DELETE FROM delivery_route_assignments',
                'invoices' => 'DELETE FROM invoices',
            ];

            foreach ($steps as $label => $sql) {
                try {
                    $pdo->exec($sql);
                    $cleared[] = $label;
                } catch (Throwable $e) {
                    $errors[] = $label . ': ' . $e->getMessage();
                }
            }

            try {
                $pdo->exec('UPDATE payment_vouchers SET customer_id = NULL WHERE customer_id IS NOT NULL');
                $cleared[] = 'payment_vouchers.customer_id';
            } catch (Throwable $e) {
                $errors[] = 'payment_vouchers: ' . $e->getMessage();
            }

            try {
                $ids = $pdo->query('SELECT id FROM customers')->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($ids as $cid) {
                    CashbookRepository::detachCashbookAccountForDeletedCustomer($pdo, (int)$cid);
                }
                $customersDeleted = count($ids);
            } catch (Throwable $e) {
                $errors[] = 'cashbook_accounts: ' . $e->getMessage();
            }

            try {
                $pdo->exec('DELETE FROM customers');
                $cleared[] = 'customers';
            } catch (Throwable $e) {
                $errors[] = 'customers: ' . $e->getMessage();
                throw $e;
            }

            $pdo->commit();

            return [
                'success' => true,
                'cleared' => $cleared,
                'errors' => $errors,
                'customers_deleted' => $customersDeleted,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Transaction failed: ' . $e->getMessage();

            return [
                'success' => false,
                'cleared' => $cleared,
                'errors' => $errors,
                'customers_deleted' => 0,
            ];
        }
    }
}
