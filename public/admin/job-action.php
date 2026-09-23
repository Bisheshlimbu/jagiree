<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/jobs.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/jobs.php');
    exit;
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$action = $_POST['action'] ?? '';
$page = max(1, (int) ($_POST['page'] ?? 1));
$redirect = '/admin/jobs.php?page=' . $page;

if ($action === 'bulk_delete') {
    $ids = $_POST['job_ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    $result = deleteJobsByAdmin($ids);
    if ($result['success']) {
        flashSet('success', $result['message']);
    } else {
        flashSet('error', $result['error']);
    }
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'delete') {
    $result = deleteJobByAdmin($jobId);
    if ($result['success']) {
        flashSet('success', $result['message']);
    } else {
        flashSet('error', $result['error']);
    }
    header('Location: ' . $redirect);
    exit;
}

$status = match ($action) {
    'approve' => 'approved',
    'reject' => 'rejected',
    default => null,
};

if ($status === null) {
    flashSet('error', 'Invalid job action.');
    header('Location: ' . $redirect);
    exit;
}

$result = updateJobStatusByAdmin($jobId, $status);

if ($result['success']) {
    flashSet('success', $result['message']);
} else {
    flashSet('error', $result['error']);
}

header('Location: ' . $redirect);
exit;
