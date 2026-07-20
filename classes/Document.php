<?php
/**
 * Document Management Class
 */

class Document {
    private $pdo;
    private $uploadDir = 'uploads/';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Get documents in directory
     */
    public function getDocuments($directoryId, $userId) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE user_id = ? AND directory_id <=> ? ORDER BY name'
        );
        $stmt->execute([$userId, $directoryId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get document by ID
     */
    public function getDocument($documentId, $userId) {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$documentId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Create document (from upload)
     */
    public function createDocument($userId, $directoryId, $name, $description, $filePath, $fileType, $fileSize) {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO documents (user_id, directory_id, name, description, file_type, file_size, file_path) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $directoryId, $name, $description, $fileType, $fileSize, $filePath]);
            
            $documentId = $this->pdo->lastInsertId();
            
            $stmt = $this->pdo->prepare('SELECT * FROM documents WHERE id = ?');
            $stmt->execute([$documentId]);
            $newDoc = $stmt->fetch();
            
            return ['success' => true, 'data' => $newDoc];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create document'];
        }
    }
    
    /**
     * Update document
     */
    public function updateDocument($documentId, $userId, $name, $description) {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE documents SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$name, $description, $documentId, $userId]);
            
            $stmt = $this->pdo->prepare('SELECT * FROM documents WHERE id = ?');
            $stmt->execute([$documentId]);
            $updated = $stmt->fetch();
            
            return ['success' => true, 'data' => $updated];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update document'];
        }
    }
    
    /**
     * Delete document
     */
    public function deleteDocument($documentId, $userId) {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT file_path FROM documents WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$documentId, $userId]);
            $doc = $stmt->fetch();
            
            if (!$doc) {
                return ['success' => false, 'message' => 'Document not found'];
            }
            
            // Delete file from storage
            if (file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }
            
            // Delete from database
            $stmt = $this->pdo->prepare(
                'DELETE FROM documents WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$documentId, $userId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete document'];
        }
    }
    
    /**
     * Handle file upload
     */
    public function handleUpload($file, $userId, $directoryId) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed'];
        }
        
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large (max 50MB)'];
        }
        
        $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExt)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $fileName = uniqid($userId . '_') . '.' . $ext;
        $filePath = $this->uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to save file'];
        }
        
        return $this->createDocument($userId, $directoryId, $originalName, '', $filePath, $ext, $file['size']);
    }
    
    /**
     * Search documents
     */
    public function searchDocuments($userId, $query) {
        $query = '%' . $query . '%';
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE user_id = ? AND (name LIKE ? OR description LIKE ?) ORDER BY updated_at DESC LIMIT 50'
        );
        $stmt->execute([$userId, $query, $query]);
        return $stmt->fetchAll();
    }
}
