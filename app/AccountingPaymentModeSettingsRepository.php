<?php

declare(strict_types=1);

/**
 * Maps payment modes (CASH, BANK, CHEQUE) to default ledger accounts for voucher auto-posting.
 */
class AccountingPaymentModeSettingsRepository
{
    /** @var list<string> */
    public const CONFIGURABLE_MODES = ['CASH', 'BANK', 'CHEQUE'];

    /** @var array<string, string> Payment modes resolved via another configured mode. */
    private const MODE_ALIASES = [
        'ONLINE' => 'BANK',
        'OTHER' => 'BANK',
    ];

    public static function ensureSchema(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'accounting_payment_mode_accounts')) {
            $pdo->exec(<<<'SQL'
CREATE TABLE accounting_payment_mode_accounts (
  payment_mode varchar(20) NOT NULL PRIMARY KEY,
  account_id bigint unsigned NOT NULL,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_apma_account (account_id),
  CONSTRAINT fk_apma_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
        }

        self::ensureChequeClearingAccount($pdo);
        self::seedDefaultsIfEmpty($pdo);
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $st->execute([$table]);

        return (int) $st->fetchColumn() > 0;
    }

    private static function ensureChequeClearingAccount(PDO $pdo): void
    {
        $st = $pdo->prepare('SELECT id FROM accounts WHERE account_code = ? LIMIT 1');
        $st->execute(['CHEQUE_CLEARING']);
        if ($st->fetchColumn()) {
            return;
        }

        $ins = $pdo->prepare(
            'INSERT INTO accounts (account_code, account_name, account_group_id, opening_balance, opening_balance_type, is_system)
             SELECT ?, ?, g.id, 0, \'DEBIT\', 1 FROM account_groups g WHERE g.group_code = \'BANK\' LIMIT 1'
        );
        $ins->execute(['CHEQUE_CLEARING', 'Cheque Clearing Account']);
    }

    private static function seedDefaultsIfEmpty(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM accounting_payment_mode_accounts')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            'CASH' => 'CASH_MAIN',
            'BANK' => 'BANK_MAIN',
            'CHEQUE' => 'CHEQUE_CLEARING',
        ];

        $ins = $pdo->prepare(
            'INSERT INTO accounting_payment_mode_accounts (payment_mode, account_id)
             SELECT ?, a.id FROM accounts a WHERE a.account_code = ? LIMIT 1'
        );

        foreach ($defaults as $mode => $code) {
            $ins->execute([$mode, $code]);
        }
    }

    public static function normalizePaymentMode(string $paymentMode): string
    {
        $mode = strtoupper(trim($paymentMode));
        if ($mode === 'PETTY_CASH') {
            return 'CASH';
        }

        return $mode;
    }

    public static function resolveConfigurableMode(string $paymentMode): string
    {
        $mode = self::normalizePaymentMode($paymentMode);

        return self::MODE_ALIASES[$mode] ?? $mode;
    }

    public static function getAccountIdForMode(PDO $pdo, string $paymentMode): ?int
    {
        self::ensureSchema($pdo);
        $mode = self::resolveConfigurableMode($paymentMode);

        $st = $pdo->prepare(
            'SELECT account_id FROM accounting_payment_mode_accounts WHERE payment_mode = ? LIMIT 1'
        );
        $st->execute([$mode]);
        $id = $st->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /** @return list<int> */
    public static function getAllDefaultAccountIds(PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $rows = $pdo->query('SELECT account_id FROM accounting_payment_mode_accounts')->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_unique(array_map('intval', $rows)));
    }

    /** @return list<array<string, mixed>> */
    public static function getAll(PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $st = $pdo->query(
            'SELECT m.payment_mode, m.account_id, a.account_code, a.account_name
             FROM accounting_payment_mode_accounts m
             INNER JOIN accounts a ON a.id = m.account_id
             ORDER BY FIELD(m.payment_mode, \'CASH\', \'BANK\', \'CHEQUE\')'
        );

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, array<string, mixed>> */
    public static function getMap(PDO $pdo): array
    {
        $map = [];
        foreach (self::getAll($pdo) as $row) {
            $map[(string) $row['payment_mode']] = $row;
        }

        return $map;
    }

    /**
     * @param array<string, int> $mappings payment_mode => account_id
     * @return list<array<string, mixed>>
     */
    public static function saveMappings(PDO $pdo, array $mappings): array
    {
        self::ensureSchema($pdo);

        $upsert = $pdo->prepare(
            'INSERT INTO accounting_payment_mode_accounts (payment_mode, account_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE account_id = VALUES(account_id)'
        );

        $pdo->beginTransaction();
        try {
            foreach (self::CONFIGURABLE_MODES as $mode) {
                if (!isset($mappings[$mode])) {
                    continue;
                }
                $accountId = (int) $mappings[$mode];
                if ($accountId <= 0) {
                    throw new InvalidArgumentException('A valid account is required for ' . $mode . ' payment mode.');
                }

                $check = $pdo->prepare('SELECT id FROM accounts WHERE id = ? AND deleted_at IS NULL LIMIT 1');
                $check->execute([$accountId]);
                if (!$check->fetchColumn()) {
                    throw new InvalidArgumentException('Selected account for ' . $mode . ' is invalid.');
                }

                $upsert->execute([$mode, $accountId]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return self::getAll($pdo);
    }
}
