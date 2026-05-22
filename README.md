# Learn Academy Platform

A course generation system that transforms numbered content directories into fully functional online courses. Supports two output modes: **static** (self-contained HTML/CSS/JS, no server required) and **dynamic** (PHP backend with authentication, payments, and persistence).

## Features

- Generates courses from a simple numbered-file directory convention
- Static mode: works offline, progress saved in localStorage
- Dynamic mode: PHP backend, SQLite, user accounts, access control
- Multilingual UI: English and Spanish
- Authentication and per-user access control (manual grant or payment)
- Payment integration: Stripe and PayPal (1-year access model)
- Comment system with moderation
- User dashboard with progress statistics and evaluation results
- Web editor for managing course content and configuration
- CLI for quick generation

## Directory Convention

```
course-root/
├── 01-introduction/
│   ├── 1.mp4             # video → main content
│   ├── 1.md              # supplemental text
│   ├── 1.conf.txt        # optional config override
│   ├── 1a.mp4            # sub-lesson
│   └── 2.pdf             # attachment (shown in Sources panel)
├── 02-fundamentals/
└── ...
```

**Rules:**
- Section = numbered folder (`01-intro`, `2_basics`, `003-advanced`)
- Lesson = group of files sharing the same numeric+letter prefix within a section
- Digit count is variable; sort is numeric
- `.conf.txt` is optional — auto-created if absent, title derived from filename

**Content priority:** video > audio > markdown/text > html

**Image insertion in markdown:**
```
{{img:filename.jpg}}
```

**Attachment types** (shown in Sources panel): `.pdf`, `.docx`, `.zip`

## Quick Start

### Static course

```bash
php generate.php --source /path/to/content --output /path/to/dist --mode static
```

Open `dist/index.html` in any browser — no server required.

### Dynamic course (PHP backend)

```bash
php generate.php --source /path/to/content --output /var/www/my-course --mode dynamic
```

Point a PHP server at the output directory. Requires PHP 8.1+ and PDO SQLite.

## .conf.txt Options

```
title: Custom Lesson Title
layout: default | video-first | text-first | audio-only
description: Short description shown below main content
show_attachments: true | false
show_image_gallery: true | false
subtitle_file: 1.vtt
```

## Requirements

- PHP 8.1+
- PDO SQLite extension (dynamic mode)
- Web server (dynamic mode): Apache / Nginx / PHP built-in server

## Project Structure

```
learn-academy-platform/
├── generate.php          # CLI entry point
├── src/
│   ├── Parser/           # Directory scanner and course data model builder
│   ├── Generator/        # Static and dynamic output generators
│   └── Templates/        # PHP templates for generated courses
├── app/                  # Dynamic web application
│   ├── auth/             # Login, register, session management
│   ├── api/              # REST endpoints
│   ├── admin/            # Admin panel (access control, moderation)
│   ├── editor/           # Course content editor
│   ├── payments/         # Stripe and PayPal integration
│   └── frontend/         # Student-facing pages
├── public/               # Web root
├── database/             # SQLite schema
└── i18n/                 # Translation files (en.php, es.php)
```

## License

MIT
# Learn_Academy_Plattform
