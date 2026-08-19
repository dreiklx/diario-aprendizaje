/**
 * JavaScript pequeño y deliberado. Tres responsabilidades, nada más:
 * 1. Alternar tema claro/oscuro y persistirlo en localStorage.
 * 2. Revelar progresivamente el contenido marcado con .reveal al entrar
 *    al viewport (degrada con seguridad: sin JS, todo es visible).
 * 3. Navegación por teclado (← →) entre semanas.
 *
 * La inicialización del tema (antes del primer pintado, para evitar un
 * flash) vive en un script inline en api/templates/layout.php — este
 * archivo solo maneja la interacción posterior al clic.
 */
(function () {
  'use strict';

  // — 1. Toggle de tema —
  var toggle = document.getElementById('theme-toggle');
  if (toggle) {
    var updateLabel = function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      toggle.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
      toggle.setAttribute('aria-pressed', String(isDark));
    };

    updateLabel();

    toggle.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try {
        localStorage.setItem('theme', next);
      } catch (err) {}
      updateLabel();
    });
  }

  // — 2. Revelado progresivo —
  var revealTargets = document.querySelectorAll('.reveal');
  if (revealTargets.length && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
    );

    revealTargets.forEach(function (target) {
      observer.observe(target);
    });
  } else {
    revealTargets.forEach(function (target) {
      target.classList.add('is-visible');
    });
  }

  // — 3. Navegación por teclado entre semanas —
  var pager = document.querySelector('.week__pager');
  if (pager) {
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
  }
})();
