/**
 * enterprise/youtube-reels — Editor Gutenberg
 */
(function () {
  'use strict';
  if (!window.wp || !wp.blocks) return;

  var el   = wp.element.createElement;
  var Frag = wp.element.Fragment;
  var be   = wp.blockEditor;
  var co   = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var ToggleControl     = co.ToggleControl;
  var RangeControl      = co.RangeControl;
  var Button            = co.Button;

  function extractId(url) {
    var m = (url || '').match(/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    return m ? m[1] : '';
  }

  /* Subcomponente: editor de un reel individual */
  function ReelItemEditor(props) {
    var item     = props.item;
    var index    = props.index;
    var onChange = props.onChange;
    var onRemove = props.onRemove;
    var vid      = extractId(item.url);
    var thumb    = vid ? 'https://img.youtube.com/vi/' + vid + '/mqdefault.jpg' : null;

    return el('div', {
        style:{ border:'1px solid #2a2a2a', borderRadius:4, padding:12, marginBottom:10, background:'#111' }
      },
      el('div', { style:{ display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:10 } },
        el('strong', { style:{ fontSize:12, color:'#f2c118' } }, 'Short ' + (index + 1) + (vid ? ' ✓' : '')),
        el(Button, { variant:'tertiary', isDestructive:true, isSmall:true, onClick:onRemove }, '✕')
      ),
      thumb && el('img', { src:thumb, style:{ width:'100%', aspectRatio:'9/16', objectFit:'cover', borderRadius:2, marginBottom:8 }, alt:'' }),
      el(TextControl, {
        label: 'URL del Short',
        value: item.url || '',
        onChange: function(v){ onChange(Object.assign({}, item, { url: v.trim() })); },
        placeholder: 'https://youtube.com/shorts/...',
        type: 'url',
      }),
      el(TextControl, {
        label: 'Título (opcional)',
        value: item.title || '',
        onChange: function(v){ onChange(Object.assign({}, item, { title: v })); },
        placeholder: 'Ej: Día 1 — Palermo',
      }),
      el(TextControl, {
        label: 'Canal (opcional)',
        value: item.channel || '',
        onChange: function(v){ onChange(Object.assign({}, item, { channel: v })); },
        placeholder: 'Ej: La Bitácora de la Enterprise',
      }),
      el(TextControl, {
        label: 'Duración (opcional)',
        value: item.duration || '',
        onChange: function(v){ onChange(Object.assign({}, item, { duration: v })); },
        placeholder: 'Ej: 0:58',
      })
    );
  }

  wp.blocks.registerBlockType('enterprise/youtube-reels', {
    title:       'YouTube Reels',
    description: 'Galería de YouTube Shorts con swipe en móvil y carrusel en desktop.',
    icon:        'smartphone',
    category:    'enterprise-moto',
    keywords:    ['youtube', 'shorts', 'reels', 'galería', 'carrusel'],
    supports:    { html: false, align: ['wide', 'full'] },

    attributes: {
      items:       { type: 'array',   default: [], items: { type: 'object' } },
      heading:     { type: 'string',  default: ''  },
      showTitles:  { type: 'boolean', default: true },
      desktopCols: { type: 'integer', default: 3   },
    },

    edit: function(props) {
      var a   = props.attributes;
      var set = props.setAttributes;

      function updateItem(idx, newItem) {
        var arr = a.items.slice(); arr[idx] = newItem; set({ items: arr });
      }
      function removeItem(idx) {
        set({ items: a.items.filter(function(_, i){ return i !== idx; }) });
      }
      function addItem() {
        set({ items: a.items.concat([{ url:'', title:'', channel:'', duration:'' }]) });
      }

      return el(Frag, null,
        el(InspectorControls, null,
          el(PanelBody, { title: 'Ajustes generales', initialOpen: true },
            el(TextControl, {
              label: 'Título de sección (opcional)',
              value: a.heading,
              onChange: function(v){ set({ heading: v }); },
              placeholder: 'Ej: Shorts del viaje',
            }),
            el(RangeControl, {
              label: 'Columnas en desktop',
              value: a.desktopCols,
              min: 2, max: 5, step: 1,
              onChange: function(v){ set({ desktopCols: v }); },
            }),
            el(ToggleControl, {
              label: 'Mostrar títulos',
              checked: a.showTitles,
              onChange: function(v){ set({ showTitles: v }); },
            })
          ),
          el(PanelBody, { title: 'Shorts (' + a.items.length + ')', initialOpen: true },
            a.items.map(function(item, i) {
              return el(ReelItemEditor, {
                key: i, item: item, index: i,
                onChange: function(ni){ updateItem(i, ni); },
                onRemove: function(){ removeItem(i); },
              });
            }),
            el(Button, {
              variant: 'primary',
              onClick: addItem,
              style: { width:'100%', justifyContent:'center', marginTop:8 },
            }, '+ Añadir Short')
          )
        ),

        el('div', useBlockProps({ className: 'ent-reels-editor-preview' }),
          a.heading && el('h2', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:22, letterSpacing:'.06em', color:'#f2c118', marginBottom:12 } }, a.heading),
          a.items.length === 0
            ? el('div', { style:{ padding:24, background:'#fff8e1', borderLeft:'3px solid #f2c118', fontSize:14, color:'#555' } },
                '▶ YouTube Reels — añade Shorts en el panel lateral.'
              )
            : el('div', { style:{ display:'grid', gridTemplateColumns:'repeat(' + Math.min(a.desktopCols, a.items.length) + ', 1fr)', gap:12 } },
                a.items.slice(0, 6).map(function(item, i) {
                  var vid   = extractId(item.url);
                  var thumb = vid ? 'https://img.youtube.com/vi/' + vid + '/mqdefault.jpg' : null;
                  return el('div', { key:i, style:{ background:'#111', border:'1px solid #2a2a2a', borderRadius:4, overflow:'hidden' } },
                    (item.channel) && el('div', { style:{ padding:'8px 12px 6px', background:'#1a1a1a', fontSize:10, fontWeight:700, letterSpacing:'.08em', textTransform:'uppercase', color:'#f2c118' } },
                      '▶ ' + item.channel
                    ),
                    el('div', { style:{ position:'relative', aspectRatio:'9/16', background:'#000', overflow:'hidden' } },
                      thumb
                        ? el('img', { src:thumb, style:{ width:'100%', height:'100%', objectFit:'cover', display:'block' }, alt:'' })
                        : el('div', { style:{ display:'flex', alignItems:'center', justifyContent:'center', height:'100%', color:'#555', fontSize:12 } }, 'Short ' + (i+1)),
                      el('div', { style:{ position:'absolute', top:'50%', left:'50%', transform:'translate(-50%,-50%)', width:40, height:40, borderRadius:'50%', background:'rgba(0,0,0,.72)', border:'2px solid rgba(255,255,255,.25)', display:'flex', alignItems:'center', justifyContent:'center' } },
                        el('div', { style:{ width:0, height:0, borderStyle:'solid', borderWidth:'7px 0 7px 14px', borderColor:'transparent transparent transparent #fff', marginLeft:3 } })
                      )
                    ),
                    a.showTitles && item.title && el('div', { style:{ padding:'8px 12px', background:'#1a1a1a', borderTop:'1px solid #2a2a2a', fontSize:11, fontFamily:'var(--font-display,sans-serif)', letterSpacing:'.04em', color:'#f0f0f0' } }, item.title)
                  );
                })
              )
        )
      );
    },

    save: function(){ return null; },
  });
})();
