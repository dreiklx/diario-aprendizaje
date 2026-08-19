<?php
/** @var array{completed:int,total:int,percent:int} $progress */
?>
<div class="progress" role="group" aria-label="Avance del diario">
  <div class="progress__meta">
    <span class="progress__figure"><?= (int) $progress['completed'] ?> / <?= (int) $progress['total'] ?></span>
    <span class="progress__label">semanas completadas</span>
  </div>
  <div class="progress__track" role="progressbar" aria-valuenow="<?= (int) $progress['percent'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= (int) $progress['percent'] ?>% del diario completado">
    <div class="progress__fill" style="--progress-percent: <?= (int) $progress['percent'] ?>%"></div>
  </div>
</div>
