/**
 * enterprise/tip-box — Block editor (Gutenberg)
 */
( function ( blocks, element, blockEditor, components ) {
    'use strict';

    var el                = element.createElement;
    var Fragment          = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var RichText          = blockEditor.RichText;
    var useBlockProps     = blockEditor.useBlockProps;
    var PanelBody         = components.PanelBody;
    var SelectControl     = components.SelectControl;
    var TextControl       = components.TextControl;

    var TYPE_CONFIG = {
        consejo:  { label: 'Consejo',  icon: 'ti-bulb',          color: '#0F6E56', bg: '#E1F5EE', border: '#1D9E75' },
        nota:     { label: 'Nota',     icon: 'ti-info-circle',   color: '#185FA5', bg: '#E6F1FB', border: '#378ADD' },
        atencion: { label: 'Atención', icon: 'ti-alert-triangle',color: '#854F0B', bg: '#FAEEDA', border: '#BA7517' },
        peligro:  { label: 'Peligro',  icon: 'ti-skull',         color: '#A32D2D', bg: '#FCEBEB', border: '#E24B4A' },
    };

    var ICON_OPTIONS = [
        { label: 'Auto (según tipo)',         value: 'auto'              },
        { label: 'Bombilla — consejo',         value: 'ti-bulb'           },
        { label: 'Info — nota',               value: 'ti-info-circle'    },
        { label: 'Triángulo — atención',      value: 'ti-alert-triangle' },
        { label: 'Calavera — peligro',        value: 'ti-skull'          },
        { label: 'Estrella',                   value: 'ti-star'           },
        { label: 'Mapa / ubicación',           value: 'ti-map-pin'        },
        { label: 'Gasolinera',                 value: 'ti-gas-station'    },
        { label: 'Herramienta',                value: 'ti-tool'           },
        { label: 'Candado — seguridad',        value: 'ti-lock'           },
        { label: 'Cámara',                     value: 'ti-camera'         },
        { label: 'Corazón',                    value: 'ti-heart'          },
        { label: 'Moto',                       value: 'ti-motorbike'      },
        { label: 'Tiempo / nube',              value: 'ti-cloud'          },
        { label: 'Dinero',                     value: 'ti-coin'           },
        { label: 'Teléfono / emergencias',     value: 'ti-phone-call'     },
        { label: 'Reloj',                      value: 'ti-clock'          },
        { label: 'Mochila / equipaje',         value: 'ti-backpack'       },
        { label: 'Brújula',                    value: 'ti-compass'        },
        { label: 'Bandera',                    value: 'ti-flag'           },
    ];

    blocks.registerBlockType( 'enterprise/tip-box', {
        title:       'Tip / Aviso',
        description: 'Bloque de consejo, nota, atención o peligro.',
        icon:        'warning',
        category:    'enterprise-moto',
        supports:    { html: false },
        attributes: {
            tipType:  { type: 'string', default: 'consejo' },
            tipText:  { type: 'string', default: ''        },
            tipTitle: { type: 'string', default: ''        },
            tipIcon:  { type: 'string', default: 'auto'    },
        },

        edit: function ( props ) {
            var attrs    = props.attributes;
            var setAttrs = props.setAttributes;
            var cfg      = TYPE_CONFIG[ attrs.tipType ] || TYPE_CONFIG.consejo;

            var resolvedIcon = ( attrs.tipIcon === 'auto' || !attrs.tipIcon )
                ? cfg.icon
                : attrs.tipIcon;

            var displayTitle = attrs.tipTitle || cfg.label;

            var blockProps = useBlockProps( {
                style: {
                    borderLeft: '4px solid ' + cfg.border,
                    background: cfg.bg,
                    padding: '16px 20px',
                    borderRadius: '0',
                    marginBottom: '4px',
                }
            } );

            return el( Fragment, null,

                el( InspectorControls, null,
                    el( PanelBody, { title: 'Tipo y apariencia', initialOpen: true },
                        el( SelectControl, {
                            label: 'Tipo de aviso',
                            value: attrs.tipType,
                            options: [
                                { label: 'Consejo',  value: 'consejo'  },
                                { label: 'Nota',     value: 'nota'     },
                                { label: 'Atención', value: 'atencion' },
                                { label: 'Peligro',  value: 'peligro'  },
                            ],
                            onChange: function ( v ) {
                                setAttrs( { tipType: v } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Icono',
                            value: attrs.tipIcon,
                            options: ICON_OPTIONS,
                            onChange: function ( v ) { setAttrs( { tipIcon: v } ); },
                        } ),
                        el( TextControl, {
                            label: 'Etiqueta personalizada (vacío = auto)',
                            value: attrs.tipTitle,
                            placeholder: cfg.label,
                            onChange: function ( v ) { setAttrs( { tipTitle: v } ); },
                        } )
                    )
                ),

                el( 'div', blockProps,
                    el( 'div', {
                            style: {
                                display: 'flex',
                                alignItems: 'center',
                                gap: '8px',
                                marginBottom: '8px',
                                color: cfg.color,
                                fontSize: '11px',
                                fontWeight: '700',
                                letterSpacing: '.1em',
                                textTransform: 'uppercase',
                            }
                        },
                        el( 'i', {
                            className: 'ti ' + resolvedIcon,
                            style: { fontSize: '22px' },
                            'aria-hidden': 'true',
                        } ),
                        el( 'span', null, displayTitle )
                    ),
                    el( RichText, {
                        tagName: 'p',
                        value: attrs.tipText,
                        onChange: function ( v ) { setAttrs( { tipText: v } ); },
                        placeholder: 'Escribe el contenido del aviso...',
                        style: {
                            margin: 0,
                            fontSize: '15px',
                            lineHeight: '1.65',
                            color: cfg.color,
                        },
                        allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
                    } )
                )
            );
        },

        save: function () { return null; },
    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components
);
