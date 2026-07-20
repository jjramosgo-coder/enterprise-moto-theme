/**
 * Enterprise Moto — block-routes-by-location.js
 * Bloque Gutenberg «Mapa de rutas por localización» (enterprise/routes-by-location).
 *
 * COMMIT 2 (editor): el lienzo muestra una vista previa ligera (recuadro + recuento);
 * un botón «Gestionar localizaciones (N)» abre un wp.components.Modal con:
 *   - mapa OpenLayers interactivo (alta por clic / edición al pulsar un marcador),
 *   - buscador Nominatim (copiado de block-location-map.js §1.2, alta por nombre),
 *   - lista propia BUSCABLE/PAGINADA con edición, borrado y borrado múltiple
 *     (fallback del §5: DataViews/DataForm NO están disponibles como globales wp.*).
 * Cada localización guarda un filtro compuesto por IDs de término:
 *   (cat_1 OR … OR cat_n) AND (tag_1 OR … OR tag_m).
 * OpenLayers se carga BAJO DEMANDA al abrir el Modal (mount-on-open). El estado del
 * Modal es local y se confirma en `markers` al guardar o cerrar.
 *
 * Vanilla JS, sin herramientas de compilación.
 */
(function () {
  'use strict';

  if ( ! window.wp || ! wp.blocks || ! wp.element ) return;

  var el        = wp.element.createElement;
  var Fragment  = wp.element.Fragment;
  var useState  = wp.element.useState;
  var useEffect = wp.element.useEffect;
  var useRef    = wp.element.useRef;
  var be        = wp.blockEditor;
  var co        = wp.components;
  var apiFetch  = wp.apiFetch;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var TextareaControl   = co.TextareaControl;
  var SelectControl     = co.SelectControl;
  var ToggleControl     = co.ToggleControl;
  var CheckboxControl   = co.CheckboxControl;
  var FormTokenField    = co.FormTokenField;
  var Button            = co.Button;
  var Modal             = co.Modal;
  var Spinner           = co.Spinner;
  var Notice            = co.Notice;

  var OL_CSS = 'https://cdn.jsdelivr.net/npm/ol@9.2.4/ol.css';
  var OL_JS  = 'https://cdn.jsdelivr.net/npm/ol@9.2.4/dist/ol.js';
  var PAGE_SIZE = 10;

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z',
      fill:'currentColor' })
  );

  /* ═══════════════════════════════════════════
     CARGA BAJO DEMANDA DE OPENLAYERS (mount-on-open)
  ═══════════════════════════════════════════ */
  function ensureOpenLayers( cb ) {
    if ( window.ol ) { cb(); return; }
    if ( ! document.getElementById('ent-ol-css') ) {
      var link = document.createElement('link');
      link.id = 'ent-ol-css'; link.rel = 'stylesheet'; link.href = OL_CSS;
      document.head.appendChild(link);
    }
    var existing = document.getElementById('ent-ol-js');
    if ( existing ) {
      if ( window.ol ) { cb(); return; }
      existing.addEventListener('load', function(){ cb(); });
      return;
    }
    var s = document.createElement('script');
    s.id = 'ent-ol-js'; s.src = OL_JS; s.async = true;
    s.onload = function(){ cb(); };
    document.head.appendChild(s);
  }

  /* ═══════════════════════════════════════════
     GEOCODER NOMINATIM (copiado de block-location-map.js §1.2)
  ═══════════════════════════════════════════ */
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

  /* Componente buscador: nombre → coordenadas → onAdd({name,lat,lng}) */
  function LocationSearch( props ) {
    var onAdd = props.onAdd;

    var _q = useState('');    var query = _q[0];      var setQuery = _q[1];
    var _r = useState([]);    var results = _r[0];    var setResults = _r[1];
    var _l = useState(false); var loading = _l[0];    var setLoading = _l[1];
    var _e = useState('');    var error = _e[0];      var setError = _e[1];
    var _n = useState('');    var customName = _n[0]; var setCustomName = _n[1];
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
        lat:  result.lat,
        lng:  result.lng,
      });
      setQuery(''); setResults([]); setCustomName(''); setError('');
    }

    return el('div', { style:{ marginBottom:12 } },
      el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em',
                        textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } },
        'Añadir por nombre'
      ),
      el('div', { style:{ display:'flex', gap:6, marginBottom:6 } },
        el('input', {
          type:'text', value:query,
          placeholder: 'Buscar lugar (ej: Tourmalet)…',
          onChange: function(e){ setQuery(e.target.value); },
          onKeyDown: function(e){ if(e.key==='Enter'){e.preventDefault();doSearch();} },
          style:{ flex:1, padding:'7px 10px', fontSize:13, border:'1px solid #ddd',
                  borderRadius:2, fontFamily:'inherit' },
        }),
        el('button', {
          type:'button', onClick: doSearch, disabled: query.length < 2 || loading,
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
                           border:'1px solid #ddd', maxHeight:160, overflowY:'auto' } },
          results.map(function(r, i){
            return el('li', { key:i },
              el('button', {
                type:'button', onClick: function(){ handleSelect(r); },
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

  /* ═══════════════════════════════════════════
     NORMALIZACIÓN DE MARCADOR
  ═══════════════════════════════════════════ */
  function normalizeMarker( m ) {
    m = m || {};
    return {
      name:         typeof m.name === 'string' ? m.name : '',
      lat:          typeof m.lat === 'number' ? m.lat : parseFloat(m.lat) || 0,
      lng:          typeof m.lng === 'number' ? m.lng : parseFloat(m.lng) || 0,
      description:  typeof m.description === 'string' ? m.description : '',
      filterCatIds: Array.isArray(m.filterCatIds) ? m.filterCatIds.map(function(v){return parseInt(v,10);}).filter(function(v){return !isNaN(v);}) : [],
      filterTagIds: Array.isArray(m.filterTagIds) ? m.filterTagIds.map(function(v){return parseInt(v,10);}).filter(function(v){return !isNaN(v);}) : [],
    };
  }

  /* ═══════════════════════════════════════════
     GESTOR DE LOCALIZACIONES (contenido del Modal)
     Montado solo al abrir → carga OL, lee términos, edita en borrador local.
  ═══════════════════════════════════════════ */
  function MarkerManager( props ) {
    var onSave  = props.onSave;   // (markers) => void
    var onClose = props.onClose;  // () => void

    var _d = useState( (props.initial || []).map(normalizeMarker) );
    var draft = _d[0]; var setDraft = _d[1];

    var _sel = useState(-1);      var selected = _sel[0];    var setSelected = _sel[1];
    var _pg  = useState(0);       var page = _pg[0];         var setPage = _pg[1];
    var _sq  = useState('');      var q = _sq[0];            var setQ = _sq[1];
    var _ck  = useState({});      var checked = _ck[0];      var setChecked = _ck[1];

    var _cats = useState([]);     var cats = _cats[0];       var setCats = _cats[1];
    var _tags = useState([]);     var tags = _tags[0];       var setTags = _tags[1];
    var _tl  = useState(false);   var termsLoaded = _tl[0];  var setTermsLoaded = _tl[1];
    var _te  = useState(false);   var termsError = _te[0];   var setTermsError = _te[1];
    var _ol  = useState(false);   var olReady = _ol[0];      var setOlReady = _ol[1];

    var mapEl   = useRef(null);
    var mapRef  = useRef(null);
    var srcRef  = useRef(null);
    var draftRef = useRef(draft);
    var selRef   = useRef(selected);
    var clickRef = useRef(null);
    draftRef.current = draft;
    selRef.current   = selected;

    /* ── Lookups término ↔ id ── */
    var catNameById = {}, catIdByName = {};
    cats.forEach(function(c){ catNameById[c.id] = c.name; catIdByName[c.name] = c.id; });
    var tagNameById = {}, tagIdByName = {};
    tags.forEach(function(t){ tagNameById[t.id] = t.name; tagIdByName[t.name] = t.id; });

    /* ── Cargar OpenLayers ── */
    useEffect(function(){
      var cancelled = false;
      ensureOpenLayers(function(){ if(!cancelled) setOlReady(true); });
      return function(){ cancelled = true; };
    }, []);

    /* ── Leer categorías y etiquetas por REST ── */
    useEffect(function(){
      var cancelled = false;
      Promise.all([
        apiFetch({ path: '/wp/v2/categories?per_page=100&_fields=id,name&orderby=name&order=asc' }),
        apiFetch({ path: '/wp/v2/tags?per_page=100&_fields=id,name&orderby=name&order=asc' })
      ]).then(function(res){
        if (cancelled) return;
        setCats( Array.isArray(res[0]) ? res[0] : [] );
        setTags( Array.isArray(res[1]) ? res[1] : [] );
        setTermsLoaded(true);
      }).catch(function(){
        if (cancelled) return;
        setTermsError(true); setTermsLoaded(true);
      });
      return function(){ cancelled = true; };
    }, []);

    /* ── Estilos de pin (normal / seleccionado) ── */
    function pinStyle( isSel ) {
      return new window.ol.style.Style({
        image: new window.ol.style.Circle({
          radius: isSel ? 9 : 6,
          fill:   new window.ol.style.Fill({ color: isSel ? '#f2c118' : '#0e0e0e' }),
          stroke: new window.ol.style.Stroke({ color: isSel ? '#0e0e0e' : '#f2c118', width: 2 }),
        }),
      });
    }

    /* ── Redibujar features desde el borrador ── */
    function redrawFeatures() {
      var ol = window.ol;
      if (!ol || !srcRef.current) return;
      srcRef.current.clear();
      var feats = [];
      draftRef.current.forEach(function(m, i){
        if (isNaN(m.lat) || isNaN(m.lng)) return;
        var f = new ol.Feature({
          geometry: new ol.geom.Point(ol.proj.fromLonLat([m.lng, m.lat])),
          _idx: i,
        });
        f.setStyle(pinStyle(i === selRef.current));
        feats.push(f);
      });
      srcRef.current.addFeatures(feats);
    }

    /* ── Construir el mapa una vez OL esté listo y el div montado ── */
    useEffect(function(){
      var ol = window.ol;
      if (!olReady || !ol || !mapEl.current || mapRef.current) return;

      var src = new ol.source.Vector();
      var vector = new ol.layer.Vector({ source: src });
      var map = new ol.Map({
        target: mapEl.current,
        layers: [ new ol.layer.Tile({ source: new ol.source.OSM() }), vector ],
        view: new ol.View({ center: ol.proj.fromLonLat([2, 42]), zoom: 4 }),
      });
      mapRef.current = map;
      srcRef.current = src;

      map.on('click', function(e){
        if (clickRef.current) clickRef.current(e);
      });
      map.on('pointermove', function(e){
        map.getTargetElement().style.cursor = map.hasFeatureAtPixel(e.pixel) ? 'pointer' : '';
      });

      redrawFeatures();
      fitToMarkers();
      // OpenLayers necesita recalcular tamaño dentro del Modal
      setTimeout(function(){ if (mapRef.current) mapRef.current.updateSize(); }, 60);
      setTimeout(function(){ if (mapRef.current) mapRef.current.updateSize(); }, 300);

      return function(){
        if (mapRef.current) { mapRef.current.setTarget(null); mapRef.current = null; srcRef.current = null; }
      };
    }, [olReady]);

    function fitToMarkers() {
      var ol = window.ol;
      if (!ol || !mapRef.current || !srcRef.current) return;
      var ext = srcRef.current.getExtent();
      if (ext && isFinite(ext[0]) && ext[0] !== Infinity) {
        if (draftRef.current.length === 1) {
          mapRef.current.getView().setCenter( srcRef.current.getFeatures()[0].getGeometry().getCoordinates() );
          mapRef.current.getView().setZoom(10);
        } else {
          mapRef.current.getView().fit(ext, { size: mapRef.current.getSize(), padding:[40,40,40,40], maxZoom:12 });
        }
      }
    }

    /* ── Redibujar cuando cambia el borrador o la selección ── */
    useEffect(function(){ redrawFeatures(); }, [draft, selected, olReady]);

    /* ── Handler de clic en el mapa (vía ref, evita closures obsoletas) ── */
    clickRef.current = function(e) {
      var ol = window.ol;
      var hit = mapRef.current.forEachFeatureAtPixel(e.pixel, function(x){ return x; });
      if (hit && typeof hit.get('_idx') === 'number') {
        var idx = hit.get('_idx');
        setSelected(idx);
        setPage(Math.floor(idx / PAGE_SIZE));
      } else {
        var lonlat = ol.proj.toLonLat(e.coordinate);
        var m = normalizeMarker({ name:'', lat: lonlat[1], lng: lonlat[0] });
        var newIdx = draftRef.current.length;
        setDraft( draftRef.current.concat([m]) );
        setSelected(newIdx);
        setPage(Math.floor(newIdx / PAGE_SIZE));
      }
    };

    /* ── Operaciones sobre el borrador ── */
    function addFromSearch( r ) {
      var m = normalizeMarker({ name: r.name, lat: r.lat, lng: r.lng });
      var newIdx = draft.length;
      setDraft( draft.concat([m]) );
      setSelected(newIdx);
      setPage(Math.floor(newIdx / PAGE_SIZE));
      if (mapRef.current) {
        setTimeout(function(){
          mapRef.current.getView().animate({ center: window.ol.proj.fromLonLat([m.lng, m.lat]), zoom: 10, duration: 300 });
        }, 30);
      }
    }
    function updateSelected( patch ) {
      if (selected < 0) return;
      setDraft( draft.map(function(m, j){ return j === selected ? Object.assign({}, m, patch) : m; }) );
    }
    function removeAt( i ) {
      setDraft( draft.filter(function(_, j){ return j !== i; }) );
      if (selected === i) setSelected(-1);
      else if (selected > i) setSelected(selected - 1);
      var nc = Object.assign({}, checked); delete nc[i]; setChecked(nc);
    }
    function toggleCheck( i ) {
      var nc = Object.assign({}, checked);
      if (nc[i]) delete nc[i]; else nc[i] = true;
      setChecked(nc);
    }
    function bulkDelete() {
      var keep = draft.filter(function(_, j){ return !checked[j]; });
      setDraft(keep); setChecked({}); setSelected(-1); setPage(0);
    }

    function commitAndClose() {
      onSave( draft.map(normalizeMarker) );
      onClose();
    }

    /* ── Lista filtrada + paginada ── */
    var indexed = draft.map(function(m, i){ return { m:m, i:i }; });
    var filtered = q
      ? indexed.filter(function(x){ return (x.m.name || '').toLowerCase().indexOf(q.toLowerCase()) !== -1; })
      : indexed;
    var totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    var curPage = Math.min(page, totalPages - 1);
    var pageRows = filtered.slice(curPage * PAGE_SIZE, curPage * PAGE_SIZE + PAGE_SIZE);
    var checkedCount = Object.keys(checked).length;

    var sel = selected >= 0 && selected < draft.length ? draft[selected] : null;

    /* ── Formulario del marcador seleccionado ── */
    function renderForm() {
      if (!sel) {
        return el('p', { style:{ fontSize:12, color:'#888', margin:0 } },
          'Selecciona una localización de la lista, pulsa un pin del mapa, o añade una nueva.');
      }
      var selCatNames = (sel.filterCatIds || []).map(function(id){ return catNameById[id]; }).filter(Boolean);
      var selTagNames = (sel.filterTagIds || []).map(function(id){ return tagNameById[id]; }).filter(Boolean);

      return el('div', null,
        el('div', { style:{ display:'flex', justifyContent:'space-between', alignItems:'baseline', marginBottom:8 } },
          el('strong', { style:{ fontSize:12 } }, 'Editar: ' + (sel.name || '(sin nombre)')),
          el('span', { style:{ fontSize:11, color:'#999' } },
            (sel.lat).toFixed(5) + ', ' + (sel.lng).toFixed(5))
        ),
        el(TextControl, {
          label: 'Nombre', value: sel.name,
          onChange: function(v){ updateSelected({ name: v }); },
          placeholder: 'Ej: Col du Tourmalet',
        }),
        el(TextareaControl, {
          label: 'Descripción (opcional)', value: sel.description, rows: 2,
          onChange: function(v){ updateSelected({ description: v }); },
        }),
        el('div', { style:{ display:'flex', gap:8 } },
          el(TextControl, {
            label: 'Latitud', type:'number', value: sel.lat,
            onChange: function(v){ updateSelected({ lat: parseFloat(v) || 0 }); },
          }),
          el(TextControl, {
            label: 'Longitud', type:'number', value: sel.lng,
            onChange: function(v){ updateSelected({ lng: parseFloat(v) || 0 }); },
          })
        ),
        !termsLoaded && el('div', { style:{ display:'flex', alignItems:'center', gap:6, fontSize:12, color:'#888', margin:'8px 0' } },
          el(Spinner), 'Cargando categorías y etiquetas…'),
        termsError && el(Notice, { status:'error', isDismissible:false },
          'No se pudieron cargar las categorías/etiquetas.'),
        termsLoaded && !termsError && el(Fragment, null,
          el(FormTokenField, {
            label: 'Categorías (OR entre ellas)',
            value: selCatNames,
            suggestions: cats.map(function(c){ return c.name; }),
            onChange: function(tokens){
              var ids = tokens.map(function(t){ return catIdByName[t]; }).filter(function(v){ return typeof v === 'number'; });
              updateSelected({ filterCatIds: ids });
            },
            __experimentalExpandOnFocus: true,
          }),
          el(FormTokenField, {
            label: 'Etiquetas (OR entre ellas)',
            value: selTagNames,
            suggestions: tags.map(function(t){ return t.name; }),
            onChange: function(tokens){
              var ids = tokens.map(function(t){ return tagIdByName[t]; }).filter(function(v){ return typeof v === 'number'; });
              updateSelected({ filterTagIds: ids });
            },
            __experimentalExpandOnFocus: true,
          }),
          el('p', { style:{ fontSize:11, color:'#888', margin:'4px 0 0' } },
            'Filtro: (categorías) Y (etiquetas). Deja un grupo vacío para no restringir por él.')
        ),
        el('div', { style:{ marginTop:10 } },
          el(Button, { isDestructive:true, variant:'secondary', onClick: function(){ removeAt(selected); } },
            'Eliminar esta localización')
        )
      );
    }

    return el('div', { className:'ent-rbl-manager' },

      termsError && el(Notice, { status:'warning', isDismissible:false },
        'Aviso: no se pudieron leer las taxonomías por REST; podrás editar nombre y coordenadas, pero no el filtro.'),

      el('div', { style:{ display:'flex', gap:20, flexWrap:'wrap', alignItems:'flex-start' } },

        /* ── Columna izquierda: buscador + mapa ── */
        el('div', { style:{ flex:'1 1 380px', minWidth:320 } },
          el(LocationSearch, { onAdd: addFromSearch }),
          !olReady && el('div', { style:{ display:'flex', alignItems:'center', gap:8, height:380,
                                          justifyContent:'center', background:'#e8e8e0', border:'1px solid #ddd' } },
            el(Spinner), el('span', { style:{ fontSize:12, color:'#666' } }, 'Cargando mapa…')),
          el('div', {
            ref: mapEl,
            style:{ height:380, width:'100%', border:'1px solid #ddd', display: olReady ? 'block' : 'none' },
          }),
          el('p', { style:{ fontSize:11, color:'#888', margin:'6px 0 0' } },
            'Pulsa en el mapa para añadir una localización; pulsa un pin para editarla.')
        ),

        /* ── Columna derecha: formulario ── */
        el('div', { style:{ flex:'1 1 320px', minWidth:300,
                            background:'#fbfbfa', border:'1px solid #eee', padding:14 } },
          renderForm()
        )
      ),

      /* ── Lista buscable / paginada (fallback §5) ── */
      el('div', { style:{ marginTop:18, borderTop:'1px solid #eee', paddingTop:14 } },
        el('div', { style:{ display:'flex', gap:10, alignItems:'center', marginBottom:8, flexWrap:'wrap' } },
          el('strong', { style:{ fontSize:12 } }, 'Localizaciones (' + draft.length + ')'),
          el('input', {
            type:'text', value:q,
            placeholder:'Filtrar por nombre…',
            onChange: function(e){ setQ(e.target.value); setPage(0); },
            style:{ flex:'1 1 200px', padding:'6px 10px', fontSize:13, border:'1px solid #ddd',
                    borderRadius:2, fontFamily:'inherit' },
          }),
          checkedCount > 0 && el(Button, { isDestructive:true, variant:'secondary', onClick: bulkDelete },
            'Eliminar seleccionadas (' + checkedCount + ')')
        ),

        draft.length === 0
          ? el('p', { style:{ fontSize:12, color:'#888', margin:'8px 0' } },
              'Sin localizaciones todavía.')
          : el('table', { style:{ width:'100%', borderCollapse:'collapse', fontSize:13 } },
              el('thead', null,
                el('tr', { style:{ textAlign:'left', color:'#666', fontSize:11, textTransform:'uppercase', letterSpacing:'.05em' } },
                  el('th', { style:{ padding:'4px 6px', width:28 } }, ''),
                  el('th', { style:{ padding:'4px 6px' } }, 'Nombre'),
                  el('th', { style:{ padding:'4px 6px', width:90 } }, 'Filtro'),
                  el('th', { style:{ padding:'4px 6px', width:120 } }, 'Coordenadas'),
                  el('th', { style:{ padding:'4px 6px', width:110 } }, '')
                )
              ),
              el('tbody', null,
                pageRows.map(function(x){
                  var i = x.i, m = x.m;
                  var nCat = (m.filterCatIds||[]).length, nTag = (m.filterTagIds||[]).length;
                  return el('tr', { key:i,
                    style:{ borderTop:'1px solid #f0f0f0', background: i === selected ? '#fff7d6' : 'transparent' } },
                    el('td', { style:{ padding:'4px 6px' } },
                      el(CheckboxControl, { checked: !!checked[i], onChange: function(){ toggleCheck(i); } })),
                    el('td', { style:{ padding:'4px 6px' } }, m.name || el('em', { style:{ color:'#aaa' } }, '(sin nombre)')),
                    el('td', { style:{ padding:'4px 6px', color:'#666' } }, nCat + 'c / ' + nTag + 't'),
                    el('td', { style:{ padding:'4px 6px', color:'#888', fontSize:11 } },
                      m.lat.toFixed(3) + ', ' + m.lng.toFixed(3)),
                    el('td', { style:{ padding:'4px 6px', textAlign:'right' } },
                      el(Button, { variant:'tertiary', size:'small',
                        onClick: function(){ setSelected(i); } }, 'Editar'),
                      el(Button, { variant:'tertiary', size:'small', isDestructive:true,
                        onClick: function(){ removeAt(i); } }, '×'))
                  );
                })
              )
            ),

        totalPages > 1 && el('div', { style:{ display:'flex', gap:8, alignItems:'center', justifyContent:'center', marginTop:10 } },
          el(Button, { variant:'secondary', size:'small', disabled: curPage === 0,
            onClick: function(){ setPage(curPage - 1); } }, '‹ Anterior'),
          el('span', { style:{ fontSize:12, color:'#666' } }, 'Página ' + (curPage + 1) + ' de ' + totalPages),
          el(Button, { variant:'secondary', size:'small', disabled: curPage >= totalPages - 1,
            onClick: function(){ setPage(curPage + 1); } }, 'Siguiente ›')
        )
      ),

      /* ── Pie: guardar y cerrar ── */
      el('div', { style:{ display:'flex', justifyContent:'flex-end', gap:8, marginTop:18,
                          borderTop:'1px solid #eee', paddingTop:14 } },
        el(Button, { variant:'primary', onClick: commitAndClose }, 'Guardar y cerrar')
      )
    );
  }

  /* ═══════════════════════════════════════════
     REGISTRO DEL BLOQUE
  ═══════════════════════════════════════════ */
  wp.blocks.registerBlockType('enterprise/routes-by-location', {

    title:       'Mapa de rutas por localización',
    description: 'Mapa con localizaciones; cada una enlaza a las entradas (viaje / etapa / jornada) que casan su filtro compuesto de categorías y etiquetas.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['mapa','map','localizaciones','rutas','filtro','entradas'],
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
      var attr       = props.attributes;
      var set        = props.setAttributes;
      var blockProps = useBlockProps({ className:'ent-map-editor' });
      var _o = useState(false); var isOpen = _o[0]; var setOpen = _o[1];

      var markers = attr.markers || [];
      var count   = markers.length;

      return el(Fragment, null,

        el(InspectorControls, null,
          el(PanelBody, { title:'Configuración del mapa', initialOpen:true },
            el(TextControl, {
              label:'Título (opcional)', value:attr.heading,
              onChange:function(v){set({heading:v});},
              placeholder:'Ej: Rutas por localización',
            }),
            el(SelectControl, {
              label:'Altura del mapa', value:attr.mapHeight,
              options:[
                {label:'Pequeño (320px)', value:'sm'},
                {label:'Mediano (480px)', value:'md'},
                {label:'Grande (640px)',  value:'lg'},
                {label:'Extra (800px)',   value:'xl'},
              ],
              onChange:function(v){set({mapHeight:v});},
            }),
            el(SelectControl, {
              label:'Zoom inicial', value:attr.mapZoom,
              options:[
                {label:'Mundo (2)',       value:2},
                {label:'Continente (4)',  value:4},
                {label:'País (6)',        value:6},
                {label:'Región (8)',      value:8},
                {label:'Ciudad (10)',     value:10},
                {label:'Barrio (12)',     value:12},
              ],
              onChange:function(v){set({mapZoom:parseInt(v,10)});},
            }),
            el(ToggleControl, {
              label:'Mostrar leyenda de localizaciones', checked:attr.showLegend,
              onChange:function(v){set({showLegend:v});},
            }),
            el(ToggleControl, {
              label:'Mostrar numeración', checked:attr.showNumbers,
              onChange:function(v){set({showNumbers:v});},
            })
          )
        ),

        el('div', blockProps,
          /* Vista previa ligera (sin mapa vivo en el lienzo) */
          el('div', { style:{ border:'1px solid #ddd', background:'#f3f3ee', padding:'18px 16px' } },
            attr.heading && el('h2', {
              style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:22,
                      letterSpacing:'.05em', textTransform:'uppercase', margin:'0 0 10px' }
            }, attr.heading),
            el('div', { style:{ display:'flex', alignItems:'center', gap:12 } },
              el('span', { style:{ fontSize:30 } }, '🧭'),
              el('div', null,
                el('p', { style:{ margin:0, fontSize:14, fontWeight:600, color:'#1a1a1a' } },
                  'Mapa de rutas por localización'),
                el('p', { style:{ margin:'2px 0 0', fontSize:12, color:'#666' } },
                  count === 0
                    ? 'Sin localizaciones — vista previa en el frontend'
                    : count + ' localización' + (count!==1?'es':'') + ' — vista previa en el frontend')
              )
            ),
            count > 0 && el('p', { style:{ margin:'10px 0 0', fontSize:12, color:'#555', lineHeight:1.5 } },
              markers.slice(0, 8).map(function(m){ return m.name || '(sin nombre)'; }).join('  ·  ')
              + (count > 8 ? '  ·  …' : '')),
            el('div', { style:{ marginTop:14 } },
              el(Button, { variant:'primary', onClick: function(){ setOpen(true); } },
                'Gestionar localizaciones (' + count + ')'))
          ),

          isOpen && el(Modal, {
            title: 'Gestionar localizaciones',
            onRequestClose: function(){ setOpen(false); },
            size: 'large',
            shouldCloseOnClickOutside: false,
          },
            el(MarkerManager, {
              initial: markers,
              onSave: function(newMarkers){ set({ markers: newMarkers }); },
              onClose: function(){ setOpen(false); },
            })
          )
        )
      );
    },

    save: function(){ return null; },
  });

})();
