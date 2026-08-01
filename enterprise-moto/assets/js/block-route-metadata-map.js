/**
 * Enterprise Moto — block-route-metadata-map.js
 * Bloque «Mapa de ruta con metadatos» (enterprise/route-metadata-map) — editor Gutenberg.
 * Dibuja la ruta planificada (opcional) y la registrada (track) como route-comparison,
 * y carga la capa de metadatos (métricas GPX + inventario geográfico).
 *
 * Commit 2 (#56 · Fase 2 del plan #45): botón → Modal (captura), ciclo de vida de dos
 * estados, barra lateral heredada bajo el Lectura-B lock y toggle useGeoInventory.
 * «Guardar» es un stub en este commit: la subida+validación en servidor y el espejo de
 * metadatos con datos reales se cablean en el Commit 3. La vista previa validada cae con
 * elegancia mientras no haya metadatos.
 *
 * Patrón Modal calcado de block-routes-by-location.js (mount-on-open,
 * shouldCloseOnClickOutside:false, estado local). Vanilla JS, sin build.
 */
(function () {
  'use strict';

  if (!window.wp || !wp.blocks || !wp.element) return;

  var el       = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useState = wp.element.useState;
  var be       = wp.blockEditor;
  var co        = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var TextareaControl   = co.TextareaControl;
  var SelectControl     = co.SelectControl;
  var ToggleControl     = co.ToggleControl;
  var RangeControl      = co.RangeControl;
  var ColorPalette      = co.ColorPalette;
  var Button            = co.Button;
  var Modal             = co.Modal;
  var Notice            = co.Notice;

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
                 strokeLinecap:'round', strokeLinejoin:'round', fill:'none', strokeDasharray:'2 2' }),
    el('circle', { cx:19, cy:5, r:3, fill:'#f2c118' }),
    el('path', { d:'M19 4v.5M19 5.5v1.5', stroke:'#0e0e0e', strokeWidth:1,
                 strokeLinecap:'round', fill:'none' })
  );

  /* ═══════════════════════════════════════════
     UTILIDADES
  ═══════════════════════════════════════════ */
  function pad2( v ) {
    v = String(v == null ? '' : v).replace(/\D/g, '');
    if (v === '') return '';
    if (v.length === 1) v = '0' + v;
    return v.slice(-2);
  }

  /* Valida que <year><month><day> forme una fecha real (año de 4 cifras, mes 1-12,
     día existente en ese mes/año). */
  function isValidYmd( y, m, d ) {
    if (!/^\d{4}$/.test(String(y))) return false;
    var yy = parseInt(y, 10), mm = parseInt(m, 10), dd = parseInt(d, 10);
    if (isNaN(mm) || isNaN(dd)) return false;
    if (mm < 1 || mm > 12 || dd < 1 || dd > 31) return false;
    var dt = new Date(yy, mm - 1, dd);
    return dt.getFullYear() === yy && (dt.getMonth() + 1) === mm && dt.getDate() === dd;
  }

  /* «trip» (viaje) es un campo INFORMATIVO: no participa en el path ni en el nombre de
     fichero (el almacenamiento usa <year>/<month>) y NO se sanea. Se muestra como etiqueta. */

  /* ═══════════════════════════════════════════
     MODAL DE CAPTURA (contenido)
     Montado solo al abrir. Dos estados: editable / solo lectura (validado).
  ═══════════════════════════════════════════ */
  function CaptureModal( props ) {
    var validated = props.validated;      // boolean
    var data      = props.data;           // { year, month, day, trip }
    var onSave    = props.onSave;         // (fields, files) => void   (stub en Commit 2)
    var onDelete  = props.onDelete;       // () => void
    var onClose   = props.onClose;        // () => void

    var now = new Date();

    var _y = useState( data.year  || String(now.getFullYear()) ); var year  = _y[0]; var setYear  = _y[1];
    var _m = useState( data.month || pad2(now.getMonth() + 1) );  var month = _m[0]; var setMonth = _m[1];
    var _d = useState( data.day   || pad2(now.getDate()) );       var day   = _d[0]; var setDay   = _d[1];
    var _t = useState( data.trip  || '' );                        var trip  = _t[0]; var setTrip  = _t[1];

    var _fp = useState(null); var plannedFile  = _fp[0]; var setPlannedFile  = _fp[1];
    var _fr = useState(null); var recordedFile = _fr[0]; var setRecordedFile = _fr[1];
    var _fj = useState(null); var metaFile     = _fj[0]; var setMetaFile     = _fj[1];

    var _err = useState(''); var error = _err[0]; var setError = _err[1];

    function handleSave() {
      var y = year, m = pad2(month), d = pad2(day);
      if (!/^\d{4}$/.test(y)) { setError('El año debe tener 4 cifras (aaaa).'); return; }
      if (!isValidYmd(y, m, d)) { setError('La fecha (año/mes/día) no es válida.'); return; }
      if (!recordedFile) { setError('Falta el GPX de la ruta registrada (track).'); return; }
      if (!metaFile) { setError('Faltan los metadatos de la ruta (JSON).'); return; }
      setError('');
      // Commit 2 (stub): persiste los campos y marca validado. El Commit 3 sustituye esto
      // por la subida+validación en servidor (hash, duplicidad, almacenamiento).
      // El almacenamiento usa <year>/<month>; «trip» es informativo y opcional (no entra en path ni nombre).
      onSave(
        { year: y, month: m, day: d, trip: trip },
        { planned: plannedFile, recorded: recordedFile, metadata: metaFile }
      );
    }

    var lblStyle = { fontSize:11, fontWeight:700, letterSpacing:'.08em',
                     textTransform:'uppercase', color:'#1e1e1e', margin:'0 0 6px' };

    return el('div', { className:'ent-rmm-modal', style:{ minWidth:320 } },

      validated && el(Notice, { status:'success', isDismissible:false },
        'Ruta y metadatos guardados. Los datos son de solo lectura; usa «Borrar ficheros» para volver a editarlos.'),

      error && el(Notice, { status:'error', isDismissible:false, onRemove:function(){ setError(''); } }, error),

      /* ── Fecha + viaje ── */
      el('div', { style:{ display:'flex', gap:10, flexWrap:'wrap', marginBottom:4 } },
        el('div', { style:{ flex:'0 0 90px' } },
          el(TextControl, { label:'Año',  value:year,  disabled:validated,
            onChange:function(v){ setYear(v.replace(/\D/g,'').slice(0,4)); } })),
        el('div', { style:{ flex:'0 0 70px' } },
          el(TextControl, { label:'Mes',  value:month, disabled:validated,
            onChange:function(v){ setMonth(v.replace(/\D/g,'').slice(0,2)); } })),
        el('div', { style:{ flex:'0 0 70px' } },
          el(TextControl, { label:'Día',  value:day,   disabled:validated,
            onChange:function(v){ setDay(v.replace(/\D/g,'').slice(0,2)); } })),
        el('div', { style:{ flex:'1 1 160px' } },
          el(TextControl, { label:'Viaje (opcional)', value:trip, disabled:validated,
            onChange:function(v){ setTrip(v); },
            help:'Nombre del viaje (informativo).' }))
      ),

      /* ── Ficheros locales ── */
      validated
        ? el('p', { style:{ fontSize:12, color:'#666', margin:'10px 0 4px', lineHeight:1.6 } },
            'Ficheros almacenados en routes/recorded/' +
            (data.year || year) + '/' + pad2(data.month || month) +
            '. Para reemplazarlos, bórralos y vuelve a cargarlos.')
        : el('div', { style:{ marginTop:10 } },
            el('p', lblStyle, 'GPX de la ruta planificada (opcional)'),
            el('input', { type:'file', accept:'.gpx,application/gpx+xml,text/xml',
              onChange:function(e){ setPlannedFile(e.target.files[0] || null); },
              style:{ display:'block', marginBottom:12, fontSize:13 } }),

            el('p', lblStyle, 'GPX de la ruta registrada (track)'),
            el('input', { type:'file', accept:'.gpx,application/gpx+xml,text/xml',
              onChange:function(e){ setRecordedFile(e.target.files[0] || null); },
              style:{ display:'block', marginBottom:12, fontSize:13 } }),

            el('p', lblStyle, 'Metadatos de la ruta (JSON)'),
            el('input', { type:'file', accept:'.json,application/json',
              onChange:function(e){ setMetaFile(e.target.files[0] || null); },
              style:{ display:'block', marginBottom:4, fontSize:13 } })
          ),

      /* ── Pie: tres botones (§3.4) ── */
      el('div', { style:{ display:'flex', justifyContent:'flex-end', gap:8, marginTop:18,
                          borderTop:'1px solid #eee', paddingTop:14 } },
        el(Button, { variant:'tertiary', onClick:onClose }, 'Cancelar'),
        el(Button, { isDestructive:true, variant:'secondary', disabled:!validated,
          onClick:function(){ onDelete(); } }, 'Borrar ficheros'),
        !validated && el(Button, { variant:'primary', onClick:handleSave }, 'Guardar')
      )
    );
  }

  /* ═══════════════════════════════════════════
     REGISTRO DEL BLOQUE
  ═══════════════════════════════════════════ */
  wp.blocks.registerBlockType('enterprise/route-metadata-map', {

    title:       'Mapa de ruta con metadatos',
    description: 'Dibuja la ruta planificada (opcional) y la registrada (track) sobre el mapa, con las métricas del fichero de metadatos y el inventario geográfico del mapa de regiones.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','ruta','gpx','track','metadatos','registrada','planificada','regiones'],
    supports:    { html: false, align: ['wide','full'] },

    attributes: {
      year:            { type:'string',  default:'' },
      month:           { type:'string',  default:'' },
      day:             { type:'string',  default:'' },
      trip:            { type:'string',  default:'' },
      validated:       { type:'boolean', default:false },
      useGeoInventory: { type:'boolean', default:true  },
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
      var a   = props.attributes;
      var set = props.setAttributes;
      var bp  = useBlockProps({ className:'ent-route-metadata-map-editor' });

      var _o = useState(false); var isOpen = _o[0]; var setOpen = _o[1];

      var locked = !a.validated; // Lectura-B lock: barra lateral inhabilitada hasta validar.

      /* ── Guardar (stub Commit 2): persiste campos + marca validado ── */
      function onModalSave( fields /*, files */ ) {
        set({
          year: fields.year, month: fields.month, day: fields.day, trip: fields.trip,
          validated: true,
        });
        setOpen(false);
      }

      /* ── Borrar ficheros: vuelve al estado editable (§3.5) ── */
      function onModalDelete() {
        set({ validated: false });
        setOpen(false);
      }

      return el(Fragment, null,

        /* ═══════ BARRA LATERAL (bajo el Lectura-B lock) ═══════ */
        el(InspectorControls, null,

          locked && el('div', { style:{ padding:'12px 16px' } },
            el(Notice, { status:'warning', isDismissible:false },
              'Carga y guarda la ruta y sus metadatos para configurar la presentación.')
          ),

          el(PanelBody, { title: 'Configuración del mapa', initialOpen: true },
            el(TextControl, {
              label: 'Título (opcional)', value: a.heading, disabled: locked,
              onChange: function(v){ set({ heading: v }); },
              placeholder: 'Ej: Ruta del día 3',
            }),
            el(SelectControl, {
              label: 'Altura del mapa', value: a.mapHeight, disabled: locked,
              options: [
                {label:'Pequeño (320px)', value:'sm'},
                {label:'Mediano (480px)', value:'md'},
                {label:'Grande (640px)',  value:'lg'},
                {label:'Extra (800px)',   value:'xl'},
              ],
              onChange: function(v){ set({ mapHeight: v }); },
            }),
            el(RangeControl, {
              label: 'Grosor de la línea', value: a.routeWeight, disabled: locked,
              min:1, max:8, step:1,
              onChange: function(v){ set({ routeWeight: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar perfil de elevación (GPX de la ruta registrada)',
              checked: a.showElevation, disabled: locked,
              onChange: function(v){ set({ showElevation: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar estadísticas',
              checked: a.showStats, disabled: locked,
              onChange: function(v){ set({ showStats: v }); },
            })
          ),

          el(PanelBody, { title: 'Colores y leyenda', initialOpen: false },
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                              textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
              'Ruta planificada'),
            el('fieldset', { disabled: locked, style:{ border:'none', margin:0, padding:0, minInlineSize:'auto' } },
              el(ColorPalette, {
                colors: THEME_COLORS, value: a.routeColor,
                onChange: function(v){ set({ routeColor: v || '#001f5c' }); }, clearable: false,
              })),
            el(TextControl, {
              label: 'Etiqueta en la leyenda (planificada)', value: a.gpxLabel1, disabled: locked,
              onChange: function(v){ set({ gpxLabel1: v }); }, placeholder: 'GPX1 — Ruta planificada',
            }),
            el('div', { style:{ borderTop:'1px solid #e0e0e0', margin:'12px 0' } }),
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                              textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
              'Ruta registrada'),
            el('fieldset', { disabled: locked, style:{ border:'none', margin:0, padding:0, minInlineSize:'auto' } },
              el(ColorPalette, {
                colors: THEME_COLORS, value: a.routeColor2,
                onChange: function(v){ set({ routeColor2: v || '#c0392b' }); }, clearable: false,
              })),
            el(TextControl, {
              label: 'Etiqueta en la leyenda (registrada)', value: a.gpxLabel2, disabled: locked,
              onChange: function(v){ set({ gpxLabel2: v }); }, placeholder: 'GPX2 — Ruta realizada',
            }),
            el('div', { style:{ borderTop:'1px solid #e0e0e0', margin:'12px 0' } }),
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                              textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
              'Marcador'),
            el('fieldset', { disabled: locked, style:{ border:'none', margin:0, padding:0, minInlineSize:'auto' } },
              el(ColorPalette, {
                colors: THEME_COLORS, value: a.markerColor,
                onChange: function(v){ set({ markerColor: v || '#f2c118' }); }, clearable: false,
              }))
          ),

          el(PanelBody, { title: 'Marcadores inicio/fin', initialOpen: false },
            el(TextControl, {
              label: 'Etiqueta del punto de inicio', value: a.startLabel, disabled: locked,
              onChange: function(v){ set({ startLabel: v }); }, placeholder: 'Ej: Palermo',
            }),
            el(TextControl, {
              label: 'Etiqueta del punto final', value: a.endLabel, disabled: locked,
              onChange: function(v){ set({ endLabel: v }); }, placeholder: 'Ej: Erice',
            })
          ),

          el(PanelBody, { title: 'Descripción de la ruta', initialOpen: false },
            el(TextareaControl, {
              label: 'Texto descriptivo (opcional)', value: a.description, disabled: locked, rows: 4,
              onChange: function(v){ set({ description: v }); },
              placeholder: 'Breve descripción de la etapa…',
            })
          ),

          el(PanelBody, { title: 'Inventario del mapa de regiones', initialOpen: false },
            el(ToggleControl, {
              label: 'Usar los metadatos geográficos para el inventario del mapa de regiones',
              checked: a.useGeoInventory, disabled: locked,
              onChange: function(v){ set({ useGeoInventory: v }); },
            })
          )
        ),

        /* ═══════ LIENZO CENTRAL (placeholder / vista previa) ═══════ */
        el('div', bp,

          a.validated

            /* Vista previa (validado). El espejo de metadatos con datos reales
               se cablea en el Commit 3 (2b): mientras tanto, estado grácil. */
            ? el('div', { style:{ border:'1px solid #ddd', background:'#fff',
                                  borderRadius:8, padding:'18px 20px' } },
                el('div', { style:{ display:'flex', alignItems:'center', gap:10, marginBottom:6 } },
                  el('span', { style:{ fontSize:22 } }, '🗺️'),
                  el('strong', { style:{ fontSize:15 } }, 'Mapa de ruta con metadatos'),
                  el('span', { style:{ marginLeft:6, fontSize:12, fontWeight:600,
                                       color:'#12703a', background:'#e3f3ea',
                                       padding:'3px 10px', borderRadius:6 } }, '✓ Validado'),
                  el('span', { style:{ marginLeft:'auto' } },
                    el(Button, { isDestructive:true, variant:'secondary',
                      onClick:function(){ setOpen(true); } }, 'Borrar ficheros'))
                ),
                el('p', { style:{ margin:'0 0 12px', fontSize:13, color:'#8a8a86' } },
                  [ (a.trip || '—'),
                    ((a.year && a.month && a.day) ? (a.day + '/' + a.month + '/' + a.year) : ''),
                    'ruta registrada (GPX2)'
                  ].filter(Boolean).join(' · ')),
                el('div', { style:{ background:'#f5f5f2', borderRadius:8, padding:'16px 18px',
                                    fontSize:13, color:'#666', lineHeight:1.6 } },
                  'El mapa y el espejo de estadísticas de los metadatos se muestran en el frontend. '
                  + 'La vista previa completa de las métricas aparecerá aquí una vez conectada la carga de ficheros.')
              )

            /* Placeholder (sin datos): un único botón lanzador. */
            : el('div', { style:{ background:'#f5f5f2', border:'1px solid #ddd', borderRadius:8,
                                  minHeight:180, display:'flex', alignItems:'center',
                                  justifyContent:'center', flexDirection:'column', gap:14,
                                  padding:'28px 20px', textAlign:'center' } },
                el('span', { style:{ fontSize:36 } }, '🗺️'),
                el('p', { style:{ margin:0, fontSize:15, fontWeight:600, color:'#1e1e1e', lineHeight:1.5 } },
                  'Rutas planificada y registrada (track) con metadatos'),
                el(Button, { variant:'primary', onClick:function(){ setOpen(true); } },
                  'Cargar ruta y metadatos')
              ),

          /* ── Modal (mount-on-open) ── */
          isOpen && el(Modal, {
            title: a.validated ? 'Ruta y metadatos (guardados)' : 'Cargar ruta y metadatos',
            onRequestClose: function(){ setOpen(false); },
            shouldCloseOnClickOutside: false,
          },
            el(CaptureModal, {
              validated: a.validated,
              data: { year: a.year, month: a.month, day: a.day, trip: a.trip },
              onSave: onModalSave,
              onDelete: onModalDelete,
              onClose: function(){ setOpen(false); },
            })
          )
        )
      );
    },

    save: function(){ return null; },
  });

})();
