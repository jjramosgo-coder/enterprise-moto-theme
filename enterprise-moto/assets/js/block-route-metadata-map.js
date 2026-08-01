/**
 * Enterprise Moto — block-route-metadata-map.js
 * Bloque «Mapa de ruta con metadatos» (enterprise/route-metadata-map) — editor Gutenberg.
 * Dibuja la ruta planificada (opcional) y la registrada (track) como route-comparison,
 * y carga la capa de metadatos (métricas GPX + inventario geográfico).
 *
 * Commit 1 (#56 · Fase 2 del plan #45): esqueleto. edit = placeholder estático, save: null.
 * El modal de captura, la barra lateral y la vista previa llegan en el Commit 2.
 */
(function () {
  'use strict';

  if (!window.wp || !wp.blocks || !wp.element) return;

  var el   = wp.element.createElement;
  var be   = wp.blockEditor;

  var useBlockProps = be.useBlockProps;

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M3 11l4-7 4 4 4-6 4 5', stroke:'#001f5c', strokeWidth:2,
                 strokeLinecap:'round', strokeLinejoin:'round', fill:'none' }),
    el('path', { d:'M3 15l4-5 4 3 4-4 4 3', stroke:'#c0392b', strokeWidth:2,
                 strokeLinecap:'round', strokeLinejoin:'round', fill:'none', strokeDasharray:'2 2' }),
    el('circle', { cx:19, cy:5, r:3, fill:'#f2c118' }),
    el('path', { d:'M19 4v.5M19 5.5v1.5', stroke:'#0e0e0e', strokeWidth:1,
                 strokeLinecap:'round', fill:'none' })
  );

  wp.blocks.registerBlockType('enterprise/route-metadata-map', {

    title:       'Mapa de ruta con metadatos',
    description: 'Dibuja la ruta planificada (opcional) y la registrada (track) sobre el mapa, con las métricas del fichero de metadatos y el inventario geográfico del mapa de regiones.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','ruta','gpx','track','metadatos','registrada','planificada','regiones'],
    supports:    { html: false, align: ['wide','full'] },

    attributes: {
      /* ── Datos del modal (Commit 2/3) ── */
      year:            { type:'string',  default:'' },
      month:           { type:'string',  default:'' },
      day:             { type:'string',  default:'' },
      trip:            { type:'string',  default:'' },
      validated:       { type:'boolean', default:false },
      useGeoInventory: { type:'boolean', default:true  },
      /* ── Presentación heredada de route-comparison (menos GPX-URL y stats manuales) ── */
      gpxLabel1:       { type:'string',  default:'GPX1 — Ruta planificada' },
      gpxLabel2:       { type:'string',  default:'GPX2 — Ruta realizada'   },
      heading:         { type:'string',  default:'' },
      description:     { type:'string',  default:'' },
      mapHeight:       { type:'string',  default:'md'      },
      routeColor:      { type:'string',  default:'#001f5c' },
      routeColor2:     { type:'string',  default:'#c0392b' },
      markerColor:     { type:'string',  default:'#f2c118' },
      routeWeight:     { type:'integer', default:4         },
      showElevation:   { type:'boolean', default:true      },
      showStats:       { type:'boolean', default:true      },
      startLabel:      { type:'string',  default:'' },
      endLabel:        { type:'string',  default:'' },
    },

    edit: function(props) {
      var bp = useBlockProps({ className:'ent-route-metadata-map-editor' });

      return el('div', bp,
        el('div', {
          style:{
            background:'#f5f5f2', border:'1px solid #ddd', minHeight:180,
            display:'flex', alignItems:'center', justifyContent:'center',
            flexDirection:'column', gap:12, padding:'24px 20px', textAlign:'center',
          },
        },
          el('span', { style:{ fontSize:36 } }, '🗺️'),
          el('p', {
            style:{ margin:0, fontSize:15, fontWeight:600, color:'#1e1e1e', lineHeight:1.5 },
          }, 'Rutas planificada y registrada (track) con metadatos')
        )
      );
    },

    save: function(){ return null; },
  });

})();
