-- Add credit-related fields to expenses and create expense_payments table
ALTER TABLE expenses
  ADD COLUMN payment_mode ENUM('cash','credit') NOT NULL DEFAULT 'cash' AFTER expense_type,
  ADD COLUMN credit_party VARCHAR(150) NULL AFTER notes,
  ADD COLUMN credit_due_date DATE NULL AFTER credit_party,
  ADD COLUMN credit_settled TINYINT(1) NOT NULL DEFAULT 0 AFTER credit_due_date;

CREATE TABLE IF NOT EXISTS expense_payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  expense_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  paid_at DATETIME NOT NULL,
  paid_by BIGINT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_exp_pay_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
  CONSTRAINT fk_exp_pay_user FOREIGN KEY (paid_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE INDEX idx_exp_pay_expense ON expense_payments(expense_id);
CREATE INDEX idx_exp_pay_paid_at ON expense_payments(paid_at);

-- Optional backfill: mark credit_settled for existing rows that are cash
UPDATE expenses SET credit_settled = 1 WHERE payment_mode = 'cash';
