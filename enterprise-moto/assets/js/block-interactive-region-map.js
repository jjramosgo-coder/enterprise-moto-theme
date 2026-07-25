/**
 * Enterprise Moto — block-interactive-region-map.js
 * Bloque Gutenberg "Mapa interactivo de regiones" (nivel-1, Europa).
 * Fase #43 (esqueleto): el editor muestra un placeholder estático; el mapa se
 * renderiza en el front (blocks/interactive-region-map/render.php, SVG inline).
 * Sin interactividad (#44) ni personalización de editor (#47).
 * Vanilla JS, sin herramientas de compilación.
 */
(function () {
  'use strict';

  if ( ! window.wp || ! wp.blocks || ! wp.element ) return;

  var el            = wp.element.createElement;
  var useBlockProps = wp.blockEditor.useBlockProps;

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M15 19l-6-2.11V5l6 2.11V19zM20.5 3c-.05 0-.1.01-.15.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5.05 0 .1-.01.15-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5z',
      fill:'currentColor' })
  );

  /* ── Registro del bloque ── */
  wp.blocks.registerBlockType('enterprise/interactive-region-map', {

    title:       'Mapa interactivo de regiones',
    description: 'Mapa coroplético SVG de regiones (nivel-1, Europa). El mapa se renderiza en la entrada publicada.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','map','regiones','europa','svg','coropletico'],
    supports:    { html:false, align:['wide','full'] },

    /* Placeholder estático en el editor: el mapa real se pinta en el front. */
    edit: function () {
      var blockProps = useBlockProps({ className:'ent-region-map-editor' });
      return el('div', blockProps,
        el('div', {
          style: {
            padding:'24px',
            border:'1px dashed #c3c4c7',
            borderRadius:'4px',
            background:'#f6f7f7',
            textAlign:'center',
            color:'#50575e'
          }
        },
          el('strong', { style:{ display:'block', fontSize:'15px', marginBottom:'6px', color:'#1e1e1e' } },
            'Mapa interactivo de regiones'),
          el('span', { style:{ fontSize:'13px' } },
            'El mapa (Europa, nivel-1) se renderiza en la entrada publicada.')
        )
      );
    },

    save: function () { return null; }
  });
})();
