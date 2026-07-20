<?php
require_once '../config/database.php';
require_once '../classes/Directory.php';
require_once '../classes/Document.php';

requireAuth();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$directory = new Directory($pdo);
$document = new Document($pdo);

try {
    switch ($action) {
        case 'get':
            $directoryId = $_POST['directory_id'] ?? null;
            if ($directoryId === 'null') $directoryId = null;

            $dirs = $directoryId ? 
                $directory->getSubdirectories($directoryId, $userId) : 
                $directory->getRootDirectories($userId);

            $docs = $directoryId ? 
                $document->getDocuments($directoryId, $userId) : 
                $document->getDocuments(null, $userId);

            $breadcrumb = $directoryId ? 
                $directory->getBreadcrumbPath($directoryId, $userId) : 
                [];

            echo json_encode([
                'success' => true,
                'directories' => $dirs,
                'documents' => $docs,
                'breadcrumb' => $breadcrumb
            ]);
            break;

        case 'create-directory':
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $parentId = $_POST['parent_id'] ?? null;
            if ($parentId === 'null') $parentId = null;

            if (empty($name)) {
                throw new Exception('Directory name is required');
            }

            $result = $directory->createDirectory($userId, $name, $description, $parentId);
            echo json_encode($result + ['message' => $result['success'] ? 'Directory created' : $result['message']]);
            break;

        case 'update':
            $directoryId = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (!$directoryId || empty($name)) {
                throw new Exception('Invalid input');
            }

            $result = $directory->updateDirectory($directoryId, $userId, $name, $description);
            echo json_encode($result + ['message' => $result['success'] ? 'Directory updated' : $result['message']]);
            break;

        case 'delete':
            $directoryId = $_POST['id'] ?? null;

            if (!$directoryId) {
                throw new Exception('Invalid input');
            }

            $result = $directory->deleteDirectory($directoryId, $userId);
            echo json_encode($result + ['message' => $result['success'] ? 'Directory deleted' : $result['message']]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
