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
 *   C2 (hecho): hover — resalte + globo con el nombre (atributo name del <path>).
 *   C3 (esta):  drill-down país→región→provincia + zoom por viewBox (rAF).
 *   C4:         icono volver.
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

  var ZOOM_MS  = 420;   // duración de la animación de viewBox
  var ZOOM_PAD = 0.08;  // margen alrededor del bbox de la región (fracción)

  /* ═══════════════════════════════════════════
     NAVEGABILIDAD
     - Clicable (drill): nivel-1 → país en region-codes.json.countries (ES/IT/FR/PT/AD);
       nivel-2 no colapsado (ES/IT/FR) → cualquier región. Nivel-3 y sub-nivel colapsado
       (PT/AD) son HOJAS: su click es no-op en #44 (el redirect es #46).
     - Hoverable (globo con nombre): las hojas también muestran globo, aunque no sean
       clicables. En nivel-1 solo los 5 países navegables; el resto de Europa es inerte.
  ═══════════════════════════════════════════ */
  function isClickable(pathEl, state) {
    if (!pathEl || !state.codes) return false;
    var id = pathEl.getAttribute('id');
    if (!id) return false;
    if (state.level === 1) {
      return !!(state.codes.countries && state.codes.countries[id]);
    }
    if (state.level === 2 && !state.collapsed) {
      return true;
    }
    return false;
  }

  function isHoverable(pathEl, state) {
    if (!pathEl || !state.codes) return false;
    var id = pathEl.getAttribute('id');
    if (!id) return false;
    if (state.level === 1) {
      return !!(state.codes.countries && state.codes.countries[id]);
    }
    return true; // nivel 2 (regiones) y nivel 3 / colapsado (hojas): muestran nombre
  }

  /* Marca con .ent-navigable los <path> CLICABLES del SVG actual (afordancia:
     cursor pointer + resalte por CSS). Se re-ejecuta al cambiar de nivel. */
  function markNavigable(state) {
    var svg = currentSvg(state);
    if (!svg) return;
    var paths = svg.querySelectorAll('path');
    for (var i = 0; i < paths.length; i++) {
      if (isClickable(paths[i], state)) {
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
     SVG: acceso, carga (con caché) e inyección
  ═══════════════════════════════════════════ */
  function currentSvg(state) {
    return state.container.querySelector('svg');
  }

  function warnLoad(err) {
    if (window.console && console.warn) {
      console.warn('[region-map]', err);
    }
  }

  /* fetch con caché por fichero, para no re-pedir al reentrar en un país. */
  function loadSvg(state, filename) {
    if (state.svgCache[filename]) {
      return Promise.resolve(state.svgCache[filename]);
    }
    return fetch(state.mapsBase + filename, { cache: 'force-cache' })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status + ' en ' + filename);
        return r.text();
      })
      .then(function (txt) {
        state.svgCache[filename] = txt;
        return txt;
      });
  }

  /* Convierte el markup en un nodo <svg> importado al documento (DOMParser en
     modo image/svg+xml: evita problemas de namespace de innerHTML). */
  function parseSvg(markup) {
    var doc = new DOMParser().parseFromString(markup, 'image/svg+xml');
    if (doc.getElementsByTagName('parsererror').length) {
      warnLoad(new Error('parsererror al parsear SVG'));
      return null;
    }
    return document.importNode(doc.documentElement, true);
  }

  /* Guarda el SVG saliente (nodo desacoplado) para poder volver sin re-fetch (#C4). */
  function pushHistory(state, svgNode) {
    state.stack.push({
      svg: svgNode,
      level: state.level,
      collapsed: state.collapsed,
      country: state.country
    });
  }

  /* Reemplaza el SVG actual por newSvg, empujando el saliente al historial. */
  function swapSvg(state, newSvg) {
    var old = currentSvg(state);
    pushHistory(state, old);
    if (old && old.parentNode) old.parentNode.removeChild(old);
    state.container.appendChild(newSvg);
  }

  /* ═══════════════════════════════════════════
     ZOOM por viewBox (rAF; viewBox no es transicionable por CSS de forma fiable)
  ═══════════════════════════════════════════ */
  function parseViewBox(svg) {
    var vb = svg.getAttribute('viewBox');
    if (!vb) return null;
    var p = vb.split(/[\s,]+/).map(Number);
    if (p.length !== 4 || p.some(isNaN)) return null;
    return p;
  }

  function easeInOut(t) {
    return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
  }

  function animateViewBox(svg, from, to, duration) {
    var start = null;
    function step(ts) {
      if (start === null) start = ts;
      var t = duration > 0 ? Math.min((ts - start) / duration, 1) : 1;
      var e = easeInOut(t);
      var vb = [
        from[0] + (to[0] - from[0]) * e,
        from[1] + (to[1] - from[1]) * e,
        from[2] + (to[2] - from[2]) * e,
        from[3] + (to[3] - from[3]) * e
      ];
      svg.setAttribute('viewBox', vb.join(' '));
      if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* Dado el bbox de la región, calcula un viewBox de destino que lo contiene con
     margen y CONSERVA la relación de aspecto del lienzo del país. Al mantener el
     aspecto, el alto del <svg> (height:auto) no cambia y el efecto se lee como un
     zoom limpio "dentro" de la región, sin que la caja se redimensione. */
  function zoomViewBox(fromVB, bbox) {
    var canvasAspect = fromVB[2] / fromVB[3];
    var pad = Math.max(bbox.width, bbox.height) * ZOOM_PAD;
    var w = bbox.width + 2 * pad;
    var h = bbox.height + 2 * pad;
    if (w / h > canvasAspect) { h = w / canvasAspect; } else { w = h * canvasAspect; }
    var cx = bbox.x + bbox.width / 2;
    var cy = bbox.y + bbox.height / 2;
    return [cx - w / 2, cy - h / 2, w, h];
  }

  /* ═══════════════════════════════════════════
     DRILL-DOWN
  ═══════════════════════════════════════════ */
  function afterInject(state, level, collapsed, country) {
    state.level = level;
    state.collapsed = collapsed;
    state.country = country;
    state.container.setAttribute('data-map-level', String(level));
    hideBalloon(state);
    markNavigable(state);
  }

  /* Nivel-1: click en país. ES/IT/FR → sus regiones (nivel-2); PT/AD → su único
     sub-nivel colapsado. Es un SWAP (europe.svg no comparte lienzo/proyección con
     el SVG del país, §1.4): sin morph de viewBox en este paso. */
  function drillCountry(state, countryId) {
    var entry = COUNTRY_FILES[countryId];
    if (!entry) return;
    var collapsed = !!entry.collapsed;
    var filename  = collapsed ? entry.collapsed : entry[2];

    state.loading = true;
    loadSvg(state, filename).then(function (markup) {
      var svg = parseSvg(markup);
      if (!svg) { state.loading = false; return; }
      swapSvg(state, svg);
      afterInject(state, 2, collapsed, countryId);
      state.loading = false;
    }).catch(function (err) { warnLoad(err); state.loading = false; });
  }

  /* Nivel-2: click en región (ES/IT/FR). El bbox se calcula sobre el SVG de nivel-2
     ACTUAL; como nivel-2 y nivel-3 comparten lienzo (§1.4), ese bbox es válido en el
     SVG de provincias, así que el zoom recorta a la región sin filtrar provincias. */
  function drillRegion(state, regionPath) {
    var entry = COUNTRY_FILES[state.country];
    if (!entry || !entry[3]) return;

    var bbox;
    try { bbox = regionPath.getBBox(); } catch (e) { return; }

    state.loading = true;
    loadSvg(state, entry[3]).then(function (markup) {
      var svg = parseSvg(markup);
      if (!svg) { state.loading = false; return; }
      swapSvg(state, svg);
      afterInject(state, 3, false, state.country);

      var fromVB = parseViewBox(svg);
      if (fromVB) {
        animateViewBox(svg, fromVB, zoomViewBox(fromVB, bbox), ZOOM_MS);
      }
      state.loading = false;
    }).catch(function (err) { warnLoad(err); state.loading = false; });
  }

  /* ═══════════════════════════════════════════
     EVENTOS (delegación en el contenedor estable → sirve a cualquier SVG inyectado)
  ═══════════════════════════════════════════ */
  function pathFromEvent(state, e) {
    var t = e.target;
    if (!t || !t.closest) return null;
    var p = t.closest('path');
    if (!p || !state.container.contains(p)) return null;
    return p;
  }

  function bindEvents(state) {
    var c = state.container;

    c.addEventListener('mouseover', function (e) {
      var p = pathFromEvent(state, e);
      if (!p || !isHoverable(p, state)) return;
      showBalloon(state, p.getAttribute('name') || '', e.clientX, e.clientY);
    });

    c.addEventListener('mousemove', function (e) {
      moveBalloon(state, e.clientX, e.clientY);
    });

    c.addEventListener('mouseout', function (e) {
      var p = pathFromEvent(state, e);
      if (!p || !isHoverable(p, state)) return;
      if (e.relatedTarget && p.contains(e.relatedTarget)) return;
      hideBalloon(state);
    });

    c.addEventListener('click', function (e) {
      if (state.loading) return;
      var p = pathFromEvent(state, e);
      if (!p || !isClickable(p, state)) return; // hojas: no-op (#46)
      if (state.level === 1) {
        drillCountry(state, p.getAttribute('id'));
      } else if (state.level === 2 && !state.collapsed) {
        drillRegion(state, p);
      }
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
      codes: null,       // region-codes.json (navegabilidad + profundidad)
      level: 1,          // 1 = Europa (inline por #43)
      collapsed: false,  // true en el sub-nivel único de PT/AD
      country: null,     // país en el que se ha entrado (para nivel-2→3)
      balloon: null,
      stack: [],         // historial de SVG salientes (para #C4 volver)
      svgCache: {},      // fichero → markup (evita re-fetch al reentrar)
      loading: false
    };
    container._entRegionMap = state;

    bindEvents(state); // delegación en el contenedor estable; inerte hasta tener codes

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
        warnLoad('no se pudo cargar region-codes.json: ' + err);
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
