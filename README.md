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
├── includes/          # Shared PHP partials (header, footer)
├── public/            # Web root
│   ├── index.php      # Landing page (guest)
│   └── assets/        # CSS, JS, images
├── python/            # NLP chatbot service (coming soon)
└── router.php         # Dev server router
```

## Run Locally

From the project root:

```bash
php -S localhost:8000 router.php
```

Open [http://localhost:8000](http://localhost:8000)

## Current Status

- [x] Landing page (guest / not logged in)
- [x] Admin dashboard (Overview, Analytics, Jobs, Users, Settings)
- [ ] Authentication (login / register)
- [ ] Employer & seeker dashboards
- [ ] Python NLP chatbot integration

## Admin Dashboard

Open [http://localhost:8000/admin/](http://localhost:8000/admin/) after starting the server.

Pages:
- `/admin/` — Overview with stats, chart, AI insights, recent registrations
- `/admin/jobs.php` — Approve/reject employer job post requests
- `/admin/users.php` — Manage platform users
- `/admin/analytics.php` — Analytics (placeholder)
- `/admin/settings.php` — Platform & NLP bot settings
