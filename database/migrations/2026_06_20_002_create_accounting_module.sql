-- Accounting Module Database Schema
-- Desktop Accounting Style (BUSY/Tally ERP)
-- Double Entry Accounting System

-- Account Groups Table
CREATE TABLE IF NOT EXISTS account_groups (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_code varchar(20) NOT NULL UNIQUE,
  group_name varchar(100) NOT NULL,
  parent_id bigint unsigned NULL,
  group_type enum('ASSETS', 'LIABILITIES', 'CAPITAL', 'INCOME', 'EXPENSES') NOT NULL,
  nature enum('DEBIT', 'CREDIT') NOT NULL,
  is_primary tinyint(1) NOT NULL DEFAULT 0,
  is_system tinyint(1) NOT NULL DEFAULT 0,
  sort_order int NOT NULL DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL,
  KEY idx_group_code (group_code),
  KEY idx_parent_id (parent_id),
  KEY idx_group_type (group_type),
  KEY idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Account Groups
INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order) VALUES
('ASSETS', 'Assets', NULL, 'ASSETS', 'DEBIT', 1, 1, 1),
('LIABILITIES', 'Liabilities', NULL, 'LIABILITIES', 'CREDIT', 1, 1, 2),
('CAPITAL', 'Capital', NULL, 'CAPITAL', 'CREDIT', 1, 1, 3),
('INCOME', 'Income', NULL, 'INCOME', 'CREDIT', 1, 1, 4),
('EXPENSES', 'Expenses', NULL, 'EXPENSES', 'DEBIT', 1, 1, 5),
('CURRENT_ASSETS', 'Current Assets', (SELECT id FROM account_groups WHERE group_code = 'ASSETS'), 'ASSETS', 'DEBIT', 0, 1, 10),
('FIXED_ASSETS', 'Fixed Assets', (SELECT id FROM account_groups WHERE group_code = 'ASSETS'), 'ASSETS', 'DEBIT', 0, 1, 11),
('CURRENT_LIABILITIES', 'Current Liabilities', (SELECT id FROM account_groups WHERE group_code = 'LIABILITIES'), 'LIABILITIES', 'CREDIT', 0, 1, 20),
('LONG_TERM_LIABILITIES', 'Long Term Liabilities', (SELECT id FROM account_groups WHERE group_code = 'LIABILITIES'), 'LIABILITIES', 'CREDIT', 0, 1, 21),
('CASH', 'Cash', (SELECT id FROM account_groups WHERE group_code = 'CURRENT_ASSETS'), 'ASSETS', 'DEBIT', 0, 1, 100),
('BANK', 'Bank', (SELECT id FROM account_groups WHERE group_code = 'CURRENT_ASSETS'), 'ASSETS', 'DEBIT', 0, 1, 101),
('SUNDRY_DEBTORS', 'Sundry Debtors', (SELECT id FROM account_groups WHERE group_code = 'CURRENT_ASSETS'), 'ASSETS', 'DEBIT', 0, 1, 102),
('SUNDRY_CREDITORS', 'Sundry Creditors', (SELECT id FROM account_groups WHERE group_code = 'CURRENT_LIABILITIES'), 'LIABILITIES', 'CREDIT', 0, 1, 200),
('FUEL_EXPENSES', 'Fuel Expenses', (SELECT id FROM account_groups WHERE group_code = 'EXPENSES'), 'EXPENSES', 'DEBIT', 0, 1, 300),
('VEHICLE_EXPENSES', 'Vehicle Expenses', (SELECT id FROM account_groups WHERE group_code = 'EXPENSES'), 'EXPENSES', 'DEBIT', 0, 1, 301),
('DRIVER_SALARY', 'Driver Salary', (SELECT id FROM account_groups WHERE group_code = 'EXPENSES'), 'EXPENSES', 'DEBIT', 0, 1, 302),
('SALES_INCOME', 'Sales Income', (SELECT id FROM account_groups WHERE group_code = 'INCOME'), 'INCOME', 'CREDIT', 0, 1, 400),
('SERVICE_INCOME', 'Service Income', (SELECT id FROM account_groups WHERE group_code = 'INCOME'), 'INCOME', 'CREDIT', 0, 1, 401);

