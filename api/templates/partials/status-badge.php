<?php
/** @var string $status */
?>
<span class="status-badge status-badge--<?= e($status) ?>">
  <span class="status-badge__dot" aria-hidden="true"></span>
  <?= e(status_label($status)) ?>
</span>
