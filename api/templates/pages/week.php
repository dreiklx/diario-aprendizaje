<?php
/**
 * @var array $course
 * @var array $entry
 * @var string $status
 * @var array $prevEntry
 * @var array $nextEntry
 */
$number = (int) $entry['number'];
$hasContent = $status === STATUS_COMPLETADA;
?>
<article class="week">
  <nav class="week__breadcrumb" aria-label="Miga de pan">
    <a href="/">Diario</a>
    <span aria-hidden="true">/</span>
    <span>Semana <?= $number ?> de <?= (int) $course['total_weeks'] ?></span>
  </nav>

  <header class="week__header">
    <p class="week__eyebrow">Semana <?= $number ?> · <?= e(ucfirst(format_weekday($entry['date']))) ?> <?= e(format_date_long($entry['date'])) ?></p>
    <h1 class="week__title"><?= $entry['title'] ? e($entry['title']) : 'Reflexión pendiente' ?></h1>
    <?php if ($entry['theme']): ?>
      <p class="week__theme"><?= e($entry['theme']) ?></p>
    <?php endif; ?>
    <?= render_partial('partials/status-badge', ['status' => $status]) ?>
  </header>

  <?php if ($hasContent): ?>
    <div class="week__body">
      <section class="week__section">
        <h2>Reflexión</h2>
        <p><?= nl2br(e($entry['reflexion'])) ?></p>
      </section>

      <?php if (!empty($entry['aprendizaje'])): ?>
        <section class="week__section">
          <h2>Aprendizaje</h2>
          <p><?= nl2br(e($entry['aprendizaje'])) ?></p>
        </section>
      <?php endif; ?>

      <?php if (!empty($entry['cuestionamiento'])): ?>
        <section class="week__section week__section--quote">
          <h2>Cuestionamiento</h2>
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
      <p>Esta semana ya está disponible, pero la reflexión todavía no se ha escrito.</p>
      <p class="week__empty-hint">Se completa editando <code>api/data/entries.php</code>.</p>
    </div>
  <?php else: ?>
    <div class="week__empty">
      <p>Esta semana todavía no llega. Vuelve el <?= e(format_date_long($entry['date'])) ?> para leer la reflexión.</p>
    </div>
  <?php endif; ?>

  <nav class="week__pager" aria-label="Navegación entre semanas">
    <?php if ($prevEntry): ?>
      <a class="week__pager-link week__pager-link--prev" href="/semana/<?= (int) $prevEntry['number'] ?>" data-pager="prev">
        <span class="week__pager-direction">← Anterior</span>
        <span class="week__pager-label">Semana <?= (int) $prevEntry['number'] ?></span>
      </a>
    <?php else: ?>
      <span class="week__pager-link week__pager-link--disabled" aria-hidden="true"></span>
    <?php endif; ?>

    <a class="week__pager-index" href="/">Índice del diario</a>

    <?php if ($nextEntry): ?>
      <a class="week__pager-link week__pager-link--next" href="/semana/<?= (int) $nextEntry['number'] ?>" data-pager="next">
        <span class="week__pager-direction">Siguiente →</span>
        <span class="week__pager-label">Semana <?= (int) $nextEntry['number'] ?></span>
      </a>
    <?php else: ?>
      <span class="week__pager-link week__pager-link--disabled" aria-hidden="true"></span>
    <?php endif; ?>
  </nav>
</article>
