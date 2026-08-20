<?php
/**
 * @var string $content
 * @var array $course
 * @var string|null $pageTitle
 * @var string|null $pageDescription
 * @var bool|null $private   true en páginas del editor: sin índice, sin caché.
 */

$siteTitle = 'Diario de Aprendizaje';
$fullTitle = $pageTitle ? "{$pageTitle} · {$siteTitle}" : "{$siteTitle} · {$course['code']}";
$description = $pageDescription ?? $course['description'];
$private ??= false;

if ($private) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, private');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<script>
/* Se ejecuta antes de pintar para evitar un flash del tema equivocado.
   Ver CLAUDE.md, sección "Modo claro/oscuro". */
(function () {
  try {
    var stored = localStorage.getItem('theme');
    var theme = (stored === 'light' || stored === 'dark')
      ? stored
      : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
  } catch (err) {}
  document.documentElement.classList.add('js');
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='14' fill='%23faf7f0' stroke='%231f3a5f' stroke-width='2'/><text x='16' y='21' font-family='Georgia,serif' font-size='13' fill='%231f3a5f' text-anchor='middle'>SR</text></svg>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap">

<link rel="stylesheet" href="<?= asset_url('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/layout.css') ?>">
<link rel="stylesheet" href="<?= asset_url('css/components.css') ?>">
<?php if ($private): ?>
<link rel="stylesheet" href="<?= asset_url('css/editor.css') ?>">
<?php endif; ?>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= render_partial('partials/nav', ['course' => $course]) ?>
<main id="contenido">
<?= $content ?>
</main>
<?= render_partial('partials/footer', ['course' => $course]) ?>
<script src="<?= asset_url('js/main.js') ?>" defer></script>
<?php if (!empty($loadComments)): ?>
<script src="<?= asset_url('js/comments.js') ?>" defer></script>
<?php endif; ?>
<?php if ($private): ?>
<script src="<?= asset_url('js/editor.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>
