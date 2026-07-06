/**
 * enterprise/markdown + enterprise/markdown-styled
 * Editor Gutenberg — JS compartido.
 * Preview en el editor usando marked.js (cargado desde CDN solo en el editor).
 */
(function () {
  'use strict';
  if (!window.wp || !wp.blocks) return;

  var el    = wp.element.createElement;
  var useState = wp.element.useState;
  var Frag  = wp.element.Fragment;
  var be    = wp.blockEditor;
  var co    = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextareaControl   = co.TextareaControl;
  var SelectControl     = co.SelectControl;
  var RangeControl      = co.RangeControl;
  var ColorPicker       = co.ColorPicker;
  var ToggleControl     = co.ToggleControl;
  var TabPanel          = co.TabPanel;
  var Button            = co.Button;

  /* ── Preview con marked.js ── */
  var markedLoaded = false;
  var markedCallbacks = [];

  function loadMarked(cb) {
    if (window.marked) { cb(); return; }
    if (markedLoaded) { markedCallbacks.push(cb); return; }
    markedLoaded = true;
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/marked@9/marked.min.js';
    s.onload = function() {
      cb();
      markedCallbacks.forEach(function(fn){ fn(); });
      markedCallbacks = [];
    };
    document.head.appendChild(s);
  }

  /* ── Componente de preview ── */
  function MarkdownPreview(props) {
    var content = props.content;
    var style   = props.style || {};
    var html = '';
    if (window.marked && content) {
      try { html = window.marked.parse(content); } catch(e) {}
    }
    return el('div', {
      className: 'ent-markdown-preview',
      style: Object.assign({
        padding: 16,
        background: '#ffffff',
        color: '#1a1a1a',
        border: '1px solid #ddd',
        borderRadius: 3,
        minHeight: 80,
        fontSize: 14,
        lineHeight: 1.7,
      }, style),
      dangerouslySetInnerHTML: { __html: html || '<em style="color:#aaa">Sin contenido todavía…</em>' },
    });
  }

  /* ── Editor compartido ── */
  function MarkdownEditor(props) {
    var a   = props.attributes;
    var set = props.setAttributes;

    // Forzar re-render cuando marked carga
    var forceUpdate = wp.element.useReducer(function(x){ return x+1; }, 0)[1];
    wp.element.useEffect(function(){
      if (!window.marked) loadMarked(function(){ forceUpdate(); });
    }, []);

    var previewStyle = props.previewStyle || {};

    return el('div', null,
      el(TextareaControl, {
        label: 'Contenido en Markdown',
        value: a.markdownContent || '',
        onChange: function(v){ set({ markdownContent: v }); },
        rows: 16,
        style: { fontFamily:"'Fira Code','Cascadia Code',monospace", fontSize:13, background:'#1a1a1a', color:'#e0e0e0', border:'1px solid #2a2a2a', borderRadius:3 },
        placeholder: '# Título\n\nEscribe aquí en **Markdown**...\n\n- Elemento 1\n- Elemento 2',
      }),
      el('p', { style:{ fontSize:11, color:'#888', marginTop:4, marginBottom:12 } },
        'Soporta: encabezados, negrita, cursiva, listas, tablas, código, enlaces, citas, separadores.'
      ),
      el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.08em', textTransform:'uppercase', color:'#555', marginBottom:6 } }, 'Vista previa'),
      el(MarkdownPreview, { content: a.markdownContent, style: previewStyle })
    );
  }

  /* ══════════════════════════════════════════════
     BLOQUE 1: enterprise/markdown (simple)
  ══════════════════════════════════════════════ */
  wp.blocks.registerBlockType('enterprise/markdown', {
    title:       'Markdown',
    description: 'Renderiza contenido Markdown heredando los estilos del tema.',
    icon:        'editor-code',
    category:    'enterprise-moto',
    keywords:    ['markdown', 'texto', 'md'],
    supports:    { html: false, align: ['wide', 'full'] },
    attributes: {
      markdownContent: { type: 'string', default: '' },
    },

    edit: function(props) {
      return el(Frag, null,
        el(InspectorControls, null,
          el(PanelBody, { title: 'Markdown simple', initialOpen: true },
            el('p', { style:{ fontSize:12, color:'#888', lineHeight:1.6 } },
              'El HTML resultante hereda directamente los estilos del tema. Para opciones de estilo usa el bloque "Markdown con estilo".'
            )
          )
        ),
        el('div', useBlockProps({ style:{ padding:'12px 0' } }),
          el(MarkdownEditor, { attributes: props.attributes, setAttributes: props.setAttributes })
        )
      );
    },

    save: function(){ return null; },
  });

  /* ══════════════════════════════════════════════
     BLOQUE 2: enterprise/markdown-styled
  ══════════════════════════════════════════════ */
  var FONT_OPTIONS = [
    { label: 'DM Sans (cuerpo)',            value: 'dm-sans'  },
    { label: 'DM Serif Display (serif)',    value: 'dm-serif' },
    { label: 'Bebas Neue (display)',        value: 'bebas'    },
  ];
  var FONT_MAP = {
    'bebas':    "'Bebas Neue', sans-serif",
    'dm-sans':  "'DM Sans', sans-serif",
    'dm-serif': "'DM Serif Display', serif",
  };
  var THEME_COLORS = [
    { name: 'Dorado',      color: '#f2c118' },
    { name: 'Blanco',      color: '#f0f0f0' },
    { name: 'Gris medio',  color: '#888888' },
    { name: 'Superficie',  color: '#1a1a1a' },
    { name: 'Negro',       color: '#0e0e0e' },
    { name: 'Rojo',        color: '#c0392b' },
    { name: 'Verde',       color: '#1a7a3a' },
    { name: 'Azul',        color: '#185FA5' },
  ];

  wp.blocks.registerBlockType('enterprise/markdown-styled', {
    title:       'Markdown con estilo',
    description: 'Renderiza Markdown con tipografía, colores, padding y borde configurables.',
    icon:        'editor-paragraph',
    category:    'enterprise-moto',
    keywords:    ['markdown', 'estilo', 'md', 'styled'],
    supports:    { html: false, align: ['wide', 'full'] },
    attributes: {
      markdownContent: { type: 'string',  default: ''        },
      fontFamily:      { type: 'string',  default: 'dm-sans' },
      fontSize:        { type: 'integer', default: 15        },
      textColor:       { type: 'string',  default: ''        },
      bgColor:         { type: 'string',  default: '#1a1a1a' },
      padding:         { type: 'integer', default: 24        },
      borderColor:     { type: 'string',  default: '#f2c118' },
      borderWidth:     { type: 'integer', default: 4         },
      showBorder:      { type: 'boolean', default: true      },
      customCss:       { type: 'string',  default: ''        },
    },

    edit: function(props) {
      var a   = props.attributes;
      var set = props.setAttributes;

      var previewStyle = {
        fontFamily: FONT_MAP[a.fontFamily] || FONT_MAP['dm-sans'],
        fontSize:   a.fontSize + 'px',
        background: a.bgColor  || 'transparent',
        color:      a.textColor || 'inherit',
        padding:    a.padding + 'px',
        borderLeft: a.showBorder && a.borderColor ? (a.borderWidth + 'px solid ' + a.borderColor) : 'none',
      };

      return el(Frag, null,

        el(InspectorControls, null,

          el(PanelBody, { title: 'Tipografía', initialOpen: true },
            el(SelectControl, {
              label: 'Familia tipográfica',
              value: a.fontFamily,
              options: FONT_OPTIONS,
              onChange: function(v){ set({ fontFamily: v }); },
            }),
            el(RangeControl, {
              label: 'Tamaño de texto (px)',
              value: a.fontSize,
              min: 11, max: 28, step: 1,
              onChange: function(v){ set({ fontSize: v }); },
            })
          ),

          el(PanelBody, { title: 'Colores', initialOpen: false },
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.08em', textTransform:'uppercase', color:'#555', marginBottom:6 } }, 'Color de texto'),
            el(co.ColorPalette, {
              colors: THEME_COLORS,
              value:  a.textColor,
              onChange: function(v){ set({ textColor: v || '' }); },
              clearable: true,
            }),
            el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.08em', textTransform:'uppercase', color:'#555', margin:'16px 0 6px' } }, 'Color de fondo'),
            el(co.ColorPalette, {
              colors: THEME_COLORS,
              value:  a.bgColor,
              onChange: function(v){ set({ bgColor: v || '' }); },
              clearable: true,
            })
          ),

          el(PanelBody, { title: 'Contenedor', initialOpen: false },
            el(RangeControl, {
              label: 'Padding interior (px)',
              value: a.padding,
              min: 0, max: 64, step: 4,
              onChange: function(v){ set({ padding: v }); },
            }),
            el(ToggleControl, {
              label: 'Borde lateral',
              checked: a.showBorder,
              onChange: function(v){ set({ showBorder: v }); },
            }),
            a.showBorder && el(Frag, null,
              el('p', { style:{ fontSize:11, fontWeight:700, letterSpacing:'.08em', textTransform:'uppercase', color:'#555', marginBottom:6 } }, 'Color del borde'),
              el(co.ColorPalette, {
                colors: THEME_COLORS,
                value:  a.borderColor,
                onChange: function(v){ set({ borderColor: v || '#f2c118' }); },
                clearable: false,
              }),
              el(RangeControl, {
                label: 'Grosor del borde (px)',
                value: a.borderWidth,
                min: 1, max: 12, step: 1,
                onChange: function(v){ set({ borderWidth: v }); },
              })
            )
          ),

          el(PanelBody, { title: 'CSS adicional', initialOpen: false },
            el('p', { style:{ fontSize:11, color:'#888', marginBottom:8, lineHeight:1.6 } },
              'Usa .ent-markdown--styled como selector raíz para apuntar a elementos internos.'
            ),
            el('pre', { style:{ fontSize:10, background:'#f6f6f6', padding:'8px', borderRadius:3, overflow:'auto', marginBottom:8, lineHeight:1.5, color:'#333' } },
              '.ent-markdown--styled th {\n  background: #c0392b;\n  color: #fff;\n}\n.ent-markdown--styled td {\n  border-color: #555;\n}'
            ),
            el(TextareaControl, {
              label: '',
              value: a.customCss || '',
              onChange: function(v){ set({ customCss: v }); },
              rows: 8,
              placeholder: '.ent-markdown--styled th {\n  background: #c0392b;\n  color: #fff;\n}',
            })
          )
        ),

        el('div', useBlockProps({ style:{ padding:'12px 0' } }),
          el(MarkdownEditor, {
            attributes:    props.attributes,
            setAttributes: props.setAttributes,
            previewStyle:  previewStyle,
          })
        )
      );
    },

    save: function(){ return null; },
  });

})();
