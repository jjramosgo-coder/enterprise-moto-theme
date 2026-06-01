/**
 * enterprise/youtube-video — Editor Gutenberg
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
  var TextareaControl   = co.TextareaControl;
  var SelectControl     = co.SelectControl;

  function extractId(url) {
    var m = (url || '').match(/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    return m ? m[1] : '';
  }

  wp.blocks.registerBlockType('enterprise/youtube-video', {
    title:       'YouTube Vídeo',
    description: 'Incrusta un vídeo o Short de YouTube con contenedor estilizado.',
    icon:        'video-alt3',
    category:    'enterprise-moto',
    keywords:    ['youtube', 'video', 'short', 'embed'],
    supports:    { html: false, align: ['wide', 'full'] },

    attributes: {
      videoUrl:    { type: 'string', default: '' },
      videoTitle:  { type: 'string', default: '' },
      channel:     { type: 'string', default: '' },
      duration:    { type: 'string', default: '' },
      description: { type: 'string', default: '' },
      ratio:       { type: 'string', default: '16/9' },
      heading:     { type: 'string', default: '' },
    },

    edit: function(props) {
      var a   = props.attributes;
      var set = props.setAttributes;
      var vid = extractId(a.videoUrl);
      var thumb = vid ? 'https://img.youtube.com/vi/' + vid + '/maxresdefault.jpg' : '';
      var isShort = a.ratio === '9/16';

      return el(Frag, null,
        el(InspectorControls, null,
          el(PanelBody, { title: 'Vídeo de YouTube', initialOpen: true },
            el(TextControl, {
              label: 'URL del vídeo o Short',
              value: a.videoUrl,
              onChange: function(v){ set({ videoUrl: v.trim() }); },
              placeholder: 'https://youtube.com/watch?v=... o /shorts/...',
              type: 'url',
            }),
            vid && el('p', { style:{ fontSize:11, color:'#0a7c3e', marginTop:4 } }, '✓ ID detectado: ' + vid),
            el(SelectControl, {
              label: 'Formato',
              value: a.ratio,
              options: [
                { label: '16:9 — Vídeo horizontal', value: '16/9' },
                { label: '9:16 — Short vertical',   value: '9/16' },
                { label: '4:3 — Formato clásico',   value: '4/3'  },
              ],
              onChange: function(v){ set({ ratio: v }); },
            })
          ),
          el(PanelBody, { title: 'Metadatos', initialOpen: true },
            el(TextControl, { label: 'Título',   value: a.videoTitle, onChange: function(v){ set({ videoTitle: v }); }, placeholder: 'Título del vídeo' }),
            el(TextControl, { label: 'Canal',    value: a.channel,    onChange: function(v){ set({ channel: v }); },    placeholder: 'Nombre del canal'  }),
            el(TextControl, { label: 'Duración', value: a.duration,   onChange: function(v){ set({ duration: v }); },   placeholder: 'Ej: 2:34'          }),
            el(TextareaControl, { label: 'Descripción (opcional)', value: a.description, onChange: function(v){ set({ description: v }); }, rows: 3 }),
            el(TextControl, { label: 'Título de sección (opcional)', value: a.heading, onChange: function(v){ set({ heading: v }); }, placeholder: 'Ej: Resumen en vídeo' })
          )
        ),

        el('div', useBlockProps({ className: 'ent-yt-editor-preview' }),
          a.heading && el('h2', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:22, letterSpacing:'.06em', color:'#f2c118', marginBottom:12 } }, a.heading),
          el('div', {
              style:{
                background: '#111',
                border: '1px solid #2a2a2a',
                borderRadius: 4,
                overflow: 'hidden',
                maxWidth: isShort ? 260 : '100%',
                margin: isShort ? '0 auto' : '0',
              }
            },
            (a.channel || a.duration) && el('div', {
                style:{ display:'flex', justifyContent:'space-between', alignItems:'center',
                        padding:'10px 14px 8px', background:'#1a1a1a', borderBottom:'1px solid #2a2a2a' }
              },
              el('span', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.08em', textTransform:'uppercase', color:'#f2c118' } },
                '▶ ' + (a.channel || '')
              ),
              a.duration && el('span', { style:{ fontSize:11, color:'#888', background:'#0e0e0e', padding:'2px 7px', borderRadius:2 } }, a.duration)
            ),
            el('div', {
                style:{
                  position:'relative',
                  aspectRatio: a.ratio || '16/9',
                  background: '#000',
                  overflow: 'hidden',
                }
              },
              thumb
                ? el('img', { src: thumb, style:{ width:'100%', height:'100%', objectFit:'cover', display:'block' }, alt:'' })
                : el('div', { style:{ display:'flex', alignItems:'center', justifyContent:'center', height:'100%', minHeight:120, color:'#555', fontSize:13 } }, 'Añade la URL del vídeo'),
              thumb && el('div', {
                  style:{
                    position:'absolute', top:'50%', left:'50%',
                    transform:'translate(-50%,-50%)',
                    width:56, height:56, borderRadius:'50%',
                    background:'rgba(0,0,0,.72)', border:'2px solid rgba(255,255,255,.25)',
                    display:'flex', alignItems:'center', justifyContent:'center',
                  }
                },
                el('div', { style:{ width:0, height:0, borderStyle:'solid', borderWidth:'9px 0 9px 18px', borderColor:'transparent transparent transparent #fff', marginLeft:4 } })
              )
            ),
            (a.videoTitle || a.description) && el('div', { style:{ padding:'12px 14px', background:'#1a1a1a', borderTop:'1px solid #2a2a2a' } },
              a.videoTitle && el('div', { style:{ fontFamily:'var(--font-display,sans-serif)', fontSize:'1rem', letterSpacing:'.04em', color:'#f0f0f0', marginBottom:4 } }, a.videoTitle),
              a.description && el('div', { style:{ fontSize:12, color:'#888', lineHeight:1.5 } }, a.description)
            )
          )
        )
      );
    },

    save: function(){ return null; },
  });
})();
