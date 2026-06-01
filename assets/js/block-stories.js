/**
 * enterprise/stories — Block editor (Gutenberg)
 * Historias verticales estilo WhatsApp/Instagram Stories.
 */
( function ( blocks, element, blockEditor, components ) {
    'use strict';

    var el        = element.createElement;
    var Fragment  = element.Fragment;
    var useState  = element.useState;
    var InspectorControls = blockEditor.InspectorControls;
    var MediaUpload       = blockEditor.MediaUpload;
    var MediaUploadCheck  = blockEditor.MediaUploadCheck;
    var useBlockProps     = blockEditor.useBlockProps;
    var PanelBody    = components.PanelBody;
    var PanelRow     = components.PanelRow;
    var TextControl  = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var RangeControl  = components.RangeControl;
    var Button        = components.Button;
    var SelectControl = components.SelectControl;
    var ServerSideRender = window.wp && window.wp.serverSideRender
        ? window.wp.serverSideRender
        : components.ServerSideRender;

    // ── Subcomponente: editor de un item individual ────────────────────────
    function StoryItemEditor( _ref ) {
        var item     = _ref.item;
        var index    = _ref.index;
        var onChange = _ref.onChange;
        var onRemove = _ref.onRemove;

        return el( 'div', { style: { border: '1px solid #ddd', borderRadius: '4px', padding: '12px', marginBottom: '10px', background: '#fafafa' } },
            el( 'div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' } },
                el( 'strong', { style: { fontSize: '12px' } }, 'Historia ' + ( index + 1 ) ),
                el( Button, { variant: 'tertiary', isDestructive: true, isSmall: true, onClick: onRemove }, '✕ Eliminar' )
            ),
            el( SelectControl, {
                label: 'Tipo',
                value: item.type || 'image',
                options: [
                    { label: '📷 Imagen', value: 'image' },
                    { label: '🎬 Vídeo',  value: 'video' },
                ],
                onChange: function ( v ) { onChange( Object.assign( {}, item, { type: v } ) ); },
            } ),
            el( TextControl, {
                label: 'Etiqueta / título',
                value: item.label || '',
                placeholder: 'Ej: Día 1 – Salida',
                onChange: function ( v ) { onChange( Object.assign( {}, item, { label: v } ) ); },
            } ),
            ( item.type || 'image' ) === 'image'
                ? el( PanelRow, null,
                    el( MediaUploadCheck, null,
                        el( MediaUpload, {
                            onSelect: function ( media ) {
                                onChange( Object.assign( {}, item, { imageId: media.id, imageUrl: media.url } ) );
                            },
                            allowedTypes: [ 'image' ],
                            value: item.imageId || 0,
                            render: function ( _r ) {
                                return el( Button, { onClick: _r.open, variant: 'secondary', isSmall: true },
                                    item.imageUrl ? '🖼 Cambiar imagen' : '+ Seleccionar imagen'
                                );
                            },
                        } )
                    ),
                    item.imageUrl && el( 'img', {
                        src: item.imageUrl,
                        style: { width: '80px', height: '60px', objectFit: 'cover', marginLeft: '8px', borderRadius: '3px' },
                        alt: '',
                    } )
                  )
                : el( TextControl, {
                    label: 'URL del vídeo (mp4)',
                    value: item.videoUrl || '',
                    placeholder: 'https://...',
                    onChange: function ( v ) { onChange( Object.assign( {}, item, { videoUrl: v } ) ); },
                  } ),
            el( TextControl, {
                label: 'Subtítulo (opcional)',
                value: item.caption || '',
                onChange: function ( v ) { onChange( Object.assign( {}, item, { caption: v } ) ); },
            } )
        );
    }

    blocks.registerBlockType( 'enterprise/stories', {
        title:       'Stories',
        description: 'Historias verticales con imagen o vídeo, al estilo WhatsApp/Instagram.',
        icon:        'slides',
        category:    'enterprise-moto',
        supports:    { html: false, align: [ 'wide', 'full' ] },
        attributes: {
            items:    { type: 'array',   default: [],    items: { type: 'object' } },
            heading:  { type: 'string',  default: ''     },
            duration: { type: 'integer', default: 5000   },
            loop:     { type: 'boolean', default: false  },
        },

        edit: function ( props ) {
            var attrs    = props.attributes;
            var setAttrs = props.setAttributes;

            function updateItem( index, newItem ) {
                var newItems = attrs.items.slice();
                newItems[ index ] = newItem;
                setAttrs( { items: newItems } );
            }

            function removeItem( index ) {
                var newItems = attrs.items.filter( function ( _, i ) { return i !== index; } );
                setAttrs( { items: newItems } );
            }

            function addItem() {
                setAttrs( { items: attrs.items.concat( [ { type: 'image', label: '', imageId: 0, imageUrl: '', videoUrl: '', caption: '' } ] ) } );
            }

            return el( Fragment, null,

                el( InspectorControls, null,
                    el( PanelBody, { title: 'Stories', initialOpen: true },
                        el( TextControl, {
                            label: 'Título del grupo (opcional)',
                            value: attrs.heading,
                            onChange: function ( v ) { setAttrs( { heading: v } ); },
                        } ),
                        el( RangeControl, {
                            label: 'Duración por historia (segundos)',
                            value: Math.round( attrs.duration / 1000 ),
                            min: 2, max: 30,
                            onChange: function ( v ) { setAttrs( { duration: v * 1000 } ); },
                        } ),
                        el( ToggleControl, {
                            label: 'Bucle infinito',
                            checked: attrs.loop,
                            onChange: function ( v ) { setAttrs( { loop: v } ); },
                        } )
                    ),
                    el( PanelBody, { title: 'Contenido (' + attrs.items.length + ' historias)', initialOpen: true },
                        attrs.items.map( function ( item, i ) {
                            return el( StoryItemEditor, {
                                key: i,
                                item: item,
                                index: i,
                                onChange: function ( newItem ) { updateItem( i, newItem ); },
                                onRemove: function () { removeItem( i ); },
                            } );
                        } ),
                        el( Button, {
                            variant: 'primary',
                            onClick: addItem,
                            style: { width: '100%', justifyContent: 'center' },
                        }, '+ Añadir historia' )
                    )
                ),

                el( 'div', useBlockProps( { className: 'ent-stories-editor-preview' } ),
                    attrs.items.length === 0
                        ? el( 'div', { style: { padding: '24px', background: '#fff8e1', borderLeft: '3px solid #f2c118', fontSize: '14px', color: '#555' } },
                            '📖 Stories — añade historias en el panel lateral.'
                          )
                        : el( ServerSideRender, {
                            block: 'enterprise/stories',
                            attributes: attrs,
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
