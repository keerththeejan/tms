-- Manual parcel item entry support
-- Safe to run multiple times (ignore duplicate-column errors).

ALTER TABLE parcel_items ADD COLUMN is_manual TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE parcel_items ADD COLUMN manual_item_name VARCHAR(200) NULL;
ALTER TABLE parcel_items ADD COLUMN manual_unit VARCHAR(50) NULL;
