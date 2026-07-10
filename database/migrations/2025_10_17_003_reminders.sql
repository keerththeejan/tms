-- Reminders for bills (insurance, tax, electricity, etc.)
CREATE TABLE IF NOT EXISTS reminders (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  category VARCHAR(60) NULL, -- e.g., insurance, tax, electricity, license
  due_date DATE NOT NULL,
  repeat_interval ENUM('none','monthly','quarterly','yearly') NOT NULL DEFAULT 'none',
  repeat_every_days INT NULL,
  notify_before_days INT NOT NULL DEFAULT 7,
  notes VARCHAR(255) NULL,
  status ENUM('open','done') NOT NULL DEFAULT 'open',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rem_due (due_date),
  INDEX idx_rem_cat (category)
) ENGINE=InnoDB;
