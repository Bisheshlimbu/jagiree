<?php
require_once __DIR__ . '/../../includes/auth.php';

requireRole(ROLE_SEEKER);

$jobId = (int) ($_GET['id'] ?? 0);
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/seeker/jobs.php';

if ($jobId > 0) {
    $target .= '?id=' . $jobId;
    if ($query !== '' && !str_starts_with($query, 'id=')) {
        $target .= '&' . $query;
    }
}

header('Location: ' . $target);
exit;
