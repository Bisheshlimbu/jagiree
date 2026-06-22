<?php
/**
 * Admin analytics page data.
 */

require_once __DIR__ . '/dashboard.php';

function fetchJobPostingChartSeries(string $period = 'monthly'): array
{
    ensureJobsSchema();

    $labels = [];
    $data = [];
    $counts = [];

    $rows = db()->query(
        "SELECT strftime('%Y-%m', created_at) AS bucket, COUNT(*) AS total
         FROM jobs
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
                "SELECT COUNT(*) FROM jobs
                 WHERE date(created_at) >= date(:start)
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

function fetchUsersByRoleBreakdown(): array
{
    ensureUsersSchema();

    $seekers = countUsersByRole(ROLE_SEEKER);
    $employers = countUsersByRole(ROLE_EMPLOYER);

    return [
        'labels' => ['Seekers', 'Employers'],
        'data' => [$seekers, $employers],
    ];
}

function fetchUsersByStatusBreakdown(): array
{
    ensureUsersSchema();

    $verified = (int) db()->query(
        "SELECT COUNT(*) FROM users WHERE role != 'admin' AND status = 'verified'"
    )->fetchColumn();
    $pending = countPendingUsers(true);

    return [
        'labels' => ['Verified', 'Pending'],
        'data' => [$verified, $pending],
    ];
}

function fetchJobsByStatusBreakdown(): array
{
    ensureJobsSchema();

    return [
        'labels' => ['Approved', 'Pending', 'Rejected'],
        'data' => [
            countJobsByStatus('approved'),
            countJobsByStatus('pending'),
            countJobsByStatus('rejected'),
        ],
    ];
}

function fetchAnalyticsSummaryRows(): array
{
    ensureUsersSchema();
    ensureJobsSchema();

    $totalJobs = countJobsByStatus('approved') + countJobsByStatus('pending') + countJobsByStatus('rejected');
    $verifiedEmployers = (int) db()->query(
        "SELECT COUNT(*) FROM users WHERE role = 'employer' AND status = 'verified'"
    )->fetchColumn();

    return [
        [
            'label' => 'Verified employers',
            'value' => $verifiedEmployers,
            'href' => '/admin/users.php',
        ],
        [
            'label' => 'Pending user reviews',
            'value' => countPendingUsers(true),
            'href' => '/admin/users.php',
        ],
        [
            'label' => 'Pending job reviews',
            'value' => countJobsByStatus('pending'),
            'href' => '/admin/jobs.php',
        ],
        [
            'label' => 'Total job listings',
            'value' => $totalJobs,
            'href' => '/admin/jobs.php',
        ],
    ];
}

function getAdminAnalyticsData(): array
{
    $seekerCount = countUsersByRole(ROLE_SEEKER);
    $employerCount = countUsersByRole(ROLE_EMPLOYER);
    $totalUsers = countAllUsers(false);
    $totalJobs = countJobsByStatus('approved') + countJobsByStatus('pending') + countJobsByStatus('rejected');

    return [
        'stats' => [
            [
                'label' => 'Total Users',
                'value' => number_format($totalUsers),
                'trend' => 'Seekers and employers',
                'trendType' => 'neutral',
                'icon' => 'users',
            ],
            [
                'label' => 'Job Seekers',
                'value' => number_format($seekerCount),
                'trend' => 'Registered seekers',
                'trendType' => 'up',
                'icon' => 'users',
            ],
            [
                'label' => 'Employers',
                'value' => number_format($employerCount),
                'trend' => 'Registered employers',
                'trendType' => 'up',
                'icon' => 'growth',
            ],
            [
                'label' => 'Job Listings',
                'value' => number_format($totalJobs),
                'trend' => countJobsByStatus('approved') . ' approved',
                'trendType' => countJobsByStatus('approved') > 0 ? 'up' : 'neutral',
                'icon' => 'jobs',
            ],
        ],
        'charts' => [
            'registrations' => [
                'monthly' => fetchRegistrationChartSeries('monthly'),
                'weekly' => fetchRegistrationChartSeries('weekly'),
            ],
            'jobs' => [
                'monthly' => fetchJobPostingChartSeries('monthly'),
                'weekly' => fetchJobPostingChartSeries('weekly'),
            ],
        ],
        'breakdowns' => [
            'usersByRole' => fetchUsersByRoleBreakdown(),
            'usersByStatus' => fetchUsersByStatusBreakdown(),
            'jobsByStatus' => fetchJobsByStatusBreakdown(),
        ],
        'summary' => fetchAnalyticsSummaryRows(),
    ];
}
