/**
 * Enterprise Moto — block-location-map.js
 * Bloque Gutenberg "Mapa de localizaciones"
 * Búsqueda por nombre con Nominatim (OpenStreetMap). Sin API key.
 * Vanilla JS, sin herramientas de compilación.
 */
(function () {
  'use strict';

  if ( ! window.wp || ! wp.blocks || ! wp.element ) return;

  var el       = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useState = wp.element.useState;
  var useEffect= wp.element.useEffect;
  var useRef   = wp.element.useRef;
  var be       = wp.blockEditor;
  var co       = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var SelectControl     = co.SelectControl;
  var ToggleControl     = co.ToggleControl;
  var Button            = co.Button;
  var Spinner           = co.Spinner;
  var Notice            = co.Notice;

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z',
      fill:'currentColor' })
  );

  /* ── Búsqueda Nominatim ── */
  function searchNominatim( query, callback ) {
    var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&q='
              + encodeURIComponent( query );
    fetch( url, { headers: { 'Accept-Language': 'es' } } )
      .then( function(r){ return r.json(); } )
      .then( function(data){
        callback( null, data.map(function(r){
          return {
            display: r.display_name,
            lat:     parseFloat( r.lat ),
            lng:     parseFloat( r.lon ),
          };
        }) );
      })
      .catch( function(e){ callback(e); } );
  }

  /* ── Componente buscador de localizaciones ── */
  function LocationSearch( props ) {
    var onAdd = props.onAdd;

    var _q = useState('');   var query = _q[0]; var setQuery = _q[1];
    var _r = useState([]);   var results = _r[0]; var setResults = _r[1];
    var _l = useState(false);var loading = _l[0]; var setLoading = _l[1];
    var _e = useState('');   var error = _e[0];   var setError = _e[1];
    var _n = useState('');   var customName = _n[0]; var setCustomName = _n[1];
    var timer = useRef(null);

    function doSearch() {
      if ( query.length < 2 ) return;
      clearTimeout( timer.current );
      timer.current = setTimeout(function(){
        setLoading(true); setResults([]); setError('');
        searchNominatim( query, function(err, data){
          setLoading(false);
          if (err) { setError('Error al buscar. Inténtalo de nuevo.'); return; }
          if (!data.length) { setError('Sin resultados para "' + query + '"'); return; }
          setResults(data);
        });
      }, 500);
    }

    function handleSelect( result ) {
      onAdd({
        name: customName || result.display.split(',')[0].trim(),
        fullAddress: result.display,
        lat: result.lat,
        lng: result.lng,
        description: '',
        postUrl: '',
      });
      setQuery(''); setResults([]); setCustomName(''); setError('');
    }

    return el('div', { style:{ marginBottom:16 } },
      el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                        textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
        'Añadir localización'
      ),
      el('div', { style:{ display:'flex', gap:6, marginBottom:6 } },
        el('input', {
          type:'text', value:query,
          placeholder: 'Buscar lugar (ej: Palermo, Sicilia)…',
          onChange: function(e){ setQuery(e.target.value); },
          onKeyDown: function(e){ if(e.key==='Enter'){e.preventDefault();doSearch();} },
          style:{ flex:1, padding:'7px 10px', fontSize:13, border:'1px solid #ddd',
                  borderRadius:2, fontFamily:'inherit' },
        }),
        el('button', {
          type:'button',
          onClick: doSearch,
          disabled: query.length < 2 || loading,
          style:{ padding:'7px 14px', background:'#0e0e0e', color:'#fff',
                  border:'none', borderRadius:2, cursor:'pointer', fontSize:13,
                  opacity: query.length < 2 ? .4 : 1 },
        }, loading ? '…' : 'Buscar')
      ),
      loading && el('div', { style:{ display:'flex', alignItems:'center', gap:6, fontSize:12, color:'#888' } },
        el(Spinner), 'Buscando…'
      ),
      error && el('p', { style:{ fontSize:12, color:'#c00', margin:'4px 0' } }, error),
      results.length > 0 && el('div', null,
        el('input', {
          type:'text', value:customName,
          placeholder: 'Nombre personalizado (opcional)…',
          onChange: function(e){ setCustomName(e.target.value); },
          style:{ width:'100%', padding:'6px 10px', fontSize:12, border:'1px solid #ddd',
                  borderRadius:2, marginBottom:6, fontFamily:'inherit' },
        }),
        el('ul', { style:{ listStyle:'none', margin:0, padding:0,
                           border:'1px solid #ddd', maxHeight:180, overflowY:'auto' } },
          results.map(function(r, i){
            return el('li', { key:i },
              el('button', {
                type:'button',
                onClick: function(){ handleSelect(r); },
                style:{ width:'100%', textAlign:'left', padding:'8px 10px',
                        background:'none', border:'none', borderBottom:'1px solid #f0f0f0',
                        cursor:'pointer', fontSize:12, lineHeight:1.4, fontFamily:'inherit' },
              }, r.display)
            );
          })
        )
      )
    );
  }

  /* ── Lista de marcadores ── */
  function MarkerList( props ) {
    var markers   = props.markers;
    var onRemove  = props.onRemove;
    var onUpdate  = props.onUpdate;
    var onMove    = props.onMove;

    if (!markers || !markers.length) {
      return el('p', { style:{ fontSize:12, color:'#888', margin:'8px 0' } },
        'Sin marcadores. Busca una localización arriba.'
      );
    }

    return el('ul', { style:{ listStyle:'none', padding:0, margin:0 } },
      markers.map(function(m, i){
        return el('li', { key: i,
                          style:{ padding:'8px 0', borderBottom:'1px solid #eee' } },
          el('div', { style:{ display:'flex', alignItems:'flex-start', gap:6 } },
            /* Reordenar */
            el('div', { style:{ display:'flex', flexDirection:'column', gap:2, flexShrink:0 } },
              el('button', { type:'button', onClick:function(){ onMove(i,'up'); },
                             disabled: i===0,
                             style:{ background:'none', border:'none', padding:'1px 4px',
                                     cursor:'pointer', fontSize:9, color:'#888' } }, '▲'),
              el('button', { type:'button', onClick:function(){ onMove(i,'down'); },
                             disabled: i===markers.length-1,
                             style:{ background:'none', border:'none', padding:'1px 4px',
                                     cursor:'pointer', fontSize:9, color:'#888' } }, '▼')
            ),
            el('span', { style:{ fontSize:10, color:'#aaa', minWidth:18, paddingTop:2 } },
              String(i+1).padStart(2,'0')
            ),
            el('div', { style:{ flex:1, minWidth:0 } },
              el('input', {
                type:'text', value:m.name,
                placeholder: 'Nombre del lugar…',
                onChange: function(e){ onUpdate(i, 'name', e.target.value); },
                style:{ width:'100%', padding:'4px 8px', fontSize:12, border:'1px solid #ddd',
                        borderRadius:2, marginBottom:4, fontFamily:'inherit' },
              }),
              el('input', {
                type:'text', value:m.description || '',
                placeholder: 'Descripción breve (opcional)…',
                onChange: function(e){ onUpdate(i, 'description', e.target.value); },
                style:{ width:'100%', padding:'4px 8px', fontSize:11, border:'1px solid #eee',
                        borderRadius:2, marginBottom:4, color:'#555', fontFamily:'inherit' },
              }),
              el('input', {
                type:'url', value:m.postUrl || '',
                placeholder: 'URL de la entrada relacionada (opcional)…',
                onChange: function(e){ onUpdate(i, 'postUrl', e.target.value); },
                style:{ width:'100%', padding:'4px 8px', fontSize:11, border:'1px solid #eee',
                        borderRadius:2, color:'#555', fontFamily:'inherit' },
              }),
              el('p', { style:{ fontSize:10, color:'#aaa', margin:'3px 0 0' } },
                m.lat.toFixed(5) + ', ' + m.lng.toFixed(5)
              )
            ),
            el('button', {
              type:'button',
              onClick: function(){ onRemove(i); },
              style:{ background:'none', border:'none', cursor:'pointer',
                      color:'#999', fontSize:16, lineHeight:1, padding:'0 4px', flexShrink:0 },
            }, '×')
          )
        );
      })
    );
  }

  /* ── Registro del bloque ── */
  wp.blocks.registerBlockType('enterprise/location-map', {

    title:       'Mapa de localizaciones',
    description: 'Mapa OpenStreetMap con marcadores añadidos por nombre de lugar.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','map','marcadores','localizaciones','openstreetmap'],
    supports:    { html:false, align:['wide','full'] },

    attributes: {
      markers:    { type:'array',   default:[], items:{ type:'object' } },
      mapHeight:  { type:'string',  default:'md'    }, // sm|md|lg|xl
      mapZoom:    { type:'integer', default:6       },
      heading:    { type:'string',  default:''      },
      showLegend: { type:'boolean', default:true    },
      showNumbers:{ type:'boolean', default:true    },
    },

    edit: function(props) {
      var attr = props.attributes;
      var set  = props.setAttributes;
      var blockProps = useBlockProps({ className:'ent-map-editor' });

      function addMarker(m)    { set({ markers: (attr.markers||[]).concat([m]) }); }
      function removeMarker(i) { var a=(attr.markers||[]).slice(); a.splice(i,1); set({markers:a}); }
      function updateMarker(i,key,val) {
        var a=(attr.markers||[]).slice(); a[i]=Object.assign({},a[i],{});
        a[i][key]=val; set({markers:a});
      }
      function moveMarker(i,dir) {
        var a=(attr.markers||[]).slice();
        var j=dir==='up'?i-1:i+1;
        if(j<0||j>=a.length) return;
        var tmp=a[i]; a[i]=a[j]; a[j]=tmp; set({markers:a});
      }

      return el(Fragment, null,

        el(InspectorControls, null,
          el(PanelBody, { title:'Configuración del mapa', initialOpen:true },
            el(TextControl, {
              label:'Título (opcional)',
              value:attr.heading,
              onChange:function(v){set({heading:v});},
              placeholder:'Ej: Etapas de Sicilia 2026',
            }),
            el(SelectControl, {
              label:'Altura del mapa',
              value:attr.mapHeight,
              options:[
                {label:'Pequeño (320px)',  value:'sm'},
                {label:'Mediano (480px)',  value:'md'},
                {label:'Grande (640px)',   value:'lg'},
                {label:'Extra (800px)',    value:'xl'},
              ],
              onChange:function(v){set({mapHeight:v});},
            }),
            el(SelectControl, {
              label:'Zoom inicial',
              value:attr.mapZoom,
              options:[
                {label:'Mundo (2)',      value:2},
                {label:'Continente (4)',value:4},
                {label:'País (6)',       value:6},
                {label:'Región (8)',     value:8},
                {label:'Ciudad (10)',    value:10},
                {label:'Barrio (12)',    value:12},
              ],
              onChange:function(v){set({mapZoom:parseInt(v,10)});},
            }),
            el(ToggleControl, {
              label:'Mostrar leyenda de marcadores',
              checked:attr.showLegend,
              onChange:function(v){set({showLegend:v});},
            }),
            el(ToggleControl, {
              label:'Mostrar numeración',
              checked:attr.showNumbers,
              onChange:function(v){set({showNumbers:v});},
            })
          ),

          el(PanelBody, { title:'Marcadores (' + (attr.markers||[]).length + ')', initialOpen:true },
            el(LocationSearch, { onAdd:addMarker }),
            el(MarkerList, {
              markers:  attr.markers||[],
              onRemove: removeMarker,
              onUpdate: updateMarker,
              onMove:   moveMarker,
            })
          )
        ),

        el('div', blockProps,
          (attr.markers||[]).length === 0
            ? el('div', { className:'ent-map-editor-placeholder' },
                el('div', { className:'ent-map-editor-placeholder__icon' }, '📍'),
                el('p',   { className:'ent-map-editor-placeholder__text' }, 'Mapa de localizaciones'),
                el('p',   { className:'ent-map-editor-placeholder__hint' },
                  'Busca lugares en el panel lateral → se añaden como marcadores al mapa')
              )
            : el('div', null,
                attr.heading && el('h2', {
                  style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:24,
                          letterSpacing:'.05em', textTransform:'uppercase', marginBottom:12 }
                }, attr.heading),
                el('div', {
                  style:{ background:'#e8e8e0', height:{ sm:200,md:280,lg:360,xl:440 }[attr.mapHeight]||280,
                          display:'flex', alignItems:'center', justifyContent:'center',
                          border:'1px solid #ddd', flexDirection:'column', gap:8 },
                },
                  el('span', { style:{fontSize:32} }, '🗺️'),
                  el('p', { style:{fontSize:13,color:'#666',margin:0} },
                    (attr.markers||[]).length + ' marcador' + ((attr.markers||[]).length!==1?'es':'') + ' — vista previa en el frontend'
                  )
                ),
                attr.showLegend && el('ul', { style:{listStyle:'none',padding:'8px 0',margin:0,
                                                      border:'1px solid #ddd',borderTop:'none',background:'#fff'} },
                  (attr.markers||[]).map(function(m,i){
                    return el('li', {key:i, style:{padding:'6px 14px',borderBottom:'1px solid #f0f0f0',
                                                    fontSize:13,display:'flex',gap:10,alignItems:'center'}},
                      attr.showNumbers && el('span',{style:{color:'#aaa',fontSize:11,minWidth:20}},
                        String(i+1).padStart(2,'0')),
                      el('strong',{style:{color:'#1a1a1a'}},m.name),
                      m.description && el('span',{style:{color:'#888',fontSize:12}},m.description)
                    );
                  })
                )
              )
        )
      );
    },

    save: function(){ return null; },
  });

})();
