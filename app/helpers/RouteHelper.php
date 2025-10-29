<?php

class RouteHelper {
    /**
     * Get or create a route between two branches
     */
    public static function getOrCreateRoute($pdo, $fromBranchId, $toBranchId) {
        // Try to find existing route
        $stmt = $pdo->prepare('SELECT id FROM routes WHERE from_branch_id = ? AND to_branch_id = ? LIMIT 1');
        $stmt->execute([$fromBranchId, $toBranchId]);
        $route = $stmt->fetch();
        
        if ($route) {
            return (int)$route['id'];
        }
        
        // Create a new route if it doesn't exist
        $fromBranch = self::getBranchName($pdo, $fromBranchId);
        $toBranch = self::getBranchName($pdo, $toBranchId);
        $routeName = "$fromBranch to $toBranch";
        
        $stmt = $pdo->prepare('INSERT INTO routes (name, from_branch_id, to_branch_id) VALUES (?, ?, ?)');
        $stmt->execute([$routeName, $fromBranchId, $toBranchId]);
        
        return (int)$pdo->lastInsertId();
    }
    
    /**
     * Generate a load number for a route
     */
    public static function generateLoadNumber($pdo, $routeId, $isReturn = false) {
        $prefix = $isReturn ? 'R' : 'L';
        $date = date('Ymd');
        
        // Get the last load number for this route today
        $stmt = $pdo->prepare(
            'SELECT load_number FROM parcels ' .
            'WHERE (route_id = ? OR return_route_id = ?) ' .
            'AND (load_number LIKE ? OR return_load_number LIKE ?) ' .
            'ORDER BY created_at DESC LIMIT 1'
        );
        $likePattern = $prefix . $routeId . '-' . $date . '%';
        $stmt->execute([$routeId, $routeId, $likePattern, $likePattern]);
        $lastLoad = $stmt->fetch();
        
        if ($lastLoad) {
            // Extract the sequence number and increment
            $lastNumber = $lastLoad['load_number'];
            if (preg_match('/-\d+$/', $lastNumber, $matches)) {
                $sequence = (int)substr($matches[0], 1) + 1;
                return $prefix . $routeId . '-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            }
        }
        
        // If no previous load or pattern doesn't match, start with 001
        return $prefix . $routeId . '-' . $date . '-001';
    }
    
    /**
     * Get branch name by ID
     */
    private static function getBranchName($pdo, $branchId) {
        static $branches = [];
        
        if (!isset($branches[$branchId])) {
            $stmt = $pdo->prepare('SELECT name FROM branches WHERE id = ?');
            $stmt->execute([$branchId]);
            $branch = $stmt->fetch();
            $branches[$branchId] = $branch ? $branch['name'] : 'Unknown';
        }
        
        return $branches[$branchId];
    }
    
    /**
     * Automatically assign route and load number to a parcel
     */
    public static function assignRouteAndLoadNumber($pdo, $parcelId, $fromBranchId, $toBranchId, $isReturnLoad = false) {
        try {
            // Get or create the route
            $routeId = self::getOrCreateRoute($pdo, $fromBranchId, $toBranchId);
            $loadNumber = self::generateLoadNumber($pdo, $routeId, $isReturnLoad);
            
            // Update the parcel
            if ($isReturnLoad) {
                $stmt = $pdo->prepare('UPDATE parcels SET return_route_id = ?, return_load_number = ?, is_return_load = 1 WHERE id = ?');
                $stmt->execute([$routeId, $loadNumber, $parcelId]);
            } else {
                $stmt = $pdo->prepare('UPDATE parcels SET route_id = ?, load_number = ? WHERE id = ?');
                $stmt->execute([$routeId, $loadNumber, $parcelId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error assigning route/load number: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a return load is needed and assign it
     */
    public static function checkAndAssignReturnLoad($pdo, $parcelId, $fromBranchId, $toBranchId) {
        try {
            // For now, we'll assume all parcels from Kilinochchi (ID 1) need a return load
            if ($toBranchId == 1) { // Kilinochchi is the main branch
                return self::assignRouteAndLoadNumber($pdo, $parcelId, $toBranchId, $fromBranchId, true);
            }
            return false;
        } catch (Exception $e) {
            error_log('Error assigning return load: ' . $e->getMessage());
            return false;
        }
    }
}
