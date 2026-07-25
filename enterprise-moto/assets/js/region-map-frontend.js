/**
 * Enterprise Moto — region-map-frontend.js
 *
 * Motor de navegación vanilla del bloque «Mapa interactivo de regiones» (#44).
 * Opera sobre el SVG que render.php emite inline (#43): país → región → provincia,
 * con carga progresiva de un SVG por nivel y "zoom" por viewBox.
 *
 * NO reutiliza el motor OpenLayers (§13.19) ni map-frontend.js: es un motor propio.
 * Fase actual (Commit 1): bootstrap — leer data-maps-base, cargar region-codes.json
 * (fuente de verdad de navegabilidad, #42) y sostener el mapa país→fichero. Sin
 * interacción todavía (hover #C2, drill-down/zoom #C3, icono volver #C4).
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

  /* Inicializa un contenedor .ent-region-map: guarda su estado y carga la
     estructura de navegabilidad (region-codes.json) desde data-maps-base. */
  function initContainer(container) {
    var mapsBase = container.dataset.mapsBase || '';
    if (!mapsBase) return;

    /* Estado por contenedor. Los niveles 2-3 se cargarán bajo demanda en #C3;
       aquí solo se prepara el andamiaje y se retiene el mapa de ficheros. */
    var state = {
      container: container,
      mapsBase: mapsBase,
      files: COUNTRY_FILES,
      codes: null,   // contenido de region-codes.json (navegabilidad + profundidad)
      level: 1       // nivel actual (1 = Europa, ya inline en el DOM por #43)
    };
    container._entRegionMap = state;

    fetch(mapsBase + 'region-codes.json', { cache: 'force-cache' })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (codes) {
        state.codes = codes;
      })
      .catch(function (err) {
        /* Fallo de carga: el mapa de nivel-1 sigue visible e inerte; la
           interacción (fases siguientes) simplemente no se activará. */
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
