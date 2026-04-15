-- Optional manual patch (usually applied automatically via CashbookRepository::ensureSchema).
-- Links cashbook_accounts to employees for HR / payroll ledgers.

ALTER TABLE cashbook_accounts
  ADD COLUMN employee_id INT UNSIGNED NULL DEFAULT NULL AFTER supplier_id;

ALTER TABLE cashbook_accounts
  MODIFY COLUMN type ENUM('cash','bank','branch','customer','supplier','employee') NOT NULL DEFAULT 'cash';

ALTER TABLE cashbook_accounts
  ADD UNIQUE KEY uq_cashbook_acc_employee (employee_id);
