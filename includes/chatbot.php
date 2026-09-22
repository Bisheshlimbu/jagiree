<?php
/**
 * Seeker chatbot — FAQ-style intent replies + NLP job recommendations.
 */

require_once __DIR__ . '/seeker/jobs.php';
require_once __DIR__ . '/seeker/profile.php';
require_once __DIR__ . '/applications.php';

function chatbotNormalize(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    $text = trim($text);

    return chatbotCorrectCommonTypos($text);
}

/**
 * Fix frequent role/skill typos so asks like "frontnd" still find frontend jobs.
 */
function chatbotCorrectCommonTypos(string $text): string
{
    $map = [
        'frontnd' => 'frontend',
        'frontent' => 'frontend',
        'fronend' => 'frontend',
        'frontned' => 'frontend',
        'froentend' => 'frontend',
        'frontedn' => 'frontend',
        'backnd' => 'backend',
        'bakend' => 'backend',
        'backent' => 'backend',
        'backned' => 'backend',
        'fullstak' => 'full stack',
        'fulstack' => 'full stack',
        'reakt' => 'react',
        'javascrpt' => 'javascript',
        'javscript' => 'javascript',
        'typescrpt' => 'typescript',
        'pythn' => 'python',
        'laravl' => 'laravel',
        'larave' => 'laravel',
    ];

    foreach ($map as $typo => $fix) {
        $text = preg_replace(
            '/(?<![a-z0-9])' . preg_quote($typo, '/') . '(?![a-z0-9])/u',
            $fix,
            $text
        ) ?? $text;
    }

    return $text;
}

/**
 * Fuzzy-match a token to a single-word lexicon skill (Levenshtein).
 */
function chatbotFuzzyLexiconMatch(string $token): ?string
{
    $token = mb_strtolower(trim($token));
    if (mb_strlen($token) < 5) {
        return null;
    }

    $best = null;
    $bestDist = PHP_INT_MAX;

    foreach (chatbotSearchLexicon() as $phrase) {
        if (str_contains($phrase, ' ') || str_contains($phrase, '/')) {
            continue;
        }
        $phrase = mb_strtolower($phrase);
        if ($phrase === $token) {
            return $phrase;
        }
        if (abs(mb_strlen($phrase) - mb_strlen($token)) > 2) {
            continue;
        }
        $dist = levenshtein($token, $phrase);
        $limit = mb_strlen($token) >= 8 ? 2 : 1;
        if ($dist > 0 && $dist <= $limit && $dist < $bestDist) {
            $bestDist = $dist;
            $best = $phrase;
        }
    }

    return $best;
}

function chatbotMatches(string $text, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        if ($pattern !== '' && str_contains($text, $pattern)) {
            return true;
        }
    }

    return false;
}

function detectChatbotIntent(string $message): string
{
    $text = chatbotNormalize($message);

    if ($text === '') {
        return 'empty';
    }

    if (preg_match('/\b(hi|hello|hey|namaste|good morning|good evening|good afternoon)\b/u', $text) === 1) {
        return 'greeting';
    }

    if (chatbotMatches($text, ['thank', 'thanks', 'dhanyabad', 'appreciate'])) {
        return 'thanks';
    }

    if (chatbotMatches($text, [
        'recommend', 'suggestion', 'suggest', 'match me', 'find job', 'find me job', 'best job', 'jobs for me',
        'based on my cv', 'based on my resume', 'based on my profile', 'for my profile', 'using my cv', 'from my cv',
    ])) {
        return 'recommend';
    }

    if (!chatbotIsHowToQuestion($text) && chatbotMessageRequestsJobs($text)) {
        return 'recommend';
    }

    if (chatbotMatches($text, ['how does', 'how do', 'what is jagiree', 'about jagiree', 'how it work', 'platform', 'what can you'])) {
        return 'about';
    }

    if (chatbotMatches($text, ['linkedin', 'external job', 'apply on linkedin'])) {
        return 'linkedin';
    }

    if (chatbotMatches($text, ['apply', 'application', 'easy apply', 'how to apply'])) {
        return 'apply';
    }

    if (chatbotMatches($text, ['upload', 'cv', 'resume', 'curriculum'])) {
        return 'cv';
    }

    if (chatbotMatches($text, ['skill', 'trending', 'demand', 'popular'])) {
        return 'skills';
    }

    if (chatbotMatches($text, ['help', 'support', 'what can i ask'])) {
        return 'help';
    }

    return 'fallback';
}

