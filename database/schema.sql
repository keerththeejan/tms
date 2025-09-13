-- Transport Management System Schema (MySQL 8+)
-- Ensure the database `tms_db` exists before running

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS branches (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  is_main TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  role ENUM('admin','staff','accountant') NOT NULL DEFAULT 'staff',
  branch_id BIGINT UNSIGNED NULL,
  is_main_branch TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  delivery_location VARCHAR(120) NULL,
  customer_type ENUM('regular','corporate') NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE INDEX idx_customers_phone ON customers(phone);

CREATE TABLE IF NOT EXISTS suppliers (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NULL,
  branch_id BIGINT UNSIGNED NULL,
  supplier_code VARCHAR(30) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_suppliers_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;
CREATE INDEX idx_suppliers_branch ON suppliers(branch_id);
CREATE INDEX idx_suppliers_code ON suppliers(supplier_code);

CREATE TABLE IF NOT EXISTS parcels (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  customer_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NULL,
  from_branch_id BIGINT UNSIGNED NOT NULL,
  to_branch_id BIGINT UNSIGNED NOT NULL,
  weight DECIMAL(10,2) NOT NULL DEFAULT 0,
  price DECIMAL(12,2) NULL,
  status ENUM('pending','in_transit','delivered') NOT NULL DEFAULT 'pending',
  tracking_number VARCHAR(50) NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_parcel_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_parcel_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  CONSTRAINT fk_parcel_from_branch FOREIGN KEY (from_branch_id) REFERENCES branches(id),
  CONSTRAINT fk_parcel_to_branch FOREIGN KEY (to_branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;
CREATE INDEX idx_parcels_customer ON parcels(customer_id);
CREATE INDEX idx_parcels_status ON parcels(status);
CREATE INDEX idx_parcels_to_branch ON parcels(to_branch_id);

-- Delivery notes group parcels per customer per day
CREATE TABLE IF NOT EXISTS delivery_notes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  customer_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL, -- issuing branch
  delivery_date DATE NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dn_customer_date_branch (customer_id, delivery_date, branch_id),
  CONSTRAINT fk_dn_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
  CONSTRAINT fk_dn_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS delivery_note_parcels (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  delivery_note_id BIGINT UNSIGNED NOT NULL,
  parcel_id BIGINT UNSIGNED NOT NULL UNIQUE, -- a parcel belongs to at most one DN
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_dnp_dn FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE,
  CONSTRAINT fk_dnp_parcel FOREIGN KEY (parcel_id) REFERENCES parcels(id)
) ENGINE=InnoDB;
CREATE INDEX idx_dnp_dn ON delivery_note_parcels(delivery_note_id);

-- Payments towards delivery notes (partial payments allowed)
CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  delivery_note_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  paid_at DATETIME NOT NULL,
  received_by BIGINT UNSIGNED NULL, -- user
  CONSTRAINT fk_payments_dn FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_user FOREIGN KEY (received_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE INDEX idx_payments_dn ON payments(delivery_note_id);
CREATE INDEX idx_payments_paid_at ON payments(paid_at);

-- Expenses
CREATE TABLE IF NOT EXISTS expenses (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  expense_type ENUM('fuel','vehicle_maintenance','office','utilities','other') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  expense_date DATE NOT NULL,
  notes VARCHAR(255) NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_expenses_branch FOREIGN KEY (branch_id) REFERENCES branches(id),
  CONSTRAINT fk_expenses_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE INDEX idx_expenses_branch ON expenses(branch_id);
CREATE INDEX idx_expenses_date ON expenses(expense_date);

-- Employees and Salaries
CREATE TABLE IF NOT EXISTS employees (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  position VARCHAR(80) NOT NULL,
  salary_amount DECIMAL(12,2) NOT NULL,
  branch_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_employees_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;
CREATE INDEX idx_employees_branch ON employees(branch_id);

CREATE TABLE IF NOT EXISTS salaries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  month YEAR(4) NOT NULL,
  month_num TINYINT UNSIGNED NOT NULL,
  payment_date DATE NULL,
  status ENUM('paid','pending') NOT NULL DEFAULT 'pending',
  amount DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_salary_emp_month (employee_id, month, month_num),
  CONSTRAINT fk_salaries_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

-- Seed minimal data
INSERT INTO branches (name, code, is_main) VALUES
  ('Main Branch', 'MAIN', 1),
  ('Branch A', 'BR-A', 0),
  ('Branch B', 'BR-B', 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Admin user is intentionally NOT created here to avoid hash mismatches across environments.
-- Use the local seeder at public/seed_admin.php to create/update the admin account
-- with a fresh bcrypt hash for the password 'admin123'.

