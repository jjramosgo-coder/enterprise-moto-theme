/**
 * Enterprise Moto — block-route-comparison.js
 * Bloque "Ruta planificada vs realizada" — editor Gutenberg.
 * GPX1 (azul) = planificada. GPX2 (rojo) = realizada con perfil de altitud.
 */
(function () {
  'use strict';

  if (!window.wp || !wp.blocks || !wp.element) return;

  var el    = wp.element.createElement;
  var Frag  = wp.element.Fragment;
  var be    = wp.blockEditor;
  var co    = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var TextareaControl   = co.TextareaControl;
  var SelectControl     = co.SelectControl;
  var ToggleControl     = co.ToggleControl;
  var RangeControl      = co.RangeControl;
  var ColorPalette      = co.ColorPalette;

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
    el('path', { d:'M3 11l4-7 4 4 4-6 4 5', stroke:'#001f5c', strokeWidth:2,
                 strokeLinecap:'round', strokeLinejoin:'round', fill:'none' }),
    el('path', { d:'M3 15l4-5 4 3 4-4 4 3', stroke:'#c0392b', strokeWidth:2,
                 strokeLinecap:'round', strokeLinejoin:'round', fill:'none', strokeDasharray:'2 2' })
  );

  wp.blocks.registerBlockType('enterprise/route-comparison', {

    title:       'Ruta planificada vs realizada',
    description: 'Superpone la ruta planificada (GPX1, azul) y la realizada (GPX2, rojo) en el mismo mapa. Perfil de altitud solo de GPX2.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','ruta','gpx','comparativa','planificada','realizada','track'],
    supports:    { html: false, align: ['wide','full'] },

    attributes: {
      gpxUrl:        { type:'string',  default:'' },
      gpxUrl2:       { type:'string',  default:'' },
      gpxLabel1:     { type:'string',  default:'GPX1 \u2014 Ruta planificada' },
      gpxLabel2:     { type:'string',  default:'GPX2 \u2014 Ruta realizada'   },
      heading:       { type:'string',  default:'' },
      description:   { type:'string',  default:'' },
      mapHeight:     { type:'string',  default:'md'      },
      routeColor:    { type:'string',  default:'#001f5c' },
      routeColor2:   { type:'string',  default:'#c0392b' },
      markerColor:   { type:'string',  default:'#f2c118' },
      routeWeight:   { type:'integer', default:4         },
      showElevation: { type:'boolean', default:true      },
      showStats:     { type:'boolean', default:true      },
      startLabel:    { type:'string',  default:'' },
      endLabel:      { type:'string',  default:'' },
      statKm:        { type:'string',  default:'' },
      statDuration:  { type:'string',  default:'' },
      statElevGain:  { type:'string',  default:'' },
    },

    edit: function(props) {
      var a   = props.attributes;
      var set = props.setAttributes;
      var bp  = useBlockProps({ className:'ent-route-editor' });
      var H   = { sm:180, md:260, lg:340, xl:420 };

      return el(Frag, null,

        el(InspectorControls, null,

          /* GPX 1 — planificada */
          el(PanelBody, { title: '\uD83D\uDDFA GPX1 \u2014 Ruta planificada', initialOpen: true },
            el('p', { style:{ fontSize:11, color:'#888', marginBottom:10, lineHeight:1.5,
                              background:'#e8f4fd', padding:'8px 10px', borderRadius:3 } },
              '\uD83D\uDCA1 Solo se usa el trazado. Las altitudes de este fichero se ignoran aunque existan.'
            ),
            el(TextControl, {
              label: 'URL del archivo GPX',
              value: a.gpxUrl,
              onChange: function(v){ set({ gpxUrl: v.trim() }); },
              placeholder: 'https://tusitioweb.com/.../planificada.gpx',
              type: 'url',
            }),
            a.gpxUrl && el('p', { style:{ fontSize:11, color:'#0a7c3e', marginTop:4 } },
              '\u2713 ' + a.gpxUrl.split('/').pop()
            ),
            el('div', { style:{ borderTop:'1px solid #e0e0e0', margin:'12px 0' } }),
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                              textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
              'Color de la ruta planificada'
            ),
            el(ColorPalette, {
              colors:    THEME_COLORS,
              value:     a.routeColor,
              onChange:  function(v){ set({ routeColor: v || '#001f5c' }); },
              clearable: false,
            }),
            el(TextControl, {
              label: 'Etiqueta en la leyenda',
              value: a.gpxLabel1,
              onChange: function(v){ set({ gpxLabel1: v }); },
              placeholder: 'GPX1 \u2014 Ruta planificada',
            })
          ),

          /* GPX 2 — realizada */
          el(PanelBody, { title: '\uD83D\uDDFA GPX2 \u2014 Ruta realizada', initialOpen: true },
            el('p', { style:{ fontSize:11, color:'#888', marginBottom:10, lineHeight:1.5,
                              background:'#fdf0ef', padding:'8px 10px', borderRadius:3 } },
              '\uD83D\uDCC8 Este fichero genera el perfil de altitud y la sincronizaci\u00f3n de posici\u00f3n en el mapa.'
            ),
            el(TextControl, {
              label: 'URL del archivo GPX',
              value: a.gpxUrl2,
              onChange: function(v){ set({ gpxUrl2: v.trim() }); },
              placeholder: 'https://tusitioweb.com/.../realizada.gpx',
              type: 'url',
            }),
            a.gpxUrl2 && el('p', { style:{ fontSize:11, color:'#0a7c3e', marginTop:4 } },
              '\u2713 ' + a.gpxUrl2.split('/').pop()
            ),
            el('div', { style:{ borderTop:'1px solid #e0e0e0', margin:'12px 0' } }),
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                              textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
              'Color de la ruta realizada'
            ),
            el(ColorPalette, {
              colors:    THEME_COLORS,
              value:     a.routeColor2,
              onChange:  function(v){ set({ routeColor2: v || '#c0392b' }); },
              clearable: false,
            }),
            el(TextControl, {
              label: 'Etiqueta en la leyenda',
              value: a.gpxLabel2,
              onChange: function(v){ set({ gpxLabel2: v }); },
              placeholder: 'GPX2 \u2014 Ruta realizada',
            })
          ),

          /* Configuración del mapa */
          el(PanelBody, { title: 'Configuraci\u00f3n del mapa', initialOpen: false },
            el(TextControl, {
              label: 'T\u00edtulo (opcional)',
              value: a.heading,
              onChange: function(v){ set({ heading: v }); },
              placeholder: 'Ej: Planificado vs Realizado \u2014 D\u00eda 3',
            }),
            el(SelectControl, {
              label: 'Altura del mapa',
              value: a.mapHeight,
              options: [
                {label:'Peque\u00f1o (320px)', value:'sm'},
                {label:'Mediano (480px)',  value:'md'},
                {label:'Grande (640px)',   value:'lg'},
                {label:'Extra (800px)',    value:'xl'},
              ],
              onChange: function(v){ set({ mapHeight: v }); },
            }),
            el(RangeControl, {
              label: 'Grosor de la l\u00ednea',
              value: a.routeWeight,
              min:1, max:8, step:1,
              onChange: function(v){ set({ routeWeight: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar perfil de elevaci\u00f3n (GPX2)',
              checked: a.showElevation,
              onChange: function(v){ set({ showElevation: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar estad\u00edsticas',
              checked: a.showStats,
              onChange: function(v){ set({ showStats: v }); },
            })
          ),

          el(PanelBody, { title: 'Marcadores inicio/fin', initialOpen: false },
            el(TextControl, {
              label: 'Etiqueta del punto de inicio',
              value: a.startLabel,
              onChange: function(v){ set({ startLabel: v }); },
              placeholder: 'Ej: Palermo',
            }),
            el(TextControl, {
              label: 'Etiqueta del punto final',
              value: a.endLabel,
              onChange: function(v){ set({ endLabel: v }); },
              placeholder: 'Ej: Erice',
            })
          ),

          el(PanelBody, { title: 'Estad\u00edsticas manuales', initialOpen: false },
            el('p', { style:{ fontSize:11, color:'#888', marginBottom:10, lineHeight:1.5 } },
              'El GPX2 tiene prioridad. Usa estos campos si no hay GPX o quieres mostrar valores concretos.'
            ),
            el(TextControl, { label:'Kil\u00f3metros',  value:a.statKm,       onChange:function(v){set({statKm:v});},       placeholder:'Ej: 120 km'    }),
            el(TextControl, { label:'Duraci\u00f3n',    value:a.statDuration, onChange:function(v){set({statDuration:v});}, placeholder:'Ej: 3h 15min'  }),
            el(TextControl, { label:'Desnivel +',       value:a.statElevGain, onChange:function(v){set({statElevGain:v});}, placeholder:'Ej: +1.240 m'  })
          ),

          el(PanelBody, { title: 'Descripci\u00f3n de la ruta', initialOpen: false },
            el(TextareaControl, {
              label: 'Texto descriptivo (opcional)',
              value: a.description,
              onChange: function(v){ set({ description: v }); },
              placeholder: 'Breve descripci\u00f3n de la etapa\u2026',
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
              border:'1px solid #ddd', flexDirection:'column', gap:10,
            },
          },
            el('span', { style:{ fontSize:36 } }, (a.gpxUrl || a.gpxUrl2) ? '\uD83D\uDDFA\uFE0F' : '\uD83D\uDCC1'),
            (a.gpxUrl || a.gpxUrl2)
              ? el('div', { style:{ textAlign:'center', fontSize:13, color:'#555', lineHeight:1.8 } },
                  a.gpxUrl && el('div', null,
                    el('span', { style:{ display:'inline-block', width:16, height:3, background:a.routeColor,
                                        borderRadius:2, marginRight:6, verticalAlign:'middle' } }),
                    a.gpxLabel1 + ': ' + a.gpxUrl.split('/').pop()
                  ),
                  a.gpxUrl2 && el('div', null,
                    el('span', { style:{ display:'inline-block', width:16, height:3, background:a.routeColor2,
                                        borderRadius:2, marginRight:6, verticalAlign:'middle',
                                        backgroundImage:'repeating-linear-gradient(90deg,' + a.routeColor2 + ' 0 4px,transparent 4px 8px)',
                                        background:'none' } }),
                    el('span', { style:{ borderBottom:'2px dashed ' + a.routeColor2, paddingBottom:1, marginRight:6 } }),
                    a.gpxLabel2 + ': ' + a.gpxUrl2.split('/').pop()
                  )
                )
              : el('p', { style:{ fontSize:13, color:'#666', margin:0, textAlign:'center', padding:'0 20px' } },
                  'A\u00f1ade las URLs de los archivos GPX en el panel lateral'
                )
          ),

          a.showStats && (a.statKm || a.statDuration || a.statElevGain) &&
            el('div', { style:{ display:'flex', border:'1px solid #ddd', borderTop:'none', background:'#fff' } },
              a.statKm && el('div', { style:{ flex:1, padding:'10px 14px', textAlign:'center', borderRight:'1px solid #ddd' } },
                el('div', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:20 } }, a.statKm),
                el('div', { style:{ fontSize:10, color:'#888', textTransform:'uppercase', letterSpacing:'.08em' } }, 'distancia')
              ),
              a.statDuration && el('div', { style:{ flex:1, padding:'10px 14px', textAlign:'center', borderRight: a.statElevGain ? '1px solid #ddd':'none' } },
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