function chatbotIsHowToQuestion(string $text): bool
{
    return chatbotMatches($text, [
        'how do',
        'how does',
        'how to',
        'how can',
        'what is jagiree',
        'about jagiree',
        'how it work',
        'what can you',
        'what can i ask',
    ]);
}

function chatbotSearchLexicon(): array
{
    return [
        'php', 'laravel', 'python', 'django', 'javascript', 'typescript', 'react', 'node', 'vue', 'angular',
        'html', 'css', 'tailwind', 'mysql', 'sql', 'figma', 'photoshop', 'illustrator', 'ui/ux', 'ui design',
        'ux design', 'graphic design', 'product design', 'design', 'designer', 'frontend', 'front end',
        'front-end', 'backend', 'back end', 'back-end', 'full stack', 'fullstack', 'devops', 'wordpress',
        'flutter', 'java', 'seo', 'digital marketing', 'software engineer', 'software developer',
        'web developer', 'mobile developer', 'pharmacy', 'farmacy', 'pharmacist', 'healthcare', 'nurse',
        'nursing', 'marketing', 'content writing', 'content writer', 'sales', 'accountant', 'accounting',
    ];
}

/**
 * Expand a user ask into related title/skill phrases used for filtering.
 *
 * @return list<string>
 */
function chatbotExpandSearchTerms(array $skills): array
{
    $families = [
        'frontend' => ['frontend', 'front end', 'front-end', 'react', 'vue', 'angular', 'javascript', 'typescript', 'html', 'css', 'tailwind', 'next.js', 'nextjs'],
        'front end' => ['frontend', 'front end', 'front-end', 'react', 'vue', 'angular', 'javascript', 'typescript', 'html', 'css', 'tailwind'],
        'front-end' => ['frontend', 'front end', 'front-end', 'react', 'vue', 'angular', 'javascript', 'typescript', 'html', 'css', 'tailwind'],
        'backend' => ['backend', 'back end', 'back-end', 'php', 'laravel', 'django', 'node', 'nodejs', 'java', 'api', 'mysql', 'sql'],
        'back end' => ['backend', 'back end', 'back-end', 'php', 'laravel', 'django', 'node', 'java', 'api'],
        'back-end' => ['backend', 'back end', 'back-end', 'php', 'laravel', 'django', 'node', 'java', 'api'],
        'full stack' => ['full stack', 'fullstack', 'full-stack', 'frontend', 'backend', 'react', 'php', 'laravel', 'node'],
        'fullstack' => ['full stack', 'fullstack', 'full-stack', 'frontend', 'backend', 'react', 'php', 'laravel', 'node'],
        'seo' => ['seo', 'search engine', 'digital marketing', 'content writing'],
        'digital marketing' => ['digital marketing', 'seo', 'marketing', 'content writing'],
        'pharmacy' => ['pharmacy', 'farmacy', 'pharmacist', 'pharmaceutical', 'healthcare'],
        'farmacy' => ['pharmacy', 'farmacy', 'pharmacist', 'pharmaceutical', 'healthcare'],
        'pharmacist' => ['pharmacy', 'farmacy', 'pharmacist', 'pharmaceutical'],
        'design' => ['design', 'designer', 'figma', 'ui', 'ux', 'ui/ux', 'graphic design', 'product design'],
        'designer' => ['designer', 'design', 'figma', 'ui', 'ux', 'ui/ux', 'graphic design', 'product design'],
        'software engineer' => ['software engineer', 'software developer', 'engineer', 'developer'],
        'software developer' => ['software engineer', 'software developer', 'engineer', 'developer'],
        'web developer' => ['web developer', 'frontend', 'backend', 'full stack', 'php', 'javascript', 'html', 'css'],
    ];

    $expanded = [];
    foreach ($skills as $skill) {
        $key = mb_strtolower(trim((string) $skill));
        if ($key === '') {
            continue;
        }
        $terms = $families[$key] ?? [$key];
        foreach ($terms as $term) {
            $expanded[$term] = $term;
        }
    }

    return array_values($expanded);
}

