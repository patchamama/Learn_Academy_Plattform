<?php
/** @var array $course  Course data model */
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? '';
?>
<header class="header">
    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Search...">
    </div>
    <div class="header-actions">
        <button id="theme-toggle" class="lang-switch" title="Toggle theme">
            <i class="fa-solid fa-circle-half-stroke"></i>
        </button>
        <button class="lang-switch" data-switch-lang="en" title="English">EN</button>
        <button class="lang-switch" data-switch-lang="es" title="Español">ES</button>
    </div>
</header>
