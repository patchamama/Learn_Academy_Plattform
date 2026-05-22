/**
 * Learn Academy Platform — Static Course Runtime
 * Handles progress, settings, navigation, search, and video controls.
 * All state stored in localStorage, keyed by course slug.
 */
(function () {
    'use strict';

    // ── Utilities ──────────────────────────────────────────────────────

    const courseSlug = document.documentElement.dataset.courseSlug || 'course';
    const storageKey = (key) => `lap:${courseSlug}:${key}`;

    function load(key, fallback = null) {
        try {
            const val = localStorage.getItem(storageKey(key));
            return val !== null ? JSON.parse(val) : fallback;
        } catch {
            return fallback;
        }
    }

    function save(key, value) {
        try {
            localStorage.setItem(storageKey(key), JSON.stringify(value));
        } catch {
            // Storage quota exceeded or private mode — silently ignore
        }
    }

    // ── Settings ───────────────────────────────────────────────────────

    const defaultSettings = {
        theme:         'light',
        fontSize:      16,
        fontSizes:     [14, 16, 18, 20],
        subtitles:     false,
        playbackSpeed: 1.0,
        locale:        'en',
    };

    let settings = Object.assign({}, defaultSettings, load('settings', {}));

    function applySettings() {
        document.documentElement.dataset.theme = settings.theme;
        document.documentElement.style.setProperty('--font-size-base', settings.fontSize + 'px');
    }

    function persistSettings() {
        save('settings', settings);
        applySettings();
    }

    applySettings();

    // Expose settings API to page templates
    window.LAP = window.LAP || {};
    window.LAP.settings = settings;
    window.LAP.saveSettings = persistSettings;

    // ── Progress ───────────────────────────────────────────────────────

    const progress = load('progress', {});

    function isCompleted(lessonId) {
        return !!progress[lessonId];
    }

    function markComplete(lessonId) {
        progress[lessonId] = { completedAt: Date.now() };
        save('progress', progress);
        updateProgressUI(lessonId);
    }

    function markIncomplete(lessonId) {
        delete progress[lessonId];
        save('progress', progress);
        updateProgressUI(lessonId);
    }

    function getCompletedCount(lessonIds) {
        return lessonIds.filter(id => isCompleted(id)).length;
    }

    function updateProgressUI(lessonId) {
        // Update sidebar checkmarks
        document.querySelectorAll(`[data-lesson-id="${lessonId}"]`).forEach(el => {
            el.classList.toggle('completed', isCompleted(lessonId));
        });

        // Refresh all progress bars
        document.querySelectorAll('.progress-bar-fill[data-section-id]').forEach(bar => {
            const sectionId = bar.dataset.sectionId;
            const ids = JSON.parse(bar.dataset.lessonIds || '[]');
            const completed = getCompletedCount(ids);
            const pct = ids.length > 0 ? Math.round((completed / ids.length) * 100) : 0;
            bar.style.width = pct + '%';

            const label = document.querySelector(`.progress-text[data-section-id="${sectionId}"]`);
            if (label) {
                label.textContent = completed + ' / ' + ids.length;
            }
        });

        // Update overall course progress bar
        const overall = document.getElementById('course-progress-bar');
        if (overall) {
            const allIds = JSON.parse(overall.dataset.allLessonIds || '[]');
            const completed = getCompletedCount(allIds);
            const pct = allIds.length > 0 ? Math.round((completed / allIds.length) * 100) : 0;
            overall.style.width = pct + '%';
            overall.textContent = pct + '%';

            const overallLabel = document.getElementById('course-progress-label');
            if (overallLabel) overallLabel.textContent = pct + '%';
        }
    }

    // ── Complete Button ─────────────────────────────────────────────────

    function initCompleteButton() {
        const btn = document.getElementById('btn-complete');
        if (!btn) return;

        const lessonId = btn.dataset.lessonId;
        const nextUrl  = btn.dataset.nextUrl;

        function updateBtn() {
            if (isCompleted(lessonId)) {
                btn.classList.add('completed');
                btn.textContent = btn.dataset.labelDone || '✓ Completed';
            } else {
                btn.classList.remove('completed');
                btn.textContent = btn.dataset.labelComplete || 'Complete & Continue →';
            }
        }

        updateBtn();
        updateProgressUI(lessonId);

        btn.addEventListener('click', () => {
            if (isCompleted(lessonId)) {
                markIncomplete(lessonId);
                updateBtn();
            } else {
                markComplete(lessonId);
                updateBtn();
                if (nextUrl) {
                    setTimeout(() => { window.location.href = nextUrl; }, 400);
                }
            }
        });
    }

    // ── Sidebar Lesson States ───────────────────────────────────────────

    function initSidebarProgress() {
        document.querySelectorAll('.sidebar-lesson-item[data-lesson-id]').forEach(item => {
            const id = item.dataset.lessonId;
            if (isCompleted(id)) {
                item.classList.add('completed');
                const check = item.querySelector('.check');
                if (check) check.style.display = 'inline';
            }
        });
    }

    // ── Sidebar Accordion ───────────────────────────────────────────────

    function initSidebarAccordion() {
        document.querySelectorAll('.sidebar-section-header').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.parentElement;
                section.classList.toggle('active');
            });
        });

        // Open the section containing the current lesson
        const active = document.querySelector('.sidebar-lesson-item.active');
        if (active) {
            const section = active.closest('.sidebar-section');
            if (section) section.classList.add('active');
        } else {
            // Open first section by default
            const first = document.querySelector('.sidebar-section');
            if (first) first.classList.add('active');
        }
    }

    // ── Main Page Accordion ─────────────────────────────────────────────

    function initMainAccordion() {
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', () => {
                header.parentElement.classList.toggle('active');
            });
        });
    }

    // ── Lesson Search ───────────────────────────────────────────────────

    function initSearch() {
        const input = document.getElementById('lesson-search');
        if (!input) return;

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            document.querySelectorAll('.sidebar-lesson-item').forEach(item => {
                const text = item.textContent.trim().toLowerCase();
                item.style.display = q === '' || text.includes(q) ? '' : 'none';
            });

            // Keep sections with visible lessons open
            document.querySelectorAll('.sidebar-section').forEach(section => {
                const visible = [...section.querySelectorAll('.sidebar-lesson-item')]
                    .some(el => el.style.display !== 'none');
                section.classList.toggle('active', visible && q !== '');
            });
        });
    }

    // ── Video Player ────────────────────────────────────────────────────

    function initVideoPlayer() {
        const video = document.querySelector('video.lesson-video');
        if (!video) return;

        const lessonId   = video.dataset.lessonId;
        const timestampKey = `video:${lessonId}:ts`;

        // Restore playback speed from settings
        video.playbackRate = settings.playbackSpeed;

        // Resume from last position
        const savedTs = load(timestampKey, 0);
        if (savedTs > 0 && savedTs < video.duration - 5) {
            video.currentTime = savedTs;
        }

        // Save position every 5 seconds
        video.addEventListener('timeupdate', () => {
            if (Math.round(video.currentTime) % 5 === 0) {
                save(timestampKey, video.currentTime);
            }
        });

        // Speed selector
        const speedSelect = document.getElementById('video-speed');
        if (speedSelect) {
            speedSelect.value = settings.playbackSpeed;
            speedSelect.addEventListener('change', () => {
                const speed = parseFloat(speedSelect.value);
                video.playbackRate     = speed;
                settings.playbackSpeed = speed;
                persistSettings();
            });
        }

        // Subtitle toggle
        const subBtn = document.getElementById('subtitle-toggle');
        if (subBtn) {
            let subtitleOn = settings.subtitles;

            function applySubtitle() {
                if (video.textTracks.length > 0) {
                    video.textTracks[0].mode = subtitleOn ? 'showing' : 'hidden';
                }
                subBtn.classList.toggle('active', subtitleOn);
            }

            applySubtitle();

            subBtn.addEventListener('click', () => {
                subtitleOn        = !subtitleOn;
                settings.subtitles = subtitleOn;
                persistSettings();
                applySubtitle();
            });
        }
    }

    // ── Settings Page ───────────────────────────────────────────────────

    function initSettingsPage() {
        if (!document.getElementById('settings-form')) return;

        // Theme
        const themeSelect = document.getElementById('setting-theme');
        if (themeSelect) {
            themeSelect.value = settings.theme;
            themeSelect.addEventListener('change', () => {
                settings.theme = themeSelect.value;
                persistSettings();
            });
        }

        // Language
        const langSelect = document.getElementById('setting-locale');
        if (langSelect) {
            langSelect.value = settings.locale;
            langSelect.addEventListener('change', () => {
                settings.locale = langSelect.value;
                persistSettings();
                // Reload to apply translated strings
                window.location.reload();
            });
        }

        // Subtitles
        const subCheck = document.getElementById('setting-subtitles');
        if (subCheck) {
            subCheck.checked = settings.subtitles;
            subCheck.addEventListener('change', () => {
                settings.subtitles = subCheck.checked;
                persistSettings();
            });
        }

        // Playback speed
        const speedSelect = document.getElementById('setting-speed');
        if (speedSelect) {
            speedSelect.value = settings.playbackSpeed;
            speedSelect.addEventListener('change', () => {
                settings.playbackSpeed = parseFloat(speedSelect.value);
                persistSettings();
            });
        }

        // Font size buttons
        document.querySelectorAll('.font-size-btn').forEach(btn => {
            const size = parseInt(btn.dataset.size, 10);
            if (size === settings.fontSize) btn.classList.add('active');
            btn.addEventListener('click', () => {
                settings.fontSize = size;
                persistSettings();
                document.querySelectorAll('.font-size-btn').forEach(b =>
                    b.classList.toggle('active', parseInt(b.dataset.size, 10) === size)
                );
            });
        });

        // Save button feedback
        const form = document.getElementById('settings-form');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            persistSettings();
            const msg = document.getElementById('settings-saved-msg');
            if (msg) {
                msg.style.display = 'inline';
                setTimeout(() => { msg.style.display = 'none'; }, 2000);
            }
        });
    }

    // ── Language Switcher (header) ──────────────────────────────────────

    function initLangSwitcher() {
        document.querySelectorAll('[data-switch-lang]').forEach(btn => {
            btn.addEventListener('click', () => {
                settings.locale = btn.dataset.switchLang;
                persistSettings();
                window.location.reload();
            });
        });
    }

    // ── Dark Mode Toggle ────────────────────────────────────────────────

    function initThemeToggle() {
        const toggle = document.getElementById('theme-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', () => {
            settings.theme = settings.theme === 'dark' ? 'light' : 'dark';
            persistSettings();
        });
    }

    // ── Init ────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => {
        initCompleteButton();
        initSidebarProgress();
        initSidebarAccordion();
        initMainAccordion();
        initSearch();
        initVideoPlayer();
        initSettingsPage();
        initLangSwitcher();
        initThemeToggle();
    });
})();
