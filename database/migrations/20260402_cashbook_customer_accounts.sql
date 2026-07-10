-- Cash Book: customer-linked accounts and account status
-- The app applies this automatically via CashbookRepository::migrateCashbookAccountsExtras() on bootstrap.
-- This file is idempotent: safe to run multiple times in phpMyAdmin / CLI if your DB user has ALTER privileges.

-- --- customer_id (after sort_order when that column exists) ---
SET @dbname = DATABASE();
SET @has_customer_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'customer_id'
);
SET @has_sort_order := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'sort_order'
);
SET @sql := IF(
  @has_customer_id = 0 AND @has_sort_order > 0,
  'ALTER TABLE cashbook_accounts ADD COLUMN customer_id INT UNSIGNED NULL DEFAULT NULL AFTER sort_order',
  IF(
    @has_customer_id = 0,
    'ALTER TABLE cashbook_accounts ADD COLUMN customer_id INT UNSIGNED NULL DEFAULT NULL',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- status (after customer_id when present, else after sort_order) ---
SET @has_sort_order := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'sort_order'
);
SET @has_status := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'status'
);
SET @has_customer_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND COLUMN_NAME = 'customer_id'
);
SET @sql := IF(
  @has_status > 0,
  'SELECT 1',
  IF(
    @has_customer_id > 0,
    'ALTER TABLE cashbook_accounts ADD COLUMN status ENUM(''active'',''inactive'') NOT NULL DEFAULT ''active'' AFTER customer_id',
    IF(
      @has_sort_order > 0,
      'ALTER TABLE cashbook_accounts ADD COLUMN status ENUM(''active'',''inactive'') NOT NULL DEFAULT ''active'' AFTER sort_order',
      'ALTER TABLE cashbook_accounts ADD COLUMN status ENUM(''active'',''inactive'') NOT NULL DEFAULT ''active'''
    )
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE cashbook_accounts
  MODIFY COLUMN type ENUM('cash','bank','branch','customer') NOT NULL DEFAULT 'cash';

-- Unique / indexes (ignore errors if they already exist — run in app or wrap below if needed)
SET @uq := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND INDEX_NAME = 'uq_cashbook_acc_customer'
);
SET @sql := IF(@uq = 0, 'ALTER TABLE cashbook_accounts ADD UNIQUE KEY uq_cashbook_acc_customer (customer_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ix := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_accounts' AND INDEX_NAME = 'idx_cashbook_acc_status'
);
SET @sql := IF(@ix = 0, 'ALTER TABLE cashbook_accounts ADD KEY idx_cashbook_acc_status (status)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Transfers: optional audit (user id)
SET @has_created_by := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'cashbook_transfers' AND COLUMN_NAME = 'created_by'
);
SET @sql := IF(
  @has_created_by = 0,
  'ALTER TABLE cashbook_transfers ADD COLUMN created_by INT UNSIGNED NULL DEFAULT NULL AFTER notes',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
