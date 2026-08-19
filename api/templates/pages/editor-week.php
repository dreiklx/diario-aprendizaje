<?php
/**
 * @var array $entry
 * @var string $sha
 * @var string $csrf
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var array|null $formValues  ['title'=>, 'theme'=>, 'blocks'=>] a reflejar (tras un error o al cargar)
 */
$week = (int) $entry['week'];
$values = $formValues ?? $entry;
$title = $values['title'] ?? '';
$theme = $values['theme'] ?? '';
$blocks = $values['blocks'] ?? [];

// Marcador para que el JS confirme, sondeando la página pública, que la
// publicación ya se ve en producción (ver assets/js/editor.js). Heurística
// simple: el título si hay uno, si no el texto del primer bloque con
// contenido — no es infalible, pero evita afirmar "Publicado" sin evidencia.
$checkMarker = trim((string) $title);
if ($checkMarker === '') {
    foreach ($blocks as $block) {
        if (in_array($block['type'] ?? '', ['heading', 'paragraph', 'highlight', 'quote'], true) && !empty(trim((string) ($block['text'] ?? '')))) {
            $checkMarker = mb_substr(trim($block['text']), 0, 40);
            break;
        }
    }
}

$initialData = [
    'week' => $week,
    'blocks' => array_values($blocks),
    'saveSuccess' => !empty($successMessage),
    'publishCheckMarker' => $checkMarker,
];
?>
<section class="editor editor--week" data-week="<?= $week ?>">
  <header class="editor__header">
    <p class="editor__eyebrow">Editor</p>
    <h1><?= sprintf('Semana %02d', $week) ?></h1>
    <p class="editor__dates">
      <span><?= e(format_week_range($entry['week_start'])) ?></span>
      <span class="editor__dates-sep" aria-hidden="true">·</span>
      <span>Clase · <?= e(format_class_short($entry['class_date'])) ?></span>
    </p>
  </header>

  <?php if (!empty($successMessage)): ?>
    <p class="editor-message editor-message--success"><?= e($successMessage) ?></p>
  <?php endif; ?>
  <?php if (!empty($errorMessage)): ?>
    <p class="editor-message editor-message--error"><?= e($errorMessage) ?></p>
  <?php endif; ?>

  <div class="editor-draft-banner" id="draft-banner" hidden>
    <p id="draft-banner-text"></p>
    <div class="editor-draft-banner__actions">
      <button type="button" class="editor-link" id="draft-restore">Restaurar borrador</button>
      <button type="button" class="editor-link" id="draft-discard">Descartar</button>
    </div>
  </div>

  <script type="application/json" id="editor-initial-data"><?= json_encode($initialData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

  <form method="post" action="/editar/semana/<?= $week ?>" id="editor-form">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="sha" value="<?= e($sha) ?>">
    <input type="hidden" name="blocks_json" id="blocks-json" value="">

    <div class="editor-field">
      <label for="title">Título de la reflexión</label>
      <input type="text" id="title" name="title" value="<?= e($title) ?>" maxlength="160" placeholder="Escribí un título…">
    </div>

    <div class="editor-field">
      <label for="theme">Tema <span class="editor-field__hint">Opcional — el eje temático de la sesión.</span></label>
      <input type="text" id="theme" name="theme" value="<?= e($theme) ?>" maxlength="200">
    </div>

    <div class="editor-tabs" role="tablist" aria-label="Vista del editor">
      <button type="button" class="editor-tab" role="tab" aria-selected="true" data-tab="edit" id="tab-edit">Editar</button>
      <button type="button" class="editor-tab" role="tab" aria-selected="false" data-tab="preview" id="tab-preview">Vista previa</button>
    </div>

    <div class="editor-workspace" id="editor-workspace">
      <div class="editor-pane editor-pane--edit" id="pane-edit" role="tabpanel">
        <div class="editor-blocks" id="blocks-container"></div>

        <div class="editor-add-block">
          <p class="editor-add-block__label">Agregar bloque</p>
          <div class="editor-add-block__buttons" role="group" aria-label="Agregar un bloque nuevo">
            <button type="button" class="editor-add-btn" data-add-block="paragraph" title="Párrafo de texto">¶ Párrafo</button>
            <button type="button" class="editor-add-btn" data-add-block="heading" title="Subtítulo de sección">H Subtítulo</button>
            <button type="button" class="editor-add-btn" data-add-block="highlight" title="Texto destacado">◆ Destacado</button>
            <button type="button" class="editor-add-btn" data-add-block="quote" title="Cita">" Cita</button>
            <button type="button" class="editor-add-btn" data-add-block="list" title="Lista ordenada o no">≡ Lista</button>
            <button type="button" class="editor-add-btn" data-add-block="divider" title="Separador visual">— Separador</button>
            <button type="button" class="editor-add-btn" data-add-block="link" title="Enlace a una URL">🔗 Enlace</button>
            <button type="button" class="editor-add-btn" data-add-block="image" title="Imagen por URL">🖼 Imagen</button>
          </div>
        </div>
      </div>

      <div class="editor-pane editor-pane--preview" id="pane-preview" role="tabpanel" hidden>
        <div class="editor-preview-surface">
          <p class="editor-preview-title" id="preview-title"></p>
          <p class="editor-preview-theme" id="preview-theme"></p>
          <div class="week__body" id="preview-blocks"></div>
        </div>
      </div>
    </div>

    <div class="editor-actions">
      <button type="submit" class="editor-button" id="save-button">Guardar y publicar</button>
      <span class="editor-save-status" id="save-status" role="status" aria-live="polite"></span>
      <a class="editor-link" href="/editar">Cancelar</a>
      <a class="editor-link" href="/semana/<?= $week ?>" target="_blank" rel="noopener">Ver en el diario ↗</a>
    </div>
    <p class="editor-draft-status" id="draft-status" role="status" aria-live="polite"></p>
  </form>
</section>
