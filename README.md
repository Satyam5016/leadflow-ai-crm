# LeadFlow AI CRM

Production-oriented multi-tenant SaaS CRM portfolio project built with Laravel 12, React, Inertia.js, MySQL, Redis, Sanctum, Spatie Permission, queues, notifications, and Docker.

## Features

- Email-verified authentication, profile settings, password reset, and Sanctum-ready users.
- Workspace-based multi-tenancy with isolated leads, customers, deals, tasks, notes, files, emails, reports, and activity logs.
- Workspace switching, invitations, member roles, and seeded Spatie roles/permissions.
- CRM modules for lead management, customer profiles, drag-and-drop deal pipeline, task assignment, activity timelines, reports, and AI assistant mock logic.
- Lead, customer, and deal detail pages with tabs for overview, notes, tasks, emails, files, and activity.
- Search and filters for leads, customers, and deals.
- CSV lead import/export.
- Secure workspace-scoped file uploads and downloads for PDF, image, and document files.
- Email logging with mock AI summaries.
- Queueable database notifications for lead, deal, task assignments, invitation emails, and scheduled task reminders.
- React/Inertia dashboard UI with sidebar navigation, workspace switcher, tables, cards, charts, pipeline board, reports, and clean empty-ready layouts.

## Local Setup

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Demo users after seeding:

- `owner@leadflow.test` / `password`
- `sales@leadflow.test` / `password`

## Docker Setup

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install
npm run dev
```

Open `http://localhost:8080`.

## Useful Commands

```bash
php artisan queue:work
php artisan schedule:work
php artisan crm:send-task-reminders
php artisan test
npm run build
vendor/bin/pint
```

## Screenshots

Add screenshots here after running the app:

- Dashboard metrics and charts
- Leads list and AI score
- Deals pipeline
- Reports
- AI Assistant
