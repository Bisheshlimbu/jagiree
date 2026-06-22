<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/applications.php';
require_once __DIR__ . '/../../includes/seeker/jobs.php';
require_once __DIR__ . '/../../includes/seeker/profile.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isLoggedIn() || currentUser()['role'] !== ROLE_SEEKER) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in as a job seeker to apply.']);
    exit;
}

$seekerId = (int) currentUser()['id'];
$jobId = (int) ($_POST['job_id'] ?? 0);
$coverLetter = trim($_POST['cover_letter'] ?? '');

if ($jobId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Please choose a valid job to apply for.']);
    exit;
}

$profile = fetchSeekerProfile($seekerId);
$seekerSkills = $profile['skill_list'] ?? [];
$jobRow = fetchApprovedJobForSeeker($jobId);
$matchScore = $jobRow ? calculateSeekerJobMatch($seekerSkills, $jobRow['skills'] ?? null) : 0;

$cvFile = $_FILES['cv'] ?? null;
if ($cvFile && ($cvFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $cvFile = null;
}

$result = applyToJob($seekerId, $jobId, $matchScore, $coverLetter, $cvFile);

if (!$result['success']) {
    $status = !empty($result['already_applied']) ? 409 : 400;
    http_response_code($status);
}

echo json_encode($result);
