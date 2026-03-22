-- Cash Book: accounts, transactions (income/expense), transfers between accounts
-- Run manually or via your migration process (CREATE IF NOT EXISTS is idempotent)

CREATE TABLE IF NOT EXISTS cashbook_accounts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  branch_id INT UNSIGNED NULL DEFAULT NULL,
  type ENUM('cash','bank','branch') NOT NULL DEFAULT 'cash',
  balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_acc_branch (branch_id),
  KEY idx_cashbook_acc_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cashbook_transactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id INT UNSIGNED NOT NULL,
  txn_type ENUM('income','expense') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  occurred_at DATETIME NOT NULL,
  notes TEXT NULL,
  parcel_id INT UNSIGNED NULL DEFAULT NULL,
  items_json TEXT NULL,
  attachment_path VARCHAR(255) NULL DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_txn_account_time (account_id, occurred_at),
  KEY idx_cashbook_txn_parcel (parcel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cashbook_transfers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  from_account_id INT UNSIGNED NOT NULL,
  to_account_id INT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  occurred_at DATETIME NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cashbook_tr_from (from_account_id, occurred_at),
  KEY idx_cashbook_tr_to (to_account_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
