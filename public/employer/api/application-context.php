<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/applications.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isLoggedIn() || currentUser()['role'] !== ROLE_EMPLOYER) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in as an employer.']);
    exit;
}

$employerId = (int) currentUser()['id'];
$applicationId = (int) ($_GET['application_id'] ?? 0);

if ($applicationId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid application.']);
    exit;
}

$detail = fetchEmployerApplicationDetail($employerId, $applicationId);

if (!$detail) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Application not found.']);
    exit;
}

echo json_encode(['success' => true] + $detail);
