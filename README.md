# Learn Academy Platform

A course generation system that transforms numbered content directories into fully functional online courses. Two output modes: **static** (self-contained HTML/CSS/JS, no server required) and **dynamic** (PHP backend with authentication, payments, and full persistence).

---

## Features

- Course parser: reads numbered file/folder conventions → structured course model
- Static mode: works offline, progress and settings saved in localStorage
- Dynamic mode: PHP + SQLite, user accounts, per-user access control, server-side progress
- Multilingual UI: English and Spanish (`t('key')` helper, cookie + DB persistence)
- Session-based authentication with CSRF protection; roles: `student`, `admin`
- Access control: admin manual grant or payment; 1-year expiry
- Payment integration: Stripe Checkout and PayPal Orders API (test/sandbox ready)
- Comment system with moderation queue (pending → approved/rejected)
- User dashboard: progress per course, enrollment expiry dates
- Web editor: admin UI to browse/edit course structure, upload files, reorder drag-and-drop
- CLI for quick static or dynamic generation

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.1+ |
| PDO SQLite extension | (bundled with most PHP installs) |
| Composer | 2.x |
| Web server | Apache / Nginx / PHP built-in (dev only) |

---

## Quick Start

### Option A — one command

```bash
./start.sh
```

This checks PHP + Composer, installs dependencies, creates `app/config.local.php` on first run, and starts the dev server at `http://localhost:8080`.

Pass a custom port:
```bash
./start.sh 9090
```

### Option B — manual

```bash
make setup        # composer install + create config.local.php
make dev          # start dev server at http://localhost:8080
```

---

## Available Make Targets

| Target | Description |
|---|---|
| `make install` | Run `composer install` |
| `make dev` | Start PHP built-in server at `localhost:8080` |
| `make setup` | `install` + create `app/config.local.php` template |
| `make help` | Show all targets |

Override the port: `PORT=9090 make dev`

---

## Configuration

All settings live in `app/config.php`. For local overrides (API keys, etc.) copy it:

```bash
cp app/config.php app/config.local.php   # already done by make setup / start.sh
```

`app/config.local.php` is gitignored — it never gets committed.

### Key config values

```php
'app_name'    => 'Learn Academy',
'app_url'     => 'http://localhost:8080',  // must match your actual URL

'stripe_public_key'     => 'pk_test_...',
'stripe_secret_key'     => 'sk_test_...',
'stripe_webhook_secret' => 'whsec_...',

'paypal_client_id' => '...',
'paypal_secret'    => '...',
'paypal_mode'      => 'sandbox',   // change to 'live' for production

'course_price_cents' => 2900,      // $29.00
'course_access_days' => 365,

'default_locale'    => 'en',
'supported_locales' => ['en', 'es'],

'admin_email' => 'you@example.com',
```

---

## First Admin User

After the first user registers, promote them to admin directly in the SQLite database:

```bash
sqlite3 database/app.sqlite \
  "UPDATE users SET role = 'admin' WHERE email = 'you@example.com';"
```

---

## Course Directory Convention

```
course-root/
├── 01-introduction/
│   ├── 1.mp4             # Lesson 1 — video (main content)
│   ├── 1.md              # Lesson 1 — supplemental text
│   ├── 1.conf.txt        # Lesson 1 — optional config
│   ├── 1a.mp4            # Lesson 1a — sub-lesson
│   └── 2.pdf             # Lesson 2 — attachment (Sources panel)
├── 02-fundamentals/
└── ...
```

**Rules:**
- **Section** = numbered folder. Prefix determines sort order (numeric, variable digits).
- **Lesson** = group of files sharing the same `N[letter]` prefix within a section.
- Sub-lessons use a letter suffix: `1a`, `1b`, `2a`, etc.
- `.conf.txt` is optional — auto-created with the title derived from the filename.

**Content priority** (first match wins): video → audio → markdown/text → html

**Embed images in markdown:**
```
{{img:filename.jpg}}
```

**Attachment types** shown in the Sources panel: `.pdf`, `.docx`, `.zip`

---

## .conf.txt Reference

```
title: Custom Lesson Title
layout: default | video-first | text-first | audio-only
description: Short text shown below main content
show_attachments: true | false
show_image_gallery: true | false
subtitle_file: 1.vtt
```

If absent, the file is auto-created with `title` derived from the main content filename.

---

## CLI — Static Generator

Generate a self-contained HTML/CSS/JS bundle (no server required):

```bash
php generate.php --source ./content --output ./dist --mode static
```

Options:

| Flag | Required | Description |
|---|---|---|
| `--source` | Yes | Path to the course content directory |
| `--output` | Yes | Output directory |
| `--mode` | Yes | `static` or `dynamic` |
| `--title=` | No | Override course title |

