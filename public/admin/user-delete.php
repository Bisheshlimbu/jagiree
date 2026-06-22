<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users.php');
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$admin = currentUser();
$result = deleteUserByAdmin($userId, (int) $admin['id']);

if ($result['success']) {
    flashSet('success', $result['message']);
} else {
    flashSet('error', $result['error']);
}

header('Location: /admin/users.php');
exit;
