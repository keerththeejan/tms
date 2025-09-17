-- Migration: Ensure only Kilinochchi is the Main branch and align user flags
-- Safe to run multiple times.

START TRANSACTION;

-- 1) Normalize branch records: create Colombo and Mullaitivu if missing (non-main)
INSERT INTO branches (name, code, is_main)
SELECT 'Colombo', 'COL', 0
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE name = 'Colombo' OR code = 'COL');

INSERT INTO branches (name, code, is_main)
SELECT 'Mullaitivu', 'MUL', 0
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE name = 'Mullaitivu' OR code = 'MUL');

-- 2) Set Kilinochchi as main (create if missing)
INSERT INTO branches (name, code, is_main)
SELECT 'Kilinochchi', 'KIL', 1
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE name = 'Kilinochchi' OR code = 'KIL');

-- 3) Flip flags: set only Kilinochchi is_main=1; others 0
UPDATE branches SET is_main = CASE WHEN name = 'Kilinochchi' OR code = 'KIL' THEN 1 ELSE 0 END;

-- 4) Align users.is_main_branch with their branch
-- First, default everyone to non-main
UPDATE users SET is_main_branch = 0;
-- Then mark users of the main branch as main
UPDATE users u
JOIN branches b ON b.id = u.branch_id
SET u.is_main_branch = 1
WHERE b.is_main = 1;

COMMIT;