/**
 * Stronger match terms for role asks — prefer the role itself over generic skills like html/css.
 *
 * @param list<string> $skills
 * @return list<string>
 */
function chatbotStrongSearchTerms(array $skills): array
{
    $generic = [
        'javascript', 'typescript', 'html', 'css', 'sql', 'mysql', 'design', 'ui', 'ux', 'ui/ux',
        'node', 'nodejs', 'java', 'api', 'php', 'python', 'figma',
    ];
    $strong = [];

    foreach ($skills as $skill) {
        $key = mb_strtolower(trim((string) $skill));
        if ($key === '') {
            continue;
        }
        $strong[$key] = $key;
        foreach (chatbotExpandSearchTerms([$key]) as $i => $term) {
            if ($i < 5 || !in_array($term, $generic, true)) {
                $strong[$term] = $term;
            }
        }
    }

    return array_values($strong);
}

function chatbotContainsPhrase(string $text, string $phrase): bool
{
    $phrase = trim(mb_strtolower($phrase));
    if ($phrase === '') {
        return false;
    }

    $pattern = '/(?<![a-z0-9])' . preg_quote($phrase, '/') . '(?![a-z0-9])/u';

    return preg_match($pattern, mb_strtolower($text)) === 1;
}

/**
 * True when the seeker wants jobs based on their profile / CV.
 */
function chatbotWantsProfileRecommendations(string $message): bool
{
    $text = chatbotNormalize($message);

    // Word-boundary checks — avoid matching "me" inside "recommend".
    return chatbotContainsAnyPhrase($text, [
        'recommend jobs for me',
        'jobs for me',
        'match me',
        'for my profile',
        'based on my profile',
        'based on my cv',
        'based on my resume',
        'from my cv',
        'from my resume',
        'using my cv',
        'using my profile',
        'suggest jobs for me',
        'best jobs for me',
        'recommend for me',
    ]) || (
        chatbotContainsAnyPhrase($text, ['recommend', 'suggest', 'suggestion'])
        && chatbotContainsAnyPhrase($text, ['for me', 'my profile', 'my cv', 'my resume'])
        && !chatbotConstraintsActive(chatbotJobSearchConstraints($message))
    );
}

function chatbotContainsAnyPhrase(string $text, array $phrases): bool
{
    foreach ($phrases as $phrase) {
        if (chatbotContainsPhrase($text, $phrase)) {
            return true;
        }
    }

    return false;
}

/**
 * Pull a freeform role/topic from phrases like "jobs related to seo" or "looking for pharmacy".
 */
function chatbotExtractFreeformTopic(string $text): string
{
    $text = chatbotNormalize($text);
    $topic = '';

    $patterns = [
        '/\b(?:related to|looking for|searching for|about|regarding)\s+(?:the\s+)?(.+)$/u',
        '/\b(?:jobs?|roles?|openings?|vacancies)\s+(?:in|for|about|on)\s+(?:the\s+)?(.+)$/u',
        '/^(.+?)\s+(?:jobs?|roles?|openings?|vacancies)\b/u',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match) === 1) {
            $topic = trim($match[1] ?? '');
            break;
        }
    }

    if ($topic === '') {
        return '';
    }

    $stop = [
        'a', 'an', 'the', 'me', 'my', 'please', 'some', 'any', 'job', 'jobs', 'role', 'roles',
        'opening', 'openings', 'vacancy', 'vacancies', 'recommend', 'recommends', 'recommendation',
        'suggest', 'suggestion', 'find', 'show', 'get', 'want', 'need', 'looking', 'search',
        'related', 'for', 'to', 'in', 'on', 'about', 'and', 'or', 'with',
    ];

    $words = preg_split('/\s+/u', $topic) ?: [];
    $kept = [];
    foreach ($words as $word) {
        $word = trim($word);
        if ($word === '' || in_array($word, $stop, true) || mb_strlen($word) < 2) {
            continue;
        }
        if (preg_match('/^\d+$/u', $word) === 1) {
            continue;
        }
        $kept[] = $word;
        if (count($kept) >= 4) {
            break;
        }
    }

    return trim(implode(' ', $kept));
}

