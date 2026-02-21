/**
 * Beerwolf Google Maps initialisation.
 *
 * Called as the Google Maps API callback via &callback=ddcwwfcscInitMaps.
 */
function ddcwwfcscInitMaps() {
    var containers = document.querySelectorAll( '.ddcwwfcsc-beerwolf-map' );

    containers.forEach( function( container ) {
        var pubs;
        try {
            pubs = JSON.parse( container.getAttribute( 'data-pubs' ) );
        } catch ( e ) {
            return;
        }

        if ( ! pubs || ! pubs.length ) {
            return;
        }

        var map = new google.maps.Map( container, {
            zoom: 14,
            mapTypeControl: false,
            streetViewControl: false,
        } );

        var bounds = new google.maps.LatLngBounds();
        var infoWindow = new google.maps.InfoWindow();

        // Gold marker SVG icon.
        var goldIcon = {
            path: google.maps.SymbolPath.CIRCLE,
            fillColor: '#d4a843',
            fillOpacity: 1,
            strokeColor: '#b8912e',
            strokeWeight: 2,
            scale: 10,
        };

        pubs.forEach( function( pub ) {
            var position = { lat: pub.lat, lng: pub.lng };
            bounds.extend( position );

            var marker = new google.maps.Marker( {
                position: position,
                map: map,
                icon: goldIcon,
                title: pub.name,
            } );

            var contentParts = [
                '<div style="color:#222;font-size:14px;line-height:1.5;padding:4px 0;">',
                '<strong style="font-size:15px;">' + pub.name + '</strong>',
            ];
            if ( pub.address ) {
                contentParts.push( '<br><span style="color:#555;">' + pub.address + '</span>' );
            }
            if ( pub.distance ) {
                contentParts.push( '<br><em style="color:#d4a843;">' + pub.distance + ' from the ground</em>' );
            }
            contentParts.push( '</div>' );

            marker.addListener( 'click', function() {
                infoWindow.setContent( contentParts.join( '' ) );
                infoWindow.open( map, marker );
            } );
        } );

        map.fitBounds( bounds );

        // If only one marker, don't zoom in too far.
        if ( pubs.length === 1 ) {
            google.maps.event.addListenerOnce( map, 'bounds_changed', function() {
                if ( map.getZoom() > 16 ) {
                    map.setZoom( 16 );
                }
            } );
        }
    } );
}
