( function ( $ ) {
    'use strict';

    var frame;

    $( document ).on( 'click', '#ddcwwfcsc-badge-upload', function ( e ) {
        e.preventDefault();

        if ( frame ) {
            frame.open();
            return;
        }

        frame = wp.media( {
            title: 'Select Badge Image',
            button: { text: 'Use as Badge' },
            multiple: false,
            library: { type: 'image' },
        } );

        frame.on( 'select', function () {
            var attachment = frame.state().get( 'selection' ).first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            $( '#ddcwwfcsc-badge-id' ).val( attachment.id );
            $( '#ddcwwfcsc-badge-preview' ).html(
                '<img src="' + url + '" style="max-width:80px;max-height:80px;">'
            );
            $( '#ddcwwfcsc-badge-remove' ).show();
        } );

        frame.open();
    } );

    $( document ).on( 'click', '#ddcwwfcsc-badge-remove', function ( e ) {
        e.preventDefault();
        $( '#ddcwwfcsc-badge-id' ).val( '' );
        $( '#ddcwwfcsc-badge-preview' ).html( '' );
        $( this ).hide();
    } );

    // After adding a new term, reset the badge fields.
    $( document ).ajaxSuccess( function ( e, xhr, settings ) {
        if (
            settings.data &&
            typeof settings.data === 'string' &&
            settings.data.indexOf( 'action=add-tag' ) !== -1 &&
            settings.data.indexOf( 'ddcwwfcsc_opponent' ) !== -1
        ) {
            $( '#ddcwwfcsc-badge-id' ).val( '' );
            $( '#ddcwwfcsc-badge-preview' ).html( '' );
            $( '#ddcwwfcsc-badge-remove' ).hide();
        }
    } );
} )( jQuery );
