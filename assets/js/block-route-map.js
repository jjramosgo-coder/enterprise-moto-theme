/**
 * Enterprise Moto — block-route-map.js v1.6.0
 * Bloque "Mapa de ruta" — editor Gutenberg.
 * Soporte de doble GPX + selector de color visual.
 */
(function () {
  'use strict';

  if (!window.wp || !wp.blocks || !wp.element) return;

  var el    = wp.element.createElement;
  var Frag  = wp.element.Fragment;
  var be    = wp.blockEditor;
  var co    = wp.components;

  var InspectorControls = be.InspectorControls;
  var MediaUpload       = be.MediaUpload;
  var MediaUploadCheck  = be.MediaUploadCheck;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var TextareaControl   = co.TextareaControl;
  var SelectControl     = co.SelectControl;
  var ToggleControl     = co.ToggleControl;
  var RangeControl      = co.RangeControl;
  var ColorPalette      = co.ColorPalette;
  var Button            = co.Button;

  var THEME_COLORS = [
    { name: 'Azul marino',  color: '#001f5c' },
    { name: 'Negro',        color: '#0e0e0e' },
    { name: 'Dorado',       color: '#f2c118' },
    { name: 'Rojo',         color: '#c0392b' },
    { name: 'Verde',        color: '#1a7a3a' },
    { name: 'Naranja',      color: '#e67e22' },
    { name: 'Gris',         color: '#5a5a5a' },
  ];

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M3 11l4-7 4 4 4-6 4 5', stroke:'currentColor', strokeWidth:2,
                 strokeLinecap:'round', strokeLinejoin:'round', fill:'none' }),
    el('circle',{ cx:3,  cy:11, r:1.5, fill:'currentColor' }),
    el('circle',{ cx:19, cy:7,  r:1.5, fill:'currentColor' })
  );

  /* ── Panel GPX (reutilizable para GPX 1 y GPX 2) ── */
  function GPXPanel(props) {
    var label      = props.label;
    var urlValue   = props.urlValue;
    var onUrlChange= props.onUrlChange;
    var color      = props.color;
    var onColor    = props.onColor;
    var colorLabel = props.colorLabel || 'Color de la ruta';
    var isOpen     = props.isOpen;
    var optional   = props.optional;

    return el(PanelBody, { title: label, initialOpen: isOpen },

      optional && el('p', { style:{ fontSize:11, color:'#888', marginBottom:12, lineHeight:1.5 } },
        'Opcional. Si se rellena, se superpone a la ruta principal en un color diferente.'
      ),

      /* Opción A: URL directa */
      el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginBottom:6 } },
        'URL del archivo GPX'
      ),
      el('p', { style:{ fontSize:11, color:'#888', marginBottom:8, lineHeight:1.5 } },
        'Sube el GPX por FTP y pega la URL. Esta opción siempre funciona.'
      ),
      el(TextControl, {
        label: '',
        value: urlValue,
        onChange: onUrlChange,
        placeholder: 'https://tusitioweb.com/wp-content/uploads/ruta.gpx',
        type: 'url',
        hideLabelFromVision: true,
      }),
      urlValue && el('p', { style:{ fontSize:11, color:'#0a7c3e', marginTop:4, marginBottom:12 } },
        '\u2713 ' + urlValue.split('/').pop()
      ),

      /* Separador */
      el('div', { style:{ borderTop:'1px solid #e0e0e0', margin:'4px 0 12px' } }),

      /* Selector de color */
      el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
        colorLabel
      ),
      el(ColorPalette, {
        colors:    THEME_COLORS,
        value:     color,
        onChange:  function(v){ onColor(v || '#001f5c'); },
        clearable: false,
      }),
      el('p', { style:{ fontSize:11, color:'#888', marginTop:4 } },
        'Seleccionado: ' + color
      )
    );
  }

  /* ── Registro del bloque ── */
  wp.blocks.registerBlockType('enterprise/route-map', {

    title:       'Mapa de ruta',
    description: 'Mapa OpenStreetMap con una o dos rutas GPX superpuestas.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','ruta','gpx','gps','recorrido','track','planificada'],
    supports:    { html: false, align: ['wide','full'] },

    attributes: {
      /* GPX 1 — ruta principal */
      gpxUrl:        { type:'string',  default:'' },
      gpxLabel1:     { type:'string',  default:'Ruta planificada' },
      routeColor:    { type:'string',  default:'#001f5c' },
      /* GPX 2 — segunda ruta (opcional) */
      gpxUrl2:       { type:'string',  default:'' },
      gpxLabel2:     { type:'string',  default:'Ruta GPS' },
      routeColor2:   { type:'string',  default:'#c0392b' },
      /* Configuración del mapa */
      heading:       { type:'string',  default:'' },
      mapHeight:     { type:'string',  default:'md' },
      routeWeight:   { type:'integer', default:4  },
      showElevation: { type:'boolean', default:true  },
      showStats:     { type:'boolean', default:true  },
      /* Etiquetas inicio/fin */
      startLabel:    { type:'string',  default:'' },
      endLabel:      { type:'string',  default:'' },
      /* Estadísticas manuales */
      statKm:        { type:'string',  default:'' },
      statDuration:  { type:'string',  default:'' },
      statElevGain:  { type:'string',  default:'' },
      description:   { type:'string',  default:'' },
    },

    edit: function(props) {
      var a   = props.attributes;
      var set = props.setAttributes;
      var bp  = useBlockProps({ className:'ent-route-editor' });
      var H   = { sm:180, md:260, lg:340, xl:420 };

      return el(Frag, null,

        el(InspectorControls, null,

          /* GPX 1 */
          el(GPXPanel, {
            label:      '\uD83D\uDDFA GPX 1 \u2014 Ruta principal',
            urlValue:   a.gpxUrl,
            onUrlChange:function(v){ set({ gpxUrl: v.trim() }); },
            color:      a.routeColor,
            onColor:    function(v){ set({ routeColor: v }); },
            colorLabel: 'Color de la ruta principal',
            isOpen:     true,
            optional:   false,
          }),

          /* GPX 2 */
          el(GPXPanel, {
            label:      '\uD83D\uDDFA GPX 2 \u2014 Segunda ruta (opcional)',
            urlValue:   a.gpxUrl2,
            onUrlChange:function(v){ set({ gpxUrl2: v.trim() }); },
            color:      a.routeColor2,
            onColor:    function(v){ set({ routeColor2: v }); },
            colorLabel: 'Color de la segunda ruta',
            isOpen:     false,
            optional:   true,
          }),

          /* Leyenda (cuando hay dos GPX) */
          a.gpxUrl && a.gpxUrl2 && el(PanelBody, { title:'Leyenda de rutas', initialOpen:false },
            el(TextControl, {
              label: 'Etiqueta ruta 1',
              value: a.gpxLabel1,
              onChange: function(v){ set({ gpxLabel1: v }); },
              placeholder: 'Ej: Ruta planificada',
            }),
            el(TextControl, {
              label: 'Etiqueta ruta 2',
              value: a.gpxLabel2,
              onChange: function(v){ set({ gpxLabel2: v }); },
              placeholder: 'Ej: Ruta GPS',
            })
          ),

          /* Configuración del mapa */
          el(PanelBody, { title:'Configuración del mapa', initialOpen:false },
            el(TextControl, {
              label: 'Título (opcional)',
              value: a.heading,
              onChange: function(v){ set({ heading: v }); },
              placeholder: 'Ej: Palermo \u2192 Erice · Día 4',
            }),
            el(SelectControl, {
              label: 'Altura del mapa',
              value: a.mapHeight,
              options: [
                {label:'Pequeño (320px)', value:'sm'},
                {label:'Mediano (480px)', value:'md'},
                {label:'Grande (640px)',  value:'lg'},
                {label:'Extra (800px)',   value:'xl'},
              ],
              onChange: function(v){ set({ mapHeight: v }); },
            }),
            el(RangeControl, {
              label: 'Grosor de la línea',
              value: a.routeWeight,
              min:1, max:8, step:1,
              onChange: function(v){ set({ routeWeight: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar perfil de elevación',
              checked: a.showElevation,
              onChange: function(v){ set({ showElevation: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar estadísticas',
              checked: a.showStats,
              onChange: function(v){ set({ showStats: v }); },
            })
          ),

          el(PanelBody, { title:'Marcadores inicio/fin', initialOpen:false },
            el(TextControl, {
              label:'Etiqueta del punto de inicio',
              value: a.startLabel,
              onChange: function(v){ set({ startLabel: v }); },
              placeholder:'Ej: Palermo',
            }),
            el(TextControl, {
              label:'Etiqueta del punto final',
              value: a.endLabel,
              onChange: function(v){ set({ endLabel: v }); },
              placeholder:'Ej: Erice',
            })
          ),

          el(PanelBody, { title:'Estadísticas manuales', initialOpen:false },
            el('p', { style:{fontSize:11,color:'#888',marginBottom:10,lineHeight:1.5} },
              'Si no hay GPX o quieres mostrar valores específicos. El GPX tiene prioridad.'
            ),
            el(TextControl, { label:'Kilómetros', value:a.statKm, onChange:function(v){set({statKm:v});}, placeholder:'Ej: 120 km' }),
            el(TextControl, { label:'Duración',   value:a.statDuration, onChange:function(v){set({statDuration:v});}, placeholder:'Ej: 3h 15min' }),
            el(TextControl, { label:'Desnivel +', value:a.statElevGain, onChange:function(v){set({statElevGain:v});}, placeholder:'Ej: +1.240 m' })
          ),

          el(PanelBody, { title:'Descripción de la ruta', initialOpen:false },
            el(TextareaControl, {
              label: 'Texto descriptivo (opcional)',
              value: a.description,
              onChange: function(v){ set({ description: v }); },
              placeholder: 'Breve descripción de la etapa…',
              rows: 4,
            })
          )
        ),

        /* ── Vista previa en el editor ── */
        el('div', bp,
          a.heading && el('h2', {
            style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:24,
                    letterSpacing:'.05em', textTransform:'uppercase', marginBottom:12 }
          }, a.heading),

          el('div', {
            style:{
              background: (a.gpxUrl || a.gpxUrl2) ? '#e8f0e8' : '#f5f5f2',
              height: H[a.mapHeight] || 260,
              display:'flex', alignItems:'center', justifyContent:'center',
              border:'1px solid #ddd', flexDirection:'column', gap:12,
            },
          },
            el('span', { style:{ fontSize:36 } }, (a.gpxUrl || a.gpxUrl2) ? '\uD83D\uDDFA\uFE0F' : '\uD83D\uDCC1'),

            (a.gpxUrl || a.gpxUrl2) ? el('div', { style:{ textAlign:'center', fontSize:13, color:'#555', lineHeight:1.6 } },
              a.gpxUrl && el('div', null,
                el('span', { style:{ display:'inline-block', width:16, height:3, background:a.routeColor, borderRadius:2, marginRight:6, verticalAlign:'middle' } }),
                (a.gpxLabel1 || 'Ruta 1') + ': ' + a.gpxUrl.split('/').pop()
              ),
              a.gpxUrl2 && el('div', { style:{ marginTop:4 } },
                el('span', { style:{ display:'inline-block', width:16, height:3, background:a.routeColor2, borderRadius:2, marginRight:6, verticalAlign:'middle' } }),
                (a.gpxLabel2 || 'Ruta 2') + ': ' + a.gpxUrl2.split('/').pop()
              )
            ) : el('p', { style:{ fontSize:13, color:'#666', margin:0, textAlign:'center', padding:'0 20px' } },
              'A\u00f1ade la URL del GPX en "GPX 1 \u2014 Ruta principal"'
            )
          ),

          a.showStats && (a.statKm || a.statDuration || a.statElevGain) &&
            el('div', { style:{ display:'flex', border:'1px solid #ddd', borderTop:'none', background:'#fff' } },
              a.statKm && el('div', { style:{ flex:1, padding:'10px 14px', textAlign:'center', borderRight:'1px solid #ddd' } },
                el('div', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:20 } }, a.statKm),
                el('div', { style:{ fontSize:10, color:'#888', textTransform:'uppercase', letterSpacing:'.08em' } }, 'distancia')
              ),
              a.statDuration && el('div', { style:{ flex:1, padding:'10px 14px', textAlign:'center', borderRight: a.statElevGain ? '1px solid #ddd' : 'none' } },
                el('div', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:20 } }, a.statDuration),
                el('div', { style:{ fontSize:10, color:'#888', textTransform:'uppercase', letterSpacing:'.08em' } }, 'duraci\u00f3n')
              ),
              a.statElevGain && el('div', { style:{ flex:1, padding:'10px 14px', textAlign:'center' } },
                el('div', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:20 } }, a.statElevGain),
                el('div', { style:{ fontSize:10, color:'#888', textTransform:'uppercase', letterSpacing:'.08em' } }, 'desnivel')
              )
            ),

          a.description && el('p', {
            style:{ padding:'12px 16px', fontSize:14, color:'#555', lineHeight:1.7,
                    borderLeft:'3px solid #f2c118', marginTop:8, background:'#fafafa' }
          }, a.description)
        )
      );
    },

    save: function(){ return null; },
  });

})();
