<?php
$pageTitle = 'Settings — Jagiree';
$activePage = 'profile';
require_once __DIR__ . '/../../includes/seeker/layout-start.php';
?>

<div class="page-layout page-layout--single">
    <div class="page-title-bar">
        <h1>Settings</h1>
        <p>Manage your account and job alert preferences</p>
    </div>

    <section class="form-section">
        <h2>Job Alerts</h2>
        <form>
            <label class="form-field">
                <span>Email notifications</span>
                <select>
                    <option selected>Daily job recommendations</option>
                    <option>Weekly summary</option>
                    <option>None</option>
                </select>
            </label>
            <label class="form-field">
                <span>Preferred job type</span>
                <select>
                    <option selected>Remote & Hybrid</option>
                    <option>On-site only</option>
                    <option>Remote only</option>
                </select>
            </label>
            <label class="form-field">
                <span>Preferred location</span>
                <input type="text" value="Kathmandu, Nepal">
            </label>
            <button type="button" class="btn-primary">Save Settings</button>
        </form>
    </section>
</div>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
