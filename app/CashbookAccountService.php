<?php

declare(strict_types=1);

/**
 * Accounts management facade: CRUD, income/expense lines, transfers, customer links.
 * Maps to {@see CashbookRepository} and {@see cashbook_accounts} / {@see cashbook_transactions} / {@see cashbook_transfers}.
 */
final class CashbookAccountService
{
    public static function createAccount(\PDO $pdo, string $name, string $type, ?int $branchId, ?int $customerId = null, string $status = 'active', float $openingBalance = 0.0, ?int $supplierId = null, ?string $description = null): int
    {
        return CashbookRepository::createAccount($pdo, $name, $type, $branchId, $customerId, $status, $openingBalance, $supplierId, $description);
    }

    public static function updateAccount(\PDO $pdo, int $id, string $name, string $type, ?int $branchId, string $status = 'active', ?float $openingBalance = null, ?string $description = null): void
    {
        CashbookRepository::updateAccount($pdo, $id, $name, $type, $branchId, $status, $openingBalance, $description);
    }

    public static function deleteAccount(\PDO $pdo, int $id): bool
    {
        return CashbookRepository::deleteAccount($pdo, $id);
    }

    /** @return list<array<string,mixed>> */
    public static function getAllAccounts(\PDO $pdo, bool $activeOnly = false): array
    {
        return CashbookRepository::listAccounts($pdo, $activeOnly);
    }

    /** Alias for {@see getAllAccounts()} — API-style name. */
    public static function getAccounts(\PDO $pdo, bool $activeOnly = false): array
    {
        return self::getAllAccounts($pdo, $activeOnly);
    }

    /**
     * Ledger lines for one account (income/expense + transfer in/out), merged and sorted.
     *
     * @return list<array<string,mixed>>
     */
    public static function getTransactionsForAccount(\PDO $pdo, int $accountId, string $fromDt, string $toDt, string $q = ''): array
    {
        return CashbookRepository::listMergedEntries($pdo, $accountId, $fromDt, $toDt, $q);
    }

    /** @return array<string,mixed>|null */
    public static function getAccountById(\PDO $pdo, int $id): ?array
    {
        return CashbookRepository::getAccount($pdo, $id);
    }

    public static function addIncome(\PDO $pdo, int $accountId, float $amount, ?string $note, ?string $occurredAt = null): void
    {
        if ($accountId <= 0 || $amount <= 0) {
            throw new \InvalidArgumentException('Account and positive amount required.');
        }
        $at = $occurredAt ?? date('Y-m-d H:i:s');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $at)) {
            $at .= ' 12:00:00';
        }
        CashbookRepository::addTransaction($pdo, $accountId, 'income', $amount, $at, $note, null, null, null);
    }

    public static function addExpense(\PDO $pdo, int $accountId, float $amount, ?string $note, ?string $occurredAt = null): void
    {
        if ($accountId <= 0 || $amount <= 0) {
            throw new \InvalidArgumentException('Account and positive amount required.');
        }
        $at = $occurredAt ?? date('Y-m-d H:i:s');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $at)) {
            $at .= ' 12:00:00';
        }
        CashbookRepository::addTransaction($pdo, $accountId, 'expense', $amount, $at, $note, null, null, null);
    }

    public static function transferAmount(\PDO $pdo, int $fromAccountId, int $toAccountId, float $amount, ?string $note, bool $preventNegative = true, ?int $createdBy = null, ?string $occurredAt = null): void
    {
        if ($fromAccountId <= 0 || $toAccountId <= 0 || $fromAccountId === $toAccountId || $amount <= 0) {
            throw new \InvalidArgumentException('Invalid transfer.');
        }
        $at = $occurredAt ?? date('Y-m-d H:i:s');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $at)) {
            $at .= ' 12:00:00';
        }
        CashbookRepository::addTransfer($pdo, $fromAccountId, $toAccountId, $amount, $at, $note, $preventNegative, $createdBy);
    }

    /** Alias for {@see transferAmount()} — matches common accounting API naming. */
    public static function transferMoney(\PDO $pdo, int $fromAccountId, int $toAccountId, float $amount, ?string $note, bool $preventNegative = true, ?int $createdBy = null, ?string $occurredAt = null): void
    {
        self::transferAmount($pdo, $fromAccountId, $toAccountId, $amount, $note, $preventNegative, $createdBy, $occurredAt);
    }

    public static function createCustomerAccount(\PDO $pdo, int $customerId, string $customerName): int
    {
        return CashbookRepository::ensureCustomerAccount($pdo, $customerId, $customerName);
    }

    /** @return array{id: int, created: bool} */
    public static function ensureEmployeeAccount(\PDO $pdo, int $employeeId, string $displayName, string $empStatus = 'active'): array
    {
        return CashbookRepository::ensureEmployeeAccount($pdo, $employeeId, $displayName, $empStatus);
    }
}
