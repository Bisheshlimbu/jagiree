<?php
/**
 * Job applications between seekers and employers.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/jobs.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/helpers.php';

function ensureApplicationsSchema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS job_applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER NOT NULL,
            seeker_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT \'applied\' CHECK (status IN (\'applied\', \'review\', \'interview\', \'rejected\', \'hired\', \'completed\')),
            match_score INTEGER NOT NULL DEFAULT 0,
            cv_path TEXT NULL,
            cover_letter TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE (job_id, seeker_id)
        )'
    );

    $columns = array_column(
        $pdo->query('PRAGMA table_info(job_applications)')->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );

    if (!in_array('cv_path', $columns, true)) {
        $pdo->exec('ALTER TABLE job_applications ADD COLUMN cv_path TEXT NULL');
    }

    if (!in_array('cover_letter', $columns, true)) {
        $pdo->exec('ALTER TABLE job_applications ADD COLUMN cover_letter TEXT NULL');
    }

    if (!in_array('interview_reply', $columns, true)) {
        $pdo->exec('ALTER TABLE job_applications ADD COLUMN interview_reply TEXT NULL');
    }

    if (!in_array('interview_date', $columns, true)) {
        $pdo->exec('ALTER TABLE job_applications ADD COLUMN interview_date TEXT NULL');
    }

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_job_applications_job ON job_applications (job_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_job_applications_seeker ON job_applications (seeker_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_job_applications_status ON job_applications (status)');

    migrateJobApplicationsCompletedStatus();
    completePastInterviewApplications();

    $checked = true;
}

function migrateJobApplicationsCompletedStatus(): void
{
    require_once __DIR__ . '/settings.php';

    if (getSiteSetting('job_applications_completed_v1') === '1') {
        return;
    }

    $pdo = db();

    $pdo->exec('PRAGMA foreign_keys = OFF');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS job_applications__completed (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER NOT NULL,
            seeker_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT \'applied\' CHECK (status IN (\'applied\', \'review\', \'interview\', \'rejected\', \'hired\', \'completed\')),
            match_score INTEGER NOT NULL DEFAULT 0,
            cv_path TEXT NULL,
            cover_letter TEXT NULL,
            interview_reply TEXT NULL,
            interview_date TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE (job_id, seeker_id)
        )'
    );

    $pdo->exec(
        'INSERT INTO job_applications__completed (
            id, job_id, seeker_id, status, match_score, cv_path, cover_letter,
            interview_reply, interview_date, created_at
         )
         SELECT
            id, job_id, seeker_id, status, match_score, cv_path, cover_letter,
            interview_reply, interview_date, created_at
         FROM job_applications'
    );

    $pdo->exec('DROP TABLE job_applications');
    $pdo->exec('ALTER TABLE job_applications__completed RENAME TO job_applications');

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_job_applications_job ON job_applications (job_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_job_applications_seeker ON job_applications (seeker_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_job_applications_status ON job_applications (status)');

    $pdo->exec('PRAGMA foreign_keys = ON');

    saveSiteSetting('job_applications_completed_v1', '1');
}

function completePastInterviewApplications(): int
{
    $stmt = db()->query(
        "SELECT a.id, a.seeker_id, j.title AS job_title
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE a.status = 'interview'
           AND a.interview_date IS NOT NULL
           AND trim(a.interview_date) != ''
           AND date(a.interview_date) < date('now')"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($rows === []) {
        return 0;
    }

    db()->exec(
        "UPDATE job_applications
         SET status = 'completed'
         WHERE status = 'interview'
           AND interview_date IS NOT NULL
           AND trim(interview_date) != ''
           AND date(interview_date) < date('now')"
    );

    require_once __DIR__ . '/notifications.php';
    foreach ($rows as $row) {
        $seekerId = (int) ($row['seeker_id'] ?? 0);
        $jobTitle = trim($row['job_title'] ?? '') ?: 'your application';
        if ($seekerId > 0) {
            notifySeekerInterviewCompleted($seekerId, (int) ($row['id'] ?? 0), $jobTitle);
        }
    }

    return count($rows);
}

function applicationStatusLabel(string $status): string
{
    return match ($status) {
        'review' => 'Under Review',
        'interview' => 'Interview Scheduled',
        'completed' => 'Completed',
        'rejected' => 'Not Selected',
        'hired' => 'Hired',
        default => 'Applied',
    };
}

function applicationStatusClass(string $status): string
{
    return match ($status) {
        'review' => 'review',
        'interview' => 'interview',
        'completed' => 'completed',
        'rejected' => 'rejected',
        'hired' => 'hired',
        default => 'applied',
    };
}

function seekerAppliedJobIds(int $seekerId): array
{
    ensureApplicationsSchema();

    $stmt = db()->prepare(
        'SELECT job_id FROM job_applications WHERE seeker_id = :seeker_id'
    );
    $stmt->execute(['seeker_id' => $seekerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    return array_map('intval', $rows);
}

function seekerHasApplied(int $seekerId, int $jobId): bool
{
    ensureApplicationsSchema();

    $stmt = db()->prepare(
        'SELECT 1 FROM job_applications WHERE seeker_id = :seeker_id AND job_id = :job_id LIMIT 1'
    );
    $stmt->execute(['seeker_id' => $seekerId, 'job_id' => $jobId]);

    return (bool) $stmt->fetchColumn();
}

function countSeekerApplications(int $seekerId): int
{
    ensureApplicationsSchema();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM job_applications WHERE seeker_id = :seeker_id'
    );
    $stmt->execute(['seeker_id' => $seekerId]);

    return (int) $stmt->fetchColumn();
}

function seekerApplicationStatusSummary(int $seekerId): array
{
    ensureApplicationsSchema();

    $stmt = db()->prepare(
        'SELECT status, COUNT(*) AS total
         FROM job_applications
         WHERE seeker_id = :seeker_id
         GROUP BY status'
    );
    $stmt->execute(['seeker_id' => $seekerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $summary = [
        'applied' => 0,
        'review' => 0,
        'interview' => 0,
        'completed' => 0,
        'rejected' => 0,
        'hired' => 0,
        'total' => 0,
    ];

    foreach ($rows as $row) {
        $status = $row['status'] ?? 'applied';
        $count = (int) ($row['total'] ?? 0);
        if (isset($summary[$status])) {
            $summary[$status] = $count;
        }
        $summary['total'] += $count;
    }

    return $summary;
}

function seekerCvPath(int $seekerId): ?string
{
    ensureUsersSchema();

    $stmt = db()->prepare(
        "SELECT cv_path FROM users WHERE id = :id AND role = 'seeker' LIMIT 1"
    );
    $stmt->execute(['id' => $seekerId]);
    $path = trim((string) ($stmt->fetchColumn() ?: ''));

    if (!isValidApplicationCvPath($path)) {
        return null;
    }

    require_once __DIR__ . '/helpers.php';

    if (!is_file(appPublicPath(ltrim($path, '/')))) {
        return null;
    }

    return $path;
}

function seekerHasCv(int $seekerId): bool
{
    return seekerCvPath($seekerId) !== null;
}

function isValidApplicationCvPath(?string $path): bool
{
    $path = trim((string) $path);

    return $path !== ''
        && (str_starts_with($path, '/assets/uploads/cvs/')
            || str_starts_with($path, '/assets/uploads/application-cvs/'));
}

function copyCvForApplication(string $sourcePath, int $seekerId, int $jobId): ?string
{
    if (!isValidApplicationCvPath($sourcePath) || !str_starts_with($sourcePath, '/assets/uploads/cvs/')) {
        return null;
    }

    $fullSource = appPublicPath(ltrim($sourcePath, '/'));
    if (!is_file($fullSource)) {
        return null;
    }

    $extension = strtolower(pathinfo($fullSource, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        return null;
    }

    $uploadDir = appPublicPath('assets/uploads/application-cvs');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = sprintf(
        'application-%d-%d-%s.%s',
        $seekerId,
        $jobId,
        bin2hex(random_bytes(8)),
        $extension
    );
    $destination = $uploadDir . '/' . $filename;

    if (!copy($fullSource, $destination)) {
        return null;
    }

    return '/assets/uploads/application-cvs/' . $filename;
}

function applyToJob(int $seekerId, int $jobId, int $matchScore = 0, string $coverLetter = '', ?array $cvFile = null): array
{
    ensureApplicationsSchema();
    ensureJobsSchema();

    if ($seekerId <= 0 || $jobId <= 0) {
        return ['success' => false, 'error' => 'Invalid application request.'];
    }

    if (seekerHasApplied($seekerId, $jobId)) {
        return ['success' => false, 'error' => 'You have already applied to this job.', 'already_applied' => true];
    }

    $stmt = db()->prepare(
        "SELECT id, employer_id, title, skills, status FROM jobs WHERE id = :id AND status = 'approved' LIMIT 1"
    );
    $stmt->execute(['id' => $jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        return ['success' => false, 'error' => 'This job is no longer available.'];
    }

    if ($cvFile && ($cvFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        require_once __DIR__ . '/seeker/profile.php';
        $uploadResult = uploadSeekerCv($seekerId, $cvFile);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }
    }

    $profileCvPath = seekerCvPath($seekerId);
    if ($profileCvPath === null) {
        return [
            'success' => false,
            'needs_cv' => true,
            'error' => 'Attach your CV to submit this application.',
            'profile_url' => '/seeker/profile.php?tab=skills',
        ];
    }

    $applicationCvPath = copyCvForApplication($profileCvPath, $seekerId, $jobId);
    if ($applicationCvPath === null) {
        return [
            'success' => false,
            'needs_cv' => true,
            'error' => 'Your CV could not be attached. Please upload it again.',
            'profile_url' => '/seeker/profile.php?tab=skills',
        ];
    }

    $coverLetter = trim($coverLetter);
    if (mb_strlen($coverLetter) > 5000) {
        return ['success' => false, 'error' => 'Cover letter must be 5000 characters or fewer.'];
    }

    try {
        $insert = db()->prepare(
            'INSERT INTO job_applications (job_id, seeker_id, status, match_score, cv_path, cover_letter)
             VALUES (:job_id, :seeker_id, :status, :match_score, :cv_path, :cover_letter)'
        );
        $insert->execute([
            'job_id' => $jobId,
            'seeker_id' => $seekerId,
            'status' => 'applied',
            'match_score' => max(0, min(99, $matchScore)),
            'cv_path' => $applicationCvPath,
            'cover_letter' => $coverLetter !== '' ? $coverLetter : null,
        ]);
    } catch (PDOException) {
        if (seekerHasApplied($seekerId, $jobId)) {
            return ['success' => false, 'error' => 'You have already applied to this job.', 'already_applied' => true];
        }

        return ['success' => false, 'error' => 'Could not submit your application. Please try again.'];
    }

    $applicationId = (int) db()->lastInsertId();
    $employerId = (int) ($job['employer_id'] ?? 0);

    if ($employerId > 0) {
        require_once __DIR__ . '/notifications.php';

        $seekerStmt = db()->prepare('SELECT full_name FROM users WHERE id = :id LIMIT 1');
        $seekerStmt->execute(['id' => $seekerId]);
        $seekerName = trim((string) ($seekerStmt->fetchColumn() ?: '')) ?: 'A seeker';
        $jobTitle = trim($job['title'] ?? '') ?: 'your job';

        notifyEmployerNewApplication($employerId, $applicationId, $seekerName, $jobTitle);
    }

    return [
        'success' => true,
        'application_id' => $applicationId,
        'message' => 'Application submitted with your CV! Track it under Applications.',
    ];
}

function seekerApplicationEditable(string $status): bool
{
    return $status === 'applied';
}

function seekerApplicationDeletable(string $status): bool
{
    return $status === 'applied';
}

function deleteApplicationCvFile(?string $path): void
{
    if (!isValidApplicationCvPath($path) || !str_starts_with($path, '/assets/uploads/application-cvs/')) {
        return;
    }

    $fullPath = appPublicPath(ltrim($path, '/'));
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function fetchSeekerApplicationRow(int $seekerId, int $applicationId): ?array
{
    ensureApplicationsSchema();

    if ($seekerId <= 0 || $applicationId <= 0) {
        return null;
    }

    $stmt = db()->prepare(
        "SELECT a.id, a.job_id, a.seeker_id, a.status, a.match_score, a.cv_path, a.cover_letter, a.created_at,
                j.title, j.company_name, j.location, j.status AS job_status
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE a.id = :id AND a.seeker_id = :seeker_id
         LIMIT 1"
    );
    $stmt->execute(['id' => $applicationId, 'seeker_id' => $seekerId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function updateSeekerApplication(int $seekerId, int $applicationId, string $coverLetter = '', ?array $cvFile = null): array
{
    ensureApplicationsSchema();

    $application = fetchSeekerApplicationRow($seekerId, $applicationId);
    if (!$application) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!seekerApplicationEditable($application['status'] ?? '')) {
        return ['success' => false, 'error' => 'This application can no longer be edited.'];
    }

    $coverLetter = trim($coverLetter);
    if (mb_strlen($coverLetter) > 5000) {
        return ['success' => false, 'error' => 'Cover letter must be 5000 characters or fewer.'];
    }

    $jobId = (int) ($application['job_id'] ?? 0);
    $cvPath = isValidApplicationCvPath($application['cv_path'] ?? null) ? $application['cv_path'] : null;

    if ($cvFile && ($cvFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        require_once __DIR__ . '/seeker/profile.php';
        $uploadResult = uploadSeekerCv($seekerId, $cvFile);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }

        $profileCvPath = seekerCvPath($seekerId);
        if ($profileCvPath === null) {
            return [
                'success' => false,
                'needs_cv' => true,
                'error' => 'Attach your CV to save this application.',
                'profile_url' => '/seeker/profile.php?tab=skills',
            ];
        }

        $newCvPath = copyCvForApplication($profileCvPath, $seekerId, $jobId);
        if ($newCvPath === null) {
            return ['success' => false, 'error' => 'Your CV could not be attached. Please try again.'];
        }

        if ($cvPath !== null && $cvPath !== $newCvPath) {
            deleteApplicationCvFile($cvPath);
        }
        $cvPath = $newCvPath;
    }

    if ($cvPath === null) {
        $profileCvPath = seekerCvPath($seekerId);
        if ($profileCvPath === null) {
            return [
                'success' => false,
                'needs_cv' => true,
                'error' => 'Attach your CV to save this application.',
                'profile_url' => '/seeker/profile.php?tab=skills',
            ];
        }

        $cvPath = copyCvForApplication($profileCvPath, $seekerId, $jobId);
        if ($cvPath === null) {
            return ['success' => false, 'error' => 'Your CV could not be attached. Please upload it again.'];
        }
    }

    $stmt = db()->prepare(
        'UPDATE job_applications
         SET cover_letter = :cover_letter, cv_path = :cv_path
         WHERE id = :id AND seeker_id = :seeker_id'
    );
    $stmt->execute([
        'cover_letter' => $coverLetter !== '' ? $coverLetter : null,
        'cv_path' => $cvPath,
        'id' => $applicationId,
        'seeker_id' => $seekerId,
    ]);

    return [
        'success' => true,
        'message' => 'Application updated successfully.',
        'cv_path' => $cvPath,
    ];
}

function deleteSeekerApplication(int $seekerId, int $applicationId): array
{
    ensureApplicationsSchema();

    $application = fetchSeekerApplicationRow($seekerId, $applicationId);
    if (!$application) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    if (!seekerApplicationDeletable($application['status'] ?? '')) {
        return ['success' => false, 'error' => 'This application cannot be withdrawn.'];
    }

    deleteApplicationCvFile($application['cv_path'] ?? null);

    $stmt = db()->prepare(
        'DELETE FROM job_applications WHERE id = :id AND seeker_id = :seeker_id'
    );
    $stmt->execute(['id' => $applicationId, 'seeker_id' => $seekerId]);

    return [
        'success' => true,
        'job_id' => (int) ($application['job_id'] ?? 0),
        'message' => 'Application withdrawn.',
    ];
}

function fetchSeekerApplications(int $seekerId): array
{
    ensureApplicationsSchema();
    completePastInterviewApplications();

    $stmt = db()->prepare(
        "SELECT a.id, a.job_id, a.status, a.match_score, a.cv_path, a.cover_letter,
                a.interview_reply, a.interview_date, a.created_at,
                j.title, j.company_name, j.location, j.job_type,
                u.avatar_path AS employer_avatar_path
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         LEFT JOIN users u ON u.id = j.employer_id
         WHERE a.seeker_id = :seeker_id
         ORDER BY datetime(a.created_at) DESC, a.id DESC"
    );
    $stmt->execute(['seeker_id' => $seekerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $applications = [];
    foreach ($rows as $row) {
        $company = trim($row['company_name'] ?? '');
        $employerUser = ['avatar_path' => $row['employer_avatar_path'] ?? null];
        $hasLogo = function_exists('userHasAvatar') && userHasAvatar($employerUser);

        $status = $row['status'] ?? 'applied';

        $applications[] = [
            'id' => (int) $row['id'],
            'job_id' => (int) $row['job_id'],
            'title' => $row['title'] ?? '',
            'company' => $company,
            'location' => trim($row['location'] ?? '') ?: 'Location not set',
            'applied' => formatJobSubmittedAt($row['created_at'] ?? null),
            'status' => $status,
            'statusLabel' => applicationStatusLabel($status),
            'match' => (int) ($row['match_score'] ?? 0),
            'logo' => strtoupper(mb_substr($company !== '' ? $company : 'J', 0, 1)),
            'logo_url' => $hasLogo ? userAvatarUrl($employerUser) : null,
            'has_logo' => $hasLogo,
            'cv_path' => isValidApplicationCvPath($row['cv_path'] ?? null) ? $row['cv_path'] : null,
            'cover_letter' => trim($row['cover_letter'] ?? ''),
            'interview_reply' => trim($row['interview_reply'] ?? ''),
            'interview_date' => trim($row['interview_date'] ?? ''),
            'interview_date_label' => formatApplicationInterviewDate($row['interview_date'] ?? null),
            'can_edit' => seekerApplicationEditable($status),
            'can_delete' => seekerApplicationDeletable($status),
        ];
    }

    return $applications;
}

function dbStatusToEmployerStatus(string $status): string
{
    return match ($status) {
        'applied' => 'new',
        'interview' => 'interviewing',
        'review' => 'review',
        'rejected' => 'rejected',
        'completed' => 'completed',
        'hired' => 'interviewing',
        default => 'new',
    };
}

function employerApplicationStatusLocked(string $employerStatus): bool
{
    return $employerStatus === 'completed';
}

function employerApplicationStatusLockedByDb(string $dbStatus): bool
{
    return $dbStatus === 'completed';
}

function employerStatusToDbStatus(string $employerStatus): ?string
{
    return match ($employerStatus) {
        'new' => 'applied',
        'interviewing' => 'interview',
        'review' => 'review',
        'rejected' => 'rejected',
        default => null,
    };
}

function employerApplicationStatusOptions(): array
{
    return [
        'new' => 'New',
        'review' => 'In review',
        'interviewing' => 'Interviewing',
        'rejected' => 'Reject',
    ];
}

function employerApplicationStatusLabel(string $employerStatus): string
{
    return match ($employerStatus) {
        'completed' => 'Completed',
        default => employerApplicationStatusOptions()[$employerStatus] ?? ucfirst($employerStatus),
    };
}

function employerApplicationFilterStatusOptions(): array
{
    return employerApplicationStatusOptions() + ['completed' => 'Completed'];
}

function formatApplicationInterviewDate(?string $date): ?string
{
    $date = trim((string) $date);
    if ($date === '') {
        return null;
    }

    $time = strtotime($date);
    if ($time === false) {
        return null;
    }

    return date('l, F j, Y', $time);
}

function validateInterviewScheduleInput(string $replyMessage, ?string $interviewDate): array
{
    $replyMessage = trim($replyMessage);
    if ($replyMessage === '') {
        return ['success' => false, 'error' => 'Please add a reply message for the applicant.'];
    }

    if (mb_strlen($replyMessage) > 2000) {
        return ['success' => false, 'error' => 'Reply message must be 2000 characters or fewer.'];
    }

    $interviewDate = trim((string) $interviewDate);
    if ($interviewDate === '') {
        return ['success' => false, 'error' => 'Please choose an interview date.'];
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $interviewDate);
    $errors = DateTime::getLastErrors();
    if (!$parsed || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
        return ['success' => false, 'error' => 'Please choose a valid interview date.'];
    }

    $parsed->setTime(0, 0, 0);
    $today = new DateTime('today');
    if ($parsed < $today) {
        return ['success' => false, 'error' => 'Interview date cannot be in the past.'];
    }

    return [
        'success' => true,
        'reply_message' => $replyMessage,
        'interview_date' => $parsed->format('Y-m-d'),
    ];
}

function formatEmployerApplicantRow(array $row, int $index = 0): array
{
    $colors = ['#0a66c2', '#057642', '#915907', '#5f259f', '#8b5cf6', '#2563eb'];
    $name = trim($row['seeker_name'] ?? '') ?: 'Applicant';
    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = strtoupper(mb_substr($parts[0] ?? 'A', 0, 1));
    if (isset($parts[1])) {
        $initials .= strtoupper(mb_substr($parts[1], 0, 1));
    }

    $status = $row['status'] ?? 'applied';
    $employerStatus = dbStatusToEmployerStatus($status);
    $seekerUser = ['avatar_path' => $row['seeker_avatar'] ?? null];
    $hasAvatar = function_exists('userHasAvatar') && userHasAvatar($seekerUser);

    return [
        'id' => (int) ($row['id'] ?? 0),
        'job_id' => (int) ($row['job_id'] ?? 0),
        'seeker_id' => (int) ($row['seeker_id'] ?? 0),
        'name' => $name,
        'initials' => $initials,
        'color' => $colors[$index % count($colors)],
        'avatar_url' => $hasAvatar ? userAvatarUrl($seekerUser) : null,
        'has_avatar' => $hasAvatar,
        'role' => $row['job_title'] ?? '',
        'status' => $employerStatus,
        'status_label' => employerApplicationStatusLabel($employerStatus),
        'status_locked' => employerApplicationStatusLocked($employerStatus),
        'match' => (int) ($row['match_score'] ?? 0),
        'date' => formatJobSubmittedAt($row['created_at'] ?? null),
        'cv_path' => isValidApplicationCvPath($row['cv_path'] ?? null) ? $row['cv_path'] : null,
        'cover_letter' => trim($row['cover_letter'] ?? ''),
        'has_cover_letter' => trim($row['cover_letter'] ?? '') !== '',
    ];
}

function fetchEmployerApplicantJobs(int $employerId): array
{
    ensureApplicationsSchema();
    ensureJobsSchema();

    $stmt = db()->prepare(
        "SELECT j.id, j.title, COUNT(a.id) AS applicant_count
         FROM jobs j
         INNER JOIN job_applications a ON a.job_id = j.id
         WHERE j.employer_id = :employer_id
         GROUP BY j.id
         ORDER BY j.title ASC"
    );
    $stmt->execute(['employer_id' => $employerId]);

    $jobs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $jobs[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'] ?? '',
            'applicant_count' => (int) ($row['applicant_count'] ?? 0),
        ];
    }

    return $jobs;
}

function fetchEmployerApplicants(int $employerId, array $filters = []): array
{
    ensureApplicationsSchema();
    completePastInterviewApplications();

    $jobId = (int) ($filters['job_id'] ?? 0);
    $statusFilter = trim($filters['status'] ?? '');
    $sort = trim($filters['sort'] ?? 'recent');

    $sql = "SELECT a.id, a.job_id, a.seeker_id, a.status, a.match_score, a.cv_path, a.cover_letter, a.created_at,
                j.title AS job_title,
                u.full_name AS seeker_name,
                u.avatar_path AS seeker_avatar
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.seeker_id
         WHERE j.employer_id = :employer_id";

    $params = ['employer_id' => $employerId];

    if ($jobId > 0) {
        $sql .= ' AND a.job_id = :job_id';
        $params['job_id'] = $jobId;
    }

    if ($statusFilter !== '') {
        $dbStatus = $statusFilter === 'completed'
            ? 'completed'
            : employerStatusToDbStatus($statusFilter);
        if ($dbStatus !== null) {
            $sql .= ' AND a.status = :status';
            $params['status'] = $dbStatus;
        }
    }

    $orderBy = $sort === 'match'
        ? 'a.match_score DESC, datetime(a.created_at) DESC, a.id DESC'
        : 'datetime(a.created_at) DESC, a.id DESC';
    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $applicants = [];
    foreach ($rows as $i => $row) {
        $applicants[] = formatEmployerApplicantRow($row, $i);
    }

    return $applicants;
}

function fetchEmployerApplicationDetail(int $employerId, int $applicationId): ?array
{
    ensureApplicationsSchema();

    if ($employerId <= 0 || $applicationId <= 0) {
        return null;
    }

    completePastInterviewApplications();

    $stmt = db()->prepare(
        "SELECT a.id, a.job_id, a.seeker_id, a.status, a.match_score, a.cv_path, a.cover_letter,
                a.interview_reply, a.interview_date, a.created_at,
                j.title AS job_title, j.company_name, j.location AS job_location, j.skills AS job_skills,
                u.full_name AS seeker_name, u.headline, u.about, u.skills, u.location AS seeker_location,
                u.avatar_path AS seeker_avatar, u.open_to_work
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.seeker_id
         WHERE a.id = :id AND j.employer_id = :employer_id
         LIMIT 1"
    );
    $stmt->execute(['id' => $applicationId, 'employer_id' => $employerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    require_once __DIR__ . '/seeker/profile.php';

    $seekerId = (int) ($row['seeker_id'] ?? 0);
    $applicant = formatEmployerApplicantRow($row, 0);
    $seekerSkills = parseSkillsList($row['skills'] ?? null);
    $jobSkills = parseSkillsList($row['job_skills'] ?? null);
    $education = fetchSeekerEducation($seekerId);
    $experience = fetchSeekerExperience($seekerId);

    return [
        'application' => array_merge($applicant, [
            'applied_at' => $row['created_at'] ?? null,
            'cover_letter' => trim($row['cover_letter'] ?? ''),
            'has_cover_letter' => trim($row['cover_letter'] ?? '') !== '',
            'interview_reply' => trim($row['interview_reply'] ?? ''),
            'interview_date' => trim($row['interview_date'] ?? ''),
            'interview_date_label' => formatApplicationInterviewDate($row['interview_date'] ?? null),
            'status_locked' => employerApplicationStatusLocked($applicant['status'] ?? ''),
        ]),
        'job' => [
            'id' => (int) ($row['job_id'] ?? 0),
            'title' => $row['job_title'] ?? '',
            'company' => trim($row['company_name'] ?? ''),
            'location' => trim($row['job_location'] ?? '') ?: 'Location not set',
            'skills' => $jobSkills,
        ],
        'seeker' => [
            'id' => $seekerId,
            'name' => trim($row['seeker_name'] ?? '') ?: 'Applicant',
            'headline' => trim($row['headline'] ?? '') ?: 'No headline added',
            'about' => trim($row['about'] ?? ''),
            'location' => trim($row['seeker_location'] ?? '') ?: 'Location not set',
            'open_to_work' => !empty($row['open_to_work']),
            'skills' => $seekerSkills,
            'avatar_url' => $applicant['avatar_url'],
            'has_avatar' => $applicant['has_avatar'],
            'initials' => $applicant['initials'],
            'color' => $applicant['color'],
            'education' => $education,
            'experience' => $experience,
        ],
        'status_options' => employerApplicationStatusOptions(),
    ];
}

function updateEmployerApplicationStatus(
    int $employerId,
    int $applicationId,
    string $employerStatus,
    string $replyMessage = '',
    ?string $interviewDate = null
): array {
    ensureApplicationsSchema();

    completePastInterviewApplications();

    $dbStatus = employerStatusToDbStatus($employerStatus);
    if ($dbStatus === null) {
        return ['success' => false, 'error' => 'Invalid status selected.'];
    }

    $stmt = db()->prepare(
        "SELECT a.id, a.seeker_id, a.status AS current_status, j.title AS job_title
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE a.id = :id AND j.employer_id = :employer_id
         LIMIT 1"
    );
    $stmt->execute(['id' => $applicationId, 'employer_id' => $employerId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        return ['success' => false, 'error' => 'Application not found.'];
    }

    $currentStatus = $application['current_status'] ?? 'applied';
    if (employerApplicationStatusLockedByDb($currentStatus)) {
        return ['success' => false, 'error' => 'Completed applications cannot be changed.'];
    }

    $interviewReply = null;
    $interviewDateValue = null;

    if ($employerStatus === 'interviewing') {
        $validation = validateInterviewScheduleInput($replyMessage, $interviewDate);
        if (!$validation['success']) {
            return $validation;
        }
        $interviewReply = $validation['reply_message'];
        $interviewDateValue = $validation['interview_date'];
    }

    if ($currentStatus === $dbStatus && $employerStatus !== 'interviewing') {
        return [
            'success' => true,
            'status' => $employerStatus,
            'status_label' => employerApplicationStatusLabel($employerStatus),
            'message' => 'Application status updated.',
        ];
    }

    if ($employerStatus === 'interviewing') {
        $update = db()->prepare(
            'UPDATE job_applications
             SET status = :status, interview_reply = :interview_reply, interview_date = :interview_date
             WHERE id = :id'
        );
        $update->execute([
            'status' => $dbStatus,
            'interview_reply' => $interviewReply,
            'interview_date' => $interviewDateValue,
            'id' => $applicationId,
        ]);
    } else {
        $update = db()->prepare(
            'UPDATE job_applications
             SET status = :status, interview_reply = NULL, interview_date = NULL
             WHERE id = :id'
        );
        $update->execute(['status' => $dbStatus, 'id' => $applicationId]);
    }

    require_once __DIR__ . '/notifications.php';
    $seekerId = (int) ($application['seeker_id'] ?? 0);
    $jobTitle = trim($application['job_title'] ?? '') ?: 'a job';
    $statusLabel = employerApplicationStatusLabel($employerStatus);

    if ($employerStatus === 'interviewing' && $interviewDateValue) {
        notifySeekerInterviewScheduled(
            $seekerId,
            $applicationId,
            $jobTitle,
            $interviewDateValue,
            $interviewReply ?? ''
        );
    } else {
        notifySeekerApplicationStatus($seekerId, $applicationId, $jobTitle, $statusLabel);
    }

    return [
        'success' => true,
        'status' => $employerStatus,
        'status_label' => employerApplicationStatusLabel($employerStatus),
        'message' => $employerStatus === 'interviewing'
            ? 'Interview scheduled and applicant notified.'
            : 'Application status updated.',
    ];
}

function countEmployerApplications(int $employerId, ?int $sinceDays = null): int
{
    ensureApplicationsSchema();

    $sql = 'SELECT COUNT(*)
            FROM job_applications a
            INNER JOIN jobs j ON j.id = a.job_id
            WHERE j.employer_id = :employer_id';
    $params = ['employer_id' => $employerId];

    if ($sinceDays !== null) {
        $sql .= " AND date(a.created_at) >= date('now', :interval)";
        $params['interval'] = '-' . $sinceDays . ' days';
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function countEmployerApplicationsByStatus(int $employerId, string $status): int
{
    ensureApplicationsSchema();

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         WHERE j.employer_id = :employer_id AND a.status = :status'
    );
    $stmt->execute(['employer_id' => $employerId, 'status' => $status]);

    return (int) $stmt->fetchColumn();
}

function countJobApplicants(int $jobId): int
{
    ensureApplicationsSchema();

    $stmt = db()->prepare('SELECT COUNT(*) FROM job_applications WHERE job_id = :job_id');
    $stmt->execute(['job_id' => $jobId]);

    return (int) $stmt->fetchColumn();
}

function countNewJobApplicantsSince(int $jobId, int $days): int
{
    ensureApplicationsSchema();

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM job_applications
         WHERE job_id = :job_id AND date(created_at) >= date('now', :interval)"
    );
    $stmt->execute([
        'job_id' => $jobId,
        'interval' => '-' . $days . ' days',
    ]);

    return (int) $stmt->fetchColumn();
}

function fetchEmployerRecentApplications(int $employerId, int $limit = 5): array
{
    ensureApplicationsSchema();
    completePastInterviewApplications();

    $stmt = db()->prepare(
        "SELECT a.id, a.job_id, a.seeker_id, a.status, a.match_score, a.cv_path, a.cover_letter, a.created_at,
                j.title AS job_title,
                u.full_name AS seeker_name,
                u.avatar_path AS seeker_avatar
         FROM job_applications a
         INNER JOIN jobs j ON j.id = a.job_id
         INNER JOIN users u ON u.id = a.seeker_id
         WHERE j.employer_id = :employer_id
         ORDER BY datetime(a.created_at) DESC, a.id DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':employer_id', $employerId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $applications = [];
    foreach ($rows as $i => $row) {
        $applications[] = formatEmployerApplicantRow($row, $i);
    }

    return $applications;
}
