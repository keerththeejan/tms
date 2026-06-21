-- Daily consolidated invoices: one invoice per customer per calendar day.
CREATE TABLE IF NOT EXISTS invoices (
    invoice_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    invoice_no VARCHAR(32) NOT NULL COMMENT 'Display number e.g. INV-20260623-001',
    parcel_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_quantity DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_weight DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    freight_charges DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    delivery_charges DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (invoice_id),
    UNIQUE KEY uq_invoices_customer_date (customer_id, invoice_date),
    UNIQUE KEY uq_invoices_invoice_no (invoice_no),
    KEY idx_invoices_date (invoice_date),
    KEY idx_invoices_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_day_sequences (
    bill_date DATE NOT NULL PRIMARY KEY,
    last_seq INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
