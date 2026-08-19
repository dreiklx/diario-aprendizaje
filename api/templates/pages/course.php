<?php
/**
 * @var array $course
 * @var array{completed:int,total:int,percent:int} $progress
 */
?>
<article class="course-page">
  <header class="course-page__header">
    <p class="course-page__eyebrow">Acerca de este espacio</p>
    <h1>El diario de aprendizaje</h1>
    <p class="course-page__lead"><?= e($course['description']) ?></p>
  </header>

  <section class="course-page__section">
    <h2>El curso</h2>
    <dl class="course-page__facts">
      <div><dt>Código</dt><dd><?= e($course['code']) ?></dd></div>
      <div><dt>Nombre</dt><dd><?= e($course['name']) ?></dd></div>
      <div><dt>Eje temático</dt><dd><?= e($course['subtitle']) ?></dd></div>
      <div><dt>Universidad</dt><dd><?= e($course['university']) ?></dd></div>
      <div><dt>Sede</dt><dd><?= e($course['campus']) ?></dd></div>
      <div><dt>Ciclo</dt><dd><?= e($course['term']) ?></dd></div>
    </dl>
  </section>

  <section class="course-page__section">
    <h2>Cómo leer el diario</h2>
    <p>El diario se organiza en <?= (int) $course['total_weeks'] ?> entradas semanales, una por cada semana lectiva del ciclo. Cada entrada documenta la reflexión de la sesión, el aprendizaje principal, una pregunta que queda abierta y su aplicación a la realidad nacional costarricense.</p>
    <p>Las entradas se distinguen por su estado: <strong>completada</strong> cuando ya tiene reflexión escrita, <strong>disponible</strong> cuando la fecha de la sesión ya pasó pero aún no se ha escrito, y <strong>próxima</strong> cuando corresponde a una semana futura del ciclo.</p>
    <?= render_partial('partials/progress', ['progress' => $progress]) ?>
  </section>

  <p class="course-page__back"><a href="/">Volver al índice del diario →</a></p>
</article>
