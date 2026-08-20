/**
 * Mejora progresiva del foro de comentarios en /semana/{n}. Solo se
 * carga cuando la semana tiene reflexión publicada (ver layout.php,
 * $loadComments). El formulario funciona igual sin JS — es un POST
 * normal y la respuesta ya incluye el comentario recién guardado —
 * este archivo solo agrega la misma confirmación honesta de
 * publicación que ya tiene el editor privado (ver editor.js,
 * pollForPublication): nunca asume que "guardado en GitHub" significa
 * "ya se ve en el sitio público".
 */
(function () {
  'use strict';

  var dataEl = document.getElementById('comments-initial-data');
  if (!dataEl) return;

  var initial = JSON.parse(dataEl.textContent);
  var week = initial.week;

  var form = document.getElementById('comment-form');
  var submitBtn = document.getElementById('comment-submit');

  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Publicando…';
    });
  }

  if (!initial.success || !initial.marker) return;

  var status = document.getElementById('comment-status');
  if (!status) return;

  var attempts = 0;
  var maxAttempts = 25;

  var poll = setInterval(function () {
    attempts++;
    fetch('/semana/' + week + '?_check=' + Date.now(), { cache: 'no-store' })
      .then(function (response) { return response.text(); })
      .then(function (html) {
        if (html.indexOf('data-comment-id="' + initial.marker + '"') !== -1) {
          clearInterval(poll);
          status.textContent = 'Comentario publicado ✓';
        } else if (attempts >= maxAttempts) {
          clearInterval(poll);
          status.textContent = 'Comentario guardado. El sitio puede tardar un poco más en actualizarse.';
        }
      })
      .catch(function () {
        if (attempts >= maxAttempts) {
          clearInterval(poll);
          status.textContent = 'Comentario guardado. No se pudo confirmar la publicación automáticamente.';
        }
      });
  }, 3000);
})();