Open `dist/index.html` in any browser.

---

## Importing a Course (Dynamic Mode)

Via the admin panel at `/admin/courses`:

1. Log in as admin
2. Go to **Admin → Courses**
3. Enter the absolute path to your course directory
4. Click **Import Course**

Or programmatically:

```php
$importer = new \LearnAcademy\App\CourseImporter($db);
$courseId  = $importer->import('/absolute/path/to/course', 'My Title', '/path/to/thumbnail.jpg');
```

---

## Web Editor

Navigate to **Admin → Editor** (`/admin/editor`).

- Select a course to open the split-panel editor
- **Left panel**: section/lesson tree — drag to reorder (sections and cross-section lesson moves supported)
- **Right panel**: lesson config form, file list with upload/delete, preview link
- **Re-import from disk**: syncs DB with the source directory and updates `.conf.txt` files

---

## Payments

### Stripe

1. Create a Stripe account and get test keys from the Dashboard
2. Set `stripe_public_key`, `stripe_secret_key` in `config.local.php`
3. For webhooks (enrollment auto-activation): set `stripe_webhook_secret`
4. Webhook endpoint: `POST /api/webhooks/stripe`

Test card: `4242 4242 4242 4242` — any future date, any CVC.

### PayPal

1. Create a PayPal Developer sandbox app at developer.paypal.com
2. Set `paypal_client_id`, `paypal_secret`, `paypal_mode = 'sandbox'`
3. Webhook endpoint: `POST /api/webhooks/paypal`

---

## Comment Moderation

- Students post a comment → status `pending` (visible only to the poster)
- Admin approves at `/admin/moderation` → status `approved` (visible to all)
- Admin rejects → status `rejected` (hidden)
- Replies follow the same flow (threaded up to 2 levels)
- Pending count badge shown in admin sidebar

---

## Project Structure

```
learn-academy-platform/
├── start.sh              # Quick-start script (install + dev server)
├── Makefile              # Build targets
├── generate.php          # CLI entry point (static/dynamic generation)
├── composer.json         # PHP dependencies (stripe/stripe-php)
├── src/
│   ├── Parser/           # Directory scanner, FileNaming, ConfigParser, CourseBuilder
│   ├── Generator/        # StaticGenerator, MarkdownRenderer, AssetManager
│   ├── helpers.php       # t(), e(), slugify() — loaded globally via composer
│   └── Templates/        # PHP templates for generated static courses
├── app/
│   ├── App.php           # Bootstrap: DB, Auth, Router, View, routes
│   ├── Auth.php          # Session auth, CSRF, access control, enrollment
│   ├── Database.php      # PDO SQLite singleton, migrations, transactions
│   ├── Router.php        # Lightweight HTTP router
│   ├── View.php          # PHP template renderer (ob_start + extract)
│   ├── CourseImporter.php# Import course directory → SQLite DB
│   ├── config.php        # Default config (copy to config.local.php for overrides)
│   ├── Controllers/
│   │   ├── AuthController.php      # Login, register, logout
│   │   ├── CourseController.php    # Course listing and detail
│   │   ├── LessonController.php    # Lesson player + markdown rendering
│   │   ├── DashboardController.php # User dashboard + progress stats
│   │   ├── AdminController.php     # Users, access grants, comment moderation, courses
│   │   ├── ApiController.php       # REST: progress, settings, comments
│   │   ├── MediaController.php     # Stream media files (range requests)
│   │   ├── PaymentController.php   # Stripe + PayPal checkout and webhooks
│   │   ├── EditorController.php    # Web editor CRUD + reorder + file upload
│   │   └── AccountController.php  # User settings
│   └── views/
│       ├── layouts/      # app.php (sidebar), lesson.php (lesson player)
│       ├── auth/         # login.php, register.php
│       ├── course/       # index.php, detail.php
│       ├── lesson/       # show.php, locked.php
│       ├── dashboard/    # index.php
│       ├── admin/        # index, users, courses, moderation, editor/
│       ├── account/      # settings.php
│       └── payment/      # checkout.php
├── database/
│   └── schema.sql        # SQLite schema (auto-applied on first boot via migrate())
├── i18n/
│   ├── en.php            # English translations
│   └── es.php            # Spanish translations
└── public/
    ├── index.php         # Front controller
    ├── .htaccess         # Apache rewrite rules
    └── assets/           # CSS, JS, images
```

---

## Database Schema

Tables: `users`, `courses`, `sections`, `lessons`, `lesson_files`, `enrollments`, `access_grants`, `progress`, `settings`, `comments`, `payments`, `evaluations`

Schema is applied automatically via `Database::migrate()` on every boot (idempotent — safe to re-run).

---

## License

MIT
