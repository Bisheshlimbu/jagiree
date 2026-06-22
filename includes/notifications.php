<?php
/**
 * In-app notifications for seekers, employers, and admins.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

function ensureNotificationsSchema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            link TEXT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications (user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications (user_id, is_read)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications (created_at)');

    $checked = true;
}

function createNotification(int $userId, string $type, string $title, string $message, ?string $link = null): bool
{
    ensureNotificationsSchema();

    if ($userId <= 0 || trim($title) === '' || trim($message) === '') {
        return false;
    }

    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (:user_id, :type, :title, :message, :link)'
    );

    return $stmt->execute([
        'user_id' => $userId,
        'type' => $type,
        'title' => trim($title),
        'message' => trim($message),
        'link' => $link !== null && trim($link) !== '' ? trim($link) : null,
    ]);
}

function countUnreadNotifications(int $userId): int
{
    ensureNotificationsSchema();

    if ($userId <= 0) {
        return 0;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
    );
    $stmt->execute(['user_id' => $userId]);

    return (int) $stmt->fetchColumn();
}

function fetchUserNotifications(int $userId, int $limit = 20): array
{
    ensureNotificationsSchema();

    if ($userId <= 0) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, type, title, message, link, is_read, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY datetime(created_at) DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $notifications = [];

    foreach ($rows as $row) {
        $notifications[] = [
            'id' => (int) $row['id'],
            'type' => $row['type'] ?? '',
            'title' => $row['title'] ?? '',
            'message' => $row['message'] ?? '',
            'link' => $row['link'] ?? null,
            'is_read' => !empty($row['is_read']),
            'time' => formatNotificationTime($row['created_at'] ?? null),
        ];
    }

    return $notifications;
}

function formatNotificationTime(?string $timestamp): string
{
    if ($timestamp === null || trim($timestamp) === '') {
        return 'Just now';
    }

    $time = strtotime($timestamp);
    if ($time === false) {
        return 'Recently';
    }

    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = max(1, (int) floor($diff / 60));
        return $mins === 1 ? '1 minute ago' : $mins . ' minutes ago';
    }
    if ($diff < 86400) {
        $hours = max(1, (int) floor($diff / 3600));
        return $hours === 1 ? '1 hour ago' : $hours . ' hours ago';
    }
    if ($diff < 604800) {
        $days = max(1, (int) floor($diff / 86400));
        return $days === 1 ? '1 day ago' : $days . ' days ago';
    }

    return date('M j, Y', $time);
}

function markNotificationRead(int $userId, int $notificationId): bool
{
    ensureNotificationsSchema();

    $stmt = db()->prepare(
        'UPDATE notifications SET is_read = 1
         WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);

    return $stmt->rowCount() > 0;
}

function markAllNotificationsRead(int $userId): int
{
    ensureNotificationsSchema();

    $stmt = db()->prepare(
        'UPDATE notifications SET is_read = 1
         WHERE user_id = :user_id AND is_read = 0'
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->rowCount();
}

function notifyEmployerNewApplication(int $employerId, int $applicationId, string $seekerName, string $jobTitle): void
{
    if ($employerId <= 0) {
        return;
    }

    createNotification(
        $employerId,
        'application_received',
        'New application',
        sprintf('%s applied to %s', $seekerName, $jobTitle),
        '/employer/applicants.php'
    );
}

function notifySeekerApplicationStatus(int $seekerId, int $applicationId, string $jobTitle, string $statusLabel): void
{
    if ($seekerId <= 0) {
        return;
    }

    createNotification(
        $seekerId,
        'application_status',
        'Application update',
        sprintf('Your application for %s is now: %s', $jobTitle, $statusLabel),
        '/seeker/applications.php'
    );
}

function notifySeekerInterviewScheduled(
    int $seekerId,
    int $applicationId,
    string $jobTitle,
    string $interviewDate,
    string $replyMessage
): void {
    if ($seekerId <= 0) {
        return;
    }

    $time = strtotime($interviewDate);
    $dateLabel = $time ? date('l, F j, Y', $time) : $interviewDate;
    $preview = mb_strlen($replyMessage) > 120 ? mb_substr($replyMessage, 0, 120) . '…' : $replyMessage;

    createNotification(
        $seekerId,
        'interview_scheduled',
        'Interview scheduled',
        sprintf('Interview for %s on %s. %s', $jobTitle, $dateLabel, $preview),
        '/seeker/applications.php'
    );
}

function notifySeekerInterviewCompleted(int $seekerId, int $applicationId, string $jobTitle): void
{
    if ($seekerId <= 0) {
        return;
    }

    createNotification(
        $seekerId,
        'interview_completed',
        'Interview completed',
        sprintf('Your interview for %s is marked completed.', $jobTitle),
        '/seeker/applications.php'
    );
}

function notifyEmployerJobReviewed(int $employerId, int $jobId, string $jobTitle, string $status): void
{
    if ($employerId <= 0) {
        return;
    }

    if ($status === 'approved') {
        createNotification(
            $employerId,
            'job_approved',
            'Job approved',
            sprintf('"%s" is now live and accepting applications.', $jobTitle),
            '/employer/job-listings.php'
        );
        return;
    }

    if ($status === 'rejected') {
        createNotification(
            $employerId,
            'job_rejected',
            'Job not approved',
            sprintf('"%s" was not approved. You can edit and resubmit.', $jobTitle),
            '/employer/job-listings.php'
        );
    }
}
