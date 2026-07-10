-- Add optional email to customers
ALTER TABLE customers
  ADD COLUMN IF NOT EXISTS email VARCHAR(180) NULL AFTER phone,
  ADD INDEX IF NOT EXISTS idx_customers_email (email);
