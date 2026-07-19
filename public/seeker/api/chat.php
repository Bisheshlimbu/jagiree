<?php
/**
 * Seeker chatbot API — FAQ replies + NLP recommendations.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/chatbot.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

authStartSession();
$user = currentUser();

if (!$user || ($user['role'] ?? '') !== ROLE_SEEKER) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please sign in as a job seeker.']);
    exit;
}

$payload = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
} else {
    $payload = $_POST;
}

$message = trim((string) ($payload['message'] ?? ''));
if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Message is required.']);
    exit;
}

if (mb_strlen($message) > 500) {
    echo json_encode(['success' => false, 'error' => 'Message is too long (max 500 characters).']);
    exit;
}

$reply = generateChatbotReply((int) $user['id'], $message);
echo json_encode($reply, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
