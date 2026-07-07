<?php

declare(strict_types=1);

/**
 * Previously seeded demo vouchers for an empty dashboard.
 * Demo seeding is disabled — dashboard must reflect only real database transactions.
 */
class AccountingDashboardSeedService
{
    public static function seedIfEmpty(PDO $pdo): bool
    {
        return false;
    }
}
