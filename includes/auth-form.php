<?php
/**
 * Shared auth form partial helpers.
 */

function authFieldLabel(string $id, string $label, array $attrs): void
{
    $isRequired = isset($attrs['required']);
    ?>
    <label for="<?= htmlspecialchars($id) ?>">
        <?= htmlspecialchars($label) ?><?php if ($isRequired): ?><span class="auth-field__required" aria-hidden="true"> *</span><?php endif; ?>
    </label>
    <?php
}

function authField(string $id, string $label, string $type = 'text', array $attrs = []): void
{
    if ($type === 'password') {
        authPasswordField($id, $label, $attrs);
        return;
    }

    $value = htmlspecialchars($attrs['value'] ?? '', ENT_QUOTES, 'UTF-8');
    unset($attrs['value']);
    $extra = '';
    foreach ($attrs as $key => $val) {
        $extra .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . '"';
    }
    ?>
    <div class="auth-field">
        <?php authFieldLabel($id, $label, $attrs); ?>
        <input type="<?= htmlspecialchars($type) ?>" id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($id) ?>" value="<?= $value ?>"<?= $extra ?>>
    </div>
    <?php
}

function authPasswordField(string $id, string $label, array $attrs = []): void
{
    unset($attrs['value']);
    $extra = '';
    foreach ($attrs as $key => $val) {
        $extra .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . '"';
    }
    ?>
    <div class="auth-field auth-field--password">
        <?php authFieldLabel($id, $label, $attrs); ?>
        <div class="auth-password-wrap">
            <input type="password" id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($id) ?>"<?= $extra ?>>
            <button type="button" class="auth-password-toggle" aria-label="Show password" aria-pressed="false">
                <svg class="auth-password-icon auth-password-icon--show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="auth-password-icon auth-password-icon--hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
            </button>
        </div>
    </div>
    <?php
}

function authAlert(?string $error, ?string $success = null): void
{
    if ($error): ?>
        <div class="auth-alert auth-alert--error" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="auth-alert auth-alert--success" role="status"><?= htmlspecialchars($success) ?></div>
    <?php endif;
}
