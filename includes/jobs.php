<?php
/**
 * Job listings for admin review and public display.
 */

require_once __DIR__ . '/database.php';

function ensureJobsSchema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employer_id INTEGER NULL,
            company_name TEXT NOT NULL,
            title TEXT NOT NULL,
            location TEXT NULL,
            job_type TEXT NOT NULL DEFAULT \'full-time\',
            salary TEXT NULL,
            skills TEXT NULL,
            description TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT \'pending\' CHECK (status IN (\'pending\', \'approved\', \'rejected\')),
            created_by TEXT NOT NULL DEFAULT \'admin\' CHECK (created_by IN (\'admin\', \'employer\')),
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE SET NULL
        )'
    );

    $columns = array_column(
        $pdo->query('PRAGMA table_info(jobs)')->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );

    if (!in_array('created_by', $columns, true)) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN created_by TEXT NOT NULL DEFAULT 'admin'");
    }

    if (!in_array('source', $columns, true)) {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN source TEXT NOT NULL DEFAULT 'employer'");
    }

    if (!in_array('external_id', $columns, true)) {
        $pdo->exec('ALTER TABLE jobs ADD COLUMN external_id TEXT NULL');
    }

    if (!in_array('external_url', $columns, true)) {
        $pdo->exec('ALTER TABLE jobs ADD COLUMN external_url TEXT NULL');
    }

    if (!in_array('synced_at', $columns, true)) {
        $pdo->exec('ALTER TABLE jobs ADD COLUMN synced_at TEXT NULL');
    }

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs (status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_created_at ON jobs (created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_jobs_source ON jobs (source)');
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_jobs_external_source ON jobs (source, external_id) WHERE external_id IS NOT NULL');

    $checked = true;
}

function jobIsExternal(array $job): bool
{
    return ($job['source'] ?? '') === 'linkedin' || trim($job['external_url'] ?? '') !== '';
}

function jobSourceLabel(?string $source): string
{
    return match ($source) {
        'linkedin' => 'LinkedIn',
        'admin' => 'Jagiree',
        'employer' => 'Jagiree',
        default => 'Jagiree',
    };
}

function jobStatusLabel(string $status): string
{
    return match ($status) {
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => 'Pending',
    };
}

function jobCreatedByLabel(string $createdBy): string
{
    return $createdBy === 'employer' ? 'Employer' : 'Admin';
}

function formatEmployerOptionLabel(array $employer): string
{
    $name = trim($employer['full_name'] ?? '');
    $company = trim($employer['company_name'] ?? '');

    if ($name !== '' && $company !== '') {
        return $name . ' — ' . $company;
    }

    return $name !== '' ? $name : ($company !== '' ? $company : 'Employer');
}

function formatJobSubmittedAt(?string $createdAt): string
{
    if (!$createdAt) {
        return '—';
    }

    $timestamp = strtotime($createdAt);
    if ($timestamp === false) {
        return '—';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $minutes = max(1, (int) floor($diff / 60));
        return $minutes === 1 ? '1 minute ago' : $minutes . ' minutes ago';
    }
    if ($diff < 86400) {
        $hours = max(1, (int) floor($diff / 3600));
        return $hours === 1 ? '1 hour ago' : $hours . ' hours ago';
    }
    if ($diff < 604800) {
        $days = max(1, (int) floor($diff / 86400));
        return $days === 1 ? '1 day ago' : $days . ' days ago';
    }

    return date('M j, Y', $timestamp);
}

function formatAdminJobRow(array $job): array
{
    $employerName = trim($job['employer_name'] ?? '');

    return [
        'id' => (int) $job['id'],
        'title' => $job['title'],
        'company' => $job['company_name'],
        'employer_name' => $employerName !== '' ? $employerName : '—',
        'created_by' => $job['created_by'] ?? 'admin',
        'created_by_label' => jobCreatedByLabel($job['created_by'] ?? 'admin'),
        'submitted' => formatJobSubmittedAt($job['created_at'] ?? null),
        'status' => $job['status'] ?? 'pending',
        'status_label' => jobStatusLabel($job['status'] ?? 'pending'),
    ];
}

function fetchAdminJobs(): array
{
    ensureJobsSchema();

    $stmt = db()->query(
        'SELECT j.id, j.employer_id, j.company_name, j.title, j.location, j.job_type, j.salary, j.skills,
                j.description, j.status, j.created_by, j.created_at, u.full_name AS employer_name
         FROM jobs j
         LEFT JOIN users u ON u.id = j.employer_id
         ORDER BY datetime(j.created_at) DESC, j.id DESC'
    );

    return array_map('formatAdminJobRow', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function fetchEmployersForJobForm(): array
{
    ensureJobsSchema();

    $stmt = db()->query(
        "SELECT id, full_name, company_name, email
         FROM users
         WHERE role = 'employer' AND status = 'verified'
         ORDER BY full_name COLLATE NOCASE, company_name COLLATE NOCASE"
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function createJobByAdmin(array $data): array
{
    ensureJobsSchema();

    $title = trim($data['title'] ?? '');
    $companyName = trim($data['company_name'] ?? '');
    $location = trim($data['location'] ?? '');
    $jobType = $data['job_type'] ?? 'full-time';
    $salary = trim($data['salary'] ?? '');
    $skills = trim($data['skills'] ?? '');
    $description = trim($data['description'] ?? '');
    $status = $data['status'] ?? 'pending';
    $employerId = (int) ($data['employer_id'] ?? 0);

    if ($title === '' || $companyName === '' || $description === '') {
        return ['success' => false, 'error' => 'Job title, company name, and description are required.'];
    }

    $allowedTypes = ['full-time', 'part-time', 'contract', 'remote'];
    if (!in_array($jobType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'Invalid job type selected.'];
    }

    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        return ['success' => false, 'error' => 'Invalid status selected.'];
    }

    if ($employerId > 0) {
        $employer = db()->prepare(
            "SELECT id, company_name, full_name FROM users WHERE id = :id AND role = 'employer' AND status = 'verified' LIMIT 1"
        );
        $employer->execute(['id' => $employerId]);
        $employerRow = $employer->fetch(PDO::FETCH_ASSOC);
        if (!$employerRow) {
            return ['success' => false, 'error' => 'Selected employer was not found or is not verified.'];
        }
        if ($companyName === '') {
            $companyName = trim($employerRow['company_name'] ?? '');
        }
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO jobs (employer_id, company_name, title, location, job_type, salary, skills, description, status, created_by)
             VALUES (:employer_id, :company_name, :title, :location, :job_type, :salary, :skills, :description, :status, :created_by)'
        );
        $stmt->execute([
            'employer_id' => $employerId > 0 ? $employerId : null,
            'company_name' => $companyName,
            'title' => $title,
            'location' => $location !== '' ? $location : null,
            'job_type' => $jobType,
            'salary' => $salary !== '' ? $salary : null,
            'skills' => $skills !== '' ? $skills : null,
            'description' => $description,
            'status' => $status,
            'created_by' => 'admin',
        ]);

        return ['success' => true, 'message' => 'Job created successfully.', 'job_id' => (int) db()->lastInsertId()];
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not create job. Please try again.'];
    }
}

function createJobByEmployer(int $employerId, array $data): array
{
    ensureJobsSchema();

    $employer = db()->prepare(
        "SELECT id, full_name, company_name FROM users WHERE id = :id AND role = 'employer' AND status = 'verified' LIMIT 1"
    );
    $employer->execute(['id' => $employerId]);
    $employerRow = $employer->fetch(PDO::FETCH_ASSOC);

    if (!$employerRow) {
        return ['success' => false, 'error' => 'Your employer account must be verified before posting jobs.'];
    }

    $title = trim($data['title'] ?? '');
    $location = trim($data['location'] ?? '');
    $jobType = $data['job_type'] ?? 'full-time';
    $skills = trim($data['skills'] ?? '');
    $description = trim($data['description'] ?? '');
    $companyName = trim($employerRow['company_name'] ?? '') ?: trim($employerRow['full_name']);

    if ($title === '' || $description === '') {
        return ['success' => false, 'error' => 'Job title and description are required.'];
    }

    $allowedTypes = ['full-time', 'part-time', 'contract', 'remote'];
    if (!in_array($jobType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'Invalid job type selected.'];
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO jobs (employer_id, company_name, title, location, job_type, salary, skills, description, status, created_by)
             VALUES (:employer_id, :company_name, :title, :location, :job_type, NULL, :skills, :description, :status, :created_by)'
        );
        $stmt->execute([
            'employer_id' => $employerId,
            'company_name' => $companyName,
            'title' => $title,
            'location' => $location !== '' ? $location : null,
            'job_type' => $jobType,
            'skills' => $skills !== '' ? $skills : null,
            'description' => $description,
            'status' => 'pending',
            'created_by' => 'employer',
        ]);

        return ['success' => true, 'message' => 'Job submitted for admin approval.', 'job_id' => (int) db()->lastInsertId()];
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not submit job. Please try again.'];
    }
}

function employerJobStatusDisplay(string $status): array
{
    return match ($status) {
        'approved' => ['label' => 'Live', 'class' => 'new'],
        'rejected' => ['label' => 'Rejected', 'class' => 'rejected'],
        default => ['label' => 'Pending Approval', 'class' => 'review'],
    };
}

function formatEmployerJobRow(array $job): array
{
    $status = employerJobStatusDisplay($job['status'] ?? 'pending');
    $jobId = (int) ($job['id'] ?? 0);
    $applicantCount = isset($job['applicant_count'])
        ? (int) $job['applicant_count']
        : ($jobId > 0 && function_exists('countJobApplicants') ? countJobApplicants($jobId) : 0);

    return [
        'id' => $jobId,
        'title' => $job['title'],
        'location' => trim($job['location'] ?? '') ?: '—',
        'applicants' => $applicantCount,
        'posted' => formatJobSubmittedAt($job['created_at'] ?? null),
        'status' => $job['status'] ?? 'pending',
        'status_label' => $status['label'],
        'status_class' => $status['class'],
    ];
}

function fetchEmployerJobs(int $employerId): array
{
    ensureJobsSchema();

    $stmt = db()->prepare(
        'SELECT j.id, j.title, j.location, j.status, j.created_at,
                (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id) AS applicant_count
         FROM jobs j
         WHERE j.employer_id = :employer_id
         ORDER BY datetime(j.created_at) DESC, j.id DESC'
    );
    $stmt->execute(['employer_id' => $employerId]);

    return array_map('formatEmployerJobRow', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function fetchEmployerJobById(int $employerId, int $jobId): ?array
{
    ensureJobsSchema();

    $stmt = db()->prepare(
        'SELECT id, employer_id, company_name, title, location, job_type, salary, skills, description, status, created_at
         FROM jobs
         WHERE id = :id AND employer_id = :employer_id
         LIMIT 1'
    );
    $stmt->execute(['id' => $jobId, 'employer_id' => $employerId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    return $job ?: null;
}

function updateJobByEmployer(int $employerId, int $jobId, array $data): array
{
    ensureJobsSchema();

    $job = fetchEmployerJobById($employerId, $jobId);
    if (!$job) {
        return ['success' => false, 'error' => 'Job not found.'];
    }

    if (($job['status'] ?? '') === 'rejected') {
        return ['success' => false, 'error' => 'Rejected jobs cannot be edited. Post a new listing instead.'];
    }

    $title = trim($data['title'] ?? '');
    $location = trim($data['location'] ?? '');
    $jobType = $data['job_type'] ?? 'full-time';
    $skills = trim($data['skills'] ?? '');
    $description = trim($data['description'] ?? '');

    if ($title === '' || $description === '') {
        return ['success' => false, 'error' => 'Job title and description are required.'];
    }

    $allowedTypes = ['full-time', 'part-time', 'contract', 'remote'];
    if (!in_array($jobType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'Invalid job type selected.'];
    }

    try {
        $stmt = db()->prepare(
            'UPDATE jobs SET title = :title, location = :location, job_type = :job_type,
             skills = :skills, description = :description
             WHERE id = :id AND employer_id = :employer_id'
        );
        $stmt->execute([
            'title' => $title,
            'location' => $location !== '' ? $location : null,
            'job_type' => $jobType,
            'skills' => $skills !== '' ? $skills : null,
            'description' => $description,
            'id' => $jobId,
            'employer_id' => $employerId,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not update job. Please try again.'];
    }

    return ['success' => true, 'message' => 'Job updated successfully.'];
}

function fetchJobById(int $id): ?array
{
    ensureJobsSchema();

    $stmt = db()->prepare(
        'SELECT j.id, j.employer_id, j.company_name, j.title, j.location, j.job_type, j.salary, j.skills,
                j.description, j.status, j.created_by, j.created_at, u.full_name AS employer_name
         FROM jobs j
         LEFT JOIN users u ON u.id = j.employer_id
         WHERE j.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    return $job ?: null;
}

function updateJobByAdmin(int $id, array $data): array
{
    ensureJobsSchema();

    $job = fetchJobById($id);
    if (!$job) {
        return ['success' => false, 'error' => 'Job not found.'];
    }

    $title = trim($data['title'] ?? '');
    $companyName = trim($data['company_name'] ?? '');
    $location = trim($data['location'] ?? '');
    $jobType = $data['job_type'] ?? 'full-time';
    $salary = trim($data['salary'] ?? '');
    $skills = trim($data['skills'] ?? '');
    $description = trim($data['description'] ?? '');
    $status = $data['status'] ?? 'pending';
    $employerId = (int) ($data['employer_id'] ?? 0);

    if ($title === '' || $companyName === '' || $description === '') {
        return ['success' => false, 'error' => 'Job title, company name, and description are required.'];
    }

    $allowedTypes = ['full-time', 'part-time', 'contract', 'remote'];
    if (!in_array($jobType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'Invalid job type selected.'];
    }

    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        return ['success' => false, 'error' => 'Invalid status selected.'];
    }

    if ($employerId > 0) {
        $employer = db()->prepare(
            "SELECT id, company_name, full_name FROM users WHERE id = :id AND role = 'employer' AND status = 'verified' LIMIT 1"
        );
        $employer->execute(['id' => $employerId]);
        $employerRow = $employer->fetch(PDO::FETCH_ASSOC);
        if (!$employerRow) {
            return ['success' => false, 'error' => 'Selected employer was not found or is not verified.'];
        }
        if ($companyName === '') {
            $companyName = trim($employerRow['company_name'] ?? '');
        }
    }

    try {
        $stmt = db()->prepare(
            'UPDATE jobs SET employer_id = :employer_id, company_name = :company_name, title = :title,
             location = :location, job_type = :job_type, salary = :salary, skills = :skills,
             description = :description, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'employer_id' => $employerId > 0 ? $employerId : null,
            'company_name' => $companyName,
            'title' => $title,
            'location' => $location !== '' ? $location : null,
            'job_type' => $jobType,
            'salary' => $salary !== '' ? $salary : null,
            'skills' => $skills !== '' ? $skills : null,
            'description' => $description,
            'status' => $status,
            'id' => $id,
        ]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not update job. Please try again.'];
    }

    return ['success' => true, 'message' => 'Job updated successfully.'];
}

function updateJobStatusByAdmin(int $id, string $status): array
{
    ensureJobsSchema();

    if (!in_array($status, ['approved', 'rejected'], true)) {
        return ['success' => false, 'error' => 'Invalid action.'];
    }

    $job = fetchJobById($id);
    if (!$job) {
        return ['success' => false, 'error' => 'Job not found.'];
    }

    if (($job['status'] ?? '') !== 'pending') {
        return ['success' => false, 'error' => 'Only pending jobs can be approved or rejected.'];
    }

    try {
        $stmt = db()->prepare('UPDATE jobs SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    } catch (PDOException) {
        return ['success' => false, 'error' => 'Could not update job status. Please try again.'];
    }

    $employerId = (int) ($job['employer_id'] ?? 0);
    if ($employerId > 0) {
        require_once __DIR__ . '/notifications.php';
        $jobTitle = trim($job['title'] ?? '') ?: 'Your job listing';
        notifyEmployerJobReviewed($employerId, $id, $jobTitle, $status);
    }

    $message = $status === 'approved'
        ? 'Job approved successfully.'
        : 'Job rejected successfully.';

    return ['success' => true, 'message' => $message];
}
