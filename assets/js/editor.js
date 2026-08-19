/**
 * Editor de bloques de /editar/semana/{n}. Solo se carga en esa página
 * (ver layout.php). Nada de esto toca el sitio público directamente —
 * su única salida es el campo oculto #blocks-json, que el servidor
 * vuelve a validar por completo con sanitize_blocks_input() antes de
 * guardar nada (ver api/lib/blocks.php). La vista previa de acá es una
 * aproximación visual con las mismas clases CSS que la página pública;
 * el renderer real y con autoridad de seguridad es el de PHP — si
 * cambiás el marcado admitido (negrita/cursiva/destacado/enlace),
 * actualizá ambos lados (este archivo y api/lib/blocks.php).
 */
(function () {
  'use strict';

  var root = document.getElementById('editor-workspace');
  if (!root) return;

  var dataEl = document.getElementById('editor-initial-data');
  var initial = JSON.parse(dataEl.textContent);
  var week = initial.week;

  var blocksContainer = document.getElementById('blocks-container');
  var blocksJsonInput = document.getElementById('blocks-json');
  var titleInput = document.getElementById('title');
  var themeInput = document.getElementById('theme');
  var previewTitle = document.getElementById('preview-title');
  var previewTheme = document.getElementById('preview-theme');
  var previewBlocks = document.getElementById('preview-blocks');
  var saveStatus = document.getElementById('save-status');
  var saveButton = document.getElementById('save-button');
  var draftStatus = document.getElementById('draft-status');
  var draftBanner = document.getElementById('draft-banner');
  var draftBannerText = document.getElementById('draft-banner-text');
  var form = document.getElementById('editor-form');
  var tabEdit = document.getElementById('tab-edit');
  var tabPreview = document.getElementById('tab-preview');
  var paneEdit = document.getElementById('pane-edit');
  var panePreview = document.getElementById('pane-preview');

  var state = { blocks: initial.blocks || [] };

  var BLOCK_LABELS = {
    heading: 'Subtítulo',
    paragraph: 'Párrafo',
    highlight: 'Destacado',
    quote: 'Cita',
    list: 'Lista',
    divider: 'Separador',
    link: 'Enlace',
    image: 'Imagen'
  };

  var TEXT_TYPES = ['heading', 'paragraph', 'highlight', 'quote'];

  // — Utilidades —

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
  }

  function isSafeUrl(url) {
    if (!url) return false;
    if (/^https?:\/\/[^\s<>"]+$/i.test(url)) return true;
    return url.indexOf('/') === 0 && url.indexOf('//') !== 0 && !/\s/.test(url);
  }

  /** Espejo de render_inline_markup() en api/lib/blocks.php — mantener en sync. */
  function renderInlineMarkup(text) {
    var html = escapeHtml(text);
    html = html.replace(/==(.+?)==/g, '<mark class="inline-highlight">$1</mark>');
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function (match, label, url) {
      if (!isSafeUrl(url)) return match;
      var external = /^https?:\/\//i.test(url);
      var attrs = external ? ' target="_blank" rel="noopener noreferrer"' : '';
      return '<a href="' + escapeHtml(url) + '"' + attrs + '>' + label + (external ? ' ↗' : '') + '</a>';
    });
    return html;
  }

  function makeButton(label, title, onClick, extraClass) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'editor-icon-btn' + (extraClass ? ' ' + extraClass : '');
    btn.textContent = label;
    btn.title = title;
    btn.setAttribute('aria-label', title);
    btn.addEventListener('click', onClick);
    return btn;
  }

  // — Estado -> campo oculto + vista previa + autoguardado (debounced) —

  var previewTimer = null;
  var autosaveTimer = null;

  function syncBlocksJson() {
    blocksJsonInput.value = JSON.stringify(state.blocks);
  }

  function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(updatePreview, 200);
  }

  function scheduleAutosave() {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(saveDraft, 1200);
  }

  function onStateChanged() {
    syncBlocksJson();
    schedulePreview();
    scheduleAutosave();
  }

  // — Vista previa —

  function renderBlockPreview(block) {
    switch (block.type) {
      case 'heading':
        return '<h2 class="block-heading">' + renderInlineMarkup(block.text) + '</h2>';
      case 'paragraph':
        return '<p class="block-paragraph">' + renderInlineMarkup(block.text) + '</p>';
      case 'highlight':
        return '<p class="block-highlight">' + renderInlineMarkup(block.text) + '</p>';
      case 'quote':
        return '<blockquote class="block-quote">' + renderInlineMarkup(block.text) + '</blockquote>';
      case 'list': {
        var tag = block.style === 'ordered' ? 'ol' : 'ul';
        var items = (block.items || [])
          .filter(function (item) { return item && item.trim() !== ''; })
          .map(function (item) { return '<li>' + renderInlineMarkup(item) + '</li>'; })
          .join('');
        return items ? '<' + tag + ' class="block-list">' + items + '</' + tag + '>' : '';
      }
      case 'divider':
        return '<hr class="block-divider">';
      case 'link': {
        if (!block.text || !isSafeUrl(block.url)) return '';
        var external = /^https?:\/\//i.test(block.url);
        var attrs = external ? ' target="_blank" rel="noopener noreferrer"' : '';
        return '<p class="block-link"><a href="' + escapeHtml(block.url) + '"' + attrs + '>' + escapeHtml(block.text) + (external ? ' ↗' : '') + '</a></p>';
      }
      case 'image': {
        if (!isSafeUrl(block.url)) return '';
        var html = '<figure class="block-image"><img src="' + escapeHtml(block.url) + '" alt="' + escapeHtml(block.alt || '') + '" loading="lazy">';
        if (block.caption) html += '<figcaption>' + renderInlineMarkup(block.caption) + '</figcaption>';
        return html + '</figure>';
      }
      default:
        return '';
    }
  }

  function updatePreview() {
    previewTitle.textContent = titleInput.value.trim() || 'Reflexión pendiente';
    previewTheme.textContent = themeInput.value.trim();
    previewTheme.hidden = themeInput.value.trim() === '';

    var html = state.blocks.map(renderBlockPreview).join('');
    previewBlocks.innerHTML = html || '<p class="editor-preview-empty">Todavía no hay contenido. Agregá un bloque para verlo acá.</p>';
  }

  // — Borrador local (recuperación, no es la publicación real) —

  var DRAFT_KEY = 'diario-editor-draft-week-' + week;

  function saveDraft() {
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify({
        savedAt: Date.now(),
        title: titleInput.value,
        theme: themeInput.value,
        blocks: state.blocks
      }));
      var d = new Date();
      var hh = String(d.getHours()).padStart(2, '0');
      var mm = String(d.getMinutes()).padStart(2, '0');
      draftStatus.textContent = 'Borrador local guardado a las ' + hh + ':' + mm + ' (todavía no publicado).';
    } catch (err) {
      /* localStorage no disponible (modo privado, cuota llena) — sin autoguardado, sin drama */
    }
  }

  function clearDraft() {
    try { localStorage.removeItem(DRAFT_KEY); } catch (err) {}
  }

  function checkForExistingDraft() {
    var raw;
    try { raw = localStorage.getItem(DRAFT_KEY); } catch (err) { return; }
    if (!raw) return;

    var draft;
    try { draft = JSON.parse(raw); } catch (err) { return; }
    if (!draft || !Array.isArray(draft.blocks)) return;

    var when = draft.savedAt ? new Date(draft.savedAt) : null;
    var whenText = when ? when.toLocaleString('es-CR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' }) : 'hace un tiempo';
    draftBannerText.textContent = 'Tenés un borrador local sin publicar, guardado el ' + whenText + '.';
    draftBanner.hidden = false;

    document.getElementById('draft-restore').addEventListener('click', function () {
      titleInput.value = draft.title || '';
      themeInput.value = draft.theme || '';
      state.blocks = draft.blocks;
      renderBlocks();
      onStateChanged();
      draftBanner.hidden = true;
    });

    document.getElementById('draft-discard').addEventListener('click', function () {
      clearDraft();
      draftBanner.hidden = true;
    });
  }

  // — Bloques de texto simple (heading/paragraph/highlight/quote) —

  function makeToolbar(textarea) {
    var bar = document.createElement('div');
    bar.className = 'editor-toolbar';
    bar.setAttribute('role', 'toolbar');
    bar.setAttribute('aria-label', 'Formato de texto');

    function wrap(before, after, placeholder) {
      var start = textarea.selectionStart;
      var end = textarea.selectionEnd;
      var value = textarea.value;
      var selected = value.slice(start, end) || placeholder;
      textarea.value = value.slice(0, start) + before + selected + after + value.slice(end);
      var cursor = start + before.length;
      textarea.focus();
      textarea.setSelectionRange(cursor, cursor + selected.length);
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    bar.appendChild(makeButton('B', 'Negrita', function () { wrap('**', '**', 'texto'); }, 'editor-toolbar-btn'));
    bar.appendChild(makeButton('I', 'Cursiva', function () { wrap('*', '*', 'texto'); }, 'editor-toolbar-btn editor-toolbar-btn--italic'));
    bar.appendChild(makeButton('◆', 'Destacado', function () { wrap('==', '==', 'texto'); }, 'editor-toolbar-btn'));
    bar.appendChild(makeButton('🔗', 'Enlace', function () { wrap('[', '](https://)', 'texto'); }, 'editor-toolbar-btn'));

    return bar;
  }

  function makeTextInputBlock(block, index) {
    var wrapper = document.createElement('div');

    var textarea = document.createElement('textarea');
    textarea.className = 'editor-block-textarea editor-block-textarea--' + block.type;
    textarea.value = block.text || '';
    textarea.rows = block.type === 'heading' ? 1 : 4;
    textarea.maxLength = 4000;
    textarea.placeholder = block.type === 'heading' ? 'Subtítulo de sección…' : 'Escribí acá…';

    wrapper.appendChild(makeToolbar(textarea));
    wrapper.appendChild(textarea);

    textarea.addEventListener('input', function () {
      state.blocks[index].text = textarea.value;
      onStateChanged();
    });

    return wrapper;
  }

  // — Bloque lista —

  function makeListBlock(block, index) {
    var wrapper = document.createElement('div');
    wrapper.className = 'editor-list-editor';

    var styleRow = document.createElement('div');
    styleRow.className = 'editor-list-style';
    ['unordered', 'ordered'].forEach(function (styleValue) {
      var id = 'list-style-' + index + '-' + styleValue;
      var label = document.createElement('label');
      label.className = 'editor-list-style__option';
      var radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'list-style-' + index;
      radio.id = id;
      radio.checked = (block.style || 'unordered') === styleValue;
      radio.addEventListener('change', function () {
        state.blocks[index].style = styleValue;
        onStateChanged();
      });
      label.appendChild(radio);
      label.append(styleValue === 'ordered' ? ' Numerada' : ' Con viñetas');
      styleRow.appendChild(label);
    });
    wrapper.appendChild(styleRow);

    var itemsContainer = document.createElement('div');
    itemsContainer.className = 'editor-list-items';

    var items = block.items && block.items.length ? block.items : [''];
    items.forEach(function (item, itemIndex) {
      itemsContainer.appendChild(makeListItemRow(block, index, item, itemIndex));
    });
    wrapper.appendChild(itemsContainer);

    var addItemBtn = document.createElement('button');
    addItemBtn.type = 'button';
    addItemBtn.className = 'editor-list-add';
    addItemBtn.textContent = '+ Agregar elemento';
    addItemBtn.addEventListener('click', function () {
      state.blocks[index].items = state.blocks[index].items || [];
      state.blocks[index].items.push('');
      renderBlocks();
      onStateChanged();
    });
    wrapper.appendChild(addItemBtn);

    return wrapper;
  }

  function makeListItemRow(block, blockIndex, item, itemIndex) {
    var row = document.createElement('div');
    row.className = 'editor-list-item';

    var input = document.createElement('input');
    input.type = 'text';
    input.value = item;
    input.maxLength = 500;
    input.placeholder = 'Elemento de la lista…';
    input.addEventListener('input', function () {
      state.blocks[blockIndex].items[itemIndex] = input.value;
      onStateChanged();
    });
    row.appendChild(input);

    row.appendChild(makeButton('✕', 'Quitar elemento', function () {
      state.blocks[blockIndex].items.splice(itemIndex, 1);
      if (state.blocks[blockIndex].items.length === 0) state.blocks[blockIndex].items.push('');
      renderBlocks();
      onStateChanged();
    }, 'editor-list-item__remove'));

    return row;
  }

  // — Bloque enlace —

  function makeLinkBlock(block, index) {
    var wrapper = document.createElement('div');
    wrapper.className = 'editor-inline-fields';

    var textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.placeholder = 'Texto del enlace';
    textInput.maxLength = 300;
    textInput.value = block.text || '';
    textInput.addEventListener('input', function () {
      state.blocks[index].text = textInput.value;
      onStateChanged();
    });

    var urlInput = document.createElement('input');
    urlInput.type = 'url';
    urlInput.placeholder = 'https://…';
    urlInput.value = block.url || '';
    urlInput.addEventListener('input', function () {
      state.blocks[index].url = urlInput.value;
      onStateChanged();
    });

    wrapper.appendChild(labeledField('Texto', textInput));
    wrapper.appendChild(labeledField('URL', urlInput));
    return wrapper;
  }

  // — Bloque imagen —

  function makeImageBlock(block, index) {
    var wrapper = document.createElement('div');
    wrapper.className = 'editor-inline-fields';

    var urlInput = document.createElement('input');
    urlInput.type = 'url';
    urlInput.placeholder = 'https://…';
    urlInput.value = block.url || '';
    urlInput.addEventListener('input', function () {
      state.blocks[index].url = urlInput.value;
      onStateChanged();
    });

    var altInput = document.createElement('input');
    altInput.type = 'text';
    altInput.placeholder = 'Descripción breve (para accesibilidad)';
    altInput.maxLength = 300;
    altInput.value = block.alt || '';
    altInput.addEventListener('input', function () {
      state.blocks[index].alt = altInput.value;
      onStateChanged();
    });

    var captionInput = document.createElement('input');
    captionInput.type = 'text';
    captionInput.placeholder = 'Pie de foto (opcional)';
    captionInput.maxLength = 300;
    captionInput.value = block.caption || '';
    captionInput.addEventListener('input', function () {
      state.blocks[index].caption = captionInput.value;
      onStateChanged();
    });

    wrapper.appendChild(labeledField('URL de la imagen', urlInput));
    wrapper.appendChild(labeledField('Texto alternativo', altInput));
    wrapper.appendChild(labeledField('Pie de foto', captionInput));
    return wrapper;
  }

  function labeledField(labelText, input) {
    var field = document.createElement('label');
    field.className = 'editor-inline-field';
    var span = document.createElement('span');
    span.textContent = labelText;
    field.appendChild(span);
    field.appendChild(input);
    return field;
  }

  // — Ensamblado de una tarjeta de bloque —

  function createBlockCard(block, index, total) {
    var card = document.createElement('div');
    card.className = 'editor-block';

    var header = document.createElement('div');
    header.className = 'editor-block__header';

    var label = document.createElement('span');
    label.className = 'editor-block__label';
    label.textContent = BLOCK_LABELS[block.type] || block.type;
    header.appendChild(label);

    var controls = document.createElement('div');
    controls.className = 'editor-block__controls';
    controls.appendChild(makeButton('↑', 'Mover arriba', function () { moveBlock(index, -1); }, index === 0 ? 'is-disabled' : ''));
    controls.appendChild(makeButton('↓', 'Mover abajo', function () { moveBlock(index, 1); }, index === total - 1 ? 'is-disabled' : ''));
    controls.appendChild(makeButton('⧉', 'Duplicar bloque', function () { duplicateBlock(index); }));
    controls.appendChild(makeButton('✕', 'Eliminar bloque', function () { deleteBlock(index); }, 'editor-block__remove'));
    header.appendChild(controls);
    card.appendChild(header);

    var body = document.createElement('div');
    body.className = 'editor-block__body';

    if (TEXT_TYPES.indexOf(block.type) !== -1) {
      body.appendChild(makeTextInputBlock(block, index));
    } else if (block.type === 'list') {
      body.appendChild(makeListBlock(block, index));
    } else if (block.type === 'link') {
      body.appendChild(makeLinkBlock(block, index));
    } else if (block.type === 'image') {
      body.appendChild(makeImageBlock(block, index));
    } else if (block.type === 'divider') {
      var hint = document.createElement('p');
      hint.className = 'editor-block__hint';
      hint.textContent = 'Línea divisoria — no necesita contenido.';
      body.appendChild(hint);
    }

    card.appendChild(body);
    return card;
  }

  // — Mutaciones estructurales (siempre seguidas de un re-render completo) —

  function defaultBlockFor(type) {
    switch (type) {
      case 'list': return { type: 'list', style: 'unordered', items: [''] };
      case 'divider': return { type: 'divider' };
      case 'link': return { type: 'link', text: '', url: '' };
      case 'image': return { type: 'image', url: '', alt: '', caption: '' };
      default: return { type: type, text: '' };
    }
  }

  function addBlock(type) {
    state.blocks.push(defaultBlockFor(type));
    renderBlocks();
    onStateChanged();
    var cards = blocksContainer.querySelectorAll('.editor-block');
    var last = cards[cards.length - 1];
    if (last) {
      var focusable = last.querySelector('textarea, input');
      if (focusable) focusable.focus();
      last.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function moveBlock(index, direction) {
    var target = index + direction;
    if (target < 0 || target >= state.blocks.length) return;
    var tmp = state.blocks[index];
    state.blocks[index] = state.blocks[target];
    state.blocks[target] = tmp;
    renderBlocks();
    onStateChanged();
  }

  function duplicateBlock(index) {
    var clone = JSON.parse(JSON.stringify(state.blocks[index]));
    state.blocks.splice(index + 1, 0, clone);
    renderBlocks();
    onStateChanged();
  }

  function deleteBlock(index) {
    state.blocks.splice(index, 1);
    renderBlocks();
    onStateChanged();
  }

  function renderBlocks() {
    blocksContainer.innerHTML = '';
    if (state.blocks.length === 0) {
      var empty = document.createElement('p');
      empty.className = 'editor-blocks-empty';
      empty.textContent = 'Todavía no hay bloques. Agregá el primero abajo.';
      blocksContainer.appendChild(empty);
      return;
    }
    state.blocks.forEach(function (block, index) {
      blocksContainer.appendChild(createBlockCard(block, index, state.blocks.length));
    });
  }

  // — Tabs Editar / Vista previa (móvil; en escritorio se ven ambos vía CSS) —

  function selectTab(name) {
    var editing = name === 'edit';
    tabEdit.setAttribute('aria-selected', String(editing));
    tabPreview.setAttribute('aria-selected', String(!editing));
    paneEdit.hidden = !editing;
    panePreview.hidden = editing;
    if (!editing) updatePreview();
  }

  tabEdit.addEventListener('click', function () { selectTab('edit'); });
  tabPreview.addEventListener('click', function () { selectTab('preview'); });

  // — Agregar bloque —

  document.querySelectorAll('[data-add-block]').forEach(function (btn) {
    btn.addEventListener('click', function () { addBlock(btn.dataset.addBlock); });
  });

  // — Título/tema también disparan vista previa + autoguardado —

  titleInput.addEventListener('input', onStateChanged);
  themeInput.addEventListener('input', onStateChanged);

  // — Confirmación honesta de publicación (sin token de Vercel: se
  //    sondea la propia página pública hasta ver el contenido nuevo) —

  function pollForPublication(marker) {
    if (!marker) {
      saveStatus.textContent = 'Guardado. Vercel está actualizando el sitio — puede tardar hasta un minuto.';
      return;
    }

    saveStatus.textContent = 'Guardado en GitHub. Actualizando el sitio…';
    var attempts = 0;
    var maxAttempts = 25;

    var poll = setInterval(function () {
      attempts++;
      fetch('/semana/' + week + '?_check=' + Date.now(), { cache: 'no-store' })
        .then(function (response) { return response.text(); })
        .then(function (html) {
          if (html.indexOf(marker) !== -1) {
            clearInterval(poll);
            saveStatus.textContent = 'Publicado ✓ — ya se ve en /semana/' + week + '.';
          } else if (attempts >= maxAttempts) {
            clearInterval(poll);
            saveStatus.textContent = 'Guardado en GitHub. El sitio puede tardar un poco más en actualizarse — revisá /semana/' + week + ' en un momento.';
          }
        })
        .catch(function () {
          if (attempts >= maxAttempts) {
            clearInterval(poll);
            saveStatus.textContent = 'Guardado en GitHub. No se pudo confirmar la publicación automáticamente.';
          }
        });
    }, 3000);
  }

  form.addEventListener('submit', function () {
    saveButton.disabled = true;
    saveButton.textContent = 'Guardando…';
  });

  // — Arranque —

  renderBlocks();
  updatePreview();
  checkForExistingDraft();

  if (initial.saveSuccess) {
    clearDraft();
    pollForPublication(initial.publishCheckMarker);
  }
})();
