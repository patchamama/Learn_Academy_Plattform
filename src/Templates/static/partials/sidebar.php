<?php
/** @var array $course  Course data model */
/** @var string $activePage  'dashboard'|'courses'|'settings' */
$activePage = $activePage ?? 'dashboard';
?>
<nav class="sidebar">
    <div class="logo">
        <img src="assets/logo.png" alt="Logo" onerror="this.style.display='none'">
        <span><?= e($course['title']) ?></span>
    </div>
    <ul class="menu">
        <li>
            <a href="index.html" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="course.html" class="<?= $activePage === 'courses' ? 'active' : '' ?>">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span>Course</span>
            </a>
        </li>
    </ul>
    <ul class="bottom-menu">
        <li>
            <a href="settings.html" class="<?= $activePage === 'settings' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</nav>
