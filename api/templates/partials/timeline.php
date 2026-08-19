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
<?php foreach ($entries as $entry):
    $status = entry_status($entry);
    $isCurrent = $entry['number'] === $currentWeek;
?>
  <li class="timeline__item timeline__item--<?= e($status) ?><?= $isCurrent ? ' timeline__item--current' : '' ?>">
    <a class="timeline__link" href="/semana/<?= (int) $entry['number'] ?>">
      <span class="timeline__node" aria-hidden="true"></span>
      <span class="timeline__number">Semana <?= (int) $entry['number'] ?></span>
      <span class="timeline__title">
        <?= $entry['title'] ? e($entry['title']) : '<span class="timeline__placeholder">Reflexión pendiente</span>' ?>
      </span>
      <span class="timeline__meta">
        <span class="timeline__date"><?= e(format_date_short($entry['date'])) ?></span>
        <?= render_partial('partials/status-badge', ['status' => $status]) ?>
      </span>
    </a>
  </li>
<?php endforeach; ?>
</ol>
