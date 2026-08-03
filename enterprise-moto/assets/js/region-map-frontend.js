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
 * ni map-regions-global.json). Un nodo es ENFOCABLE por nivel administrativo
 * (Commit 7, §8): país (admin-0) y región (admin-1) son enfocables; la provincia
 * (admin-2) es la hoja, y su clic es no-op aquí (el redirect a entradas filtradas
 * es #46). Al enfocar, si el nodo tiene hijos se revela su capa interior; si no
 * los tiene (región TERMINAL: distritos PT, parroquias AD, Ceuta/Melilla) se
 * enfoca a sí mismo (zoom y queda activo), sin revelar un sub-nivel inexistente.
 *
 * NO reutiliza el motor OpenLayers (§13.19) ni map-frontend.js: es un motor propio.
 * Commits de #51 (sub-fase 3):
 *   C1 (hecho): bootstrap + modelo de visibilidad (ent-active/ent-dimmed/ent-hidden).
 *   C2 (hecho): hover con globo bilingüe.
 *   C3 (hecho): drill + zoom de viewBox + vecinos atenuados + revelar tier.
 *   C4 (hecho): icono volver (aleja un nivel).
 *   C5 (hecho): vecinos atenuados clicables (salto directo) — §7, rediseño de la
 *               navegación de vecinos: ent-dimmed pasa a visible + CLICABLE + con
 *               globo en todos los niveles; clic en cualquier unidad visible la
 *               reenfoca según su data-admin (país→sus regiones; región→sus
 *               provincias); la hoja (provincia) sigue siendo no-op (#46).
 *   C6 (hecho): saltos región↔región entre países (§7, opción b) — al enfocar una
 *               región, TODAS las demás regiones tier1 (incluidas las de otros
 *               países) quedan atenuadas y clicables, para saltar de región a
 *               región cruzando frontera sin «volver». tier0 sigue oculto a nivel
 *               región (las regiones ya representan a los países).
 *   C7 (este):  enfocar regiones TERMINALES (§8, opción b) — las 27 unidades admin-1
 *               sin sub-nivel (18 distritos PT, 7 parroquias AD, Ceuta/Melilla) eran
 *               no-op porque el motor solo actuaba si el path tenía hijos. Ahora la
 *               enfocabilidad es por nivel admin (país/región enfocables; provincia
 *               hoja) y focusCountry/focusRegion se ramifican: con hijos → revela la
 *               capa interior (comportamiento actual); sin hijos → zoom a la unidad
 *               y la deja ent-active (terminal), atenúa el resto y oculta las capas
 *               vacías. Las terminales pasan a llevar ent-navigable (cursor honesto).
 *               Están DUPLICADAS en tier2 con el mismo id y data-parent=país, así que
 *               getChildren(región)=0 (terminal) y el gemelo tier2 queda oculto: se
 *               opera siempre sobre la instancia de tier1.
 *
 * Copyright (C) 2026 Juanjo Ramos y María José Moreno
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
(function () {
  'use strict';

  /* Clases de estado por-path que gobiernan la visibilidad (las define el CSS):
       - ent-active:    visible e interactivo (unidad enfocada / capa revelada).
       - ent-dimmed:    visible, atenuado y CLICABLE (vecino: salto directo, §7).
       - ent-hidden:    oculto (display:none).
       - ent-navigable: afordancia (cursor pointer + resalte) sobre las unidades
         visibles —activas o atenuadas— que además tienen hijos (drilleables). */
  var CLS_ACTIVE = 'ent-active';
  var CLS_DIMMED = 'ent-dimmed';
  var CLS_HIDDEN = 'ent-hidden';
  var CLS_NAV    = 'ent-navigable';

  var ZOOM_MS  = 420;   // duración de la animación de viewBox
  var ZOOM_PAD = 0.08;  // margen alrededor del bbox enfocado (fracción)

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

  /* #46 (Commit 3) — Href de la página de destino por región para una pieza TERMINAL.
     Base = data-region-dest del contenedor (permalink de la Página-destino + region_src,
     emitido por render.php solo si está configurada); se le añade region=<id de la pieza>
     (= region_code). Cadena vacía si no hay base (sin Página-destino → globo sin enlace). */
  function buildRegionHref(state, pieceId) {
    var base = state.regionDestBase;
    if (!base || !pieceId) return '';
    return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'region=' + encodeURIComponent(pieceId);
  }

  /* Un nodo es ENFOCABLE por nivel administrativo, no por tener hijos (§8.2.1):
     país (admin-0) y región (admin-1) son enfocables —una región terminal, sin
     hijos, también—; la provincia (admin-2) es la hoja (clic no-op → #46). Esta es
     la afordancia y el disparador del clic; que además revele o no una capa interior
     lo decide hasChildren dentro de focusCountry/focusRegion. */
  function isFocusable(pathEl) {
    var admin = pathEl.getAttribute('data-admin');
    return admin === '0' || admin === '1';
  }

  /* País (tier0) de un id de país. */
  function getCountry(state, countryId) {
    if (!countryId) return null;
    return state.svg.querySelector('#tier0 [id="' + countryId + '"]');
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
    if (isFocusable(pathEl)) {
      pathEl.classList.add(CLS_NAV);
    }
  }

  /* Vecino atenuado: visible y clicable (§7). Si es enfocable (país o región, incluida
     una terminal) recibe además la afordancia de navegable (cursor/resalte), §8.2.3;
     una provincia (hoja) no la recibe: su clic es no-op (#46). */
  function setDimmed(state, pathEl) {
    clearVisibility(pathEl);
    pathEl.classList.add(CLS_DIMMED);
    if (isFocusable(pathEl)) {
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
    /* #46 (Commit 3) — nº de entradas de la pieza (data-count), avance de #48. */
    var cnt = document.createElement('span');
    cnt.className = 'ent-region-map__balloon-count';
    cnt.style.display = 'none';
    b.appendChild(main);
    b.appendChild(sub);
    b.appendChild(cnt);
    document.body.appendChild(b);
    /* #46 (Commit 3) — en modo enlace (terminal con data-count>0) el globo es clicable y
       navega a la página de destino por región; en el resto lleva pointer-events:none (CSS)
       y este handler no dispara (balloonHref vacío). */
    b.addEventListener('click', function () {
      if (state.balloonHref) { window.location.href = state.balloonHref; }
    });
    state.balloon = b;
    state.balloonMain = main;
    state.balloonSub = sub;
    state.balloonCount = cnt;
    return b;
  }

  /* Muestra el globo de una PIEZA (path): nombre bilingüe + nº de entradas (data-count).
     #46 (Commit 3): si la pieza es TERMINAL (sin hijos) y su data-count>0, el globo entra en
     modo ENLACE — se ancla (deja de seguir al cursor) y se hace clicable hacia la página de
     destino por región—; en cualquier otro caso conserva el comportamiento previo (sigue al
     cursor, pointer-events:none, no enlaza). Todos los llamadores tienen el path en la mano. */
  function showBalloon(state, pathEl, x, y) {
    ensureBalloon(state);
    var parsed = parseName(pathEl.getAttribute('data-name') || '');
    state.balloonMain.textContent = parsed.primary;
    if (parsed.secondary) {
      state.balloonSub.textContent = parsed.secondary;
      state.balloonSub.style.display = 'block';
    } else {
      state.balloonSub.textContent = '';
      state.balloonSub.style.display = 'none';
    }

    /* nº de entradas: se pinta solo si hay data-count>0 (el «0» y la ausencia de término no
       se muestran en este avance; el globo completo con etiqueta/estados es #48). */
    var countAttr = pathEl.getAttribute('data-count');
    var count = (countAttr === null || countAttr === '') ? NaN : parseInt(countAttr, 10);
    if (!isNaN(count) && count > 0) {
      state.balloonCount.textContent = String(count);
      state.balloonCount.style.display = 'block';
    } else {
      state.balloonCount.textContent = '';
      state.balloonCount.style.display = 'none';
    }

    /* Modo enlace: terminal (sin hijos) + data-count>0 + base configurada. */
    var linkable = !hasChildren(state, pathEl) && !isNaN(count) && count > 0;
    var href = linkable ? buildRegionHref(state, pathEl.getAttribute('id')) : '';
    if (href) {
      state.balloonHref     = href;
      state.balloonAnchored = true;                 // deja de seguir al cursor
      state.balloon.classList.add('is-link');
      state.balloon.style.pointerEvents = 'auto';   // clicable
      state.balloon.style.cursor        = 'pointer';
    } else {
      state.balloonHref     = '';
      state.balloonAnchored = false;
      state.balloon.classList.remove('is-link');
      state.balloon.style.pointerEvents = 'none';
      state.balloon.style.cursor        = '';
    }

    state.balloon.style.display = 'block';
    /* Posición inicial directa (moveBalloon respeta el anclaje y no reposicionaría). */
    state.balloon.style.left = (x + 14) + 'px';
    state.balloon.style.top  = (y + 14) + 'px';
  }

  function moveBalloon(state, x, y) {
    if (!state.balloon || state.balloon.style.display === 'none') return;
    if (state.balloonAnchored) return; // #46: globo-enlace anclado (terminal) no sigue al cursor
    state.balloon.style.left = (x + 14) + 'px';
    state.balloon.style.top  = (y + 14) + 'px';
  }

  function hideBalloon(state) {
    if (state.balloon) state.balloon.style.display = 'none';
    state.balloonAnchored = false; // #46: al ocultar se sale del modo enlace/anclaje
    state.balloonHref     = '';
  }

  /* Reconcilia el globo al terminar cada zoom (#64), sin exigir movimiento del ratón.
     Dos fuentes, por este orden:
       1) Pieza enfocada conocida (state.pendingBalloonPath): un drill a una TERMINAL
          enfoca una pieza concreta que el motor ya tiene en la mano; su nombre es el que
          hay que mostrar. NO se hace hit-test: tras el zoom el mismo píxel de pantalla cae
          sobre otra coordenada SVG (el vecino atenuado que asoma en el margen del encuadre),
          y consultarlo daría el nombre equivocado.
       2) Sin pieza enfocada única (volver / Europa): sí se mira qué hay bajo el último
          puntero con elementFromPoint → apuntable → su data-name; si no → ocultar. El globo
          lleva pointer-events:none (CSS), así que devuelve el path de debajo, no el globo.
     El globo se ancla en la última posición del puntero/toque (ptrX/ptrY). */
  function reconcileBalloonAtPointer(state) {
    var focused = state.pendingBalloonPath;
    state.pendingBalloonPath = null; // consumido: solo aplica a este zoom
    var x = state.ptrX, y = state.ptrY;
    if (x == null || y == null) return;
    if (focused) {
      showBalloon(state, focused, x, y);
      return;
    }
    var el = document.elementFromPoint(x, y);
    var p  = el && el.closest ? el.closest('path') : null;
    if (p && state.svg.contains(p) && isHoverable(p)) {
      showBalloon(state, p, x, y);
    } else {
      hideBalloon(state);
    }
  }

  /* Hoverable = path visible (activo O atenuado): el globo con el nombre aparece
     también sobre los vecinos atenuados (§7). Solo los ocultos (ent-hidden) no se
     pueden apuntar (display:none). */
  function isHoverable(pathEl) {
    return pathEl.classList.contains(CLS_ACTIVE) ||
           pathEl.classList.contains(CLS_DIMMED);
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

  function animateViewBox(svg, from, to, duration, onDone) {
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
      if (t < 1) {
        requestAnimationFrame(step);
      } else if (onDone) {
        onDone();
      }
    }
    requestAnimationFrame(step);
  }

  /* Dado un bbox objetivo, calcula un viewBox que lo contiene con margen y
     CONSERVA la relación de aspecto del lienzo (800×502). Al mantener el aspecto,
     el alto del <svg> (height:auto) no cambia y el efecto se lee como un zoom
     limpio, sin que la caja se redimensione. */
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

  /* getBBox seguro: devuelve {x,y,width,height} o null (elemento no medible). */
  function bboxOf(el) {
    var b;
    try { b = el.getBBox(); } catch (e) { return null; }
    if (!b || (b.width === 0 && b.height === 0)) return null;
    return { x: b.x, y: b.y, width: b.width, height: b.height };
  }

  function unionRect(a, b) {
    var x1 = Math.min(a.x, b.x);
    var y1 = Math.min(a.y, b.y);
    var x2 = Math.max(a.x + a.width, b.x + b.width);
    var y2 = Math.max(a.y + a.height, b.y + b.height);
    return { x: x1, y: y1, width: x2 - x1, height: y2 - y1 };
  }

  /* Bbox de encuadre de un padre = unión de los bbox de sus hijos (ya visibles).
     Los hijos deben estar revelados (sin display:none) para que getBBox devuelva
     medidas válidas. */
  function childrenBBox(state, parentId) {
    var kids = getChildren(state, parentId);
    var box = null;
    for (var i = 0; i < kids.length; i++) {
      var b = bboxOf(kids[i]);
      if (!b) continue;
      box = box ? unionRect(box, b) : b;
    }
    return box;
  }

  /* Anima el viewBox actual → toVB (array [x,y,w,h]). */
  function animateVB(state, toVB) {
    hideBalloon(state);
    if (!toVB) return;
    var fromVB = parseViewBox(state.svg);
    if (!fromVB) return;
    state.animating = true;
    animateViewBox(state.svg, fromVB, toVB, ZOOM_MS, function () {
      state.animating = false;
      reconcileBalloonAtPointer(state); // #64: devuelve el globo bajo el puntero al acabar el zoom
    });
  }

  /* Anima el viewBox para encuadrar un bbox (con margen y aspecto del lienzo). */
  function animateToBBox(state, bbox) {
    if (!bbox) { hideBalloon(state); return; }
    var fromVB = parseViewBox(state.svg);
    if (!fromVB) { hideBalloon(state); return; }
    animateVB(state, zoomViewBox(fromVB, bbox));
  }

  /* ═══════════════════════════════════════════
     ENFOQUE (reasignación de clases + zoom; nunca swap: es un único SVG)
  ═══════════════════════════════════════════ */
  /* Nivel-0: Europa completa. Restablece visibilidad de países y aleja el viewBox
     al lienzo maestro; oculta el control «volver» (quita .is-drilled). */
  function focusEurope(state) {
    applyLevel0(state);
    state.focus = null;
    state.level = 0;
    state.container.classList.remove('is-drilled');
    animateVB(state, state.baseVB.slice());
  }

  /* Enfocar un país: si tiene regiones (los 5 del maestro actual) revela sus tier1
     hijas y encuadra su cuerpo principal (bbox de las hijas). Si NO
     tiene (país terminal admin-0: no ocurre hoy, pero sí en un maestro más amplio,
     §8.4) se enfoca a sí mismo —queda ent-active y se encuadra su propio bbox— sin
     revelar una capa vacía. En ambos casos atenúa los otros países (tier0) como
     vecinos y oculta las capas inferiores no reveladas. Rama genérica: un cambio de
     mapa no exige tocar código. */
  function focusCountry(state, country) {
    var countryId = country.getAttribute('id');
    var terminal = !hasChildren(state, country);
    var bbox = terminal ? bboxOf(country) : null; // país sin regiones: su propio bbox es el objetivo del zoom
    var list, i;

    list = state.svg.querySelectorAll('#tier0 path');
    for (i = 0; i < list.length; i++) {
      if (list[i] === country) {
        if (terminal) setActive(state, list[i]); // terminal: el país se queda activo
        else setHidden(list[i]);                 // con regiones: se oculta y se revelan sus tier1
      } else {
        setDimmed(state, list[i]);
      }
    }
    list = state.svg.querySelectorAll('#tier1 path');
    for (i = 0; i < list.length; i++) {
      if (!terminal && list[i].getAttribute('data-parent') === countryId) setActive(state, list[i]);
      else setHidden(list[i]);
    }
    list = state.svg.querySelectorAll('#tier2 path');
    for (i = 0; i < list.length; i++) setHidden(list[i]);

    state.focus = country;
    state.level = 1;
    state.container.classList.add('is-drilled');
    // #64: si el país es terminal se enfoca una pieza única → su nombre es el del globo;
    // si revela sus regiones no hay pieza única (el reconcile cae al hit-test).
    state.pendingBalloonPath = terminal ? country : null;

    if (terminal) animateToBBox(state, bbox);
    else animateToBBox(state, childrenBBox(state, countryId));
  }

  /* Enfocar una región. Ramifica según tenga hijos (§8.2.2):
       · CON hijos → oculta la región y revela sus provincias (tier2 hijas) activas
         (comportamiento de §7).
       · SIN hijos (región TERMINAL: distrito PT, parroquia AD, Ceuta/Melilla) → deja
         la propia región ent-active y no revela un tier2 inexistente.
     En ambos casos atenúa TODAS las demás regiones tier1 —incluidas las de otros
     países— dejándolas clicables, para saltar de región a región cruzando frontera
     sin «volver» (§7, opción b). tier0 sigue oculto a nivel región (las regiones ya
     representan a los países; mostrar ambos duplicaría el dibujo). El bbox se captura
     de la instancia de tier1 (aún visible), no del gemelo de tier2 —que queda oculto—,
     evitando capturar la instancia equivocada o dibujar dos veces (§8.4). */
  function focusRegion(state, region) {
    var regionId = region.getAttribute('id');
    var terminal = !hasChildren(state, region);
    var bbox = bboxOf(region); // instancia tier1 aún visible: su bbox es el objetivo del zoom
    var list, i;

    list = state.svg.querySelectorAll('#tier0 path');
    for (i = 0; i < list.length; i++) setHidden(list[i]);
    list = state.svg.querySelectorAll('#tier1 path');
    for (i = 0; i < list.length; i++) {
      if (list[i] === region) {
        if (terminal) setActive(state, list[i]); // terminal: la región enfocada se queda activa
        else setHidden(list[i]);                 // con hijos: se oculta y se revelan sus provincias
      } else {
        setDimmed(state, list[i]);
      }
    }
    list = state.svg.querySelectorAll('#tier2 path');
    for (i = 0; i < list.length; i++) {
      if (!terminal && list[i].getAttribute('data-parent') === regionId) setActive(state, list[i]);
      else setHidden(list[i]); // terminal: todo tier2 oculto (incluido el gemelo de la región)
    }

    state.focus = region;
    state.level = 2;
    state.container.classList.add('is-drilled');
    // #64: región terminal → pieza única enfocada, su nombre es el del globo; región con
    // provincias → destapa un sub-nivel (varias piezas), el reconcile cae al hit-test.
    state.pendingBalloonPath = terminal ? region : null;

    animateToBBox(state, bbox);
  }

  function drill(state, pathEl) {
    var admin = pathEl.getAttribute('data-admin');
    if (admin === '0') focusCountry(state, pathEl);
    else if (admin === '1') focusRegion(state, pathEl);
    /* admin '2' (provincia) es hoja: el guard isFocusable del clic la descarta antes
       de llegar aquí (no-op → #46). */
  }

  /* Sube un nivel: región → su país (data-parent), país → Europa. El estado se
     deriva del DOM (no hay pila de historial); cada re-enfoque recalcula la
     visibilidad y anima el viewBox al encuadre del padre. */
  function goBack(state) {
    if (state.animating) return;
    if (state.level === 2 && state.focus) {
      var country = getCountry(state, state.focus.getAttribute('data-parent'));
      if (country) focusCountry(state, country);
      else focusEurope(state);
    } else if (state.level === 1) {
      focusEurope(state);
    }
    /* nivel-0: no-op (el control está oculto). */
  }

  /* ═══════════════════════════════════════════
     CONTROL «VOLVER»
  ═══════════════════════════════════════════ */
  /* <button> con un SVG inline (flecha de retorno curva), sin texto visible,
     arriba-izquierda. Su visibilidad la gobierna el CSS por la clase .is-drilled
     del contenedor (oculto en nivel-0). */
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
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      goBack(state);
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
      showBalloon(state, p, e.clientX, e.clientY);
    });

    c.addEventListener('mousemove', function (e) {
      state.ptrX = e.clientX; state.ptrY = e.clientY; // #64: última posición para el reconcile
      moveBalloon(state, e.clientX, e.clientY);
    });

    c.addEventListener('mouseout', function (e) {
      // #46: un globo-enlace anclado (terminal con data-count>0) persiste al salir de la
      // pieza, para poder llevar el cursor hasta él y pulsarlo; se reemplaza al entrar en
      // otra pieza (mouseover) o se cierra al clicar mar/vacío.
      if (state.balloonAnchored) return;
      var p = pathFromEvent(state, e);
      if (!p || !isHoverable(p)) return;
      if (e.relatedTarget && p.contains(e.relatedTarget)) return;
      hideBalloon(state);
    });

    /* Clic delegado: reenfoca cualquier unidad VISIBLE ENFOCABLE —activa o atenuada
       (§7)—. Enfocable = país (admin-0) o región (admin-1), tenga hijos o no (§8): un
       vecino atenuado es un salto directo (clic en país vecino entra en él; clic en
       región hermana salta a ella, aunque sea terminal). drill() despacha por
       data-admin (país→focusCountry; región→focusRegion, que ya ramifica terminal).
       La provincia (admin-2) es la hoja: no-op — su redirect es #46. */
    c.addEventListener('click', function (e) {
      if (state.animating) return;
      var p = pathFromEvent(state, e);
      state.ptrX = e.clientX; state.ptrY = e.clientY; // #64: el click fingido táctil trae las coords del toque
      if (!p || !isHoverable(p)) { hideBalloon(state); return; } // toque en mar/vacío o pieza oculta: apaga el globo
      if (!isFocusable(p)) return;   // hoja (provincia): no-op (#46), el globo persiste
      drill(state, p);
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
      baseVB: parseViewBox(svg) || [0, 0, 800, 502], // viewBox maestro (Europa)
      focus: null,      // null = Europa (nivel-0)
      level: 0,
      animating: false,
      ptrX: null,       // última posición conocida del puntero/toque (coords de viewport)
      ptrY: null,       // null hasta el primer evento; la usa reconcileBalloonAtPointer (#64)
      pendingBalloonPath: null, // #64: pieza enfocada por el drill (terminal); su nombre es el
                                // que debe mostrar el globo al acabar el zoom, sin hit-test
      regionDestBase: container.getAttribute('data-region-dest') || '', // #46: base del enlace del globo
      balloonAnchored: false,   // #46: globo en modo enlace (terminal) → no sigue al cursor
      balloonHref: '',          // #46: destino del clic del globo cuando está en modo enlace
      backButton: null,
      balloon: null,
      balloonMain: null,
      balloonSub: null,
      balloonCount: null        // #46: elemento del nº de entradas (data-count) en el globo
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
