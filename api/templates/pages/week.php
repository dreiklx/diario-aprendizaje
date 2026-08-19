<?php
/**
 * @var array $course
 * @var array $entry
 * @var string $status
 * @var array $prevEntry
 * @var array $nextEntry
 */
$week = (int) $entry['week'];
$hasContent = $status === STATUS_COMPLETADA;
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
    <?= render_partial('partials/status-badge', ['status' => $status]) ?>
  </header>

  <?php if ($hasContent): ?>
    <div class="week__body">
      <section class="week__section">
        <h2>Lo que me llevo</h2>
        <p><?= nl2br(e($entry['reflexion'])) ?></p>
      </section>

      <?php if (!empty($entry['aprendizaje'])): ?>
        <section class="week__section">
          <h2>Lo que espero desarrollar</h2>
          <p><?= nl2br(e($entry['aprendizaje'])) ?></p>
        </section>
      <?php endif; ?>

      <?php if (!empty($entry['cuestionamiento'])): ?>
        <section class="week__section week__section--quote">
          <h2>Una pregunta que me queda</h2>
          <p><?= nl2br(e($entry['cuestionamiento'])) ?></p>
        </section>
      <?php endif; ?>

      <?php if (!empty($entry['aplicacion'])): ?>
        <section class="week__section">
          <h2>Aplicación a la realidad nacional</h2>
          <p><?= nl2br(e($entry['aplicacion'])) ?></p>
        </section>
      <?php endif; ?>

      <?php if (!empty($entry['evidencia']['url'])): ?>
        <section class="week__section">
          <h2>Evidencia</h2>
          <p><a class="week__evidence" href="<?= e($entry['evidencia']['url']) ?>" target="_blank" rel="noopener noreferrer">
            <?= e($entry['evidencia']['label'] ?? 'Ver evidencia') ?> ↗
          </a></p>
        </section>
      <?php endif; ?>
    </div>
  <?php elseif ($status === STATUS_DISPONIBLE): ?>
    <div class="week__empty">
      <p>Esta semana ya tuvo clase, pero la reflexión todavía no se ha escrito.</p>
      <p class="week__empty-hint">Se completa editando <code>api/data/entries.php</code>.</p>
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
