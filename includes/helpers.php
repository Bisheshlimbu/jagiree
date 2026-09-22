<?php
/**
 * Resolve asset URLs relative to the current script path.
 * Works with php -S router.php, php -S -t public, and nested routes like /seeker/chat.php
 */
function appRootPath(): string
{
    return dirname(__DIR__);
}

function appPublicPath(string $suffix = ''): string
{
    $base = appRootPath() . '/public';

    return $suffix !== '' ? $base . '/' . ltrim($suffix, '/') : $base;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $dir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

    if ($dir === '' || $dir === '.') {
        return '/' . $path;
    }

    $depth = count(array_filter(explode('/', $dir)));
    return str_repeat('../', $depth) . $path;
}

/**
 * Flatten mathematical / fullwidth fancy letters to plain ASCII (LinkedIn posts often use these).
 */
function flattenFancyUnicodeLetters(string $text): string
{
    return preg_replace_callback('/./u', static function (array $m): string {
        $cp = mb_ord($m[0], 'UTF-8');
        if ($cp === false) {
            return $m[0];
        }

        // Mathematical Bold
        if ($cp >= 0x1D400 && $cp <= 0x1D419) {
            return chr($cp - 0x1D400 + 65);
        }
        if ($cp >= 0x1D41A && $cp <= 0x1D433) {
            return chr($cp - 0x1D41A + 97);
        }
        // Mathematical Sans-Serif Bold
        if ($cp >= 0x1D5D4 && $cp <= 0x1D5ED) {
            return chr($cp - 0x1D5D4 + 65);
        }
        if ($cp >= 0x1D5EE && $cp <= 0x1D607) {
            return chr($cp - 0x1D5EE + 97);
        }
        // Fullwidth Latin
        if ($cp >= 0xFF21 && $cp <= 0xFF3A) {
            return chr($cp - 0xFF21 + 65);
        }
        if ($cp >= 0xFF41 && $cp <= 0xFF5A) {
            return chr($cp - 0xFF41 + 97);
        }

        return $m[0];
    }, $text) ?? $text;
}

/**
 * Clean scraped / pasted job description text for readable display.
 */
function cleanJobDescriptionText(string $raw): string
{
    $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = flattenFancyUnicodeLetters($text);

    $text = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $text) ?? $text;
    $text = preg_replace('/<\/\s*p\s*>/iu', "\n\n", $text) ?? $text;
    $text = preg_replace('/<\/\s*h[1-6]\s*>/iu', "\n\n", $text) ?? $text;
    $text = preg_replace('/<\s*li[^>]*>/iu', "\n- ", $text) ?? $text;
    $text = preg_replace('/<\/\s*li\s*>/iu', '', $text) ?? $text;
    $text = preg_replace('/<\/\s*(ul|ol)\s*>/iu', "\n", $text) ?? $text;
    $text = strip_tags($text);

    $text = str_replace(["\xC2\xA0", "\xE2\x80\xAF", "\t"], ' ', $text);
    $text = preg_replace('/[ ]{2,}/u', ' ', $text) ?? $text;
    $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;

    // Drop embedded apply-form junk common on scraped company career pages / LinkedIn.
    $text = preg_replace(
        '/\n\s*(?:Apply\s+Now|Apply\s+For\s+This\s+Position|Please fill out the form below)\b.*$/isu',
        '',
        $text
    ) ?? $text;
    $text = preg_replace(
        '/\n\s*(?:What[’’\']s your Name\?|What[’’\']s your Email|Upload Your CV|Upload CV\/Resume)\b.*$/isu',
        '',
        $text
    ) ?? $text;
    $text = preg_replace('/\n\s*Δ\s*$/u', '', $text) ?? $text;

    $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

    return trim($text);
}

/**
 * Whether a cleaned line should render as a section heading.
 */
