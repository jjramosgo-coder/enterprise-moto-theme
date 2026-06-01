/**
 * Enterprise Moto — block-animated-route-map.js
 * Bloque "Mapa de ruta animado"
 * Igual que el bloque de ruta, pero con sincronización
 * entre el perfil de elevación y el marcador en el mapa.
 */
(function () {
  'use strict';
  if (!window.wp || !wp.blocks || !wp.element) return;

  var el   = wp.element.createElement;
  var Frag = wp.element.Fragment;
  var be   = wp.blockEditor;
  var co   = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody      = co.PanelBody;
  var TextControl    = co.TextControl;
  var TextareaControl= co.TextareaControl;
  var SelectControl  = co.SelectControl;
  var ToggleControl  = co.ToggleControl;
  var RangeControl   = co.RangeControl;
  var ColorPalette   = co.ColorPalette;

  var THEME_COLORS = [
    { name:'Azul marino', color:'#001f5c' },
    { name:'Negro',       color:'#0e0e0e' },
    { name:'Dorado',      color:'#f2c118' },
    { name:'Rojo',        color:'#c0392b' },
    { name:'Verde',       color:'#1a7a3a' },
    { name:'Naranja',     color:'#e67e22' },
    { name:'Gris',        color:'#5a5a5a' },
  ];

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M3 11l4-7 4 4 4-6 4 5', stroke:'currentColor', strokeWidth:2, strokeLinecap:'round', strokeLinejoin:'round', fill:'none' }),
    el('circle', { cx:12, cy:12, r:2.5, fill:'currentColor' }),
    el('circle', { cx:12, cy:12, r:5, stroke:'currentColor', strokeWidth:1.5, fill:'none', opacity:0.5 })
  );

  wp.blocks.registerBlockType('enterprise/animated-route-map', {
    title:       'Mapa de ruta animado',
    description: 'Mapa con sincronización en tiempo real entre el perfil de elevación y un marcador en el mapa.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','ruta','gpx','animado','elevación','marcador','sincronizado'],
    supports:    { html:false, align:['wide','full'] },

    attributes: {
      gpxUrl:        { type:'string',  default:'' },
      heading:       { type:'string',  default:'' },
      mapHeight:     { type:'string',  default:'md' },
      routeColor:    { type:'string',  default:'#001f5c' },
      routeWeight:   { type:'integer', default:4  },
      markerColor:   { type:'string',  default:'#f2c118' },
      showElevation: { type:'boolean', default:true  },
      showStats:     { type:'boolean', default:true  },
      startLabel:    { type:'string',  default:'' },
      endLabel:      { type:'string',  default:'' },
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

          el(PanelBody, { title:'\uD83D\uDDFA GPX — Archivo de ruta', initialOpen:true },
            el('p', { style:{ fontSize:11, color:'#888', marginBottom:8, lineHeight:1.5 } },
              'Sube el GPX por FTP y pega la URL. El archivo debe contener datos de elevación (<ele>) para la sincronización.'
            ),
            el(TextControl, {
              label:'URL del archivo GPX',
              value:a.gpxUrl, onChange:function(v){set({gpxUrl:v.trim()});},
              placeholder:'https://tusitioweb.com/wp-content/uploads/ruta.gpx',
              type:'url',
            }),
            a.gpxUrl && el('p', { style:{ fontSize:11, color:'#0a7c3e', marginTop:4 } },
              '\u2713 ' + a.gpxUrl.split('/').pop()
            )
          ),

          el(PanelBody, { title:'Configuración del mapa', initialOpen:false },
            el(TextControl, {
              label:'Título (opcional)', value:a.heading,
              onChange:function(v){set({heading:v});},
              placeholder:'Ej: Palermo \u2192 Erice',
            }),
            el(SelectControl, {
              label:'Altura del mapa', value:a.mapHeight,
              options:[
                {label:'Pequeño (320px)', value:'sm'},
                {label:'Mediano (480px)', value:'md'},
                {label:'Grande (640px)',  value:'lg'},
                {label:'Extra (800px)',   value:'xl'},
              ],
              onChange:function(v){set({mapHeight:v});},
            }),
            el(RangeControl, {
              label:'Grosor de la línea', value:a.routeWeight,
              min:1, max:8, step:1,
              onChange:function(v){set({routeWeight:v});},
            }),

            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginTop:16, marginBottom:8 } },
              'Color de la ruta'
            ),
            el(ColorPalette, {
              colors:THEME_COLORS, value:a.routeColor, clearable:false,
              onChange:function(v){set({routeColor:v||'#001f5c'});},
            }),

            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginTop:16, marginBottom:8 } },
              'Color del marcador animado'
            ),
            el(ColorPalette, {
              colors:THEME_COLORS, value:a.markerColor, clearable:false,
              onChange:function(v){set({markerColor:v||'#f2c118'});},
            }),

            el(ToggleControl, {
              label:'Mostrar perfil de elevación (requerido para animación)',
              checked:a.showElevation,
              onChange:function(v){set({showElevation:v});},
            }),
            el(ToggleControl, { label:'Mostrar estadísticas', checked:a.showStats, onChange:function(v){set({showStats:v});} })
          ),

          el(PanelBody, { title:'Marcadores inicio/fin', initialOpen:false },
            el(TextControl, { label:'Inicio', value:a.startLabel, onChange:function(v){set({startLabel:v});}, placeholder:'Ej: Palermo' }),
            el(TextControl, { label:'Final',  value:a.endLabel,   onChange:function(v){set({endLabel:v});},   placeholder:'Ej: Erice' })
          ),

          el(PanelBody, { title:'Estadísticas manuales', initialOpen:false },
            el(TextControl, { label:'Kilómetros', value:a.statKm, onChange:function(v){set({statKm:v});}, placeholder:'Ej: 120 km' }),
            el(TextControl, { label:'Duración',   value:a.statDuration, onChange:function(v){set({statDuration:v});}, placeholder:'Ej: 3h 15min' }),
            el(TextControl, { label:'Desnivel +', value:a.statElevGain, onChange:function(v){set({statElevGain:v});}, placeholder:'Ej: +1.240 m' })
          ),

          el(PanelBody, { title:'Descripción', initialOpen:false },
            el(TextareaControl, { label:'Texto descriptivo', value:a.description, onChange:function(v){set({description:v});}, rows:4 })
          )
        ),

        /* Vista previa en editor */
        el('div', bp,
          a.heading && el('h2', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:24, letterSpacing:'.05em', textTransform:'uppercase', marginBottom:12 } }, a.heading),
          el('div', {
            style:{ background:a.gpxUrl?'#e8f0e8':'#f5f5f2', height:H[a.mapHeight]||260,
                    display:'flex', alignItems:'center', justifyContent:'center',
                    border:'1px solid #ddd', flexDirection:'column', gap:10 }
          },
            el('span', { style:{ fontSize:36 } }, a.gpxUrl ? '\uD83D\uDDFA\uFE0F' : '\uD83D\uDCC1'),
            el('p', { style:{ fontSize:13, color:'#555', margin:0, textAlign:'center', padding:'0 20px', lineHeight:1.5 } },
              a.gpxUrl
                ? '\u2713 ' + a.gpxUrl.split('/').pop() + ' — El marcador animado se sincroniza con el perfil de elevación'
                : 'A\u00f1ade la URL del GPX en el panel lateral'
            ),
            a.gpxUrl && el('p', { style:{ fontSize:11, color:'#888', margin:0 } },
              'Ruta: ' + a.routeColor + ' \u00b7 Marcador: ' + a.markerColor
            )
          ),
          a.showElevation && a.gpxUrl && el('div', {
            style:{ background:'#f5f5f2', border:'1px solid #ddd', borderTop:'none',
                    height:120, display:'flex', alignItems:'center', justifyContent:'center',
                    fontSize:12, color:'#888' }
          }, '\uD83D\uDCCA Perfil de elevaci\u00f3n — mueve el rat\u00f3n para animar el marcador'),
          a.description && el('p', { style:{ padding:'12px 16px', fontSize:14, color:'#555', lineHeight:1.7, borderLeft:'3px solid #f2c118', marginTop:8, background:'#fafafa' } }, a.description)
        )
      );
    },

    save: function(){ return null; },
  });
})();
