-- Migration: add Google location fields to customers
ALTER TABLE customers
  ADD COLUMN place_id VARCHAR(191) NULL AFTER delivery_location,
  ADD COLUMN lat DECIMAL(10,7) NULL AFTER place_id,
  ADD COLUMN lng DECIMAL(10,7) NULL AFTER lat;
