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
    <p class="hero__eyebrow"><?= e($course['university']) ?> — <?= e($course['campus']) ?></p>
    <h1 class="hero__title">Diario de<br>Aprendizaje</h1>
    <p class="hero__course"><?= e($course['code']) ?> · <?= e($course['name']) ?></p>
    <p class="hero__subtitle"><?= e($course['subtitle']) ?></p>
    <p class="hero__term"><?= e($course['term']) ?><?= $course['author'] ? ' · ' . e($course['author']) : '' ?></p>
    <p class="hero__description"><?= e($course['description']) ?></p>
  </div>
</section>

<section class="overview" aria-labelledby="overview-heading">
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
      <dd><?= e(format_date_long($entries[count($entries) - 1]['date'])) ?></dd>
    </div>
  </dl>
</section>

<section class="diary" aria-labelledby="diary-heading">
  <div class="diary__intro">
    <h2 id="diary-heading">El diario</h2>
    <p>Un recorrido semana a semana por el curso. Cada entrada reúne la reflexión, el aprendizaje principal, una pregunta abierta y su aplicación a la realidad nacional.</p>
  </div>
  <?= render_partial('partials/timeline', ['entries' => $entries, 'currentWeek' => $currentWeek]) ?>
</section>
