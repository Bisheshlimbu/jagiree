<?php
$pageTitle = 'Jobs — Jagiree Admin';
$activePage = 'jobs';
$pageHeading = 'Jobs';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$jobRequests = [
    ['title' => 'Senior UX Designer', 'company' => 'Nexus Digital', 'submitted' => '2 hours ago', 'status' => 'pending'],
    ['title' => 'Full Stack Developer', 'company' => 'TechVentures', 'submitted' => '5 hours ago', 'status' => 'pending'],
    ['title' => 'Marketing Manager', 'company' => 'Growth Labs', 'submitted' => '1 day ago', 'status' => 'approved'],
    ['title' => 'Data Analyst', 'company' => 'Insight Co.', 'submitted' => '2 days ago', 'status' => 'approved'],
];
?>

<section class="panel panel--table">
    <div class="panel-header">
        <div>
            <h2>Job Post Requests</h2>
            <p class="panel-sub">Review and approve employer job listings</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobRequests as $job): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($job['title']) ?></strong></td>
                    <td><?= htmlspecialchars($job['company']) ?></td>
                    <td><?= htmlspecialchars($job['submitted']) ?></td>
                    <td>
                        <span class="status-badge status-badge--<?= $job['status'] === 'approved' ? 'verified' : 'pending' ?>">
                            <?= $job['status'] === 'approved' ? 'Approved' : 'Pending' ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <?php if ($job['status'] === 'pending'): ?>
                            <button type="button" class="btn-sm btn-sm--success">Approve</button>
                            <button type="button" class="btn-sm btn-sm--danger">Reject</button>
                            <?php else: ?>
                            <button type="button" class="table-action-btn" aria-label="Edit job">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
