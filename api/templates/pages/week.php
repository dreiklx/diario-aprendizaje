<?php
/**
 * @var array $course
 * @var array $entry
 * @var string $status
 * @var array $prevEntry
 * @var array $nextEntry
 * @var bool $isEditorAuthenticated
 * @var array{name:string, content:string, errors:array<int,string>, success:bool, marker:?string} $commentForm
 */
$week = (int) $entry['week'];
$hasContent = $status === STATUS_COMPLETADA;
$comments = entry_comments($entry);
$commentCount = count($comments);
?>
<article class="week">
  <nav class="week__breadcrumb" aria-label="Miga de pan">
    <a href="/">Diario</a>
    <span aria-hidden="true">/</span>
    <span>Semana <?= $week ?> de <?= (int) $course['total_weeks'] ?></span>
  </nav>

  <header class="week__header">
    <p class="week__index"><?= sprintf('Semana %02d', $week) ?></p>
    <p class="week__eyebrow">
      <span><?= e(format_week_range($entry['week_start'])) ?></span>
      <span class="week__eyebrow-sep" aria-hidden="true">·</span>
      <span>Clase · <?= e(format_class_short($entry['class_date'])) ?></span>
    </p>
    <h1 class="week__title"><?= $entry['title'] ? e($entry['title']) : 'Reflexión pendiente' ?></h1>
    <?php if ($entry['theme']): ?>
      <p class="week__theme"><?= e($entry['theme']) ?></p>
    <?php endif; ?>
    <div class="week__header-actions">
      <?= render_partial('partials/status-badge', ['status' => $status]) ?>
      <a class="week__edit-link" href="/editar/semana/<?= $week ?>">
        <?= $hasContent ? 'Editar reflexión' : '+ Agregar reflexión' ?>
      </a>
    </div>
  </header>

  <?php if ($hasContent): ?>
    <div class="week__body">
      <?= render_blocks_html($entry['blocks']) ?>
    </div>

    <section class="comments" id="comentarios" aria-labelledby="comments-heading">
      <header class="comments__header">
        <h2 class="comments__title" id="comments-heading">Comentarios<?= $commentCount > 0 ? ' · ' . $commentCount : '' ?></h2>
      </header>

      <?php if ($commentCount === 0): ?>
        <p class="comments__empty">Sé la primera persona en comentar.</p>
      <?php else: ?>
        <ol class="comments__list">
          <?php foreach ($comments as $comment): ?>
            <li class="comments__item" data-comment-id="<?= e($comment['id']) ?>">
              <div class="comments__meta">
                <span class="comments__name"><?= e($comment['name']) ?></span>
                <time class="comments__date" datetime="<?= e($comment['created_at']) ?>"><?= e(format_comment_timestamp($comment['created_at'])) ?></time>
              </div>
              <p class="comments__text"><?= render_comment_content_html($comment['content']) ?></p>
              <?php if (!empty($isEditorAuthenticated)): ?>
                <form class="comments__delete" method="post" action="/editar/semana/<?= $week ?>/comentarios/eliminar">
                  <input type="hidden" name="csrf" value="<?= e(editor_csrf_token()) ?>">
                  <input type="hidden" name="comment_id" value="<?= e($comment['id']) ?>">
                  <button type="submit" class="comments__delete-btn">Eliminar</button>
                </form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>

      <div class="comments__form-wrap">
        <p class="comments__form-label">Participar en la conversación</p>

        <?php if (!empty($commentForm['errors'])): ?>
          <div class="comments__message comments__message--error">
            <?php foreach ($commentForm['errors'] as $error): ?>
              <p><?= e($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php elseif (!empty($commentForm['success'])): ?>
          <p class="comments__message comments__message--success" id="comment-status">Comentario guardado. Actualizando el diario…</p>
        <?php endif; ?>

        <form class="comments__form" method="post" action="/semana/<?= $week ?>#comentarios" id="comment-form">
          <input type="text" name="comments_hp" class="comments__honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="hidden" name="form_token" value="<?= e(issue_comment_form_token()) ?>">

          <label class="comments__field">
            <span>Nombre</span>
            <input type="text" name="name" maxlength="<?= COMMENT_NAME_MAX ?>" value="<?= e($commentForm['name']) ?>" required>
          </label>

          <label class="comments__field">
            <span>Comentario</span>
            <textarea name="content" maxlength="<?= COMMENT_TEXT_MAX ?>" rows="4" required><?= e($commentForm['content']) ?></textarea>
          </label>

          <button type="submit" class="comments__submit" id="comment-submit">Publicar comentario</button>
        </form>
      </div>
    </section>

    <script type="application/json" id="comments-initial-data"><?= json_encode([
        'week' => $week,
        'success' => (bool) $commentForm['success'],
        'marker' => $commentForm['marker'],
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  <?php elseif ($status === STATUS_DISPONIBLE): ?>
    <div class="week__empty">
      <p>Esta semana ya tuvo clase, pero la reflexión todavía no se ha escrito.</p>
      <p class="week__empty-hint"><a href="/editar/semana/<?= $week ?>">Escribirla ahora →</a></p>
    </div>
  <?php else: ?>
    <div class="week__empty">
      <p>Esta semana todavía no llega. Vuelve después del <?= e(format_class_short($entry['class_date'])) ?> para leer la reflexión.</p>
    </div>
  <?php endif; ?>

  <nav class="week__pager" aria-label="Navegación entre semanas">
    <?php if ($prevEntry): ?>
      <a class="week__pager-link week__pager-link--prev" href="/semana/<?= (int) $prevEntry['week'] ?>" data-pager="prev">
        <span class="week__pager-direction">← Anterior</span>
        <span class="week__pager-label">Semana <?= (int) $prevEntry['week'] ?></span>
      </a>
    <?php else: ?>
      <span class="week__pager-link week__pager-link--disabled" aria-hidden="true"></span>
    <?php endif; ?>

    <a class="week__pager-index" href="/">Índice del diario</a>

    <?php if ($nextEntry): ?>
      <a class="week__pager-link week__pager-link--next" href="/semana/<?= (int) $nextEntry['week'] ?>" data-pager="next">
        <span class="week__pager-direction">Siguiente →</span>
        <span class="week__pager-label">Semana <?= (int) $nextEntry['week'] ?></span>
      </a>
    <?php else: ?>
      <span class="week__pager-link week__pager-link--disabled" aria-hidden="true"></span>
    <?php endif; ?>
  </nav>
</article>
