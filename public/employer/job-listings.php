<?php
require_once __DIR__ . '/../../includes/jobs.php';

$pageTitle = 'Job Listings — Jagiree Employer';
$activePage = 'jobs';
$activeTopNav = 'dashboard';
$extraScripts = ['employer-applications.js'];
require_once __DIR__ . '/../../includes/employer/layout-start.php';

$listings = fetchEmployerJobs((int) $authUser['id']);
$posted = isset($_GET['posted']);
$updated = isset($_GET['updated']);
?>

<div class="page-header">
    <div>
        <h1>Job Listings</h1>
        <p>Manage your active and pending job postings.</p>
    </div>
    <a href="/employer/post-job.php" class="btn-post-job">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Post a New Job
    </a>
</div>

<?php if ($posted): ?>
    <div class="employer-alert employer-alert--success" role="alert">Job submitted for admin approval.</div>
<?php elseif ($updated): ?>
    <div class="employer-alert employer-alert--success" role="alert">Job updated successfully.</div>
<?php endif; ?>

<section class="panel panel--table">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Location</th>
                    <th>Applicants</th>
                    <th>Posted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($listings === []): ?>
                <tr>
                    <td colspan="6" class="table-empty">No job listings yet. Click Post a New Job to create one.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($listings as $job): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($job['title']) ?></strong></td>
                    <td><?= htmlspecialchars($job['location']) ?></td>
                    <td>
                        <?php if ($job['applicants'] > 0): ?>
                        <a href="/employer/applicants.php?job_id=<?= (int) $job['id'] ?>"><?= (int) $job['applicants'] ?></a>
                        <?php else: ?>
                        <?= (int) $job['applicants'] ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($job['posted']) ?></td>
                    <td>
                        <span class="app-status app-status--<?= htmlspecialchars($job['status_class']) ?>">
                            <?= htmlspecialchars($job['status_label']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="job-list-actions">
                            <?php if ($job['status'] !== 'rejected'): ?>
                            <a href="/employer/job-edit.php?id=<?= $job['id'] ?>" class="icon-tool" aria-label="Edit job">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
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

<?php require_once __DIR__ . '/../../includes/employer/layout-end.php'; ?>
