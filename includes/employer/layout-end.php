        </main>
    </div>
</div>

<?php require __DIR__ . '/application-detail-modal.php'; ?>

<script src="/assets/js/employer.js"></script>
<script src="/assets/js/notifications.js"></script>
<?php foreach ($extraScripts as $script): ?>
<script src="/assets/js/<?= htmlspecialchars(ltrim($script, '/')) ?>"></script>
<?php endforeach; ?>
</body>
</html>
