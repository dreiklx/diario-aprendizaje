<?php
/**
 * Editor simple del comentario de la profesora — un solo textarea, sin
 * editor de bloques. Reutiliza los mismos componentes visuales que
 * editor-week.php (.editor-field, .editor-button, .editor-message).
 *
 * @var int $week
 * @var array $entry
 * @var string $sha
 * @var string $csrf
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var string $commentValue
 */
?>
<section class="editor">
  <header class="editor__header">
    <p class="editor__eyebrow">Editor · Comentario</p>
    <h1><?= sprintf('Semana %02d', $week) ?></h1>
    <?php if (!empty($entry['title'])): ?>
      <p class="editor__dates"><?= e($entry['title']) ?></p>
    <?php endif; ?>
  </header>

  <?php if (!empty($successMessage)): ?>
    <p class="editor-message editor-message--success"><?= e($successMessage) ?></p>
  <?php endif; ?>
  <?php if (!empty($errorMessage)): ?>
    <p class="editor-message editor-message--error"><?= e($errorMessage) ?></p>
  <?php endif; ?>

  <form method="post" action="/editar/semana/<?= $week ?>/comentario">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="sha" value="<?= e($sha) ?>">

    <div class="editor-field">
      <label for="teacher_comment">Comentario de la profesora
        <span class="editor-field__hint">Se muestra debajo de la reflexión en /semana/<?= $week ?>. Dejalo vacío para quitar el comentario.</span>
      </label>
      <textarea id="teacher_comment" name="teacher_comment" maxlength="4000" rows="8"><?= e($commentValue) ?></textarea>
    </div>

    <div class="editor-actions">
      <button type="submit" class="editor-button">Guardar comentario</button>
      <a class="editor-link" href="/semana/<?= $week ?>">Cancelar</a>
      <a class="editor-link" href="/semana/<?= $week ?>" target="_blank" rel="noopener">Ver en el diario ↗</a>
    </div>
  </form>
</section>
