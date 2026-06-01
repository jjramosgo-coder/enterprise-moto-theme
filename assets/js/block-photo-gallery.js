/**
 * enterprise/photo-gallery — Block editor (Gutenberg)
 * Carrusel de fotos con autoplay, tamaño de imagen y lightbox.
 */
( function ( blocks, element, blockEditor, components, data ) {
    'use strict';

    var el        = element.createElement;
    var Fragment  = element.Fragment;
    var useState  = element.useState;
    var InspectorControls = blockEditor.InspectorControls;
    var MediaUpload       = blockEditor.MediaUpload;
    var MediaUploadCheck  = blockEditor.MediaUploadCheck;
    var useBlockProps     = blockEditor.useBlockProps;
    var PanelBody   = components.PanelBody;
    var PanelRow    = components.PanelRow;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var RangeControl  = components.RangeControl;
    var Button        = components.Button;
    var ServerSideRender = window.wp && window.wp.serverSideRender
        ? window.wp.serverSideRender
        : components.ServerSideRender;

    blocks.registerBlockType( 'enterprise/photo-gallery', {
        title:       'Carrusel de fotos',
        description: 'Carrusel de imágenes con autoplay, tamaño y lightbox.',
        icon:        'format-gallery',
        category:    'enterprise-moto',
        supports:    { html: false, align: [ 'wide', 'full' ] },
        attributes: {
            imageIds:      { type: 'array',   default: [],         items: { type: 'integer' } },
            heading:       { type: 'string',  default: ''          },
            autoplay:      { type: 'boolean', default: false       },
            autoplayDelay: { type: 'integer', default: 4000        },
            imageSize:     { type: 'string',  default: 'large'     },
            showCaptions:  { type: 'boolean', default: true        },
            containerRatio:{ type: 'string',  default: '16/9'      },
        },

        edit: function ( props ) {
            var attrs    = props.attributes;
            var setAttrs = props.setAttributes;

            // Obtener URLs de preview de las imágenes seleccionadas
            var imgPreviews = attrs.imageIds.map( function ( id ) {
                var img = document.querySelector( '[data-pg-preview="' + id + '"]' );
                return img ? img.src : null;
            } );

            return el( Fragment, null,

                // ── Inspector ──────────────────────────────────────
                el( InspectorControls, null,

                    el( PanelBody, { title: 'Imágenes', initialOpen: true },
                        el( PanelRow, null,
                            el( MediaUploadCheck, null,
                                el( MediaUpload, {
                                    onSelect: function ( media ) {
                                        setAttrs( { imageIds: media.map( function ( m ) { return m.id; } ) } );
                                    },
                                    allowedTypes: [ 'image' ],
                                    multiple: 'add',
                                    gallery: false,
                                    value: attrs.imageIds,
                                    render: function ( _ref ) {
                                        var open = _ref.open;
                                        return el( Button, {
                                            onClick: open,
                                            variant: 'secondary',
                                            style: { width: '100%' },
                                        }, attrs.imageIds.length
                                            ? 'Editar imágenes (' + attrs.imageIds.length + ')'
                                            : '+ Seleccionar imágenes' );
                                    },
                                } )
                            )
                        ),
                        attrs.imageIds.length > 0 && el( PanelRow, null,
                            el( Button, {
                                variant: 'tertiary',
                                isDestructive: true,
                                onClick: function () { setAttrs( { imageIds: [] } ); },
                            }, 'Eliminar todas' )
                        )
                    ),

                    el( PanelBody, { title: 'Presentación', initialOpen: true },
                        el( TextControl, {
                            label: 'Título (opcional)',
                            value: attrs.heading,
                            onChange: function ( v ) { setAttrs( { heading: v } ); },
                        } ),
                        el( SelectControl, {
                            label: 'Proporción del contenedor',
                            value: attrs.containerRatio,
                            options: [
                                { label: '16:9 — Apaisado (vídeo/paisaje)',  value: '16/9'     },
                                { label: '4:3 — Apaisado clásico',           value: '4/3'      },
                                { label: '1:1 — Cuadrado',                   value: '1/1'      },
                                { label: '3:4 — Vertical moderado',          value: '3/4'      },
                                { label: '9:16 — Vertical (móvil/retrato)',   value: '9/16'     },
                                { label: 'Adaptativo — se ajusta a la foto', value: 'adaptive' },
                            ],
                            onChange: function ( v ) { setAttrs( { containerRatio: v } ); },
                            help: attrs.containerRatio === 'adaptive'
                                ? 'El contenedor se adapta a la altura de cada imagen.'
                                : 'Ratio fijo. Usa "Adaptativo" para fotos verticales sin recorte.',
                        } ),
                        el( SelectControl, {
                            label: 'Tamaño de imagen',
                            value: attrs.imageSize,
                            options: [
                                { label: 'Miniatura',       value: 'thumbnail' },
                                { label: 'Medio',           value: 'medium'    },
                                { label: 'Grande',          value: 'large'     },
                                { label: 'Tamaño completo', value: 'full'      },
                            ],
                            onChange: function ( v ) { setAttrs( { imageSize: v } ); },
                        } ),
                        el( ToggleControl, {
                            label: 'Mostrar subtítulos de imagen',
                            checked: attrs.showCaptions,
                            onChange: function ( v ) { setAttrs( { showCaptions: v } ); },
                        } )
                    ),

                    el( PanelBody, { title: 'Reproducción', initialOpen: false },
                        el( ToggleControl, {
                            label: 'Reproducción automática',
                            checked: attrs.autoplay,
                            onChange: function ( v ) { setAttrs( { autoplay: v } ); },
                        } ),
                        attrs.autoplay && el( RangeControl, {
                            label: 'Intervalo (segundos)',
                            value: Math.round( attrs.autoplayDelay / 1000 ),
                            min: 1, max: 15,
                            onChange: function ( v ) { setAttrs( { autoplayDelay: v * 1000 } ); },
                        } )
                    )
                ),

                // ── Vista previa en el editor ──────────────────────
                el( 'div', useBlockProps( { className: 'ent-gallery-editor-preview' } ),
                    attrs.imageIds.length === 0
                        ? el( 'div', { style: { padding: '24px', background: '#fff8e1', borderLeft: '3px solid #f2c118', fontSize: '14px', color: '#555' } },
                            '📷 Carrusel de fotos — selecciona imágenes en el panel lateral.'
                          )
                        : el( ServerSideRender, {
                            block: 'enterprise/photo-gallery',
                            attributes: attrs,
                          } )
                )
            );
        },

        save: function () { return null; }, // render_callback en PHP
    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.data
);
