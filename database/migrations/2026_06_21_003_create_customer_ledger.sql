-- Customer Ledger: links customers to Accounts Receivable (SUNDRY_DEBTORS) accounts
CREATE TABLE IF NOT EXISTS customer_ledger (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id bigint unsigned NOT NULL,
  account_id bigint unsigned NOT NULL,
  ledger_code varchar(30) NOT NULL,
  ledger_type varchar(50) NOT NULL DEFAULT 'Accounts Receivable',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_customer_ledger_customer (customer_id),
  UNIQUE KEY uq_customer_ledger_account (account_id),
  UNIQUE KEY uq_customer_ledger_code (ledger_code),
  KEY idx_customer_ledger_code (ledger_code),
  CONSTRAINT fk_customer_ledger_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_customer_ledger_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
