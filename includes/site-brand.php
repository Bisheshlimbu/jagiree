<?php
/**
 * Reusable site logo / name branding from admin settings.
 */

require_once __DIR__ . '/settings.php';

function renderSiteBrand(string $variant = 'landing', ?string $href = null): void
{
    $presets = [
        'admin' => [
            'href' => '/admin/',
            'wrapper_class' => 'sidebar-brand',
            'logo_only_class' => 'sidebar-brand--logo-only',
            'logo_class' => 'sidebar-brand-logo',
            'icon_class' => 'sidebar-brand-icon',
            'icon_html' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        ],
        'landing' => [
            'href' => '/',
            'wrapper_class' => 'logo',
            'logo_only_class' => 'logo--logo-only',
            'logo_class' => 'logo-image',
            'icon_class' => 'logo-icon',
            'icon_html' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>',
        ],
        'seeker' => [
            'href' => '/seeker/',
            'wrapper_class' => 'header-logo',
            'logo_only_class' => 'header-logo--logo-only',
            'logo_class' => 'header-logo-image',
            'icon_class' => 'header-logo-icon',
            'icon_html' => null,
        ],
        'employer' => [
            'href' => '/employer/',
            'wrapper_class' => 'topnav-brand',
            'logo_only_class' => 'topnav-brand--logo-only',
            'logo_class' => 'topnav-brand-logo',
            'icon_class' => 'topnav-brand-icon',
            'icon_html' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        ],
        'auth' => [
            'href' => '/',
            'wrapper_class' => 'auth-split-brand',
            'logo_only_class' => 'auth-split-brand--logo-only',
            'logo_class' => 'auth-split-brand-logo',
            'icon_class' => 'auth-split-brand-icon',
            'icon_html' => null,
        ],
    ];

    if (!isset($presets[$variant])) {
        $variant = 'landing';
    }

    $options = $presets[$variant];
    if ($href !== null) {
        $options['href'] = $href;
    }

    $name = siteName();
    $logoUrl = siteLogoUrl();
    $hasLogo = (bool) $logoUrl;

    $classes = $options['wrapper_class'];
    if ($hasLogo && !empty($options['logo_only_class'])) {
        $classes .= ' ' . $options['logo_only_class'];
    }

    echo '<a href="' . htmlspecialchars($options['href']) . '" class="' . htmlspecialchars($classes) . '">';

    if ($hasLogo) {
        echo '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($name) . '" class="' . htmlspecialchars($options['logo_class']) . '">';
    } else {
        $iconHtml = $options['icon_html'];
        if ($iconHtml === null) {
            $iconHtml = htmlspecialchars(mb_strtoupper(mb_substr($name, 0, 1)));
        }

        if (!empty($options['icon_class'])) {
            echo '<span class="' . htmlspecialchars($options['icon_class']) . '">' . $iconHtml . '</span>';
        }

        echo htmlspecialchars($name);
    }

    echo '</a>';
}
