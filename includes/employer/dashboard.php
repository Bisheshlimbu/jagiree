<?php
/**
 * Employer dashboard metrics and content.
 */

require_once __DIR__ . '/../jobs.php';
require_once __DIR__ . '/../applications.php';

function countEmployerJobsByStatus(int $employerId, string $status): int
{
    ensureJobsSchema();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM jobs WHERE employer_id = :employer_id AND status = :status'
    );
    $stmt->execute(['employer_id' => $employerId, 'status' => $status]);

    return (int) $stmt->fetchColumn();
}

function countEmployerJobsSince(int $employerId, int $days, ?string $status = null): int
{
    ensureJobsSchema();

    $sql = "SELECT COUNT(*) FROM jobs
            WHERE employer_id = :employer_id
            AND date(created_at) >= date('now', :interval)";
    $params = [
        'employer_id' => $employerId,
        'interval' => '-' . $days . ' days',
    ];

    if ($status !== null) {
        $sql .= ' AND status = :status';
        $params['status'] = $status;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function fetchEmployerRecentApprovedJobs(int $employerId, int $limit = 5): array
{
    ensureJobsSchema();

    $stmt = db()->prepare(
        'SELECT id, title, location, job_type, created_at
         FROM jobs
         WHERE employer_id = :employer_id AND status = :status
         ORDER BY datetime(created_at) DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':employer_id', $employerId, PDO::PARAM_INT);
    $stmt->bindValue(':status', 'approved');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return array_map('formatEmployerDashboardJobRow', $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function formatEmployerDashboardJobRow(array $job): array
{
    $location = trim($job['location'] ?? '') ?: 'Location not set';
    $posted = formatJobSubmittedAt($job['created_at'] ?? null);
    $title = $job['title'] ?? '';

    $icon = 'code';
    $jobType = $job['job_type'] ?? '';
    if ($jobType === 'remote' || preg_match('/design|ui|ux|product/i', $title)) {
        $icon = 'design';
    }

    return [
        'id' => (int) $job['id'],
        'title' => $title,
        'meta' => 'Posted ' . $posted . ' · ' . $location,
        'applicants' => countJobApplicants((int) $job['id']),
        'new' => countNewJobApplicantsSince((int) $job['id'], 7),
        'icon' => $icon,
    ];
}

function fetchEmployerDashboardInsights(
    int $activeJobs,
    int $pendingJobs,
    int $totalJobs,
    ?string $latestJobTitle
): array
{
    if ($totalJobs === 0) {
        return [
            'desc' => 'Post your first job listing to start receiving matched applicants on Jagiree.',
            'checklist' => [
                'No job listings yet — use Post a New Job to get started.',
                'Applications and AI match scores will appear here once seekers apply.',
            ],
        ];
    }

    if ($pendingJobs > 0 && $activeJobs === 0) {
        return [
            'desc' => sprintf(
                'You have %d job listing%s awaiting admin approval. They will go live once reviewed.',
                $pendingJobs,
                $pendingJobs === 1 ? '' : 's'
            ),
            'checklist' => [
                sprintf('%d listing%s pending approval.', $pendingJobs, $pendingJobs === 1 ? ' is' : 's are'),
                'Share approved roles to start attracting applicants.',
            ],
        ];
    }

    if ($activeJobs > 0 && $latestJobTitle) {
        $desc = $activeJobs === 1
            ? sprintf(
                'Your "%s" role is live and ready for applicants. Promote it to reach more seekers.',
                $latestJobTitle
            )
            : sprintf(
                'Your "%s" role and %d other live listings are ready for applicants. Promote them to reach more seekers.',
                $latestJobTitle,
                $activeJobs - 1
            );

        return [
            'desc' => $desc,
            'checklist' => [
                sprintf('%d active job%s currently live.', $activeJobs, $activeJobs === 1 ? ' is' : 's are'),
                'Application tracking will appear here once seekers apply to your roles.',
            ],
        ];
    }

    return [
        'desc' => sprintf(
            'You have %d live job listing%s on the platform.',
            $activeJobs,
            $activeJobs === 1 ? '' : 's'
        ),
        'checklist' => [
            'Keep job descriptions updated to improve match quality.',
            'Applicants will show up in Recent Applications once they apply.',
        ],
    ];
}

function getEmployerDashboardData(int $employerId): array
{
    $activeJobs = countEmployerJobsByStatus($employerId, 'approved');
    $pendingJobs = countEmployerJobsByStatus($employerId, 'pending');
    $totalJobs = countEmployerJobsByStatus($employerId, 'pending')
        + $activeJobs
        + countEmployerJobsByStatus($employerId, 'rejected');
    $newActiveThisWeek = countEmployerJobsSince($employerId, 7, 'approved');
    $totalApplicants = countEmployerApplications($employerId);
    $newApplicantsThisWeek = countEmployerApplications($employerId, 7);
    $interviewCount = countEmployerApplicationsByStatus($employerId, 'interview');
    $shortlistedCount = countEmployerApplicationsByStatus($employerId, 'review')
        + countEmployerApplicationsByStatus($employerId, 'hired');

    $activeTrend = $newActiveThisWeek > 0
        ? '+' . $newActiveThisWeek . ' this week'
        : ($activeJobs > 0 ? 'Live listings' : 'No live jobs yet');

    $applicantTrend = $newApplicantsThisWeek > 0
        ? '+' . $newApplicantsThisWeek . ' this week'
        : ($totalApplicants > 0 ? 'Total applicants' : 'No applications yet');

    $stats = [
        [
            'label' => 'Active Jobs',
            'value' => (string) $activeJobs,
            'trend' => $activeTrend,
            'trendType' => $newActiveThisWeek > 0 ? 'up' : 'neutral',
            'icon' => 'jobs',
        ],
        [
            'label' => 'New Applicants',
            'value' => (string) $totalApplicants,
            'trend' => $applicantTrend,
            'trendType' => $newApplicantsThisWeek > 0 ? 'up' : 'neutral',
            'icon' => 'applicants',
        ],
        [
            'label' => 'Interviews',
            'value' => (string) $interviewCount,
            'trend' => $interviewCount > 0 ? 'Scheduled' : 'None scheduled',
            'trendType' => $interviewCount > 0 ? 'up' : 'neutral',
            'icon' => 'interviews',
        ],
        [
            'label' => 'Shortlisted',
            'value' => (string) $shortlistedCount,
            'trend' => $shortlistedCount > 0 ? 'In review pipeline' : 'Awaiting applicants',
            'trendType' => $shortlistedCount > 0 ? 'up' : 'neutral',
            'icon' => 'shortlisted',
        ],
    ];

    $activeJobRows = fetchEmployerRecentApprovedJobs($employerId, 5);
    $latestJobTitle = $activeJobRows[0]['title'] ?? null;

    return [
        'stats' => $stats,
        'applications' => fetchEmployerRecentApplications($employerId, 5),
        'activeJobs' => $activeJobRows,
        'interviews' => [],
        'insights' => fetchEmployerDashboardInsights($activeJobs, $pendingJobs, $totalJobs, $latestJobTitle),
        'pendingJobs' => $pendingJobs,
    ];
}
