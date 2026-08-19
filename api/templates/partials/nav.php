<?php
/** @var array $course */
$currentPath = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$isHome = $currentPath === '/';
$isCourse = $currentPath === '/curso';
?>
<header class="site-nav">
  <div class="site-nav__inner">
    <div class="site-nav__row">
      <a class="site-nav__brand" href="/">
        <span class="site-nav__mark" aria-hidden="true">SR</span>
        <span class="site-nav__title">
          Diario de Aprendizaje
          <span class="site-nav__subtitle"><?= e($course['code']) ?> · <?= e($course['term']) ?></span>
        </span>
      </a>
      <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Cambiar a modo oscuro" aria-pressed="false">
        <svg class="theme-toggle__icon theme-toggle__icon--sun" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="4.5" stroke="currentColor" stroke-width="1.6"/>
          <path stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M12 2.5v2.2M12 19.3v2.2M21.5 12h-2.2M4.7 12H2.5M18.4 5.6l-1.55 1.55M7.15 16.85 5.6 18.4M18.4 18.4l-1.55-1.55M7.15 7.15 5.6 5.6"/>
        </svg>
        <svg class="theme-toggle__icon theme-toggle__icon--moon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M20 14.2A8.3 8.3 0 1 1 9.8 4a6.6 6.6 0 0 0 10.2 10.2Z"/>
        </svg>
      </button>
    </div>
    <nav aria-label="Navegación principal" class="site-nav__links">
      <a href="/" aria-current="<?= $isHome ? 'page' : 'false' ?>">Diario</a>
      <a href="/curso" aria-current="<?= $isCourse ? 'page' : 'false' ?>">Acerca del curso</a>
    </nav>
  </div>
</header>
