( function( blocks, element, blockEditor, components, data ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var SelectControl = components.SelectControl;
    var PanelBody = components.PanelBody;
    var Placeholder = components.Placeholder;
    var useSelect = data.useSelect;

    blocks.registerBlockType( 'ddcwwfcsc/beerwolf', {
        edit: function( props ) {
            var blockProps = useBlockProps();
            var opponentId = props.attributes.opponentId;

            var opponents = useSelect( function( select ) {
                return select( 'core' ).getEntityRecords( 'taxonomy', 'ddcwwfcsc_opponent', {
                    per_page: -1,
                    orderby: 'name',
                    order: 'asc',
                } );
            }, [] );

            var options = [ { label: '— Select Opponent —', value: 0 } ];
            if ( opponents ) {
                opponents.forEach( function( term ) {
                    options.push( { label: term.name, value: term.id } );
                } );
            }

            var selectedName = '';
            if ( opponentId && opponents ) {
                for ( var i = 0; i < opponents.length; i++ ) {
                    if ( opponents[ i ].id === opponentId ) {
                        selectedName = opponents[ i ].name;
                        break;
                    }
                }
            }

            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Beerwolf Settings' },
                        el( SelectControl, {
                            label: 'Opponent',
                            value: opponentId,
                            options: options,
                            onChange: function( val ) {
                                props.setAttributes( { opponentId: parseInt( val, 10 ) || 0 } );
                            },
                        } )
                    )
                ),
                el(
                    Placeholder,
                    {
                        icon: 'beer',
                        label: 'Beerwolf Pub Guide',
                    },
                    opponentId && selectedName
                        ? el( 'p', null, 'Showing pub guide for: ' + selectedName )
                        : el( 'p', null, 'Select an opponent in the block settings to display a pub guide.' )
                )
            );
        },
        save: function() {
            return null; // Server-side rendered.
        },
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.data
);
