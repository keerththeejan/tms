<?php

declare(strict_types=1);

/**
 * Cash Book: accounts, income/expense transactions, inter-account transfers.
 */
class CashbookRepository
{
    private static bool $schemaChecked = false;

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
  branch_id INT UNSIGNED NULL DEFAULT NULL,
  type ENUM('cash','bank','branch') NOT NULL DEFAULT 'cash',
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_acc_branch (branch_id),
  KEY idx_cashbook_acc_sort (sort_order)
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
  parcel_id INT UNSIGNED NULL DEFAULT NULL,
  items_json TEXT NULL,
  attachment_path VARCHAR(255) NULL DEFAULT NULL,
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
        try {
            $n = (int) $pdo->query('SELECT COUNT(*) FROM cashbook_accounts')->fetchColumn();
            if ($n === 0) {
                $ins = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, balance, sort_order) VALUES (?,?,?,?,?)');
                $ins->execute(['Cash Book', null, 'cash', 0.0, 1]);
                $ins->execute(['T.S', null, 'cash', 0.0, 2]);
            }
        } catch (\Throwable $e) {
            /* ignore */
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

        $bal = $tx - $out + $in;
        $up = $pdo->prepare('UPDATE cashbook_accounts SET balance=? WHERE id=?');
        $up->execute([$bal, $accountId]);

        return $bal;
    }

    /** @return list<array<string,mixed>> */
    public static function listAccounts(\PDO $pdo): array
    {
        $q = $pdo->query('SELECT id, name, branch_id, type, balance, sort_order, created_at FROM cashbook_accounts ORDER BY sort_order ASC, id ASC');

        return $q ? $q->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function getAccount(\PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT id, name, branch_id, type, balance, sort_order FROM cashbook_accounts WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public static function createAccount(\PDO $pdo, string $name, string $type, ?int $branchId): int
    {
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM cashbook_accounts')->fetchColumn();
        $st = $pdo->prepare('INSERT INTO cashbook_accounts (name, branch_id, type, balance, sort_order) VALUES (?,?,?,0,?)');
        $st->execute([$name, $branchId, $type, $max + 1]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateAccount(\PDO $pdo, int $id, string $name, string $type, ?int $branchId): void
    {
        $st = $pdo->prepare('UPDATE cashbook_accounts SET name=?, branch_id=?, type=? WHERE id=?');
        $st->execute([$name, $branchId, $type, $id]);
    }

    public static function deleteAccount(\PDO $pdo, int $id): bool
    {
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
        $st = $pdo->prepare('SELECT t.id, t.txn_type AS kind, t.amount, t.occurred_at, t.notes, t.parcel_id, t.items_json, t.attachment_path, '
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
            $rows[] = $r;
        }

        $st = $pdo->prepare('SELECT tr.id AS transfer_id, tr.amount, tr.occurred_at, tr.notes, tr.from_account_id AS peer_account_id, a.name AS peer_name '
            . 'FROM cashbook_transfers tr JOIN cashbook_accounts a ON a.id=tr.from_account_id '
            . 'WHERE tr.to_account_id=? AND tr.occurred_at BETWEEN ? AND ?');
        $st->execute([$accountId, $fromDt, $toDt]);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $r['kind'] = 'transfer_in';
            $r['id'] = 'tin-' . $r['transfer_id'];
            $rows[] = $r;
        }

        usort($rows, static function ($a, $b) {
            return strcmp((string) $b['occurred_at'], (string) $a['occurred_at']);
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

    public static function addTransaction(\PDO $pdo, int $accountId, string $txnType, float $amount, string $occurredAt, ?string $notes, ?int $parcelId, ?string $itemsJson, ?string $attachmentPath): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $st = $pdo->prepare('INSERT INTO cashbook_transactions (account_id, txn_type, amount, occurred_at, notes, parcel_id, items_json, attachment_path) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute([$accountId, $txnType, $amount, $occurredAt, $notes, $parcelId, $itemsJson, $attachmentPath]);
        $id = (int) $pdo->lastInsertId();
        self::recalcBalance($pdo, $accountId);

        return $id;
    }

    public static function updateTransaction(\PDO $pdo, int $id, int $accountId, string $txnType, float $amount, string $occurredAt, ?string $notes, ?int $parcelId, ?string $itemsJson, ?string $attachmentPath): void
    {
        $st = $pdo->prepare('SELECT account_id FROM cashbook_transactions WHERE id=?');
        $st->execute([$id]);
        $old = $st->fetchColumn();
        if (!$old) {
            throw new \RuntimeException('Transaction not found.');
        }
        $oldAid = (int) $old;
        $st = $pdo->prepare('UPDATE cashbook_transactions SET account_id=?, txn_type=?, amount=?, occurred_at=?, notes=?, parcel_id=?, items_json=?, attachment_path=? WHERE id=?');
        $st->execute([$accountId, $txnType, $amount, $occurredAt, $notes, $parcelId, $itemsJson, $attachmentPath, $id]);
        self::recalcBalance($pdo, $oldAid);
        if ($oldAid !== $accountId) {
            self::recalcBalance($pdo, $accountId);
        }
    }

    public static function deleteTransaction(\PDO $pdo, int $id): void
    {
        $st = $pdo->prepare('SELECT account_id FROM cashbook_transactions WHERE id=?');
        $st->execute([$id]);
        $aid = $st->fetchColumn();
        if (!$aid) {
            return;
        }
        $pdo->prepare('DELETE FROM cashbook_transactions WHERE id=?')->execute([$id]);
        self::recalcBalance($pdo, (int) $aid);
    }

    public static function addTransfer(\PDO $pdo, int $fromId, int $toId, float $amount, string $occurredAt, ?string $notes): int
    {
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Cannot transfer to the same account.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }
        $st = $pdo->prepare('INSERT INTO cashbook_transfers (from_account_id, to_account_id, amount, occurred_at, notes) VALUES (?,?,?,?,?)');
        $st->execute([$fromId, $toId, $amount, $occurredAt, $notes]);
        $tid = (int) $pdo->lastInsertId();
        self::recalcBalance($pdo, $fromId);
        self::recalcBalance($pdo, $toId);

        return $tid;
    }

    public static function deleteTransfer(\PDO $pdo, int $id): void
    {
        $st = $pdo->prepare('SELECT from_account_id, to_account_id FROM cashbook_transfers WHERE id=?');
        $st->execute([$id]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$r) {
            return;
        }
        $pdo->prepare('DELETE FROM cashbook_transfers WHERE id=?')->execute([$id]);
        self::recalcBalance($pdo, (int) $r['from_account_id']);
        self::recalcBalance($pdo, (int) $r['to_account_id']);
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
