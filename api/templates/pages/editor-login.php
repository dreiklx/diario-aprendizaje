<?php
/** @var string|null $loginError */
?>
<section class="editor-login">
  <p class="editor__eyebrow">Acceso privado</p>
  <h1>Editar el diario</h1>
  <p class="editor-login__lead">Este espacio es solo para quien escribe el diario. La lectura pública no necesita contraseña.</p>

  <?php if (!empty($loginError)): ?>
    <p class="editor-message editor-message--error"><?= e($loginError) ?></p>
  <?php endif; ?>

  <form method="post" action="/editar" autocomplete="off">
    <div class="editor-field">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required autofocus>
    </div>
    <button type="submit" class="editor-button">Entrar</button>
  </form>

  <p class="editor-login__back"><a class="editor-link" href="/">← Volver al diario</a></p>
</section>