function isJobDescriptionHeading(string $line): bool
{
    $trimmed = rtrim(trim($line), " \t:");
    if ($trimmed === '' || mb_strlen($trimmed) > 70) {
        return false;
    }
    if (preg_match('/[.!?]$/u', $trimmed) === 1) {
        return false;
    }
    if (preg_match('/^[-•●▪*]/u', $trimmed) === 1 || preg_match('/^\d+[.)]\s/u', $trimmed) === 1) {
        return false;
    }

    $known = '/^(requirements?|qualifications?|responsibilities|benefits?|skills?|experience|education|'
        . 'job summary|key responsibilities|preferred qualifications|minimum qualifications|'
        . 'about(?:\s+the)?\s+(?:role|job|us|company)|about you|about us|the role|overview|summary|'
        . 'what you.?ll do|what we offer|who you are|nice to have|must have|'
        . 'how to apply|what success looks like|why this role(?:[,\s]+why now)?|'
        . 'equal opportunity|location|compensation|perks)\s*$/iu';

    if (preg_match($known, $trimmed) === 1) {
        return true;
    }

    // Question-style / section openers: What…, Why…, How To…
    if (preg_match('/^(what|why|how|who)\b/iu', $trimmed) === 1 && str_word_count($trimmed) <= 10) {
        return true;
    }

    // Short Title Case lines (e.g. "Key Responsibilities")
    $words = preg_split('/[\s,\/&\-]+/u', $trimmed) ?: [];
    if (count($words) < 2 || count($words) > 8) {
        return false;
    }

    $small = ['a', 'an', 'the', 'and', 'or', 'of', 'for', 'to', 'in', 'on', 'with', 'at', 'by'];
    $contentWords = 0;
    $titled = 0;
    foreach ($words as $word) {
        $clean = preg_replace('/[^A-Za-z0-9]/u', '', $word) ?? '';
        if ($clean === '') {
            continue;
        }
        $lower = mb_strtolower($clean);
        if (in_array($lower, $small, true)) {
            continue;
        }
        $contentWords++;
        if (preg_match('/^[A-Z0-9]/u', $clean) === 1) {
            $titled++;
        }
    }

    return $contentWords >= 2 && $titled === $contentWords;
}

/**
 * Escape inline text and turn emails into mailto links.
 */
function formatJobDescriptionInlineHtml(string $text): string
{
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    return preg_replace_callback(
        '/\b([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})\b/i',
        static function (array $match): string {
            $email = $match[1];

            return '<a href="mailto:' . $email . '" class="job-detail-email">' . $email . '</a>';
        },
        $escaped
    ) ?? $escaped;
}

/**
 * Escape a body line and bold short "Label: value" prefixes.
 */
function formatJobDescriptionLineHtml(string $line): string
{
    if (isJobDescriptionLabelLine($line)) {
        preg_match('/^([^:]{2,45}):\s+(.+)$/u', $line, $match);
        $label = trim($match[1]);
        $value = trim($match[2]);

        return '<strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ':</strong> '
            . formatJobDescriptionInlineHtml($value);
    }

    return formatJobDescriptionInlineHtml($line);
}

/**
 * Short "Label: value" lines (Apply to:, Subject:, Within 6 months:, …).
 */
function isJobDescriptionLabelLine(string $line): bool
{
    if (preg_match('/^([^:]{2,45}):\s+(.+)$/u', $line, $match) !== 1) {
        return false;
    }

    $label = trim($match[1]);
    $value = trim($match[2]);

    return $value !== ''
        && str_word_count($label) <= 6
        && preg_match('/[.!?]/u', $label) !== 1;
}

/**
 * Render a job description as safe structured HTML (paragraphs, headings, lists).
 */
function formatJobDescriptionHtml(string $raw): string
{
    $text = cleanJobDescriptionText($raw);
    if ($text === '') {
        return '<p class="job-detail-empty">No description provided.</p>';
    }

    $lines = preg_split("/\n/u", $text) ?: [];
    $html = '';
    $inList = false;

    $closeList = static function () use (&$html, &$inList): void {
        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $closeList();
            continue;
        }

        if (preg_match('/^[-•●▪*]\s+(.+)$/u', $line, $match) === 1
            || preg_match('/^\d+[.)]\s+(.+)$/u', $line, $match) === 1
        ) {
            if (!$inList) {
                $html .= '<ul>';
                $inList = true;
            }
            $html .= '<li>' . formatJobDescriptionLineHtml($match[1]) . '</li>';
            continue;
        }

        // Label lines before headings so "Subject: …" stays bold text, not a heading.
        if (isJobDescriptionLabelLine($line)) {
            $closeList();
            $html .= '<p>' . formatJobDescriptionLineHtml($line) . '</p>';
            continue;
        }

        if (isJobDescriptionHeading($line)) {
            $closeList();
            $label = rtrim($line, " \t:");
            $html .= '<h4>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h4>';
            continue;
        }

        $closeList();
        $html .= '<p>' . formatJobDescriptionLineHtml($line) . '</p>';
    }

    $closeList();

    return $html;
}
