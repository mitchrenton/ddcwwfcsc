( function ( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;

    blocks.registerBlockType( 'ddcwwfcsc/honorary-members', {
        edit: function ( props ) {
            var blockProps = useBlockProps();
            var columns = props.attributes.columns;
            var limit = props.attributes.limit;

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Honorary Members Settings' },
                        el( SelectControl, {
                            label: 'Columns',
                            value: String( columns ),
                            options: [
                                { label: '2 Columns', value: '2' },
                                { label: '3 Columns', value: '3' },
                                { label: '4 Columns', value: '4' },
                            ],
                            onChange: function ( val ) {
                                props.setAttributes( { columns: parseInt( val, 10 ) } );
                            },
                        } ),
                        el( RangeControl, {
                            label: 'Limit (0 = show all)',
                            value: limit,
                            onChange: function ( val ) {
                                props.setAttributes( { limit: val } );
                            },
                            min: 0,
                            max: 20,
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
                            className: 'dashicons dashicons-awards',
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
                        'DDCWWFCSC Honorary Members'
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
                        columns + ' columns' + ( limit ? ', showing ' + limit : ', showing all' )
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
