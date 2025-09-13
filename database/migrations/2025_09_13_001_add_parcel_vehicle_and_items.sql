-- Migration: add vehicle_no to parcels and create parcel_items for multi-line entries
ALTER TABLE parcels
  ADD COLUMN vehicle_no VARCHAR(50) NULL AFTER tracking_number;

CREATE TABLE IF NOT EXISTS parcel_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  parcel_id BIGINT UNSIGNED NOT NULL,
  qty DECIMAL(10,2) NOT NULL DEFAULT 0,
  description VARCHAR(255) NOT NULL,
  rate DECIMAL(12,2) NULL,
  amount DECIMAL(12,2) GENERATED ALWAYS AS (IFNULL(qty,0) * IFNULL(rate,0)) STORED,
  CONSTRAINT fk_parcel_items_parcel FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_parcel_items_parcel ON parcel_items(parcel_id);
