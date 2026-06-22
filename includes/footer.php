<footer class="site-footer">
    <?php require_once __DIR__ . '/site-brand.php'; ?>
    <?php $siteName = siteName(); ?>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <?php renderSiteBrand('landing'); ?>
                <p>The intelligent job platform helping professionals grow faster in the AI era.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Website">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </a>
                    <a href="#" aria-label="Social">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="#jobs">Job Board</a></li>
                    <li><a href="/seeker/chat.php">AI Assistant</a></li>
                    <li><a href="#employers">Pricing</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Press</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Case Studies</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Privacy</a></li>
                    <li><a href="#">Terms</a></li>
                    <li><a href="#">Security</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<button class="chatbot-fab" aria-label="Open AI assistant" title="Chat with <?= htmlspecialchars($siteName) ?> Assistant" onclick="window.location.href='/seeker/chat.php'">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
</button>

<script src="/assets/js/landing.js"></script>
<?php
$extraScripts = $extraScripts ?? [];
foreach ($extraScripts as $script): ?>
<script src="<?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
