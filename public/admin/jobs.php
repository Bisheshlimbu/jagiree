<?php
require_once __DIR__ . '/../../includes/jobs.php';
require_once __DIR__ . '/../../includes/flash.php';

$pageTitle = 'Jobs — Jagiree Admin';
$activePage = 'jobs';
$pageHeading = 'Jobs';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$jobRequests = fetchAdminJobs();
$jobCount = count($jobRequests);
?>

<?php renderAdminFlash(); ?>

<section class="panel panel--table">
    <div class="panel-header">
        <div>
            <h2>Job Post Requests <span class="panel-count">(<?= $jobCount ?>)</span></h2>
            <p class="panel-sub">Review and approve employer job listings</p>
        </div>
        <a href="/admin/job-add.php" class="btn-sm btn-sm--primary">Add Job</a>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Employer</th>
                    <th>Created By</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($jobRequests === []): ?>
                <tr>
                    <td colspan="7" class="table-empty">No job listings yet. Click Add Job to create one.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($jobRequests as $job): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($job['title']) ?></strong></td>
                    <td><?= htmlspecialchars($job['company']) ?></td>
                    <td><?= htmlspecialchars($job['employer_name']) ?></td>
                    <td>
                        <span class="created-by-badge created-by-badge--<?= htmlspecialchars($job['created_by']) ?>">
                            <?= htmlspecialchars($job['created_by_label']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($job['submitted']) ?></td>
                    <td>
                        <span class="status-badge status-badge--<?= $job['status'] === 'approved' ? 'verified' : ($job['status'] === 'rejected' ? 'rejected' : 'pending') ?>">
                            <?= htmlspecialchars($job['status_label']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <?php if ($job['status'] === 'pending'): ?>
                            <form method="post" action="/admin/job-action.php" class="table-action-form">
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn-sm btn-sm--success">Approve</button>
                            </form>
                            <form method="post" action="/admin/job-action.php" class="table-action-form" onsubmit="return confirm('Reject <?= htmlspecialchars($job['title'], ENT_QUOTES) ?>?');">
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-sm btn-sm--danger">Reject</button>
                            </form>
                            <?php else: ?>
                            <a href="/admin/job-edit.php?id=<?= (int) $job['id'] ?>" class="table-action-btn" aria-label="Edit job">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