function chatbotMessageRequestsJobs(string $text): bool
{
    if (preg_match('/\b(jobs?|roles?|openings?|vacanc(?:y|ies)|hiring)\b/u', $text) === 1) {
        return true;
    }

    $matchedSkills = [];
    foreach (chatbotSearchLexicon() as $phrase) {
        if (chatbotContainsPhrase($text, $phrase)) {
            $matchedSkills[] = $phrase;
        }
    }

    if ($matchedSkills === []) {
        return false;
    }

    // Bare role/skill asks: "frontend", "seo", "react developer"
    if (preg_match('/\b(find|looking|want|need|anything|fresher|junior|intern|internship|remote|developer|engineer|designer|role)\b/u', $text) === 1) {
        return true;
    }

    $stripped = $text;
    foreach ($matchedSkills as $phrase) {
        $stripped = preg_replace('/(?<![a-z0-9])' . preg_quote($phrase, '/') . '(?![a-z0-9])/u', ' ', $stripped) ?? $stripped;
    }
    foreach (['kathmandu', 'lalitpur', 'bhaktapur', 'pokhara', 'biratnagar', 'butwal', 'chitwan', 'dharan', 'nepal', 'in', 'for', 'a', 'an', 'the', 'me', 'please'] as $noise) {
        $stripped = preg_replace('/(?<![a-z0-9])' . preg_quote($noise, '/') . '(?![a-z0-9])/u', ' ', $stripped) ?? $stripped;
    }
    $stripped = trim(preg_replace('/\s+/', ' ', $stripped) ?? $stripped);

    return $stripped === '';
}

/**
 * Pull role, place, and job-type constraints out of a chat sentence.
 *
 * @return array{skills: list<string>, location: string, remote: bool, fresher: bool, terms: list<string>}
 */
function chatbotJobSearchConstraints(string $message): array
{
    $text = chatbotNormalize($message);
    $skills = [];
    foreach (chatbotSearchLexicon() as $phrase) {
        if (!chatbotContainsPhrase($text, $phrase)) {
            continue;
        }
        $covered = false;
        foreach ($skills as $existing) {
            if (chatbotContainsPhrase($existing, $phrase) || chatbotContainsPhrase($phrase, $existing)) {
                $covered = true;
                break;
            }
        }
        if (!$covered) {
            $skills[] = $phrase;
        }
    }

    $location = '';
    foreach (['kathmandu', 'lalitpur', 'bhaktapur', 'pokhara', 'biratnagar', 'butwal', 'chitwan', 'dharan', 'nepal'] as $place) {
        if (chatbotContainsPhrase($text, $place)) {
            $location = $place;
            break;
        }
    }

    if ($skills === []) {
        $tokens = preg_split('/\s+/u', $text) ?: [];
        foreach ($tokens as $token) {
            $fuzzy = chatbotFuzzyLexiconMatch($token);
            if ($fuzzy === null) {
                continue;
            }
            $covered = false;
            foreach ($skills as $existing) {
                if (chatbotContainsPhrase($existing, $fuzzy) || chatbotContainsPhrase($fuzzy, $existing)) {
                    $covered = true;
                    break;
                }
            }
            if (!$covered) {
                $skills[] = $fuzzy;
            }
        }
    }

    if ($skills === []) {
        $freeform = chatbotExtractFreeformTopic($text);
        if ($freeform !== '') {
            $skills[] = $freeform;
        }
    }

    return [
        'skills' => $skills,
        'location' => $location,
        'remote' => preg_match('/\b(remote|wfh|work from home)\b/u', $text) === 1,
        'fresher' => preg_match('/\b(fresher|junior|intern|internship|entry level|graduate)\b/u', $text) === 1,
        'terms' => chatbotExpandSearchTerms($skills),
    ];
}

function chatbotNoJobsFoundMessage(string $summary): string
{
    $label = trim($summary);
    if ($label === '') {
        return 'No jobs found for that search right now. Try a different role, or browse all jobs.';
    }

    return 'No jobs found for “' . $label . '” right now. Try a different role or wording, or browse all jobs.';
}

function chatbotConstraintsActive(array $constraints): bool
{
    return ($constraints['skills'] ?? []) !== []
        || ($constraints['location'] ?? '') !== ''
        || !empty($constraints['remote'])
        || !empty($constraints['fresher']);
}

