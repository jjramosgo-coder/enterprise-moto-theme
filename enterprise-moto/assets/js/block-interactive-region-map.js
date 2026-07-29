/**
 * Enterprise Moto — block-interactive-region-map.js
 * Bloque Gutenberg "Mapa interactivo de regiones" (nivel-1, Europa).
 * #43 (esqueleto): el editor muestra un placeholder estático; el mapa se
 * renderiza en el front (blocks/interactive-region-map/render.php, SVG inline).
 * #54: controles de color del editor — paleta global por nivel (juego cerrado),
 * grosores/opacidades por nivel, acento hover y preajuste del botón «volver».
 * La navegación es #44; la personalización por región es #47.
 * Vanilla JS, sin herramientas de compilación.
 */
(function () {
  'use strict';

  if ( ! window.wp || ! wp.blocks || ! wp.element ) return;

  var el   = wp.element.createElement;
  var Frag = wp.element.Fragment;
  var be   = wp.blockEditor;
  var co   = wp.components;

  var useBlockProps     = be.useBlockProps;
  var InspectorControls = be.InspectorControls;
  var PanelBody         = co.PanelBody;
  var SelectControl     = co.SelectControl;
  var RangeControl      = co.RangeControl;
  var ColorPalette      = co.ColorPalette;

  /* ── Paletas cerradas (#54, §3.1 del requisito). Cada ColorPalette se alimenta
     SOLO con estos swatches y con disableCustomColors: no hay color libre. ── */
  var EDITORIAL_SWATCHES = [
    { name: 'Dorado',        color: '#f2c118' },
    { name: 'Dorado oscuro', color: '#c9a010' },
    { name: 'Negro',         color: '#0e0e0e' },
    { name: 'Negro suave',   color: '#1a1a1a' },
    { name: 'Gris borde',    color: '#2a2a2a' },
    { name: 'Gris',          color: '#3a3a3a' },
    { name: 'Gris medio',    color: '#5a5a5a' },
    { name: 'Crema',         color: '#f5f5f2' },
    { name: 'Blanco',        color: '#ffffff' },
  ];
  var ENTERPRISE_SWATCHES = [
    { name: 'Black Storm',             color: '#1B1C1E' },
    { name: 'Night Black',             color: '#121212' },
    { name: 'Granit Grey',             color: '#323436' },
    { name: 'Agate Grey',              color: '#4A4D50' },
    { name: 'Plata (White Aluminium)', color: '#D1D5DB' },
    { name: 'Magnesium',               color: '#5B554D' },
    { name: 'Brembo Gold',             color: '#C5A059' },
    { name: 'Blanco',                  color: '#FFFFFF' },
  ];

  /* Asignación por paleta (§3.1). Al seleccionar una paleta se aplica en bloque
     a todos los colores (decisión del operador), manteniendo el invariante de
     «juego cerrado». Grosores y opacidades son idénticos en ambas → no se tocan. */
  var PALETTE_ASSIGN = {
    editorial: {
      landFill:'#1a1a1a', baseStroke:'#0e0e0e', countryStroke:'#f2c118',
      regionFill:'#2a2a2a', regionStroke:'#3a3a3a',
      provinceFill:'#5a5a5a', provinceStroke:'#2a2a2a', hoverAccent:'#c9a010',
    },
    enterprise: {
      landFill:'#1B1C1E', baseStroke:'#121212', countryStroke:'#C5A059',
      regionFill:'#323436', regionStroke:'#4A4D50',
      provinceFill:'#4A4D50', provinceStroke:'#5B554D', hoverAccent:'#C5A059',
    },
  };
  var PALETTE_LABELS = { editorial: 'Editorial', enterprise: 'Enterprise' };

  var LABEL_STYLE = {
    fontSize:11, fontWeight:700, letterSpacing:'.08em',
    textTransform:'uppercase', color:'#555', margin:'12px 0 6px'
  };

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

    /* Deben coincidir con los 'attributes' del register_block_type de PHP
       (functions.php), como en el bloque de referencia markdown-styled. */
    attributes: {
      colorSource:     { type:'string', default:'asset'     },
      palette:         { type:'string', default:'editorial' },
      landFill:        { type:'string', default:'#1a1a1a'   },
      baseStroke:      { type:'string', default:'#0e0e0e'   },
      countryStroke:   { type:'string', default:'#f2c118'   },
      regionFill:      { type:'string', default:'#2a2a2a'   },
      regionStroke:    { type:'string', default:'#3a3a3a'   },
      provinceFill:    { type:'string', default:'#5a5a5a'   },
      provinceStroke:  { type:'string', default:'#2a2a2a'   },
      hoverAccent:     { type:'string', default:'#c9a010'   },
      baseStrokeWidth: { type:'number', default:0.5 },
      t0StrokeWidth:   { type:'number', default:0.8 },
      t1StrokeWidth:   { type:'number', default:0.5 },
      t2StrokeWidth:   { type:'number', default:0.3 },
      baseOpacity:     { type:'number', default:1 },
      t0Opacity:       { type:'number', default:1 },
      t1Opacity:       { type:'number', default:1 },
      t2Opacity:       { type:'number', default:1 },
      backCanvas:      { type:'string', default:'light' },
    },

    edit: function (props) {
      var a   = props.attributes;
      var set = props.setAttributes;
      var isTheme  = a.colorSource === 'theme';
      var swatches = a.palette === 'enterprise' ? ENTERPRISE_SWATCHES : EDITORIAL_SWATCHES;

      /* Un ColorPalette limitado a la paleta activa, sin color libre. */
      function colorField(label, attrName) {
        return el(Frag, { key: attrName },
          el('p', { style: LABEL_STYLE }, label),
          el(ColorPalette, {
            colors: swatches,
            value: a[attrName],
            onChange: function (v) { var o = {}; o[attrName] = v || a[attrName]; set(o); },
            disableCustomColors: true,
            clearable: false,
          })
        );
      }

      function rangeField(label, attrName, min, max, step) {
        return el(RangeControl, {
          key: attrName,
          label: label,
          value: a[attrName],
          min: min, max: max, step: step,
          onChange: function (v) { var o = {}; o[attrName] = v; set(o); },
        });
      }

      var blockProps = useBlockProps({ className:'ent-region-map-editor' });

      return el(Frag, null,

        el(InspectorControls, null,

          el(PanelBody, { title: 'Colores del mapa', initialOpen: true },
            el(SelectControl, {
              label: 'Colores del mapa',
              value: a.colorSource,
              options: [
                { label: 'Los del propio mapa', value: 'asset' },
                { label: 'Personalizar',        value: 'theme' },
              ],
              onChange: function (v) { set({ colorSource: v }); },
            }),
            isTheme && el(SelectControl, {
              label: 'Paleta',
              value: a.palette,
              options: [
                { label: 'Editorial',  value: 'editorial'  },
                { label: 'Enterprise', value: 'enterprise' },
              ],
              onChange: function (v) {
                if (v === a.palette) return;
                var ok = window.confirm(
                  'Cambiar la paleta a «' + (PALETTE_LABELS[v] || v) + '» reasignará ' +
                  'todos los colores del mapa a los de esa paleta. Si has cambiado ' +
                  'colores y no has guardado la entrada, esos cambios se perderán. ¿Continuar?'
                );
                if (!ok) return;
                set(Object.assign({ palette: v }, PALETTE_ASSIGN[v]));
              },
            })
          ),

          isTheme && el(PanelBody, { title: 'Colores por nivel', initialOpen: true },
            colorField('Relleno de las tierras',    'landFill'),
            colorField('Borde base',                 'baseStroke'),
            colorField('Borde de los países',        'countryStroke'),
            colorField('Relleno de las regiones',    'regionFill'),
            colorField('Borde de las regiones',      'regionStroke'),
            colorField('Relleno de las provincias',  'provinceFill'),
            colorField('Borde de las provincias',    'provinceStroke'),
            colorField('Resalte al pasar el ratón',  'hoverAccent')
          ),

          isTheme && el(PanelBody, { title: 'Grosor y opacidad', initialOpen: false },
            rangeField('Grosor de línea (base)',       'baseStrokeWidth', 0, 3, 0.1),
            rangeField('Grosor de línea (países)',     't0StrokeWidth',   0, 3, 0.1),
            rangeField('Grosor de línea (regiones)',   't1StrokeWidth',   0, 3, 0.1),
            rangeField('Grosor de línea (provincias)', 't2StrokeWidth',   0, 3, 0.1),
            rangeField('Opacidad (base)',              'baseOpacity',     0, 1, 0.05),
            rangeField('Opacidad (países)',            't0Opacity',       0, 1, 0.05),
            rangeField('Opacidad (regiones)',          't1Opacity',       0, 1, 0.05),
            rangeField('Opacidad (provincias)',        't2Opacity',       0, 1, 0.05)
          ),

          isTheme && el(PanelBody, { title: 'Botón volver', initialOpen: false },
            el(SelectControl, {
              label: 'Botón volver',
              value: a.backCanvas,
              options: [
                { label: 'sobre lienzo claro',  value: 'light' },
                { label: 'sobre lienzo oscuro', value: 'dark'  },
              ],
              onChange: function (v) { set({ backCanvas: v }); },
            })
          )
        ),

        el('div', blockProps,
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
        )
      );
    },

    save: function () { return null; }
  });
})();
