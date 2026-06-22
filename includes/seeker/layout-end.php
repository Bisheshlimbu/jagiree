</div>

<?php
if (str_contains($bodyClass ?? '', 'seeker')) {
    require __DIR__ . '/apply-modal.php';
}
?>

<script src="<?= asset('assets/js/seeker.js') ?>"></script>
<script src="<?= asset('assets/js/notifications.js') ?>"></script>
<?php foreach ($extraScripts as $script): ?>
<script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
