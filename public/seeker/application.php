<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/applications.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isLoggedIn() || currentUser()['role'] !== ROLE_SEEKER) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in as a job seeker.']);
    exit;
}

$seekerId = (int) currentUser()['id'];
$action = trim($_POST['action'] ?? '');
$applicationId = (int) ($_POST['application_id'] ?? 0);

if ($applicationId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid application.']);
    exit;
}

if ($action === 'delete') {
    $result = deleteSeekerApplication($seekerId, $applicationId);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
    exit;
}

if ($action === 'update') {
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $cvFile = $_FILES['cv'] ?? null;
    if ($cvFile && ($cvFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $cvFile = null;
    }

    $result = updateSeekerApplication($seekerId, $applicationId, $coverLetter, $cvFile);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
    exit;
}

http_response_code(422);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
