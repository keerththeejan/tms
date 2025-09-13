-- Migration: expand users.role enum to include cashier, collector, parcel_user
ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','staff','accountant','cashier','collector','parcel_user') NOT NULL DEFAULT 'staff';
