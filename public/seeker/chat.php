<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/seeker/profile.php';
require_once __DIR__ . '/../../includes/seeker/jobs.php';
require_once __DIR__ . '/../../includes/applications.php';
require_once __DIR__ . '/../../includes/site-brand.php';

requireRole(ROLE_SEEKER);

$authUser = currentUser();
$userId = (int) $authUser['id'];
$profile = fetchSeekerProfile($userId);
$seekerName = $profile['full_name'] ?? displayName($authUser);
$cvMeta = seekerCvMeta($profile ?? []);
$seekerHasCv = $cvMeta['has_cv'];

$pageTitle = 'AI Assistant — Jagiree';
$activePage = 'chat';
$bodyClass = 'seeker-body seeker-body--chat';
$extraScripts = ['assets/js/job-apply.js', 'assets/js/chat.js'];
$siteLogoUrl = siteLogoUrl();
$chatSiteName = siteName();
$chatLogoLetter = mb_strtoupper(mb_substr($chatSiteName, 0, 1));

$chatBotAvatarHtml = static function (string $class) use ($siteLogoUrl, $chatLogoLetter): string {
    if ($siteLogoUrl) {
        return '<span class="' . htmlspecialchars($class) . ' ' . htmlspecialchars($class) . '--logo" aria-hidden="true">'
            . '<img src="' . htmlspecialchars($siteLogoUrl) . '" alt="">'
            . '</span>';
    }

    return '<span class="' . htmlspecialchars($class) . '" aria-hidden="true">'
        . htmlspecialchars($chatLogoLetter)
        . '</span>';
};

require_once __DIR__ . '/../../includes/seeker/layout-start.php';
?>

<div class="chat-layout">
    <aside class="chat-sidebar">
        <div class="chat-intro-card">
            <?= $chatBotAvatarHtml('chat-intro-icon') ?>
            <div>
                <h2>Jagiree AI Assistant</h2>
                <p>Ask about jobs or upload CV for recommendations</p>
            </div>
            <a href="#chatWindow" class="btn-start-chat">Start Chat</a>
        </div>

        <div class="chat-sidebar-section">
            <h3>Quick questions</h3>
            <div class="quick-prompts">
                <button type="button" class="quick-prompt" data-prompt="How does Jagiree work?">How does Jagiree work?</button>
                <button type="button" class="quick-prompt" data-prompt="Recommend jobs for me">Recommend jobs for me</button>
                <button type="button" class="quick-prompt" data-prompt="How do I apply for a job?">How do I apply?</button>
                <button type="button" class="quick-prompt" data-prompt="What skills are in demand?">Trending skills</button>
            </div>
        </div>

        <div class="chat-sidebar-section">
            <h3>Profile CV</h3>
            <p class="chat-sidebar-desc">One CV for your profile, Easy Apply, and AI job matches.</p>
            <label class="cv-upload-btn">
                <input type="file" id="cvUpload" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" hidden>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <?= $cvMeta['has_cv'] ? 'Replace CV' : 'Upload CV / Resume' ?>
            </label>
            <p class="cv-upload-hint" id="cvFileName">
                <?php if ($cvMeta['has_cv']): ?>
                <?= htmlspecialchars($cvMeta['filename']) ?><?= $cvMeta['updated_label'] ? ' · Updated ' . htmlspecialchars($cvMeta['updated_label']) : '' ?>
                <?php else: ?>
                PDF or DOCX, max 5MB
                <?php endif; ?>
            </p>
            <?php if ($cvMeta['has_cv']): ?>
            <a href="<?= htmlspecialchars($cvMeta['path']) ?>" class="chat-cv-link" target="_blank" rel="noopener">View profile CV</a>
            <?php endif; ?>
        </div>
    </aside>

    <section class="chat-window" id="chatWindow">
        <header class="chat-header">
            <div class="chat-header-bot">
                <?= $chatBotAvatarHtml('chat-bot-avatar') ?>
                <div>
                    <strong>Jagiree AI</strong>
                    <span class="chat-status"><span class="status-online"></span> Online · Profile-aware matching</span>
                </div>
            </div>
            <button type="button" class="chat-clear-btn" id="clearChat" title="Clear conversation">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
        </header>

        <div class="chat-messages" id="chatMessages" role="log" aria-live="polite">
            <div class="message message--bot">
                <?= $chatBotAvatarHtml('message-avatar') ?>
                <div class="message-bubble">
                    <p>Hi <?= htmlspecialchars($seekerName) ?>! I'm your Jagiree AI Assistant.</p>
                    <p>I can explain the platform, Easy Apply vs LinkedIn jobs, and recommend roles with <strong>NLP</strong> (CV text + TF-IDF).</p>
                    <ul>
                        <li>Ask how Jagiree or applying works</li>
                        <li>Upload a CV for NLP skill extraction</li>
                        <li>Get NLP job recommendations</li>
                    </ul>
                    <?php if (!$cvMeta['has_cv']): ?>
                    <p>Upload your CV to unlock Easy Apply and NLP matching.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="chat-suggestions" id="chatSuggestions">
            <button type="button" class="suggestion-chip" data-prompt="Recommend jobs for me">Get recommendations</button>
            <button type="button" class="suggestion-chip" data-prompt="How does Jagiree work?">About Jagiree</button>
            <button type="button" class="suggestion-chip" data-action="upload-cv">Upload CV</button>
        </div>

        <form class="chat-input-area" id="chatForm">
            <label class="chat-attach-btn" title="Attach CV to profile">
                <input type="file" id="chatFileAttach" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" hidden>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </label>
            <input
                type="text"
                id="chatInput"
                class="chat-input"
                placeholder="Ask about jobs, upload CV, get recommendations..."
                autocomplete="off"
                maxlength="500"
            >
            <button type="submit" class="chat-send-btn" aria-label="Send message">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </form>
    </section>
</div>

<script>
window.chatSeekerConfig = <?= json_encode([
    'hasCv' => $cvMeta['has_cv'],
    'cvFilename' => $cvMeta['filename'],
    'cvUpdatedLabel' => $cvMeta['updated_label'],
    'skills' => $profile['skill_list'] ?? [],
    'profileCvUrl' => '/seeker/profile.php?tab=skills',
    'siteLogoUrl' => $siteLogoUrl,
    'siteName' => $chatSiteName,
    'siteLogoLetter' => $chatLogoLetter,
], JSON_UNESCAPED_SLASHES) ?>;
window.seekerHasCv = <?= json_encode($seekerHasCv) ?>;
window.seekerProfileCvUrl = <?= json_encode('/seeker/profile.php?tab=skills') ?>;
</script>

<?php require_once __DIR__ . '/../../includes/seeker/layout-end.php'; ?>
