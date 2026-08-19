<?php
/**
 * @var array $course
 * @var array $entry
 * @var string $status
 * @var array $prevEntry
 * @var array $nextEntry
 * @var bool $isEditorAuthenticated
 */
$week = (int) $entry['week'];
$hasContent = $status === STATUS_COMPLETADA;
$comment = trim((string) ($entry['teacher_comment'] ?? ''));
?>
<article class="week">
  <nav class="week__breadcrumb" aria-label="Miga de pan">
    <a href="/">Diario</a>
    <span aria-hidden="true">/</span>
    <span>Semana <?= $week ?> de <?= (int) $course['total_weeks'] ?></span>
  </nav>

  <header class="week__header">
    <p class="week__index"><?= sprintf('Semana %02d', $week) ?></p>
    <p class="week__eyebrow">
      <span><?= e(format_week_range($entry['week_start'])) ?></span>
      <span class="week__eyebrow-sep" aria-hidden="true">·</span>
      <span>Clase · <?= e(format_class_short($entry['class_date'])) ?></span>
    </p>
    <h1 class="week__title"><?= $entry['title'] ? e($entry['title']) : 'Reflexión pendiente' ?></h1>
    <?php if ($entry['theme']): ?>
      <p class="week__theme"><?= e($entry['theme']) ?></p>
    <?php endif; ?>
    <div class="week__header-actions">
      <?= render_partial('partials/status-badge', ['status' => $status]) ?>
      <a class="week__edit-link" href="/editar/semana/<?= $week ?>">
        <?= $hasContent ? 'Editar reflexión' : '+ Agregar reflexión' ?>
      </a>
    </div>
  </header>

  <?php if ($hasContent): ?>
    <div class="week__body">
      <?= render_blocks_html($entry['blocks']) ?>
    </div>

    <section class="week__feedback" aria-labelledby="feedback-heading">
      <p class="week__feedback-label" id="feedback-heading">Comentarios de la profesora</p>
      <?php if ($comment !== ''): ?>
        <p class="week__feedback-text"><?= nl2br(e($comment)) ?></p>
      <?php else: ?>
        <p class="week__feedback-text week__feedback-text--empty">Sin retroalimentación todavía.</p>
      <?php endif; ?>
      <?php if (!empty($isEditorAuthenticated)): ?>
        <a class="week__edit-link week__feedback-edit" href="/editar/semana/<?= $week ?>/comentario">
          <?= $comment !== '' ? 'Editar comentario' : '+ Agregar comentario' ?>
        </a>
      <?php endif; ?>
    </section>
  <?php elseif ($status === STATUS_DISPONIBLE): ?>
    <div class="week__empty">
      <p>Esta semana ya tuvo clase, pero la reflexión todavía no se ha escrito.</p>
      <p class="week__empty-hint"><a href="/editar/semana/<?= $week ?>">Escribirla ahora →</a></p>
    </div>
  <?php else: ?>
    <div class="week__empty">
      <p>Esta semana todavía no llega. Vuelve después del <?= e(format_class_short($entry['class_date'])) ?> para leer la reflexión.</p>
    </div>
  <?php endif; ?>

  <nav class="week__pager" aria-label="Navegación entre semanas">
    <?php if ($prevEntry): ?>
      <a class="week__pager-link week__pager-link--prev" href="/semana/<?= (int) $prevEntry['week'] ?>" data-pager="prev">
        <span class="week__pager-direction">← Anterior</span>
        <span class="week__pager-label">Semana <?= (int) $prevEntry['week'] ?></span>
      </a>
    <?php else: ?>
      <span class="week__pager-link week__pager-link--disabled" aria-hidden="true"></span>
    <?php endif; ?>

    <a class="week__pager-index" href="/">Índice del diario</a>

    <?php if ($nextEntry): ?>
      <a class="week__pager-link week__pager-link--next" href="/semana/<?= (int) $nextEntry['week'] ?>" data-pager="next">
        <span class="week__pager-direction">Siguiente →</span>
        <span class="week__pager-label">Semana <?= (int) $nextEntry['week'] ?></span>
      </a>
    <?php else: ?>
      <span class="week__pager-link week__pager-link--disabled" aria-hidden="true"></span>
    <?php endif; ?>
  </nav>
</article>
