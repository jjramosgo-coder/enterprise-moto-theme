/**
 * Enterprise Moto — block-routes-by-location.js
 * Bloque Gutenberg «Mapa de rutas por localización» (enterprise/routes-by-location).
 *
 * COMMIT 1 (registro): versión mínima para que el bloque aparezca en el insertador
 * y se inserte/guarde limpio. La gestión de localizaciones (Modal wp.components.Modal
 * + mapa OpenLayers + geocoder Nominatim + lista buscable/paginada) se añade en el
 * Commit 2, que REEMPLAZA este fichero por completo.
 *
 * Vanilla JS, sin herramientas de compilación.
 */
(function () {
  'use strict';

  if ( ! window.wp || ! wp.blocks || ! wp.element ) return;

  var el       = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var be       = wp.blockEditor;
  var co       = wp.components;

  var InspectorControls = be.InspectorControls;
  var useBlockProps     = be.useBlockProps;
  var PanelBody         = co.PanelBody;
  var TextControl       = co.TextControl;
  var SelectControl     = co.SelectControl;
  var ToggleControl     = co.ToggleControl;

  var blockIcon = el('svg', { viewBox:'0 0 24 24', xmlns:'http://www.w3.org/2000/svg' },
    el('path', { d:'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z',
      fill:'currentColor' })
  );

  /* ── Registro del bloque ── */
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
      var count      = (attr.markers||[]).length;

      return el(Fragment, null,

        el(InspectorControls, null,
          el(PanelBody, { title:'Configuración del mapa', initialOpen:true },
            el(TextControl, {
              label:'Título (opcional)',
              value:attr.heading,
              onChange:function(v){set({heading:v});},
              placeholder:'Ej: Rutas por localización',
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
              label:'Mostrar leyenda de localizaciones',
              checked:attr.showLegend,
              onChange:function(v){set({showLegend:v});},
            }),
            el(ToggleControl, {
              label:'Mostrar numeración',
              checked:attr.showNumbers,
              onChange:function(v){set({showNumbers:v});},
            })
          )
        ),

        el('div', blockProps,
          el('div', { className:'ent-map-editor-placeholder' },
            el('div', { className:'ent-map-editor-placeholder__icon' }, '🧭'),
            el('p',   { className:'ent-map-editor-placeholder__text' }, 'Mapa de rutas por localización'),
            el('p',   { className:'ent-map-editor-placeholder__hint' },
              count === 0
                ? 'La gestión de localizaciones (mapa y buscador) se añade en el siguiente paso.'
                : count + ' localización' + (count!==1?'es':'') + ' guardada' + (count!==1?'s':'') + ' — vista previa en el frontend')
          )
        )
      );
    },

    save: function(){ return null; },
  });

})();
