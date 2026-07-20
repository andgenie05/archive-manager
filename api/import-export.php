<?php
require_once '../config/database.php';
require_once '../classes/BatchImporter.php';
require_once '../classes/ArchiveCompressor.php';

requireAuth();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$importer = new BatchImporter($pdo);
$compressor = new ArchiveCompressor($pdo);

try {
    switch ($action) {
        case 'import-zip':
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }
            
            $directoryId = $_POST['directory_id'] ?? null;
            
            $tmpFile = $_FILES['file']['tmp_name'];
            $result = $importer->importFromZip($tmpFile, $userId, $directoryId);
            
            echo json_encode($result);
            break;
        
        case 'import-csv':
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }
            
            $directoryId = $_POST['directory_id'] ?? null;
            
            $tmpFile = $_FILES['file']['tmp_name'];
            $result = $importer->importFromCSV($tmpFile, $userId, $directoryId);
            
            echo json_encode($result);
            break;
        
        case 'import-json':
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }
            
            $parentId = $_POST['parent_id'] ?? null;
            
            $tmpFile = $_FILES['file']['tmp_name'];
            $result = $importer->importDirectoriesFromJSON($tmpFile, $userId, $parentId);
            
            echo json_encode($result);
            break;
        
        case 'create-archive':
            $directoryId = $_POST['directory_id'] ?? null;
            
            if (!$directoryId) {
                throw new Exception('Directory ID required');
            }
            
            $result = $compressor->createArchive($directoryId, $userId);
            echo json_encode($result);
            break;
        
        case 'backup-all':
            $result = $compressor->compressUserArchives($userId);
            echo json_encode($result);
            break;
        
        case 'compression-stats':
            $directoryId = $_GET['directory_id'] ?? null;
            
            if (!$directoryId) {
                throw new Exception('Directory ID required');
            }
            
            $stats = $compressor->getCompressionStats($directoryId, $userId);
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
