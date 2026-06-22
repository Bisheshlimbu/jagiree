# Jagiree

AI-powered job recommendation platform built with **PHP** (CRUD & web app) and **Python** (NLP chatbot).

## Roles

| Role | Responsibilities |
|------|------------------|
| **Admin** | Manage users, approve employer job post requests |
| **Employer** | Submit job postings (pending admin approval) |
| **Seeker** | Browse jobs, upload CV/resume, chat with AI bot for recommendations |

## Project Structure

```
Jageree/
├── database/          # SQLite file (created by setup script)
├── includes/          # Shared PHP partials (header, footer, auth)
├── public/            # Web root
│   ├── index.php      # Landing page (guest)
│   └── assets/        # CSS, JS, images
├── python/            # NLP chatbot service (coming soon)
├── sql/               # Database schema
└── router.php         # Dev server router
```

## Run Locally

### 1. Database setup (SQLite — no XAMPP/MAMP/MySQL needed)

Just like Laravel's SQLite driver, Jagiree stores data in a local file. Run once:

```bash
php scripts/setup.php
```

This creates `database/jagiree.sqlite` and seeds the default admin user:

| Field | Value |
|-------|-------|
| Username | `bishesh` |
| Password | `adminBishesh` |

Only one admin account exists — admin users cannot register through the site.

Optional: copy `.env.example` to `.env` if you want to change the database file path.

### 2. Start the server

From the project root:

```bash
php -S localhost:8000 router.php
```

Open [http://localhost:8000](http://localhost:8000)

## Authentication

| Page | URL |
|------|-----|
| Log in | `/login.php` |
| Choose role | `/register.php` |
| Job seeker registration | `/register-seeker.php` |
| Employer registration | `/register-employer.php` |
| Log out | `/logout.php` |

Dashboards require login with the matching role. Wrong-role users are redirected to their own dashboard.

## Current Status

- [x] Landing page (guest / not logged in)
- [x] Admin dashboard (Overview, Analytics, Jobs, Users, Settings)
- [x] Employer dashboard (Overview, Job Listings, Applicants, Analytics, Settings, Post Job)
- [x] Seeker dashboard (Overview, Profile, Applications, Recommendations, Analytics, Settings)
- [x] Authentication (login / register)
- [ ] Python NLP chatbot integration

## Admin Dashboard

Open [http://localhost:8000/admin/](http://localhost:8000/admin/) after starting the server.

Pages:
- `/admin/` — Overview with stats, chart, AI insights, recent registrations
- `/admin/jobs.php` — Approve/reject employer job post requests
- `/admin/users.php` — Manage platform users
- `/admin/analytics.php` — Analytics (placeholder)
- `/admin/settings.php` — Platform & NLP bot settings

## Employer Dashboard

Open [http://localhost:8000/employer/](http://localhost:8000/employer/) after starting the server.

Pages:
- `/employer/` — Overview with stats, applications, AI insights, active jobs, interviews
- `/employer/job-listings.php` — Manage job postings
- `/employer/applicants.php` — Talent pool / applicants
- `/employer/post-job.php` — Submit new job for admin approval
- `/employer/analytics.php` — Reports (placeholder)
- `/employer/settings.php` — Company profile settings

## Seeker Dashboard

Open [http://localhost:8000/seeker/](http://localhost:8000/seeker/) after starting the server.

Pages:
- `/seeker/` — Home feed with job search, AI recommendations, profile sidebar
- `/seeker/jobs.php` — Browse all jobs with filters
- `/seeker/applications.php` — Track application status
- `/seeker/profile.php` — Edit profile & upload CV
- `/seeker/chat.php` — AI chatbot (rule-based NLP, CV upload, job recommendations)
- `/seeker/settings.php` — Job alert preferences
