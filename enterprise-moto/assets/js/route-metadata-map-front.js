/**
 * Enterprise Moto — route-metadata-map-front.js
 * Tooltips «info» del espejo de estadísticas (#56, Fase 2 de #45).
 * Contrato §3.10: accesible y táctil — se muestra por hover y por foco de teclado,
 * se fija (pin) al tocar/click, se cierra con Esc, blur o click fuera. Sin :hover CSS.
 */
(function () {
  'use strict';

  function init() {
    var buttons = document.querySelectorAll('.ent-rmm-info');
    if (!buttons.length) return;

    var tips = [];

    function closeAll(except) {
      tips.forEach(function (t) {
        if (t !== except) { t.hidden = true; t.dataset.pinned = ''; }
      });
    }

    buttons.forEach(function (btn) {
      var tip = document.getElementById(btn.getAttribute('aria-describedby'));
      if (!tip) return;
      tips.push(tip);

      function show() { closeAll(tip); tip.hidden = false; }
      function hideIfNotPinned() { if (tip.dataset.pinned !== '1') tip.hidden = true; }

      btn.addEventListener('mouseenter', show);
      btn.addEventListener('mouseleave', hideIfNotPinned);
      btn.addEventListener('focus', show);
      btn.addEventListener('blur', hideIfNotPinned);
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (tip.hidden || tip.dataset.pinned !== '1') {
          closeAll(tip);
          tip.hidden = false;
          tip.dataset.pinned = '1';
        } else {
          tip.hidden = true;
          tip.dataset.pinned = '';
        }
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAll(null);
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.ent-rmm-info-wrap')) closeAll(null);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
