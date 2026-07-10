-- Optional: run on existing databases if BranchRepository::ensureSchema did not run (e.g. CLI-only bootstrap).
-- Safe to run multiple times if you remove duplicate lines after first success.

ALTER TABLE `branches`
  ADD COLUMN `location` VARCHAR(255) NULL DEFAULT NULL AFTER `code`;

ALTER TABLE `branches`
  ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_main`;

ALTER TABLE `branches`
  ADD KEY `idx_branches_active` (`is_active`);

-- Letterhead / address fields (Settings → Primary / Branch 2 / Branch 3 syncs here)
ALTER TABLE `branches`
  ADD COLUMN `address_tamil` VARCHAR(500) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE `branches`
  ADD COLUMN `address_english` VARCHAR(500) NULL DEFAULT NULL AFTER `address_tamil`;

ALTER TABLE `branches`
  ADD COLUMN `phones` VARCHAR(255) NULL DEFAULT NULL AFTER `address_english`;

ALTER TABLE `branches`
  ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

ALTER TABLE `branches`
  ADD COLUMN `settings_slot` TINYINT NULL DEFAULT NULL COMMENT '0-2 Settings letterhead slots' AFTER `phones`;
