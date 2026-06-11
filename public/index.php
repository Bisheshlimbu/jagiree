<?php
$pageTitle = 'Jagiree — Your Career, Accelerated by AI';
$activeNav = 'home';
require_once __DIR__ . '/../includes/header.php';

$trendingJobs = [
    [
        'title' => 'Senior UX Designer',
        'company' => 'Nexus Digital',
        'location' => 'San Francisco, CA (Remote)',
        'match' => 98,
        'logo' => 'N',
        'tags' => ['Design Systems', 'Figma', 'SaaS'],
    ],
    [
        'title' => 'AI Research Engineer',
        'company' => 'Anthropic',
        'location' => 'London, UK',
        'match' => 95,
        'logo' => 'A',
        'tags' => ['Python', 'LLMs', 'Transformers'],
    ],
    [
        'title' => 'Product Manager',
        'company' => 'Stripe',
        'location' => 'Dublin, Ireland',
        'match' => 92,
        'logo' => 'S',
        'tags' => ['Strategy', 'Analytics', 'Fintech'],
    ],
];
?>

<main>
    <!-- Hero -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <span class="hero-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Next-Gen Career Matching
                </span>
                <h1 class="hero-title">Your Career, Accelerated by AI.</h1>
                <p class="hero-subtitle">
                    Stop searching. Start matching. Our neural engine connects top talent with visionary
                    companies through precision AI analysis.
                </p>
                <div class="hero-actions">
                    <a href="/register.php?role=seeker" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="#features" class="btn btn-outline btn-lg">How it Works</a>
                </div>
            </div>

            <div class="hero-visual">
                <img
                    src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800&h=900&fit=crop&q=80"
                    alt="Professional using Jagiree AI job matching platform"
                    width="520"
                    height="580"
                    loading="eager"
                    onerror="this.src='/assets/images/hero.svg'"
                >
                <div class="hero-status">
                    <div class="hero-status-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                        </svg>
                    </div>
                    <div class="hero-status-text">
                        <strong>Neural Engine Active</strong>
                        <span>Scanning 1.2M opportunities&hellip;</span>
                    </div>
                    <div class="hero-status-bar">
                        <div class="hero-status-fill"></div>
                    </div>
                    <span class="hero-status-pct">98%</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section features" id="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Engineered for Excellence</h2>
                <p class="section-desc">
                    We don't just list jobs. Our engine understands your unique professional DNA to deliver
                    opportunities that actually fit.
                </p>
            </div>

            <div class="features-grid">
                <article class="feature-card">
                    <div class="feature-icon feature-icon--purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 18h8M4 22h12M6 14V2l6 4 6-4v12"/>
                        </svg>
                    </div>
                    <h3>Smart Matching</h3>
                    <p>
                        Our 98% accuracy rating ensures you're only seeing roles where your skills truly shine.
                        No more scrolling through irrelevant listings.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon feature-icon--green">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                    </div>
                    <h3>AI Career Assistant</h3>
                    <p>
                        Meet your personalized bot that helps you prepare for interviews, optimize your profile,
                        and understand market trends in real-time.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon feature-icon--orange">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                        </svg>
                    </div>
                    <h3>Direct Connections</h3>
                    <p>
                        Skip the traditional application queue. Connect directly with hiring managers at
                        companies looking for your exact skill set.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- Trending Jobs -->
    <section class="section jobs-section" id="jobs">
        <div class="container">
            <div class="jobs-header">
                <div>
                    <h2>Trending Roles</h2>
                    <p class="jobs-subtitle">Real matches happening right now on Jagiree.</p>
                </div>
                <a href="/jobs.php" class="jobs-link">
                    View all listings
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="jobs-grid">
                <?php foreach ($trendingJobs as $job): ?>
                <article class="job-card">
                    <div class="job-card-top">
                        <div class="job-logo"><?= htmlspecialchars($job['logo']) ?></div>
                        <span class="job-match"><?= $job['match'] ?>% Match</span>
                    </div>
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <p class="job-meta"><?= htmlspecialchars($job['company']) ?> &bull; <?= htmlspecialchars($job['location']) ?></p>
                    <div class="job-tags">
                        <?php foreach ($job['tags'] as $tag): ?>
                        <span class="job-tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Employer CTA -->
    <section class="section employer-section" id="employers">
        <div class="container">
            <div class="employer-cta">
                <div class="employer-content">
                    <h2>Hiring? Find your next star candidate in minutes, not months.</h2>
                    <div class="employer-actions">
                        <a href="/register.php?role=employer" class="btn btn-white btn-lg">Post a Job</a>
                        <a href="/contact.php" class="btn btn-ghost-white btn-lg">Request a Demo</a>
                    </div>
                </div>
                <div class="employer-icon" aria-hidden="true">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
                        <path d="M11 12h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 14"/>
                        <path d="m7 18 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"/>
                        <path d="m2 13 6 6"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
