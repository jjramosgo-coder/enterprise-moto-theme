/**
 * Enterprise Moto — region-map-frontend.js
 *
 * Motor de navegación vanilla del bloque «Mapa interactivo de regiones»
 * (#51, replantea #44). Opera sobre un ÚNICO SVG maestro georreferenciado que
 * render.php emite inline (una sola proyección; nunca se cambia de fichero):
 * país → región → provincia por zoom de viewBox, con los vecinos atenuados
 * (no desaparecen) y las capas interiores reveladas al entrar.
 *
 * DOM-driven (Decisión H): navegabilidad, jerarquía y nombres salen de los
 * data-* del SVG en línea; el motor NO hace fetch de nada (ni region-codes.json
 * ni map-regions-global.json). Un nodo es «drilleable» si tiene hijos (paths con
 * data-parent == su id); sin hijos es una hoja (su clic es no-op aquí — el
 * redirect a entradas filtradas es #46).
 *
 * NO reutiliza el motor OpenLayers (§13.19) ni map-frontend.js: es un motor propio.
 * Commits de #51 (sub-fase 3):
 *   C1 (hecho): bootstrap + modelo de visibilidad (ent-active/ent-dimmed/ent-hidden).
 *   C2 (este):  hover con globo bilingüe.
 *   C3: drill + zoom de viewBox + vecinos atenuados + revelar tier.
 *   C4: icono volver (aleja un nivel).
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
  'use strict';

  /* Clases de estado por-path que gobiernan la visibilidad (las define el CSS):
       - ent-active:    visible e interactivo.
       - ent-dimmed:    visible pero atenuado y no interactivo (vecinos).
       - ent-hidden:    oculto (display:none).
       - ent-navigable: afordancia (cursor pointer + resalte) sobre los activos
         que además tienen hijos (drilleables). */
  var CLS_ACTIVE = 'ent-active';
  var CLS_DIMMED = 'ent-dimmed';
  var CLS_HIDDEN = 'ent-hidden';
  var CLS_NAV    = 'ent-navigable';

  /* ═══════════════════════════════════════════
     SVG MAESTRO Y JERARQUÍA (todo del DOM, Decisión H)
  ═══════════════════════════════════════════ */
  /* El SVG maestro es un HIJO DIRECTO del contenedor. Se busca así, y no con
     querySelector('svg'), para no capturar el <svg> del icono «volver» (que va
     anidado dentro del <button>). */
  function getMasterSvg(container) {
    var kids = container.children;
    for (var i = 0; i < kids.length; i++) {
      if (kids[i].tagName && kids[i].tagName.toLowerCase() === 'svg') {
        return kids[i];
      }
    }
    return null;
  }

  /* Hijos de un nodo = paths cuyo data-parent es su id. */
  function getChildren(state, id) {
    if (!id) return [];
    return state.svg.querySelectorAll('path[data-parent="' + id + '"]');
  }

  /* Un nodo es drilleable si tiene al menos un hijo en el SVG. */
  function hasChildren(state, pathEl) {
    var id = pathEl.getAttribute('id');
    return !!id && getChildren(state, id).length > 0;
  }

  /* ═══════════════════════════════════════════
     MODELO DE VISIBILIDAD
  ═══════════════════════════════════════════ */
  function clearVisibility(pathEl) {
    pathEl.classList.remove(CLS_ACTIVE, CLS_DIMMED, CLS_HIDDEN, CLS_NAV);
  }

  function setActive(state, pathEl) {
    clearVisibility(pathEl);
    pathEl.classList.add(CLS_ACTIVE);
    if (hasChildren(state, pathEl)) {
      pathEl.classList.add(CLS_NAV);
    }
  }

  function setHidden(pathEl) {
    clearVisibility(pathEl);
    pathEl.classList.add(CLS_HIDDEN);
  }

  /* Nivel-0 (Europa): tier0 (los 5 países) activos —navegables los que tienen
     hijos—; tier1 y tier2 ocultos. La gestión por-path del motor sustituye al
     ocultado estático de grupos (#tier1,#tier2) del CSS, que queda como fallback
     sin-JS mientras el contenedor no tenga la clase .is-ready. */
  function applyLevel0(state) {
    var t0 = state.svg.querySelectorAll('#tier0 path');
    var i;
    for (i = 0; i < t0.length; i++) setActive(state, t0[i]);
    var deeper = state.svg.querySelectorAll('#tier1 path, #tier2 path');
    for (i = 0; i < deeper.length; i++) setHidden(deeper[i]);
  }

  /* ═══════════════════════════════════════════
     GLOBO BILINGÜE (Decisión G)
     data-name viene como «Nativo [Español]». Se muestra el Español arriba y el
     Nativo en cursiva debajo; una sola línea si no hay corchete o si coinciden.
     (nombre solo; nº de entradas y descripción son #48.)
  ═══════════════════════════════════════════ */
  function parseName(raw) {
    var s = (raw || '').trim();
    if (!s) return { primary: '', secondary: '' };
    var m = s.match(/^(.*?)\s*\[(.*?)\]\s*$/);
    if (m) {
      var native  = m[1].trim(); // Nativo
      var spanish = m[2].trim(); // Español
      if (spanish && native && spanish !== native) {
        return { primary: spanish, secondary: native }; // Español arriba, Nativo debajo
      }
      return { primary: spanish || native, secondary: '' };
    }
    return { primary: s, secondary: '' };
  }

  function ensureBalloon(state) {
    if (state.balloon) return state.balloon;
    var b = document.createElement('div');
    b.className = 'ent-region-map__balloon';
    b.style.display = 'none';
    var main = document.createElement('span');
    main.className = 'ent-region-map__balloon-name';
    var sub = document.createElement('span');
    sub.className = 'ent-region-map__balloon-native';
    b.appendChild(main);
    b.appendChild(sub);
    document.body.appendChild(b);
    state.balloon = b;
    state.balloonMain = main;
    state.balloonSub = sub;
    return b;
  }

  function showBalloon(state, raw, x, y) {
    ensureBalloon(state);
    var parsed = parseName(raw);
    state.balloonMain.textContent = parsed.primary;
    if (parsed.secondary) {
      state.balloonSub.textContent = parsed.secondary;
      state.balloonSub.style.display = 'block';
    } else {
      state.balloonSub.textContent = '';
      state.balloonSub.style.display = 'none';
    }
    state.balloon.style.display = 'block';
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

  /* Hoverable = path visible e interactivo (activo o hoja revelada). Los vecinos
     atenuados (ent-dimmed) ya no reciben eventos por pointer-events:none, y los
     ocultos (ent-hidden) no se pueden apuntar. */
  function isHoverable(pathEl) {
    return pathEl.classList.contains(CLS_ACTIVE);
  }

  /* ═══════════════════════════════════════════
     CONTROL «VOLVER» (creado aquí; su comportamiento —subir un nivel— es C4)
  ═══════════════════════════════════════════ */
  /* <button> con un SVG inline (flecha de retorno curva), sin texto visible,
     arriba-izquierda. En C1/C2 queda oculto por CSS (solo visible con .is-drilled,
     que gobierna el motor en C4). */
  function createBackButton(state) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ent-region-map__back';
    btn.setAttribute('aria-label', 'Volver');
    btn.innerHTML =
      '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">' +
        '<path d="M9 6 L4 11 L9 16" fill="none" stroke="currentColor" stroke-width="2" ' +
          'stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M4 11 H14 a5 5 0 0 1 5 5 V19" fill="none" stroke="currentColor" ' +
          'stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
      '</svg>';
    /* El comportamiento (goBack) y el toggle de .is-drilled llegan en C4;
       stopPropagation ya evita que el clic escale al contenedor. */
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
    });
    state.container.appendChild(btn);
    state.backButton = btn;
  }

  /* ═══════════════════════════════════════════
     EVENTOS (delegación en el contenedor estable → un único SVG inline)
  ═══════════════════════════════════════════ */
  function pathFromEvent(state, e) {
    var t = e.target;
    if (!t || !t.closest) return null;
    var p = t.closest('path');
    if (!p || !state.svg.contains(p)) return null;
    return p;
  }

  function bindEvents(state) {
    var c = state.container;

    c.addEventListener('mouseover', function (e) {
      var p = pathFromEvent(state, e);
      if (!p || !isHoverable(p)) return;
      showBalloon(state, p.getAttribute('data-name') || '', e.clientX, e.clientY);
    });

    c.addEventListener('mousemove', function (e) {
      moveBalloon(state, e.clientX, e.clientY);
    });

    c.addEventListener('mouseout', function (e) {
      var p = pathFromEvent(state, e);
      if (!p || !isHoverable(p)) return;
      if (e.relatedTarget && p.contains(e.relatedTarget)) return;
      hideBalloon(state);
    });

    /* Clic delegado. En C2 solo se resuelve el path; el drill (zoom + revelar
       tier + atenuar vecinos) llega en C3 y las hojas son no-op (#46). */
    c.addEventListener('click', function (e) {
      var p = pathFromEvent(state, e);
      if (!p) return;
      /* Drill en C3. */
    });
  }

  /* ═══════════════════════════════════════════
     BOOTSTRAP
  ═══════════════════════════════════════════ */
  function initContainer(container) {
    var svg = getMasterSvg(container);
    if (!svg) return;

    var state = {
      container: container,
      svg: svg,
      focus: null, // null = Europa (nivel-0)
      level: 0,
      backButton: null,
      balloon: null,
      balloonMain: null,
      balloonSub: null
    };
    container._entRegionMap = state;

    /* Marca el contenedor: el CSS deja de aplicar el ocultado estático de
       #tier1/#tier2 y pasa a regir la visibilidad por-path del motor. */
    container.classList.add('is-ready');

    createBackButton(state);
    bindEvents(state);
    applyLevel0(state);
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
