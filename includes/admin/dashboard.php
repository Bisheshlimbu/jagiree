<?php
/**
 * Admin dashboard metrics and chart data.
 */

require_once __DIR__ . '/../users.php';
require_once __DIR__ . '/../jobs.php';

function countUsersThisMonth(bool $excludeAdmin = true): int
{
    ensureUsersSchema();

    $sql = "SELECT COUNT(*) FROM users
            WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')";
    if ($excludeAdmin) {
        $sql .= " AND role != 'admin'";
    }

    return (int) db()->query($sql)->fetchColumn();
}

function countPendingUsers(bool $excludeAdmin = true): int
{
    ensureUsersSchema();

    $sql = "SELECT COUNT(*) FROM users WHERE status = 'pending'";
    if ($excludeAdmin) {
        $sql .= " AND role != 'admin'";
    }

    return (int) db()->query($sql)->fetchColumn();
}

function countUsersByRole(string $role): int
{
    ensureUsersSchema();

    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
    $stmt->execute(['role' => $role]);

    return (int) $stmt->fetchColumn();
}

function countJobsByStatus(string $status): int
{
    ensureJobsSchema();

    $stmt = db()->prepare('SELECT COUNT(*) FROM jobs WHERE status = :status');
    $stmt->execute(['status' => $status]);

    return (int) $stmt->fetchColumn();
}

function fetchRegistrationChartSeries(string $period = 'monthly'): array
{
    ensureUsersSchema();

    $labels = [];
    $data = [];
    $counts = [];

    $rows = db()->query(
        "SELECT strftime('%Y-%m', created_at) AS bucket, COUNT(*) AS total
         FROM users
         WHERE role != 'admin'
         GROUP BY bucket"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $counts[$row['bucket']] = (int) $row['total'];
    }

    if ($period === 'weekly') {
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = strtotime("-{$i} weeks", strtotime('monday this week'));
            $weekEnd = strtotime('+6 days', $weekStart);
            $labels[] = date('M j', $weekStart);

            $stmt = db()->prepare(
                "SELECT COUNT(*) FROM users
                 WHERE role != 'admin'
                 AND date(created_at) >= date(:start)
                 AND date(created_at) <= date(:end)"
            );
            $stmt->execute([
                'start' => date('Y-m-d', $weekStart),
                'end' => date('Y-m-d', $weekEnd),
            ]);
            $data[] = (int) $stmt->fetchColumn();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    for ($i = 7; $i >= 0; $i--) {
        $monthKey = date('Y-m', strtotime("-{$i} months"));
        $labels[] = date('M', strtotime($monthKey . '-01'));
        $data[] = $counts[$monthKey] ?? 0;
    }

    return ['labels' => $labels, 'data' => $data];
}

function fetchAdminDashboardInsights(
    int $pendingUsers,
    int $pendingJobs,
    int $monthlyGrowth,
    int $activeJobs,
    int $employerCount,
    int $seekerCount
): array {
    $insights = [];

    if ($pendingJobs > 0) {
        $insights[] = [
            'type' => 'purple',
            'title' => 'Pending Job Reviews',
            'text' => sprintf(
                '%d job listing%s waiting for approval. Review them on the Jobs page to keep listings fresh.',
                $pendingJobs,
                $pendingJobs === 1 ? ' is' : 's are'
            ),
        ];
    }

    if ($pendingUsers > 0) {
        $insights[] = [
            'type' => 'teal',
            'title' => 'Account Verification',
            'text' => sprintf(
                '%d user account%s pending verification. Approve verified seekers and employers from the Users page.',
                $pendingUsers,
                $pendingUsers === 1 ? ' is' : 's are'
            ),
        ];
    }

    if ($monthlyGrowth > 0) {
        $insights[] = [
            'type' => 'purple',
            'title' => 'Growth Opportunity',
            'text' => sprintf(
                '%d new user%s joined this month. Promote job listings to convert recent registrations into active seekers.',
                $monthlyGrowth,
                $monthlyGrowth === 1 ? '' : 's'
            ),
        ];
    }

    if ($employerCount > 0 && $activeJobs === 0) {
        $insights[] = [
            'type' => 'teal',
            'title' => 'Job Listings Needed',
            'text' => sprintf(
                'You have %d verified employer%s but no approved jobs yet. Encourage employers to post openings or add jobs from the admin panel.',
                $employerCount,
                $employerCount === 1 ? '' : 's'
            ),
        ];
    }

    if ($seekerCount > 0 && $activeJobs > 0) {
        $insights[] = [
            'type' => 'teal',
            'title' => 'Platform Health',
            'text' => sprintf(
                '%d approved job%s and %d seeker%s are live on the platform. Keep approvals moving to maintain engagement.',
                $activeJobs,
                $activeJobs === 1 ? '' : 's',
                $seekerCount,
                $seekerCount === 1 ? '' : 's'
            ),
        ];
    }

    if ($insights === []) {
        $insights[] = [
            'type' => 'purple',
            'title' => 'Getting Started',
            'text' => 'Add users and job listings to start building activity on the platform.',
        ];
    }

    return array_slice($insights, 0, 2);
}

function getAdminDashboardData(): array
{
    $totalUsers = countAllUsers(false);
    $monthlyGrowth = countUsersThisMonth(false);
    $activeJobs = countJobsByStatus('approved');
    $pendingJobs = countJobsByStatus('pending');
    $pendingUsers = countPendingUsers(true);
    $pendingRequests = $pendingUsers + $pendingJobs;
    $employerCount = countUsersByRole(ROLE_EMPLOYER);
    $seekerCount = countUsersByRole(ROLE_SEEKER);

    $jobsTrend = $pendingJobs > 0
        ? $pendingJobs . ' pending approval'
        : ($activeJobs > 0 ? 'Live listings' : 'No approved jobs yet');

    $stats = [
        [
            'label' => 'Total Users',
            'value' => number_format($totalUsers),
            'trend' => 'Live data',
            'trendType' => 'up',
            'icon' => 'users',
        ],
        [
            'label' => 'Active Jobs',
            'value' => number_format($activeJobs),
            'trend' => $jobsTrend,
            'trendType' => $activeJobs > 0 ? 'up' : 'neutral',
            'icon' => 'jobs',
        ],
        [
            'label' => 'Pending Requests',
            'value' => number_format($pendingRequests),
            'trend' => $pendingRequests > 0 ? 'Awaiting review' : 'All caught up',
            'trendType' => $pendingRequests > 0 ? 'down' : 'neutral',
            'icon' => 'reports',
        ],
    ];

    return [
        'stats' => $stats,
        'registrations' => fetchRecentUsers(4),
        'chart' => [
            'monthly' => fetchRegistrationChartSeries('monthly'),
            'weekly' => fetchRegistrationChartSeries('weekly'),
        ],
        'insights' => fetchAdminDashboardInsights(
            $pendingUsers,
            $pendingJobs,
            $monthlyGrowth,
            $activeJobs,
            $employerCount,
            $seekerCount
        ),
    ];
}
