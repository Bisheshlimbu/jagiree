<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/notifications.php';

header('Content-Type: application/json');

if (!isLoggedIn() || currentUser()['role'] !== ROLE_EMPLOYER) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

$userId = (int) currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'unread' => countUnreadNotifications($userId),
        'notifications' => fetchUserNotifications($userId),
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$action = trim($_POST['action'] ?? '');

if ($action === 'mark_all_read') {
    markAllNotificationsRead($userId);
    echo json_encode([
        'success' => true,
        'unread' => 0,
        'notifications' => fetchUserNotifications($userId),
    ]);
    exit;
}

if ($action === 'mark_read') {
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid notification.']);
        exit;
    }

    markNotificationRead($userId, $notificationId);
    echo json_encode([
        'success' => true,
        'unread' => countUnreadNotifications($userId),
        'notifications' => fetchUserNotifications($userId),
    ]);
    exit;
}

http_response_code(422);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
