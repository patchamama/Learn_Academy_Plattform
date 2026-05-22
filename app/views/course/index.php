<?php
/** @var array $courses */
?>
<h2 style="margin-bottom:1.5rem"><?= e(t('nav.courses')) ?></h2>

<?php if (empty($courses)): ?>
<div style="text-align:center;padding:3rem;color:var(--color-text-muted)">
    <i class="fa-solid fa-book-open" style="font-size:3rem;margin-bottom:1rem;color:var(--color-accent);display:block"></i>
    <p>No courses available yet.</p>
</div>
<?php else: ?>
<div class="carousel-wrapper">
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
                    <div class="course-rating">
                        <?php if ($course['enrolled']): ?>
                        <span class="tag beginner" style="background:var(--color-accent);color:#fff">Enrolled</span>
                        <?php else: ?>
                        <span class="tag beginner"><?= e(t('course.start')) ?></span>
                        <?php endif; ?>
                        <div class="course-meta">
                            <span><i class="fa-solid fa-file"></i> <?= (int)$course['lesson_count'] ?></span>
                        </div>
                    </div>

                    <h3 class="course-title"><?= e($course['title']) ?></h3>

                    <?php if (!empty($course['description'])): ?>
                    <p style="font-size:0.82rem;color:var(--color-text-muted);line-height:1.4">
                        <?= e(mb_substr($course['description'], 0, 90)) ?><?= mb_strlen($course['description']) > 90 ? '…' : '' ?>
                    </p>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
