/**
 * Enterprise Moto — region-map-frontend.js
 *
 * Motor de navegación vanilla del bloque «Mapa interactivo de regiones» (#44).
 * Opera sobre el SVG que render.php emite inline (#43): país → región → provincia,
 * con carga progresiva de un SVG por nivel y "zoom" por viewBox.
 *
 * NO reutiliza el motor OpenLayers (§13.19) ni map-frontend.js: es un motor propio.
 * Fases:
 *   C1 (hecho): bootstrap — data-maps-base + region-codes.json + mapa país→fichero.
 *   C2 (esta):  hover — resalte + globo con el nombre (atributo name del <path>).
 *   C3/C4:      drill-down + zoom viewBox / icono volver.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
  'use strict';

  /* País → fichero SVG por nivel. Los nombres de fichero no siguen regla
     (es-regiones/es-provincias vs it-reg/it-prov vs pt-prov): es una cuestión de
     presentación y vive con el motor, NO en region-codes.json (§2/§3.1 de #44). */
  var COUNTRY_FILES = {
    ES: { 2: 'es-regiones.svg', 3: 'es-provincias.svg' },
    IT: { 2: 'it-reg.svg',      3: 'it-prov.svg'        },
    FR: { 2: 'fr-reg.svg',      3: 'fr-prov.svg'        },
    PT: { collapsed: 'pt-prov.svg' },
    AD: { collapsed: 'ad-prov.svg' }
  };

  /* ═══════════════════════════════════════════
     NAVEGABILIDAD
     Un <path> es navegable en el nivel actual sii:
       nivel 1 → su id es una clave de region-codes.json.countries (ES/IT/FR/PT/AD);
       nivel 2 → cualquier región del país (ES/IT/FR).
     Todo lo demás es inerte en #44 (los 39 países restantes no reaccionan). El
     resalte/globo del nivel-3 (hojas) llega con el drill-down (#C3).
  ═══════════════════════════════════════════ */
  function isNavigable(pathEl, state) {
    if (!pathEl || !state.codes) return false;
    var id = pathEl.getAttribute('id');
    if (!id) return false;
    if (state.level === 1) {
      return !!(state.codes.countries && state.codes.countries[id]);
    }
    if (state.level === 2) {
      return true;
    }
    return false;
  }

  /* Marca con .ent-navigable los <path> navegables del SVG actual (afordancia:
     cursor pointer + resalte por CSS). Se re-ejecuta al cambiar de nivel (#C3). */
  function markNavigable(state) {
    var svg = state.container.querySelector('svg');
    if (!svg) return;
    var paths = svg.querySelectorAll('path');
    for (var i = 0; i < paths.length; i++) {
      if (isNavigable(paths[i], state)) {
        paths[i].classList.add('ent-navigable');
      } else {
        paths[i].classList.remove('ent-navigable');
      }
    }
  }

  /* ═══════════════════════════════════════════
     GLOBO (solo nombre)
  ═══════════════════════════════════════════ */
  function ensureBalloon(state) {
    if (state.balloon) return state.balloon;
    var b = document.createElement('div');
    b.className = 'ent-region-map__balloon';
    b.style.display = 'none';
    document.body.appendChild(b);
    state.balloon = b;
    return b;
  }

  function showBalloon(state, name, x, y) {
    var b = ensureBalloon(state);
    b.textContent = name;
    b.style.display = 'block';
    moveBalloon(state, x, y);
  }

  function moveBalloon(state, x, y) {
    if (!state.balloon || state.balloon.style.display === 'none') return;
    state.balloon.style.left = (x + 14) + 'px';
    state.balloon.style.top  = (y + 14) + 'px';
  }

  function hideBalloon(state) {
    if (state.balloon) state.balloon.style.display = 'none';
  }

  /* ═══════════════════════════════════════════
     HOVER (delegación de eventos en el contenedor)
  ═══════════════════════════════════════════ */
  function pathFromEvent(state, e) {
    var t = e.target;
    if (!t || !t.closest) return null;
    var p = t.closest('path');
    if (!p || !state.container.contains(p)) return null;
    return p;
  }

  function bindHover(state) {
    var c = state.container;

    c.addEventListener('mouseover', function (e) {
      var p = pathFromEvent(state, e);
      if (!p || !isNavigable(p, state)) return;
      showBalloon(state, p.getAttribute('name') || '', e.clientX, e.clientY);
    });

    c.addEventListener('mousemove', function (e) {
      moveBalloon(state, e.clientX, e.clientY);
    });

    c.addEventListener('mouseout', function (e) {
      var p = pathFromEvent(state, e);
      if (!p || !isNavigable(p, state)) return;
      /* Ignora los movimientos internos al mismo <path> (hacia un hijo). */
      if (e.relatedTarget && p.contains(e.relatedTarget)) return;
      hideBalloon(state);
    });
  }

  /* ═══════════════════════════════════════════
     BOOTSTRAP
  ═══════════════════════════════════════════ */
  function initContainer(container) {
    var mapsBase = container.dataset.mapsBase || '';
    if (!mapsBase) return;

    var state = {
      container: container,
      mapsBase: mapsBase,
      files: COUNTRY_FILES,
      codes: null,     // region-codes.json (navegabilidad + profundidad)
      level: 1,        // nivel actual (1 = Europa, ya inline en el DOM por #43)
      balloon: null
    };
    container._entRegionMap = state;

    /* La delegación se ata ya; los handlers no hacen nada hasta que hay codes
       (isNavigable devuelve false mientras state.codes es null). */
    bindHover(state);

    fetch(mapsBase + 'region-codes.json', { cache: 'force-cache' })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (codes) {
        state.codes = codes;
        markNavigable(state);
      })
      .catch(function (err) {
        if (window.console && console.warn) {
          console.warn('[region-map] no se pudo cargar region-codes.json:', err);
        }
      });
  }

  function init() {
    var containers = document.querySelectorAll('.ent-region-map');
    for (var i = 0; i < containers.length; i++) {
      initContainer(containers[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
