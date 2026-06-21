-- Group daily invoices by customer + date + branch pair (same-bill merge key).
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS from_branch_id INT UNSIGNED NULL AFTER customer_id;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS to_branch_id INT UNSIGNED NULL AFTER from_branch_id;

-- MySQL 8.0 may not support IF NOT EXISTS on ADD COLUMN; run via ParcelBillingService::ensureSchema() in app.
