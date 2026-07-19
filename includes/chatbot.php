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

    return trim($text);
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

    if (chatbotMatches($text, ['hi', 'hello', 'hey', 'namaste', 'good morning', 'good evening', 'good afternoon'])) {
        return 'greeting';
    }

    if (chatbotMatches($text, ['thank', 'thanks', 'dhanyabad', 'appreciate'])) {
        return 'thanks';
    }

    if (chatbotMatches($text, ['recommend', 'suggestion', 'suggest', 'match me', 'find job', 'find me job', 'best job', 'jobs for me'])) {
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

function buildChatbotJobCards(array $jobs): array
{
    $cards = [];

    foreach ($jobs as $job) {
        $isExternal = !empty($job['is_external']);
        $cards[] = [
            'id' => (int) ($job['id'] ?? 0),
            'title' => $job['title'] ?? '',
            'company' => $job['company'] ?? '',
            'location' => $job['location'] ?? '',
            'match' => (int) ($job['match'] ?? 0),
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
                . "Ask me to recommend jobs and I will rank live listings using your skills (and CV text when available).";
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
                . "• Recommend jobs for me\n"
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

            if (!$hasCv && $cvText === '' && $skills === []) {
                $reply['text'] = 'Upload a CV or add skills on your profile so I can recommend matching jobs.';
                $reply['action'] = 'highlight-upload';
                break;
            }

            $nlpJobsPayload = fetchJobsForNlpRanking(80);
            $nlpResult = nlpRecommendJobs($nlpJobsPayload, $skills, $titles, $message, $cvText, 5);

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
                        'match' => (int) ($job['match'] ?? 0),
                        'url' => $job['url'] ?? ($original['url'] ?? ('/seeker/jobs.php?id=' . $id)),
                        'is_external' => $isExternal,
                        'external_url' => $externalUrl !== '' ? $externalUrl : null,
                        'apply_label' => $isExternal ? 'Apply on LinkedIn' : 'Easy Apply',
                        'source_label' => $job['source_label'] ?? ($original['source_label'] ?? ($isExternal ? 'LinkedIn' : 'Jagiree')),
                    ];
                }
                $reply['jobs'] = $cards;
                $reply['nlp_engine'] = $nlpResult['engine'] ?? 'tfidf-cosine';
                if ($cvText !== '') {
                    $reply['text'] = 'Here are NLP-ranked jobs using your CV text and skills (TF-IDF similarity).';
                } elseif ($hasCv) {
                    $reply['text'] = 'Here are NLP-ranked jobs using your profile skills (TF-IDF). Re-upload your CV while NLP is running for stronger matches.';
                } else {
                    $reply['text'] = 'No CV on file — ranking from your profile skills only. Upload a CV for stronger matches.';
                }
                break;
            }

            $jobs = getSeekerRecommendations($seekerId, 5, 'match');
            $reply['jobs'] = buildChatbotJobCards($jobs);
            $reply['text'] = !empty($nlpResult['offline'])
                ? 'NLP service is offline, so I used profile skill matching instead. Start the Python service for CV-based ranking.'
                : ($reply['jobs'] === []
                    ? 'No live jobs match your profile right now. Browse all jobs or update your skills.'
                    : 'Here are your top matched jobs from profile skills.');
            break;

        default:
            $reply['text'] = "I can help with job recommendations, how Jagiree works, Easy Apply vs LinkedIn, or your CV. Try: \"Recommend jobs for me\".";
            break;
    }

    return $reply;
}
