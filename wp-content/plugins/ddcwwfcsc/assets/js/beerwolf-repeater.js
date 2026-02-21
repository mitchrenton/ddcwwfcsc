( function( $ ) {
    'use strict';

    var $repeater = $( '#ddcwwfcsc-pubs-repeater' );

    /**
     * Attach Google Places Autocomplete to the address input within a fieldset.
     */
    function initAutocomplete( $fieldset ) {
        if ( typeof google === 'undefined' || ! google.maps || ! google.maps.places ) {
            return;
        }

        var input = $fieldset.find( '.ddcwwfcsc-pub-address' ).get( 0 );
        if ( ! input ) {
            return;
        }

        var autocomplete = new google.maps.places.Autocomplete( input, {
            types: [ 'establishment', 'geocode' ],
        } );

        autocomplete.addListener( 'place_changed', function() {
            var place = autocomplete.getPlace();
            if ( ! place.geometry || ! place.geometry.location ) {
                return;
            }

            $fieldset.find( '[name$="[lat]"]' ).val( place.geometry.location.lat() );
            $fieldset.find( '[name$="[lng]"]' ).val( place.geometry.location.lng() );

            if ( place.formatted_address ) {
                $( input ).val( place.formatted_address );
            }
        } );
    }

    // Init autocomplete on existing fieldsets.
    $repeater.find( '.ddcwwfcsc-pub-fieldset' ).each( function() {
        initAutocomplete( $( this ) );
    } );

    /**
     * Renumber pub headers and input names after add/remove/reorder.
     */
    function renumber() {
        $repeater.find( '.ddcwwfcsc-pub-fieldset' ).each( function( i ) {
            var $fieldset = $( this );
            $fieldset.find( '.ddcwwfcsc-pub-number' ).text( 'Pub #' + ( i + 1 ) );

            // Update input/textarea/select name attributes.
            $fieldset.find( '[name]' ).each( function() {
                var name = $( this ).attr( 'name' );
                $( this ).attr( 'name', name.replace( /ddcwwfcsc_pubs\[\d+\]/, 'ddcwwfcsc_pubs[' + i + ']' ) );
            } );
        } );
    }

    // Add new pub.
    $( '#ddcwwfcsc-add-pub' ).on( 'click', function() {
        var count = $repeater.find( '.ddcwwfcsc-pub-fieldset' ).length;
        var template = wp.template( 'ddcwwfcsc-pub-fieldset' );
        $repeater.append( template( { index: count } ) );
        renumber();
        initAutocomplete( $repeater.find( '.ddcwwfcsc-pub-fieldset' ).last() );
    } );

    // Remove pub.
    $repeater.on( 'click', '.ddcwwfcsc-pub-remove', function() {
        $( this ).closest( '.ddcwwfcsc-pub-fieldset' ).remove();
        renumber();
    } );

    // Move up.
    $repeater.on( 'click', '.ddcwwfcsc-pub-move-up', function() {
        var $fieldset = $( this ).closest( '.ddcwwfcsc-pub-fieldset' );
        var $prev = $fieldset.prev( '.ddcwwfcsc-pub-fieldset' );
        if ( $prev.length ) {
            $fieldset.insertBefore( $prev );
            renumber();
        }
    } );

    // Move down.
    $repeater.on( 'click', '.ddcwwfcsc-pub-move-down', function() {
        var $fieldset = $( this ).closest( '.ddcwwfcsc-pub-fieldset' );
        var $next = $fieldset.next( '.ddcwwfcsc-pub-fieldset' );
        if ( $next.length ) {
            $fieldset.insertAfter( $next );
            renumber();
        }
    } );

    // Image upload (delegated — each pub gets its own frame).
    $repeater.on( 'click', '.ddcwwfcsc-pub-image-upload', function( e ) {
        e.preventDefault();
        var $button = $( this );
        var $fieldset = $button.closest( '.ddcwwfcsc-pub-fieldset' );
        var $input = $fieldset.find( '.ddcwwfcsc-pub-image-id' );
        var $preview = $fieldset.find( '.ddcwwfcsc-pub-image-preview' );
        var $remove = $fieldset.find( '.ddcwwfcsc-pub-image-remove' );

        var frame = wp.media( {
            title: 'Select Pub Image',
            multiple: false,
            library: { type: 'image' },
        } );

        frame.on( 'select', function() {
            var attachment = frame.state().get( 'selection' ).first().toJSON();
            var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            $input.val( attachment.id );
            $preview.html( '<img src="' + url + '">' );
            $remove.show();
        } );

        frame.open();
    } );

    // Image remove.
    $repeater.on( 'click', '.ddcwwfcsc-pub-image-remove', function( e ) {
        e.preventDefault();
        var $fieldset = $( this ).closest( '.ddcwwfcsc-pub-fieldset' );
        $fieldset.find( '.ddcwwfcsc-pub-image-id' ).val( '' );
        $fieldset.find( '.ddcwwfcsc-pub-image-preview' ).empty();
        $( this ).hide();
    } );

} )( jQuery );
