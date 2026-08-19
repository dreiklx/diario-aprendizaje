<?php
/** @var array $course */
$currentPath = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$isHome = $currentPath === '/';
$isCourse = $currentPath === '/curso';
?>
<header class="site-nav">
  <div class="site-nav__inner">
    <a class="site-nav__brand" href="/">
      <span class="site-nav__mark" aria-hidden="true">SR</span>
      <span class="site-nav__title">
        Diario de Aprendizaje
        <span class="site-nav__subtitle"><?= e($course['code']) ?> · <?= e($course['term']) ?></span>
      </span>
    </a>
    <nav aria-label="Navegación principal" class="site-nav__links">
      <a href="/" aria-current="<?= $isHome ? 'page' : 'false' ?>">Diario</a>
      <a href="/curso" aria-current="<?= $isCourse ? 'page' : 'false' ?>">Acerca del curso</a>
    </nav>
  </div>
</header>