function chatbotJobMatchesConstraints(array $job, array $constraints): bool
{
    $title = mb_strtolower((string) ($job['title'] ?? ''));
    $skills = mb_strtolower((string) ($job['skills'] ?? ''));
    $description = mb_strtolower((string) ($job['description'] ?? ''));
    $location = mb_strtolower((string) ($job['location'] ?? ''));
    $titleSkills = $title . ' ' . $skills;
    $haystack = $titleSkills . ' ' . $description . ' ' . $location;

    if (!empty($constraints['remote'])) {
        if (preg_match('/\b(remote|wfh|work from home)\b/u', $haystack) !== 1) {
            return false;
        }
    }

    if (($constraints['location'] ?? '') !== '' && !str_contains($location . ' ' . $title, $constraints['location'])) {
        return false;
    }

    if (!empty($constraints['fresher']) && preg_match('/\b(fresher|junior|intern|internship|entry level|graduate)\b/u', $haystack) !== 1) {
        return false;
    }

    $askedSkills = $constraints['skills'] ?? [];
    $strongTerms = chatbotStrongSearchTerms($askedSkills);
    $terms = $strongTerms !== []
        ? $strongTerms
        : ($constraints['terms'] ?? chatbotExpandSearchTerms($askedSkills));

    if ($terms !== []) {
        $matched = false;
        foreach ($terms as $phrase) {
            if (chatbotContainsPhrase($titleSkills, $phrase)) {
                $matched = true;
                break;
            }
        }
        // Fallback: match the asked role words in the description when title/skills are sparse.
        if (!$matched) {
            foreach ($askedSkills as $skill) {
                if (chatbotContainsPhrase($description, $skill)) {
                    $matched = true;
                    break;
                }
            }
        }
        if (!$matched) {
            return false;
        }
    }

    return true;
}

function chatbotAskRelevanceScore(array $job, array $constraints): int
{
    $title = mb_strtolower((string) ($job['title'] ?? ''));
    $skills = mb_strtolower((string) ($job['skills'] ?? ''));
    $description = mb_strtolower((string) ($job['description'] ?? ''));
    $askedSkills = $constraints['skills'] ?? [];
    $strongTerms = chatbotStrongSearchTerms($askedSkills);
    $terms = $strongTerms !== [] ? $strongTerms : ($constraints['terms'] ?? chatbotExpandSearchTerms($askedSkills));
    $score = 0;

    foreach ($askedSkills as $skill) {
        if (chatbotContainsPhrase($title, $skill)) {
            $score += 6;
        } elseif (chatbotContainsPhrase($skills, $skill)) {
            $score += 4;
        } elseif (chatbotContainsPhrase($description, $skill)) {
            $score += 2;
        }
    }

    foreach ($terms as $phrase) {
        if (chatbotContainsPhrase($title, $phrase)) {
            $score += 3;
        } elseif (chatbotContainsPhrase($skills, $phrase)) {
            $score += 2;
        }
    }

    if (!empty($constraints['remote']) && preg_match('/\b(remote|wfh|work from home)\b/u', $title . ' ' . $skills) === 1) {
        $score += 1;
    }
    if (($constraints['location'] ?? '') !== '' && str_contains($job['location'] ?? '', $constraints['location'])) {
        $score += 1;
    }

    return $score;
}

function chatbotConstraintSummary(array $constraints): string
{
    $parts = [];
    if (!empty($constraints['remote'])) {
        $parts[] = 'remote';
    }
    if (!empty($constraints['fresher'])) {
        $parts[] = 'fresher';
    }
    if (($constraints['skills'] ?? []) !== []) {
        $parts[] = implode(', ', $constraints['skills']);
    }
    $summary = implode(' ', $parts);
    if (($constraints['location'] ?? '') !== '') {
        $place = mb_convert_case($constraints['location'], MB_CASE_TITLE, 'UTF-8');
        $summary = trim($summary . ' in ' . $place);
    }

    return $summary;
}

