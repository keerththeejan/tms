-- Cash book: account_kind for main vs customer receivable mapping
-- Auto-applied via CashbookRepository::migrateCashbookAccountsExtras() on bootstrap.
-- Idempotent: safe to run multiple times.

SET @dbname = DATABASE();

SET @has_account_kind := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'account_kind'
);
SET @has_type := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'type'
);

SET @sql := IF(
  @has_account_kind > 0,
  'SELECT 1',
  IF(
    @has_type > 0,
    'ALTER TABLE cashbook_accounts ADD COLUMN account_kind ENUM(''cash'',''bank'',''digital'',''receivable'') NULL DEFAULT NULL AFTER type',
    'ALTER TABLE cashbook_accounts ADD COLUMN account_kind ENUM(''cash'',''bank'',''digital'',''receivable'') NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE cashbook_accounts
SET account_kind = CASE type
  WHEN 'cash' THEN 'cash'
  WHEN 'bank' THEN 'bank'
  WHEN 'branch' THEN 'digital'
  WHEN 'customer' THEN 'receivable'
END
WHERE account_kind IS NULL;
