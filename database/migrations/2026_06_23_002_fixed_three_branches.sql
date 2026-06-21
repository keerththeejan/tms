-- Fixed three-branch master: Colombo (1), Kilinochchi (2), Mullaitivu (3).
-- Run once on existing databases; runtime migration also runs via BranchFixedMaster::ensure().

-- Normalize via application on boot (BranchFixedMaster merges duplicates and remaps FKs).
-- After migration, only ids 1, 2, 3 should remain in branches.
