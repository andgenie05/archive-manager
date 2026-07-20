<?php
require_once '../config/database.php';
require_once '../classes/Document.php';

requireAuth();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$document = new Document($pdo);

try {
    switch ($action) {
        case 'get':
            $documentId = $_POST['id'] ?? $_GET['id'] ?? null;

            if (!$documentId) {
                throw new Exception('Document ID is required');
            }

            $doc = $document->getDocument($documentId, $userId);
            
            if (!$doc) {
                throw new Exception('Document not found');
            }

            echo json_encode([
                'success' => true,
                'data' => $doc
            ]);
            break;

        case 'upload-document':
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $directoryId = $_POST['directory_id'] ?? null;
            if ($directoryId === 'null') $directoryId = null;

            $result = $document->handleUpload($_FILES['file'], $userId, $directoryId);
            echo json_encode($result + ['message' => $result['success'] ? 'Document uploaded' : $result['message']]);
            break;

        case 'update':
            $documentId = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (!$documentId || empty($name)) {
                throw new Exception('Invalid input');
            }

            $result = $document->updateDocument($documentId, $userId, $name, $description);
            echo json_encode($result + ['message' => $result['success'] ? 'Document updated' : $result['message']]);
            break;

        case 'delete':
            $documentId = $_POST['id'] ?? null;

            if (!$documentId) {
                throw new Exception('Invalid input');
            }

            $result = $document->deleteDocument($documentId, $userId);
            echo json_encode($result + ['message' => $result['success'] ? 'Document deleted' : $result['message']]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
