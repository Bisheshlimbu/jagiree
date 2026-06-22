<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/applications.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$action = trim($_POST['action'] ?? '');
$applicationId = (int) ($_POST['application_id'] ?? 0);

if ($applicationId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid application.']);
    exit;
}

if ($action === 'update_status') {
    $status = trim($_POST['status'] ?? '');
    $replyMessage = trim($_POST['reply_message'] ?? '');
    $interviewDate = trim($_POST['interview_date'] ?? '');
    $result = updateEmployerApplicationStatus(
        $employerId,
        $applicationId,
        $status,
        $replyMessage,
        $interviewDate !== '' ? $interviewDate : null
    );
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
    exit;
}

http_response_code(422);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
