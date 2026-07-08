<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/flash.php';
require_once __DIR__ . '/../../../includes/external-jobs.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/settings.php#integrations');
    exit;
}

$result = syncLinkedInJobsFromApify();

if ($result['success']) {
    flashSet('success', $result['message']);
} else {
    flashSet('error', $result['error']);
}

header('Location: /admin/settings.php#integrations');
exit;
