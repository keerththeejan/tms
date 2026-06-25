-- Ensure account_code uniqueness on accounts table (idempotent)
-- Chart of Accounts: Sri Lanka LKR + account code integrity

SET @idx_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'accounts'
      AND index_name = 'account_code'
      AND non_unique = 0
);

SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE accounts ADD UNIQUE KEY account_code (account_code)',
    'SELECT ''accounts.account_code unique index already exists'' AS note'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
