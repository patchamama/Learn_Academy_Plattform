<?php
/** @var array  $course */
/** @var array  $sections */
/** @var bool   $hasAccess */
/** @var bool   $isAdmin */
/** @var int    $totalLessons */
?>
<div class="course-layout">

    <!-- Left: sections accordion -->
    <div class="course-sections">
        <h2><?= e(t('course.content')) ?></h2>
        <p style="font-size:0.85rem;color:var(--color-text-muted);margin-bottom:1rem">
            <?= e(t('course.sections', ['count' => count($sections)])) ?> &bull;
            <?= e(t('course.lessons', ['count' => $totalLessons])) ?>
        </p>

        <div class="accordion" id="course-accordion">
            <?php foreach ($sections as $si => $section): ?>
            <div class="accordion-item">
                <button class="accordion-header">
                    <?= e($section['title'] ?: $section['folder_name']) ?>
                    <span style="font-size:0.8rem;font-weight:400;color:var(--color-text-muted)">
                        <?= count($section['lessons']) ?> <?= e(t('course.lessons', ['count' => ''])) ?>
                    </span>
                </button>
                <div class="accordion-content">
                    <ul>
                        <?php foreach ($section['lessons'] as $lesson):
                            $config = json_decode($lesson['config_json'] ?? '{}', true);
                            $type   = $config['type'] ?? 'text';
                            $icon   = match($type) {
                                'video' => 'fa-video',
                                'audio' => 'fa-music',
                                default => 'fa-file-lines',
                            };
                        ?>
                        <li style="display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0">
                            <?php if ($hasAccess): ?>
                            <a href="/courses/<?= e($course['slug']) ?>/lesson/<?= (int)$lesson['id'] ?>"
                               style="display:flex;align-items:center;gap:0.5rem;color:inherit;text-decoration:none;flex:1">
                                <i class="fa-solid <?= $icon ?>"></i>
                                <?= e($lesson['title']) ?>
                            </a>
                            <?php else: ?>
                            <i class="fa-solid fa-lock" style="color:var(--color-text-muted)"></i>
                            <span style="color:var(--color-text-muted)"><?= e($lesson['title']) ?></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: info card -->
    <div class="course-info-card">
        <div class="card-banner">
            <div class="tag"><?= e($course['slug']) ?></div>
            <?php if (!empty($course['thumbnail'])): ?>
            <img src="<?= e($course['thumbnail']) ?>" alt="<?= e($course['title']) ?>" />
            <?php else: ?>
            <div style="background:var(--color-primary);height:140px;display:flex;align-items:center;justify-content:center">
                <i class="fa-solid fa-chalkboard-user" style="font-size:3rem;color:var(--color-accent)"></i>
            </div>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <h3><?= e(t('course.about')) ?></h3>
            <?php if (!empty($course['description'])): ?>
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:1rem;line-height:1.5">
                <?= e($course['description']) ?>
            </p>
            <?php endif; ?>

            <ul class="course-details">
                <li><i class="fa-solid fa-file"></i> <?= e(t('course.lessons', ['count' => $totalLessons])) ?></li>
                <li><i class="fa-solid fa-layer-group"></i> <?= e(t('course.sections', ['count' => count($sections)])) ?></li>
            </ul>

            <!-- Progress bar (filled via API on load) -->
            <?php if ($hasAccess): ?>
            <div class="progress-bar-wrap" style="margin:1rem 0 0.4rem" id="course-progress-bar-wrap">
                <div class="progress-bar-fill" id="course-progress-bar" style="width:0%"></div>
            </div>
            <p id="course-progress-label" style="font-size:0.8rem;color:var(--color-text-muted);margin-bottom:1rem">
                <?= e(t('course.completed', ['percent' => 0])) ?>
            </p>
            <?php endif; ?>

            <!-- CTA -->
            <?php if ($hasAccess): ?>
            <?php
            // Find first lesson for "continue" link
            $firstLesson = null;
            foreach ($sections as $sec) {
                if (!empty($sec['lessons'])) {
                    $firstLesson = $sec['lessons'][0];
                    break;
                }
            }
            ?>
            <a class="btn bnt-primary btn-course"
               href="<?= $firstLesson ? '/courses/' . e($course['slug']) . '/lesson/' . (int)$firstLesson['id'] : '#' ?>">
                <?= e(t('course.continue')) ?>
            </a>

            <?php elseif (isset($auth) && $auth->isLoggedIn()): ?>
            <a class="btn bnt-primary btn-course" href="/purchase/<?= e($course['slug']) ?>">
                <?= e(t('course.unlock')) ?>
            </a>
            <?php else: ?>
            <a class="btn bnt-primary btn-course" href="/login?redirect=<?= urlencode('/courses/' . $course['slug']) ?>">
                <?= e(t('nav.login')) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($hasAccess): ?>
<script>
(function () {
    fetch('/api/progress/<?= e($course['slug']) ?>', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const total = <?= (int)$totalLessons ?>;
            if (total === 0) return;
            const completed = Object.values(data).filter(v => v.completed).length;
            const percent = Math.round((completed / total) * 100);
            const bar = document.getElementById('course-progress-bar');
            const label = document.getElementById('course-progress-label');
            if (bar) bar.style.width = percent + '%';
            if (label) label.textContent = percent + '% completed';
        })
        .catch(() => {});
})();
</script>
<?php endif; ?>

<script>
// Accordion toggle
document.querySelectorAll('.accordion-header').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.accordion-item');
        const content = item.querySelector('.accordion-content');
        const isOpen = content.style.display === 'block';
        document.querySelectorAll('.accordion-content').forEach(c => c.style.display = 'none');
        content.style.display = isOpen ? 'none' : 'block';
    });
});
// Open first section by default
const first = document.querySelector('.accordion-content');
if (first) first.style.display = 'block';
</script>
