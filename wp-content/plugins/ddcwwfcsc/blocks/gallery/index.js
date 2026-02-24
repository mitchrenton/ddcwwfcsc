( function ( blocks, element, blockEditor, components ) {
    var el               = element.createElement;
    var useBlockProps    = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var MediaUpload      = blockEditor.MediaUpload;
    var MediaUploadCheck = blockEditor.MediaUploadCheck;
    var PanelBody        = components.PanelBody;
    var RangeControl     = components.RangeControl;
    var Button           = components.Button;

    blocks.registerBlockType( 'ddcwwfcsc/gallery', {

        edit: function ( props ) {
            var images     = props.attributes.images  || [];
            var columns    = props.attributes.columns || 3;
            var blockProps = useBlockProps();

            /**
             * Called by MediaUpload when the user confirms their selection.
             * With multiple: true, `selection` is an array of attachment objects.
             */
            function onSelectImages( selection ) {
                var attachments = Array.isArray( selection ) ? selection : [ selection ];

                var newImages = attachments.map( function ( attachment ) {
                    var sizes   = attachment.sizes || {};
                    var thumbUrl = (
                        ( sizes.large        || {} ).url ||
                        ( sizes.medium_large || {} ).url ||
                        ( sizes.medium       || {} ).url ||
                        ( sizes.full         || {} ).url ||
                        attachment.url
                    );
                    var fullUrl = ( sizes.full || {} ).url || attachment.url;

                    return {
                        id:      attachment.id,
                        url:     thumbUrl,
                        fullUrl: fullUrl,
                        alt:     attachment.alt     || '',
                        caption: attachment.caption || '',
                    };
                } );

                props.setAttributes( { images: images.concat( newImages ) } );
            }

            function removeImage( index ) {
                props.setAttributes( {
                    images: images.filter( function ( _, i ) { return i !== index; } ),
                } );
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
                        min:      2,
                        max:      4,
                    } )
                )
            );

            // Empty state — show upload placeholder.
            if ( images.length === 0 ) {
                return el(
                    'div',
                    blockProps,
                    inspector,
                    el( MediaUploadCheck, null,
                        el( MediaUpload, {
                            onSelect:     onSelectImages,
                            allowedTypes: [ 'image' ],
                            multiple:     true,
                            render: function ( obj ) {
                                return el(
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
                                        onClick: obj.open,
                                    },
                                    el( 'span', {
                                        className: 'dashicons dashicons-format-gallery',
                                        style: {
                                            fontSize:  '40px',
                                            width:     '40px',
                                            height:    '40px',
                                            display:   'block',
                                            margin:    '0 auto 12px',
                                            color:     '#FDB913',
                                        },
                                    } ),
                                    el( 'p', { style: { fontWeight: '600', margin: '0 0 6px', fontSize: '15px' } }, 'Image Gallery' ),
                                    el( 'p', { style: { color: '#666', margin: '0 0 16px', fontSize: '13px' } }, 'Click to select images — hold Shift or Ctrl to pick multiple' ),
                                    el( Button, { variant: 'primary', onClick: obj.open }, 'Add Images' )
                                );
                            },
                        } )
                    )
                );
            }

            // Grid preview with remove buttons.
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
                            position:  'absolute',
                            inset:     0,
                            width:     '100%',
                            height:    '100%',
                            objectFit: 'cover',
                            display:   'block',
                        },
                    } ),
                    el(
                        'button',
                        {
                            onClick: function ( e ) { e.stopPropagation(); removeImage( idx ); },
                            title:   'Remove image',
                            style: {
                                position:       'absolute',
                                top:            '4px',
                                right:          '4px',
                                width:          '24px',
                                height:         '24px',
                                border:         'none',
                                borderRadius:   '50%',
                                background:     'rgba(0,0,0,0.6)',
                                color:          '#fff',
                                cursor:         'pointer',
                                display:        'flex',
                                alignItems:     'center',
                                justifyContent: 'center',
                                fontSize:       '14px',
                                lineHeight:     1,
                                padding:        0,
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
                            display:             'grid',
                            gridTemplateColumns: 'repeat(' + columns + ', 1fr)',
                            gap:                 '8px',
                            marginBottom:        '12px',
                        },
                    },
                    gridItems
                ),
                el( MediaUploadCheck, null,
                    el( MediaUpload, {
                        onSelect:     onSelectImages,
                        allowedTypes: [ 'image' ],
                        multiple:     true,
                        render: function ( obj ) {
                            return el( Button, { variant: 'secondary', onClick: obj.open }, '+ Add More Images' );
                        },
                    } )
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
