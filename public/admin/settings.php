<?php
$pageTitle = 'Settings — Jagiree Admin';
$activePage = 'settings';
$pageHeading = 'Settings';
require_once __DIR__ . '/../../includes/admin/layout-start.php';
?>

<div class="settings-grid">
    <section class="panel">
        <div class="panel-header panel-header--compact">
            <h2>General Settings</h2>
        </div>
        <form class="settings-form">
            <label class="form-field">
                <span>Platform Name</span>
                <input type="text" value="Jagiree">
            </label>
            <label class="form-field">
                <span>Admin Email</span>
                <input type="email" value="admin@jagiree.com">
            </label>
            <label class="form-field">
                <span>Job Approval</span>
                <select>
                    <option selected>Require admin approval</option>
                    <option>Auto-approve trusted employers</option>
                </select>
            </label>
            <button type="button" class="btn-sm btn-sm--primary">Save Changes</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header panel-header--compact">
            <h2>NLP Bot Settings</h2>
        </div>
        <form class="settings-form">
            <label class="form-field">
                <span>Python API URL</span>
                <input type="url" value="http://localhost:5000" placeholder="http://localhost:5000">
            </label>
            <label class="form-field">
                <span>Recommendation Threshold (%)</span>
                <input type="number" value="70" min="0" max="100">
            </label>
            <button type="button" class="btn-sm btn-sm--primary">Save Changes</button>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/../../includes/admin/layout-end.php'; ?>
