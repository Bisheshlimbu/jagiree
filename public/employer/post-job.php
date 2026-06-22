<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/jobs.php';

requireRole(ROLE_EMPLOYER);

$authUser = currentUser();
$error = null;
$form = [
    'title' => '',
    'location' => '',
    'job_type' => 'full-time',
    'skills' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'title' => trim($_POST['title'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'job_type' => $_POST['job_type'] ?? 'full-time',
        'skills' => trim($_POST['skills'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
    ];

    $result = createJobByEmployer((int) $authUser['id'], $form);

    if ($result['success']) {
        header('Location: /employer/job-listings.php?posted=1');
        exit;
    } else {
        $error = $result['error'];
    }
}

$pageTitle = 'Post a Job — Jagiree Employer';
$activePage = 'jobs';
$activeTopNav = 'dashboard';
require_once __DIR__ . '/../../includes/employer/layout-start.php';
?>

<div class="page-header">
    <div>
        <h1>Post a New Job</h1>
        <p>Submit a job listing for admin approval before it goes live.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="employer-alert employer-alert--error" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="panel form-panel">
    <form method="post" novalidate>
        <label class="form-field">
            <span>Job Title *</span>
            <input type="text" name="title" value="<?= htmlspecialchars($form['title']) ?>" placeholder="e.g. Senior Product Designer" required>
        </label>
        <label class="form-field">
            <span>Location</span>
            <input type="text" name="location" value="<?= htmlspecialchars($form['location']) ?>" placeholder="e.g. Kathmandu, Nepal (Remote)">
        </label>
        <label class="form-field">
            <span>Job Type *</span>
            <select name="job_type" required>
                <option value="full-time" <?= $form['job_type'] === 'full-time' ? 'selected' : '' ?>>Full-time</option>
                <option value="part-time" <?= $form['job_type'] === 'part-time' ? 'selected' : '' ?>>Part-time</option>
                <option value="contract" <?= $form['job_type'] === 'contract' ? 'selected' : '' ?>>Contract</option>
                <option value="remote" <?= $form['job_type'] === 'remote' ? 'selected' : '' ?>>Remote</option>
            </select>
        </label>
        <label class="form-field">
            <span>Required Skills (comma separated)</span>
            <input type="text" name="skills" value="<?= htmlspecialchars($form['skills']) ?>" placeholder="PHP, Python, MySQL, NLP">
        </label>
        <label class="form-field">
            <span>Job Description *</span>
            <textarea name="description" placeholder="Describe the role, responsibilities, and requirements..." required><?= htmlspecialchars($form['description']) ?></textarea>
        </label>
        <button type="submit" class="btn-primary">Submit for Approval</button>
    </form>
</section>

<?php require_once __DIR__ . '/../../includes/employer/layout-end.php'; ?>
