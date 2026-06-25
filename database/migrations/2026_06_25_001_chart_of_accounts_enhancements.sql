-- Chart of Accounts enhancements (idempotent — safe to run multiple times)
-- Adds optional account group metadata columns used by the COA modal.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'account_groups' AND column_name = 'description') = 0,
  'ALTER TABLE account_groups ADD COLUMN description varchar(500) NULL AFTER sort_order',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'account_groups' AND column_name = 'is_active') = 0,
  'ALTER TABLE account_groups ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1 AFTER description',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
