-- Learn Academy Platform — SQLite Schema

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    email        TEXT    NOT NULL UNIQUE,
    password_hash TEXT   NOT NULL,
    name         TEXT    NOT NULL DEFAULT '',
    role         TEXT    NOT NULL DEFAULT 'student' CHECK (role IN ('student', 'admin')),
    locale       TEXT    NOT NULL DEFAULT 'en' CHECK (locale IN ('en', 'es')),
    created_at   INTEGER NOT NULL DEFAULT (strftime('%s', 'now'))
);

-- Courses (registered courses in the system)
CREATE TABLE IF NOT EXISTS courses (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT    NOT NULL UNIQUE,
    title        TEXT    NOT NULL,
    description  TEXT    NOT NULL DEFAULT '',
    source_dir   TEXT    NOT NULL,
    thumbnail    TEXT    NOT NULL DEFAULT '',
    created_at   INTEGER NOT NULL DEFAULT (strftime('%s', 'now'))
);

-- Course sections (numbered folders)
CREATE TABLE IF NOT EXISTS sections (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id    INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    folder_name  TEXT    NOT NULL,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    title        TEXT    NOT NULL DEFAULT ''
);

-- Lessons (file groups within a section)
CREATE TABLE IF NOT EXISTS lessons (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    section_id   INTEGER NOT NULL REFERENCES sections(id) ON DELETE CASCADE,
    prefix       TEXT    NOT NULL,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    title        TEXT    NOT NULL DEFAULT '',
    config_json  TEXT    NOT NULL DEFAULT '{}'
);

-- Files belonging to a lesson
CREATE TABLE IF NOT EXISTS lesson_files (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    lesson_id    INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    filename     TEXT    NOT NULL,
    file_type    TEXT    NOT NULL CHECK (file_type IN ('video','audio','image','text','html','markdown','attachment','subtitle')),
    path         TEXT    NOT NULL
);

-- Enrollments (access grants per user per course)
CREATE TABLE IF NOT EXISTS enrollments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    course_id    INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    granted_by   INTEGER REFERENCES users(id),
    expires_at   INTEGER NOT NULL,
    created_at   INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    UNIQUE (user_id, course_id)
);

-- Per-lesson access overrides (admin can unlock specific lessons)
CREATE TABLE IF NOT EXISTS access_grants (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    enrollment_id   INTEGER NOT NULL REFERENCES enrollments(id) ON DELETE CASCADE,
    lesson_id       INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    unlocked_at     INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    UNIQUE (enrollment_id, lesson_id)
);

-- Progress (completed lessons per user)
CREATE TABLE IF NOT EXISTS progress (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    lesson_id    INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    completed    INTEGER NOT NULL DEFAULT 0 CHECK (completed IN (0, 1)),
    completed_at INTEGER,
    UNIQUE (user_id, lesson_id)
);

-- User settings (JSON blob)
CREATE TABLE IF NOT EXISTS settings (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    settings_json TEXT   NOT NULL DEFAULT '{}'
);

-- Comments (threaded, up to 2 levels)
CREATE TABLE IF NOT EXISTS comments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    lesson_id    INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    parent_id    INTEGER REFERENCES comments(id) ON DELETE CASCADE,
    body         TEXT    NOT NULL,
    status       TEXT    NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    created_at   INTEGER NOT NULL DEFAULT (strftime('%s', 'now'))
);

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    course_id    INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    provider     TEXT    NOT NULL CHECK (provider IN ('stripe', 'paypal')),
    provider_ref TEXT    NOT NULL DEFAULT '',
    amount       INTEGER NOT NULL DEFAULT 0,
    currency     TEXT    NOT NULL DEFAULT 'USD',
    status       TEXT    NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
    created_at   INTEGER NOT NULL DEFAULT (strftime('%s', 'now'))
);

-- Evaluations / quiz results
CREATE TABLE IF NOT EXISTS evaluations (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    lesson_id    INTEGER NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    score        REAL    NOT NULL DEFAULT 0,
    data_json    TEXT    NOT NULL DEFAULT '{}',
    created_at   INTEGER NOT NULL DEFAULT (strftime('%s', 'now'))
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_sections_course    ON sections(course_id);
CREATE INDEX IF NOT EXISTS idx_lessons_section    ON lessons(section_id);
CREATE INDEX IF NOT EXISTS idx_lesson_files_lesson ON lesson_files(lesson_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_user   ON enrollments(user_id);
CREATE INDEX IF NOT EXISTS idx_progress_user      ON progress(user_id);
CREATE INDEX IF NOT EXISTS idx_comments_lesson    ON comments(lesson_id);
CREATE INDEX IF NOT EXISTS idx_comments_status    ON comments(status);
CREATE INDEX IF NOT EXISTS idx_payments_user      ON payments(user_id);
