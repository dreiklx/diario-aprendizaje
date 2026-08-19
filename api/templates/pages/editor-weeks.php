<?php
/**
 * @var array $entries
 */
?>
<section class="editor">
  <header class="editor__header">
    <p class="editor__eyebrow">Editor</p>
    <h1>Elegí una semana</h1>
  </header>

  <ol class="editor-weeks">
    <?php foreach ($entries as $entry): $status = entry_status($entry); ?>
      <li>
        <a class="editor-weeks__item" href="/editar/semana/<?= (int) $entry['week'] ?>">
          <span class="editor-weeks__label">
            <span class="editor-weeks__number"><?= sprintf('%02d', $entry['week']) ?></span>
            <span><?= $entry['title'] ? e($entry['title']) : 'Reflexión pendiente' ?></span>
          </span>
          <?= render_partial('partials/status-badge', ['status' => $status]) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ol>

  <p class="editor-login__back editor-footer-row">
    <a class="editor-link" href="/">← Ver el diario</a>
    <form class="editor-logout-form" method="post" action="/editar/logout">
      <button type="submit" class="editor-link editor-logout-button">Cerrar sesión</button>
    </form>
  </p>
</section>
