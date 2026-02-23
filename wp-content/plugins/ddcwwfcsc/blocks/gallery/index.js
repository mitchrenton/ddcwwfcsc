( function ( blocks, element, blockEditor, components ) {
    var el              = element.createElement;
    var useBlockProps   = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody       = components.PanelBody;
    var RangeControl    = components.RangeControl;
    var Button          = components.Button;

    /**
     * Open the WordPress media library and call onSelect with the updated image array.
     */
    function openMediaLibrary( currentImages, onSelect ) {
        var frame = wp.media( {
            title:   currentImages.length ? 'Add to Gallery' : 'Create Gallery',
            button:  { text: currentImages.length ? 'Add to Gallery' : 'Create Gallery' },
            multiple: true,
            library: { type: 'image' },
        } );

        frame.on( 'select', function () {
            var selection  = frame.state().get( 'selection' );
            var newImages  = [];

            selection.each( function ( attachment ) {
                var data  = attachment.toJSON();
                var sizes = data.sizes || {};
                var thumb = ( sizes.large || sizes.medium_large || sizes.medium || sizes.full || {} ).url || data.url;

                newImages.push( {
                    id:      data.id,
                    url:     thumb,
                    fullUrl: data.url,
                    alt:     data.alt     || '',
                    caption: data.caption || '',
                } );
            } );

            onSelect( currentImages.concat( newImages ) );
        } );

        frame.open();
    }

    blocks.registerBlockType( 'ddcwwfcsc/gallery', {

        edit: function ( props ) {
            var images  = props.attributes.images  || [];
            var columns = props.attributes.columns || 3;
            var blockProps = useBlockProps();

            function addImages() {
                openMediaLibrary( images, function ( updated ) {
                    props.setAttributes( { images: updated } );
                } );
            }

            function removeImage( index ) {
                var updated = images.filter( function ( _, i ) { return i !== index; } );
                props.setAttributes( { images: updated } );
            }

            // Inspector panel.
            var inspector = el(
                InspectorControls,
                null,
                el( PanelBody, { title: 'Gallery Settings', initialOpen: true },
                    el( RangeControl, {
                        label:    'Columns',
                        value:    columns,
                        onChange: function ( val ) { props.setAttributes( { columns: val } ); },
                        min: 2,
                        max: 4,
                    } )
                )
            );

            // Empty state.
            if ( images.length === 0 ) {
                return el(
                    'div',
                    blockProps,
                    inspector,
                    el(
                        'div',
                        {
                            style: {
                                border:          '2px dashed #FDB913',
                                borderRadius:    '8px',
                                padding:         '48px 24px',
                                textAlign:       'center',
                                backgroundColor: '#fffdf0',
                                cursor:          'pointer',
                            },
                            onClick: addImages,
                        },
                        el( 'span', {
                            className: 'dashicons dashicons-format-gallery',
                            style: {
                                fontSize:     '40px',
                                width:        '40px',
                                height:       '40px',
                                display:      'block',
                                margin:       '0 auto 12px',
                                color:        '#FDB913',
                            },
                        } ),
                        el( 'p', { style: { fontWeight: '600', margin: '0 0 6px', fontSize: '15px' } }, 'Image Gallery' ),
                        el( 'p', { style: { color: '#666', margin: '0 0 16px', fontSize: '13px' } }, 'Click to add images from the media library' ),
                        el( Button, { variant: 'primary', onClick: addImages }, 'Add Images' )
                    )
                );
            }

            // Grid preview.
            var gridItems = images.map( function ( image, idx ) {
                return el(
                    'div',
                    {
                        key:   image.id || idx,
                        style: {
                            position:        'relative',
                            paddingBottom:   '100%',
                            overflow:        'hidden',
                            borderRadius:    '4px',
                            backgroundColor: '#e0e0e0',
                        },
                    },
                    el( 'img', {
                        src:   image.url,
                        alt:   image.alt,
                        style: {
                            position:   'absolute',
                            inset:      0,
                            width:      '100%',
                            height:     '100%',
                            objectFit:  'cover',
                            display:    'block',
                        },
                    } ),
                    el(
                        'button',
                        {
                            onClick: function ( e ) { e.stopPropagation(); removeImage( idx ); },
                            title:   'Remove image',
                            style: {
                                position:        'absolute',
                                top:             '4px',
                                right:           '4px',
                                width:           '24px',
                                height:          '24px',
                                border:          'none',
                                borderRadius:    '50%',
                                background:      'rgba(0,0,0,0.6)',
                                color:           '#fff',
                                cursor:          'pointer',
                                display:         'flex',
                                alignItems:      'center',
                                justifyContent:  'center',
                                fontSize:        '14px',
                                lineHeight:      1,
                                padding:         0,
                            },
                        },
                        '×'
                    )
                );
            } );

            return el(
                'div',
                blockProps,
                inspector,
                el(
                    'div',
                    {
                        style: {
                            display:              'grid',
                            gridTemplateColumns:  'repeat(' + columns + ', 1fr)',
                            gap:                  '8px',
                            marginBottom:         '12px',
                        },
                    },
                    gridItems
                ),
                el(
                    'div',
                    { style: { display: 'flex', gap: '8px' } },
                    el( Button, { variant: 'secondary', onClick: addImages }, '+ Add More Images' )
                )
            );
        },

        save: function () {
            return null; // Server-side rendered.
        },
    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components
);
