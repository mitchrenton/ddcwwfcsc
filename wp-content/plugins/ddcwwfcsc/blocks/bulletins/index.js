( function ( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;

    blocks.registerBlockType( 'ddcwwfcsc/bulletins', {
        edit: function ( props ) {
            var blockProps = useBlockProps();
            var limit = props.attributes.limit;
            var speed = props.attributes.speed;

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Bulletin Ticker Settings' },
                        el( RangeControl, {
                            label: 'Number of bulletins',
                            value: limit,
                            onChange: function ( val ) {
                                props.setAttributes( { limit: val } );
                            },
                            min: 1,
                            max: 50,
                        } ),
                        el( RangeControl, {
                            label: 'Scroll speed (px/s)',
                            value: speed,
                            onChange: function ( val ) {
                                props.setAttributes( { speed: val } );
                            },
                            min: 5,
                            max: 120,
                        } )
                    )
                ),
                el(
                    'div',
                    {
                        style: {
                            border: '2px dashed #FDB913',
                            borderRadius: '8px',
                            padding: '30px 20px',
                            textAlign: 'center',
                            backgroundColor: '#fffdf0',
                        },
                    },
                    el(
                        'span',
                        {
                            className: 'dashicons dashicons-megaphone',
                            style: {
                                fontSize: '36px',
                                width: '36px',
                                height: '36px',
                                marginBottom: '10px',
                                display: 'block',
                                color: '#FDB913',
                            },
                        }
                    ),
                    el(
                        'p',
                        {
                            style: {
                                fontSize: '16px',
                                fontWeight: 'bold',
                                margin: '10px 0 5px',
                            },
                        },
                        'DDCWWFCSC Bulletin Ticker'
                    ),
                    el(
                        'p',
                        {
                            style: {
                                fontSize: '13px',
                                color: '#666',
                                margin: 0,
                            },
                        },
                        'Showing ' + limit + ' bulletins at ' + speed + ' px/s'
                    )
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
