<?php
require_once 'config/database.php';
require_once 'classes/Auth.php';
require_once 'classes/Directory.php';
require_once 'classes/Document.php';

requireAuth();

$userId = getCurrentUserId();
$auth = new Auth($pdo);
$directory = new Directory($pdo);
$document = new Document($pdo);

$userInfo = $auth->getUserInfo($userId);

// Get all root directories with document counts
$stmt = $pdo->prepare(
    'SELECT d.*, COUNT(doc.id) as document_count 
     FROM directories d 
     LEFT JOIN documents doc ON d.id = doc.directory_id 
     WHERE d.user_id = ? AND d.parent_id IS NULL 
     GROUP BY d.id 
     ORDER BY d.name'
);
$stmt->execute([$userId]);
$rootDirectories = $stmt->fetchAll();

// Count total documents
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM documents WHERE user_id = ?');
$stmt->execute([$userId]);
$totalDocs = $stmt->fetch()['total'];

// Count total directories
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM directories WHERE user_id = ?');
$stmt->execute([$userId]);
$totalDirs = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Manager</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="layout">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <span style="font-size: 24px;">📦</span>
                <h1>Archive</h1>
            </div>

            <div class="sidebar-user">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($userInfo['full_name']); ?></div>
                <div class="sidebar-user-email"><?php echo htmlspecialchars($userInfo['email']); ?></div>
                <a href="login.php?action=logout" class="sidebar-logout">Logout</a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Navigation</div>
                <div class="sidebar-item active" onclick="archiveManager.navigateToDirectory(null)">
                    🏠 Home
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Your Folders</div>
                <?php foreach ($rootDirectories as $dir): ?>
                    <div class="sidebar-item" data-dir-id="<?php echo $dir['id']; ?>" onclick="archiveManager.navigateToDirectory(<?php echo $dir['id']; ?>)">
                        📁 <?php echo htmlspecialchars(substr($dir['name'], 0, 20)); ?>
                        <span class="sidebar-toggle">→</span>
                    </div>
                    <?php
                    // Get subdirectories
                    $stmt = $pdo->prepare(
                        'SELECT * FROM directories WHERE user_id = ? AND parent_id = ? ORDER BY name'
                    );
                    $stmt->execute([$userId, $dir['id']]);
                    $subDirs = $stmt->fetchAll();
                    
                    if (count($subDirs) > 0):
                    ?>
                        <div class="sidebar-submenu">
                            <?php foreach ($subDirs as $subDir): ?>
                                <div class="sidebar-submenu-item" onclick="archiveManager.navigateToDirectory(<?php echo $subDir['id']; ?>)">
                                    📂 <?php echo htmlspecialchars(substr($subDir['name'], 0, 18)); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Stats</div>
                <div style="padding: 10px 20px; font-size: 13px; opacity: 0.8;">
                    <div>📁 <?php echo $totalDirs; ?> folder<?php echo $totalDirs !== 1 ? 's' : ''; ?></div>
                    <div>📄 <?php echo $totalDocs; ?> document<?php echo $totalDocs !== 1 ? 's' : ''; ?></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">📦 Archive Manager</div>
                <div class="header-actions">
                    <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search...">
                    <button id="createDirBtn" class="btn btn-primary btn-small">+ Folder</button>
                    <button id="uploadFileBtn" class="btn btn-success btn-small">⬆️ Upload</button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content">
                <div id="alertContainer"></div>

                <div id="breadcrumb" class="breadcrumb"></div>

                <div id="itemsContainer" class="item-list"></div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
