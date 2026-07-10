-- Premium Payment Voucher System - Database Schema
-- Enterprise-level accounting module for Payment Management

-- ============================================================
-- 1. PAYMENT VOUCHERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `voucher_number` varchar(50) NOT NULL UNIQUE KEY COMMENT 'Unique voucher number - auto-generated',
  `voucher_type` enum('RECEIPT', 'PAYMENT', 'JOURNAL', 'TRANSFER', 'CONTRA') NOT NULL DEFAULT 'PAYMENT' COMMENT 'Type of voucher',
  `fiscal_year` varchar(10) NOT NULL COMMENT 'Fiscal year for validation',
  `voucher_date` date NOT NULL COMMENT 'Date of voucher transaction',
  `payment_mode` enum('CASH', 'BANK', 'CHEQUE', 'ONLINE', 'PETTY_CASH', 'OTHER') NOT NULL DEFAULT 'CASH' COMMENT 'Mode of payment',
  
  -- Cheque specific fields
  `cheque_number` varchar(50) NULL COMMENT 'Cheque number if payment mode is CHEQUE',
  `cheque_date` date NULL COMMENT 'Cheque maturity date',
  `cheque_bank` varchar(100) NULL COMMENT 'Cheque issuing bank',
  
  -- Reference fields
  `reference_number` varchar(100) NULL COMMENT 'External reference (PO, Bill, etc)',
  `narration` text NULL COMMENT 'Description/memo for the voucher',
  
  -- Status and approval workflow
  `status` enum('DRAFT', 'SUBMITTED', 'APPROVED', 'POSTED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT' COMMENT 'Voucher status',
  `approval_status` enum('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING' COMMENT 'Approval workflow status',
  
  -- Totals and balancing
  `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total debit amount',
  `total_credit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total credit amount',
  `balance_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Remaining balance to balance',
  
  -- User tracking
  `created_by` bigint unsigned NULL COMMENT 'User who created voucher',
  `approved_by` bigint unsigned NULL COMMENT 'User who approved voucher',
  `posted_by` bigint unsigned NULL COMMENT 'User who posted voucher',
  
  -- Timestamps
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `posted_at` timestamp NULL COMMENT 'When voucher was posted to ledger',
  `deleted_at` timestamp NULL COMMENT 'Soft delete timestamp',
  
  -- Indexes
  KEY `idx_voucher_date` (`voucher_date`),
  KEY `idx_status` (`status`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_fiscal_year` (`fiscal_year`),
  KEY `idx_payment_mode` (`payment_mode`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_voucher_type` (`voucher_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master voucher records';

-- ============================================================
-- 2. VOUCHER LINE ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `voucher_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to voucher',
  `line_number` int NOT NULL COMMENT 'Line item sequence number',
  
  -- Account/Ledger references
  `ledger_account_id` bigint unsigned NULL COMMENT 'Reference to ledger account',
  `account_name` varchar(255) NOT NULL COMMENT 'Account name for quick display',
  `account_code` varchar(50) NOT NULL COMMENT 'Account code/GL code',
  
  -- Optional entity references (for employee/customer/supplier payments)
  `employee_id` bigint unsigned NULL COMMENT 'If payment to employee',
  `customer_id` bigint unsigned NULL COMMENT 'If payment to/from customer',
  `supplier_id` bigint unsigned NULL COMMENT 'If payment to supplier',
  
  -- Debit/Credit amounts
  `debit_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Debit amount for this line',
  `credit_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Credit amount for this line',
  
  -- Description
  `description` text NULL COMMENT 'Line item description/narration',
  
  -- Tracking
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Constraints
  CONSTRAINT `fk_voucher_items_voucher` FOREIGN KEY (`voucher_id`) 
    REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_voucher_items_employee` FOREIGN KEY (`employee_id`) 
    REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  
  -- Indexes
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_account_code` (`account_code`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_line_number` (`line_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Line items for each voucher - double-entry rows';

-- ============================================================
-- 3. VOUCHER APPROVAL WORKFLOW TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `voucher_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` bigint unsigned NOT NULL UNIQUE KEY COMMENT 'Reference to voucher',
  
  -- Approval levels
  `first_approver_id` bigint unsigned NULL COMMENT 'First level approver',
  `first_approval_at` timestamp NULL COMMENT 'First approval timestamp',
  `first_approval_notes` text NULL COMMENT 'First approver comments',
  
  `second_approver_id` bigint unsigned NULL COMMENT 'Second level approver',
  `second_approval_at` timestamp NULL COMMENT 'Second approval timestamp',
  `second_approval_notes` text NULL COMMENT 'Second approver comments',
  
  `final_approver_id` bigint unsigned NULL COMMENT 'Final approver',
  `final_approval_at` timestamp NULL COMMENT 'Final approval timestamp',
  `final_approval_notes` text NULL COMMENT 'Final approver comments',
  
  -- Rejection tracking
  `rejected_by_id` bigint unsigned NULL COMMENT 'User who rejected',
  `rejected_at` timestamp NULL COMMENT 'Rejection timestamp',
  `rejection_reason` text NULL COMMENT 'Reason for rejection',
  
  -- Timestamps
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT `fk_voucher_approvals_voucher` FOREIGN KEY (`voucher_id`) 
    REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  
  KEY `idx_voucher_id` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Approval workflow tracking for vouchers';

-- ============================================================
-- 4. EMPLOYEE PAYMENT TRACKING TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `employee_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to payment voucher',
  `employee_id` bigint unsigned NOT NULL COMMENT 'Employee being paid',
  
  -- Payment breakdown
  `salary_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Salary component',
  `advance_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Advance payment',
  `bonus_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Bonus/incentive',
  `ot_payment` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Overtime payment',
  `allowance_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Allowances',
  `deduction_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Deductions (loans, etc)',
  `total_payment` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total payment amount',
  
  -- Employee balance tracking
  `employee_balance_before` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Employee ledger balance before payment',
  `employee_balance_after` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Employee ledger balance after payment',
  
  -- Payment tracking
  `payment_date` date NOT NULL COMMENT 'Date payment was made',
  `payment_status` enum('PENDING', 'POSTED', 'RECONCILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  
  -- Timestamps
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT `fk_employee_payments_voucher` FOREIGN KEY (`voucher_id`) 
    REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_employee_payments_employee` FOREIGN KEY (`employee_id`) 
    REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee payment tracking and breakdown';

-- ============================================================
-- 5. TRANSACTION AUDIT LOG TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `transaction_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to voucher',
  
  -- Action tracking
  `action` varchar(50) NOT NULL COMMENT 'Action performed (CREATE, UPDATE, DELETE, APPROVE, REJECT, POST, etc)',
  `action_type` enum('CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'POST', 'REVERT', 'EXPORT') NOT NULL DEFAULT 'CREATE',
  
  -- Change tracking
  `old_values` json NULL COMMENT 'Previous values (JSON)',
  `new_values` json NULL COMMENT 'New values (JSON)',
  `changed_fields` json NULL COMMENT 'Array of changed field names',
  
  -- User and context
  `user_id` bigint unsigned NULL COMMENT 'User who performed action',
  `ip_address` varchar(45) NULL COMMENT 'IP address of user',
  `user_agent` varchar(500) NULL COMMENT 'Browser user agent',
  `session_id` varchar(100) NULL COMMENT 'Session identifier',
  
  -- Additional tracking
  `reason` text NULL COMMENT 'Reason for action (approval/rejection/etc)',
  `status_before` varchar(50) NULL COMMENT 'Status before action',
  `status_after` varchar(50) NULL COMMENT 'Status after action',
  
  -- Timestamp
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT `fk_transaction_audit_voucher` FOREIGN KEY (`voucher_id`) 
    REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for all voucher transactions';

-- ============================================================
-- 6. LEDGER ACCOUNTS TABLE (Core Accounting)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ledger_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `account_code` varchar(50) NOT NULL UNIQUE KEY COMMENT 'Unique account code/GL code',
  `account_name` varchar(255) NOT NULL COMMENT 'Account name',
  `account_type` enum('ASSET', 'LIABILITY', 'EQUITY', 'INCOME', 'EXPENSE', 'BANK', 'CASH', 'CUSTOMER', 'SUPPLIER', 'EMPLOYEE', 'SUSPENSE') NOT NULL COMMENT 'Type of account',
  
  -- Account hierarchy
  `parent_account_id` bigint unsigned NULL COMMENT 'Parent account for hierarchy',
  `account_level` int NOT NULL DEFAULT 1 COMMENT 'Hierarchy level',
  
  -- Balances (debit/credit basis)
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Current account balance',
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Opening balance',
  
  -- Configuration
  `is_active` boolean NOT NULL DEFAULT 1 COMMENT 'Whether account is active',
  `allow_manual_entry` boolean NOT NULL DEFAULT 1 COMMENT 'Allow manual journal entries',
  `is_header` boolean NOT NULL DEFAULT 0 COMMENT 'Is this a header/group account',
  
  -- Description
  `description` text NULL COMMENT 'Account description',
  
  -- Tracking
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  KEY `idx_account_code` (`account_code`),
  KEY `idx_account_type` (`account_type`),
  KEY `idx_parent_account_id` (`parent_account_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master ledger accounts for accounting system';

-- ============================================================
-- 7. VOUCHER DRAFT AUTO-SAVE TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `voucher_drafts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` bigint unsigned NOT NULL COMMENT 'User creating draft',
  `voucher_id` bigint unsigned NULL COMMENT 'Reference to voucher if linked',
  
  -- Draft data (JSON)
  `draft_data` json NOT NULL COMMENT 'Complete voucher data as JSON',
  `draft_name` varchar(255) NULL COMMENT 'User-given name for draft',
  
  -- Status
  `status` enum('ACTIVE', 'ARCHIVED', 'CONVERTED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Draft status',
  
  -- Timestamps
  `last_saved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last auto-save time',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` timestamp NULL COMMENT 'When draft expires (30 days after creation)',
  
  KEY `idx_user_id` (`user_id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-save drafts for voucher entries';

-- ============================================================
-- 8. INDICES FOR PERFORMANCE
-- ============================================================
CREATE INDEX idx_vouchers_search ON vouchers(voucher_date, status, created_by, deleted_at);
CREATE INDEX idx_voucher_items_search ON voucher_items(voucher_id, account_code, employee_id);
CREATE INDEX idx_audit_search ON transaction_audit_logs(voucher_id, created_at, user_id);

-- ============================================================
-- INITIAL DATA: Core Ledger Accounts
-- ============================================================
INSERT INTO `ledger_accounts` (account_code, account_name, account_type, is_active) VALUES
-- Asset Accounts
('1001', 'Cash on Hand', 'CASH', 1),
('1002', 'Bank Account', 'BANK', 1),
('1003', 'Petty Cash', 'PETTY_CASH', 1),

-- Expense Accounts
('5001', 'Salary Expense', 'EXPENSE', 1),
('5002', 'Employee Advances', 'EXPENSE', 1),
('5003', 'Bonus & Incentives', 'EXPENSE', 1),
('5004', 'Travel Expense', 'EXPENSE', 1),
('5005', 'Utilities', 'EXPENSE', 1),
('5006', 'Office Supplies', 'EXPENSE', 1),

-- Income Accounts
('4001', 'Sales Revenue', 'INCOME', 1),
('4002', 'Service Revenue', 'INCOME', 1),

-- Suspense/Holding
('9001', 'Suspense Account', 'SUSPENSE', 1)
ON DUPLICATE KEY UPDATE is_active = 1;
