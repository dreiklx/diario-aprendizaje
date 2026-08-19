<?php
/**
 * Índice principal del diario: una entrada por semana, en forma de
 * tabla de contenidos editorial con un hilo temporal a la izquierda.
 *
 * @var array $entries
 * @var int $currentWeek
 */
?>
<ol class="timeline">
<?php foreach ($entries as $i => $entry):
    $status = entry_status($entry);
    $isCurrent = $entry['week'] === $currentWeek;
    $delay = min($i * 50, 400);
    $hasContent = $status === STATUS_COMPLETADA;
?>
  <li class="timeline__item timeline__item--<?= e($status) ?><?= $isCurrent ? ' timeline__item--current' : '' ?> reveal" style="transition-delay: <?= $delay ?>ms">
    <div class="timeline__row">
      <a class="timeline__link" href="/semana/<?= (int) $entry['week'] ?>">
        <span class="timeline__node" aria-hidden="true"></span>
        <span class="timeline__index" aria-hidden="true"><?= sprintf('%02d', $entry['week']) ?></span>
        <span class="timeline__body">
          <span class="timeline__dates">
            <span class="timeline__range"><?= e(format_week_range($entry['week_start'])) ?></span>
            <span class="timeline__class">Clase · <?= e(format_class_short($entry['class_date'])) ?></span>
          </span>
          <span class="timeline__title">
            <?= $entry['title'] ? e($entry['title']) : '<span class="timeline__placeholder">Reflexión pendiente</span>' ?>
          </span>
        </span>
      </a>
      <span class="timeline__meta-actions">
        <?= render_partial('partials/status-badge', ['status' => $status]) ?>
        <a class="timeline__edit-link" href="/editar/semana/<?= (int) $entry['week'] ?>">
          <?= $hasContent ? 'Editar' : '+ Agregar' ?>
        </a>
      </span>
    </div>
  </li>
<?php endforeach; ?>
</ol>
