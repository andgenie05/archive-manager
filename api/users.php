<?php
require_once '../config/database.php';
require_once '../classes/UserManager.php';
require_once '../classes/RBAC.php';

requireAuth();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$userManager = new UserManager($pdo);
$rbac = new RBAC($pdo);

// Check if admin
if (!$rbac->hasPermission($userId, RBAC::PERMISSION_DELETE)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list-users':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $users = $userManager->getAllUsers($limit, $offset);
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            break;
        
        case 'get-user':
            $targetUserId = $_GET['user_id'] ?? null;
            
            if (!$targetUserId) {
                throw new Exception('User ID required');
            }
            
            $user = $userManager->getUserDetails($targetUserId);
            $storage = $userManager->getUserStorageUsage($targetUserId);
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'storage' => $storage
            ]);
            break;
        
        case 'update-profile':
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            
            $result = $userManager->updateUserProfile($userId, $fullName, $email);
            echo json_encode($result + ['message' => 'Profile updated']);
            break;
        
        case 'toggle-status':
            $targetUserId = $_POST['user_id'] ?? null;
            $status = $_POST['status'] ?? true;
            
            if (!$targetUserId) {
                throw new Exception('User ID required');
            }
            
            $result = $userManager->toggleUserStatus($targetUserId, $status);
            echo json_encode($result + ['message' => 'Status updated']);
            break;
        
        case 'delete-user':
            $targetUserId = $_POST['user_id'] ?? null;
            
            if (!$targetUserId || $targetUserId == $userId) {
                throw new Exception('Cannot delete own account');
            }
            
            $result = $userManager->deleteUser($targetUserId);
            echo json_encode($result + ['message' => 'User deleted']);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
