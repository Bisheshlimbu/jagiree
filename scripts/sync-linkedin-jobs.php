#!/usr/bin/env php
<?php
/**
 * LinkedIn / Apify sync runner for cron.
 *
 * Usage:
 *   php scripts/sync-linkedin-jobs.php          # run only if schedule is due
 *   php scripts/sync-linkedin-jobs.php --force  # run now regardless of schedule
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/external-jobs.php';

$force = in_array('--force', $argv, true);
$result = runScheduledLinkedInSyncIfDue($force);

if (!empty($result['skipped'])) {
    fwrite(STDOUT, ($result['reason'] ?? 'Skipped.') . PHP_EOL);
    exit(0);
}

if (!empty($result['success'])) {
    fwrite(STDOUT, ($result['message'] ?? 'Sync complete.') . PHP_EOL);
    exit(0);
}

fwrite(STDERR, ($result['error'] ?? 'Sync failed.') . PHP_EOL);
exit(1);
