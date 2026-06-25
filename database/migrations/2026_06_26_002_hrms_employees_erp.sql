-- HRMS Employees ERP enhancements (idempotent; EmployeeSchemaRepository also applies)

CREATE TABLE IF NOT EXISTS hr_departments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(120) NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_hr_departments_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hr_designations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(120) NOT NULL,
  department_id INT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_hr_designations_code (code),
  KEY idx_hr_designations_dept (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  doc_type VARCHAR(40) NOT NULL DEFAULT 'other',
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_emp_docs_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO hr_departments (code, name, sort_order) VALUES
('operations', 'Operations', 10),
('finance', 'Finance', 20),
('hr', 'Human Resources', 30),
('maintenance', 'Maintenance', 40),
('admin', 'Administration', 50),
('logistics', 'Logistics', 60);

INSERT IGNORE INTO hr_designations (code, name, sort_order) VALUES
('driver', 'Driver', 10),
('manager', 'Manager', 20),
('clerk', 'Clerk', 30),
('mechanic', 'Mechanic', 40),
('accountant', 'Accountant', 50),
('supervisor', 'Supervisor', 60);
