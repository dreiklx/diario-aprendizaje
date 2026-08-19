<?php
/**
 * @var array $entry
 * @var string $sha
 * @var string $csrf
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var array|null $formValues  Valores a reflejar en el formulario (tras un error, para no perder lo escrito)
 */
$week = (int) $entry['week'];
$values = $formValues ?? $entry;
?>
<section class="editor">
  <header class="editor__header">
    <p class="editor__eyebrow">Editor</p>
    <h1><?= sprintf('Semana %02d', $week) ?></h1>
    <p class="editor__dates">
      <span><?= e(format_week_range($entry['week_start'])) ?></span>
      <span class="editor__dates-sep" aria-hidden="true">·</span>
      <span>Clase · <?= e(format_class_short($entry['class_date'])) ?></span>
    </p>
  </header>

  <?php if (!empty($successMessage)): ?>
    <p class="editor-message editor-message--success"><?= e($successMessage) ?></p>
  <?php endif; ?>
  <?php if (!empty($errorMessage)): ?>
    <p class="editor-message editor-message--error"><?= e($errorMessage) ?></p>
  <?php endif; ?>

  <form method="post" action="/editar/semana/<?= $week ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="sha" value="<?= e($sha) ?>">

    <div class="editor-field">
      <label for="title">Título</label>
      <input type="text" id="title" name="title" value="<?= e($values['title'] ?? '') ?>" maxlength="120">
    </div>

    <div class="editor-field">
      <label for="theme">Tema</label>
      <input type="text" id="theme" name="theme" value="<?= e($values['theme'] ?? '') ?>" maxlength="160">
    </div>

    <div class="editor-field">
      <label for="reflexion">Lo que me llevo
        <span class="editor-field__hint">Determina el estado de la entrada: en cuanto tenga contenido, pasa a "completada".</span>
      </label>
      <textarea id="reflexion" name="reflexion" maxlength="4000"><?= e($values['reflexion'] ?? '') ?></textarea>
    </div>

    <div class="editor-field">
      <label for="aprendizaje">Lo que espero desarrollar</label>
      <textarea id="aprendizaje" name="aprendizaje" maxlength="4000"><?= e($values['aprendizaje'] ?? '') ?></textarea>
    </div>

    <div class="editor-field">
      <label for="cuestionamiento">Una pregunta que me queda</label>
      <textarea id="cuestionamiento" name="cuestionamiento" maxlength="2000"><?= e($values['cuestionamiento'] ?? '') ?></textarea>
    </div>

    <div class="editor-field">
      <label for="aplicacion">Aplicación a la realidad nacional
        <span class="editor-field__hint">Opcional.</span>
      </label>
      <textarea id="aplicacion" name="aplicacion" maxlength="2000"><?= e($values['aplicacion'] ?? '') ?></textarea>
    </div>

    <div class="editor-actions">
      <button type="submit" class="editor-button">Guardar</button>
      <a class="editor-link" href="/editar">Cancelar</a>
      <a class="editor-link" href="/semana/<?= $week ?>" target="_blank" rel="noopener">Ver en el diario ↗</a>
    </div>
  </form>
</section>
