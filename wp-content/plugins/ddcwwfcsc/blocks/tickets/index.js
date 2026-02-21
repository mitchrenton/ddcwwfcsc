( function ( blocks, element, blockEditor ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType( 'ddcwwfcsc/tickets', {
        edit: function ( props ) {
            var blockProps = useBlockProps();
            return el(
                'div',
                blockProps,
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
                            className: 'dashicons dashicons-tickets',
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
                        'DDCWWFCSC Tickets'
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
                        'Displays fixtures currently on sale with ticket request forms.'
                    )
                )
            );
        },
        save: function () {
            return null; // Server-side rendered.
        },
    } );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor );
