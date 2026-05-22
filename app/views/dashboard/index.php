<?php
/** @var array $courses */
/** @var array $user */
?>
<h1 style="margin-bottom:0.25rem"><?= e(t('dashboard.greeting', ['name' => $user['name'] ?? ''])) ?></h1>
<p style="color:var(--color-text-muted);margin-bottom:2rem"><?= e(t('dashboard.enrolled')) ?></p>

<?php if (empty($courses)): ?>
<div style="text-align:center;padding:3rem;color:var(--color-text-muted)">
    <i class="fa-solid fa-graduation-cap" style="font-size:3rem;margin-bottom:1rem;color:var(--color-accent);display:block"></i>
    <p><?= e(t('dashboard.no_courses')) ?></p>
    <a href="/courses"
       style="display:inline-block;margin-top:1rem;padding:0.6rem 1.5rem;background:var(--color-accent);color:#fff;border-radius:6px;text-decoration:none;font-weight:600">
        <?= e(t('nav.courses')) ?>
    </a>
</div>
<?php else: ?>
<div class="course-grid">
    <?php foreach ($courses as $course): ?>
    <div class="course-card">
        <a href="/courses/<?= e($course['slug']) ?>">
            <?php if (!empty($course['thumbnail'])): ?>
            <img src="<?= e($course['thumbnail']) ?>" alt="<?= e($course['title']) ?>" class="course-img" />
            <?php else: ?>
            <div class="course-img" style="background:var(--color-primary);display:flex;align-items:center;justify-content:center;min-height:140px">
                <i class="fa-solid fa-chalkboard-user" style="font-size:2.5rem;color:var(--color-accent)"></i>
            </div>
            <?php endif; ?>

            <div class="course-info">
                <h3 class="course-title"><?= e($course['title']) ?></h3>

                <?php if (!empty($course['description'])): ?>
                <p style="font-size:0.85rem;color:var(--color-text-muted);margin-bottom:0.75rem;line-height:1.4">
                    <?= e(mb_substr($course['description'], 0, 100)) ?><?= mb_strlen($course['description']) > 100 ? '…' : '' ?>
                </p>
                <?php endif; ?>

                <!-- Progress bar -->
                <div class="progress-bar-wrap" style="margin-bottom:0.4rem">
                    <div class="progress-bar-fill" style="width:<?= (int)$course['percent'] ?>%"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--color-text-muted)">
                    <span><?= e(t('course.completed', ['percent' => $course['percent']])) ?></span>
                    <span><?= (int)$course['completed'] ?> / <?= (int)$course['total'] ?> <?= e(t('course.lessons', ['count' => ''])) ?></span>
                </div>

                <?php if (!empty($course['expires_at'])): ?>
                <p style="font-size:0.75rem;color:var(--color-text-muted);margin-top:0.5rem">
                    <?= e(t('dashboard.expires', ['date' => date('Y-m-d', $course['expires_at'])])) ?>
                </p>
                <?php endif; ?>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
