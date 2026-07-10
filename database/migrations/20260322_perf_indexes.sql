-- Optional: speed up dashboard / parcel list date-range filters.
-- Run manually. If you see "Duplicate key name", the index already exists — skip.
ALTER TABLE parcels ADD INDEX idx_parcels_created_at (created_at);
