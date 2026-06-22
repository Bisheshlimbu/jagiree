<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/jobs.php';
require_once __DIR__ . '/../../includes/flash.php';

requireRole(ROLE_ADMIN);

$jobId = (int) ($_GET['id'] ?? 0);
$job = fetchJobById($jobId);

if (!$job) {
    flashSet('error', 'Job not found.');
    header('Location: /admin/jobs.php');
    exit;
}

$error = null;
$employers = fetchEmployersForJobForm();
$form = [
    'title' => $job['title'],
    'company_name' => $job['company_name'],
    'employer_id' => $job['employer_id'] ?? '',
    'location' => $job['location'] ?? '',
    'job_type' => $job['job_type'] ?? 'full-time',
    'salary' => $job['salary'] ?? '',
    'skills' => $job['skills'] ?? '',
    'description' => $job['description'],
    'status' => $job['status'] ?? 'pending',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'title' => trim($_POST['title'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'employer_id' => trim($_POST['employer_id'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'job_type' => $_POST['job_type'] ?? 'full-time',
        'salary' => trim($_POST['salary'] ?? ''),
        'skills' => trim($_POST['skills'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'status' => $_POST['status'] ?? 'pending',
    ];

    $result = updateJobByAdmin($jobId, $form);

    if ($result['success']) {
        flashSet('success', $result['message']);
        header('Location: /admin/jobs.php');
        exit;
    }

    $error = $result['error'];
}

$pageTitle = 'Edit Job — Jagiree Admin';
$activePage = 'jobs';
$pageHeading = 'Edit Job';
require_once __DIR__ . '/../../includes/admin/layout-start.php';
?>

<section class="panel panel--form">
    <div class="panel-header panel-header--compact">
        <div>
            <h2>Edit Job</h2>
            <p>Update details for <?= htmlspecialchars($job['title']) ?></p>
        </div>
        <a href="/admin/jobs.php" class="panel-link">Back to Jobs</a>
    </div>

    <?php if ($error): ?>
        <div class="admin-flash admin-flash--error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="settings-form settings-form--wide" method="post" novalidate>
        <label class="form-field">
            <span>Job title *</span>
            <input type="text" name="title" value="<?= htmlspecialchars($form['title']) ?>" required autofocus>
        </label>

        <label class="form-field">
            <span>Company name *</span>
            <input type="text" name="company_name" value="<?= htmlspecialchars($form['company_name']) ?>" required>
        </label>

        <label class="form-field">
            <span>Link to employer (optional)</span>
            <select name="employer_id">
                <option value="">No linked employer</option>
                <?php foreach ($employers as $employer): ?>
                <option value="<?= (int) $employer['id'] ?>" <?= (string) $form['employer_id'] === (string) $employer['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(formatEmployerOptionLabel($employer)) ?> (<?= htmlspecialchars($employer['email']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="form-field">
            <span>Created by</span>
            <input type="text" value="<?= htmlspecialchars(jobCreatedByLabel($job['created_by'] ?? 'admin')) ?>" disabled>
        </label>

        <label class="form-field">
            <span>Location</span>
            <input type="text" name="location" value="<?= htmlspecialchars($form['location']) ?>" placeholder="e.g. Kathmandu, Nepal (Remote)">
        </label>

        <label class="form-field">
            <span>Job type *</span>
            <select name="job_type" required>
                <option value="full-time" <?= $form['job_type'] === 'full-time' ? 'selected' : '' ?>>Full-time</option>
                <option value="part-time" <?= $form['job_type'] === 'part-time' ? 'selected' : '' ?>>Part-time</option>
                <option value="contract" <?= $form['job_type'] === 'contract' ? 'selected' : '' ?>>Contract</option>
                <option value="remote" <?= $form['job_type'] === 'remote' ? 'selected' : '' ?>>Remote</option>
            </select>
        </label>

        <label class="form-field">
            <span>Salary range</span>
            <input type="text" name="salary" value="<?= htmlspecialchars($form['salary']) ?>" placeholder="e.g. Rs. 60K – 90K">
        </label>

        <label class="form-field">
            <span>Required skills</span>
            <input type="text" name="skills" value="<?= htmlspecialchars($form['skills']) ?>" placeholder="PHP, Python, MySQL">
            <small class="form-field__hint">Comma-separated list.</small>
        </label>

        <label class="form-field">
            <span>Job description *</span>
            <textarea name="description" rows="6" required><?= htmlspecialchars($form['description']) ?></textarea>
        </label>

        <label class="form-field">
            <span>Status *</span>
            <select name="status">
                <option value="approved" <?= $form['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="pending" <?= $form['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="rejected" <?= $form['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn-sm btn-sm--primary">Save Changes</button>
            <a href="/admin/jobs.php" class="btn-sm btn-sm--ghost">Cancel</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
