<?php

declare(strict_types=1);

/**
 * DataReset – full database reset and scoped data purge utilities.
 * Automatically discovers application tables and truncates all except preserved system tables.
 */
class DataReset
{
    /** Tables that are never truncated during a full reset. */
    public static function getPreservedTables(): array
    {
        return [
            'migrations',
            'schema_migrations',
            'system_config',
            'app_settings',
            'license',
            'licenses',
            'countries',
            'currencies',
            'languages',
            'permissions',
        ];
    }

    /**
     * Master chart-of-accounts tables may be re-seeded after reset; they are not transaction tables.
     *
     * @return list<string>
     */
    public static function getAccountingMasterTables(): array
    {
        return [
            'account_groups',
            'accounts',
            'voucher_series',
            'cost_centers',
            'accounting_payment_mode_accounts',
        ];
    }

    /**
     * Detect accounting/finance tables by naming convention.
     */
    public static function isAccountingRelatedTable(string $table): bool
    {
        $t = strtolower($table);
        $patterns = [
            '/^voucher/',
            '/^journal/',
            '/ledger/',
            '/^account_/',
            '/^accounts$/',
            '/^account_groups$/',
            '/cashbook/',
            '/cash_book/',
            '/bankbook/',
            '/bank_book/',
            '/^receipt/',
            '/^payment/',
            '/^expense/',
            '/^income/',
            '/^transfer/',
            '/^contra/',
            '/trial_balance/',
            '/balance_/',
            '/_cache$/',
            '/^financial_/',
            '/general_ledger/',
            '/day_book/',
            '/reconciliation/',
            '/cheque_register/',
            '/invoice/',
            '/customer_ledger/',
            '/cost_center/',
            '/transport_voucher/',
            '/accounting_/',
            '/transaction_audit/',
            '/^cashbook$/',
            '/^bankbook$/',
            '/^ledger$/',
            '/monthly_summary/',
            '/yearly_summary/',
            '/dashboard_cache/',
            '/account_summary/',
            '/account_closing/',
            '/profit_loss/',
            '/balance_sheet/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $t) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Transaction tables that must be completely empty after reset (no rows allowed).
     *
     * @return list<string>
     */
    public static function getAccountingTransactionTables(): array
    {
        return [
            'vouchers', 'voucher_items', 'voucher_entries', 'voucher_details',
            'voucher_approvals', 'voucher_drafts',
            'journal_entries', 'journal_details',
            'ledger', 'ledger_entries', 'general_ledger',
            'account_transactions', 'account_balances', 'opening_balances', 'account_opening_balances',
            'cashbook_transactions', 'cashbook_transfers', 'cashbook_audit_logs',
            'cash_book', 'bank_book', 'bankbook',
            'receipts', 'receipt_items', 'payments', 'payment_items',
            'expenses', 'expense_items', 'expense_payments',
            'income', 'income_items',
            'transfers', 'transfer_items', 'transfer_vouchers', 'contra_entries', 'contra_vouchers',
            'day_book', 'trial_balance', 'trial_balance_cache', 'balance_cache',
            'balance_sheet_cache', 'profit_loss_cache', 'financial_reports',
            'account_summary', 'account_closing', 'accounting_dashboard', 'accounting_cache',
            'dashboard_cache', 'monthly_summary', 'yearly_summary',
            'reconciliation', 'cheque_register', 'financial_year_transactions',
            'accounting_audit_log', 'transaction_audit_logs', 'transport_voucher_mapping',
            'employee_payments', 'customer_ledger', 'invoices', 'invoice_day_sequences',
            'expense_categories', 'cashbook_accounts',
            'ledger_accounts',
            'parcel_activity_log', 'parcel_emails', 'parcel_items', 'parcels',
            'delivery_notes', 'delivery_note_parcels', 'delivery_note_emails',
            'reminders', 'employee_advances', 'employee_advance_payments', 'employee_payroll',
            'salaries', 'employee_documents', 'employee_payments',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function getAccountingTables(): array
    {
        $map = [];
        foreach (self::getAccountingTransactionTables() as $table) {
            $map[$table] = true;
        }
        return $map;
    }

    /**
     * @return list<string>
     */
    public static function discoverTables(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME"
        );
        $tables = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        return array_values(array_map('strval', $tables ?: []));
    }

    /**
     * @return list<string>
     */
    public static function discoverAccountingTables(PDO $pdo): array
    {
        $tables = [];
        foreach (self::discoverTables($pdo) as $table) {
            if (self::isAccountingRelatedTable($table)) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    /**
     * @return list<string>
     */
    public static function getTablesToReset(PDO $pdo): array
    {
        $preserved = array_fill_keys(array_map('strtolower', self::getPreservedTables()), true);
        $tables = [];
        foreach (self::discoverTables($pdo) as $table) {
            if (!isset($preserved[strtolower($table)])) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    /**
     * @return list<string>
     */
    public static function getTransactionTablesToVerify(PDO $pdo): array
    {
        $masters = array_fill_keys(array_map('strtolower', self::getAccountingMasterTables()), true);
        $verify = [];

        foreach (self::discoverTables($pdo) as $table) {
            $lower = strtolower($table);
            if (isset($masters[$lower])) {
                continue;
            }
            if (self::isAccountingRelatedTable($table) || in_array($lower, array_map('strtolower', self::getAccountingTransactionTables()), true)) {
                $verify[] = $table;
            }
        }

        foreach (self::getTablesToReset($pdo) as $table) {
            if (!in_array($table, $verify, true) && !isset($masters[strtolower($table)])) {
                $verify[] = $table;
            }
        }

        sort($verify);
        return array_values(array_unique($verify));
    }

    /**
     * @param callable(string): void|null $onPhase
     * @return array{
     *   success: bool,
     *   cleared: list<string>,
     *   errors: list<string>,
     *   verification_failures: array<string, int>,
     *   accounting_failures: array<string, int>,
     *   tables_found: int,
     *   phases: list<string>
     * }
     */
    public static function performFullDatabaseReset(PDO $pdo, ?callable $onPhase = null): array
    {
        $phase = static function (string $label) use ($onPhase): void {
            if ($onPhase !== null) {
                $onPhase($label);
            }
        };

        $cleared = [];
        $errors = [];
        $phases = [];
        $verificationFailures = [];
        $accountingFailures = [];

        $recordPhase = static function (string $label) use (&$phases, $phase): void {
            $phases[] = $label;
            $phase($label);
        };

        try {
            $recordPhase('Preparing...');

            $tablesToReset = self::getTablesToReset($pdo);
            $user = Auth::user();
            self::logResetActivity($pdo, [
                'action' => 'full_database_reset',
                'user_id' => (int) ($user['id'] ?? 0),
                'username' => (string) ($user['username'] ?? 'unknown'),
                'tables' => $tablesToReset,
                'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);

            $recordPhase('Deleting Records...');
            self::truncateTables($pdo, $tablesToReset, $cleared, $errors);

            $recordPhase('Resetting Accounting...');
            $accountingTables = array_values(array_unique(array_merge(
                self::discoverAccountingTables($pdo),
                self::getAccountingTransactionTables()
            )));
            self::truncateTables($pdo, $accountingTables, $cleared, $errors);
            self::resetAccountingBalances($pdo, $errors);

            $recordPhase('Resetting Auto Increment...');
            self::forceEmptyRemainingTables($pdo, self::getTransactionTablesToVerify($pdo), $verificationFailures, $errors);

            foreach (self::getTransactionTablesToVerify($pdo) as $table) {
                $safe = self::safeTableName($table);
                if ($safe === '') {
                    continue;
                }
                try {
                    $count = self::tableRowCount($pdo, $safe);
                    if ($count > 0) {
                        $verificationFailures[$table] = $count;
                    }
                } catch (Throwable $e) {
                    $errors[] = 'verify:' . $table . ': ' . $e->getMessage();
                }
            }

            foreach (self::getAccountingTransactionTables() as $table) {
                $safe = self::safeTableName($table);
                if ($safe === '') {
                    continue;
                }
                try {
                    if (!self::tableExists($pdo, $safe)) {
                        continue;
                    }
                    $count = self::tableRowCount($pdo, $safe);
                    if ($count > 0) {
                        $accountingFailures[$table] = $count;
                    }
                } catch (Throwable $e) {
                    $errors[] = 'accounting_verify:' . $table . ': ' . $e->getMessage();
                }
            }

            $recordPhase('Clearing Cache...');
            self::clearApplicationCache();
            self::markDatabaseReset();

            $success = empty($verificationFailures) && empty($accountingFailures) && empty($errors);

            return [
                'success' => $success,
                'cleared' => array_values(array_unique($cleared)),
                'errors' => $errors,
                'verification_failures' => $verificationFailures,
                'accounting_failures' => $accountingFailures,
                'tables_found' => count($tablesToReset),
                'phases' => $phases,
            ];
        } catch (Throwable $e) {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable $_) {
                // ignore
            }
            $errors[] = 'Reset failed: ' . $e->getMessage();

            return [
                'success' => false,
                'cleared' => $cleared,
                'errors' => $errors,
                'verification_failures' => $verificationFailures,
                'accounting_failures' => $accountingFailures,
                'tables_found' => 0,
                'phases' => $phases,
            ];
        }
    }

    /**
     * @param list<string> $tables
     * @param list<string> $cleared
     * @param list<string> $errors
     */
    private static function truncateTables(PDO $pdo, array $tables, array &$cleared, array &$errors): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_unique($tables) as $table) {
            $safe = self::safeTableName($table);
            if ($safe === '' || !self::tableExists($pdo, $safe)) {
                continue;
            }
            try {
                $pdo->exec("TRUNCATE TABLE `{$safe}`");
                $cleared[] = $table;
            } catch (Throwable $e) {
                try {
                    $pdo->exec("DELETE FROM `{$safe}`");
                    try {
                        $pdo->exec("ALTER TABLE `{$safe}` AUTO_INCREMENT = 1");
                    } catch (Throwable $_) {
                        // ignore if no auto_increment
                    }
                    $cleared[] = $table;
                } catch (Throwable $e2) {
                    $errors[] = $table . ': ' . $e2->getMessage();
                }
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @param list<string> $tables
     * @param array<string, int> $failures
     * @param list<string> $errors
     */
    private static function forceEmptyRemainingTables(PDO $pdo, array $tables, array &$failures, array &$errors): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_unique($tables) as $table) {
            $safe = self::safeTableName($table);
            if ($safe === '' || !self::tableExists($pdo, $safe)) {
                continue;
            }
            try {
                $count = self::tableRowCount($pdo, $safe);
                if ($count <= 0) {
                    continue;
                }
                $pdo->exec("DELETE FROM `{$safe}`");
                try {
                    $pdo->exec("ALTER TABLE `{$safe}` AUTO_INCREMENT = 1");
                } catch (Throwable $_) {
                    // ignore
                }
                $remaining = self::tableRowCount($pdo, $safe);
                if ($remaining > 0) {
                    $failures[$table] = $remaining;
                }
            } catch (Throwable $e) {
                $errors[] = 'force_empty:' . $table . ': ' . $e->getMessage();
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** @param list<string> $errors */
    private static function resetAccountingBalances(PDO $pdo, array &$errors): void
    {
        if (!self::tableExists($pdo, 'accounts')) {
            return;
        }

        try {
            $pdo->exec(
                "UPDATE accounts
                 SET opening_balance = 0.00,
                     current_balance = 0.00,
                     opening_balance_type = 'DEBIT'
                 WHERE opening_balance <> 0 OR current_balance <> 0 OR opening_balance_type <> 'DEBIT'"
            );
        } catch (Throwable $e) {
            $errors[] = 'reset_account_balances: ' . $e->getMessage();
        }

        if (self::tableExists($pdo, 'ledger_accounts')) {
            try {
                $pdo->exec('UPDATE ledger_accounts SET current_balance = 0.00 WHERE current_balance <> 0');
            } catch (Throwable $e) {
                $errors[] = 'reset_ledger_accounts: ' . $e->getMessage();
            }
        }
    }

    private static function safeTableName(string $table): string
    {
        return preg_replace('/[^a-z0-9_]/i', '', $table) ?? '';
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND TABLE_TYPE = ?'
        );
        $st->execute([$table, 'BASE TABLE']);

        return (int) $st->fetchColumn() > 0;
    }

    private static function tableRowCount(PDO $pdo, string $table): int
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function logResetActivity(PDO $pdo, array $context): void
    {
        $payload = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        @error_log('[TMS DataReset] ' . ($payload ?: '{}'));

        $userId = (int) ($context['user_id'] ?? 0);
        $ip = (string) ($context['ip'] ?? '');
        $meta = json_encode([
            'tables_count' => is_array($context['tables'] ?? null) ? count($context['tables']) : 0,
            'tables' => $context['tables'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        foreach (['accounting_audit_log', 'cashbook_audit_logs'] as $logTable) {
            try {
                if (!self::tableExists($pdo, $logTable)) {
                    continue;
                }

                if ($logTable === 'accounting_audit_log') {
                    $pdo->prepare(
                        'INSERT INTO accounting_audit_log (entity_type, entity_id, action, old_values, new_values, user_id, ip_address, user_agent)
                         VALUES (?, 0, ?, NULL, ?, ?, ?, ?)'
                    )->execute([
                        'system',
                        'full_database_reset',
                        $meta,
                        $userId > 0 ? $userId : null,
                        $ip,
                        (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                    ]);
                } else {
                    $pdo->prepare(
                        'INSERT INTO cashbook_audit_logs (entity, entity_id, action, user_id, ip, meta_json)
                         VALUES (?, 0, ?, ?, ?, ?)'
                    )->execute([
                        'system',
                        'full_database_reset',
                        $userId > 0 ? $userId : null,
                        $ip,
                        $meta,
                    ]);
                }
            } catch (Throwable $e) {
                @error_log('[TMS DataReset] audit log failed (' . $logTable . '): ' . $e->getMessage());
            }
        }
    }

    public static function markDatabaseReset(): void
    {
        $root = realpath(__DIR__ . '/../storage');
        if ($root === false) {
            return;
        }
        @file_put_contents($root . DIRECTORY_SEPARATOR . '.database_reset_at', date('c'));
    }

    public static function clearApplicationCache(): void
    {
        $projectRoot = realpath(__DIR__ . '/..');
        $storageRoot = $projectRoot !== false ? $projectRoot . DIRECTORY_SEPARATOR . 'storage' : null;

        $cacheDirs = [];
        if ($projectRoot !== false) {
            $cacheDirs[] = $projectRoot . DIRECTORY_SEPARATOR . 'cache';
            $cacheDirs[] = $projectRoot . DIRECTORY_SEPARATOR . 'tmp';
            $cacheDirs[] = $projectRoot . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'cache';
        }
        if ($storageRoot !== false) {
            $cacheDirs[] = $storageRoot . DIRECTORY_SEPARATOR . 'cache';
            $cacheDirs[] = $storageRoot . DIRECTORY_SEPARATOR . 'reports_cache';
            $cacheDirs[] = $storageRoot . DIRECTORY_SEPARATOR . 'exports';
            $cacheDirs[] = $storageRoot . DIRECTORY_SEPARATOR . 'dashboard_cache';
            $cacheDirs[] = $storageRoot . DIRECTORY_SEPARATOR . 'accounting_cache';
        }

        foreach ($cacheDirs as $dir) {
            self::emptyDirectory($dir);
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        if (function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
        }
    }

    private static function emptyDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                self::emptyDirectory($path);
                @rmdir($path);
            }
        }
    }

    /**
     * @return array{success: bool, cleared: array<string>, errors: array<string>}
     */
    public static function deleteAllRecords(PDO $pdo): array
    {
        $result = self::performFullDatabaseReset($pdo);
        return [
            'success' => $result['success'],
            'cleared' => $result['cleared'],
            'errors' => array_merge(
                $result['errors'],
                array_map(
                    static fn ($table, $count) => "Verification failed: {$table} still has {$count} row(s)",
                    array_keys($result['verification_failures']),
                    $result['verification_failures']
                ),
                array_map(
                    static fn ($table, $count) => "Accounting verification failed: {$table} still has {$count} row(s)",
                    array_keys($result['accounting_failures']),
                    $result['accounting_failures']
                )
            ),
        ];
    }

    /**
     * @return array{success: bool, cleared: array<string>, errors: array<string>}
     */
    public static function truncateAllTables(PDO $pdo): array
    {
        return self::deleteAllRecords($pdo);
    }

    /**
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
