<?php
require_once '../config/database.php';
require_once '../classes/Directory.php';
require_once '../classes/Document.php';

requireAuth();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$query = $_POST['query'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Query is required']);
    exit;
}

$directory = new Directory($pdo);
$document = new Document($pdo);

// Search directories
$stmt = $pdo->prepare(
    'SELECT * FROM directories WHERE user_id = ? AND name LIKE ? ORDER BY name LIMIT 50'
);
$searchQuery = '%' . $query . '%';
$stmt->execute([$userId, $searchQuery]);
$directories = $stmt->fetchAll();

// Search documents
$documents = $document->searchDocuments($userId, $query);

echo json_encode([
    'success' => true,
    'directories' => $directories,
    'documents' => $documents
]);