function buildChatbotJobCards(array $jobs, bool $includeMatch = true): array
{
    $cards = [];

    foreach ($jobs as $job) {
        $isExternal = !empty($job['is_external']);
        $cards[] = [
            'id' => (int) ($job['id'] ?? 0),
            'title' => $job['title'] ?? '',
            'company' => $job['company'] ?? '',
            'location' => $job['location'] ?? '',
            'match' => $includeMatch ? (int) ($job['match'] ?? 0) : null,
            'url' => $job['url'] ?? ('/seeker/jobs.php?id=' . (int) ($job['id'] ?? 0)),
            'is_external' => $isExternal,
            'external_url' => $job['external_url'] ?? null,
            'apply_label' => $isExternal ? 'Apply on LinkedIn' : 'Easy Apply',
            'source_label' => $job['source_label'] ?? ($isExternal ? 'LinkedIn' : 'Jagiree'),
        ];
    }

    return $cards;
}

function generateChatbotReply(int $seekerId, string $message): array
{
    $profile = fetchSeekerProfile($seekerId);
    if (!$profile) {
        return [
            'success' => false,
            'error' => 'Could not load your profile.',
        ];
    }

    $intent = detectChatbotIntent($message);
    $hasCv = seekerHasCv($seekerId);
    $skills = $profile['skill_list'] ?? [];
    $name = trim($profile['full_name'] ?? 'there');

    // Drop stale NLP CV cache if the file was already removed.
    if (
        !$hasCv
        && (
            trim((string) ($profile['cv_parsed_text'] ?? '')) !== ''
            || trim((string) ($profile['cv_titles'] ?? '')) !== ''
            || trim((string) ($profile['cv_parsed_at'] ?? '')) !== ''
        )
    ) {
        clearSeekerCvParseData($seekerId);
        $profile['cv_parsed_text'] = null;
        $profile['cv_parsed_at'] = null;
        $profile['cv_titles'] = null;
        $profile['title_list'] = [];
    }

    $reply = [
        'success' => true,
        'intent' => $intent,
        'text' => '',
        'html' => null,
        'action' => null,
        'skills' => $skills,
        'has_cv' => $hasCv,
        'jobs' => [],
    ];

    switch ($intent) {
        case 'empty':
            $reply['text'] = 'Please type a message, or tap a quick question below.';
            break;

        case 'greeting':
            $reply['text'] = "Hi {$name}! I can explain how Jagiree works, help with Easy Apply vs LinkedIn jobs, or recommend roles from your profile. What would you like?";
            break;

        case 'thanks':
            $reply['text'] = "You're welcome! Ask anytime for recommendations, CV help, or how to apply.";
            break;

        case 'about':
            $reply['text'] = "Jagiree connects job seekers and employers:\n\n"
                . "1. Build your profile and upload one CV\n"
                . "2. Browse jobs ranked by match score\n"
                . "3. **Easy Apply** on Jagiree jobs sends your CV to the employer\n"
                . "4. **LinkedIn** jobs open on LinkedIn — you apply there\n"
                . "5. Track Jagiree applications under Applications\n\n"
                . "Ask for a role (\"frontend jobs\", \"SEO\") and I'll search by that, or say \"recommend jobs for me\" to rank from your CV/profile.";
            break;

        case 'apply':
            $reply['text'] = $hasCv
                ? "How to apply:\n\n"
                    . "**Jagiree jobs**\n"
                    . "1. Open a job\n"
                    . "2. Click **Easy Apply**\n"
                    . "3. Your profile + CV go to the employer on this site\n"
                    . "4. Track status under Applications\n\n"
                    . "**LinkedIn jobs** (badge: LinkedIn)\n"
                    . "Click **Apply on LinkedIn** — you finish the application on LinkedIn, not here."
                : "Upload your CV first (sidebar or attach icon), then:\n\n"
                    . "• **Easy Apply** for Jagiree employer jobs\n"
                    . "• **Apply on LinkedIn** for LinkedIn listings\n\n"
                    . "Only Easy Apply creates an application on this site.";
            $reply['action'] = $hasCv ? null : 'highlight-upload';
            break;

        case 'linkedin':
            $reply['text'] = "LinkedIn jobs are synced into Jagiree for discovery and matching.\n\n"
                . "• They show a **LinkedIn** badge\n"
                . "• The button is **Apply on LinkedIn**\n"
                . "• Your application is completed on LinkedIn — employers do not receive it in the Jagiree dashboard\n\n"
                . "Jagiree employer jobs use **Easy Apply** and stay fully on this platform.";
            break;

        case 'cv':
            $parsedAt = trim((string) ($profile['cv_parsed_at'] ?? ''));
            if ($hasCv && $parsedAt !== '') {
                $skillLine = $skills !== [] ? implode(', ', array_slice($skills, 0, 10)) : 'none detected yet';
                $reply['text'] = "Your CV is saved and NLP-parsed.\n\nExtracted skills: {$skillLine}\n\nAsk for recommendations to rank jobs using your CV text and skills.";
            } elseif ($hasCv) {
                $reply['text'] = "Your CV is saved, but NLP has not parsed it yet. Re-upload the CV while the Python NLP service is running, or ask me to recommend jobs from your profile skills.";
                $reply['action'] = 'highlight-upload';
            } else {
                $reply['text'] = "Upload a PDF or DOCX (max 5MB) with the sidebar button or the paperclip icon. When the NLP service is running, skills are extracted from the file automatically.";
                $reply['action'] = 'highlight-upload';
            }
            break;

        case 'skills':
            $skillsLine = $skills !== []
                ? 'Your profile skills: ' . implode(', ', $skills) . '.'
                : 'You have no skills on your profile yet. Add some under Profile → Skills & CV.';
            $reply['text'] = $skillsLine . "\n\nCommon in-demand skills on Jagiree include PHP, React, Figma, UI/UX, Python, and digital marketing.";
            break;

        case 'help':
            $reply['text'] = "You can ask me:\n\n"
                . "• Frontend jobs / backend / SEO / software engineer\n"
                . "• Recommend jobs for me (uses your CV/profile)\n"
                . "• How does Jagiree work?\n"
                . "• How do I apply?\n"
                . "• What about LinkedIn jobs?\n"
                . "• Help with my CV\n"
                . "• What skills are in demand?";
            break;

        case 'recommend':
            require_once __DIR__ . '/nlp-client.php';

            $cvText = $hasCv ? trim((string) ($profile['cv_parsed_text'] ?? '')) : '';
            $titles = $hasCv ? ($profile['title_list'] ?? []) : [];
            $constraints = chatbotJobSearchConstraints($message);
            $constrained = chatbotConstraintsActive($constraints);
            $summary = chatbotConstraintSummary($constraints);
            // Role/skill asks search by what they typed. Profile/CV ranking only when they ask for it (or no role was named).
            $useProfile = !$constrained || chatbotWantsProfileRecommendations($message);

            if ($useProfile && !$hasCv && $cvText === '' && $skills === []) {
                $reply['text'] = 'Upload a CV or add skills on your profile so I can recommend matching jobs. Or ask for a role directly, like “frontend jobs” or “SEO roles”.';
                $reply['action'] = 'highlight-upload';
                break;
            }

            $nlpJobsPayload = fetchJobsForNlpRanking($constrained ? 200 : 80);
            if ($constrained) {
                $nlpJobsPayload = array_values(array_filter(
                    $nlpJobsPayload,
                    static fn (array $job): bool => chatbotJobMatchesConstraints($job, $constraints)
                ));
            }

            if ($constrained && $nlpJobsPayload === []) {
                $reply['text'] = chatbotNoJobsFoundMessage($summary);
                break;
            }

            if ($useProfile) {
                $rankSkills = $skills;
                $rankTitles = $titles;
                $rankFocus = $constrained ? $summary : '';
                $rankCv = $cvText;
            } else {
                // Ask-first mode: rank only against what the user typed, not the CV/profile.
                $rankSkills = $constraints['terms'] !== [] ? $constraints['terms'] : $constraints['skills'];
                $rankTitles = $constraints['skills'];
                $rankFocus = $summary;
                $rankCv = '';
                $reply['skills'] = $constraints['skills'];
            }

            $nlpResult = nlpRecommendJobs($nlpJobsPayload, $rankSkills, $rankTitles, $rankFocus, $rankCv, 5);

            if (!empty($nlpResult['success']) && !empty($nlpResult['jobs'])) {
                $byId = [];
                foreach ($nlpJobsPayload as $row) {
                    $byId[(int) ($row['id'] ?? 0)] = $row;
                }

                $cards = [];
                foreach ($nlpResult['jobs'] as $job) {
                    $id = (int) ($job['id'] ?? 0);
                    $original = $byId[$id] ?? [];
                    $isExternal = !empty($job['is_external']) || !empty($original['is_external']);
                    $externalUrl = trim((string) ($job['external_url'] ?? $original['external_url'] ?? ''));
                    $cards[] = [
                        'id' => $id,
                        'title' => $job['title'] ?? ($original['title'] ?? ''),
                        'company' => $job['company'] ?? ($original['company'] ?? ''),
                        'location' => $job['location'] ?? ($original['location'] ?? ''),
                        // Profile match % only when ranking against CV/skills — ask-mode scores are not a profile match.
                        'match' => $useProfile ? (int) ($job['match'] ?? 0) : null,
                        'url' => $job['url'] ?? ($original['url'] ?? ('/seeker/jobs.php?id=' . $id)),
                        'is_external' => $isExternal,
                        'external_url' => $externalUrl !== '' ? $externalUrl : null,
                        'apply_label' => $isExternal ? 'Apply on LinkedIn' : 'Easy Apply',
                        'source_label' => $job['source_label'] ?? ($original['source_label'] ?? ($isExternal ? 'LinkedIn' : 'Jagiree')),
                    ];
                }
                $reply['jobs'] = $cards;
                $reply['match_basis'] = $useProfile ? 'profile' : 'ask';
                $reply['nlp_engine'] = $nlpResult['engine'] ?? 'tfidf-cosine';
                if (!$useProfile) {
                    $reply['text'] = 'Here are ' . $summary . ' roles from live listings (matched to your ask, not your CV).';
                } elseif ($constrained) {
                    $reply['text'] = 'Here are ' . $summary . ' roles, ranked against your profile and CV.';
                } elseif ($cvText !== '') {
                    $reply['text'] = 'Here are jobs ranked from your CV and profile skills.';
                } elseif ($hasCv) {
                    $reply['text'] = 'Here are jobs ranked from your profile skills. Re-upload your CV while NLP is running for stronger matches.';
                } else {
                    $reply['text'] = 'No CV on file — ranking from your profile skills only. Upload a CV for stronger matches.';
                }
                break;
            }

            if ($constrained) {
                if ($nlpJobsPayload !== []) {
                    usort($nlpJobsPayload, static function (array $a, array $b) use ($constraints, $useProfile, $skills): int {
                        if (!$useProfile) {
                            return chatbotAskRelevanceScore($b, $constraints) <=> chatbotAskRelevanceScore($a, $constraints);
                        }

                        return calculateSeekerJobMatch($skills, $b['skills'] ?? null)
                            <=> calculateSeekerJobMatch($skills, $a['skills'] ?? null);
                    });

                    $relevant = $nlpJobsPayload;
                    if (!$useProfile) {
                        $relevant = array_values(array_filter(
                            $nlpJobsPayload,
                            static fn (array $job): bool => chatbotAskRelevanceScore($job, $constraints) > 0
                        ));
                    }

                    if ($relevant === []) {
                        $reply['text'] = chatbotNoJobsFoundMessage($summary);
                        break;
                    }

                    $reply['jobs'] = buildChatbotJobCards(array_slice($relevant, 0, 5), $useProfile);
                    $reply['match_basis'] = $useProfile ? 'profile' : 'ask';
                    if (!$useProfile) {
                        $reply['skills'] = $constraints['skills'];
                        $reply['text'] = 'Here are ' . $summary . ' roles from the live listings (matched to your ask).';
                    } else {
                        $reply['text'] = 'NLP is offline, so these are ' . $summary . ' roles filtered from listings, then sorted by your profile skills.';
                    }
                    break;
                }

                $reply['text'] = chatbotNoJobsFoundMessage($summary);
                break;
            }

            $jobs = getSeekerRecommendations($seekerId, 5, 'match');
            $reply['jobs'] = buildChatbotJobCards($jobs, true);
            $reply['match_basis'] = 'profile';
            $reply['text'] = !empty($nlpResult['offline'])
                ? 'NLP service is offline, so I used profile skill matching instead. Start the Python service for CV-based ranking.'
                : ($reply['jobs'] === []
                    ? 'No jobs found that match your profile right now. Browse all jobs or update your skills.'
                    : 'Here are your top matched jobs from profile skills.');
            break;

        default:
            $reply['text'] = "I can help with job recommendations, how Jagiree works, Easy Apply vs LinkedIn, or your CV. Try: \"Recommend jobs for me\".";
            break;
    }

    return $reply;
}
