<?php
require_once __DIR__ . '/../../includes/jobs.php';
require_once __DIR__ . '/../../includes/flash.php';

$pageTitle = 'Jobs — Jagiree Admin';
$activePage = 'jobs';
$pageHeading = 'Jobs';
require_once __DIR__ . '/../../includes/admin/layout-start.php';

$perPage = 20;
$totalJobs = countAdminJobs();
$totalPages = max(1, (int) ceil($totalJobs / $perPage));
$page = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;
$jobRequests = fetchAdminJobs($perPage, $offset);
$from = $totalJobs === 0 ? 0 : $offset + 1;
$to = min($totalJobs, $offset + count($jobRequests));

$jobsPageUrl = static function (int $targetPage): string {
    return '/admin/jobs.php?page=' . $targetPage;
};
?>

<?php renderAdminFlash(); ?>

<section class="panel panel--table">
    <div class="panel-header">
        <div>
            <h2>Job Post Requests <span class="panel-count">(<?= $totalJobs ?>)</span></h2>
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
                        <span class="created-by-badge created-by-badge--<?= htmlspecialchars($job['created_by_class'] ?? 'admin') ?>">
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
                            <?php if ($job['status'] === 'pending' && empty($job['is_external'])): ?>
                            <form method="post" action="/admin/job-action.php" class="table-action-form">
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="page" value="<?= (int) $page ?>">
                                <button type="submit" class="btn-sm btn-sm--success">Approve</button>
                            </form>
                            <form method="post" action="/admin/job-action.php" class="table-action-form" onsubmit="return confirm('Reject <?= htmlspecialchars($job['title'], ENT_QUOTES) ?>?');">
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="page" value="<?= (int) $page ?>">
                                <button type="submit" class="btn-sm btn-sm--danger">Reject</button>
                            </form>
                            <?php elseif (!empty($job['can_edit'])): ?>
                            <a href="/admin/job-edit.php?id=<?= (int) $job['id'] ?>" class="table-action-btn" aria-label="Edit job">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <?php endif; ?>
                            <form method="post" action="/admin/job-action.php" class="table-action-form" onsubmit="return confirm('Delete <?= htmlspecialchars($job['title'], ENT_QUOTES) ?>? This cannot be undone.');">
                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="page" value="<?= (int) $page ?>">
                                <button type="submit" class="table-action-btn table-action-btn--danger" aria-label="Delete job">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalJobs > 0): ?>
    <div class="admin-pagination">
        <p class="admin-pagination__meta">Showing <?= (int) $from ?>–<?= (int) $to ?> of <?= (int) $totalJobs ?></p>
        <nav class="admin-pagination__nav" aria-label="Jobs pagination">
            <?php if ($page > 1): ?>
            <a class="admin-pagination__btn" href="<?= htmlspecialchars($jobsPageUrl($page - 1)) ?>">Previous</a>
            <?php else: ?>
            <span class="admin-pagination__btn is-disabled">Previous</span>
            <?php endif; ?>

            <?php
            $window = 2;
            $startPage = max(1, $page - $window);
            $endPage = min($totalPages, $page + $window);
            if ($startPage > 1):
            ?>
            <a class="admin-pagination__page" href="<?= htmlspecialchars($jobsPageUrl(1)) ?>">1</a>
            <?php if ($startPage > 2): ?><span class="admin-pagination__ellipsis">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
            <?php if ($p === $page): ?>
            <span class="admin-pagination__page is-current" aria-current="page"><?= $p ?></span>
            <?php else: ?>
            <a class="admin-pagination__page" href="<?= htmlspecialchars($jobsPageUrl($p)) ?>"><?= $p ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?><span class="admin-pagination__ellipsis">…</span><?php endif; ?>
            <a class="admin-pagination__page" href="<?= htmlspecialchars($jobsPageUrl($totalPages)) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
            <a class="admin-pagination__btn" href="<?= htmlspecialchars($jobsPageUrl($page + 1)) ?>">Next</a>
            <?php else: ?>
            <span class="admin-pagination__btn is-disabled">Next</span>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
