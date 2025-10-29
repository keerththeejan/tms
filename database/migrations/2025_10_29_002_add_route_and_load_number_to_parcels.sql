-- Add route and load number fields to parcels table
ALTER TABLE parcels 
ADD COLUMN route_id BIGINT UNSIGNED NULL AFTER to_branch_id,
ADD COLUMN load_number VARCHAR(50) NULL AFTER route_id,
ADD COLUMN is_return_load BOOLEAN NOT NULL DEFAULT FALSE AFTER load_number,
ADD COLUMN return_route_id BIGINT UNSIGNED NULL AFTER is_return_load,
ADD COLUMN return_load_number VARCHAR(50) NULL AFTER return_route_id,
ADD INDEX idx_parcels_route (route_id),
ADD INDEX idx_parcels_load_number (load_number),
ADD INDEX idx_parcels_return_load (is_return_load, return_load_number);

-- Create routes table if it doesn't exist
CREATE TABLE IF NOT EXISTS routes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    from_branch_id BIGINT UNSIGNED NOT NULL,
    to_branch_id BIGINT UNSIGNED NOT NULL,
    distance_km DECIMAL(10,2) NULL,
    estimated_hours DECIMAL(5,1) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_route_branches (from_branch_id, to_branch_id),
    CONSTRAINT fk_route_from_branch FOREIGN KEY (from_branch_id) REFERENCES branches (id) ON DELETE RESTRICT,
    CONSTRAINT fk_route_to_branch FOREIGN KEY (to_branch_id) REFERENCES branches (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Add foreign key for route_id in parcels table
ALTER TABLE parcels
ADD CONSTRAINT fk_parcel_route FOREIGN KEY (route_id) REFERENCES routes (id) ON DELETE SET NULL,
ADD CONSTRAINT fk_parcel_return_route FOREIGN KEY (return_route_id) REFERENCES routes (id) ON DELETE SET NULL;
