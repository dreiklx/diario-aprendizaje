/**
 * JavaScript mínimo y deliberado: sólo navegación por teclado entre
 * semanas. Todo lo demás en el sitio funciona sin JavaScript.
 */
(function () {
  'use strict';

  var pager = document.querySelector('.week__pager');
  if (!pager) return;

  var prevLink = pager.querySelector('[data-pager="prev"]');
  var nextLink = pager.querySelector('[data-pager="next"]');

  document.addEventListener('keydown', function (event) {
    var target = event.target;
    var isTyping = target instanceof HTMLElement &&
      (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

    if (isTyping || event.metaKey || event.ctrlKey || event.altKey) return;

    if (event.key === 'ArrowLeft' && prevLink) {
      window.location.href = prevLink.getAttribute('href');
    } else if (event.key === 'ArrowRight' && nextLink) {
      window.location.href = nextLink.getAttribute('href');
    }
  });
})();
