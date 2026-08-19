<?php /** @var array $course */ ?>
<footer class="site-footer">
  <div class="site-footer__inner">
    <p>
      <?= e($course['university']) ?>, <?= e($course['campus']) ?><br>
      <?= e($course['code']) ?> — <?= e($course['name']) ?>, <?= e($course['subtitle']) ?><br>
      <?= e($course['term']) ?>
    </p>
    <p class="site-footer__note">Diario de aprendizaje construido con PHP puro, sin base de datos.</p>
  </div>
</footer>
