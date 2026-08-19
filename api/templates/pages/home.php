<?php
/**
 * @var array $course
 * @var array $entries
 * @var array{completed:int,total:int,percent:int} $progress
 * @var int $currentWeek
 */
?>
<section class="hero">
  <div class="hero__inner">
    <p class="hero__eyebrow reveal"><?= e($course['university']) ?> — <?= e($course['campus']) ?></p>
    <h1 class="hero__title reveal" style="transition-delay: 60ms">Diario de<br>Aprendizaje</h1>
    <p class="hero__course reveal" style="transition-delay: 120ms"><?= e($course['code']) ?> · <?= e($course['name']) ?></p>
    <p class="hero__subtitle reveal" style="transition-delay: 160ms"><?= e($course['subtitle']) ?></p>
    <p class="hero__term reveal" style="transition-delay: 200ms"><?= e($course['term']) ?><?= $course['author'] ? ' · ' . e($course['author']) : '' ?></p>
    <p class="hero__description reveal" style="transition-delay: 240ms"><?= e($course['description']) ?></p>
  </div>
</section>

<section class="overview reveal" aria-labelledby="overview-heading">
  <h2 id="overview-heading" class="visually-hidden">Avance del semestre</h2>
  <?= render_partial('partials/progress', ['progress' => $progress]) ?>
  <dl class="overview__stats">
    <div class="overview__stat">
      <dt>Semana actual</dt>
      <dd><?= (int) $currentWeek ?> de <?= (int) $course['total_weeks'] ?></dd>
    </div>
    <div class="overview__stat">
      <dt>Inicio del ciclo</dt>
      <dd><?= e(format_date_long($course['semester_start'])) ?></dd>
    </div>
    <div class="overview__stat">
      <dt>Cierre estimado</dt>
      <dd><?= e(format_date_long($entries[count($entries) - 1]['class_date'])) ?></dd>
    </div>
  </dl>
</section>

<section class="diary" aria-labelledby="diary-heading">
  <div class="diary__intro reveal">
    <h2 id="diary-heading">El diario</h2>
    <p>Un recorrido semana a semana por el curso. Cada entrada reúne lo que me llevo de la sesión, lo que espero desarrollar, una pregunta que queda abierta y, cuando aplica, su conexión con la realidad nacional.</p>
  </div>
  <?= render_partial('partials/timeline', ['entries' => $entries, 'currentWeek' => $currentWeek]) ?>
</section>
