<?php
require_once '../config/database.php';
require_once '../classes/NotificationManager.php';

requireAuth();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$notificationManager = new NotificationManager($pdo);

try {
    switch ($action) {
        case 'list':
            $unreadOnly = $_GET['unread_only'] ?? false;
            $notifications = $notificationManager->getUserNotifications($userId, $unreadOnly);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $notificationManager->getUnreadCount($userId)
            ]);
            break;
        
        case 'mark-read':
            $notificationId = $_POST['notification_id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }
            
            $result = $notificationManager->markAsRead($notificationId, $userId);
            echo json_encode($result + ['message' => 'Marked as read']);
            break;
        
        case 'unread-count':
            $count = $notificationManager->getUnreadCount($userId);
            echo json_encode(['success' => true, 'count' => $count]);
            break;
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
