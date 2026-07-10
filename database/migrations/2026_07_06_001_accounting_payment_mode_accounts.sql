-- Default payment mode → main account mappings for voucher auto-posting
CREATE TABLE IF NOT EXISTS accounting_payment_mode_accounts (
  payment_mode varchar(20) NOT NULL PRIMARY KEY,
  account_id bigint unsigned NOT NULL,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_apma_account (account_id),
  CONSTRAINT fk_apma_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cheque clearing account (seeded if missing)
INSERT INTO accounts (account_code, account_name, account_group_id, opening_balance, opening_balance_type, is_system)
SELECT 'CHEQUE_CLEARING', 'Cheque Clearing Account', g.id, 0, 'DEBIT', 1
FROM account_groups g
WHERE g.group_code = 'BANK'
  AND NOT EXISTS (SELECT 1 FROM accounts WHERE account_code = 'CHEQUE_CLEARING')
LIMIT 1;

INSERT INTO accounting_payment_mode_accounts (payment_mode, account_id)
SELECT 'CASH', a.id FROM accounts a WHERE a.account_code = 'CASH_MAIN'
  AND NOT EXISTS (SELECT 1 FROM accounting_payment_mode_accounts WHERE payment_mode = 'CASH')
LIMIT 1;

INSERT INTO accounting_payment_mode_accounts (payment_mode, account_id)
SELECT 'BANK', a.id FROM accounts a WHERE a.account_code = 'BANK_MAIN'
  AND NOT EXISTS (SELECT 1 FROM accounting_payment_mode_accounts WHERE payment_mode = 'BANK')
LIMIT 1;

INSERT INTO accounting_payment_mode_accounts (payment_mode, account_id)
SELECT 'CHEQUE', a.id FROM accounts a WHERE a.account_code = 'CHEQUE_CLEARING'
  AND NOT EXISTS (SELECT 1 FROM accounting_payment_mode_accounts WHERE payment_mode = 'CHEQUE')
LIMIT 1;
