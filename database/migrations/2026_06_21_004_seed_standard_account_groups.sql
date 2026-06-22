-- Fix group_code length (LONG_TERM_LIABILITIES is 21 chars; old column was varchar(20))
ALTER TABLE account_groups MODIFY group_code varchar(40) NOT NULL;

-- Repair truncated code from earlier seeds
UPDATE account_groups
SET group_code = 'LONG_TERM_LIABILITIES', group_name = 'Long-Term Liabilities'
WHERE group_code = 'LONG_TERM_LIABILITIE'
  AND NOT EXISTS (SELECT 1 FROM account_groups g2 WHERE g2.group_code = 'LONG_TERM_LIABILITIES');

-- Standard account groups for TMS Chart of Accounts
-- Safe to run multiple times: uses INSERT for missing codes only.

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'ASSETS', 'Assets', NULL, 'ASSETS', 'DEBIT', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'ASSETS');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'LIABILITIES', 'Liabilities', NULL, 'LIABILITIES', 'CREDIT', 1, 1, 2
WHERE NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'LIABILITIES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'CAPITAL', 'Equity', NULL, 'CAPITAL', 'CREDIT', 1, 1, 3
WHERE NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'CAPITAL');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'INCOME', 'Income', NULL, 'INCOME', 'CREDIT', 1, 1, 4
WHERE NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'INCOME');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'EXPENSES', 'Expenses', NULL, 'EXPENSES', 'DEBIT', 1, 1, 5
WHERE NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'EXPENSES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'CURRENT_ASSETS', 'Current Assets', p.id, 'ASSETS', 'DEBIT', 0, 1, 10 FROM account_groups p WHERE p.group_code = 'ASSETS'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'CURRENT_ASSETS');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'FIXED_ASSETS', 'Fixed Assets', p.id, 'ASSETS', 'DEBIT', 0, 1, 11 FROM account_groups p WHERE p.group_code = 'ASSETS'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'FIXED_ASSETS');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'CURRENT_LIABILITIES', 'Current Liabilities', p.id, 'LIABILITIES', 'CREDIT', 0, 1, 20 FROM account_groups p WHERE p.group_code = 'LIABILITIES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'CURRENT_LIABILITIES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'LONG_TERM_LIABILITIES', 'Long-Term Liabilities', p.id, 'LIABILITIES', 'CREDIT', 0, 1, 21 FROM account_groups p WHERE p.group_code = 'LIABILITIES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code IN ('LONG_TERM_LIABILITIES', 'LONG_TERM_LIABILITIE'));

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'SALES_INCOME', 'Sales Revenue', p.id, 'INCOME', 'CREDIT', 0, 1, 30 FROM account_groups p WHERE p.group_code = 'INCOME'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'SALES_INCOME');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'SERVICE_INCOME', 'Service Revenue', p.id, 'INCOME', 'CREDIT', 0, 1, 31 FROM account_groups p WHERE p.group_code = 'INCOME'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'SERVICE_INCOME');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'ADMIN_EXPENSES', 'Administrative Expenses', p.id, 'EXPENSES', 'DEBIT', 0, 1, 40 FROM account_groups p WHERE p.group_code = 'EXPENSES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'ADMIN_EXPENSES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'TRANSPORT_EXPENSES', 'Transport Expenses', p.id, 'EXPENSES', 'DEBIT', 0, 1, 41 FROM account_groups p WHERE p.group_code = 'EXPENSES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'TRANSPORT_EXPENSES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'SALARY_EXPENSES', 'Salary Expenses', p.id, 'EXPENSES', 'DEBIT', 0, 1, 42 FROM account_groups p WHERE p.group_code = 'EXPENSES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'SALARY_EXPENSES');

UPDATE account_groups SET group_name = 'Equity' WHERE group_code = 'CAPITAL';
UPDATE account_groups SET group_name = 'Long-Term Liabilities' WHERE group_code IN ('LONG_TERM_LIABILITIES', 'LONG_TERM_LIABILITIE');
UPDATE account_groups SET group_name = 'Sales Revenue' WHERE group_code = 'SALES_INCOME';
UPDATE account_groups SET group_name = 'Service Revenue' WHERE group_code = 'SERVICE_INCOME';

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'CASH', 'Cash', p.id, 'ASSETS', 'DEBIT', 0, 1, 100 FROM account_groups p WHERE p.group_code = 'CURRENT_ASSETS'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'CASH');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'BANK', 'Bank', p.id, 'ASSETS', 'DEBIT', 0, 1, 101 FROM account_groups p WHERE p.group_code = 'CURRENT_ASSETS'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'BANK');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'SUNDRY_DEBTORS', 'Sundry Debtors', p.id, 'ASSETS', 'DEBIT', 0, 1, 102 FROM account_groups p WHERE p.group_code = 'CURRENT_ASSETS'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'SUNDRY_DEBTORS');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'SUNDRY_CREDITORS', 'Sundry Creditors', p.id, 'LIABILITIES', 'CREDIT', 0, 1, 200 FROM account_groups p WHERE p.group_code = 'CURRENT_LIABILITIES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'SUNDRY_CREDITORS');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'FUEL_EXPENSES', 'Fuel Expenses', p.id, 'EXPENSES', 'DEBIT', 0, 1, 300 FROM account_groups p WHERE p.group_code = 'TRANSPORT_EXPENSES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'FUEL_EXPENSES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'VEHICLE_EXPENSES', 'Vehicle Expenses', p.id, 'EXPENSES', 'DEBIT', 0, 1, 301 FROM account_groups p WHERE p.group_code = 'TRANSPORT_EXPENSES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'VEHICLE_EXPENSES');

INSERT INTO account_groups (group_code, group_name, parent_id, group_type, nature, is_primary, is_system, sort_order)
SELECT 'DRIVER_SALARY', 'Driver Salary', p.id, 'EXPENSES', 'DEBIT', 0, 1, 302 FROM account_groups p WHERE p.group_code = 'SALARY_EXPENSES'
AND NOT EXISTS (SELECT 1 FROM account_groups WHERE group_code = 'DRIVER_SALARY');
