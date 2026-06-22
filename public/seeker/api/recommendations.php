<?php
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/seeker/profile.php';
require_once __DIR__ . '/../../../includes/seeker/jobs.php';

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

$userId = (int) currentUser()['id'];
$limit = max(1, min(12, (int) ($_GET['limit'] ?? 6)));
$sort = $_GET['sort'] ?? 'match';
if (!in_array($sort, ['match', 'recent', 'salary'], true)) {
    $sort = 'match';
}

$profile = fetchSeekerProfile($userId);
$cvMeta = seekerCvMeta($profile ?? []);
$jobs = getSeekerRecommendations($userId, $limit, $sort);

echo json_encode([
    'success' => true,
    'jobs' => array_map('formatSeekerJobForApi', $jobs),
    'has_cv' => $cvMeta['has_cv'],
    'cv' => $cvMeta,
    'skills' => $profile['skill_list'] ?? [],
    'match_count' => countSeekerJobMatches($userId),
]);
