<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/applications.php';
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
$applicationId = (int) ($_GET['application_id'] ?? 0);

if ($applicationId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid application.']);
    exit;
}

$application = fetchSeekerApplicationRow($seekerId, $applicationId);

if (!$application) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Application not found.']);
    exit;
}

$status = $application['status'] ?? 'applied';
$cvPath = isValidApplicationCvPath($application['cv_path'] ?? null) ? $application['cv_path'] : null;
$profile = fetchSeekerProfile($seekerId);
$profileCv = seekerCvMeta($profile ?? []);

echo json_encode([
    'success' => true,
    'application' => [
        'id' => (int) $application['id'],
        'job_id' => (int) $application['job_id'],
        'cover_letter' => trim($application['cover_letter'] ?? ''),
        'status' => $status,
        'status_label' => applicationStatusLabel($status),
        'can_edit' => seekerApplicationEditable($status),
        'can_delete' => seekerApplicationDeletable($status),
        'cv' => [
            'has_cv' => $cvPath !== null,
            'path' => $cvPath,
            'filename' => $cvPath ? basename($cvPath) : null,
            'updated_label' => null,
        ],
    ],
    'job' => [
        'id' => (int) $application['job_id'],
        'title' => $application['title'] ?? '',
        'company' => trim($application['company_name'] ?? ''),
        'location' => trim($application['location'] ?? '') ?: 'Location not set',
    ],
    'profile_cv' => $profileCv,
]);
