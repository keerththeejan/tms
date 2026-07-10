-- Expenses ERP enhancements (run once; ExpenseSchemaRepository also applies idempotently)

CREATE TABLE IF NOT EXISTS expense_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(120) NOT NULL,
  account_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_expense_categories_code (code),
  KEY idx_expense_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default categories (ignore duplicates)
INSERT IGNORE INTO expense_categories (code, name, is_system, sort_order) VALUES
('fuel', 'Fuel', 1, 10),
('transport', 'Transport', 1, 20),
('office', 'Office Expenses', 1, 30),
('electricity', 'Electricity', 1, 40),
('water', 'Water', 1, 50),
('internet', 'Internet', 1, 60),
('telephone', 'Telephone', 1, 70),
('vehicle_repairs', 'Vehicle Repairs', 1, 80),
('vehicle_insurance', 'Vehicle Insurance', 1, 90),
('tyres', 'Tyres', 1, 100),
('staff_salary', 'Staff Salary', 1, 110),
('meals', 'Meals', 1, 120),
('accommodation', 'Accommodation', 1, 130),
('maintenance', 'Maintenance', 1, 140),
('marketing', 'Marketing', 1, 150),
('printing', 'Printing', 1, 160),
('stationery', 'Stationery', 1, 170),
('cleaning', 'Cleaning', 1, 180),
('miscellaneous', 'Miscellaneous', 1, 190);

-- expenses column additions (run individually in PHP if column exists checks needed)
ALTER TABLE expenses
  ADD COLUMN IF NOT EXISTS expense_number VARCHAR(32) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS category_id INT UNSIGNED NULL AFTER expense_type,
  ADD COLUMN IF NOT EXISTS supplier_id BIGINT UNSIGNED NULL AFTER category_id,
  ADD COLUMN IF NOT EXISTS account_id BIGINT UNSIGNED NULL AFTER supplier_id,
  ADD COLUMN IF NOT EXISTS reference_number VARCHAR(64) NULL AFTER account_id,
  ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER reference_number,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER amount,
  ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER tax_amount,
  ADD COLUMN IF NOT EXISTS total_amount DECIMAL(12,2) NULL AFTER discount_amount,
  ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) NOT NULL DEFAULT 'cash' AFTER total_amount,
  ADD COLUMN IF NOT EXISTS payment_account_id BIGINT UNSIGNED NULL AFTER payment_method,
  ADD COLUMN IF NOT EXISTS balance_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER paid_amount,
  ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER balance_amount,
  ADD COLUMN IF NOT EXISTS voucher_id BIGINT UNSIGNED NULL AFTER status,
  ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER voucher_id,
  ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by,
  ADD COLUMN IF NOT EXISTS rejected_by BIGINT UNSIGNED NULL AFTER approved_at,
  ADD COLUMN IF NOT EXISTS rejected_at DATETIME NULL AFTER rejected_by,
  ADD COLUMN IF NOT EXISTS attachment_path VARCHAR(255) NULL AFTER notes;

-- MySQL 8.0.12+ supports IF NOT EXISTS on ADD COLUMN; older WAMP may need ExpenseSchemaRepository.

CREATE UNIQUE INDEX IF NOT EXISTS uq_expenses_number ON expenses (expense_number);
CREATE INDEX IF NOT EXISTS idx_expenses_category ON expenses (category_id);
CREATE INDEX IF NOT EXISTS idx_expenses_supplier ON expenses (supplier_id);
CREATE INDEX IF NOT EXISTS idx_expenses_status ON expenses (status);
CREATE INDEX IF NOT EXISTS idx_expenses_payment_method ON expenses (payment_method);
CREATE INDEX IF NOT EXISTS idx_expenses_voucher ON expenses (voucher_id);

-- Backfill
UPDATE expenses SET total_amount = amount WHERE total_amount IS NULL;
UPDATE expenses SET payment_method = COALESCE(NULLIF(payment_mode, ''), 'cash') WHERE payment_method = 'cash' AND payment_mode IS NOT NULL AND payment_mode <> '';
UPDATE expenses SET status = 'approved' WHERE approved_by IS NOT NULL AND status = 'pending';
UPDATE expenses e
  INNER JOIN expense_categories c ON c.code = e.expense_type
  SET e.category_id = c.id
  WHERE e.category_id IS NULL AND e.expense_type IS NOT NULL AND e.expense_type <> '';