-- Accounts Table (Chart of Accounts)
CREATE TABLE IF NOT EXISTS accounts (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  account_code varchar(30) NOT NULL UNIQUE,
  account_name varchar(150) NOT NULL,
  account_group_id bigint unsigned NOT NULL,
  opening_balance decimal(15,2) NOT NULL DEFAULT 0.00,
  opening_balance_type enum('DEBIT', 'CREDIT') NOT NULL DEFAULT 'DEBIT',
  current_balance decimal(15,2) NOT NULL DEFAULT 0.00,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  is_system tinyint(1) NOT NULL DEFAULT 0,
  branch_id bigint unsigned NULL,
  created_by bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL,
  KEY idx_account_code (account_code),
  KEY idx_account_name (account_name),
  KEY idx_account_group_id (account_group_id),
  KEY idx_branch_id (branch_id),
  KEY idx_is_active (is_active),
  FOREIGN KEY (account_group_id) REFERENCES account_groups(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Accounts
INSERT IGNORE INTO accounts (account_code, account_name, account_group_id, opening_balance, opening_balance_type, is_system) VALUES
('CASH_MAIN', 'Main Cash Account', (SELECT id FROM account_groups WHERE group_code = 'CASH'), 0.00, 'DEBIT', 1),
('CASH_PETTY', 'Petty Cash', (SELECT id FROM account_groups WHERE group_code = 'CASH'), 0.00, 'DEBIT', 1),
('BANK_MAIN', 'Main Bank Account', (SELECT id FROM account_groups WHERE group_code = 'BANK'), 0.00, 'DEBIT', 1),
('BANK_SAVINGS', 'Savings Bank Account', (SELECT id FROM account_groups WHERE group_code = 'BANK'), 0.00, 'DEBIT', 1),
('CAPITAL_OWNER', 'Owner Capital', (SELECT id FROM account_groups WHERE group_code = 'CAPITAL'), 0.00, 'CREDIT', 1),
('FUEL_DIESEL', 'Diesel Fuel Expense', (SELECT id FROM account_groups WHERE group_code = 'FUEL_EXPENSES'), 0.00, 'DEBIT', 1),
('FUEL_PETROL', 'Petrol Fuel Expense', (SELECT id FROM account_groups WHERE group_code = 'FUEL_EXPENSES'), 0.00, 'DEBIT', 1),
('VEH_MAINTENANCE', 'Vehicle Maintenance', (SELECT id FROM account_groups WHERE group_code = 'VEHICLE_EXPENSES'), 0.00, 'DEBIT', 1),
('VEH_REPAIRS', 'Vehicle Repairs', (SELECT id FROM account_groups WHERE group_code = 'VEHICLE_EXPENSES'), 0.00, 'DEBIT', 1),
('DRIVER_SALARY_WAGES', 'Driver Salary & Wages', (SELECT id FROM account_groups WHERE group_code = 'DRIVER_SALARY'), 0.00, 'DEBIT', 1),
('SALES_FREIGHT', 'Freight Sales', (SELECT id FROM account_groups WHERE group_code = 'SALES_INCOME'), 0.00, 'CREDIT', 1),
('SALES_LOADING', 'Loading Charges', (SELECT id FROM account_groups WHERE group_code = 'SALES_INCOME'), 0.00, 'CREDIT', 1);

-- Vouchers Table
CREATE TABLE IF NOT EXISTS vouchers (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_number varchar(50) NOT NULL UNIQUE,
  voucher_type enum('PAYMENT', 'RECEIPT', 'JOURNAL', 'CONTRA', 'TRANSFER') NOT NULL,
  series_id bigint unsigned NULL,
  voucher_date date NOT NULL,
  fiscal_year varchar(10) NOT NULL,
  reference_number varchar(50) NULL,
  payment_mode enum('CASH', 'BANK', 'CHEQUE', 'ONLINE', 'OTHER') NOT NULL DEFAULT 'CASH',
  cheque_number varchar(50) NULL,
  cheque_date date NULL,
  bank_account_id bigint unsigned NULL,
  narration text NULL,
  total_debit decimal(15,2) NOT NULL DEFAULT 0.00,
  total_credit decimal(15,2) NOT NULL DEFAULT 0.00,
  status enum('DRAFT', 'POSTED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
  posted_at timestamp NULL,
  posted_by bigint unsigned NULL,
  cancelled_at timestamp NULL,
  cancelled_by bigint unsigned NULL,
  cancellation_reason text NULL,
  branch_id bigint unsigned NULL,
  created_by bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL,
  KEY idx_voucher_number (voucher_number),
  KEY idx_voucher_type (voucher_type),
  KEY idx_voucher_date (voucher_date),
  KEY idx_fiscal_year (fiscal_year),
  KEY idx_series_id (series_id),
  KEY idx_status (status),
  KEY idx_branch_id (branch_id),
  KEY idx_created_at (created_at),
  FOREIGN KEY (bank_account_id) REFERENCES accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Voucher Series Table (for automatic numbering)
CREATE TABLE IF NOT EXISTS voucher_series (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  series_name varchar(50) NOT NULL UNIQUE,
  voucher_type enum('PAYMENT', 'RECEIPT', 'JOURNAL', 'CONTRA', 'TRANSFER') NOT NULL,
  prefix varchar(20) NOT NULL,
  starting_number int NOT NULL DEFAULT 1,
  current_number int NOT NULL DEFAULT 0,
  reset_type enum('NONE', 'YEARLY', 'MONTHLY') NOT NULL DEFAULT 'YEARLY',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  branch_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_series_name (series_name),
  KEY idx_voucher_type (voucher_type),
  KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Voucher Series
INSERT INTO voucher_series (series_name, voucher_type, prefix, starting_number, current_number, reset_type, is_active) VALUES
('PAYMENT_SERIES', 'PAYMENT', 'PY-', 1, 0, 'YEARLY', 1),
('RECEIPT_SERIES', 'RECEIPT', 'RC-', 1, 0, 'YEARLY', 1),
('JOURNAL_SERIES', 'JOURNAL', 'JR-', 1, 0, 'YEARLY', 1),
('CONTRA_SERIES', 'CONTRA', 'CT-', 1, 0, 'YEARLY', 1),
('TRANSFER_SERIES', 'TRANSFER', 'TR-', 1, 0, 'YEARLY', 1);

-- Voucher Details Table (Line Items)
CREATE TABLE IF NOT EXISTS voucher_details (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  line_number int NOT NULL,
  account_id bigint unsigned NOT NULL,
  debit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  credit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  narration varchar(255) NULL,
  cost_center_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_account_id (account_id),
  KEY idx_line_number (line_number),
  FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ledger Entries Table (Double Entry Bookkeeping)
CREATE TABLE IF NOT EXISTS ledger_entries (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  voucher_detail_id bigint unsigned NULL,
  account_id bigint unsigned NOT NULL,
  entry_date date NOT NULL,
  voucher_type enum('PAYMENT', 'RECEIPT', 'JOURNAL', 'CONTRA', 'TRANSFER') NOT NULL,
  voucher_number varchar(50) NOT NULL,
  debit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  credit_amount decimal(15,2) NOT NULL DEFAULT 0.00,
  balance_type enum('DEBIT', 'CREDIT') NOT NULL,
  running_balance decimal(15,2) NOT NULL DEFAULT 0.00,
  narration text NULL,
  reference_id bigint unsigned NULL,
  reference_type varchar(50) NULL,
  branch_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_account_id (account_id),
  KEY idx_entry_date (entry_date),
  KEY idx_voucher_number (voucher_number),
  KEY idx_voucher_type (voucher_type),
  KEY idx_branch_id (branch_id),
  KEY idx_reference (reference_id, reference_type),
  FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cost Centers Table (Optional - for expense tracking)
CREATE TABLE IF NOT EXISTS cost_centers (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cost_center_code varchar(20) NOT NULL UNIQUE,
  cost_center_name varchar(100) NOT NULL,
  parent_id bigint unsigned NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  branch_id bigint unsigned NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cost_center_code (cost_center_code),
  KEY idx_parent_id (parent_id),
  KEY idx_branch_id (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log Table
CREATE TABLE IF NOT EXISTS accounting_audit_log (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity_type varchar(50) NOT NULL,
  entity_id bigint unsigned NOT NULL,
  action varchar(50) NOT NULL,
  old_values json NULL,
  new_values json NULL,
  user_id bigint unsigned NULL,
  ip_address varchar(45) NULL,
  user_agent varchar(255) NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id),
  KEY idx_action (action),
  KEY idx_user_id (user_id),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transport Integration References Table
CREATE TABLE IF NOT EXISTS transport_voucher_mapping (
  id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  voucher_id bigint unsigned NOT NULL,
  transport_type enum('FUEL', 'VEHICLE_EXPENSE', 'CUSTOMER_INVOICE', 'SUPPLIER_PAYMENT', 'DRIVER_SALARY') NOT NULL,
  transport_id bigint unsigned NOT NULL,
  mapping_details json NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_voucher_id (voucher_id),
  KEY idx_transport (transport_type, transport_id),
  FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
