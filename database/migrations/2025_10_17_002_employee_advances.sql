-- Employee advances for trip expenses (not salary)
CREATE TABLE IF NOT EXISTS employee_advances (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  advance_date DATE NOT NULL,
  purpose VARCHAR(255) NULL,
  settled TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_emp_adv_employee (employee_id),
  INDEX idx_emp_adv_branch (branch_id),
  INDEX idx_emp_adv_date (advance_date),
  CONSTRAINT fk_emp_adv_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_emp_adv_branch FOREIGN KEY (branch_id) REFERENCES branches(id),
  CONSTRAINT fk_emp_adv_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_advance_payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  advance_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  paid_at DATETIME NOT NULL,
  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_adv_pay_adv (advance_id),
  INDEX idx_adv_pay_paid_at (paid_at),
  CONSTRAINT fk_adv_pay_adv FOREIGN KEY (advance_id) REFERENCES employee_advances(id) ON DELETE CASCADE,
  CONSTRAINT fk_adv_pay_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;
