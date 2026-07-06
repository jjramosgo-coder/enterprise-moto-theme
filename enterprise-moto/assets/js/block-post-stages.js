/**
 * Enterprise Moto — block-post-stages.js v1.7.0
 * Filtro por categorías, etiquetas, y fechas (año/mes).
 * Vanilla JS, sin herramientas de compilación.
 */
(function () {
  'use strict';
  if (!window.wp || !wp.blocks || !wp.element) return;

  var el        = wp.element.createElement;
  var Fragment  = wp.element.Fragment;
  var useState  = wp.element.useState;
  var useSelect = wp.data.useSelect;
  var be        = wp.blockEditor;
  var co        = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody     = co.PanelBody;
  var SelectControl = co.SelectControl;
  var RangeControl  = co.RangeControl;
  var ToggleControl = co.ToggleControl;
  var TextControl   = co.TextControl;
  var CheckboxControl = co.CheckboxControl;
  var Spinner       = co.Spinner;
  var SSR           = wp.serverSideRender;

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M4 6h16M4 10h16M4 14h10M4 18h6', stroke:'currentColor', strokeWidth:1.5, strokeLinecap:'round', fill:'none' })
  );

  /* ── CheckList reutilizable ── */
  function CheckList(props) {
    var items    = props.items || [];
    var selected = props.selected || [];
    var onChange = props.onChange;
    var loading  = props.loading;
    var _s = useState(''); var q = _s[0]; var setQ = _s[1];

    if (loading) return el('div', { style:{ display:'flex', alignItems:'center', gap:8, padding:'8px 0' } },
      el(Spinner), el('span', { style:{ fontSize:13, color:'#666' } }, 'Cargando…'));

    if (!items.length) return el('p', { style:{ fontSize:12, color:'#888', margin:'8px 0' } }, 'Sin elementos.');

    var filtered = q.length > 1
      ? items.filter(function(i){ return i.name.toLowerCase().indexOf(q.toLowerCase()) !== -1; })
      : items;

    return el(Fragment, null,
      el('input', {
        type:'text', placeholder:'Filtrar…', value:q,
        onChange:function(e){ setQ(e.target.value); },
        style:{ width:'100%', padding:'5px 8px', fontSize:12, border:'1px solid #ddd', borderRadius:2, marginBottom:8, fontFamily:'inherit' },
      }),
      el('div', { style:{ maxHeight:180, overflowY:'auto', border:'1px solid #e0e0e0', borderRadius:2, padding:'4px 8px' } },
        filtered.map(function(item) {
          return el('div', { key:item.id, style:{ padding:'4px 0', borderBottom:'1px solid #f0f0f0' } },
            el(CheckboxControl, {
              label: item.name + (item.count !== undefined ? ' ('+item.count+')' : ''),
              checked: selected.indexOf(item.id) !== -1,
              onChange: function() {
                var next = selected.indexOf(item.id) === -1
                  ? selected.concat([item.id])
                  : selected.filter(function(s){ return s !== item.id; });
                onChange(next);
              },
            })
          );
        })
      ),
      selected.length > 0 && el('p', { style:{ fontSize:11, color:'#888', marginTop:6 } },
        selected.length + ' seleccionada' + (selected.length !== 1 ? 's' : ''))
    );
  }

  wp.blocks.registerBlockType('enterprise/post-stages', {
    title:       'Etapas de ruta',
    description: 'Muestra posts filtrados por categorías, etiquetas y fechas como carrusel o timeline.',
    category:    'enterprise-moto',
    icon:        blockIcon,
    keywords:    ['carrusel','timeline','etapas','rutas','categoría','etiqueta','fecha'],
    supports:    { html:false, align:['wide','full'] },

    attributes: {
      categoryIds:    { type:'array',   default:[], items:{ type:'integer' } },
      tagIds:         { type:'array',   default:[], items:{ type:'integer' } },
      /* Filtros de fecha absoluta */
      filterDateFrom: { type:'string',  default:'' },
      filterDateTo:   { type:'string',  default:'' },
      tagRelation:    { type:'string',  default:'OR'  }, // AND | OR entre etiquetas
      /* Cantidad y orden */
      postsPerPage: { type:'integer', default:6    },
      orderBy:      { type:'string',  default:'date'   },
      order:        { type:'string',  default:'DESC'   },
      /* Presentación */
      layout:       { type:'string',  default:'carousel' },
      cardSize:     { type:'string',  default:'normal' },
      heading:      { type:'string',  default:''       },
      showExcerpt:  { type:'boolean', default:true     },
      showKm:       { type:'boolean', default:true     },
      showDate:     { type:'boolean', default:true     },
    },

    edit: function(props) {
      var attr = props.attributes;
      var set  = props.setAttributes;
      var bp   = useBlockProps({ className:'ent-block-editor' });

      var categories = useSelect(function(select) {
        return select('core').getEntityRecords('taxonomy','category',{ per_page:100, hide_empty:true, _fields:'id,name,count' });
      },[]);

      var tags = useSelect(function(select) {
        return select('core').getEntityRecords('taxonomy','post_tag',{ per_page:100, hide_empty:true, _fields:'id,name,count' });
      },[]);

      /* Resumen de filtros activos */
      function filterSummary() {
        var p = [];
        if (attr.categoryIds && attr.categoryIds.length) p.push(attr.categoryIds.length + ' cat.');
        if (attr.tagIds && attr.tagIds.length)           p.push(attr.tagIds.length + ' etiq.');
        if (attr.filterDateFrom) p.push('desde ' + attr.filterDateFrom);
        if (attr.filterDateTo)   p.push('hasta ' + attr.filterDateTo);
        return p.length ? p.join(' + ') : 'Sin filtro';
      }

      return el(Fragment, null,

        el(InspectorControls, null,

          el(PanelBody, { title:'Filtros', initialOpen:true },
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } }, 'Categorías'),
            el(CheckList, {
              items:    categories ? categories.map(function(c){ return { id:c.id, name:c.name, count:c.count }; }) : [],
              selected: attr.categoryIds || [],
              loading:  !categories,
              onChange: function(v){ set({ categoryIds:v }); },
            }),

            el('div', { style:{ borderTop:'1px solid #e0e0e0', paddingTop:16, marginTop:16 } },
              el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } }, 'Etiquetas'),
              el(CheckList, {
                items:    tags ? tags.map(function(t){ return { id:t.id, name:t.name, count:t.count }; }) : [],
                selected: attr.tagIds || [],
                loading:  !tags,
                onChange: function(v){ set({ tagIds:v }); },
              }),
              (attr.tagIds && attr.tagIds.length > 1) && el('div', { style:{ marginTop:10 } },
                el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.08em', color:'#1e1e1e', marginBottom:6 } },
                  'Relación entre etiquetas'
                ),
                el('div', { style:{ display:'flex', gap:8 } },
                  ['OR','AND'].map(function(rel) {
                    return el('button', {
                      key: rel,
                      onClick: function(){ set({ tagRelation: rel }); },
                      style: {
                        padding:'5px 14px', fontSize:11, fontWeight:700,
                        border: '1px solid ' + (attr.tagRelation === rel ? '#0e0e0e' : '#ddd'),
                        background: attr.tagRelation === rel ? '#0e0e0e' : '#fff',
                        color: attr.tagRelation === rel ? '#fff' : '#555',
                        cursor:'pointer', letterSpacing:'.08em',
                      }
                    }, rel);
                  })
                ),
                el('p', { style:{ fontSize:11, color:'#888', marginTop:4 } },
                  attr.tagRelation === 'AND'
                    ? 'AND: el post debe tener TODAS las etiquetas seleccionadas'
                    : 'OR: el post puede tener CUALQUIERA de las etiquetas'
                )
              )
            ),

            el('div', { style:{ borderTop:'1px solid #e0e0e0', paddingTop:16, marginTop:16 } },
              el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.1em', textTransform:'uppercase', color:'#1e1e1e', marginBottom:8 } }, 'Fecha'),
              el('div', { style:{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:8 } },
                el('div', null,
                  el('label', { style:{ display:'block', fontSize:11, fontWeight:600, color:'#555', marginBottom:4 } }, 'Desde'),
                  el('input', {
                    type:'date',
                    value: attr.filterDateFrom || '',
                    style:{ width:'100%', fontSize:12, padding:'6px 8px', border:'1px solid #ddd', boxSizing:'border-box' },
                    onChange: function(e){ set({ filterDateFrom: e.target.value }); },
                  })
                ),
                el('div', null,
                  el('label', { style:{ display:'block', fontSize:11, fontWeight:600, color:'#555', marginBottom:4 } }, 'Hasta'),
                  el('input', {
                    type:'date',
                    value: attr.filterDateTo || '',
                    style:{ width:'100%', fontSize:12, padding:'6px 8px', border:'1px solid #ddd', boxSizing:'border-box' },
                    onChange: function(e){ set({ filterDateTo: e.target.value }); },
                  })
                )
              )
            ),

            el('div', { style:{ marginTop:12, padding:'8px 10px', background:'#f0f0f0', borderRadius:2, fontSize:11, color:'#555' } },
              el('strong', null, 'Filtro activo: '), filterSummary()
            )
          ),

          el(PanelBody, { title:'Cantidad y orden', initialOpen:false },
            el(RangeControl, {
              label:'Número de posts', value:attr.postsPerPage, min:1, max:24, step:1,
              onChange:function(v){ set({ postsPerPage:v }); },
            }),
            el(SelectControl, {
              label:'Ordenar por', value:attr.orderBy,
              options:[
                { label:'Fecha de publicación', value:'date' },
                { label:'Título (A-Z)',          value:'title' },
                { label:'Orden personalizado',   value:'menu_order' },
              ],
              onChange:function(v){ set({ orderBy:v }); },
            }),
            el(SelectControl, {
              label:'Dirección', value:attr.order,
              options:[
                { label:'Descendente (más reciente primero)', value:'DESC' },
                { label:'Ascendente (más antiguo primero)',   value:'ASC'  },
              ],
              onChange:function(v){ set({ order:v }); },
            })
          ),

          el(PanelBody, { title:'Presentación', initialOpen:false },
            el(TextControl, {
              label:'Título de sección (opcional)', value:attr.heading,
              onChange:function(v){ set({ heading:v }); },
              placeholder:'Ej: Etapas de Sicilia 2026',
            }),
            el(SelectControl, {
              label:'Diseño', value:attr.layout,
              options:[
                { label:'⟵  Carrusel horizontal', value:'carousel' },
                { label:'↓  Timeline vertical',   value:'timeline'  },
              ],
              onChange:function(v){ set({ layout:v }); },
            }),
            attr.layout === 'carousel' && el(SelectControl, {
              label:'Tamaño de tarjeta', value:attr.cardSize,
              options:[
                { label:'Normal (3 por fila)', value:'normal' },
                { label:'Grande (2 por fila)', value:'large'  },
              ],
              onChange:function(v){ set({ cardSize:v }); },
            })
          ),

          el(PanelBody, { title:'Campos visibles', initialOpen:false },
            el(ToggleControl, { label:'Extracto',   checked:attr.showExcerpt, onChange:function(v){set({showExcerpt:v})} }),
            el(ToggleControl, { label:'Kilómetros', checked:attr.showKm,      onChange:function(v){set({showKm:v})} }),
            el(ToggleControl, { label:'Fecha',      checked:attr.showDate,    onChange:function(v){set({showDate:v})} })
          )
        ),

        el('div', bp,
          el(SSR, {
            block:'enterprise/post-stages', attributes:attr,
            EmptyResponsePlaceholder:function() {
              return el('div', { className:'ent-block-placeholder' },
                el('div', { className:'ent-block-placeholder__icon' }, '🏍️'),
                el('p',   { className:'ent-block-placeholder__text' },
                  'Etapas de ruta — ' + (attr.layout === 'carousel' ? 'Carrusel' : 'Timeline')),
                el('p', { className:'ent-block-placeholder__hint' }, filterSummary())
              );
            },
          })
        )
      );
    },

    save: function(){ return null; },
  });

  if (wp.blocks.registerBlockCollection) {
    wp.blocks.registerBlockCollection('enterprise-moto', { title:'Enterprise Moto', icon:blockIcon });
  }

  var s = document.createElement('style');
  s.textContent = [
    '.ent-block-editor{font-family:sans-serif;}',
    '.ent-block-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 24px;background:#f5f5f2;border:2px dashed #e2e2de;text-align:center;gap:8px;}',
    '.ent-block-placeholder__icon{font-size:40px;}',
    '.ent-block-placeholder__text{font-size:15px;font-weight:600;color:#1a1a1a;margin:0;}',
    '.ent-block-placeholder__hint{font-size:12px;color:#888;margin:0;}',
  ].join('');
  document.head.appendChild(s);

})();
