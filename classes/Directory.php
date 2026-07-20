<?php
/**
 * Directory Management Class
 */

class Directory {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all root directories for user
     */
    public function getRootDirectories($userId) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM directories WHERE user_id = ? AND parent_id IS NULL ORDER BY name'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get subdirectories of a parent
     */
    public function getSubdirectories($parentId, $userId) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM directories WHERE user_id = ? AND parent_id = ? ORDER BY name'
        );
        $stmt->execute([$userId, $parentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get directory with full path
     */
    public function getDirectoryWithPath($directoryId, $userId) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM directories WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$directoryId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get directory breadcrumb path
     */
    public function getBreadcrumbPath($directoryId, $userId) {
        $path = [];
        $current = $directoryId;
        
        while ($current !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT id, parent_id, name FROM directories WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$current, $userId]);
            $dir = $stmt->fetch();
            
            if (!$dir) break;
            
            array_unshift($path, $dir);
            $current = $dir['parent_id'];
        }
        
        return $path;
    }
    
    /**
     * Create new directory
     */
    public function createDirectory($userId, $name, $description = '', $parentId = null) {
        // Check if name already exists at this level
        $stmt = $this->pdo->prepare(
            'SELECT id FROM directories WHERE user_id = ? AND parent_id <=> ? AND name = ?'
        );
        $stmt->execute([$userId, $parentId, $name]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Directory name already exists at this level'];
        }
        
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO directories (user_id, parent_id, name, description) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $parentId, $name, $description]);
            
            $directoryId = $this->pdo->lastInsertId();
            
            $stmt = $this->pdo->prepare('SELECT * FROM directories WHERE id = ?');
            $stmt->execute([$directoryId]);
            $newDir = $stmt->fetch();
            
            return ['success' => true, 'data' => $newDir];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create directory'];
        }
    }
    
    /**
     * Update directory
     */
    public function updateDirectory($directoryId, $userId, $name, $description) {
        // Check if name already exists
        $stmt = $this->pdo->prepare(
            'SELECT id FROM directories WHERE user_id = ? AND id != ? AND parent_id = (SELECT parent_id FROM directories WHERE id = ?) AND name = ?'
        );
        $stmt->execute([$userId, $directoryId, $directoryId, $name]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Directory name already exists'];
        }
        
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE directories SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$name, $description, $directoryId, $userId]);
            
            $stmt = $this->pdo->prepare('SELECT * FROM directories WHERE id = ?');
            $stmt->execute([$directoryId]);
            $updated = $stmt->fetch();
            
            return ['success' => true, 'data' => $updated];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update directory'];
        }
    }
    
    /**
     * Delete directory (recursive)
     */
    public function deleteDirectory($directoryId, $userId) {
        try {
            $this->pdo->beginTransaction();
            
            // Delete all documents in this directory and subdirectories
            $this->deleteDirectoryDocuments($directoryId, $userId);
            
            // Delete all subdirectories
            $this->deleteSubdirectories($directoryId, $userId);
            
            // Delete the directory itself
            $stmt = $this->pdo->prepare(
                'DELETE FROM directories WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$directoryId, $userId]);
            
            $this->pdo->commit();
            
            return ['success' => true];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to delete directory'];
        }
    }
    
    /**
     * Helper to delete directory documents recursively
     */
    private function deleteDirectoryDocuments($directoryId, $userId) {
        // Delete documents in this directory
        $stmt = $this->pdo->prepare(
            'DELETE FROM documents WHERE directory_id = ? AND user_id = ?'
        );
        $stmt->execute([$directoryId, $userId]);
        
        // Delete documents in subdirectories
        $stmt = $this->pdo->prepare(
            'SELECT id FROM directories WHERE parent_id = ? AND user_id = ?'
        );
        $stmt->execute([$directoryId, $userId]);
        
        while ($subdir = $stmt->fetch()) {
            $this->deleteDirectoryDocuments($subdir['id'], $userId);
        }
    }
    
    /**
     * Helper to delete subdirectories recursively
     */
    private function deleteSubdirectories($directoryId, $userId) {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM directories WHERE parent_id = ? AND user_id = ?'
        );
        $stmt->execute([$directoryId, $userId]);
        
        while ($subdir = $stmt->fetch()) {
            $this->deleteSubdirectories($subdir['id'], $userId);
            $this->pdo->prepare(
                'DELETE FROM directories WHERE id = ? AND user_id = ?'
            )->execute([$subdir['id'], $userId]);
        }
    }
}
