<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/applications.php';
require_once __DIR__ . '/../../../includes/seeker/jobs.php';
require_once __DIR__ . '/../../../includes/seeker/profile.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
$jobId = (int) ($_GET['job_id'] ?? 0);

if ($jobId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid job.']);
    exit;
}

$profile = fetchSeekerProfile($seekerId);
$jobRow = fetchApprovedJobForSeeker($jobId);

if (!$jobRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'This job is no longer available.']);
    exit;
}

$skills = $profile['skill_list'] ?? [];
$job = formatSeekerJobCard($jobRow, $skills, seekerAppliedJobIds($seekerId));
$cvMeta = seekerCvMeta($profile ?? []);

echo json_encode([
    'success' => true,
    'job' => [
        'id' => (int) $job['id'],
        'title' => $job['title'],
        'company' => $job['company'],
        'location' => $job['location'],
        'match' => (int) ($job['match'] ?? 0),
        'applied' => !empty($job['applied']),
    ],
    'cv' => $cvMeta,
]);
