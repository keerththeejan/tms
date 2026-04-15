<?php

declare(strict_types=1);

/**
 * Cash Book: accounts, income/expense transactions, inter-account transfers.
 */
class CashbookRepository
{
    private static bool $schemaChecked = false;

    /** @var bool|null Cache: whether cashbook_accounts.is_system exists (invalidated after migrations). */
    private static ?bool $cacheAccountsIsSystemColumn = null;

    public static function ensureSchema(\PDO $pdo): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;
        $stmts = [
            <<<'SQL'
CREATE TABLE IF NOT EXISTS cashbook_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  branch_id INT UNSIGNED NULL DEFAULT NULL,
  type ENUM('cash','bank','branch','customer','supplier') NOT NULL DEFAULT 'cash',
  account_kind ENUM('cash','bank','digital','receivable','payable') NULL DEFAULT NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  sort_order INT NOT NULL DEFAULT 0,
  customer_id INT UNSIGNED NULL DEFAULT NULL,
  supplier_id INT UNSIGNED NULL DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cashbook_acc_customer (customer_id),
  UNIQUE KEY uq_cashbook_acc_supplier (supplier_id),
  KEY idx_cashbook_acc_branch (branch_id),
  KEY idx_cashbook_acc_sort (sort_order),
  KEY idx_cashbook_acc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS cashbook_transactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id INT UNSIGNED NOT NULL,
  txn_type ENUM('income','expense') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  occurred_at DATETIME NOT NULL,
  notes TEXT NULL,
  reference_no VARCHAR(80) NULL DEFAULT NULL,
  parcel_id INT UNSIGNED NULL DEFAULT NULL,
  items_json TEXT NULL,
  attachment_path VARCHAR(255) NULL DEFAULT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_txn_account_time (account_id, occurred_at),
  KEY idx_cashbook_txn_parcel (parcel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS cashbook_transfers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  from_account_id INT UNSIGNED NOT NULL,
  to_account_id INT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  occurred_at DATETIME NOT NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_tr_from (from_account_id, occurred_at),
  KEY idx_cashbook_tr_to (to_account_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            ,
        ];
        foreach ($stmts as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\PDOException $e) {
                /* ignore duplicate */
            }
        }
        self::migrateCashbookAccountsExtras($pdo);
        self::$cacheAccountsIsSystemColumn = null;
        self::migrateCashbookTransfersExtras($pdo);
        self::migrateCashbookTransactionsExtras($pdo);
        self::ensureAuditSchema($pdo);
        try {
            $n = (int) $pdo->query('SELECT COUNT(*) FROM cashbook_accounts')->fetchColumn();
            if ($n === 0) {
                try {
                    $ins = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, account_kind, balance, sort_order, is_system) VALUES (?,?,?,?,?,?,?)');
                    $ins->execute(['Cash Book', null, 'cash', 'cash', 0.0, 1, 1]);
                    $ins->execute(['T.S', null, 'cash', 'cash', 0.0, 2, 1]);
                } catch (\Throwable $e) {
                    $ins = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, account_kind, balance, sort_order) VALUES (?,?,?,?,?,?)');
                    $ins->execute(['Cash Book', null, 'cash', 'cash', 0.0, 1]);
                    $ins->execute(['T.S', null, 'cash', 'cash', 0.0, 2]);
                }
            }
        } catch (\Throwable $e) {
            /* ignore */
        }
        self::ensureMinimumMainAccounts($pdo);
        self::syncMissingCustomerAccounts($pdo);
    }

    private static function migrateCashbookTransactionsExtras(\PDO $pdo): void
    {
        try {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $dbName = $dbName !== false ? (string) $dbName : '';
            if ($dbName === '') {
                return;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $has = static function (string $col) use ($chk, $dbName): bool {
                $chk->execute([$dbName, 'cashbook_transactions', $col]);
                return (int) $chk->fetchColumn() > 0;
            };
            if (!$has('reference_no')) {
                $pdo->exec('ALTER TABLE cashbook_transactions ADD COLUMN reference_no VARCHAR(80) NULL DEFAULT NULL AFTER notes');
            }
            if (!$has('created_by')) {
                $pdo->exec('ALTER TABLE cashbook_transactions ADD COLUMN created_by INT UNSIGNED NULL DEFAULT NULL AFTER attachment_path');
            }
        } catch (\Throwable $e) {
            /* ignore */
        }
    }

    private static function ensureAuditSchema(\PDO $pdo): void
    {
        try {
            $pdo->exec(
                <<<'SQL'
CREATE TABLE IF NOT EXISTS cashbook_audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity VARCHAR(40) NOT NULL,
  entity_id VARCHAR(64) NOT NULL,
  action VARCHAR(20) NOT NULL,
  user_id INT UNSIGNED NULL DEFAULT NULL,
  ip VARCHAR(64) NULL DEFAULT NULL,
  meta_json TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_audit_entity (entity, entity_id),
  KEY idx_cashbook_audit_created (created_at),
  KEY idx_cashbook_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
            );
        } catch (\Throwable $e) {
            /* ignore */
        }
    }

    public static function audit(\PDO $pdo, string $entity, string $entityId, string $action, ?int $userId, array $meta = []): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            if (is_string($ip)) {
                $ip = trim($ip) !== '' ? trim($ip) : null;
            } else {
                $ip = null;
            }
            $mj = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $st = $pdo->prepare('INSERT INTO cashbook_audit_logs (entity, entity_id, action, user_id, ip, meta_json) VALUES (?,?,?,?,?,?)');
            $st->execute([$entity, $entityId, $action, $userId, $ip, $mj]);
        } catch (\Throwable $e) {
            /* non-fatal */
        }
    }

    /** If only customer (or empty-type) accounts exist, add one main cash account for income/expense/transfers. */
    private static function ensureMinimumMainAccounts(\PDO $pdo): void
    {
        try {
            $nMain = (int) $pdo->query("SELECT COUNT(*) FROM cashbook_accounts WHERE type IN ('cash','bank','branch')")->fetchColumn();
            if ($nMain > 0) {
                return;
            }
            $total = (int) $pdo->query('SELECT COUNT(*) FROM cashbook_accounts')->fetchColumn();
            if ($total === 0) {
                return;
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM cashbook_accounts')->fetchColumn();
            try {
                $ins = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, account_kind, balance, sort_order, customer_id, status, is_system) VALUES (?,?,?,?,?,?,?,?,?)');
                $ins->execute(['Main Cash', null, 'cash', 'cash', 0.0, $max + 1, null, 'active', 1]);
            } catch (\Throwable $e) {
                $ins = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, account_kind, balance, sort_order, customer_id, status) VALUES (?,?,?,?,?,?,?,?)');
                $ins->execute(['Main Cash', null, 'cash', 'cash', 0.0, $max + 1, null, 'active']);
            }
        } catch (\Throwable $e) {
            /* ignore */
        }
    }

    private static function migrateCashbookTransfersExtras(\PDO $pdo): void
    {
        try {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $dbName = $dbName !== false ? (string) $dbName : '';
            if ($dbName === '') {
                return;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $chk->execute([$dbName, 'cashbook_transfers', 'created_by']);
            if ((int) $chk->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE cashbook_transfers ADD COLUMN created_by INT UNSIGNED NULL DEFAULT NULL AFTER notes');
            }
        } catch (\Throwable $e) {
            /* ignore */
        }
    }

    /**
     * Create cash book accounts for any customer that does not yet have customer_id linked.
     * Safe to run repeatedly (e.g. after deploy or for customers created before auto-link existed).
     *
     * @return int Number of customers successfully linked (0 if none missing or on failure)
     */
    public static function syncMissingCustomerAccounts(\PDO $pdo): int
    {
        $linked = 0;
        try {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $dbName = $dbName !== false ? (string) $dbName : '';
            if ($dbName === '') {
                return 0;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $chk->execute([$dbName, 'cashbook_accounts', 'customer_id']);
            if ((int) $chk->fetchColumn() === 0) {
                return 0;
            }
            $st = $pdo->query(
                'SELECT c.id, c.name FROM customers c '
                . 'LEFT JOIN cashbook_accounts ca ON ca.customer_id = c.id '
                . 'WHERE ca.id IS NULL'
            );
            if (!$st) {
                return 0;
            }
            foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $cid = (int) ($row['id'] ?? 0);
                $nm = trim((string) ($row['name'] ?? ''));
                if ($cid <= 0 || $nm === '') {
                    continue;
                }
                try {
                    self::ensureCustomerAccount($pdo, $cid, $nm);
                    $linked++;
                } catch (\Throwable $e) {
                    /* skip row; next request may succeed after schema fix */
                }
            }
        } catch (\Throwable $e) {
            /* customers table missing or no permission */
        }

        return $linked;
    }

    /**
     * Upgrade older cashbook_accounts tables (add customer link, status, extended type enum).
     */
    private static function migrateCashbookAccountsExtras(\PDO $pdo): void
    {
        try {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $dbName = $dbName !== false ? (string) $dbName : '';
            if ($dbName === '') {
                return;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $hasCol = static function (string $column) use ($chk, $dbName): bool {
                $chk->execute([$dbName, 'cashbook_accounts', $column]);

                return (int) $chk->fetchColumn() > 0;
            };

            if (!$hasCol('customer_id')) {
                try {
                    if ($hasCol('sort_order')) {
                        $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN customer_id INT UNSIGNED NULL DEFAULT NULL AFTER sort_order');
                    } else {
                        $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN customer_id INT UNSIGNED NULL DEFAULT NULL');
                    }
                } catch (\Throwable $e) {
                    try {
                        $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN customer_id INT UNSIGNED NULL DEFAULT NULL');
                    } catch (\Throwable $e2) {
                        /* duplicate column, denied, or non-MySQL */
                    }
                }
            }

            if (!$hasCol('status')) {
                $after = $hasCol('customer_id') ? 'customer_id' : ($hasCol('sort_order') ? 'sort_order' : null);
                $statusSql = "ALTER TABLE cashbook_accounts ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'";
                if ($after !== null) {
                    $statusSql .= ' AFTER ' . $after;
                }
                try {
                    $pdo->exec($statusSql);
                } catch (\Throwable $e) {
                    try {
                        $pdo->exec("ALTER TABLE cashbook_accounts ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
                    } catch (\Throwable $e2) {
                        /* ignore */
                    }
                }
            }

            try {
                $pdo->exec("ALTER TABLE cashbook_accounts MODIFY COLUMN type ENUM('cash','bank','branch','customer','supplier') NOT NULL DEFAULT 'cash'");
            } catch (\Throwable $e) {
                /* already applied */
            }
            try {
                $pdo->exec('ALTER TABLE cashbook_accounts ADD UNIQUE KEY uq_cashbook_acc_customer (customer_id)');
            } catch (\Throwable $e) {
                /* exists */
            }
            try {
                $pdo->exec('ALTER TABLE cashbook_accounts ADD KEY idx_cashbook_acc_status (status)');
            } catch (\Throwable $e) {
                /* exists */
            }
            if (!$hasCol('account_kind')) {
                try {
                    if ($hasCol('type')) {
                        $pdo->exec("ALTER TABLE cashbook_accounts ADD COLUMN account_kind ENUM('cash','bank','digital','receivable','payable') NULL DEFAULT NULL AFTER type");
                    } else {
                        $pdo->exec("ALTER TABLE cashbook_accounts ADD COLUMN account_kind ENUM('cash','bank','digital','receivable','payable') NULL DEFAULT NULL");
                    }
                } catch (\Throwable $e) {
                    try {
                        $pdo->exec("ALTER TABLE cashbook_accounts ADD COLUMN account_kind ENUM('cash','bank','digital','receivable','payable') NULL DEFAULT NULL");
                    } catch (\Throwable $e2) {
                        /* duplicate column, denied, or non-MySQL */
                    }
                }
            }
            try {
                $pdo->exec("UPDATE cashbook_accounts SET account_kind = CASE type WHEN 'cash' THEN 'cash' WHEN 'bank' THEN 'bank' WHEN 'branch' THEN 'digital' WHEN 'customer' THEN 'receivable' WHEN 'supplier' THEN 'payable' END WHERE account_kind IS NULL");
            } catch (\Throwable $e) {
                /* ignore */
            }
            if (!$hasCol('description')) {
                try {
                    $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN description TEXT NULL AFTER name');
                } catch (\Throwable $e) {
                    /* ignore */
                }
            }
            if (!$hasCol('opening_balance')) {
                try {
                    $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER account_kind');
                } catch (\Throwable $e) {
                    /* ignore */
                }
            }
            if (!$hasCol('supplier_id')) {
                try {
                    $after = $hasCol('customer_id') ? 'customer_id' : ($hasCol('sort_order') ? 'sort_order' : null);
                    $sql = 'ALTER TABLE cashbook_accounts ADD COLUMN supplier_id INT UNSIGNED NULL DEFAULT NULL';
                    if ($after !== null) {
                        $sql .= ' AFTER ' . $after;
                    }
                    $pdo->exec($sql);
                } catch (\Throwable $e) {
                    /* ignore */
                }
            }
            if (!$hasCol('is_system')) {
                try {
                    $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN is_system TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
                } catch (\Throwable $e) {
                    /* ignore */
                }
            }
            try {
                $pdo->exec('ALTER TABLE cashbook_accounts ADD UNIQUE KEY uq_cashbook_acc_supplier (supplier_id)');
            } catch (\Throwable $e) {
                /* exists/denied */
            }

            /* Employee-linked ledger accounts (HR / payroll payable) */
            $chk->execute([$dbName, 'cashbook_accounts', 'employee_id']);
            if ((int) $chk->fetchColumn() === 0) {
                try {
                    if ($hasCol('supplier_id')) {
                        $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN employee_id INT UNSIGNED NULL DEFAULT NULL AFTER supplier_id');
                    } else {
                        $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN employee_id INT UNSIGNED NULL DEFAULT NULL');
                    }
                } catch (\Throwable $e) {
                    try {
                        $pdo->exec('ALTER TABLE cashbook_accounts ADD COLUMN employee_id INT UNSIGNED NULL DEFAULT NULL');
                    } catch (\Throwable $e2) {
                        /* duplicate / denied */
                    }
                }
            }
            try {
                $pdo->exec("ALTER TABLE cashbook_accounts MODIFY COLUMN type ENUM('cash','bank','branch','customer','supplier','employee') NOT NULL DEFAULT 'cash'");
            } catch (\Throwable $e) {
                /* already applied or denied */
            }
            try {
                $pdo->exec('ALTER TABLE cashbook_accounts ADD UNIQUE KEY uq_cashbook_acc_employee (employee_id)');
            } catch (\Throwable $e) {
                /* exists */
            }
            try {
                $pdo->exec("UPDATE cashbook_accounts SET is_system=1 WHERE name IN ('Cash Book','Main Cash','T.S') AND type IN ('cash','bank','branch')");
            } catch (\Throwable $e) {
                /* ignore */
            }
        } catch (\Throwable $e) {
            /* insufficient privileges or non-MySQL */
        }
    }

    /**
     * Before deleting a customer row: remove or detach the linked cash book account.
     */
    public static function detachCashbookAccountForDeletedCustomer(\PDO $pdo, int $customerId): void
    {
        if ($customerId <= 0) {
            return;
        }
        try {
            $st = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE customer_id = ? LIMIT 1');
            $st->execute([$customerId]);
            $aid = $st->fetchColumn();
            if (!$aid) {
                return;
            }
            $accountId = (int) $aid;
            if (self::deleteAccount($pdo, $accountId)) {
                return;
            }
            $pdo->prepare('UPDATE cashbook_accounts SET customer_id = NULL, status = ? WHERE id = ?')->execute(['inactive', $accountId]);
        } catch (\Throwable $e) {
            /* non-fatal */
        }
    }

    public static function recalcBalance(\PDO $pdo, int $accountId): float
    {
        $st = $pdo->prepare('SELECT COALESCE(SUM(CASE WHEN txn_type=\'income\' THEN amount ELSE -amount END),0) FROM cashbook_transactions WHERE account_id=?');
        $st->execute([$accountId]);
        $tx = (float) $st->fetchColumn();

        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM cashbook_transfers WHERE from_account_id=?');
        $st->execute([$accountId]);
        $out = (float) $st->fetchColumn();

        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM cashbook_transfers WHERE to_account_id=?');
        $st->execute([$accountId]);
        $in = (float) $st->fetchColumn();

        $opening = 0.0;
        try {
            $st = $pdo->prepare('SELECT opening_balance FROM cashbook_accounts WHERE id=?');
            $st->execute([$accountId]);
            $opening = (float) ($st->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            $opening = 0.0;
        }

        $bal = $opening + $tx - $out + $in;
        $up = $pdo->prepare('UPDATE cashbook_accounts SET balance=? WHERE id=?');
        $up->execute([$bal, $accountId]);

        return $bal;
    }

    private static function defaultAccountKindForType(string $type): ?string
    {
        if ($type === 'cash') {
            return 'cash';
        }
        if ($type === 'bank') {
            return 'bank';
        }
        if ($type === 'branch') {
            return 'digital';
        }
        if ($type === 'customer') {
            return 'receivable';
        }
        if ($type === 'supplier') {
            return 'payable';
        }
        if ($type === 'employee') {
            return 'payable';
        }

        return null;
    }

    /**
     * SELECT list for account rows (avoids SQL errors if is_system migration has not run yet).
     */
    private static function sqlAccountsSelectFields(\PDO $pdo): string
    {
        $isSystemExpr = self::cashbookAccountsHasIsSystemColumn($pdo) ? 'ca.is_system' : '0 AS is_system';

        return 'ca.id, ca.name, ca.description, ca.branch_id, ca.type, ca.account_kind, ca.opening_balance, ca.balance, ca.sort_order, ca.created_at, ca.customer_id, ca.supplier_id, ca.employee_id, ca.status, ' . $isSystemExpr . ', c.name AS customer_name, e.name AS employee_name';
    }

    private static function cashbookAccountsHasIsSystemColumn(\PDO $pdo): bool
    {
        if (self::$cacheAccountsIsSystemColumn !== null) {
            return self::$cacheAccountsIsSystemColumn;
        }
        self::$cacheAccountsIsSystemColumn = false;
        try {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $dbName = $dbName !== false ? (string) $dbName : '';
            if ($dbName === '') {
                return false;
            }
            $chk = $pdo->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $chk->execute([$dbName, 'cashbook_accounts', 'is_system']);
            self::$cacheAccountsIsSystemColumn = (int) $chk->fetchColumn() > 0;
        } catch (\Throwable $e) {
            self::$cacheAccountsIsSystemColumn = false;
        }

        return self::$cacheAccountsIsSystemColumn;
    }

    /** @return list<array<string,mixed>> */
    public static function listAccounts(\PDO $pdo, bool $activeOnly = false): array
    {
        $sql = 'SELECT ' . self::sqlAccountsSelectFields($pdo) . ' '
            . 'FROM cashbook_accounts ca LEFT JOIN customers c ON c.id = ca.customer_id LEFT JOIN employees e ON e.id = ca.employee_id';
        if ($activeOnly) {
            $sql .= " WHERE ca.status = 'active'";
        }
        $sql .= ' ORDER BY ca.sort_order ASC, ca.id ASC';
        $q = $pdo->query($sql);

        return $q ? $q->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    /**
     * @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int}
     */
    public static function listAccountsPaged(\PDO $pdo, string $q, ?string $typeFilter, ?string $statusFilter, int $page, int $perPage, string $sort = 'default'): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = 'ca.name LIKE ?';
            $like = '%' . $q . '%';
            $params[] = $like;
        }
        if ($typeFilter !== null && $typeFilter !== '') {
            if ($typeFilter === 'main') {
                // "Main" = Cash + Digital (branch)
                $where[] = "ca.type IN ('cash','branch')";
            } elseif (in_array($typeFilter, ['cash', 'bank', 'branch', 'customer', 'supplier', 'employee'], true)) {
                $where[] = 'ca.type = ?';
                $params[] = $typeFilter;
            }
        }
        if ($statusFilter !== null && $statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'], true)) {
            $where[] = 'ca.status = ?';
            $params[] = $statusFilter;
        }
        $orderBy = 'ca.sort_order ASC, ca.id ASC';
        if ($sort === 'name_asc') {
            $orderBy = 'ca.name ASC, ca.id ASC';
        } elseif ($sort === 'name_desc') {
            $orderBy = 'ca.name DESC, ca.id DESC';
        } elseif ($sort === 'balance_asc') {
            $orderBy = 'ca.balance ASC, ca.id ASC';
        } elseif ($sort === 'balance_desc') {
            $orderBy = 'ca.balance DESC, ca.id DESC';
        }
        $w = implode(' AND ', $where);
        $from = 'cashbook_accounts ca LEFT JOIN customers c ON c.id = ca.customer_id LEFT JOIN employees e ON e.id = ca.employee_id';
        $st = $pdo->prepare("SELECT COUNT(*) FROM $from WHERE $w");
        $st->execute($params);
        $total = (int) $st->fetchColumn();
        $lim = (int) $perPage;
        $off = (int) $offset;
        $st = $pdo->prepare(
            'SELECT ' . self::sqlAccountsSelectFields($pdo) . ' '
            . "FROM $from WHERE $w ORDER BY $orderBy LIMIT $lim OFFSET $off"
        );
        $st->execute($params);
        $items = $st->fetchAll(\PDO::FETCH_ASSOC);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public static function accountNameExists(\PDO $pdo, string $name, int $excludeId): bool
    {
        $nm = trim($name);
        if ($nm === '') {
            return false;
        }
        $st = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND id != ? LIMIT 1');
        $st->execute([$nm, $excludeId]);

        return (bool) $st->fetchColumn();
    }

    /**
     * Aggregates for the Accounts management dashboard (calendar month based on anchor date).
     *
     * @return array{total_balance: float, period_income: float, period_expense: float, period_from: string, period_to: string}
     */
    public static function managementDashboardTotals(\PDO $pdo, string $anchorYmd): array
    {
        $t = strtotime($anchorYmd . ' 12:00:00') ?: time();
        $monthStart = date('Y-m-01', $t);
        $monthEnd = date('Y-m-t 23:59:59', $t);
        $tb = (float) $pdo->query('SELECT COALESCE(SUM(balance),0) FROM cashbook_accounts')->fetchColumn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM cashbook_transactions WHERE txn_type='income' AND occurred_at BETWEEN ? AND ?");
        $st->execute([$monthStart . ' 00:00:00', $monthEnd]);
        $inc = (float) $st->fetchColumn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM cashbook_transactions WHERE txn_type='expense' AND occurred_at BETWEEN ? AND ?");
        $st->execute([$monthStart . ' 00:00:00', $monthEnd]);
        $exp = (float) $st->fetchColumn();

        return [
            'total_balance' => $tb,
            'period_income' => $inc,
            'period_expense' => $exp,
            'period_from' => $monthStart . ' 00:00:00',
            'period_to' => $monthEnd,
        ];
    }

    public static function ensureCustomerAccount(\PDO $pdo, int $customerId, string $customerName): int
    {
        $name = trim($customerName);
        if ($name === '') {
            throw new \InvalidArgumentException('Customer name is required for account link.');
        }
        $st = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE customer_id = ? LIMIT 1');
        $st->execute([$customerId]);
        $existing = $st->fetchColumn();
        if ($existing) {
            $id = (int) $existing;
            $pdo->prepare('UPDATE cashbook_accounts SET name = ?, type = ?, account_kind = ? WHERE id = ?')->execute([$name, 'customer', 'receivable', $id]);

            return $id;
        }
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM cashbook_accounts')->fetchColumn();
        $st = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, account_kind, balance, sort_order, customer_id, status) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute([$name, null, 'customer', 'receivable', 0.0, $max + 1, $customerId, 'active']);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Ensure a cash book account exists for an employee (one per employee_id). Idempotent.
     *
     * @return array{id: int, created: bool}
     */
    public static function ensureEmployeeAccount(\PDO $pdo, int $employeeId, string $displayName, string $empStatus = 'active'): array
    {
        self::ensureSchema($pdo);
        if ($employeeId <= 0) {
            throw new \InvalidArgumentException('Invalid employee id.');
        }
        $name = trim($displayName);
        if ($name === '') {
            $st = $pdo->prepare('SELECT COALESCE(NULLIF(TRIM(name), ""), TRIM(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")))) AS dn FROM employees WHERE id = ? LIMIT 1');
            $st->execute([$employeeId]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            $name = trim((string) ($row['dn'] ?? ''));
        }
        if ($name === '') {
            $name = 'Employee #' . $employeeId;
        }
        $accountStatus = ($empStatus === 'inactive' || $empStatus === 'suspended') ? 'inactive' : 'active';

        $st = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE employee_id = ? LIMIT 1');
        $st->execute([$employeeId]);
        $existing = $st->fetchColumn();
        if ($existing) {
            $id = (int) $existing;
            $pdo->prepare('UPDATE cashbook_accounts SET name = ?, type = ?, account_kind = ?, status = ? WHERE id = ?')->execute([$name, 'employee', 'payable', $accountStatus, $id]);

            return ['id' => $id, 'created' => false];
        }
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM cashbook_accounts')->fetchColumn();
        $st = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, account_kind, balance, sort_order, employee_id, status) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute([$name, null, 'employee', 'payable', 0.0, $max + 1, $employeeId, $accountStatus]);

        return ['id' => (int) $pdo->lastInsertId(), 'created' => true];
    }

    public static function syncCustomerAccountName(\PDO $pdo, int $customerId, string $customerName): void
    {
        $name = trim($customerName);
        if ($name === '') {
            return;
        }
        $st = $pdo->prepare('UPDATE cashbook_accounts SET name = ? WHERE customer_id = ?');
        $st->execute([$name, $customerId]);
    }

    public static function getAccount(\PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare(
            'SELECT ' . self::sqlAccountsSelectFields($pdo) . ' '
            . 'FROM cashbook_accounts ca LEFT JOIN customers c ON c.id = ca.customer_id LEFT JOIN employees e ON e.id = ca.employee_id WHERE ca.id=? LIMIT 1'
        );
        $st->execute([$id]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public static function createAccount(\PDO $pdo, string $name, string $type, ?int $branchId, ?int $customerId = null, string $status = 'active', float $openingBalance = 0.0, ?int $supplierId = null, ?string $description = null): int
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM cashbook_accounts')->fetchColumn();
        $kind = self::defaultAccountKindForType($type);
        $openingBalance = is_finite($openingBalance) ? $openingBalance : 0.0;
        $desc = $description !== null ? trim($description) : null;
        if ($desc === '') {
            $desc = null;
        }
        $st = $pdo->prepare('INSERT INTO cashbook_accounts (name, description, branch_id, type, account_kind, opening_balance, balance, sort_order, customer_id, supplier_id, status) VALUES (?,?,?,?,?,?,0,?,?,?,?)');
        $st->execute([$name, $desc, $branchId, $type, $kind, $openingBalance, $max + 1, $customerId, $supplierId, $status]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateAccount(\PDO $pdo, int $id, string $name, string $type, ?int $branchId, string $status = 'active', ?float $openingBalance = null, ?string $description = null): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }
        $existing = self::getAccount($pdo, $id);
        if ($existing && (int) ($existing['is_system'] ?? 0) === 1) {
            $desc = $description !== null ? trim($description) : null;
            if ($desc === '') {
                $desc = null;
            }
            $st = $pdo->prepare('UPDATE cashbook_accounts SET name=?, description=?, branch_id=?, status=? WHERE id=?');
            $st->execute([$name, $desc, $branchId, $status, $id]);

            return;
        }
        if ($existing && !empty($existing['customer_id'])) {
            $type = 'customer';
        }
        if ($existing && !empty($existing['employee_id'])) {
            $type = 'employee';
        }
        $kind = self::defaultAccountKindForType($type);
        $desc = $description !== null ? trim($description) : null;
        if ($desc === '') {
            $desc = null;
        }
        if ($openingBalance !== null && $type !== 'customer' && $type !== 'employee') {
            $openingBalance = is_finite($openingBalance) ? $openingBalance : 0.0;
            $st = $pdo->prepare('UPDATE cashbook_accounts SET name=?, description=?, branch_id=?, type=?, account_kind=?, opening_balance=?, status=? WHERE id=?');
            $st->execute([$name, $desc, $branchId, $type, $kind, $openingBalance, $status, $id]);
        } else {
            $st = $pdo->prepare('UPDATE cashbook_accounts SET name=?, description=?, branch_id=?, type=?, account_kind=?, status=? WHERE id=?');
            $st->execute([$name, $desc, $branchId, $type, $kind, $status, $id]);
        }
        self::recalcBalance($pdo, $id);
    }

    public static function deleteAccount(\PDO $pdo, int $id): bool
    {
        $acc = self::getAccount($pdo, $id);
        if (!$acc) {
            return false;
        }
        if ((int) ($acc['is_system'] ?? 0) === 1) {
            return false;
        }
        if (!empty($acc['customer_id'])) {
            return false;
        }
        if (!empty($acc['employee_id'])) {
            return false;
        }
        $bal = (float) ($acc['balance'] ?? 0);
        if (abs($bal) > 0.00001) {
            return false;
        }
        $c = $pdo->prepare('SELECT COUNT(*) FROM cashbook_transactions WHERE account_id=?');
        $c->execute([$id]);
        if ((int) $c->fetchColumn() > 0) {
            return false;
        }
        $c = $pdo->prepare('SELECT COUNT(*) FROM cashbook_transfers WHERE from_account_id=? OR to_account_id=?');
        $c->execute([$id, $id]);
        if ((int) $c->fetchColumn() > 0) {
            return false;
        }
        $pdo->prepare('DELETE FROM cashbook_accounts WHERE id=?')->execute([$id]);

        return true;
    }

    /**
     * @return array{from:string,to:string}
     */
    public static function periodBounds(string $period, string $anchorDate): array
    {
        $t = strtotime($anchorDate . ' 12:00:00') ?: time();
        $d = date('Y-m-d', $t);
        switch ($period) {
            case 'daily':
                return [$d . ' 00:00:00', $d . ' 23:59:59'];
            case 'weekly':
                $w = (int) date('w', $t);
                $mon = $w === 0 ? strtotime('-6 days', $t) : strtotime('-' . ($w - 1) . ' days', $t);
                $sun = strtotime('+6 days', $mon);

                return [date('Y-m-d', $mon) . ' 00:00:00', date('Y-m-d', $sun) . ' 23:59:59'];
            case 'monthly':
                $start = date('Y-m-01 00:00:00', $t);
                $end = date('Y-m-t 23:59:59', $t);

                return [$start, $end];
            case 'yearly':
                $y = (int) date('Y', $t);

                return [$y . '-01-01 00:00:00', $y . '-12-31 23:59:59'];
            case 'all':
            default:
                return ['1970-01-01 00:00:00', '2099-12-31 23:59:59'];
        }
    }

    /**
     * @return array{income:float,expense:float,balance:float}
     */
    public static function totalsForAccount(\PDO $pdo, int $accountId, string $fromDt, string $toDt): array
    {
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM cashbook_transactions WHERE account_id=? AND txn_type='income' AND occurred_at BETWEEN ? AND ?");
        $st->execute([$accountId, $fromDt, $toDt]);
        $income = (float) $st->fetchColumn();

        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM cashbook_transactions WHERE account_id=? AND txn_type='expense' AND occurred_at BETWEEN ? AND ?");
        $st->execute([$accountId, $fromDt, $toDt]);
        $expense = (float) $st->fetchColumn();

        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM cashbook_transfers WHERE from_account_id=? AND occurred_at BETWEEN ? AND ?');
        $st->execute([$accountId, $fromDt, $toDt]);
        $tOut = (float) $st->fetchColumn();

        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM cashbook_transfers WHERE to_account_id=? AND occurred_at BETWEEN ? AND ?');
        $st->execute([$accountId, $fromDt, $toDt]);
        $tIn = (float) $st->fetchColumn();

        $st = $pdo->prepare('SELECT balance FROM cashbook_accounts WHERE id=?');
        $st->execute([$accountId]);
        $stored = (float) ($st->fetchColumn() ?: 0);

        return [
            'income' => $income,
            'expense' => $expense,
            'transfer_out' => $tOut,
            'transfer_in' => $tIn,
            'balance' => $stored,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function listMergedEntries(\PDO $pdo, int $accountId, string $fromDt, string $toDt, string $q = ''): array
    {
        $rows = [];
        $st = $pdo->prepare('SELECT t.id, t.account_id, t.txn_type AS kind, t.amount, t.occurred_at, t.notes, t.reference_no, t.created_by, t.parcel_id, t.items_json, t.attachment_path, '
            . 'NULL AS transfer_id, NULL AS peer_account_id, NULL AS peer_name '
            . 'FROM cashbook_transactions t WHERE t.account_id=? AND t.occurred_at BETWEEN ? AND ?');
        $st->execute([$accountId, $fromDt, $toDt]);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $rows[] = $r;
        }

        $st = $pdo->prepare('SELECT tr.id AS transfer_id, tr.amount, tr.occurred_at, tr.notes, tr.to_account_id AS peer_account_id, a.name AS peer_name '
            . 'FROM cashbook_transfers tr JOIN cashbook_accounts a ON a.id=tr.to_account_id '
            . 'WHERE tr.from_account_id=? AND tr.occurred_at BETWEEN ? AND ?');
        $st->execute([$accountId, $fromDt, $toDt]);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $r['kind'] = 'transfer_out';
            $r['id'] = 'tout-' . $r['transfer_id'];
            $r['account_id'] = $accountId;
            $rows[] = $r;
        }

        $st = $pdo->prepare('SELECT tr.id AS transfer_id, tr.amount, tr.occurred_at, tr.notes, tr.from_account_id AS peer_account_id, a.name AS peer_name '
            . 'FROM cashbook_transfers tr JOIN cashbook_accounts a ON a.id=tr.from_account_id '
            . 'WHERE tr.to_account_id=? AND tr.occurred_at BETWEEN ? AND ?');
        $st->execute([$accountId, $fromDt, $toDt]);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $r['kind'] = 'transfer_in';
            $r['id'] = 'tin-' . $r['transfer_id'];
            $r['account_id'] = $accountId;
            $rows[] = $r;
        }

        $opening = self::accountNetBefore($pdo, $accountId, $fromDt);
        usort($rows, static function ($a, $b) {
            $t = strcmp((string) $a['occurred_at'], (string) $b['occurred_at']);
            if ($t !== 0) {
                return $t;
            }

            return strcmp((string) $a['id'], (string) $b['id']);
        });
        $run = $opening;
        foreach ($rows as $k => $r) {
            $kind = (string) ($r['kind'] ?? '');
            $amt = (float) ($r['amount'] ?? 0);
            if ($kind === 'income') {
                $run += $amt;
            } elseif ($kind === 'expense') {
                $run -= $amt;
            } elseif ($kind === 'transfer_out') {
                $run -= $amt;
            } elseif ($kind === 'transfer_in') {
                $run += $amt;
            }
            $rows[$k]['running_balance'] = $run;
        }
        usort($rows, static function ($a, $b) {
            $t = strcmp((string) $b['occurred_at'], (string) $a['occurred_at']);
            if ($t !== 0) {
                return $t;
            }

            return strcmp((string) $b['id'], (string) $a['id']);
        });

        if ($q !== '') {
            $ql = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function ($r) use ($ql) {
                $n = mb_strtolower((string) ($r['notes'] ?? ''));
                $peer = mb_strtolower((string) ($r['peer_name'] ?? ''));

                return $ql === '' || mb_strpos($n, $ql) !== false || mb_strpos($peer, $ql) !== false;
            }));
        }

        return $rows;
    }

    /** Net balance effect on account from all activity strictly before $beforeExclusive (datetime string). */
    private static function accountNetBefore(\PDO $pdo, int $accountId, string $beforeExclusive): float
    {
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM cashbook_transactions WHERE account_id=? AND txn_type='income' AND occurred_at < ?");
        $st->execute([$accountId, $beforeExclusive]);
        $in = (float) $st->fetchColumn();
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM cashbook_transactions WHERE account_id=? AND txn_type='expense' AND occurred_at < ?");
        $st->execute([$accountId, $beforeExclusive]);
        $exp = (float) $st->fetchColumn();
        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM cashbook_transfers WHERE from_account_id=? AND occurred_at < ?');
        $st->execute([$accountId, $beforeExclusive]);
        $out = (float) $st->fetchColumn();
        $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM cashbook_transfers WHERE to_account_id=? AND occurred_at < ?');
        $st->execute([$accountId, $beforeExclusive]);
        $tin = (float) $st->fetchColumn();

        return $in - $exp - $out + $tin;
    }

    public static function addTransaction(\PDO $pdo, int $accountId, string $txnType, float $amount, string $occurredAt, ?string $notes, ?int $parcelId, ?string $itemsJson, ?string $attachmentPath): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO cashbook_transactions (account_id, txn_type, amount, occurred_at, notes, reference_no, parcel_id, items_json, attachment_path, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$accountId, $txnType, $amount, $occurredAt, $notes, null, $parcelId, $itemsJson, $attachmentPath, null]);
            $id = (int) $pdo->lastInsertId();
            self::recalcBalance($pdo, $accountId);
            $pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function addTransactionV2(\PDO $pdo, int $accountId, string $txnType, float $amount, string $occurredAt, ?string $notes, ?int $parcelId, ?string $itemsJson, ?string $attachmentPath, ?int $createdBy = null, ?string $referenceNo = null): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO cashbook_transactions (account_id, txn_type, amount, occurred_at, notes, reference_no, parcel_id, items_json, attachment_path, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$accountId, $txnType, $amount, $occurredAt, $notes, $referenceNo, $parcelId, $itemsJson, $attachmentPath, $createdBy]);
            $id = (int) $pdo->lastInsertId();
            self::recalcBalance($pdo, $accountId);
            $pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function updateTransaction(\PDO $pdo, int $id, int $accountId, string $txnType, float $amount, string $occurredAt, ?string $notes, ?int $parcelId, ?string $itemsJson, ?string $attachmentPath, ?string $referenceNo = null): void
    {
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT account_id FROM cashbook_transactions WHERE id=?');
            $st->execute([$id]);
            $old = $st->fetchColumn();
            if (!$old) {
                $pdo->rollBack();
                throw new \RuntimeException('Transaction not found.');
            }
            $oldAid = (int) $old;
            $st = $pdo->prepare('UPDATE cashbook_transactions SET account_id=?, txn_type=?, amount=?, occurred_at=?, notes=?, reference_no=?, parcel_id=?, items_json=?, attachment_path=? WHERE id=?');
            $st->execute([$accountId, $txnType, $amount, $occurredAt, $notes, $referenceNo, $parcelId, $itemsJson, $attachmentPath, $id]);
            self::recalcBalance($pdo, $oldAid);
            if ($oldAid !== $accountId) {
                self::recalcBalance($pdo, $accountId);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function getTransaction(\PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $st = $pdo->prepare(
            'SELECT t.id, t.account_id, a.name AS account_name, a.type AS account_type, '
            . 't.txn_type, t.amount, t.occurred_at, t.notes, t.reference_no, t.parcel_id, t.items_json, '
            . 't.attachment_path, t.created_by, u.full_name AS created_by_name, t.created_at '
            . 'FROM cashbook_transactions t '
            . 'LEFT JOIN cashbook_accounts a ON a.id = t.account_id '
            . 'LEFT JOIN users u ON u.id = t.created_by '
            . 'WHERE t.id=? LIMIT 1'
        );
        $st->execute([$id]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function getTransfer(\PDO $pdo, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $st = $pdo->prepare('SELECT tr.*, fa.name AS from_name, ta.name AS to_name FROM cashbook_transfers tr LEFT JOIN cashbook_accounts fa ON fa.id=tr.from_account_id LEFT JOIN cashbook_accounts ta ON ta.id=tr.to_account_id WHERE tr.id=? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function deleteTransaction(\PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT account_id FROM cashbook_transactions WHERE id=?');
            $st->execute([$id]);
            $aid = $st->fetchColumn();
            if (!$aid) {
                $pdo->commit();

                return;
            }
            $pdo->prepare('DELETE FROM cashbook_transactions WHERE id=?')->execute([$id]);
            self::recalcBalance($pdo, (int) $aid);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function addTransfer(\PDO $pdo, int $fromId, int $toId, float $amount, string $occurredAt, ?string $notes, bool $preventNegativeBalance = false, ?int $createdBy = null): int
    {
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Cannot transfer to the same account.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $pdo->beginTransaction();
        try {
            if ($preventNegativeBalance) {
                self::recalcBalance($pdo, $fromId);
                $acc = self::getAccount($pdo, $fromId);
                $bal = $acc ? (float) $acc['balance'] : 0.0;
                if ($bal + 1e-6 < $amount) {
                    $pdo->rollBack();
                    throw new \InvalidArgumentException('Insufficient balance in source account for this transfer.');
                }
            }
            $st = $pdo->prepare('INSERT INTO cashbook_transfers (from_account_id, to_account_id, amount, occurred_at, notes, created_by) VALUES (?,?,?,?,?,?)');
            $st->execute([$fromId, $toId, $amount, $occurredAt, $notes, $createdBy]);
            $tid = (int) $pdo->lastInsertId();
            self::recalcBalance($pdo, $fromId);
            self::recalcBalance($pdo, $toId);
            $pdo->commit();

            return $tid;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function deleteTransfer(\PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT from_account_id, to_account_id FROM cashbook_transfers WHERE id=?');
            $st->execute([$id]);
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$r) {
                $pdo->commit();

                return;
            }
            $pdo->prepare('DELETE FROM cashbook_transfers WHERE id=?')->execute([$id]);
            self::recalcBalance($pdo, (int) $r['from_account_id']);
            self::recalcBalance($pdo, (int) $r['to_account_id']);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Resolve the customer's linked Cash Book account from a parcel id (for transaction UX).
     *
     * @return array{customer_id:int|null,customer_name:?string,cashbook_account_id:int|null}|null
     */
    public static function parcelCustomerCashbookAccount(\PDO $pdo, int $parcelId): ?array
    {
        if ($parcelId <= 0) {
            return null;
        }
        $st = $pdo->prepare(
            'SELECT p.customer_id, c.name AS customer_name, ca.id AS cashbook_account_id '
            . 'FROM parcels p '
            . 'LEFT JOIN customers c ON c.id = p.customer_id '
            . 'LEFT JOIN cashbook_accounts ca ON ca.customer_id = p.customer_id AND ca.type = \'customer\' '
            . 'WHERE p.id = ? LIMIT 1'
        );
        $st->execute([$parcelId]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'customer_id' => $row['customer_id'] !== null && $row['customer_id'] !== '' ? (int) $row['customer_id'] : null,
            'customer_name' => isset($row['customer_name']) && $row['customer_name'] !== '' ? (string) $row['customer_name'] : null,
            'cashbook_account_id' => $row['cashbook_account_id'] !== null && $row['cashbook_account_id'] !== ''
                ? (int) $row['cashbook_account_id']
                : null,
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function searchParcels(\PDO $pdo, string $q, int $limit = 20): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $like = '%' . $q . '%';
        if (ctype_digit($q)) {
            $st = $pdo->prepare('SELECT id, tracking_number, invoice_no, price, status, created_at FROM parcels WHERE id=? OR tracking_number LIKE ? OR CAST(invoice_no AS CHAR) LIKE ? ORDER BY id DESC LIMIT ' . (int) $limit);
            $st->execute([(int) $q, $like, $like]);
        } else {
            $st = $pdo->prepare('SELECT id, tracking_number, invoice_no, price, status, created_at FROM parcels WHERE tracking_number LIKE ? OR CAST(invoice_no AS CHAR) LIKE ? ORDER BY id DESC LIMIT ' . (int) $limit);
            $st->execute([$like, $like]);
        }

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array{period:string,income:float,expense:float}>
     */
    public static function reportByMonth(\PDO $pdo, int $accountId, string $fromDate, string $toDate): array
    {
        $st = $pdo->prepare("SELECT DATE_FORMAT(occurred_at,'%Y-%m') AS p, txn_type, COALESCE(SUM(amount),0) AS s FROM cashbook_transactions WHERE account_id=? AND occurred_at BETWEEN ? AND ? GROUP BY p, txn_type ORDER BY p");
        $st->execute([$accountId, $fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        $map = [];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $p = (string) $r['p'];
            if (!isset($map[$p])) {
                $map[$p] = ['period' => $p, 'income' => 0.0, 'expense' => 0.0];
            }
            if ($r['txn_type'] === 'income') {
                $map[$p]['income'] += (float) $r['s'];
            } else {
                $map[$p]['expense'] += (float) $r['s'];
            }
        }

        return array_values($map);
    }
}
