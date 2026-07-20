<?php
/**
 * Database Configuration and Connection
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'archive_manager');

// Create PDO connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Connection error: ' . $e->getMessage());
}

// Session configuration
session_start();
define('SESSION_TIMEOUT', 3600); // 1 hour

// Check session timeout
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Helper function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to redirect to login
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

// Helper function to format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Helper function to get file icon based on type
function getFileIcon($fileType) {
    $iconMap = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xlsx' => '📊',
        'xls' => '📊',
        'ppt' => '🎯',
        'pptx' => '🎯',
        'zip' => '📦',
        'jpg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'txt' => '📃',
    ];
    return $iconMap[strtolower($fileType)] ?? '📄';
}
